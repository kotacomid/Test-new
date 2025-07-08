import os
import sys
from urllib.parse import urlsplit

import requests
from libgen_api import LibgenSearch
from dotenv import load_dotenv

load_dotenv()

# === Configuration ===
# Set these environment variables (e.g. in a .env file)
WP_URL = os.getenv("WP_URL")  # e.g. "https://your-wordpress-site.com"
WP_USER = os.getenv("WP_USER")  # WordPress username
WP_APP_PASS = os.getenv("WP_APP_PASS")  # Application password or basic auth token
DOWNLOAD_DIR = os.getenv("DOWNLOAD_DIR", "downloads")

if not all([WP_URL, WP_USER, WP_APP_PASS]):
    print(
        "Error: Please set WP_URL, WP_USER, and WP_APP_PASS in environment variables or .env file.",
        file=sys.stderr,
    )
    sys.exit(1)


# === Libgen helpers ===

def search_libgen(query: str, search_field: str = "title"):
    """Search Libgen and return a list of result dictionaries."""
    lg = LibgenSearch()
    if search_field == "author":
        return lg.search_author(query)
    if search_field == "isbn":
        return lg.search_isbn(query)
    # Default: search by title
    return lg.search_title(query)


def choose_best_result(results):
    """Pick a single result. Currently chooses the newest PDF/EPUB if possible."""
    if not results:
        return None

    # Prioritise PDF/EPUB then by year descending
    preferred_ext = {"pdf": 1, "epub": 2}

    def sort_key(x):
        ext_score = preferred_ext.get(x.get("Extension", "").lower(), 99)
        # Newer year first; unknown years treated as 0
        year = int(x.get("Year", 0)) if str(x.get("Year")).isdigit() else 0
        return (ext_score, -year)

    return sorted(results, key=sort_key)[0]


def get_download_link(result_dict):
    """Resolve mirrors and return a direct download URL."""
    lg = LibgenSearch()
    mirrors = lg.resolve_download_links(result_dict)
    # Heuristic: prefer directly downloadable links
    for key in (
        "GET",
        "Cloudflare",
        "IPFS.io",
        "Glib",
        "FTP",
        "http://",
        "https://",
    ):
        link = mirrors.get(key) if isinstance(mirrors, dict) else None
        if link:
            return link
    # Fall back to the first available link
    return next(iter(mirrors.values())) if mirrors else None


def download_file(url: str, dst_folder: str = DOWNLOAD_DIR) -> str:
    """Download URL to dst_folder and return local file path."""
    os.makedirs(dst_folder, exist_ok=True)
    filename = urlsplit(url).path.split("/")[-1] or "libgen_download"
    filepath = os.path.join(dst_folder, filename)

    with requests.get(url, stream=True, timeout=30) as r:
        r.raise_for_status()
        with open(filepath, "wb") as f:
            for chunk in r.iter_content(chunk_size=8192):
                if chunk:
                    f.write(chunk)
    return filepath


# === WordPress helpers ===

def _wp_auth():
    return (WP_USER, WP_APP_PASS)


def upload_media(filepath: str) -> dict:
    """Upload a file to WP media library. Returns media JSON."""
    filename = os.path.basename(filepath)
    headers = {
        "Content-Disposition": f"attachment; filename={filename}",
    }
    with open(filepath, "rb") as f:
        file_data = f.read()
    resp = requests.post(
        f"{WP_URL}/wp-json/wp/v2/media",
        headers=headers,
        data=file_data,
        auth=_wp_auth(),
        timeout=60,
    )
    resp.raise_for_status()
    return resp.json()


def create_post(meta: dict, media_json: dict) -> dict:
    """Publish a WordPress post with given metadata and attached media."""
    title = meta.get("Title") or "Untitled"
    authors = meta.get("Author")
    year = meta.get("Year")
    language = meta.get("Language")
    extension = meta.get("Extension")
    pages = meta.get("Pages")
    publisher = meta.get("Publisher")

    content_parts = [
        f"<p><strong>Author(s):</strong> {authors}</p>" if authors else "",
        f"<p><strong>Year:</strong> {year}</p>" if year else "",
        f"<p><strong>Language:</strong> {language}</p>" if language else "",
        f"<p><strong>Pages:</strong> {pages}</p>" if pages else "",
        f"<p><strong>Publisher:</strong> {publisher}</p>" if publisher else "",
        f"<p><a href=\"{media_json.get('source_url')}\">Download ({extension.upper()})</a></p>"
        if media_json.get("source_url")
        else "",
    ]

    payload = {
        "title": title,
        "status": "publish",
        "content": "\n".join(filter(None, content_parts)),
    }

    resp = requests.post(
        f"{WP_URL}/wp-json/wp/v2/posts",
        json=payload,
        auth=_wp_auth(),
        timeout=30,
    )
    resp.raise_for_status()
    return resp.json()


# === Orchestration ===

def process_query(query: str):
    print(f"Searching Libgen for: {query}")
    results = search_libgen(query)
    best = choose_best_result(results)

    if not best:
        print("No results found.")
        return

    print(f"Chosen: {best.get('Title')} ({best.get('Year')}) by {best.get('Author')}")

    download_url = get_download_link(best)
    if not download_url:
        print("No downloadable link was found.")
        return

    print("Downloading file ...")
    local_path = download_file(download_url)
    print(f"Saved to {local_path}")

    print("Uploading to WordPress media library ...")
    media_info = upload_media(local_path)
    print("Media uploaded.")

    print("Creating WordPress post ...")
    post_info = create_post(best, media_info)
    print(f"Post published: {post_info.get('link')}")


# === CLI entry point ===

def main():
    if len(sys.argv) < 2:
        print(
            "Usage: python libgen_wp_bot.py \"search query or ISBN\"",
            file=sys.stderr,
        )
        sys.exit(1)

    query = " ".join(sys.argv[1:]).strip()
    process_query(query)


if __name__ == "__main__":
    main()