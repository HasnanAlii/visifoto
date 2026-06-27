<x-app-layout>
    <div class="p-4 md:p-6">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-1 text-sm flex-wrap" id="breadcrumb-container">
                <a href="{{ route('drive.index') }}" class="text-blue-600 hover:underline font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    VisiFoto
                </a>
                <span id="breadcrumb-items"></span>
            </div>

            <!-- Actions -->
            {{-- <div class="flex items-center gap-2 flex-shrink-0">
                <div class="relative hidden sm:block">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="search-input" class="pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:ring-1 focus:ring-blue-400 focus:outline-none w-52 transition-all" placeholder="Cari di VisiFoto" onkeyup="filterItems()">
                </div>
                <div class="relative" id="new-dropdown-wrapper">
                    <button onclick="toggleNewDropdown(event)" class="flex items-center gap-2 text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition shadow-sm select-none">
                        <svg class="w-4 h-4" viewBox="0 0 36 36"><path fill="#fff" opacity=".8" d="M16 16v14h4V20z"></path><path fill="#fff" opacity=".8" d="M30 16H20l-4 4h14z"></path><path fill="#fff" opacity=".6" d="M6 16v4h10l4-4z"></path><path fill="#fff" opacity=".9" d="M20 16V6h-4v14z"></path></svg>
                        Baru
                    </button>

                    <div id="new-dropdown-menu" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 z-50 py-1 overflow-hidden">
                        <button onclick="openUploadForm(); closeNewDropdown()" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-800">Upload Foto</div>
                                <div class="text-xs text-gray-400">JPG, PNG, GIF, PDF...</div>
                            </div>
                        </button>
                        <hr class="border-gray-100 mx-3">
                        <button onclick="openFolderForm(); closeNewDropdown()" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-800">Folder Baru</div>
                                <div class="text-xs text-gray-400">Buat direktori baru</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div> --}}
        </div>

        <!-- Grid Content -->
        <div id="drive-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <!-- Dirender via JS -->
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden flex-col items-center justify-center py-20 text-gray-400">
            <svg class="w-24 h-24 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <p class="text-base font-medium text-gray-500">Folder ini masih kosong</p>
            <p class="text-sm mt-1">Upload foto atau buat folder baru untuk memulai.</p>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="flex flex-col items-center justify-center py-20 text-gray-400">
            <svg class="animate-spin w-10 h-10 text-blue-400 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <p class="text-sm">Memuat...</p>
        </div>
    </div>

    @include('components.lightbox')
    @include('drive.form')
    @include('drive.script')
</x-app-layout>
