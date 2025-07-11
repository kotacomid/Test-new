import os
import shutil
import asyncio
import pandas as pd
from fastapi import FastAPI, UploadFile, File, Form, BackgroundTasks
from fastapi.responses import FileResponse, JSONResponse
from zlibrary import ZLibrary
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from google.oauth2.credentials import Credentials

app = FastAPI()

CSV_FILE = "zlib_metadata.csv"
COVERS_DIR = "covers"
FILES_DIR = "files"
SCOPES = ['https://www.googleapis.com/auth/drive.file']

# In-memory config
ZLIB_ACCOUNTS = []

# --- GOOGLE DRIVE AUTH ---
def gdrive_auth():
    creds = None
    if os.path.exists('token.json'):
        creds = Credentials.from_authorized_user_file('token.json', SCOPES)
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
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

async def zlib_download_and_upload(account, query, gdrive_service):
    zlib = ZLibrary()
    await zlib.login(account['email'], account['password'])
    results = await zlib.search(query)
    if not results:
        return None
    book = results[0]
    meta = await zlib.get_metadata(book['id'])
    # Download cover
    os.makedirs(COVERS_DIR, exist_ok=True)
    cover_path = os.path.join(COVERS_DIR, f"{book['id']}.jpg")
    if meta.get('cover_url'):
        await zlib.download_cover(meta['cover_url'], cover_path)
    # Download file
    os.makedirs(FILES_DIR, exist_ok=True)
    file_path = os.path.join(FILES_DIR, f"{book['id']}.pdf")
    await zlib.download_file(book['id'], file_path)
    # Upload to Google Drive
    cover_drive_id = upload_to_drive(gdrive_service, cover_path, 'image/jpeg') if os.path.exists(cover_path) else None
    file_drive_id = upload_to_drive(gdrive_service, file_path, 'application/pdf')
    # Store metadata
    meta_row = {
        'account': account['email'],
        'book_id': book['id'],
        'title': meta.get('title'),
        'author': meta.get('author'),
        'cover_drive_id': cover_drive_id,
        'file_drive_id': file_drive_id,
        'cover_path': cover_path if os.path.exists(cover_path) else '',
        'file_path': file_path,
    }
    if os.path.exists(CSV_FILE):
        df = pd.read_csv(CSV_FILE)
        df = pd.concat([df, pd.DataFrame([meta_row])], ignore_index=True)
    else:
        df = pd.DataFrame([meta_row])
    df.to_csv(CSV_FILE, index=False)
    return meta_row

@app.post("/upload-credentials")
def upload_credentials(file: UploadFile = File(...)):
    with open("credentials.json", "wb") as f:
        shutil.copyfileobj(file.file, f)
    return {"status": "credentials uploaded"}

@app.post("/add-account")
def add_account(email: str = Form(...), password: str = Form(...)):
    ZLIB_ACCOUNTS.append({"email": email, "password": password})
    return {"status": "account added", "total_accounts": len(ZLIB_ACCOUNTS)}

@app.post("/run")
def run_process(query: str = Form(...), background_tasks: BackgroundTasks = None):
    if not os.path.exists("credentials.json"):
        return JSONResponse(status_code=400, content={"error": "Upload credentials.json first"})
    if not ZLIB_ACCOUNTS:
        return JSONResponse(status_code=400, content={"error": "Add at least one Z-Library account"})
    background_tasks.add_task(run_all, query)
    return {"status": "processing started"}

def run_all(query):
    gdrive_service = gdrive_auth()
    loop = asyncio.new_event_loop()
    asyncio.set_event_loop(loop)
    tasks = [zlib_download_and_upload(acc, query, gdrive_service) for acc in ZLIB_ACCOUNTS]
    loop.run_until_complete(asyncio.gather(*tasks))

@app.get("/download-csv")
def download_csv():
    if not os.path.exists(CSV_FILE):
        return JSONResponse(status_code=404, content={"error": "No CSV found"})
    return FileResponse(CSV_FILE, media_type='text/csv', filename=CSV_FILE)

@app.get("/")
def root():
    return {"message": "Z-Library to Google Drive Web Bot. Use /docs for API UI."}