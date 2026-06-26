<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $folders = Folder::with(['parent', 'user'])->latest()->get();
            return response()->json(['data' => $folders]);
        }
        
        $parents = Folder::all();
        return view('folders.index', compact('parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:folders,id'
        ]);

        $folder = Folder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder berhasil dibuat',
            'data' => $folder
        ]);
    }

    public function show(Folder $folder, Request $request)
    {
        $folder->load(['children', 'files']);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['data' => $folder]);
        }

        return view('folders.show', compact('folder'));
    }

    public function update(Request $request, Folder $folder)
    {
        $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:folders,id'
        ]);

        $folder->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder berhasil diperbarui',
            'data' => $folder
        ]);
    }

    public function destroy(Folder $folder)
    {
        $folder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Folder berhasil dihapus'
        ]);
    }
}