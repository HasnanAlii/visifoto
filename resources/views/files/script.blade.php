@push('scripts')
<script>
    const filesBaseUrl = '{{ url("files") }}';
    const storageUrl = '{{ asset("storage") }}';
    let allFiles = []; // For client-side search filtering
    
    document.addEventListener('DOMContentLoaded', function() {
        loadFiles();
        
        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-menu-btn')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    });

    function formatBytes(bytes, decimals = 2) {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    async function loadFiles() {
        try {
            const response = await fetch(filesBaseUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            allFiles = result.data;
            renderGrid(allFiles);
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal memuat data files');
        }
    }

    function renderGrid(files) {
        const grid = document.getElementById('drive-grid');
        const emptyState = document.getElementById('empty-state');
        
        grid.innerHTML = '';
        
        if (files.length === 0) {
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        } else {
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
        }
        
        files.forEach(file => {
            const isImage = file.mime_type && file.mime_type.startsWith('image/');
            const previewContent = isImage 
                ? `<img src="${storageUrl}/${file.file_path}" class="w-full h-full object-cover" alt="${file.original_name}">`
                : `<svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg>`;

            const card = document.createElement('div');
            card.className = "group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col";
            card.innerHTML = `
                <!-- Preview Area -->
                <div class="h-32 bg-gray-50 border-b border-gray-100 flex items-center justify-center overflow-hidden">
                    ${previewContent}
                </div>
                
                <!-- Info Area -->
                <div class="p-3 flex items-center justify-between">
                    <div class="flex items-center truncate max-w-[80%]">
                        ${isImage ? `<svg class="w-4 h-4 text-red-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>` : `<svg class="w-4 h-4 text-blue-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>`}
                        <span class="text-sm font-medium text-gray-700 truncate" title="${file.original_name}">${file.original_name}</span>
                    </div>
                    
                    <!-- Dropdown Context Menu -->
                    <div class="relative">
                        <button onclick="toggleMenu(${file.id}, event)" class="dropdown-menu-btn p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 focus:outline-none">
                            <svg class="w-5 h-5 pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                        </button>
                        
                        <div id="menu-${file.id}" class="dropdown-menu hidden absolute right-0 bottom-full mb-1 w-48 bg-white rounded-md shadow-xl z-50 border border-gray-100">
                            <div class="py-1">
                                <a href="${filesBaseUrl}/${file.id}/download" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                                <button onclick="editForm(${file.id})" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    Ganti Nama / Pindah
                                </button>
                                <hr class="my-1 border-gray-100">
                                <button onclick="deleteFile(${file.id})" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function toggleMenu(id, event) {
        event.stopPropagation();
        const allMenus = document.querySelectorAll('.dropdown-menu');
        const targetMenu = document.getElementById(`menu-${id}`);
        
        allMenus.forEach(menu => {
            if (menu.id !== `menu-${id}`) {
                menu.classList.add('hidden');
            }
        });
        
        targetMenu.classList.toggle('hidden');
    }

    function filterFiles() {
        const query = document.getElementById('search-input').value.toLowerCase();
        const filtered = allFiles.filter(file => file.original_name.toLowerCase().includes(query));
        renderGrid(filtered);
    }

    function openForm() {
        document.getElementById('file-form').reset();
        document.getElementById('file_id').value = '';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('modal-title').innerText = 'Upload File Baru';
        document.getElementById('file-name-display').innerText = '';
        
        // Show file input, hide rename input
        document.getElementById('file_input_container').classList.remove('hidden');
        document.getElementById('rename_input_container').classList.add('hidden');
        document.getElementById('file').required = true;
        document.getElementById('original_name').required = false;
        
        document.getElementById('form-modal').classList.remove('hidden');
    }

    function closeForm() {
        document.getElementById('form-modal').classList.add('hidden');
    }

    async function editForm(id) {
        try {
            const file = allFiles.find(f => f.id === id);
            
            document.getElementById('file_id').value = file.id;
            document.getElementById('folder_id').value = file.folder_id || '';
            document.getElementById('original_name').value = file.original_name;
            
            document.getElementById('form_method').value = 'PUT';
            document.getElementById('modal-title').innerText = 'Ganti Nama File / Pindah Folder';
            
            // Hide file input, show rename input
            document.getElementById('file_input_container').classList.add('hidden');
            document.getElementById('rename_input_container').classList.remove('hidden');
            document.getElementById('file').required = false;
            document.getElementById('original_name').required = true;
            
            document.getElementById('form-modal').classList.remove('hidden');
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal mengambil data');
        }
    }

    async function submitForm(e) {
        e.preventDefault();
        
        const id = document.getElementById('file_id').value;
        const url = id ? `${filesBaseUrl}/${id}` : filesBaseUrl;
        const form = document.getElementById('file-form');
        const formData = new FormData(form);

        try {
            const response = await fetch(url, {
                method: 'POST', // POST with _method inside for PUT
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                    // Do not set Content-Type for FormData (browser sets it with boundary)
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (response.ok) {
                closeForm();
                loadFiles(); // Reload all files to update grid
            } else {
                alert('Terjadi kesalahan: ' + (result.message || 'Validasi gagal'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menyimpan file');
        }
    }

    async function deleteFile(id) {
        if (confirm('Yakin ingin menghapus file ini? File akan dihapus permanen.')) {
            try {
                const response = await fetch(`${filesBaseUrl}/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    })
                });
                
                if (response.ok) {
                    loadFiles();
                } else {
                    alert('Gagal menghapus file');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            }
        }
    }
</script>
@endpush
