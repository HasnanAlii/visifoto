<x-app-layout>
    <div class="p-4 md:p-6" x-data="faceSearch()" @keydown.window.escape="closeViewer()" @keydown.window.right="nextImage()" @keydown.window.left="prevImage()">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-800">Cari Foto Saya</h1>
                <p class="text-sm text-gray-500 mt-0.5">Temukan semua foto yang menampilkan wajah Anda secara otomatis</p>
            </div>
            <div class="flex items-center gap-3">
                @if($serviceOnline)
                    <span class="flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-200 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> AI Aktif
                    </span>
                @else
                    <span class="flex items-center gap-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> AI Offline
                    </span>
                @endif
                <template x-if="tab === 'search' && (searchCompleted || searchFailed)">
                    <button @click="resetSearch()" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Cari Foto Lain
                    </button>
                </template>
            </div>
        </div>

        {{-- Service offline warning --}}
        @unless($serviceOnline)
        <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Layanan AI Belum Aktif</p>
                <code class="mt-1 block bg-amber-100 text-amber-900 text-xs font-mono px-3 py-2 rounded-lg">cd python-facenet && venv/bin/python3 start.py</code>
            </div>
        </div>
        @endunless

        <!-- ===== TAB: CARI FOTO ===== -->
        <div x-show="tab === 'search'">

            <!-- Form Upload -->
            <div x-show="!isSearching && !searchCompleted && !searchFailed" x-transition.opacity.duration.300ms class="max-w-xl mx-auto mt-4">
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <div class="text-center mb-5">
                        <h2 class="text-base font-semibold text-gray-800">Unggah Foto Wajah</h2>
                        <p class="text-xs text-gray-500 mt-1">Gunakan foto selfie dengan pencahayaan yang baik</p>
                    </div>

                    {{-- Filter Folder --}}
                    @if(count($folderStats) > 0)
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">🗂️ Cari dari folder</label>
                        <div class="relative">
                            <select x-model="selectedFolderId" class="w-full appearance-none bg-gray-50 border border-gray-200 text-sm text-gray-700 rounded-xl px-4 py-2.5 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="">— Semua Folder —</option>
                                @foreach($folderStats as $f)
                                    @if($f['with_face'] > 0 || $f['indexed'] > 0)
                                    <option value="{{ $f['id'] }}">{{ $f['name'] }} ({{ $f['with_face'] }} wajah terindeks)</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Pilih folder untuk mempercepat pencarian, atau biarkan kosong untuk cari di semua folder.</p>
                    </div>
                    @endif

                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl p-8 cursor-pointer hover:bg-gray-50 hover:border-blue-500 transition-all duration-200 group">
                        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Pilih atau Seret Foto Ke Sini</p>
                        <p class="text-xs text-gray-400 mt-1">PNG, JPG, atau JPEG (Maks. 5MB)</p>
                        <input id="photo-upload-input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileUpload">
                    </label>
                    <p class="text-center text-xs text-gray-400 mt-4">Foto dianalisis menggunakan AI (FaceNet) — tidak disimpan</p>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="isSearching" x-transition.opacity.duration.300ms class="flex flex-col items-center justify-center py-16">
                <div class="relative w-44 h-44 rounded-2xl overflow-hidden shadow-lg border-2 border-blue-500 mb-6 bg-gray-100">
                    <template x-if="uploadedImage"><img :src="uploadedImage" class="w-full h-full object-cover"></template>
                    <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-blue-500 to-transparent animate-scan shadow-[0_0_12px_rgba(59,130,246,0.8)] z-10"></div>
                    <div class="absolute inset-0 bg-blue-500 bg-opacity-10"></div>
                </div>

                <!-- Folder badge jika filter aktif -->
                <template x-if="selectedFolderName">
                    <div class="flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-medium px-3 py-1.5 rounded-full mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                        <span x-text="'Mencari di: ' + selectedFolderName"></span>
                    </div>
                </template>

                <div class="flex items-center gap-3 mb-3">
                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <p class="text-base font-semibold text-gray-700">Memindai Wajah & Mencari Foto...</p>
                </div>
                <p class="text-xs text-gray-400 mb-4">Menganalisis kemiripan fitur wajah di galeri</p>

                <!-- Tombol Batalkan -->
                <button @click="cancelSearch()" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Batalkan Pencarian
                </button>
            </div>

            <!-- Error State -->
            <div x-show="searchFailed" x-transition.opacity.duration.300ms class="max-w-xl mx-auto mt-4">
                <template x-if="uploadedImage">
                    <div class="flex justify-center mb-4">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-gray-200 opacity-60">
                            <img :src="uploadedImage" class="w-full h-full object-cover">
                        </div>
                    </div>
                </template>
                <div class="flex items-start gap-3 rounded-xl p-4 mb-4" :class="noFaceDetected ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200'">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" :class="noFaceDetected ? 'text-orange-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
                    <div>
                        <p class="text-sm font-semibold" :class="noFaceDetected ? 'text-orange-800' : 'text-red-800'" x-text="noFaceDetected ? 'Wajah Tidak Terdeteksi' : 'Pencarian Gagal'"></p>
                        <p class="text-xs mt-0.5" :class="noFaceDetected ? 'text-orange-700' : 'text-red-700'" x-text="errorMessage"></p>
                    </div>
                </div>
            </div>

            <!-- Results Grid -->
            <div x-show="searchCompleted" x-transition.opacity.duration.300ms class="mt-2">
                <div class="flex items-center gap-4 mb-5">
                    <template x-if="uploadedImage">
                        <div class="w-14 h-14 rounded-xl overflow-hidden border-2 border-blue-400 shadow-sm flex-shrink-0">
                            <img :src="uploadedImage" class="w-full h-full object-cover">
                        </div>
                    </template>
                    <div class="flex-1">
                        <template x-if="totalFound > 0">
                            <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl p-4">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <div>
                                    <p class="text-sm font-semibold text-green-800">Pencarian Berhasil</p>
                                    <p class="text-xs text-green-700 mt-0.5">Ditemukan <strong x-text="totalFound"></strong> foto dari <span x-text="totalChecked"></span> yang diindeks (kemiripan ≥ <span x-text="Math.round(thresholdUsed * 100)"></span>%).</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="totalFound === 0">
                            <div class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl p-4">
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <p class="text-sm text-gray-600">Tidak ada foto dengan kemiripan wajah yang cukup.</p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                    <template x-for="(img, index) in images" :key="index">
                        <div class="group bg-white border border-gray-200 rounded-xl hover:shadow-md transition-shadow flex flex-col overflow-hidden">
                            <div @click="openViewer(index)" class="aspect-square bg-gray-50 overflow-hidden relative cursor-pointer">
                                <img :src="img.thumbnail_url" :alt="img.original_name" class="w-full h-full object-cover" loading="lazy">
                                <div class="absolute top-1.5 right-1.5 bg-black/60 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" x-text="img.similarity_pct + '%'"></div>
                            </div>
                            <div class="p-2.5 flex items-center justify-between border-t border-gray-100">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-medium text-gray-700 truncate" x-text="img.original_name"></span>
                                    <span class="text-[10px] text-gray-400 truncate" x-text="img.folder_name || 'Root'"></span>
                                </div>
                                <a :href="img.url" target="_blank" @click.stop class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>



        <!-- Lightbox -->
        <div x-show="viewerOpen" style="display:none" class="fixed inset-0 z-[100] flex items-center justify-center">
            <div x-show="viewerOpen" x-transition.opacity.duration.300ms class="absolute inset-0 bg-black/90 backdrop-blur-sm" @click="closeViewer()"></div>
            <div class="fixed top-4 right-4 flex items-center gap-2.5 z-[110]">
                <div class="text-white bg-black/60 px-4 py-2 rounded-full text-xs font-semibold"><span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span></div>
                <a :href="currentImage" download target="_blank" class="text-white bg-black/60 hover:bg-black/80 rounded-full p-2.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
                <button @click="closeViewer()" class="text-white bg-black/60 hover:bg-black/80 rounded-full p-2.5 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <button @click.stop="prevImage()" class="fixed left-4 top-1/2 -translate-y-1/2 text-white bg-black/60 hover:bg-black/80 rounded-full p-3 z-[110]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button @click.stop="nextImage()" class="fixed right-4 top-1/2 -translate-y-1/2 text-white bg-black/60 hover:bg-black/80 rounded-full p-3 z-[110]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <div x-show="viewerOpen" x-transition.scale.80.duration.300ms class="relative z-10 flex flex-col items-center p-4" @click.self="closeViewer()">
                <img :src="currentImage" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl" @click.stop>
                <div class="mt-3 flex items-center gap-3" @click.stop>
                    <span class="text-white text-xs bg-black/60 px-3 py-1.5 rounded-full" x-text="images[currentIndex]?.original_name"></span>
                    <span class="text-green-300 text-xs font-bold bg-black/60 px-3 py-1.5 rounded-full" x-text="'Kemiripan: ' + (images[currentIndex]?.similarity_pct || 0) + '%'"></span>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes scanEffect { 0%,100%{top:0%} 50%{top:100%} }
        .animate-scan { animation: scanEffect 2s infinite ease-in-out; position: absolute; }
    </style>

    <script>
    function faceSearch() {
        return {
            tab: 'search',
            uploadedImage: null,
            uploadedFile: null,
            isSearching: false,
            searchCompleted: false,
            searchFailed: false,
            noFaceDetected: false,
            errorMessage: '',
            viewerOpen: false,
            currentIndex: 0,
            images: [],
            totalFound: 0,
            totalChecked: 0,
            thresholdUsed: 0,
            selectedFolderId: '',
            selectedFolderName: '',
            // AbortController untuk cancel
            _abortController: null,

            get currentImage() { return this.images[this.currentIndex]?.url || ''; },

            handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                if (file.size > 5 * 1024 * 1024) { alert('Maksimal 5MB.'); return; }
                // Simpan nama folder yang dipilih untuk ditampilkan di loading
                const sel = document.querySelector('select[x-model="selectedFolderId"]');
                this.selectedFolderName = sel && this.selectedFolderId
                    ? sel.options[sel.selectedIndex].text.replace(/ \(.*\)/, '')
                    : '';
                this.uploadedImage = URL.createObjectURL(file);
                this.uploadedFile = file;
                this.triggerSearch();
            },

            async triggerSearch() {
                this.isSearching = true;
                this.searchCompleted = false;
                this.searchFailed = false;
                this.noFaceDetected = false;
                this.errorMessage = '';
                this.images = [];

                this._abortController = new AbortController();

                const formData = new FormData();
                formData.append('photo', this.uploadedFile);
                formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
                if (this.selectedFolderId) {
                    formData.append('folder_id', this.selectedFolderId);
                }

                const startTime = Date.now();

                try {
                    const res = await fetch('{{ route('face-search.search') }}', {
                        method: 'POST',
                        body: formData,
                        signal: this._abortController.signal,
                    });
                    const data = await res.json();

                    // Minimum loading duration of 3 seconds to let scanning animation run
                    const elapsedTime = Date.now() - startTime;
                    const minDelay = 3000;
                    if (elapsedTime < minDelay && !this._abortController.signal.aborted) {
                        await new Promise((resolve, reject) => {
                            const timeoutId = setTimeout(() => {
                                this._abortController.signal.removeEventListener('abort', onAbort);
                                resolve();
                            }, minDelay - elapsedTime);

                            const onAbort = () => {
                                clearTimeout(timeoutId);
                                reject(new DOMException('Aborted', 'AbortError'));
                            };

                            this._abortController.signal.addEventListener('abort', onAbort);
                        });
                    }

                    if (res.ok && data.success) {
                        this.images = data.matches || [];
                        this.totalFound = data.total_found || 0;
                        this.totalChecked = data.total_checked || 0;
                        this.thresholdUsed = data.threshold_used || 0;
                        this.searchCompleted = true;
                    } else {
                        this.noFaceDetected = !! data.no_face;
                        this.searchFailed = true;
                        this.errorMessage = data.message || 'Gagal mencari foto.';
                    }
                } catch (err) {
                    if (err.name === 'AbortError') {
                        // Dibatalkan user — kembali ke form
                        this.resetSearch();
                        return;
                    }
                    this.searchFailed = true;
                    this.errorMessage = 'Koneksi ke server gagal.';
                } finally {
                    this.isSearching = false;
                    this._abortController = null;
                }
            },

            cancelSearch() {
                if (this._abortController) {
                    this._abortController.abort();
                }
            },

            resetSearch() {
                this.uploadedImage = null;
                this.uploadedFile = null;
                this.isSearching = false;
                this.searchCompleted = false;
                this.searchFailed = false;
                this.noFaceDetected = false;
                this.errorMessage = '';
                this.images = [];
                this.selectedFolderName = '';
                const input = document.getElementById('photo-upload-input');
                if (input) input.value = '';
            },


            openViewer(index) { this.currentIndex = index; this.viewerOpen = true; document.body.classList.add('overflow-hidden'); },
            closeViewer() { this.viewerOpen = false; document.body.classList.remove('overflow-hidden'); },
            nextImage() { this.currentIndex = (this.currentIndex < this.images.length - 1) ? this.currentIndex + 1 : 0; },
            prevImage() { this.currentIndex = (this.currentIndex > 0) ? this.currentIndex - 1 : this.images.length - 1; },
        }
    }
    </script>
</x-app-layout>
