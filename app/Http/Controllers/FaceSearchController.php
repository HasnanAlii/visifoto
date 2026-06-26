<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\Folder;
use App\Services\FaceSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class FaceSearchController extends Controller
{
    public function __construct(private readonly FaceSearchService $faceSearchService) {}

    /**
     * Halaman utama "Cari Foto Saya".
     */
    public function index(): View
    {
        $serviceStatus = $this->faceSearchService->getServiceStatus();

        // Statistik index per folder
        $folderStats = $this->getFolderIndexStats();

        return view('face-search.index', [
            'serviceOnline' => ($serviceStatus['status'] ?? '') === 'ok',
            'serviceStatus' => $serviceStatus,
            'folderStats' => $folderStats,
        ]);
    }

    /**
     * API: Cari foto berdasarkan foto referensi wajah.
     * POST /face-search/search
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'threshold' => ['nullable', 'numeric', 'min:0.1', 'max:1.0'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:100'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ], [
            'photo.required' => 'Foto wajah harus diunggah.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto tidak boleh lebih dari 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (! $this->faceSearchService->isServiceReady()) {
            return response()->json([
                'success' => false,
                'service_offline' => true,
                'message' => 'Layanan pengenalan wajah sedang tidak aktif.',
            ], 503);
        }

        $photo = $request->file('photo');
        $threshold = (float) ($request->input('threshold') ?? 0.70);
        $topK = (int) ($request->input('top_k') ?? 30);
        $folderId = $request->integer('folder_id') ?: null;

        $result = $this->faceSearchService->searchByFace($photo, $threshold, $topK, $folderId);

        if (! $result['success']) {
            $statusCode = isset($result['no_face']) && $result['no_face'] ? 422 : 500;

            return response()->json([
                'success' => false,
                'no_face' => $result['no_face'] ?? false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'matches' => $result['matches'],
            'total_found' => $result['total_found'],
            'total_checked' => $result['total_checked'],
            'threshold_used' => $result['threshold_used'],
        ]);
    }

    /**
     * API: Status Python FaceNet service.
     * GET /face-search/status
     */
    public function status(): JsonResponse
    {
        $status = $this->faceSearchService->getServiceStatus();

        return response()->json($status);
    }

    /**
     * API: Daftar folder dengan statistik index.
     * GET /face-search/folders
     */
    public function folders(): JsonResponse
    {
        return response()->json($this->getFolderIndexStats());
    }

    /**
     * API: Index foto-foto dalam satu folder tertentu.
     * Mengirim file satu-per-satu ke Python service via /index-single.
     * POST /face-search/index-folder
     */
    public function indexFolder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => ['required', 'exists:folders,id'],
            'include_subfolders' => ['nullable', 'boolean'],
            'force_reindex' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        if (! $this->faceSearchService->isServiceReady()) {
            return response()->json(['success' => false, 'message' => 'Python service tidak aktif.'], 503);
        }

        $folderId = $request->integer('folder_id');
        $includeSubfolders = $request->boolean('include_subfolders', true);
        $forceReindex = $request->boolean('force_reindex', false);

        // Kumpulkan semua folder ID yang perlu diproses
        $folderIds = $this->collectFolderIds($folderId, $includeSubfolders);

        // Ambil semua file dalam folder-folder tersebut
        $query = DriveFile::whereIn('folder_id', $folderIds)
            ->whereNotNull('mime_type')
            ->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('extension', ['jpg', 'jpeg', 'png', 'webp', 'bmp']);
            });

        // Jika tidak force re-index, skip file yang sudah ada embeddingnya
        if (! $forceReindex) {
            $alreadyIndexed = DB::table('face_embeddings')
                ->pluck('drive_file_id')
                ->toArray();
            if (! empty($alreadyIndexed)) {
                $query->whereNotIn('id', $alreadyIndexed);
            }
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua file di folder ini sudah terindeks.',
                'indexed' => 0,
                'with_face' => 0,
                'failed' => 0,
            ]);
        }

        // Proses indexing satu per satu
        $indexed = 0;
        $withFace = 0;
        $failed = 0;

        foreach ($files as $file) {
            $disk = 'public';
            $storagePath = $file->file_path;

            if (! Storage::disk($disk)->exists($storagePath)) {
                $failed++;

                continue;
            }

            $ok = $this->faceSearchService->indexSingleFile($file->id, $disk, $storagePath);

            if ($ok) {
                $indexed++;
                // Cek apakah wajah terdeteksi
                $hasEmbedding = DB::table('face_embeddings')
                    ->where('drive_file_id', $file->id)
                    ->whereRaw('JSON_LENGTH(embedding) > 0')
                    ->exists();
                if ($hasEmbedding) {
                    $withFace++;
                }
            } else {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Indexing selesai untuk {$indexed} file.",
            'total_files' => $files->count(),
            'indexed' => $indexed,
            'with_face' => $withFace,
            'failed' => $failed,
        ]);
    }

    /**
     * API: Trigger indexing semua foto di storage (admin only).
     * POST /face-search/index
     */
    public function triggerIndex(Request $request): JsonResponse
    {
        $async = $request->boolean('async', false);
        $subPath = $request->input('path');

        if ($async) {
            $result = $this->faceSearchService->indexFilesAsync($subPath);
        } else {
            $result = $this->faceSearchService->indexFiles($subPath);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * API: Hapus index Python (full re-index).
     * DELETE /face-search/index
     */
    public function clearIndex(): JsonResponse
    {
        $result = $this->faceSearchService->clearIndex();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Kumpulkan semua folder ID secara rekursif.
     */
    private function collectFolderIds(int $rootId, bool $includeSubfolders): array
    {
        $ids = [$rootId];

        if ($includeSubfolders) {
            $children = Folder::where('parent_id', $rootId)->pluck('id')->toArray();
            foreach ($children as $childId) {
                $ids = array_merge($ids, $this->collectFolderIds($childId, true));
            }
        }

        return $ids;
    }

    /**
     * Ambil statistik indexing per folder dari database.
     */
    private function getFolderIndexStats(): array
    {
        $stats = DB::select("
            SELECT
                f.id,
                f.name,
                f.parent_id,
                COUNT(df.id) AS total_files,
                COUNT(fe.id) AS indexed,
                SUM(CASE WHEN fe.id IS NOT NULL AND JSON_LENGTH(fe.embedding) > 0 THEN 1 ELSE 0 END) AS with_face
            FROM folders f
            LEFT JOIN drive_files df ON df.folder_id = f.id
                AND (df.mime_type LIKE 'image/%'
                     OR df.extension IN ('jpg','jpeg','png','webp','bmp'))
            LEFT JOIN face_embeddings fe ON fe.drive_file_id = df.id
            GROUP BY f.id, f.name, f.parent_id
            ORDER BY f.parent_id IS NULL DESC, f.name ASC
        ");

        return array_map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'parent_id' => $row->parent_id,
            'total_files' => (int) $row->total_files,
            'indexed' => (int) $row->indexed,
            'with_face' => (int) $row->with_face,
            'not_indexed' => max(0, (int) $row->total_files - (int) $row->indexed),
            'index_pct' => $row->total_files > 0
                ? round(($row->indexed / $row->total_files) * 100)
                : 0,
        ], $stats);
    }
}
