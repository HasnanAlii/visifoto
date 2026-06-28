<?php

namespace App\Jobs;

use App\Models\DriveFile;
use App\Services\FaceSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessFaceIndex implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public DriveFile $file)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(FaceSearchService $faceSearchService): void
    {
        if (str_starts_with($this->file->mime_type ?? '', 'image/') || in_array($this->file->extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp'])) {
            $faceSearchService->indexSingleFile($this->file->id, 'public', $this->file->file_path);
        }
    }
}
