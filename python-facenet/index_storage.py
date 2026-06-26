#!/usr/bin/env python3
"""
Index semua file gambar dari storage Laravel.
Jalankan setelah service berjalan.

Contoh:
  python index_storage.py                          # Index semua
  python index_storage.py --path drive-files       # Index sub-folder tertentu
  python index_storage.py --host 127.0.0.1 --port 8001
"""

import argparse
import sys
import time
import requests


def main():
    parser = argparse.ArgumentParser(description="Trigger FaceNet indexing")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=8001)
    parser.add_argument("--path", default=None, help="Sub-path di dalam LARAVEL_STORAGE_PATH")
    parser.add_argument("--async-mode", action="store_true", help="Jalankan indexing di background")
    args = parser.parse_args()

    base_url = f"http://{args.host}:{args.port}"

    # Cek service
    print(f"[*] Cek service di {base_url}...")
    try:
        r = requests.get(f"{base_url}/status", timeout=5)
        status = r.json()
        print(f"[✓] Service aktif | Device: {status['device']} | Terindeks: {status['total_indexed']}")
    except Exception as e:
        print(f"[✗] Service tidak bisa diakses: {e}")
        sys.exit(1)

    # Trigger indexing
    endpoint = "/index-files/async" if args.async_mode else "/index-files"
    print(f"[*] Menjalankan indexing via {endpoint}...")
    t0 = time.time()

    data = {}
    if args.path:
        data["path"] = args.path

    try:
        r = requests.post(f"{base_url}{endpoint}", data=data, timeout=300)
        elapsed = time.time() - t0

        if r.ok:
            resp = r.json()
            if args.async_mode:
                print(f"[✓] {resp.get('message', 'Indexing dimulai di background')}")
            else:
                result = resp.get("result", {})
                print(f"\n[✓] Indexing selesai dalam {elapsed:.1f}s")
                print(f"    Total file      : {result.get('total_files', 0)}")
                print(f"    Berhasil diindex: {result.get('indexed', 0)}")
                print(f"    Tidak ada wajah : {result.get('no_face_detected', 0)}")
                print(f"    Skip (unchanged): {result.get('skipped_unchanged', 0)}")
                print(f"    Gagal           : {result.get('failed', 0)}")
        else:
            print(f"[✗] Error: {r.text}")
            sys.exit(1)

    except requests.Timeout:
        print(f"[!] Request timeout setelah {time.time()-t0:.0f}s (normal untuk folder besar)")
        print("    Gunakan --async-mode untuk indexing background")
    except Exception as e:
        print(f"[✗] Error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()
