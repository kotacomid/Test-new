import os
import shutil
import asyncio
import pandas as pd
from fastapi import FastAPI, UploadFile, File, Form, BackgroundTasks, Request
from fastapi.responses import FileResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from zlibrary import ZLibrary
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request as GRequest
from google.oauth2.credentials import Credentials

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

CSV_FILE = "zlib_metadata.csv"
COVERS_DIR = "covers"
FILES_DIR = "files"
SCOPES = ['https://www.googleapis.com/auth/drive.file']

# In-memory config
ZLIB_ACCOUNTS = []
SEARCH_RESULTS = []  # List of dicts, reset per session

# --- GOOGLE DRIVE AUTH ---
def gdrive_auth():
    creds = None
    if os.path.exists('token.json'):
        creds = Credentials.from_authorized_user_file('token.json', SCOPES)
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(GRequest())
        else:
            flow = InstalledAppFlow.from_client_secrets_file('credentials.json', SCOPES)
            creds = flow.run_local_server(port=0)
        with open('token.json', 'w') as token:
            token.write(creds.to_json())
    return build('drive', 'v3', credentials=creds)

def upload_to_drive(service, file_path, mime_type, folder_id=None):
    file_metadata = {'name': os.path.basename(file_path)}
    if folder_id:
        file_metadata['parents'] = [folder_id]
    media = MediaFileUpload(file_path, mimetype=mime_type)
    file = service.files().create(body=file_metadata, media_body=media, fields='id').execute()
    return file.get('id')

async def zlib_search(account, query):
    zlib = ZLibrary()
    await zlib.login(account['email'], account['password'])
    results = await zlib.search(query)
    return results

async def zlib_download(book_id, account):
    zlib = ZLibrary()
    await zlib.login(account['email'], account['password'])
    meta = await zlib.get_metadata(book_id)
    # Download cover
    os.makedirs(COVERS_DIR, exist_ok=True)
    cover_path = os.path.join(COVERS_DIR, f"{book_id}.jpg")
    if meta.get('cover_url'):
        await zlib.download_cover(meta['cover_url'], cover_path)
    # Download file
    os.makedirs(FILES_DIR, exist_ok=True)
    file_path = os.path.join(FILES_DIR, f"{book_id}.pdf")
    await zlib.download_file(book_id, file_path)
    return meta, cover_path, file_path

@app.post("/upload-credentials")
def upload_credentials(file: UploadFile = File(...)):
    with open("credentials.json", "wb") as f:
        shutil.copyfileobj(file.file, f)
    return {"status": "credentials uploaded"}

@app.post("/add-account")
def add_account(email: str = Form(...), password: str = Form(...)):
    ZLIB_ACCOUNTS.append({"email": email, "password": password})
    return {"status": "account added", "total_accounts": len(ZLIB_ACCOUNTS)}

@app.post("/search")
async def search(query: str = Form(...), account_index: int = Form(0)):
    if not ZLIB_ACCOUNTS:
        return JSONResponse(status_code=400, content={"error": "Add at least one Z-Library account"})
    results = await zlib_search(ZLIB_ACCOUNTS[account_index], query)
    global SEARCH_RESULTS
    SEARCH_RESULTS = results
    return {"results": results}

@app.get("/list-results")
def list_results():
    return {"results": SEARCH_RESULTS}

@app.post("/download")
async def download(book_id: str = Form(...), account_index: int = Form(0)):
    meta, cover_path, file_path = await zlib_download(book_id, ZLIB_ACCOUNTS[account_index])
    # Simpan metadata ke CSV
    meta_row = {
        'account': ZLIB_ACCOUNTS[account_index]['email'],
        'book_id': book_id,
        'title': meta.get('title'),
        'author': meta.get('author'),
        'cover_path': cover_path if os.path.exists(cover_path) else '',
        'file_path': file_path,
    }
    if os.path.exists(CSV_FILE):
        df = pd.read_csv(CSV_FILE)
        df = pd.concat([df, pd.DataFrame([meta_row])], ignore_index=True)
    else:
        df = pd.DataFrame([meta_row])
    df.to_csv(CSV_FILE, index=False)
    return {"meta": meta_row}

@app.get("/get-cover/{book_id}")
def get_cover(book_id: str):
    cover_path = os.path.join(COVERS_DIR, f"{book_id}.jpg")
    if not os.path.exists(cover_path):
        return JSONResponse(status_code=404, content={"error": "Cover not found"})
    return FileResponse(cover_path, media_type='image/jpeg', filename=f"{book_id}.jpg")

@app.get("/get-file/{book_id}")
def get_file(book_id: str):
    file_path = os.path.join(FILES_DIR, f"{book_id}.pdf")
    if not os.path.exists(file_path):
        return JSONResponse(status_code=404, content={"error": "File not found"})
    return FileResponse(file_path, media_type='application/pdf', filename=f"{book_id}.pdf")

@app.post("/upload-drive")
def upload_drive(book_id: str = Form(...)):
    gdrive_service = gdrive_auth()
    cover_path = os.path.join(COVERS_DIR, f"{book_id}.jpg")
    file_path = os.path.join(FILES_DIR, f"{book_id}.pdf")
    cover_drive_id = upload_to_drive(gdrive_service, cover_path, 'image/jpeg') if os.path.exists(cover_path) else None
    file_drive_id = upload_to_drive(gdrive_service, file_path, 'application/pdf') if os.path.exists(file_path) else None
    # Update CSV
    if os.path.exists(CSV_FILE):
        df = pd.read_csv(CSV_FILE)
        idx = df[df['book_id'] == book_id].index
        if len(idx) > 0:
            df.loc[idx, 'cover_drive_id'] = cover_drive_id
            df.loc[idx, 'file_drive_id'] = file_drive_id
            df.to_csv(CSV_FILE, index=False)
    return {"cover_drive_id": cover_drive_id, "file_drive_id": file_drive_id}

@app.get("/download-csv")
def download_csv():
    if not os.path.exists(CSV_FILE):
        return JSONResponse(status_code=404, content={"error": "No CSV found"})
    return FileResponse(CSV_FILE, media_type='text/csv', filename=CSV_FILE)

@app.post("/reset-session")
def reset_session():
    global SEARCH_RESULTS
    SEARCH_RESULTS = []
    return {"status": "session reset"}

@app.get("/")
def root():
    return {"message": "Z-Library to Google Drive Web Bot. Use /docs for API UI."}

# WSGI/ASGI compatibility
def app_factory():
    return app