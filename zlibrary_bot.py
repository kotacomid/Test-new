import asyncio
import csv
import json
import os
from datetime import datetime
from typing import Dict, List, Optional, Any

import aiohttp
# pandas removed
from pydrive2.auth import GoogleAuth
from pydrive2.drive import GoogleDrive
import zlibrary
from zlibrary import AsyncZlib

# ----------------------------------------------------------------------------
# CONFIGURATION SECTION
# ----------------------------------------------------------------------------

# Path to a JSON file holding a list of account objects:
# [
#     {"email": "user@example.com", "password": "secret"},
#     ...
# ]
ACCOUNTS_FILE: str = os.getenv("ZLIBRARY_ACCOUNTS", "accounts.json")

# CSV file where metadata is appended.
METADATA_CSV: str = os.getenv("ZLIBRARY_METADATA", "metadata.csv")

# Local download folders
DOWNLOAD_DIR: str = os.getenv("ZLIBRARY_DOWNLOAD_DIR", "downloads")
COVER_DIR: str = os.getenv("ZLIBRARY_COVER_DIR", "covers")

# Google Drive folder (ID) where the files will be uploaded.
# Create this folder manually and copy its ID (it looks like a long string)
DRIVE_FOLDER_ID: Optional[str] = os.getenv("DRIVE_FOLDER_ID")

# Google API credentials:
# 1. For a Service Account: set GOOGLE_APPLICATION_CREDENTIALS env var
# 2. For OAuth user credential flow: credentials will be stored in drive_creds.json
GDRIVE_CREDENTIALS_FILE = os.getenv("GOOGLE_APPLICATION_CREDENTIALS", "service_account.json")

# ----------------------------------------------------------------------------


async def download_http(url: str, dest_path: str) -> None:
    """Download a file via HTTP asynchronously and save to *dest_path*."""
    os.makedirs(os.path.dirname(dest_path), exist_ok=True)
    async with aiohttp.ClientSession() as session:
        async with session.get(url) as resp:
            resp.raise_for_status()
            with open(dest_path, "wb") as f:
                async for chunk in resp.content.iter_chunked(1024 * 64):
                    f.write(chunk)


def drive_auth() -> GoogleDrive:
    """Authenticate and return a GoogleDrive instance (via PyDrive2)."""
    gauth = GoogleAuth()

    # Try service-account first (recommended for non-interactive scripts)
    if os.path.isfile(GDRIVE_CREDENTIALS_FILE):
        gauth.settings["client_config_backend"] = "service"
        gauth.settings["service_config"] = {
            "client_json_file_path": GDRIVE_CREDENTIALS_FILE
        }
        gauth.ServiceAuth()
    else:
        # Fallback to local browser-based OAuth (will open auth URL on first run)
        gauth.LoadCredentialsFile("drive_creds.json")
        if gauth.credentials is None:
            gauth.LocalWebserverAuth()  # will require manual browser login
        elif gauth.access_token_expired:
            gauth.Refresh()
        else:
            gauth.Authorize()
        gauth.SaveCredentialsFile("drive_creds.json")

    return GoogleDrive(gauth)


def upload_to_drive(drive: GoogleDrive, local_path: str, parent_id: Optional[str]) -> str:
    """Upload *local_path* to Drive and return the file ID."""
    file_meta = {"title": os.path.basename(local_path)}
    if parent_id:
        file_meta["parents"] = [{"id": parent_id}]

    gfile = drive.CreateFile(file_meta)
    gfile.SetContentFile(local_path)
    gfile.Upload()
    return gfile["id"]


async def download_book_file(client: AsyncZlib, book_details: Dict, dest_dir: str) -> str:
    """Use zlibrary to download the book file itself and return local path."""
    os.makedirs(dest_dir, exist_ok=True)
    # If the primary download_url requires conversion, we skip for now.
    download_url = book_details.get("download_url")
    if not download_url or download_url == "CONVERSION_NEEDED":
        raise RuntimeError("No direct download URL available for this book.")

    # zlibrary provides an async download helper on the client
    gen = client.download_with_progress(book_details, download_dir=dest_dir)
    final_path: Optional[str] = None
    async for current, total, status in gen:
        if status.startswith("completed"):
            final_path = status.split(":", 1)[1]
    if not final_path:
        raise RuntimeError("Download did not complete properly.")
    return final_path


