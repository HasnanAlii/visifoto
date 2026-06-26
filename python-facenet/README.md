# VisiFoto FaceNet Service

Backend Python untuk fitur **"Cari Foto Saya"** — mencari foto berdasarkan kemiripan wajah menggunakan:

| Library | Fungsi |
|---|---|
| **MTCNN** | Deteksi wajah (Multi-task Cascaded CNN) |
| **FaceNet** | Ekstraksi fitur wajah (InceptionResnetV1, VGGFace2) |
| **OpenCV** | Pre-processing citra |
| **scikit-learn** | Cosine similarity |
| **PyMySQL** | Simpan embedding ke MySQL Laravel |
| **FastAPI** | REST API server |

---

## Instalasi

```bash
cd python-facenet

# Install semua dependensi Python (hanya sekali)
python start.py --install
```

> **Catatan:** Proses install bisa memakan waktu 5–15 menit karena mengunduh model PyTorch (~100MB).

---

## Menjalankan Service

```bash
cd python-facenet

# Mode normal
python start.py

# Mode development (auto-reload saat kode berubah)
python start.py --reload

# Port kustom
python start.py --port 8002
```

Service berjalan di `http://127.0.0.1:8001`

---

## Indexing Foto

Sebelum fitur pencarian dapat bekerja, semua foto di storage harus di-index.

### Cara 1: Via Laravel (Admin)

```
POST /face-search/index
```

### Cara 2: Via Script Python

```bash
cd python-facenet

# Index semua foto
python index_storage.py

# Index subfolder tertentu (misal hanya drive-files)
python index_storage.py --path drive-files

# Background indexing (cocok untuk banyak foto)
python index_storage.py --async-mode
```

### Cara 3: Via API Langsung

```bash
# Sinkron
curl -X POST http://127.0.0.1:8001/index-files

# Async/background
curl -X POST http://127.0.0.1:8001/index-files/async
```

---

## API Endpoints

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/status` | Status service & statistik |
| `POST` | `/index-files` | Index semua foto (sinkron) |
| `POST` | `/index-files/async` | Index semua foto (background) |
| `POST` | `/index-single` | Index satu file baru |
| `POST` | `/search` | Cari foto berdasarkan wajah |
| `DELETE` | `/index` | Hapus semua embedding |

Dokumentasi interaktif tersedia di: **http://127.0.0.1:8001/docs**

---

## Konfigurasi `.env`

| Variable | Default | Keterangan |
|---|---|---|
| `LARAVEL_STORAGE_PATH` | `../storage/app/public` | Path ke storage Laravel |
| `DB_HOST` | `127.0.0.1` | Host MySQL |
| `DB_DATABASE` | `app_pemotretan` | Nama database |
| `DB_USERNAME` | `root` | Username MySQL |
| `DB_PASSWORD` | _(kosong)_ | Password MySQL |
| `SIMILARITY_THRESHOLD` | `0.70` | Ambang batas kemiripan (0.0–1.0) |

---

## Alur Kerja

```
[User upload foto wajah]
        │
        ▼
[Laravel POST /face-search/search]
        │
        ▼
[Python: MTCNN deteksi wajah → FaceNet extract embedding 512-dim]
        │
        ▼
[Python: Cosine similarity vs semua embedding di MySQL]
        │
        ▼
[Python: Return matches (drive_file_id + similarity%)]
        │
        ▼
[Laravel: Resolve drive_file_id → DriveFile → download URL]
        │
        ▼
[Frontend: Tampilkan grid foto hasil dengan badge similarity%]
```

---

## Fitur Baru: Pencarian Per Folder & Pembatalan

### Cari Foto dari Folder Tertentu

Pengguna bisa membatasi pencarian hanya pada folder tertentu untuk mempercepat proses. Filter dilakukan di sisi **Laravel** — Python tetap mengembalikan semua kandidat, kemudian Laravel menyaring berdasarkan `folder_id`.

**Alur:**
```
[User pilih folder di dropdown → upload foto]
        │
        ▼
[POST /face-search/search?folder_id=6]
        │
        ▼
[Python: extract embedding → cosine similarity (semua embedding)]
        │
        ▼
[Laravel: filter hasil → hanya drive_files dengan folder_id IN (6, subfolder...)]
        │
        ▼
