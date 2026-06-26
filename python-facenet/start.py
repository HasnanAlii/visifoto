#!/usr/bin/env python3
"""
VisiFoto FaceNet Service Launcher
Gunakan: python start.py [--install] [--host 0.0.0.0] [--port 8001]
"""

import subprocess
import sys
import os
from pathlib import Path


def load_dotenv(env_file: Path):
    """Load .env file ke os.environ."""
    if not env_file.exists():
        return
    with open(env_file) as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, value = line.partition("=")
            os.environ.setdefault(key.strip(), value.strip())


def get_python():
    """Gunakan Python dari venv jika tersedia, fallback ke python3."""
    import shutil
    venv_python = Path(__file__).parent / "venv" / "bin" / "python3"
    if venv_python.exists():
        return str(venv_python)
    return shutil.which("python3") or sys.executable


def install_requirements():
    req_file = Path(__file__).parent / "requirements.txt"
    print(f"[*] Installing requirements from {req_file}...")
    subprocess.check_call([get_python(), "-m", "pip", "install", "-r", str(req_file)])
    print("[✓] Requirements installed.")


def start_server(host="0.0.0.0", port=8001, reload=False):
    print(f"[*] Starting FaceNet service on http://{host}:{port}")
    print(f"    DB  : {os.getenv('DB_USERNAME')}@{os.getenv('DB_HOST', '127.0.0.1')}:{os.getenv('DB_PORT', '3306')}/{os.getenv('DB_DATABASE')}")
    print(f"    Path: {os.getenv('LARAVEL_STORAGE_PATH')}")
    cmd = [
        get_python(), "-m", "uvicorn",
        "main:app",
        "--host", host,
        "--port", str(port),
    ]
    if reload:
        cmd.append("--reload")
    subprocess.run(cmd)


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="VisiFoto FaceNet Service Launcher")
    parser.add_argument("--install", action="store_true", help="Install Python dependencies first")
    parser.add_argument("--host", default="0.0.0.0", help="Host (default: 0.0.0.0)")
    parser.add_argument("--port", type=int, default=8001, help="Port (default: 8001)")
    parser.add_argument("--reload", action="store_true", help="Auto-reload (dev mode)")
    args = parser.parse_args()

    script_dir = Path(__file__).parent
    os.chdir(script_dir)

    # Load .env
    load_dotenv(script_dir / ".env")

    if args.install:
        install_requirements()

    start_server(host=args.host, port=args.port, reload=args.reload)
