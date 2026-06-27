"""
FaceNet Face Recognition Service (MySQL-backed)
=================================================
Backend Python untuk fitur "Cari Foto Saya" di aplikasi VisiFoto.

Stack:
- FastAPI       : REST API server
- MTCNN         : Face detector (Multi-task Cascaded Convolutional Networks)
- FaceNet       : Feature extractor (InceptionResnetV1, pretrained=vggface2)
- DBSCAN        : Clustering wajah (untuk fitur pengelompokan)
- OpenCV        : Pre-processing citra + klasifikasi objek
- scikit-learn  : Cosine similarity
- PyMySQL       : Koneksi ke MySQL Laravel

Endpoint:
  POST /search           -> Cari foto berdasarkan foto referensi wajah
  POST /analyze          -> Deteksi & klasifikasi semua wajah (manusia/boneka/objek)
  POST /index-files      -> Indeks ulang semua file foto dari storage
  POST /index-single     -> Indeks satu file (dipanggil setelah upload)
  GET  /status           -> Status service & statistik index
  DELETE /index          -> Hapus semua embedding (re-index)

Klasifikasi Subjek (classify_face_type):
  'manusia' -> Wajah manusia nyata (skin color OK, texture tinggi, gradasi natural)
  'boneka'  -> Boneka / mannequin / patung / wajah tidak nyata
  'objek'   -> Objek lain yang salah terdeteksi sebagai wajah
"""

import os
import io
import json
import time
import logging
import hashlib
from pathlib import Path
from typing import Optional

import cv2
import numpy as np
from PIL import Image
from fastapi import FastAPI, File, UploadFile, HTTPException, BackgroundTasks, Form
from fastapi.middleware.cors import CORSMiddleware
import torch
from facenet_pytorch import MTCNN, InceptionResnetV1
from sklearn.metrics.pairwise import cosine_similarity
import pymysql
import pymysql.cursors

# ─── Logging ──────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger(__name__)

# ─── Config ───────────────────────────────────────────────────────────────────
# Storage: folder public disk Laravel  (storage/app/public)
STORAGE_PATH = Path(os.getenv("LARAVEL_STORAGE_PATH", "../storage/app/public"))
SIMILARITY_THRESHOLD = float(os.getenv("SIMILARITY_THRESHOLD", "0.70"))
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

