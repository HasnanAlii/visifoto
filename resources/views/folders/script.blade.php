@push('scripts')
<script>
    const foldersBaseUrl = '{{ url("folders") }}';
    let allFolders = [];
    
    document.addEventListener('DOMContentLoaded', function() {
        loadFolders();
        
        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-menu-btn')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    });

    async function loadFolders() {
        try {
            const response = await fetch(foldersBaseUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            allFolders = result.data;
            renderGrid(allFolders);
            updateParentSelects(allFolders);
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal memuat data folders');
        }
    }

    function renderGrid(folders) {
        const grid = document.getElementById('drive-grid');
        const emptyState = document.getElementById('empty-state');
        
        grid.innerHTML = '';
        
        if (folders.length === 0) {
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        } else {
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
        }
        
        folders.forEach(folder => {
            const card = document.createElement('div');
            card.className = "group relative bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 flex flex-col";
            
            // Double click opens show page, or we could just link it
            card.onclick = (e) => {
                if (!e.target.closest('.dropdown-menu-btn') && !e.target.closest('.dropdown-menu')) {
                    window.location.href = `${foldersBaseUrl}/${folder.id}`;
                }
            };

            card.innerHTML = `
                <!-- Preview Area -->
                <div class="h-32 bg-gray-50 border-b border-gray-100 flex items-center justify-center overflow-hidden group-hover:bg-gray-100 transition-colors">
                    <svg class="w-16 h-16 text-gray-400 group-hover:text-gray-500 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                </div>
                
                <!-- Info Area -->
                <div class="p-3 flex items-center justify-between">
                    <div class="flex items-center truncate max-w-[80%]">
                        <svg class="w-4 h-4 text-gray-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        <span class="text-sm font-medium text-gray-700 truncate" title="${folder.name}">${folder.name}</span>
                    </div>
                    
                    <!-- Dropdown Context Menu -->
                    <div class="relative">
                        <button onclick="toggleMenu(${folder.id}, event)" class="dropdown-menu-btn p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 focus:outline-none">
                            <svg class="w-5 h-5 pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg>
                        </button>
                        
                        <div id="menu-${folder.id}" class="dropdown-menu hidden absolute right-0 bottom-full mb-1 w-48 bg-white rounded-md shadow-xl z-50 border border-gray-100">
                            <div class="py-1">
                                <a href="${foldersBaseUrl}/${folder.id}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Buka Folder
                                </a>
                                <button onclick="editForm(${folder.id})" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    Ganti Nama / Pindah
                                </button>
                                <hr class="my-1 border-gray-100">
                                <button onclick="deleteFolder(${folder.id})" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center">
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

    function filterFolders() {
        const query = document.getElementById('search-input').value.toLowerCase();
        const filtered = allFolders.filter(folder => folder.name.toLowerCase().includes(query));
        renderGrid(filtered);
    }

    function openForm() {
        document.getElementById('folder-form').reset();
        document.getElementById('folder_id').value = '';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('modal-title').innerText = 'Folder Baru';
        
        // Show all parents in the select
        Array.from(document.getElementById('parent_id').options).forEach(opt => opt.hidden = false);
        
        document.getElementById('form-modal').classList.remove('hidden');
    }

    function closeForm() {
        document.getElementById('form-modal').classList.add('hidden');
    }

    async function editForm(id) {
        try {
            const folder = allFolders.find(f => f.id === id);
            
            document.getElementById('folder_id').value = folder.id;
            document.getElementById('name').value = folder.name;
            document.getElementById('parent_id').value = folder.parent_id || '';
            
            document.getElementById('form_method').value = 'PUT';
            document.getElementById('modal-title').innerText = 'Edit Folder';
            
            // Hide the folder itself from the parent select so it can't be a parent of itself
            Array.from(document.getElementById('parent_id').options).forEach(opt => {
                opt.hidden = (opt.value == folder.id);
            });
            
            document.getElementById('form-modal').classList.remove('hidden');
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal mengambil data');
        }
    }

    async function submitForm(e) {
        e.preventDefault();
        
        const id = document.getElementById('folder_id').value;
        const url = id ? `${foldersBaseUrl}/${id}` : foldersBaseUrl;
        const formData = new FormData(document.getElementById('folder-form'));

        try {
            const response = await fetch(url, {
                method: 'POST', // POST with _method
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (response.ok) {
                closeForm();
                loadFolders(); // Reload folders to update grid
            } else {
                alert('Terjadi kesalahan: ' + (result.message || 'Validasi gagal'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menyimpan folder');
        }
    }

    async function deleteFolder(id) {
        if (confirm('Yakin ingin menghapus folder ini?')) {
            try {
                const response = await fetch(`${foldersBaseUrl}/${id}`, {
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
                    loadFolders();
                } else {
                    alert('Gagal menghapus folder');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            }
        }
    }

    function updateParentSelects(folders) {
        const select = document.getElementById('parent_id');
        const currentValue = select.value;
        
        // Clear all options except the first one
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        folders.forEach(folder => {
            const option = new Option(folder.name, folder.id);
            select.add(option);
        });
        
        select.value = currentValue; // restore value if any
    }
</script>
@endpush
