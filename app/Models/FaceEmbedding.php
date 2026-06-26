<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceEmbedding extends Model
{
    protected $fillable = [
        'drive_file_id',
        'face_cluster_id',
        'embedding',
        'face_index',
        'bbox',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'bbox' => 'array',
            'confidence' => 'float',
        ];
    }

    public function driveFile(): BelongsTo
    {
        return $this->belongsTo(DriveFile::class);
    }

    public function faceCluster(): BelongsTo
    {
        return $this->belongsTo(FaceCluster::class);
    }

    /**
     * Apakah file ini sudah memiliki embedding (sudah terdeteksi wajah).
     */
    public function hasFace(): bool
    {
        return ! empty($this->embedding);
    }
}