# Database MySQL (baca dari env, default sama dengan .env Laravel)
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_DATABASE = os.getenv("DB_DATABASE", "app_pemotretan")
DB_USERNAME = os.getenv("DB_USERNAME", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

logger.info(f"Device: {DEVICE}")
logger.info(f"Storage path: {STORAGE_PATH.resolve()}")
logger.info(f"DB: {DB_USERNAME}@{DB_HOST}:{DB_PORT}/{DB_DATABASE}")

# ─── Model Init ───────────────────────────────────────────────────────────────
logger.info("Loading MTCNN & FaceNet models...")
# mtcnn_single: untuk foto selfie/referensi (ambil wajah terbaik saja)
mtcnn_single = MTCNN(
    image_size=160,
    margin=20,
    min_face_size=20,
    thresholds=[0.6, 0.7, 0.7],
    factor=0.709,
    keep_all=False,
    device=DEVICE,
)
# mtcnn_all: untuk foto ramai (deteksi SEMUA wajah)
mtcnn_all = MTCNN(
    image_size=160,
    margin=20,
    min_face_size=20,
    thresholds=[0.6, 0.7, 0.7],
    factor=0.709,
    keep_all=True,
    device=DEVICE,
)
facenet = InceptionResnetV1(pretrained="vggface2").eval().to(DEVICE)
logger.info("Models loaded successfully.")


# ─── Database Helper ──────────────────────────────────────────────────────────
def get_db():
    return pymysql.connect(
        host=DB_HOST,
        port=DB_PORT,
        user=DB_USERNAME,
        password=DB_PASSWORD,
        database=DB_DATABASE,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
        connect_timeout=5,
    )


# ─── FastAPI App ───────────────────────────────────────────────────────────────
app = FastAPI(
    title="VisiFoto FaceNet Service",
    description="Face recognition service menggunakan MTCNN + FaceNet",
    version="2.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─── Core Functions ────────────────────────────────────────────────────────────

def preprocess_image(image_bytes: bytes) -> Image.Image:
    """Pre-process bytes gambar dengan OpenCV -> PIL Image RGB."""
    nparr = np.frombuffer(image_bytes, np.uint8)
    img_cv = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

    if img_cv is None:
        raise ValueError("Gambar tidak dapat dibaca.")

    img_rgb = cv2.cvtColor(img_cv, cv2.COLOR_BGR2RGB)

    # Resize jika terlalu besar
    h, w = img_rgb.shape[:2]
    max_dim = 1280
    if max(h, w) > max_dim:
        scale = max_dim / max(h, w)
        img_rgb = cv2.resize(img_rgb, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)

    return Image.fromarray(img_rgb)


def get_embedding(image: Image.Image) -> tuple[Optional[np.ndarray], Optional[list], Optional[float]]:
    """
    Detect wajah terbaik & extract embedding (untuk foto referensi/selfie).
    Returns: (embedding_array, bbox_list, confidence) atau (None, None, None)
    """
    img_rgb = image.convert("RGB")

    # Detect dengan probabilitas — ambil 1 wajah terbaik saja
    boxes, probs = mtcnn_single.detect(img_rgb)

    if boxes is None or len(boxes) == 0:
        return None, None, None

    # Ambil wajah dengan confidence tertinggi
    best_idx = int(np.argmax(probs))
    box = boxes[best_idx]
    prob = float(probs[best_idx])

    # Crop & preprocess wajah
    face_tensor = mtcnn_single(img_rgb)
    if face_tensor is None:
        return None, None, None

    face_tensor = face_tensor.unsqueeze(0).to(DEVICE)

    with torch.no_grad():
        embedding = facenet(face_tensor)

    return (
        embedding.cpu().numpy()[0],           # ndarray (512,)
        [float(x) for x in box],              # [x1, y1, x2, y2]
        prob,
    )


def get_all_embeddings(image: Image.Image) -> list[dict]:
    """
    Detect SEMUA wajah dalam gambar & extract masing-masing embedding.
    Dipakai saat indexing foto (bisa berisi banyak orang).

    Returns: list of dict {embedding, bbox, confidence, face_index}
             List kosong jika tidak ada wajah terdeteksi.
    """
    img_rgb = image.convert("RGB")

    # Deteksi semua wajah sekaligus
    boxes, probs = mtcnn_all.detect(img_rgb)

    if boxes is None or len(boxes) == 0:
        return []

    # Crop semua wajah
    face_tensors = mtcnn_all(img_rgb)  # Tensor (N, 3, 160, 160) atau None
    if face_tensors is None:
        return []

    # mtcnn_all bisa return tensor tunggal jika hanya 1 wajah — normalisasi
    if face_tensors.dim() == 3:
        face_tensors = face_tensors.unsqueeze(0)

    face_tensors = face_tensors.to(DEVICE)

    with torch.no_grad():
        embeddings = facenet(face_tensors)  # (N, 512)

    results = []
    emb_np = embeddings.cpu().numpy()
    for i, (box, prob) in enumerate(zip(boxes, probs)):
        if i >= len(emb_np):
            break
        results.append({
            "embedding": emb_np[i],            # ndarray (512,)
            "bbox": [float(x) for x in box],  # [x1, y1, x2, y2]
            "confidence": float(prob),
            "face_index": i,
        })

    return results


# ─── Face Type Classification ───────────────────────────────────────────────────────────────

def classify_face_type(
    pil_image: Image.Image,
    bbox: list,
    mtcnn_confidence: float = 0.0,
) -> dict:
    """
    Klasifikasi jenis subjek pada area wajah yang terdeteksi.

    Kategori:
      'manusia' — Wajah manusia nyata
      'boneka'  — Boneka, mannequin, patung, atau wajah tidak nyata
      'objek'   — Objek lain yang salah terdeteksi sebagai wajah

    Sinyal yang dianalisis (tanpa model tambahan, murni OpenCV):
      1. Skin color ratio  — rentang warna kulit pada HSV
      2. Texture variance  — Laplacian variance (kulit manusia bertekstur)
      3. Color diversity   — std dev per channel RGB
      4. Edge density      — kepadatan tepi (Canny)
      5. MTCNN confidence  — bobot dari detektor wajah
    """
    img_np = np.array(pil_image.convert("RGB"))
    h_img, w_img = img_np.shape[:2]

    x1, y1, x2, y2 = [int(v) for v in bbox]
    x1, y1 = max(0, x1), max(0, y1)
    x2, y2 = min(w_img, x2), min(h_img, y2)

    face_w, face_h = x2 - x1, y2 - y1
    if face_w <= 0 or face_h <= 0:
        return {
            "type": "objek",
            "label_en": "non-face object",
            "confidence": 0.5,
            "reason": "invalid_crop",
            "scores": {},
        }

    face_crop = img_np[y1:y2, x1:x2]
    total_pixels = face_w * face_h

    # ── 1. Skin Color Analysis (HSV) ─────────────────────────────────────────────
    # Warna kulit manusia: hue 0-25 (warm) dan 160-180 (wrap-around)
    hsv = cv2.cvtColor(face_crop, cv2.COLOR_RGB2HSV)
    mask1 = cv2.inRange(hsv, np.array([0,  20, 70],  dtype=np.uint8),
                              np.array([25, 255, 255], dtype=np.uint8))
    mask2 = cv2.inRange(hsv, np.array([160, 20, 70], dtype=np.uint8),
                              np.array([180, 255, 255], dtype=np.uint8))
    skin_ratio = float(np.sum(cv2.bitwise_or(mask1, mask2) > 0)) / total_pixels

    # ── 2. Texture Variance (Laplacian) ──────────────────────────────────────
    # Kulit manusia bertekstur; boneka/cetak cenderung flat
    gray = cv2.cvtColor(face_crop, cv2.COLOR_RGB2GRAY)
    laplacian_var = float(cv2.Laplacian(gray, cv2.CV_64F).var())

    # ── 3. Color Diversity (per-channel std) ──────────────────────────────
    # Wajah manusia punya gradasi warna yang kaya
    color_std = float(np.mean([face_crop[:, :, c].std() for c in range(3)]))

    # ── 4. Edge Density (Canny) ───────────────────────────────────────────
    # Wajah manusia punya tepi bermakna (mata, hidung, bibir)
    edges = cv2.Canny(gray, 50, 150)
    edge_density = float(np.sum(edges > 0)) / total_pixels

    # ── 5. Saturation Analysis ───────────────────────────────────────────
    # Boneka plastik: saturasi tinggi & seragam; manusia: moderat & bervariasi
    sat_mean = float(hsv[:, :, 1].mean())
    sat_std  = float(hsv[:, :, 1].std())

    # ── Weighted Human Score (0–1) ────────────────────────────────────────
    # Skin ratio: ideal manusia 0.20–0.75
    if 0.20 <= skin_ratio <= 0.75:
        skin_score = 1.0
    elif skin_ratio < 0.20:
        skin_score = skin_ratio / 0.20
    else:
        skin_score = max(0.0, 1.0 - (skin_ratio - 0.75) * 4)

    # Texture: manusia > 100, boneka < 50
    texture_score = min(1.0, laplacian_var / 250.0)

    # Color diversity: manusia 12–60
    if 12 <= color_std <= 60:
        color_score = 1.0
    elif color_std < 12:
        color_score = color_std / 12.0
    else:
        color_score = max(0.0, 1.0 - (color_std - 60) / 60)

    # MTCNN confidence
    conf_score = min(1.0, mtcnn_confidence)

    human_score = (
        skin_score    * 0.35 +
        texture_score * 0.30 +
        color_score   * 0.20 +
        conf_score    * 0.15
    )

    # ── Classification Decision ───────────────────────────────────────────
    LABEL_MAP = {
        "manusia": "human",
        "boneka":  "doll / mannequin",
        "objek":   "non-face object",
    }

    if human_score >= 0.60:
        subject_type = "manusia"
        confidence   = human_score
    elif human_score >= 0.35:
        # Sinyal tambahan untuk membedakan boneka vs manusia remang-remang
        if skin_ratio < 0.10 or laplacian_var < 30:
            subject_type = "boneka"
            confidence   = 0.55 + (0.35 - human_score)
        else:
            subject_type = "boneka"
            confidence   = 0.50
    else:
        if laplacian_var < 20 and skin_ratio < 0.05:
            subject_type = "objek"
            confidence   = 0.70
        else:
            subject_type = "boneka"
            confidence   = 0.60

    return {
        "type":       subject_type,
        "label_en":   LABEL_MAP[subject_type],
        "confidence": round(min(0.99, confidence), 3),
        "scores": {
            "skin_ratio":       round(skin_ratio, 3),
            "texture_variance": round(laplacian_var, 2),
            "color_std":        round(color_std, 2),
            "edge_density":     round(edge_density, 3),
            "sat_mean":         round(sat_mean, 1),
            "sat_std":          round(sat_std, 1),
            "human_score":      round(human_score, 3),
        },
    }


def file_hash_from_bytes(data: bytes) -> str:
    return hashlib.md5(data).hexdigest()


# ─── Indexing ──────────────────────────────────────────────────────────────────

def _do_index_files(base_path: Path) -> dict:
    """Scan semua gambar di base_path, extract embedding, simpan ke DB."""
    image_extensions = {".jpg", ".jpeg", ".png", ".webp", ".bmp"}
    processed = 0
    skipped = 0
    failed = 0
    no_face = 0

    image_files = [
        f for f in base_path.rglob("*")
        if f.suffix.lower() in image_extensions and f.is_file()
    ]
    logger.info(f"Found {len(image_files)} image files.")

    conn = get_db()
    try:
        for img_path in image_files:
            # Relative path yang dipakai sebagai file_path di tabel drive_files
            rel_path = str(img_path.relative_to(base_path))

            with conn.cursor() as cur:
                # Cari drive_file_id berdasarkan file_path
                cur.execute(
                    "SELECT id FROM drive_files WHERE file_path = %s LIMIT 1",
                    (rel_path,)
                )
                row = cur.fetchone()

            if not row:
                # File belum terdaftar di database (belum diupload via Laravel)
                skipped += 1
                continue

            drive_file_id = row["id"]

            try:
                with open(img_path, "rb") as f:
                    img_bytes = f.read()

                # Cek apakah sudah ada embedding (face_index=0 sebagai penanda sudah diproses)
                with conn.cursor() as cur:
                    cur.execute(
                        """SELECT id FROM face_embeddings
                           WHERE drive_file_id = %s AND face_index = 0
                           LIMIT 1""",
                        (drive_file_id,)
                    )
                    existing = cur.fetchone()

                if existing:
                    skipped += 1
                    continue

                pil_img = preprocess_image(img_bytes)
                # Deteksi SEMUA wajah dalam foto (fix utama untuk foto ramai)
                all_faces = get_all_embeddings(pil_img)

                with conn.cursor() as cur:
                    if not all_faces:
                        # Simpan record tanpa embedding (wajah tidak terdeteksi)
                        cur.execute(
                            """INSERT INTO face_embeddings
                               (drive_file_id, embedding, face_index, bbox, confidence, created_at, updated_at)
                               VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
                               ON DUPLICATE KEY UPDATE updated_at=NOW()""",
                            (drive_file_id, json.dumps([]), 0, None, None)
                        )
                        no_face += 1
                    else:
                        # Simpan SETIAP wajah yang terdeteksi sebagai baris terpisah
                        for face in all_faces:
                            cur.execute(
                                """INSERT INTO face_embeddings
                                   (drive_file_id, embedding, face_index, bbox, confidence, created_at, updated_at)
                                   VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
                                   ON DUPLICATE KEY UPDATE
                                       embedding=VALUES(embedding),
                                       bbox=VALUES(bbox),
                                       confidence=VALUES(confidence),
                                       updated_at=NOW()""",
                                (
                                    drive_file_id,
                                    json.dumps(face["embedding"].tolist()),
                                    face["face_index"],
                                    json.dumps(face["bbox"]),
                                    face["confidence"],
                                )
                            )
                        processed += len(all_faces)
                        logger.info(f"{rel_path}: {len(all_faces)} wajah terdeteksi")

                conn.commit()
            except Exception as e:
                logger.error(f"Failed to index {img_path}: {e}")
                conn.rollback()
                failed += 1

    finally:
        conn.close()

    return {
        "total_files": len(image_files),
        "indexed": processed,
        "skipped": skipped,
        "no_face_detected": no_face,
        "failed": failed,
    }


# ─── Endpoints ─────────────────────────────────────────────────────────────────

@app.get("/status")
def get_status():
    """Status service & statistik."""
    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) as total FROM face_embeddings")
            total = cur.fetchone()["total"]
            cur.execute(
                "SELECT COUNT(*) as cnt FROM face_embeddings WHERE JSON_LENGTH(embedding) > 0"
            )
            with_face = cur.fetchone()["cnt"]
        conn.close()

        return {
            "status": "ok",
            "device": DEVICE,
            "total_indexed": total,
            "with_face": with_face,
            "without_face": total - with_face,
            "similarity_threshold": SIMILARITY_THRESHOLD,
            "storage_path": str(STORAGE_PATH.resolve()),
        }
    except Exception as e:
        return {"status": "ok", "device": DEVICE, "db_error": str(e)}


@app.post("/index-files")
async def index_files(path: Optional[str] = Form(None)):
    """Index semua gambar di storage (sinkron)."""
    base = (STORAGE_PATH / path).resolve() if path else STORAGE_PATH.resolve()
    if not base.exists():
        raise HTTPException(status_code=404, detail=f"Path tidak ditemukan: {base}")
    result = _do_index_files(base)
    return {"message": "Indexing selesai.", "result": result}


@app.post("/index-files/async")
async def index_files_async(background_tasks: BackgroundTasks, path: Optional[str] = Form(None)):
    """Index semua gambar di storage (background)."""
    base = (STORAGE_PATH / path).resolve() if path else STORAGE_PATH.resolve()
    if not base.exists():
        raise HTTPException(status_code=404, detail=f"Path tidak ditemukan: {base}")
    background_tasks.add_task(_do_index_files, base)
    return {"message": "Indexing dimulai di background.", "path": str(base)}


@app.post("/search")
async def search_face(
    file: UploadFile = File(...),
    threshold: Optional[float] = Form(None),
    top_k: Optional[int] = Form(20),
):
    """
    Cari foto yang mengandung wajah mirip dengan foto referensi.
    Menggunakan cosine similarity terhadap semua embedding di DB.
    """
    threshold = threshold if threshold is not None else SIMILARITY_THRESHOLD
    top_k = top_k or 20

    # Validasi
    if file.content_type not in ["image/jpeg", "image/png", "image/webp", "image/bmp"]:
        raise HTTPException(status_code=400, detail="Format tidak didukung.")

    # Preprocess & extract embedding referensi
    try:
        image_bytes = await file.read()
        pil_img = preprocess_image(image_bytes)
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Gagal membaca gambar: {e}")

    query_embedding, query_bbox, query_conf = get_embedding(pil_img)

    if query_embedding is None:
        raise HTTPException(
            status_code=422,
            detail="Tidak ada wajah yang terdeteksi pada foto yang diunggah. Gunakan foto selfie yang jelas.",
        )

    # Klasifikasi apakah foto referensi adalah manusia nyata
    face_classification = None
    if query_bbox is not None:
        face_classification = classify_face_type(pil_img, query_bbox, query_conf or 0.0)
        if face_classification["type"] != "manusia":
            logger.warning(
                f"/search: foto referensi diklasifikasikan sebagai '{face_classification['type']}' "
                f"(human_score={face_classification['scores'].get('human_score', '?')})"
            )

    # Ambil semua embedding dari DB
    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute(
                """SELECT fe.id, fe.drive_file_id, fe.embedding, fe.confidence,
                          df.file_path, df.original_name, df.folder_id
                   FROM face_embeddings fe
                   JOIN drive_files df ON df.id = fe.drive_file_id
                   WHERE JSON_LENGTH(fe.embedding) > 0
                """
            )
            rows = cur.fetchall()
        conn.close()
    except Exception as e:
        logger.error(f"DB error: {e}")
        raise HTTPException(status_code=500, detail=f"Database error: {e}")

    if not rows:
        return {
            "matches": [],
            "total_found": 0,
            "total_checked": 0,
            "threshold_used": threshold,
            "message": "Belum ada foto yang terindeks. Jalankan /index-files terlebih dahulu.",
        }

    # Hitung cosine similarity — satu foto bisa punya banyak wajah (face_index berbeda)
    # Ambil similarity TERTINGGI per drive_file_id
    best_per_file: dict[int, dict] = {}
    query_emb = query_embedding.reshape(1, -1)

    for row in rows:
        try:
            emb = np.array(json.loads(row["embedding"])).reshape(1, -1)
            sim = float(cosine_similarity(query_emb, emb)[0][0])

            if sim < threshold:
                continue

            fid = row["drive_file_id"]
            # Simpan hanya yang similarity-nya paling tinggi untuk foto yang sama
            if fid not in best_per_file or sim > best_per_file[fid]["similarity"]:
                best_per_file[fid] = {
                    "drive_file_id": fid,
                    "file_path": row["file_path"],
                    "similarity": round(sim, 4),
                    "similarity_pct": round(sim * 100, 1),
                }
        except Exception:
            continue

    scored = sorted(best_per_file.values(), key=lambda x: x["similarity"], reverse=True)
    matches = scored[:top_k]

    return {
        "matches": matches,
        "total_found": len(matches),
        "total_checked": len(rows),
        "threshold_used": threshold,
        "query_face_type": face_classification,
    }


@app.post("/analyze")
async def analyze_image(file: UploadFile = File(...)):
    """
    Analisis gambar: deteksi semua wajah dan klasifikasi apakah
    setiap wajah adalah manusia nyata, boneka, atau objek lain.

    Response berisi daftar wajah yang terdeteksi beserta:
    - bbox        : koordinat bounding box [x1, y1, x2, y2]
    - confidence  : confidence deteksi MTCNN
    - type        : 'manusia' | 'boneka' | 'objek'
    - label_en    : label bahasa Inggris
    - scores      : detail sinyal analisis (skin_ratio, texture_variance, dst.)
    """
    if file.content_type not in ["image/jpeg", "image/png", "image/webp", "image/bmp"]:
        raise HTTPException(status_code=400, detail="Format tidak didukung.")

    try:
        image_bytes = await file.read()
        pil_img = preprocess_image(image_bytes)
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Gagal membaca gambar: {e}")

    # Deteksi semua wajah
    img_rgb = pil_img.convert("RGB")
    boxes, probs = mtcnn_all.detect(img_rgb)

    if boxes is None or len(boxes) == 0:
        return {
            "total_faces": 0,
            "faces": [],
            "summary": {"manusia": 0, "boneka": 0, "objek": 0},
            "message": "Tidak ada wajah yang terdeteksi dalam gambar.",
        }

    # Klasifikasi setiap wajah
    faces = []
    summary = {"manusia": 0, "boneka": 0, "objek": 0}

    for i, (box, prob) in enumerate(zip(boxes, probs)):
        bbox = [float(x) for x in box]
        cls = classify_face_type(pil_img, bbox, float(prob))
        face_result = {
            "face_index":  i,
            "bbox":        bbox,
            "confidence":  round(float(prob), 4),
            "type":        cls["type"],
            "label_en":    cls["label_en"],
            "type_confidence": cls["confidence"],
            "scores":      cls["scores"],
        }
        faces.append(face_result)
        summary[cls["type"]] = summary.get(cls["type"], 0) + 1

    # Urutkan: manusia dulu, lalu boneka, lalu objek
    type_order = {"manusia": 0, "boneka": 1, "objek": 2}
    faces.sort(key=lambda f: (type_order.get(f["type"], 3), -f["confidence"]))

    return {
        "total_faces": len(faces),
        "faces":   faces,
        "summary": summary,
        "message": (
            f"{summary['manusia']} manusia, "
            f"{summary['boneka']} boneka/mannequin, "
            f"{summary['objek']} objek lain terdeteksi."
        ),
    }


@app.post("/search-debug")
async def search_face_debug(file: UploadFile = File(...)):
    """
    DEBUG: Tampilkan top-10 similarity score untuk SEMUA foto (tanpa threshold).
    Dipakai untuk diagnosa mengapa foto tidak terdeteksi.
    """
    image_bytes = await file.read()
    pil_img = preprocess_image(image_bytes)
    query_embedding, bbox, conf = get_embedding(pil_img)

    if query_embedding is None:
        return {"error": "Tidak ada wajah terdeteksi pada foto referensi yang dikirim."}

    conn = get_db()
    with conn.cursor() as cur:
        cur.execute(
            """SELECT fe.drive_file_id, fe.embedding, fe.face_index, fe.confidence,
                      df.file_path
               FROM face_embeddings fe
               JOIN drive_files df ON df.id = fe.drive_file_id
               WHERE JSON_LENGTH(fe.embedding) > 0"""
        )
        rows = cur.fetchall()
    conn.close()

    query_emb = query_embedding.reshape(1, -1)
    scores = []
    for row in rows:
        try:
            emb = np.array(json.loads(row["embedding"])).reshape(1, -1)
            sim = float(cosine_similarity(query_emb, emb)[0][0])
            scores.append({
                "drive_file_id": row["drive_file_id"],
                "file_path": row["file_path"],
                "face_index": row["face_index"],
                "index_confidence": row["confidence"],
                "similarity": round(sim, 4),
                "similarity_pct": f"{round(sim * 100, 1)}%",
            })
        except Exception:
            continue

    scores.sort(key=lambda x: x["similarity"], reverse=True)

    return {
        "query_face_detected": True,
        "query_face_confidence": round(conf, 4) if conf else None,
        "total_embeddings_checked": len(rows),
        "top_10": scores[:10],
        "current_threshold": SIMILARITY_THRESHOLD,
        "note": "Jika foto ada di top_10 tapi similarity < threshold, turunkan SIMILARITY_THRESHOLD di .env",
    }


@app.post("/index-single")
async def index_single_file(
    file: UploadFile = File(...),
    drive_file_id: int = Form(...),
):

    """Index satu file (dipanggil setelah upload foto baru) — semua wajah di-index."""
    try:
        image_bytes = await file.read()
        pil_img = preprocess_image(image_bytes)
        # Deteksi semua wajah dalam foto
        all_faces = get_all_embeddings(pil_img)

        conn = get_db()
        # Hapus embedding lama untuk file ini (re-index bersih)
        with conn.cursor() as cur:
            cur.execute("DELETE FROM face_embeddings WHERE drive_file_id = %s", (drive_file_id,))

        with conn.cursor() as cur:
            if all_faces:
                for face in all_faces:
                    cur.execute(
                        """INSERT INTO face_embeddings
                           (drive_file_id, embedding, face_index, bbox, confidence, created_at, updated_at)
                           VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE
                               embedding=VALUES(embedding),
                               bbox=VALUES(bbox),
                               confidence=VALUES(confidence),
                               updated_at=NOW()""",
                        (
                            drive_file_id,
                            json.dumps(face["embedding"].tolist()),
                            face["face_index"],
                            json.dumps(face["bbox"]),
                            face["confidence"],
                        )
                    )
            else:
                cur.execute(
                    """INSERT IGNORE INTO face_embeddings
                       (drive_file_id, embedding, face_index, created_at, updated_at)
                       VALUES (%s, %s, %s, NOW(), NOW())""",
                    (drive_file_id, json.dumps([]), 0)
                )
        conn.commit()
        conn.close()

        return {
            "drive_file_id": drive_file_id,
            "has_face": len(all_faces) > 0,
            "face_count": len(all_faces),
            "confidence": round(all_faces[0]["confidence"], 4) if all_faces else None,
            "indexed": True,
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.delete("/index")
def delete_index():
    """Hapus semua embedding dari DB."""
    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute("DELETE FROM face_embeddings")
            cur.execute("DELETE FROM face_clusters")
        conn.commit()
        conn.close()
        return {"message": "Semua embedding dan data pengelompokan dihapus. Jalankan /index-files untuk re-index."}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ─── DBSCAN Clustering ─────────────────────────────────────────────────────────

def _do_cluster(folder_ids: list = None) -> dict:
    """Jalankan DBSCAN pada embedding file publik dan simpan hasilnya ke DB."""
    from sklearn.cluster import DBSCAN
    from sklearn.preprocessing import normalize

    conn = get_db()
    try:
        # 1. Ambil embedding dari file-file publik
        if folder_ids:
            placeholders = ",".join(["%s"] * len(folder_ids))
            query = f"""
                SELECT fe.id AS emb_id, fe.drive_file_id, fe.embedding, df.folder_id
                FROM face_embeddings fe
                JOIN drive_files df ON df.id = fe.drive_file_id
                JOIN folders f ON f.id = df.folder_id
                WHERE JSON_LENGTH(fe.embedding) > 0
                  AND df.folder_id IN ({placeholders})
                  AND (df.is_public = 1 OR f.is_public = 1)
            """
            params = folder_ids
        else:
            query = """
                SELECT fe.id AS emb_id, fe.drive_file_id, fe.embedding, df.folder_id
                FROM face_embeddings fe
                JOIN drive_files df ON df.id = fe.drive_file_id
                JOIN folders f ON f.id = df.folder_id
                WHERE JSON_LENGTH(fe.embedding) > 0
                  AND (df.is_public = 1 OR f.is_public = 1)
            """
            params = []

        with conn.cursor() as cur:
            cur.execute(query, params)
            rows = cur.fetchall()

        if not rows:
            # Jika tidak ada embedding publik, bersihkan kluster lama agar UI tidak menampilkan data basi (ghost)
            with conn.cursor() as cur:
                if not folder_ids:
                    cur.execute("UPDATE face_embeddings SET face_cluster_id = NULL")
                    cur.execute("DELETE FROM face_clusters")
            conn.commit()
            return {"clusters": 0, "noise": 0, "total_faces": 0, "message": "Tidak ada embedding publik."}

        # 2. Siapkan matrix embedding
        emb_ids = [r["emb_id"] for r in rows]
        file_ids = [r["drive_file_id"] for r in rows]
        embeddings = np.array([json.loads(r["embedding"]) for r in rows], dtype=np.float32)
        embeddings_norm = normalize(embeddings, norm="l2")

        # 3. DBSCAN (cosine metric)
        eps = float(os.getenv("DBSCAN_EPS", "0.4"))
        min_samples = int(os.getenv("DBSCAN_MIN_SAMPLES", "2"))
        dbscan = DBSCAN(eps=eps, min_samples=min_samples, metric="cosine", n_jobs=-1)
        labels = dbscan.fit_predict(embeddings_norm)

        cluster_labels = sorted(set(l for l in labels if l != -1))
        noise_count = int(sum(1 for l in labels if l == -1))
        logger.info(f"DBSCAN: {len(cluster_labels)} clusters, {noise_count} noise / {len(rows)} wajah")

        # 4. Reset cluster_id untuk embedding yang diproses
        with conn.cursor() as cur:
            if emb_ids:
                phs = ",".join(["%s"] * len(emb_ids))
                cur.execute(f"UPDATE face_embeddings SET face_cluster_id = NULL WHERE id IN ({phs})", emb_ids)
            if not folder_ids:
                # Hapus semua kluster lama (SET NULL dulu agar FK tidak error)
                cur.execute("UPDATE face_embeddings SET face_cluster_id = NULL")
                cur.execute("DELETE FROM face_clusters")
        conn.commit()

        # 5. Buat face_clusters dan update face_embeddings
        for label in cluster_labels:
            member_indices = [i for i, l in enumerate(labels) if l == label]
            member_emb_ids = [emb_ids[i] for i in member_indices]
            member_file_ids = [file_ids[i] for i in member_indices]

            # Representative = embedding paling dekat ke centroid
            cluster_embs = embeddings_norm[member_indices]
            centroid = cluster_embs.mean(axis=0, keepdims=True)
            sims = cosine_similarity(centroid, cluster_embs)[0]
            rep_idx = member_indices[int(np.argmax(sims))]
            rep_drive_file_id = file_ids[rep_idx]

            with conn.cursor() as cur:
                cur.execute(
                    """INSERT INTO face_clusters
                       (name, representative_drive_file_id, member_count, created_at, updated_at)
                       VALUES (%s, %s, %s, NOW(), NOW())""",
                    (f"Orang {label + 1}", rep_drive_file_id, len(member_emb_ids))
                )
                cluster_id = cur.lastrowid

            phs = ",".join(["%s"] * len(member_emb_ids))
            with conn.cursor() as cur:
                cur.execute(
                    f"UPDATE face_embeddings SET face_cluster_id = %s WHERE id IN ({phs})",
                    [cluster_id] + member_emb_ids
                )
            conn.commit()

        return {"clusters": len(cluster_labels), "noise": noise_count, "total_faces": len(rows), "folder_filter": folder_ids}

    except Exception as e:
        logger.error(f"Clustering error: {e}")
        conn.rollback()
        raise
    finally:
        conn.close()


@app.post("/cluster")
async def run_clustering(
    background_tasks: BackgroundTasks,
    folder_ids: Optional[str] = Form(None),
    async_mode: Optional[bool] = Form(False),
):
    """Jalankan DBSCAN clustering pada embedding file publik."""
    parsed_ids = None
    if folder_ids:
        try:
            parsed_ids = [int(x.strip()) for x in folder_ids.split(",") if x.strip()]
        except ValueError:
            raise HTTPException(status_code=400, detail="folder_ids harus berupa angka dipisah koma.")

    if async_mode:
        background_tasks.add_task(_do_cluster, parsed_ids)
        return {"message": "Clustering dimulai di background.", "folder_ids": parsed_ids}

    result = _do_cluster(parsed_ids)
    return {"message": "Clustering selesai.", "result": result}


@app.get("/clusters")
def list_clusters():
    """Daftar semua face cluster."""
    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute("""
                SELECT fc.id, fc.name, fc.member_count, fc.representative_drive_file_id,
                       df.file_path AS rep_file_path
                FROM face_clusters fc
                LEFT JOIN drive_files df ON df.id = fc.representative_drive_file_id
                ORDER BY fc.member_count DESC
            """)
            clusters = cur.fetchall()
        conn.close()
        return {"clusters": clusters, "total": len(clusters)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/clusters/{cluster_id}/photos")
def cluster_photos(cluster_id: int):
    """Ambil semua foto dalam satu cluster."""
    try:
        conn = get_db()
        with conn.cursor() as cur:
            cur.execute("""
                SELECT fe.drive_file_id, fe.confidence,
                       df.file_path, df.original_name, df.folder_id
                FROM face_embeddings fe
                JOIN drive_files df ON df.id = fe.drive_file_id
                WHERE fe.face_cluster_id = %s
                ORDER BY fe.confidence DESC
            """, (cluster_id,))
            photos = cur.fetchall()
        conn.close()
        return {"cluster_id": cluster_id, "photos": photos, "total": len(photos)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