[Frontend: tampilkan foto dari folder yang dipilih saja]
```

### Batalkan Pencarian

Tombol **"Batalkan Pencarian"** muncul saat AI sedang memindai wajah. Dibuat menggunakan `AbortController` di JavaScript — membatalkan HTTP request ke server secara langsung.

```js
const controller = new AbortController();
fetch('/face-search/search', { signal: controller.signal, ... });

// Untuk membatalkan:
controller.abort();
```

---

## Fitur Baru: Pengelompokan Wajah Otomatis (DBSCAN)

### Deskripsi

Mengelompokkan foto-foto dari folder/file **publik** secara otomatis berdasarkan kemiripan wajah menggunakan algoritma **DBSCAN** (*Density-Based Spatial Clustering of Applications with Noise*).

| Library | Fungsi |
|---|---|
| **DBSCAN** (scikit-learn) | Clustering berbasis densitas — tidak perlu tentukan jumlah kluster |
| **FaceNet** | Embedding 512-dim sebagai representasi wajah |
| **Cosine distance** | Metrik jarak antar embedding |

### Parameter DBSCAN

| Variable `.env` | Default | Keterangan |
|---|---|---|
| `DBSCAN_EPS` | `0.4` | Radius kluster (cosine distance). Kecilkan untuk lebih ketat |
| `DBSCAN_MIN_SAMPLES` | `2` | Minimal foto per kluster. 1 = semua wajah jadi kluster |

> **Tips:** `eps=0.4` berarti dua wajah dianggap "sama orang" jika cosine similarity ≥ 60%. Naikkan ke `0.5` jika terlalu banyak kluster, turunkan ke `0.3` jika terlalu sedikit.

### API Endpoint Clustering

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/cluster` | Jalankan DBSCAN (sinkron/async) |
| `GET` | `/clusters` | Daftar semua kluster |
| `GET` | `/clusters/{id}/photos` | Foto dalam satu kluster |

**Contoh penggunaan:**

```bash
# Cluster semua folder publik (sinkron)
curl -X POST http://127.0.0.1:8001/cluster

# Cluster folder tertentu (ID 6 dan 7)
curl -X POST http://127.0.0.1:8001/cluster \
  -d "folder_ids=6,7"

# Jalankan di background (async)
curl -X POST http://127.0.0.1:8001/cluster \
  -d "async_mode=true"

# Lihat hasil kluster
curl http://127.0.0.1:8001/clusters

# Foto dalam kluster ID 3
curl http://127.0.0.1:8001/clusters/3/photos
```

### Auto-Trigger via Laravel Observer

Clustering berjalan **otomatis** tanpa perlu dijalankan manual ketika:

- 📁 **Folder** diubah status `is_public` → `true` → semua foto di folder di-index & di-cluster
- 🖼️ **File gambar baru** di-upload ke folder publik → di-index & di-cluster

```
[Folder/File jadi Publik]
        │
        ▼
[FolderObserver / DriveFileObserver (Laravel)]
        │
        ├─► Index foto baru yang belum terindeks
        │
        └─► POST /cluster?async_mode=true&folder_ids={id}
                │
                ▼
        [DBSCAN jalan di background]
                │
                ▼
        [Hasil disimpan ke face_clusters + face_embeddings.face_cluster_id]
```

### Alur Kerja Clustering Lengkap

```
[Foto publik terindeks di face_embeddings]
        │
        ▼
[Ambil semua embedding publik dari MySQL]
        │
        ▼
[Normalisasi L2 → matrix (N x 512)]
        │
        ▼
[DBSCAN: eps=0.4, metric=cosine, min_samples=2]
        │
        ├─► Label -1 = NOISE (wajah unik, tidak dikelompokkan)
        │
        └─► Label 0,1,2,... = Kluster wajah
                │
                ▼
        [Pilih "representative" = foto paling dekat ke centroid kluster]
                │
                ▼
        [INSERT INTO face_clusters (name, representative_drive_file_id, member_count)]
                │
                ▼
        [UPDATE face_embeddings SET face_cluster_id = {cluster_id}]
```

### Rename Kluster di UI

Nama kluster default adalah `Orang 1`, `Orang 2`, dst. Pengguna bisa mengganti nama dengan **klik 2x** pada nama di halaman `/face-clusters`. Rename dikirim ke:

```
PATCH /face-clusters/{id}/rename
Body: { name: "Nama Baru" }
```
