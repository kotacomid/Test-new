import os
import asyncio
import pandas as pd
from zlibrary import ZLibrary
from googleapiclient.discovery import build
from googleapiclient.http import MediaFileUpload
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from google.oauth2.credentials import Credentials

# --- CONFIG ---
ZLIB_ACCOUNTS = [
    {"email": "your_email1@example.com", "password": "your_password1"},
    {"email": "your_email2@example.com", "password": "your_password2"},
    # Add more accounts as needed
]
CSV_FILE = "zlib_metadata.csv"
COVERS_DIR = "covers"
FILES_DIR = "files"
SCOPES = ['https://www.googleapis.com/auth/drive.file']

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

# --- ZLIBRARY BOT ---
async def zlib_download_and_upload(account, query, gdrive_service):
    zlib = ZLibrary()
    await zlib.login(account['email'], account['password'])
    results = await zlib.search(query)
    if not results:
        print(f"No results for {query}")
        return
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
    print(f"Done: {meta_row['title']} ({meta_row['book_id']})")

async def main():
    gdrive_service = gdrive_auth()
    query = input("Enter book search query: ")
    tasks = [zlib_download_and_upload(acc, query, gdrive_service) for acc in ZLIB_ACCOUNTS]
    await asyncio.gather(*tasks)

if __name__ == "__main__":
    asyncio.run(main())