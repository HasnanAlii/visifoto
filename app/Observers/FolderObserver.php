<?php

namespace App\Observers;

use App\Models\Folder;
use App\Services\FaceClusterService;
use App\Services\FaceSearchService;
use Illuminate\Support\Facades\Log;

class FolderObserver
{
    public function __construct(
        private readonly FaceClusterService $clusterService,
        private readonly FaceSearchService $searchService,
    ) {}

    /**
     * Saat folder diupdate dan status is_public berubah menjadi true,
     * trigger indexing + clustering untuk folder tersebut.
     */
    public function updated(Folder $folder): void
    {
        // Hanya proses jika is_public berubah ke TRUE
        if (! $folder->wasChanged('is_public') || ! $folder->is_public) {
            return;
        }

        Log::info("FolderObserver: Folder #{$folder->id} '{$folder->name}' jadi publik, trigger clustering.");

        // Index semua file dalam folder yang belum terindeks
        $this->indexFolderFiles($folder);

        // Jalankan clustering untuk folder ini (async agar tidak block request)
        $this->clusterService->runClusteringAsync([$folder->id]);
    }

    private function indexFolderFiles(Folder $folder): void
    {
        $files = $folder->files()
            ->whereNotIn('id', function ($q) {
                $q->select('drive_file_id')->from('face_embeddings');
            })
            ->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('extension', ['jpg', 'jpeg', 'png', 'webp']);
            })
            ->get();

        foreach ($files as $file) {
            $this->searchService->indexSingleFile($file->id, 'public', $file->file_path);
        }
    }
}
