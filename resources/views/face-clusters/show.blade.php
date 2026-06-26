<x-app-layout>
    <div class="p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
            <a href="{{ route('face-clusters.index') }}" class="flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-800">{{ $cluster->name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $photos->count() }} foto ditemukan dalam kelompok ini</p>
            </div>
        </div>

        @if($photos->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <p class="text-gray-400 text-sm">Belum ada foto dalam kluster ini.</p>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            @foreach($photos as $photo)
            <div class="group bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow overflow-hidden">
                <div class="aspect-square bg-gray-50 overflow-hidden">
                    <img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </div>
                <div class="p-2.5 flex items-center justify-between border-t border-gray-100">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-700 truncate">{{ $photo['name'] }}</p>
                        @if($photo['folder_name'])
                        <p class="text-[10px] text-gray-400 truncate">{{ $photo['folder_name'] }}</p>
                        @endif
                    </div>
                    <a href="{{ $photo['url'] }}" target="_blank" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-app-layout>
