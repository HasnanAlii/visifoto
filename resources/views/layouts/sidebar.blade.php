<aside class="hidden md:flex flex-col w-56 bg-white flex-shrink-0 pt-4 border-r border-gray-100">
    <!-- Dropdown Baru Sidebar -->
    <div class="px-3 mb-4 relative" id="sidebar-new-wrapper">
        <button onclick="toggleSidebarNew(event)" class="flex items-center gap-2.5 bg-white border border-gray-200 shadow-sm hover:shadow-md hover:bg-gray-50 text-gray-700 font-medium py-2.5 px-4 rounded-2xl transition-shadow w-full text-sm select-none">
            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 36 36"><path fill="#34A853" d="M16 16v14h4V20z"></path><path fill="#4285F4" d="M30 16H20l-4 4h14z"></path><path fill="#FBBC05" d="M6 16v4h10l4-4z"></path><path fill="#EA4335" d="M20 16V6h-4v14z"></path><path fill="none" d="M0 0h36v36H0z"></path></svg>
            Baru
            {{-- <svg class="w-3.5 h-3.5 ml-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg> --}}
        </button>

        <!-- Dropdown Menu -->
        <div id="sidebar-new-menu" class="hidden absolute left-3 right-3 top-full mt-1.5 bg-white rounded-xl shadow-xl border border-gray-100 z-50 py-1 overflow-hidden">
            <button onclick="typeof openUploadForm === 'function' ? (closeSidebarNew(), openUploadForm()) : window.location.href='{{ route('drive.index') }}'" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div class="text-left">
                    <div class="font-medium text-gray-800 text-xs">Upload Foto</div>
                    <div class="text-xs text-gray-400">JPG, PNG, PDF...</div>
                </div>
            </button>
            <hr class="border-gray-100 mx-3">
            <button onclick="typeof openUploadForm === 'function' ? (closeSidebarNew(), openUploadForm(true)) : window.location.href='{{ route('drive.index') }}'" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </div>
                <div class="text-left">
                    <div class="font-medium text-gray-800 text-xs">Upload Folder</div>
                    <div class="text-xs text-gray-400">Upload isi folder sekaligus</div>
                </div>
            </button>
            <hr class="border-gray-100 mx-3">
            <button onclick="typeof openFolderForm === 'function' ? (closeSidebarNew(), openFolderForm()) : window.location.href='{{ route('drive.index') }}'" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                </div>
                <div class="text-left">
                    <div class="font-medium text-gray-800 text-xs">Folder Baru</div>
                    <div class="text-xs text-gray-400">Buat direktori baru</div>
                </div>
            </button>
        </div>
    </div>

    <nav class="flex-1 space-y-0.5 px-2">
        <a href="{{ route('drive.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('drive.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('drive.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
            Foto Saya
        </a>
        <a href="{{ route('public.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('public.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('public.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
            Publik
        </a>
        <a href="{{ route('face-clusters.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('face-clusters.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('face-clusters.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Pengelompokan Wajah
        </a>
        <a href="{{ route('face-search.index') }}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-r-full {{ request()->routeIs('face-search.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
            <svg class="mr-3 h-5 w-5 {{ request()->routeIs('face-search.*') ? 'text-blue-700' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Cari Foto Saya
        </a>
    </nav>
</aside>
