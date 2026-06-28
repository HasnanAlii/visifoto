<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFaceIndex;
use App\Models\DriveFile;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DriveFileController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $files = DriveFile::with(['folder', 'user'])->latest()->get();

            return response()->json(['data' => $files]);
        }

        $folders = Folder::all();

        return view('files.index', compact('folders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,id',
            'file' => 'required|file|max:2097152',
        ]);

        $file = $request->file('file');

        $path = $file->store('drive-files', 'public');

        $driveFile = DriveFile::create([
            'folder_id' => $request->folder_id,
            'user_id' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'file_name' => uniqid().'.'.$file->getClientOriginalExtension(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
        ]);

        ProcessFaceIndex::dispatch($driveFile);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diupload',
            'data' => $driveFile,
        ]);
    }

    public function show(DriveFile $file)
    {
        return response()->json(['data' => $file]);
    }

    public function update(Request $request, DriveFile $file)
    {
        $request->validate([
            'folder_id' => 'nullable|exists:folders,id',
            'original_name' => 'required',
        ]);

        $file->update([
            'folder_id' => $request->folder_id,
            'original_name' => $request->original_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diperbarui',
            'data' => $file,
        ]);
    }

    public function destroy(DriveFile $file)
    {
        Storage::disk('public')->delete($file->file_path);

        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus',
        ]);
    }

    public function download(DriveFile $file)
    {
        return Storage::disk('public')->download(
            $file->file_path,
            $file->original_name
        );
    }
}
