<?php

namespace App\Providers;

use App\Models\DriveFile;
use App\Models\Folder;
use App\Observers\DriveFileObserver;
use App\Observers\FolderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Folder::observe(FolderObserver::class);
        DriveFile::observe(DriveFileObserver::class);
    }
}
