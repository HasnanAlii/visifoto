<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaceCluster extends Model
{
    protected $fillable = [
        'name',
        'representative_drive_file_id',
        'member_count',
    ];

    protected function casts(): array
    {
        return [
            'member_count' => 'integer',
        ];
    }

    public function representativeFile(): BelongsTo
    {
        return $this->belongsTo(DriveFile::class, 'representative_drive_file_id');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(FaceEmbedding::class);
    }

    /** Ambil semua DriveFile yang ada di kluster ini melalui embeddings. */
    public function driveFiles()
    {
        return DriveFile::whereIn('id',
            $this->embeddings()->pluck('drive_file_id')
        );
    }
}
