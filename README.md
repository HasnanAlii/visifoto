# VisiFoto — Smart AI-Powered Photo Management System

VisiFoto is a modern, high-performance digital asset and file management system. It combines a robust **Laravel 12** web application with a specialized **Python FastAPI** AI microservice to provide seamless cloud drive management, public sharing galleries, facial search capabilities, and automatic face clustering using DBSCAN.

---

## 🚀 Key Features

*   **Unified Drive System**
    *   Create, rename, and delete folders recursively.
    *   Upload images and documents with ease.
    *   Toggle individual files or entire folders between **Public** and **Private** status.
    *   Download single files or download folders directly as a compressed `.zip` archive.
*   **Public Directory Gallery**
    *   Dedicated landing view for guest or user discovery of publicly shared folders and items.
*   **AI-Powered Face Search ("Cari Foto Saya")**
    *   Upload a reference face photo to find all photos containing similar faces.
    *   Configurable similarity thresholds and result limits (Top-K).
    *   Targeted search within specific folders (and their subfolders) to accelerate lookup times.
    *   Real-time progress feedback UI with a cancel button that aborts the HTTP request gracefully (using JS `AbortController`).
*   **Automatic Face Clustering (DBSCAN)**
    *   Automatically runs face clustering when folders are set to public or when new photos are uploaded to public folders.
    *   Group photos automatically without needing to define the number of clusters beforehand.
    *   Interactive cluster management dashboard to view grouped images and rename clusters (using double-click in-place editing).

---

## 📐 System Architecture

The interaction flow between the Laravel frontend/backend, the MySQL database, and the Python FaceNet microservice:

```mermaid
graph TD
    User["👤 User (Web Browser)"]
    Laravel["🌐 Laravel 12 Backend"]
    MySQL["🗄️ MySQL Database"]
    Storage["📂 Laravel Storage Disk"]
    Python["🐍 Python FastAPI Service"]
    Models["🤖 AI Models (MTCNN + FaceNet)"]

    User <-->|HTTP / Blade + AlpineJS| Laravel
    Laravel <-->|Read/Write Metadata| MySQL
    Laravel -->|Saves Images| Storage
    Laravel <-->|HTTP API Calls| Python
    Python <-->|Direct Connection (PyMySQL)| MySQL
    Python <-->|Reads Uploads / Embeddings| Storage
    Python <-->|Process Image Data| Models
```

---

## 🛠️ System Requirements

### 💻 Web Application (Laravel)
*   **PHP**: `^8.2`
*   **Database**: MySQL / MariaDB (supporting JSON columns for embeddings)
*   **Package Manager**: Composer
*   **Frontend Tools**: Node.js & npm (for Alpine.js v3, Tailwind CSS v3, and Vite)

### 🐍 AI Microservice (Python)
*   **Python**: `>= 3.12` (Tested and validated on Python `3.14` via Linuxbrew)
*   **Virtual Environment**: `virtualenv` or built-in `venv`
*   **Core Libraries**:
    *   `FastAPI` & `Uvicorn` (High-performance API server)
    *   `MTCNN` (Multi-task Cascaded CNN for face detection)
    *   `FaceNet` (InceptionResnetV1 pre-trained on VGGFace2 for embedding generation)
    *   `scikit-learn` (DBSCAN clustering & Cosine distance metrics)
    *   `PyMySQL` (Direct database interaction for async processing)
    *   `OpenCV` & `Pillow` (Image processing)
    *   `PyTorch` & `Torchvision` (Deep learning framework)

---

## 📦 Installation & Setup

Follow these steps to set up both the Laravel application and the Python AI service on your local environment:

### Step 1: Configure the Laravel Application

1.  Clone the repository and navigate to the project root directory.
2.  Copy the example environment file:
    ```bash
    cp .env.example .env
    ```
