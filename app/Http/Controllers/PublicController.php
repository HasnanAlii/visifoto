<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\Folder;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function index(Request $request)
    {
        $folders = Folder::with('user')
            ->where('is_public', true)
            ->latest()
            ->get();

        $files = DriveFile::with('user')
            ->where('is_public', true)
            ->latest()
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'folders' => $folders,
                'files' => $files,
                'breadcrumbs' => [],
                'current_folder' => null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return view('drive.public');
    }

    public function folder(Request $request, Folder $folder)
    {
        // Hanya boleh diakses jika folder root-nya publik
        abort_if(!$folder->is_public && !$this->hasPublicAncestor($folder), 403);

        $subfolders = Folder::where('parent_id', $folder->id)->get();
        $files = DriveFile::where('folder_id', $folder->id)->get();
        $breadcrumbs = $this->buildBreadcrumbs($folder);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'folders' => $subfolders,
                'files' => $files,
                'breadcrumbs' => $breadcrumbs,
                'current_folder' => $folder,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return view('drive.public');
    }

    private function hasPublicAncestor(Folder $folder): bool
    {
        $current = $folder->parent_id ? Folder::find($folder->parent_id) : null;
        while ($current) {
            if ($current->is_public) return true;
            $current = $current->parent_id ? Folder::find($current->parent_id) : null;
        }
        return false;
    }

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
