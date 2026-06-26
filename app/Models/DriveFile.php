<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'user_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'extension',
        'size',
        'is_public'
    ];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shares()
    {
        return $this->hasMany(FileShare::class);
    }
}