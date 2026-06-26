<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    /**
     * Root: tampilkan folder root + file root (tanpa folder)
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        if ($search) {
            $folders = Folder::with('user')
                ->where('user_id', Auth::id())
                ->where('name', 'like', "%{$search}%")
                ->latest()
                ->get();

            $files = DriveFile::with('user')
                ->where('user_id', Auth::id())
                ->where('original_name', 'like', "%{$search}%")
                ->latest()
                ->get();
        } else {
            $folders = Folder::with('user')
                ->whereNull('parent_id')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();

            $files = DriveFile::with('user')
                ->whereNull('folder_id')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'folders' => $folders,
                'files' => $files,
                'breadcrumbs' => [],
                'current_folder' => null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return view('drive.index');
    }

    /**
     * Folder view: tampilkan subfolder + file dalam folder ini
     */
    public function folder(Request $request, Folder $folder)
    {
        $subfolders = Folder::with('user')
            ->where('parent_id', $folder->id)
            ->latest()
            ->get();

        $files = DriveFile::with('user')
            ->where('folder_id', $folder->id)
            ->latest()
            ->get();

        // Build breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($folder);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'folders' => $subfolders,
                'files' => $files,
                'breadcrumbs' => $breadcrumbs,
                'current_folder' => $folder,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return view('drive.index', compact('folder'));
    }

    /**
     * Buat folder baru
     */
    public function storeFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        $folder = Folder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id ?: null,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder berhasil dibuat',
            'data' => $folder,
        ]);
    }

    /**
     * Update nama folder
     */
    public function updateFolder(Request $request, Folder $folder)
    {
        $request->validate([
            'name' => 'required|max:255',
        ]);

        $folder->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Folder diperbarui',
            'data' => $folder,
        ]);
    }

    /**
     * Hapus folder
     */
    public function destroyFolder(Folder $folder)
    {
        $this->deleteFolderRecursively($folder);

        return response()->json(['success' => true, 'message' => 'Folder dan isinya berhasil dihapus']);
    }

    private function deleteFolderRecursively(Folder $folder)
    {
        // 1. Hapus semua file di dalam folder ini beserta file fisiknya
        $files = DriveFile::where('folder_id', $folder->id)->get();
        foreach ($files as $file) {
            Storage::disk('public')->delete($file->file_path);
            $file->delete();
        }

        // 2. Hapus semua sub-folder secara rekursif
        $subfolders = Folder::where('parent_id', $folder->id)->get();
        foreach ($subfolders as $subfolder) {
            $this->deleteFolderRecursively($subfolder);
        }

        // 3. Hapus folder itu sendiri
        $folder->delete();
    }

    /**
     * Upload file
     */
    public function storeFile(Request $request)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,id',
            'files' => 'required|array',
            'files.*' => 'file|max:2097152',
            'paths' => 'nullable|array',
        ]);

        $uploadedFiles = [];
        $paths = $request->input('paths', []);
        $files = $request->file('files');

        foreach ($files as $index => $file) {
            $relativePath = $paths[$index] ?? $file->getClientOriginalName();
            $pathParts = explode('/', $relativePath);

            // Get the base folder ID
            $currentFolderId = $request->folder_id ?: null;

            // If the path has directories (e.g. "MyFolder/Subfolder/file.jpg")
            if (count($pathParts) > 1) {
                // Remove the last part (the filename)
                array_pop($pathParts);

                // Create or find each directory in the path
                foreach ($pathParts as $folderName) {
                    $folder = Folder::firstOrCreate([
                        'name' => $folderName,
                        'parent_id' => $currentFolderId,
                        'user_id' => Auth::id(),
                    ], [
                        // Inherit is_public from parent if we want, but for now default to false
                        'is_public' => false,
                    ]);
                    $currentFolderId = $folder->id;
                }
            }

            $path = $file->store('drive-files', 'public');

            $driveFile = DriveFile::create([
                'folder_id' => $currentFolderId,
                'user_id' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_name' => uniqid().'.'.$file->getClientOriginalExtension(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
            ]);

            $uploadedFiles[] = $driveFile;
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles).' file berhasil diupload',
            'data' => $uploadedFiles,
        ]);
    }

    /**
     * Rename file
     */
    public function updateFile(Request $request, DriveFile $file)
    {
        $request->validate([
            'original_name' => 'required',
        ]);

        $file->update(['original_name' => $request->original_name]);

        return response()->json([
            'success' => true,
            'message' => 'File diperbarui',
            'data' => $file,
        ]);
    }

    /**
     * Hapus file
     */
    public function destroyFile(DriveFile $file)
    {
        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return response()->json(['success' => true, 'message' => 'File dihapus']);
    }

    /**
     * Download folder sebagai ZIP
     */
    public function downloadFolder(Folder $folder)
    {
        $zipName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $folder->name).'.zip';
        $tmpPath = storage_path('app/tmp/'.uniqid('folder_').'.zip');

        if (! file_exists(storage_path('app/tmp'))) {
            mkdir(storage_path('app/tmp'), 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak bisa membuat file ZIP');
        }

        $this->addFolderToZip($zip, $folder, '');
        $zip->close();

        return response()->download($tmpPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * Rekursif menambahkan file folder ke ZIP
     */
    private function addFolderToZip(\ZipArchive $zip, Folder $folder, string $prefix): void
    {
        $files = DriveFile::where('folder_id', $folder->id)->get();
        foreach ($files as $file) {
            $diskPath = Storage::disk('public')->path($file->file_path);
            if (file_exists($diskPath)) {
                $zip->addFile($diskPath, $prefix.$file->original_name);
            }
        }

        $subfolders = Folder::where('parent_id', $folder->id)->get();
        foreach ($subfolders as $sub) {
            $subPrefix = $prefix.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sub->name).'/';
            $zip->addEmptyDir($subPrefix);
            $this->addFolderToZip($zip, $sub, $subPrefix);
        }
    }

    /**
     * Download file
     */
    public function downloadFile(DriveFile $file)
    {
        return Storage::disk('public')->download($file->file_path, $file->original_name);
    }

    /**
     * Toggle akses publik/privat
     */
    public function togglePublic(DriveFile $file)
    {
        $file->update(['is_public' => ! $file->is_public]);
        $file->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Akses file diubah menjadi '.($file->is_public ? 'Publik' : 'Privat'),
            'data' => $file,
        ]);
    }

    /**
     * Toggle akses publik/privat untuk folder
     */
    public function toggleFolderPublic(Folder $folder)
    {
        $folder->update(['is_public' => ! $folder->is_public]);
        $folder->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Akses folder diubah menjadi '.($folder->is_public ? 'Publik' : 'Privat'),
            'data' => $folder,
        ]);
    }

    /**
     * Build breadcrumb trail untuk folder
     */
    private function buildBreadcrumbs(Folder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;

        while ($current) {
            array_unshift($breadcrumbs, ['id' => $current->id, 'name' => $current->name]);
            $current = $current->parent_id ? Folder::find($current->parent_id) : null;
        }

        return $breadcrumbs;
    }
}