3.  Configure your database credentials and path settings inside the newly created `.env` file:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=app_pemotretan
    DB_USERNAME=root
    DB_PASSWORD=your_password
    ```
4.  Run the composer setup script to install dependencies, generate application keys, run migrations, and build frontend assets:
    ```bash
    composer run setup
    ```
5.  Link the storage folder:
    ```bash
    php artisan storage:link
    ```

### Step 2: Configure the Python FaceNet Microservice

1.  Navigate into the `python-facenet` folder:
    ```bash
    cd python-facenet
    ```
2.  Copy the service environment file:
    ```bash
    cp .env.example .env
    ```
3.  Adjust the environment values in `python-facenet/.env`. Make sure the MySQL configuration matches your Laravel `.env` settings:
    ```env
    LARAVEL_STORAGE_PATH=../storage/app/public
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=app_pemotretan
    DB_USERNAME=root
    DB_PASSWORD=your_password
    ```
4.  Run the script command to automatically create a virtual environment (`venv`) and install all required Python packages (this may take 5–15 minutes as it downloads PyTorch and the pre-trained weights):
    ```bash
    python start.py --install
    ```

---

## 🚦 Running the Services

### Running Laravel
To run the Laravel web server, queue listener, log tailing, and Vite hot reload concurrently, run:
```bash
composer run dev
```
The application will be accessible at: `http://localhost:8000`.

### Running Python Microservice
To start the FastAPI server:
```bash
cd python-facenet

# Normal Mode
python start.py

# Development Mode (Auto-reloads on file changes)
python start.py --reload

# Custom Port Mode (Default port is 8001)
python start.py --port 8002
```
The microservice will run at: `http://127.0.0.1:8001`. Interactive API documentation is available at `http://127.0.0.1:8001/docs`.

---

## 🗂️ Face Indexing Guide

Before face searching and clustering can work, photos in storage must be indexed (meaning faces are detected, and their 512-dimensional embeddings are saved).

### Method 1: Using Python Script (Recommended for bulk seeding)
Run the script to index all existing photos in your storage:
```bash
cd python-facenet

# Index all photos
python index_storage.py

# Index a specific folder/subpath
python index_storage.py --path drive-files

# Index asynchronously in the background
python index_storage.py --async-mode
```

### Method 2: Via Laravel Admin Interface
Click **"Index Storage"** from the "Cari Foto Saya" dashboard, or use the background indexing feature.

### Method 3: Direct API Request
```bash
# Synchronous Indexing
curl -X POST http://127.0.0.1:8001/index-files

# Asynchronous Indexing
curl -X POST http://127.0.0.1:8001/index-files/async
```

---

## ⚙️ Configuration Variables

These variables can be customized in your environment configurations:

### Laravel `.env`
*   `FACENET_SERVICE_URL`: The URL of the running Python service (default: `http://127.0.0.1:8001`).
*   `FACENET_TIMEOUT`: The maximum request timeout to the service in seconds (default: `60`).

### Python `.env`
*   `SIMILARITY_THRESHOLD`: The cosine similarity threshold value range `0.0 - 1.0`. Defaults to `0.40` (lower values are more lenient; higher values like `0.70` are more strict).
*   `DBSCAN_EPS`: DBSCAN clustering neighborhood search radius (default: `0.4`). Smaller radius enforces tighter cluster similarity requirements.
*   `DBSCAN_MIN_SAMPLES`: Minimum number of photos to form a cluster (default: `2`).

---

## 📊 API Endpoints Reference

### Laravel Face-Search Endpoints
| HTTP Method | Route | Description |
|---|---|---|
| `GET` | `/face-search` | Face search dashboard index. |
| `POST` | `/face-search/search` | Search photos by uploading target face image. |
| `GET` | `/face-search/status` | Get status check of the Python microservice. |
| `POST` | `/face-search/index` | Trigger a full sync/async face index of all files. |
| `DELETE` | `/face-search/index` | Clear all database face embeddings (full re-index). |

### Python FaceNet Endpoints
| HTTP Method | Route | Description |
|---|---|---|
| `GET` | `/status` | Service health status and database statistics. |
| `POST` | `/index-files` | Index all images synchronously. |
| `POST` | `/index-files/async` | Index all images asynchronously. |
| `POST` | `/index-single` | Index a single newly uploaded file by ID. |
| `POST` | `/search` | Parse image, compute embedding, and run cosine similarity. |
| `POST` | `/cluster` | Trigger DBSCAN clustering on public folders. |
| `DELETE` | `/index` | Clear all embedding datasets. |

---
