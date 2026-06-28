<?php

use App\Http\Controllers\DriveController;
use App\Http\Controllers\FaceClusterController;
use App\Http\Controllers\FaceSearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('public.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Unified Drive Routes
    Route::get('/drive', [DriveController::class, 'index'])->name('drive.index');
    Route::get('/drive/folder/{folder}', [DriveController::class, 'folder'])->name('drive.folder');
    Route::post('/drive/access-code', [DriveController::class, 'accessByCode'])->name('drive.access-code');

    // Drive API actions
    Route::post('/drive/folders', [DriveController::class, 'storeFolder'])->name('drive.folders.store');
    Route::put('/drive/folders/{folder}', [DriveController::class, 'updateFolder'])->name('drive.folders.update');
    Route::delete('/drive/folders/{folder}', [DriveController::class, 'destroyFolder'])->name('drive.folders.destroy');
    Route::put('/drive/folders/{folder}/toggle-public', [DriveController::class, 'toggleFolderPublic'])->name('drive.folders.toggle-public');
    Route::post('/drive/folders/{folder}/generate-code', [DriveController::class, 'generateUniqueCode'])->name('drive.folders.generate-code');
    Route::get('/drive/folders/{folder}/download', [DriveController::class, 'downloadFolder'])->name('drive.folders.download');

    Route::post('/drive/files', [DriveController::class, 'storeFile'])->name('drive.files.store');
    Route::put('/drive/files/{file}', [DriveController::class, 'updateFile'])->name('drive.files.update');
    Route::delete('/drive/files/{file}', [DriveController::class, 'destroyFile'])->name('drive.files.destroy');
    Route::put('/drive/files/{file}/toggle-public', [DriveController::class, 'togglePublic'])->name('drive.files.toggle-public');
    Route::get('/drive/files/{file}/download', [DriveController::class, 'downloadFile'])->name('drive.files.download');

    // Publik Menu
    Route::get('/publik', [PublicController::class, 'index'])->name('public.index');
    Route::get('/publik/folder/{folder}', [PublicController::class, 'folder'])->name('public.folder');

    // Pengelompokan Wajah & Cari Foto Saya
    Route::get('/face-clusters', [FaceClusterController::class, 'index'])->name('face-clusters.index');
    Route::get('/face-clusters/{id}', [FaceClusterController::class, 'show'])->name('face-clusters.show');
    Route::post('/face-clusters/run', [FaceClusterController::class, 'runClustering'])->name('face-clusters.run');
    Route::patch('/face-clusters/{id}/rename', [FaceClusterController::class, 'rename'])->name('face-clusters.rename');
    Route::get('/face-search', [FaceSearchController::class, 'index'])->name('face-search.index');
    Route::post('/face-search/search', [FaceSearchController::class, 'search'])->name('face-search.search');
    Route::get('/face-search/status', [FaceSearchController::class, 'status'])->name('face-search.status');
    Route::get('/face-search/folders', [FaceSearchController::class, 'folders'])->name('face-search.folders');
    Route::post('/face-search/index-folder', [FaceSearchController::class, 'indexFolder'])->name('face-search.index-folder');
    Route::post('/face-search/index', [FaceSearchController::class, 'triggerIndex'])->name('face-search.index.trigger');
    Route::delete('/face-search/index', [FaceSearchController::class, 'clearIndex'])->name('face-search.index.clear');
});

require __DIR__.'/auth.php';
