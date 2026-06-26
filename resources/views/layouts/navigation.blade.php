<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 px-4 py-2.5 flex items-center justify-between h-16 shrink-0">
    <div class="flex items-center gap-4">
        <!-- Mobile Menu Button -->
        <button @click="open = !open" class="md:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-full focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        
        <!-- Logo & Title -->
        <a href="{{ route('drive.index') }}" class="flex items-center gap-2">
            <x-application-logo class="w-8 h-8 rounded-md object-contain" />
            <span class="text-xl text-gray-700 hidden sm:block font-semibold tracking-tight">VisiFoto</span>
        </a>
    </div>

    <!-- Center Search Bar -->
    <div class="hidden md:flex flex-1 max-w-2xl px-8 relative" id="global-search-wrapper">
        <div class="relative w-full">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
            </div>
            <input type="text" 
                   id="global-search-input" 
                   value="{{ request('search') }}"
                   placeholder="Telusuri di VisiFoto" 
                   oninput="debounceSearch(this.value)"
                   onfocus="showAutocomplete(this.value)"
                   onkeydown="if(event.key === 'Enter') handleGlobalSearch(this.value)"
                   class="block w-full pl-12 pr-3 py-3 border-transparent bg-[#F1F3F4] text-gray-900 rounded-full focus:bg-white focus:border-white focus:ring-1 focus:ring-blue-500 focus:shadow-md sm:text-sm transition-all"
                   autocomplete="off">
        </div>

        <!-- Autocomplete Dropdown -->
        <div id="search-autocomplete-dropdown" class="hidden absolute left-8 right-8 top-full mt-2 bg-white rounded-2xl border border-gray-200/80 shadow-xl z-50 max-h-96 overflow-y-auto py-2">
            <div id="autocomplete-loading" class="hidden py-4 text-center text-sm text-gray-400">
                <svg class="animate-spin w-5 h-5 text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <div id="autocomplete-results" class="divide-y divide-gray-100">
                <!-- Results will be injected here -->
            </div>
        </div>
    </div>

    <script>
        var storageUrl = storageUrl || '{{ asset("storage") }}';
        var driveRootUrl = driveRootUrl || '{{ url("drive") }}';
        let searchTimeout = null;

        function debounceSearch(query) {
            clearTimeout(searchTimeout);
            if (query.trim().length === 0) {
                document.getElementById('search-autocomplete-dropdown').classList.add('hidden');
                return;
            }
            searchTimeout = setTimeout(() => {
                fetchAutocompleteResults(query);
            }, 300);
        }

        function showAutocomplete(query) {
            if (query.trim().length > 0) {
                document.getElementById('search-autocomplete-dropdown').classList.remove('hidden');
                fetchAutocompleteResults(query);
            }
        }

        async function fetchAutocompleteResults(query) {
            const dropdown = document.getElementById('search-autocomplete-dropdown');
            const loading = document.getElementById('autocomplete-loading');
            const results = document.getElementById('autocomplete-results');

            dropdown.classList.remove('hidden');
            loading.classList.remove('hidden');
            results.innerHTML = '';

            try {
                const url = `${driveRootUrl}?search=${encodeURIComponent(query)}`;
                const res = await fetch(url, { 
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                const data = await res.json();
                
                loading.classList.add('hidden');

                let html = '';

                // Folders Group
                if (data.folders && data.folders.length > 0) {
                    html += `
                        <div class="px-4 py-1.5 text-xs font-semibold text-gray-400 bg-gray-50 uppercase tracking-wider">Folder</div>
                        <div class="py-1">
                    `;
                    data.folders.forEach(folder => {
                        html += `
                            <div onclick="handleFolderClick(${folder.id})" class="px-4 py-2.5 hover:bg-blue-50/50 cursor-pointer flex items-center gap-3 transition-colors">
                                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                                <span class="text-sm font-medium text-gray-700 truncate">${escapeHtml(folder.name)}</span>
                            </div>
                        `;
                    });
                    html += `</div>`;
                }

                // Files Group
                if (data.files && data.files.length > 0) {
                    html += `
                        <div class="px-4 py-1.5 text-xs font-semibold text-gray-400 bg-gray-50 uppercase tracking-wider">File & Foto</div>
                        <div class="py-1">
                    `;
                    data.files.forEach(file => {
                        const isImage = file.mime_type && file.mime_type.startsWith('image/');
                        const icon = isImage
                            ? `<img src="${storageUrl}/${file.file_path}" class="w-8 h-8 rounded-md object-cover bg-gray-100 border border-gray-100 shrink-0">`
                            : `<div class="w-8 h-8 rounded-md bg-blue-50 text-blue-500 flex items-center justify-center shrink-0"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path></svg></div>`;
                        
                        html += `
                            <a href="${storageUrl}/${file.file_path}" target="_blank" class="px-4 py-2.5 hover:bg-blue-50/50 flex items-center gap-3 transition-colors">
                                ${icon}
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-medium text-gray-700 truncate">${escapeHtml(file.original_name)}</span>
                                    <span class="text-xs text-gray-400">${formatBytes(file.size)}</span>
                                </div>
                            </a>
                        `;
                    });
                    html += `</div>`;
                }

                if ((!data.folders || data.folders.length === 0) && (!data.files || data.files.length === 0)) {
                    html = `
                        <div class="px-4 py-6 text-center text-sm text-gray-400">
                            Tidak ada hasil ditemukan untuk "${escapeHtml(query)}"
                        </div>
                    `;
                }

                results.innerHTML = html;
            } catch (e) {
                console.error(e);
                loading.classList.add('hidden');
                results.innerHTML = `
                    <div class="px-4 py-6 text-center text-sm text-red-500">
                        Gagal memuat hasil.
                    </div>
                `;
            }
        }

        function handleFolderClick(id) {
            const currentPath = window.location.pathname;
            document.getElementById('search-autocomplete-dropdown').classList.add('hidden');
            document.getElementById('global-search-input').value = '';
            
            if (currentPath.includes('/drive')) {
                if (typeof loadFolder === 'function') {
                    loadFolder(id);
                    return;
                }
            }
            window.location.href = `${driveRootUrl}/folder/${id}`;
        }

        function handleGlobalSearch(query) {
            document.getElementById('search-autocomplete-dropdown').classList.add('hidden');
            const currentPath = window.location.pathname;
            if (currentPath === '/drive' || currentPath === '/drive/') {
                if (typeof loadSearch === 'function') {
                    if (query.trim() === '') {
                        loadRoot();
                    } else {
                        history.pushState({ search: query }, '', `${driveRootUrl}?search=${encodeURIComponent(query)}`);
                        loadSearch(query);
                    }
                    return;
                }
            }
            window.location.href = `${driveRootUrl}?search=${encodeURIComponent(query)}`;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }

        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('global-search-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('search-autocomplete-dropdown').classList.add('hidden');
            }
        });
    </script>

    <!-- Right Actions -->
    <div class="flex items-center gap-4">
        <!-- User Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 p-1 border border-transparent rounded-full hover:bg-gray-100 focus:outline-none transition">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=EBF4FF&color=1E3A8A&rounded=true" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full">
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b border-gray-100 text-sm">
                    <div class="font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-gray-500 truncate">{{ Auth::user()->email }}</div>
                </div>
                
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>

    <!-- Mobile Sidebar Overlay & Navigation -->
    <div x-show="open" class="fixed inset-0 z-40 flex md:hidden" style="display: none;">
        <!-- Overlay -->
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-25" @click="open = false"></div>
        
        <!-- Sidebar Content -->
        <div x-show="open" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-white pt-5 pb-4">
            
            <div class="px-4 mb-4 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 rounded-md bg-blue-600 text-white font-bold text-lg">D</div>
                    <span class="text-xl text-gray-700 font-semibold">VisiFoto</span>
                </a>
                <button @click="open = false" class="text-gray-500 hover:bg-gray-100 rounded-full p-2">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="px-3 mb-4 mt-2">
                <button onclick="window.location.href='{{ route('drive.index') }}'" class="flex items-center gap-3 bg-white border border-gray-200 shadow-sm text-gray-700 font-medium py-3 px-5 rounded-2xl w-full">
                    <svg class="w-6 h-6" viewBox="0 0 36 36"><path fill="#34A853" d="M16 16v14h4V20z"></path><path fill="#4285F4" d="M30 16H20l-4 4h14z"></path><path fill="#FBBC05" d="M6 16v4h10l4-4z"></path><path fill="#EA4335" d="M20 16V6h-4v14z"></path></svg>
                    Baru
                </button>
            </div>

            <div class="flex-1 h-0 overflow-y-auto px-3">
                <nav class="space-y-1">
                    <a href="{{ route('drive.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('drive.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('drive.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        Foto Saya
                    </a>
                    <a href="{{ route('public.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('public.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('public.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        Publik
                    </a>
                </nav>
            </div>
        </div>
    </div>
</nav>
