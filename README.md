# Z-Library to Google Drive Bot

## Features
- Multi-account Z-Library login
- Search and download book metadata, cover, and file
- Upload cover and file to Google Drive
- Store all metadata in a single CSV file

## Setup (Local)

### 1. Clone and Install Requirements
```
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 2. Google Drive API Credentials
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Create a project and enable the Google Drive API
- Create OAuth 2.0 credentials (Desktop app)
- Download `credentials.json` and place it in the project root

### 3. Jalankan Web App
```
uvicorn web_zlib_gdrive:app --host 0.0.0.0 --port 8000
```
- Buka browser ke `http://localhost:8000/docs` untuk UI interaktif.

## Deployment ke Render.com
1. Push semua file ke GitHub.
2. Buat web service baru di [Render](https://render.com/):
   - Environment: Python 3
   - Start command: `uvicorn web_zlib_gdrive:app --host 0.0.0.0 --port 10000`
   - Tambahkan file `requirements.txt` dan pastikan semua dependensi sudah ada.
3. Upload `credentials.json` via endpoint `/upload-credentials` di web app.

## Deployment ke Netlify (via Netlify Functions)
- Netlify tidak support long-running server seperti FastAPI secara langsung, tapi bisa diadaptasi ke serverless function (lihat Netlify docs untuk Python functions).
- Untuk deployment Python web app, lebih direkomendasikan Render, Railway, atau platform serupa.

## Endpoints
- `/upload-credentials` (POST): Upload Google credentials.json
- `/add-account` (POST): Tambah akun Z-Library
- `/run` (POST): Mulai proses download+upload
- `/download-csv` (GET): Download metadata CSV

## Notes
- Proses Google Auth tetap butuh interaksi manual pertama kali (browser popup).
- Semua metadata, termasuk Google Drive file ID, disimpan di `zlib_metadata.csv`.
- Covers dan files disimpan di folder `covers/` dan `files/`.