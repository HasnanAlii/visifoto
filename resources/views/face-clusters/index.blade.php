<x-app-layout>
    <div class="p-4 md:p-6" x-data="faceClusters()">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Pengelompokan Wajah</h1>
                <p class="text-sm text-gray-500 mt-0.5">Foto dikelompokkan otomatis menggunakan AI (DBSCAN) berdasarkan kemiripan wajah</p>
            </div>
            <div class="flex items-center gap-3">
                @if($totalClusters > 0)
                    <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full font-medium">
                        {{ $totalClusters }} orang · {{ $totalPhotos }} foto
                    </span>
                @endif
                <button
                    @click="runClustering()"
                    :disabled="isRunning"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 rounded-xl transition-all"
                >
                    <svg class="w-4 h-4" :class="isRunning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="isRunning ? 'Mengelompokkan...' : 'Jalankan Clustering'"></span>
                </button>
            </div>
        </div>

        <!-- Status banner -->
        <template x-if="statusMsg">
            <div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl border text-sm"
                 :class="statusOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusOk ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'"/>
                </svg>
                <span x-text="statusMsg"></span>
            </div>
        </template>

        <!-- Empty state -->
        @if($totalClusters === 0)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-5">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="text-gray-600 font-medium text-base">Belum Ada Pengelompokan</p>
            <p class="text-gray-400 text-sm mt-1 max-w-sm">Jadikan folder sebagai Publik, lalu klik <strong>Jalankan Clustering</strong> untuk mengelompokkan wajah secara otomatis.</p>
            <p class="text-xs text-gray-400 mt-3 bg-gray-50 px-4 py-2 rounded-lg">Clustering berjalan otomatis saat folder baru menjadi publik.</p>
        </div>
        @endif

        <!-- Grid Kluster -->
        @if($totalClusters > 0)
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6 mt-2">
            @foreach($clusters as $cluster)
                <div class="flex flex-col items-center group" x-data="{ editing: false, name: '{{ addslashes($cluster['name']) }}' }">
                    <a href="{{ route('face-clusters.show', $cluster['id']) }}" class="flex flex-col items-center w-full">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden border-4 border-white shadow-sm ring-1 ring-gray-100 group-hover:ring-blue-500 group-hover:shadow-md transition-all duration-200 relative mb-3 bg-gray-100">
                            @if($cluster['thumbnail_url'])
                                <img src="{{ $cluster['thumbnail_url'] }}" alt="{{ $cluster['name'] }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                            @endif
                        </div>
                    </a>

                    <!-- Nama (editable) -->
                    <template x-if="!editing">
                        <span @dblclick="editing = true" class="text-sm font-medium text-gray-700 group-hover:text-blue-600 truncate max-w-full px-2 text-center cursor-text" x-text="name" title="Klik 2x untuk ganti nama"></span>
                    </template>
                    <template x-if="editing">
                        <input
                            x-model="name"
                            @keydown.enter="renameCluster({{ $cluster['id'] }}, name); editing = false"
                            @keydown.escape="editing = false"
                            @blur="renameCluster({{ $cluster['id'] }}, name); editing = false"
                            class="text-sm font-medium text-blue-700 border-b border-blue-500 bg-transparent text-center w-full px-1 outline-none"
                            x-init="$el.focus()"
                        >
                    </template>

                    <span class="text-xs text-gray-400 mt-0.5">{{ $cluster['member_count'] }} foto</span>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <script>
    function faceClusters() {
        return {
            isRunning: false,
            statusMsg: '',
            statusOk: false,

            async runClustering() {
                this.isRunning = true;
                this.statusMsg = '';
                try {
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('async', '0');
                    const res = await fetch('{{ route('face-clusters.run') }}', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        const r = data.result || {};
                        this.statusMsg = `Clustering selesai: ${r.clusters ?? '?'} kluster, ${r.total_faces ?? '?'} wajah diproses. Refresh halaman untuk melihat hasilnya.`;
                        this.statusOk = true;
                    } else {
                        this.statusMsg = data.message || 'Clustering gagal.';
                        this.statusOk = false;
                    }
                } catch {
                    this.statusMsg = 'Koneksi ke server gagal.';
                    this.statusOk = false;
                } finally {
                    this.isRunning = false;
                }
            },

            async renameCluster(id, name) {
                if (!name || !name.trim()) return;
                try {
                    const fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('_method', 'PATCH');
                    fd.append('name', name.trim());
                    await fetch(`/face-clusters/${id}/rename`, { method: 'POST', body: fd });
                } catch {}
            }
        }
    }
    </script>
</x-app-layout>
