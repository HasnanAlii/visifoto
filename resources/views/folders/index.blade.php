<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Folder') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg min-h-[500px]">
                
                <!-- Drive Toolbar -->
                <div class="border-b border-gray-200 p-4 flex flex-col sm:flex-row justify-between items-center bg-gray-50">
                    <div class="flex items-center space-x-4 w-full sm:w-auto mb-4 sm:mb-0">
                        <button type="button" class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 rounded-full shadow-sm flex items-center shadow" onclick="openForm()">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Folder Baru
                        </button>
                        
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari Folder" onkeyup="filterFolders()">
                        </div>
                    </div>
                </div>

                <!-- Drive Grid Container -->
                <div class="p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Daftar Folder</h3>
                    
                    <div id="drive-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        <!-- Data dimuat melalui AJAX -->
                    </div>
                    
                    <div id="empty-state" class="hidden flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-24 h-24 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                        <p class="text-lg font-medium">Belum ada folder</p>
                        <p class="text-sm">Gunakan tombol "Folder Baru" untuk membuat direktori penyimpanan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('folders.form')
    @include('folders.script')
</x-app-layout>
