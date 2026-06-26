<?php

namespace App\Observers;

use App\Models\DriveFile;
use App\Services\FaceClusterService;
use App\Services\FaceSearchService;
use Illuminate\Support\Facades\Log;

class DriveFileObserver
{
    public function __construct(
        private readonly FaceClusterService $clusterService,
        private readonly FaceSearchService $searchService,
    ) {}

    /**
     * Saat file baru dibuat dalam folder publik, langsung index + trigger clustering.
     */
    public function created(DriveFile $file): void
    {
        if (! $this->isImageFile($file)) {
            return;
        }

        // Cek apakah file atau foldernya publik
        if (! $file->is_public && ! optional($file->folder)->is_public) {
            return;
        }

        Log::info("DriveFileObserver: File #{$file->id} baru di folder publik, trigger index+cluster.");

        $this->searchService->indexSingleFile($file->id, 'public', $file->file_path);

        // Clustering per folder secara async
        if ($file->folder_id) {
            $this->clusterService->runClusteringAsync([$file->folder_id]);
        }
    }

    /**
     * Saat file diupdate dan is_public berubah jadi true.
     */
    public function updated(DriveFile $file): void
    {
        if (! $this->isImageFile($file)) {
            return;
        }

        if (! $file->wasChanged('is_public') || ! $file->is_public) {
            return;
        }

        Log::info("DriveFileObserver: File #{$file->id} jadi publik, trigger index+cluster.");

        $this->searchService->indexSingleFile($file->id, 'public', $file->file_path);

        if ($file->folder_id) {
            $this->clusterService->runClusteringAsync([$file->folder_id]);
        }
    }

    private function isImageFile(DriveFile $file): bool
    {
        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];

        return str_starts_with((string) $file->mime_type, 'image/')
            || in_array(strtolower((string) $file->extension), $imageExts);
    }
}
