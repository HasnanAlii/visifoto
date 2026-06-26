<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\FaceCluster;
use App\Services\FaceClusterService;
use App\Services\FaceSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaceClusterController extends Controller
{
    public function __construct(
        private readonly FaceClusterService $clusterService,
        private readonly FaceSearchService $searchService,
    ) {}

    /**
     * Halaman daftar semua kluster wajah.
     */
    public function index(): View
    {
        $clusters = FaceCluster::with('representativeFile')
            ->where('member_count', '>', 0)
            ->orderByDesc('member_count')
            ->get()
            ->map(function (FaceCluster $cluster) {
                return [
                    'id' => $cluster->id,
                    'name' => $cluster->name,
                    'member_count' => $cluster->member_count,
                    'thumbnail_url' => $cluster->representativeFile
                        ? route('drive.files.download', $cluster->representative_drive_file_id)
                        : null,
                ];
            });

        $totalClusters = $clusters->count();
        $totalPhotos = $clusters->sum('member_count');

        return view('face-clusters.index', compact('clusters', 'totalClusters', 'totalPhotos'));
    }

    /**
     * Halaman detail satu kluster — semua foto di dalamnya.
     */
    public function show(int $id): View
    {
        $cluster = FaceCluster::findOrFail($id);

        $photos = DriveFile::whereIn('id', function ($q) use ($id) {
            $q->select('drive_file_id')
                ->from('face_embeddings')
                ->where('face_cluster_id', $id);
        })
            ->with('folder')
            ->get()
            ->map(fn (DriveFile $f) => [
                'id' => $f->id,
                'name' => $f->original_name,
                'url' => route('drive.files.download', $f->id),
                'folder_name' => $f->folder?->name,
            ]);

        return view('face-clusters.show', compact('cluster', 'photos'));
    }

    /**
     * API: Jalankan clustering (sinkron/async).
     * POST /face-clusters/run
     */
    public function runClustering(Request $request): JsonResponse
    {
        $async = $request->boolean('async', true);
        $folderIds = $request->input('folder_ids')
            ? array_map('intval', explode(',', $request->input('folder_ids')))
            : null;

        $result = $async
            ? $this->clusterService->runClusteringAsync($folderIds)
            : $this->clusterService->runClustering($folderIds);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * API: Ganti nama kluster.
     * PATCH /face-clusters/{id}/rename
     */
    public function rename(Request $request, int $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $cluster = FaceCluster::findOrFail($id);
        $cluster->update(['name' => $request->input('name')]);

        return response()->json(['success' => true, 'name' => $cluster->name]);
    }
}
