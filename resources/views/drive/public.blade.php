<x-app-layout>
    <div class="p-4 md:p-6">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-1 text-sm flex-wrap" id="breadcrumb-container">
                <a href="#" onclick="loadPublic(); return false;" class="text-blue-600 hover:underline font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    File Publik
                </a>
                <span id="breadcrumb-items"></span>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="relative hidden sm:block">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="search-input"
                        class="pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:ring-1 focus:ring-blue-400 focus:outline-none w-52 transition-all"
                        placeholder="Cari File Publik" onkeyup="filterItems()">
                </div>
            </div>
        </div>

        <!-- Grid Content -->
        <div id="drive-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <!-- Dirender via JS -->
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden flex-col items-center justify-center py-20 text-gray-400">
            <svg class="w-24 h-24 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
            <p class="text-base font-medium text-gray-500">Belum ada file publik</p>
            <p class="text-sm mt-1">Ubah akses file menjadi publik untuk menampilkannya di sini.</p>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="flex flex-col items-center justify-center py-20 text-gray-400">
            <svg class="animate-spin w-10 h-10 text-blue-400 mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm">Memuat...</p>
        </div>
    </div>
    @include('components.lightbox')
    @push('scripts')
        <script>
            var CSRF = CSRF || document.querySelector('meta[name="csrf-token"]').content;
            var publicRootUrl = publicRootUrl || '{{ url('publik') }}';
            var driveRootUrl = driveRootUrl || '{{ url('drive') }}';
            var storageUrl = storageUrl || '{{ asset('storage') }}';

            let allFolders = [];
            let allFiles = [];

            document.addEventListener('DOMContentLoaded', () => {
                const urlMatch = window.location.pathname.match(/\/publik\/folder\/(\d+)/);
                if (urlMatch) {
                    const folderId = parseInt(urlMatch[1]);
                    history.replaceState({ folder: folderId }, '');
                    loadPublicFolder(folderId, false);
                } else {
                    history.replaceState({ folder: null }, '');
                    loadPublic(false);
                }

                // Handle tombol back/forward browser (same-page pushState)
                window.addEventListener('popstate', e => {
                    const state = e.state;
                    if (state && state.folder) {
                        loadPublicFolder(state.folder, false);
                    } else {
                        loadPublic(false);
                    }
                });

                // Handle bfcache restore (browser back/forward antar halaman berbeda)
                window.addEventListener('pageshow', e => {
                    if (e.persisted) {
                        const state = history.state;
                        if (state && state.folder) {
                            loadPublicFolder(state.folder, false);
                        } else {
                            loadPublic(false);
                        }
                    }
                });

                document.addEventListener('click', e => {
                    if (!e.target.closest('.ctx-btn')) {
                        document.querySelectorAll('.ctx-menu').forEach(m => m.classList.add('hidden'));
                    }
                });
            });

            async function loadPublic(pushHistory = true) {
                setLoading(true);
                document.getElementById('breadcrumb-items').innerHTML = '';
                if (pushHistory) {
                    history.pushState({ folder: null }, '', publicRootUrl);
                }
                try {
                    const res = await fetch(publicRootUrl, {
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

            async function loadPublicFolder(folderId, pushHistory = true) {
                setLoading(true);
                if (pushHistory) {
                    history.pushState({ folder: folderId }, '', `${publicRootUrl}/folder/${folderId}`);
                }
                try {
                    const res = await fetch(`${publicRootUrl}/folder/${folderId}`, {
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
                    <a href="#" onclick="loadPublicFolder(${c.id}); return false;" class="text-blue-600 hover:underline font-medium">${c.name}</a>
                `).join('');
            }

            function renderGrid(folders, files) {
                const grid = document.getElementById('drive-grid');
                const empty = document.getElementById('empty-state');
                grid.innerHTML = '';
                lightboxImages = [];

                if (!folders.length && !files.length) {
                    empty.classList.remove('hidden');
                    empty.classList.add('flex');
                    return;
                }
                empty.classList.add('hidden');
                empty.classList.remove('flex');

                folders.forEach(folder => {
                    const card = document.createElement('div');
                    card.className =
                        'group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col cursor-pointer';
                    card.innerHTML = `
                <div class="relative flex flex-col items-center justify-center p-5 h-full transition-colors group-hover:bg-gray-50 rounded-xl">
                    <!-- Titik Tiga -->
                    <div class="absolute top-2 right-2">
                        <button onclick="toggleCtx('fpub-${folder.id}', event)" class="ctx-btn p-1.5 text-gray-400 hover:text-gray-700 rounded-full hover:bg-gray-200 transition-colors">
                            <svg class="w-5 h-5 pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                        </button>
                        <div id="fpub-${folder.id}" class="ctx-menu hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-lg shadow-xl z-50 border border-gray-100 py-1 text-sm text-left">
                            <a href="${driveRootUrl}/folders/${folder.id}/download" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download
                            </a>
                        </div>
                    </div>

                    <!-- Ikon Folder Besar -->
                    <svg class="w-20 h-20 text-amber-400 mb-3 group-hover:scale-105 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                    <span class="text-sm font-medium text-gray-800 truncate w-full text-center px-2" title="${folder.name}">${folder.name}</span>
                </div>
            `;
                    // Klik folder untuk masuk ke dalamnya
                    card.addEventListener('click', e => {
                        if (!e.target.closest('.ctx-btn') && !e.target.closest('.ctx-menu')) {
                            loadPublicFolder(folder.id);
                        }
                    });
                    grid.appendChild(card);
                });

                files.forEach(file => {
                    const isImage = file.mime_type && file.mime_type.startsWith('image/');
                    const preview = isImage ?
                        `<img src="${storageUrl}/${file.file_path}" class="w-full h-full object-cover" alt="${file.original_name}" loading="lazy">` :
                        `<svg class="w-12 h-12 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>`;

                    const card = document.createElement('div');
                    card.className =
                        'group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col';
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
                         
                            <hr class="my-1 border-gray-100">
                            <a href="${driveRootUrl}/files/${file.id}/download" target="_blank" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            `;
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
                const fFolders = allFolders.filter(f => f.name.toLowerCase().includes(q));
                const fFiles = allFiles.filter(f => f.original_name.toLowerCase().includes(q));
                renderGrid(fFolders, fFiles);
            }

            function toggleCtx(id, event) {
                event.stopPropagation();
                document.querySelectorAll('.ctx-menu').forEach(m => {
                    if (m.id !== id) m.classList.add('hidden');
                });
                document.getElementById(id).classList.toggle('hidden');
            }

            function setLoading(state) {
                document.getElementById('loading-state').classList.toggle('hidden', !state);
                document.getElementById('drive-grid').classList.toggle('hidden', state);
            }

            async function toggleFilePublic(id) {
                await fetch(`${driveRootUrl}/files/${id}/toggle-public`, {
                    method: 'PUT',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                });
                loadPublic();
            }

            async function toggleFolderPublic(id) {
                await fetch(`${driveRootUrl}/folders/${id}/toggle-public`, {
                    method: 'PUT',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                });
                loadPublic();
            }
        </script>
    @endpush
</x-app-layout>
