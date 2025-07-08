# Libgen ➜ WordPress Bot

This simple Python script searches for a book on **Library Genesis**, downloads the file, and republishes it on your WordPress site with matching metadata.

> ⚠️  **Disclaimer**: Make sure you have the legal right to distribute any content you upload to WordPress. Respect copyrights in your jurisdiction.

---

## Features

1. Searches Libgen (title, author, or ISBN).
2. Chooses the best-matching result (prefers PDF/EPUB, newest year).
3. Downloads the file.
4. Uploads the file to WordPress media library.
5. Publishes a post containing metadata (author, year, language, etc.) and a download link.

## Quick start

```bash
# 1. Clone this repo / copy the script into your workspace

# 2. Install dependencies (preferably in a virtualenv)
pip install -r requirements.txt

# 3. Configure environment variables (copy the template)
cp .env.example .env  # edit values

# 4. Run the bot with a search query or ISBN
python libgen_wp_bot.py "Clean Code"
```

### Environment variables (.env)

```
WP_URL=https://your-wordpress-site.com
WP_USER=your_username
WP_APP_PASS=your_application_password   # create under WP → User Profile
```

The script also respects `DOWNLOAD_DIR` (default `downloads`) if you want to customise where files are stored.

### Creating an *application password*

1. Log in to WordPress (must be on WP ≥ 5.6).
2. Go to **Users → Profile**.
3. Scroll to **Application Passwords**, give it a name, and click **Add New**.
4. Copy the generated 24-character password – **there’s no second chance!**
5. Put it in `WP_APP_PASS`.

## Extending the bot

* Change `choose_best_result()` to adjust ranking logic.
* Edit `create_post()` to add categories, tags, or a different post layout.
* Wrap `process_query()` in a loop or cron job to automate multiple uploads.

---

MIT License – do whatever you want, but no warranty.