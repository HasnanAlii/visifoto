<?php

namespace App\Services;

use App\Models\DriveFile;
use App\Models\Folder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FaceSearchService
 *
 * Jembatan antara Laravel dan Python FaceNet microservice.
 */
class FaceSearchService
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.facenet.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (int) config('services.facenet.timeout', 60);
    }

    /** Cek apakah Python service aktif. */
    public function isServiceReady(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/status");

            return $response->successful() && ($response->json('status') === 'ok');
        } catch (\Exception) {
            return false;
        }
    }

    /** Ambil status lengkap service. */
    public function getServiceStatus(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/status");

            return $response->successful() ? $response->json() : ['status' => 'error', 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['status' => 'offline', 'message' => $e->getMessage()];
        }
    }

    /** Trigger indexing semua foto (sinkron). */
    public function indexFiles(?string $subPath = null): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/index-files", $subPath ? ['path' => $subPath] : []);

            if ($response->successful()) {
                return ['success' => true, 'result' => $response->json('result', [])];
            }

            return ['success' => false, 'message' => $response->body()];
        } catch (ConnectionException) {
            return ['success' => false, 'message' => 'Python service tidak berjalan.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Trigger indexing async (background). */
    public function indexFilesAsync(?string $subPath = null): array
    {
        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post("{$this->baseUrl}/index-files/async", $subPath ? ['path' => $subPath] : []);

            return $response->successful()
                ? ['success' => true, 'message' => $response->json('message')]
                : ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cari foto berdasarkan foto referensi wajah.
     *
     * @return array{ success: bool, matches?: array, total_found?: int, ... }
     */
    public function searchByFace(UploadedFile $queryImage, float $threshold = 0.70, int $topK = 20, ?int $folderId = null): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', $queryImage->get(), $queryImage->getClientOriginalName(), ['Content-Type' => $queryImage->getMimeType()])
                ->post("{$this->baseUrl}/search", [
                    'threshold' => $threshold,
                    'top_k' => $topK,
                ]);

            if ($response->status() === 422) {
                return [
                    'success' => false,
                    'no_face' => true,
                    'message' => $response->json('detail', 'Wajah tidak terdeteksi.'),
                ];
            }

            if (! $response->successful()) {
                return ['success' => false, 'message' => $response->json('detail', 'Gagal mencari foto.')];
            }

            $data = $response->json();
            $matches = $this->resolveMatches($data['matches'] ?? [], $folderId);

            return [
                'success' => true,
                'matches' => $matches,
                'total_found' => count($matches),
                'total_checked' => $data['total_checked'] ?? 0,
                'threshold_used' => $data['threshold_used'] ?? $threshold,
            ];
        } catch (ConnectionException) {
            return ['success' => false, 'message' => 'Python FaceNet service tidak berjalan.'];
        } catch (\Exception $e) {
            Log::error('FaceSearch error: '.$e->getMessage());

            return ['success' => false, 'message' => 'Terjadi kesalahan saat mencari foto.'];
        }
    }

    /**
     * Index satu file setelah di-upload.
     * Kirim file ke Python service beserta drive_file_id.
     */
    public function indexSingleFile(int $driveFileId, string $storageDisk, string $storagePath): bool
    {
        try {
            if (! Storage::disk($storageDisk)->exists($storagePath)) {
                return false;
            }

            $filePath = Storage::disk($storageDisk)->path($storagePath);
            $response = Http::timeout(30)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/index-single", [
                    'drive_file_id' => $driveFileId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("FaceNet: Gagal index file ID={$driveFileId}: ".$e->getMessage());

            return false;
        }
    }

    /** Hapus seluruh embedding (full re-index). */
    public function clearIndex(): array
    {
        try {
            $response = Http::timeout(10)->delete("{$this->baseUrl}/index");

            return ['success' => $response->successful(), 'message' => $response->json('message', '')];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resolve drive_file_id dari hasil Python ke data lengkap DriveFile + URL.
     *
     * @param  array  $matches  [{ drive_file_id, similarity, similarity_pct, file_path }, ...]
     */
    private function resolveMatches(array $matches, ?int $folderId = null): array
    {
        if (empty($matches)) {
            return [];
        }

        $ids = array_column($matches, 'drive_file_id');

        $query = DriveFile::whereIn('id', $ids)->with('folder');

        // Filter ke folder tertentu (termasuk subfolder)
        if ($folderId !== null) {
            $allFolderIds = $this->collectFolderIds($folderId);
            $query->whereIn('folder_id', $allFolderIds);
        }

        $driveFiles = $query->get()->keyBy('id');

        // Buat similarity map
        $simMap = [];
        foreach ($matches as $m) {
            $simMap[$m['drive_file_id']] = $m;
        }

        $resolved = [];
        foreach ($ids as $id) {
            $driveFile = $driveFiles->get($id);
            $sim = $simMap[$id] ?? [];

            if ($driveFile) {
                $resolved[] = [
                    'id' => $driveFile->id,
                    'original_name' => $driveFile->original_name,
                    'similarity' => $sim['similarity'] ?? 0,
                    'similarity_pct' => $sim['similarity_pct'] ?? 0,
                    'url' => route('drive.files.download', $driveFile->id),
                    'thumbnail_url' => route('drive.files.download', $driveFile->id),
                    'folder_name' => $driveFile->folder?->name,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Kumpulkan semua folder ID secara rekursif (untuk filter pencarian).
     *
     * @return int[]
     */
    private function collectFolderIds(int $rootId): array
    {
        $ids = [$rootId];
        $children = Folder::where('parent_id', $rootId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectFolderIds($childId));
        }

        return $ids;
    }
}
