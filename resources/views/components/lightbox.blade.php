<!-- Lightbox Viewer -->
<div id="lightbox-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black bg-opacity-90 p-4" onclick="if(event.target===this) closeLightbox()">
    <!-- Close -->
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white bg-white bg-opacity-10 hover:bg-opacity-25 rounded-full p-2 transition z-10">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <!-- Prev -->
    <button id="lightbox-prev" onclick="navigateLightbox(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-white bg-opacity-10 hover:bg-opacity-25 rounded-full p-3 transition z-10">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <!-- Next -->
    <button id="lightbox-next" onclick="navigateLightbox(1)" class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-white bg-opacity-10 hover:bg-opacity-25 rounded-full p-3 transition z-10">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <!-- Image + info -->
    <div class="max-w-5xl max-h-full w-full flex flex-col items-center gap-3">
        <img id="lightbox-img" src="" alt="" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-2xl select-none" style="transition: opacity 0.15s;">
        <div class="flex items-center gap-4">
            <p id="lightbox-name" class="text-white text-sm opacity-70 truncate max-w-xs"></p>
            <p id="lightbox-counter" class="text-white text-xs opacity-50 flex-shrink-0"></p>
        </div>
    </div>
</div>

<script>
let lightboxImages = [];
let lightboxIndex = 0;

function openLightbox(index) {
    lightboxIndex = index;
    const item = lightboxImages[lightboxIndex];
    if (!item) return;
    document.getElementById('lightbox-img').src = item.src;
    document.getElementById('lightbox-name').innerText = item.name;
    const lb = document.getElementById('lightbox-modal');
    lb.classList.remove('hidden');
    lb.classList.add('flex');
    document.body.style.overflow = 'hidden';
    updateLightboxNav();
}

function navigateLightbox(dir) {
    lightboxIndex = (lightboxIndex + dir + lightboxImages.length) % lightboxImages.length;
    const item = lightboxImages[lightboxIndex];
    const img = document.getElementById('lightbox-img');
    img.style.opacity = '0';
    img.style.transition = 'opacity 0.15s';
    setTimeout(() => {
        img.src = item.src;
        document.getElementById('lightbox-name').innerText = item.name;
        img.style.opacity = '1';
    }, 150);
    updateLightboxNav();
}

function updateLightboxNav() {
    const total = lightboxImages.length;
    document.getElementById('lightbox-counter').innerText = total > 1 ? `${lightboxIndex + 1} / ${total}` : '';
    document.getElementById('lightbox-prev').style.visibility = total > 1 ? 'visible' : 'hidden';
    document.getElementById('lightbox-next').style.visibility = total > 1 ? 'visible' : 'hidden';
}

function closeLightbox() {
    const lb = document.getElementById('lightbox-modal');
    lb.classList.add('hidden');
    lb.classList.remove('flex');
    document.getElementById('lightbox-img').src = '';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    const lb = document.getElementById('lightbox-modal');
    if (!lb || lb.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') navigateLightbox(1);
    if (e.key === 'ArrowLeft') navigateLightbox(-1);
});
</script>