def append_metadata(row: Dict) -> None:
    """Append a single row (dict) to the CSV specified by METADATA_CSV."""
    file_exists = os.path.isfile(METADATA_CSV)
    with open(METADATA_CSV, "a", newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=list(row.keys()))
        if not file_exists:
            writer.writeheader()
        writer.writerow(row)


async def process_book(
    client: AsyncZlib,
    book_stub: Any,
    account_email: str,
    drive: GoogleDrive,
) -> None:
    """Fetch full metadata, download cover + book, upload to Drive, log CSV."""
    details = await book_stub.fetch()

    # ------------------- Download cover -------------------
    cover_path = ""
    cover_drive_id = ""
    if cover_url := details.get("cover"):
        ext = cover_url.split(".")[-1].split("?")[0]
        cover_path = os.path.join(COVER_DIR, f"{details['id']}_cover.{ext}")
        await download_http(cover_url, cover_path)
        if DRIVE_FOLDER_ID:
            cover_drive_id = upload_to_drive(drive, cover_path, DRIVE_FOLDER_ID)

    # ------------------- Download book file -------------------
    try:
        book_path = await download_book_file(client, details, DOWNLOAD_DIR)
    except Exception as ex:
        print(f"[WARN] Could not download book {details.get('name')}: {ex}")
        return
    book_drive_id = ""
    if DRIVE_FOLDER_ID:
        book_drive_id = upload_to_drive(drive, book_path, DRIVE_FOLDER_ID)

    # ------------------- Save metadata -------------------
    metadata_row = {
        "timestamp": datetime.utcnow().isoformat(),
        "account": account_email,
        "id": details.get("id"),
        "title": details.get("name"),
        "authors": "; ".join(a.get("author") for a in details.get("authors", [])),
        "year": details.get("year"),
        "language": details.get("language"),
        "extension": details.get("extension"),
        "size": details.get("size"),
        "cover_path": cover_path,
        "book_path": book_path,
        "cover_drive_id": cover_drive_id,
        "book_drive_id": book_drive_id,
    }
    append_metadata(metadata_row)
    print(f"[INFO] Processed book '{details.get('name')}' (ID {details.get('id')})")


async def process_account(account: Dict, drive: GoogleDrive) -> None:
    """Login with one account and process some books (example: first 20 of query)."""
    email = account["email"]
    password = account["password"]
    query = account.get("query", "computer science")

    client = zlibrary.AsyncZlib()
    await client.login(email, password)
    print(f"[INFO] Logged in as {email}")

    paginator = await client.search(q=query, count=20)
    books = await paginator.next()  # fetch first result set
    # Process each book sequentially (could also gather if you like)
    for book_stub in books:
        try:
            await process_book(client, book_stub, email, drive)
        except Exception as ex:
            print(f"[ERROR] Failed processing a book: {ex}")

    await client.close()


async def main() -> None:
    # Ensure folders exist
    os.makedirs(DOWNLOAD_DIR, exist_ok=True)
    os.makedirs(COVER_DIR, exist_ok=True)

    # Load accounts
    try:
        accounts: List[Dict] = json.load(open(ACCOUNTS_FILE, "r", encoding="utf-8"))
    except FileNotFoundError:
        raise SystemExit(f"Accounts file '{ACCOUNTS_FILE}' not found.")

    drive = drive_auth() if DRIVE_FOLDER_ID else None

    # Run accounts sequentially (you can also spawn tasks concurrently if desired)
    for acc in accounts:
        try:
            await process_account(acc, drive)
        except Exception as exc:
            print(f"[ERROR] Account {acc.get('email')} failed: {exc}")


if __name__ == "__main__":
    asyncio.run(main())