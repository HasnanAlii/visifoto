<!-- Modal: Buat Folder Baru / Rename Folder -->
<div id="folder-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-30" onclick="closeFolderForm()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10">
        <h3 class="text-lg font-semibold text-gray-900 mb-4" id="folder-modal-title">Folder Baru</h3>
        <form id="folder-form" onsubmit="submitFolderForm(event)">
            @csrf
            <input type="hidden" id="folder_id" name="id">
            <input type="hidden" id="folder_method" name="_method" value="POST">
            <input type="hidden" id="folder_parent_id" name="parent_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Folder</label>
                <input type="text" id="folder_name" name="name" required autofocus
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Masukan nama folder">
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeFolderForm()" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Buat</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Inputs for Direct Upload -->
<form id="hidden-upload-form" class="hidden">
    <input type="file" id="hidden-file-input" name="files[]" multiple onchange="handleFileSelect(this, false)">
    <input type="file" id="hidden-folder-input" name="files[]" multiple webkitdirectory directory onchange="handleFileSelect(this, true)">
</form>

<!-- Modal: Rename File -->
<div id="rename-file-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-30" onclick="closeRenameFile()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ganti Nama File</h3>
        <form onsubmit="submitRenameFile(event)">
            @csrf
            <input type="hidden" id="rename_file_id">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama File</label>
                <input type="text" id="rename_file_name" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeRenameFile()" class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Konfirmasi Aksi -->
<div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-30" onclick="closeConfirmModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" id="confirm-modal-title">Konfirmasi</h3>
        <p class="text-sm text-gray-500 mb-6" id="confirm-modal-message">Apakah Anda yakin ingin melakukan aksi ini?</p>
        <div class="flex gap-3 justify-center">
            <button type="button" onclick="closeConfirmModal()" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 w-full">Batal</button>
            <button type="button" id="confirm-modal-btn" class="px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 w-full">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Modal: Info / Sukses -->
<div id="info-modal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black bg-opacity-30" onclick="hideModal('info-modal')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 z-10 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2" id="info-modal-title">Berhasil</h3>
        <p class="text-sm text-gray-700 mb-4 font-mono bg-gray-100 p-2 rounded-lg break-all" id="info-modal-message"></p>
        <div class="flex gap-3 justify-center">
            <button type="button" onclick="hideModal('info-modal')" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 w-full">Tutup</button>
        </div>
    </div>
</div>

