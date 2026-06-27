@push('scripts')
<script>
var CSRF = CSRF || document.querySelector('meta[name="csrf-token"]').content;
var driveRootUrl = driveRootUrl || '{{ url("drive") }}';
var storageUrl = storageUrl || '{{ asset("storage") }}';

let currentFolderId = null;
let allFolders = [];
let allFiles = [];

// --- Dropdown Baru ---
function toggleNewDropdown(event) {
    event.stopPropagation();
    document.getElementById('new-dropdown-menu').classList.toggle('hidden');
}
function closeNewDropdown() {
    document.getElementById('new-dropdown-menu').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('search');
    const urlMatch = window.location.pathname.match(/\/drive\/folder\/(\d+)/);
    
    if (searchQuery) {
        history.replaceState({ search: searchQuery }, '');
        loadSearch(searchQuery, false);
    } else if (urlMatch) {
        const folderId = parseInt(urlMatch[1]);
        history.replaceState({ folder: folderId }, '');
        loadFolder(folderId, false);
    } else {
        history.replaceState({ folder: null }, '');
        loadRoot(false);
    }

    // Handle tombol back/forward browser (same-page pushState navigation)
    window.addEventListener('popstate', e => {
        const state = e.state;
        const params = new URLSearchParams(window.location.search);
        const searchVal = params.get('search');
        
        if (searchVal) {
            loadSearch(searchVal, false);
        } else if (state && state.folder) {
            loadFolder(state.folder, false);
        } else {
            loadRoot(false);
        }
    });

    // Handle bfcache restore (browser back/forward antar halaman berbeda)
    window.addEventListener('pageshow', e => {
        if (e.persisted) {
            const params = new URLSearchParams(window.location.search);
            const searchVal = params.get('search');
            
            if (searchVal) {
                loadSearch(searchVal, false);
            } else {
                const state = history.state;
                if (state && state.folder) {
                    loadFolder(state.folder, false);
                } else {
                    loadRoot(false);
                }
            }
        }
    });

    document.addEventListener('click', e => {
        // Tutup context menu item
        if (!e.target.closest('.ctx-btn')) {
            document.querySelectorAll('.ctx-menu').forEach(m => m.classList.add('hidden'));
        }
        // Tutup dropdown Baru
        if (!e.target.closest('#new-dropdown-wrapper')) {
            closeNewDropdown();
        }
    });
});

