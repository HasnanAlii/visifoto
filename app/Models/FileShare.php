<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'drive_file_id',
        'owner_id',
        'shared_to',
        'permission'
    ];

    public function file()
    {
        return $this->belongsTo(DriveFile::class, 'drive_file_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'shared_to');
    }
}
