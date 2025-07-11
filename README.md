# Z-Library to Google Drive Bot

## Features
- Multi-account Z-Library login
- Search and download book metadata, cover, and file
- Upload cover and file to Google Drive
- Store all metadata in a single CSV file

## Setup

### 1. Clone and Install Requirements
```
python3 -m venv venv
source venv/bin/activate
pip install zlibrary google-api-python-client google-auth-httplib2 google-auth-oauthlib pandas
```

### 2. Google Drive API Credentials
- Go to [Google Cloud Console](https://console.cloud.google.com/)
- Create a project and enable the Google Drive API
- Create OAuth 2.0 credentials (Desktop app)
- Download `credentials.json` and place it in the project root

### 3. Configure Z-Library Accounts
Edit `zlib_gdrive_bot.py`:
```
ZLIB_ACCOUNTS = [
    {"email": "your_email1@example.com", "password": "your_password1"},
    {"email": "your_email2@example.com", "password": "your_password2"},
]
```

## Usage
```
source venv/bin/activate
python zlib_gdrive_bot.py
```
- Enter your search query when prompted.
- The bot will download the first result for each account, save metadata to `zlib_metadata.csv`, and upload files to Google Drive.

## Notes
- The first run will open a browser for Google authentication.
- All metadata, including Google Drive file IDs, is stored in `zlib_metadata.csv`.
- Covers and files are saved in `covers/` and `files/` folders.