async function loadRoot(pushHistory = true) {
    currentFolderId = null;
    document.getElementById('breadcrumb-items').innerHTML = '';
    // Kosongkan kolom input pencarian jika kembali ke root
    const searchInput = document.getElementById('global-search-input');
    if (searchInput) searchInput.value = '';
    
    if (pushHistory) {
        history.pushState({ folder: null }, '', driveRootUrl);
    }
    setLoading(true);
    try {
        const res = await fetch(driveRootUrl, { 
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const data = await res.json();
        allFolders = data.folders;
        allFiles = data.files;
        renderGrid(allFolders, allFiles);
    } catch (e) { console.error(e); }
    setLoading(false);
}

async function loadSearch(query, pushHistory = true) {
    currentFolderId = null;
    
    // Perbarui isi input pencarian agar sinkron
    const searchInput = document.getElementById('global-search-input');
    if (searchInput) searchInput.value = query;

    // Tampilkan indikator pencarian di breadcrumbs
    const el = document.getElementById('breadcrumb-items');
    if (el) {
        el.innerHTML = `
            <span class="text-gray-400">/</span>
            <span class="text-gray-500 font-medium">Hasil Pencarian untuk "${query}"</span>
        `;
    }

    if (pushHistory) {
        history.pushState({ search: query }, '', `${driveRootUrl}?search=${encodeURIComponent(query)}`);
    }
    
    setLoading(true);
    try {
        const res = await fetch(`${driveRootUrl}?search=${encodeURIComponent(query)}`, { 
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const data = await res.json();
        allFolders = data.folders;
        allFiles = data.files;
        renderGrid(allFolders, allFiles);
    } catch (e) { console.error(e); }
    setLoading(false);
}

async function loadFolder(folderId, pushHistory = true) {
    currentFolderId = folderId;
    // Kosongkan kolom input pencarian jika masuk ke folder
    const searchInput = document.getElementById('global-search-input');
    if (searchInput) searchInput.value = '';

    if (pushHistory) {
        history.pushState({ folder: folderId }, '', `${driveRootUrl}/folder/${folderId}`);
    }
    setLoading(true);
    try {
        const res = await fetch(`${driveRootUrl}/folder/${folderId}`, { 
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            cache: 'no-store'
        });
        const data = await res.json();
        allFolders = data.folders;
        allFiles = data.files;
        renderBreadcrumbs(data.breadcrumbs);
        renderGrid(allFolders, allFiles);
    } catch (e) { console.error(e); }
    setLoading(false);
}

function renderBreadcrumbs(crumbs) {
    const el = document.getElementById('breadcrumb-items');
    el.innerHTML = crumbs.map(c => `
        <span class="text-gray-400">/</span>
        <a href="#" onclick="loadFolder(${c.id}); return false;" class="text-blue-600 hover:underline font-medium">${c.name}</a>
    `).join('');
}


function renderGrid(folders, files) {
    const grid = document.getElementById('drive-grid');
    const empty = document.getElementById('empty-state');
    grid.innerHTML = '';
    lightboxImages = []; // reset setiap render

    if (!folders.length && !files.length) {
        empty.classList.remove('hidden');
        empty.classList.add('flex');
        return;
    }
    empty.classList.add('hidden');
    empty.classList.remove('flex');

    folders.forEach(folder => {
        const card = document.createElement('div');
        card.className = 'group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col cursor-pointer';
        card.innerHTML = `
            <div class="relative flex flex-col items-center justify-center p-5 h-full transition-colors group-hover:bg-gray-50 rounded-xl">
                <!-- Dropdown (Absolute Top Right) -->
                <div class="absolute top-2 right-2">
                    <button onclick="toggleCtx('fctx-${folder.id}', event)" class="ctx-btn p-1.5 text-gray-400 hover:text-gray-700 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                    </button>
                    <div id="fctx-${folder.id}" class="ctx-menu hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-lg shadow-xl z-50 border border-gray-100 py-1 text-sm text-left">
                        <button onclick="toggleFolderPublic(${folder.id})" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 ${folder.is_public ? 'text-green-500' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            ${folder.is_public ? 'Jadikan Privat' : 'Jadikan Publik'}
                        </button>
                        <hr class="my-1 border-gray-100">
                        <a href="${driveRootUrl}/folders/${folder.id}/download" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download
                        </a>
                        <hr class="my-1 border-gray-100">
                        <button onclick="renameFolderForm(${folder.id}, '${folder.name.replace(/'/g, "\\'")}')" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.5-6.5a2.121 2.121 0 013 3L12 14H9v-3z"></path></svg>
                            Ganti Nama
                        </button>
                        <hr class="my-1 border-gray-100">
                        <button onclick="deleteFolder(${folder.id})" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus
                        </button>
                    </div>
                </div>

                <!-- Ikon Besar di Tengah -->
                <svg class="w-20 h-20 text-amber-400 mb-3 group-hover:scale-105 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                
                <!-- Nama Folder -->
                <span class="text-sm font-medium text-gray-800 truncate w-full text-center px-2" title="${folder.name}">${folder.name}</span>
            </div>
        `;
        card.addEventListener('click', e => {
            if (!e.target.closest('.ctx-btn') && !e.target.closest('.ctx-menu')) {
                loadFolder(folder.id);
            }
        });
        grid.appendChild(card);
    });

    files.forEach(file => {
        const isImage = file.mime_type && file.mime_type.startsWith('image/');
        const preview = isImage
            ? `<img src="${storageUrl}/${file.file_path}" class="w-full h-full object-cover" alt="${file.original_name}" loading="lazy">`
            : `<svg class="w-12 h-12 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>`;

        const card = document.createElement('div');
        card.className = 'group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col';
        card.innerHTML = `
            <div class="h-28 bg-gray-50 border-b border-gray-100 flex items-center justify-center overflow-hidden rounded-t-xl">
                ${preview}
            </div>
            <div class="p-2.5 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700 truncate max-w-[80%]" title="${file.original_name}">${file.original_name}</span>
                <div class="relative">
                    <button onclick="toggleCtx('fctx-file-${file.id}', event)" class="ctx-btn p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                        <svg class="w-4 h-4 pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                    </button>
                    <div id="fctx-file-${file.id}" class="ctx-menu hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-lg shadow-xl z-50 border border-gray-100 py-1 text-sm text-left">
                        <button onclick="toggleFilePublic(${file.id})" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 ${file.is_public ? 'text-green-500' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            ${file.is_public ? 'Jadikan Privat' : 'Jadikan Publik'}
                        </button>
                        <hr class="my-1 border-gray-100">
                        <a href="${driveRootUrl}/files/${file.id}/download" target="_blank" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download
                        </a>
                        <button onclick="openRenameFile(${file.id}, '${file.original_name.replace(/'/g, "\\'")}')" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.5-6.5a2.121 2.121 0 013 3L12 14H9v-3z"></path></svg>
                            Ganti Nama
                        </button>
                        <hr class="my-1 border-gray-100">
                        <button onclick="deleteFile(${file.id})" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        `;
        // Klik thumbnail foto → buka lightbox
        if (isImage) {
            const thumb = card.querySelector('.h-28');
            if (thumb) {
                thumb.style.cursor = 'pointer';
                thumb.dataset.fileIndex = lightboxImages.length;
                lightboxImages.push({ src: `${storageUrl}/${file.file_path}`, name: file.original_name });
                thumb.addEventListener('click', e => {
                    if (!e.target.closest('.ctx-btn') && !e.target.closest('.ctx-menu')) {
                        openLightbox(parseInt(thumb.dataset.fileIndex));
                    }
                });
            }
        }
        grid.appendChild(card);
    });
}

function filterItems() {
    const q = document.getElementById('search-input').value.toLowerCase();
    const fF = allFolders.filter(f => f.name.toLowerCase().includes(q));
    const fFiles = allFiles.filter(f => f.original_name.toLowerCase().includes(q));
    renderGrid(fF, fFiles);
}

function toggleCtx(id, event) {
    event.stopPropagation();
    document.querySelectorAll('.ctx-menu').forEach(m => { if (m.id !== id) m.classList.add('hidden'); });
    document.getElementById(id).classList.toggle('hidden');
}

function setLoading(state) {
    document.getElementById('loading-state').classList.toggle('hidden', !state);
    document.getElementById('drive-grid').classList.toggle('hidden', state);
}

// --- Folder Actions ---
function openFolderForm() {
    document.getElementById('folder_id').value = '';
    document.getElementById('folder_method').value = 'POST';
    document.getElementById('folder_name').value = '';
    document.getElementById('folder_parent_id').value = currentFolderId || '';
    document.getElementById('folder-modal-title').innerText = 'Folder Baru';
    showModal('folder-modal');
}

function renameFolderForm(id, name) {
    document.getElementById('folder_id').value = id;
    document.getElementById('folder_method').value = 'PUT';
    document.getElementById('folder_name').value = name;
    document.getElementById('folder-modal-title').innerText = 'Ganti Nama Folder';
    showModal('folder-modal');
}

function closeFolderForm() { hideModal('folder-modal'); }

async function submitFolderForm(e) {
    e.preventDefault();
    const id = document.getElementById('folder_id').value;
    const name = document.getElementById('folder_name').value;
    const parentId = document.getElementById('folder_parent_id').value;
    const url = id ? `${driveRootUrl}/folders/${id}` : `${driveRootUrl}/folders`;
    const method = id ? 'PUT' : 'POST';

    await apiCall(url, method, { name, parent_id: parentId || null });
    closeFolderForm();
    currentFolderId ? loadFolder(currentFolderId) : loadRoot();
}

function deleteFolder(id) {
    showConfirmModal(
        'Hapus Folder',
        'Hapus folder ini? Semua sub-folder dan file di dalamnya akan dihapus secara permanen.',
        async () => {
            await apiCall(`${driveRootUrl}/folders/${id}`, 'DELETE');
            currentFolderId ? loadFolder(currentFolderId) : loadRoot();
        }
    );
}

async function toggleFolderPublic(id) {
    await apiCall(`${driveRootUrl}/folders/${id}/toggle-public`, 'PUT');
    if (typeof loadPublic === 'function') {
        loadPublic();
    } else {
        currentFolderId ? loadFolder(currentFolderId) : loadRoot();
    }
}

// --- File Actions ---
let pendingUploadFiles = null;
let isPendingFolderUpload = false;

function openUploadForm(isFolder = false) {
    if (isFolder) {
        document.getElementById('hidden-folder-input').click();
    } else {
        document.getElementById('hidden-file-input').click();
    }
}

function handleFileSelect(input, isFolder) {
    if (!input.files || input.files.length === 0) return;
    
    pendingUploadFiles = input.files;
    isPendingFolderUpload = isFolder;
    executeUpload();
}

async function executeUpload() {
    if (!pendingUploadFiles) return;
    
    const formData = new FormData();
    if (currentFolderId) formData.append('folder_id', currentFolderId);
    
    for (let i = 0; i < pendingUploadFiles.length; i++) {
        const file = pendingUploadFiles[i];
        formData.append('files[]', file);
        if (isPendingFolderUpload) {
            formData.append('paths[]', file.webkitRelativePath || file.name);
        }
    }
    
    setLoading(true);
    try {
        const res = await fetch(`${driveRootUrl}/files`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: formData
        });
        if (res.ok) {
            currentFolderId ? loadFolder(currentFolderId) : loadRoot();
        } else {
            const d = await res.json();
            alert(d.message || 'Gagal upload');
        }
    } catch (err) { console.error(err); alert('Terjadi kesalahan'); }
    
    document.getElementById('hidden-file-input').value = '';
    document.getElementById('hidden-folder-input').value = '';
    pendingUploadFiles = null;
    isPendingFolderUpload = false;
    setLoading(false);
}

function openRenameFile(id, name) {
    document.getElementById('rename_file_id').value = id;
    document.getElementById('rename_file_name').value = name;
    showModal('rename-file-modal');
}

function closeRenameFile() { hideModal('rename-file-modal'); }

async function submitRenameFile(e) {
    e.preventDefault();
    const id = document.getElementById('rename_file_id').value;
    const name = document.getElementById('rename_file_name').value;
    await apiCall(`${driveRootUrl}/files/${id}`, 'PUT', { original_name: name });
    closeRenameFile();
    currentFolderId ? loadFolder(currentFolderId) : loadRoot();
}

async function toggleFilePublic(id) {
    await apiCall(`${driveRootUrl}/files/${id}/toggle-public`, 'PUT');
    // If we're on public.index, we might want to reload public files. 
    // For now we'll just reload the current folder/root.
    if (typeof loadPublic === 'function') {
        loadPublic();
    } else {
        currentFolderId ? loadFolder(currentFolderId) : loadRoot();
    }
}

function deleteFile(id) {
    showConfirmModal(
        'Hapus File',
        'Hapus file ini secara permanen?',
        async () => {
            await apiCall(`${driveRootUrl}/files/${id}`, 'DELETE');
            currentFolderId ? loadFolder(currentFolderId) : loadRoot();
        }
    );
}

// --- Helpers ---
async function apiCall(url, method, body = null) {
    const opts = { method, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF } };
    if (body && method !== 'DELETE') opts.body = JSON.stringify(body);
    try {
        const res = await fetch(url, opts);
        return await res.json();
    } catch (e) { console.error(e); }
}

function showModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
}

function hideModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('flex');
    el.classList.add('hidden');
}

// --- Confirm Modal Logic ---
let confirmActionCallback = null;

function showConfirmModal(title, message, callback) {
    document.getElementById('confirm-modal-title').innerText = title;
    document.getElementById('confirm-modal-message').innerText = message;
    confirmActionCallback = callback;
    showModal('confirm-modal');
}

function closeConfirmModal() {
    hideModal('confirm-modal');
    confirmActionCallback = null;
}

document.getElementById('confirm-modal-btn')?.addEventListener('click', () => {
    if (confirmActionCallback) {
        confirmActionCallback();
    }
    closeConfirmModal();
});


</script>
@endpush
