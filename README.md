# Libgen to WordPress Bot

Bot untuk scrape metadata dan download file dari Library Genesis (libgen) kemudian repost ke WordPress dengan metadata yang sesuai.

## Fitur

- 🔍 **Pencarian Buku**: Cari buku berdasarkan judul, penulis, ISBN, atau penerbit
- 📚 **Ekstraksi Metadata**: Ekstrak metadata lengkap (judul, penulis, penerbit, tahun, bahasa, dll.)
- ⬇️ **Download File**: Download file buku dari multiple mirror
- 📝 **WordPress Integration**: Upload otomatis ke WordPress dengan metadata terstruktur
- 🎨 **Tampilan Menarik**: Post WordPress dengan styling yang rapi
- 🔄 **Batch Processing**: Proses multiple query sekaligus
- 📊 **Logging**: Log lengkap untuk monitoring
- ⚙️ **Konfigurasi Fleksibel**: Konfigurasi melalui environment variables

## Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd libgen-wordpress-bot
```

### 2. Install Dependencies
```bash
pip install -r requirements.txt
```

### 3. Konfigurasi

Salin file konfigurasi dan edit sesuai kebutuhan:
```bash
cp .env.example .env
```

Edit file `.env`:
```env
# WordPress Configuration
WP_URL=https://yourwordpress.com/xmlrpc.php
WP_USERNAME=your_username
WP_PASSWORD=your_password

# Download Configuration
DOWNLOAD_DIR=./downloads
MAX_FILE_SIZE_MB=100

# Libgen Configuration
LIBGEN_BASE_URL=http://libgen.rs
SEARCH_DELAY=2

# Bot Configuration
MAX_RETRIES=3
CHUNK_SIZE=1024
```

### 4. Persiapan WordPress

Pastikan WordPress Anda memiliki:
- XML-RPC enabled (biasanya enabled by default)
- User dengan permission untuk upload media dan create posts
- Theme yang mendukung custom fields (optional)

## Penggunaan

### Test Koneksi
```bash
python libgen_bot.py --test
```

### Pencarian Single Query
```bash
# Cari berdasarkan judul
python libgen_bot.py --query "python programming" --search-type title --max-results 3

# Cari berdasarkan penulis
python libgen_bot.py --query "John Doe" --search-type author

# Skip download file (hanya metadata)
python libgen_bot.py --query "machine learning" --no-download
```

### Batch Processing
Buat file `queries.txt` dengan daftar query:
```
python programming
machine learning
data science
web development
artificial intelligence
```

Jalankan batch:
```bash
python libgen_bot.py --batch-file queries.txt --max-results 2
```

### Parameter Lengkap
```bash
python libgen_bot.py --help
```

## Struktur File

```
libgen-wordpress-bot/
├── libgen_scraper.py      # Module untuk scraping libgen
├── wordpress_uploader.py  # Module untuk upload ke WordPress
├── libgen_bot.py         # Script utama
├── requirements.txt      # Dependencies
├── .env.example         # Template konfigurasi
├── README.md           # Dokumentasi
├── downloads/          # Folder download (auto-created)
└── libgen_bot.log     # Log file (auto-created)
```

## Cara Kerja

1. **Pencarian**: Bot mencari buku di libgen berdasarkan query
2. **Ekstraksi**: Ekstrak metadata lengkap dari hasil pencarian
3. **Download**: Download file buku dari mirror yang tersedia
4. **Upload**: Upload file ke WordPress media library
5. **Post**: Buat post WordPress dengan metadata terstruktur

## Format Post WordPress

Setiap post akan berisi:
- **Judul**: Judul buku
- **Konten**: Metadata terstruktur dengan styling
- **Custom Fields**: Metadata dalam format machine-readable
- **Tags**: Auto-generated dari metadata
- **Categories**: "Books", "Library"
- **Media**: File buku sebagai attachment

## Contoh Output

### Metadata yang Diekstrak
```json
{
  "id": "123456",
  "title": "Python Programming: An Introduction",
  "author": "John Doe",
  "publisher": "Tech Books",
  "year": "2023",
  "pages": "450",
  "language": "English",
  "size": "15 MB",
  "extension": "pdf",
  "download_links": ["http://..."],
  "wp_post_id": "789",
  "file_path": "./downloads/Python-Programming-An-Introduction.pdf"
}
```

### Custom Fields WordPress
```
book_author: John Doe
book_publisher: Tech Books
book_year: 2023
book_pages: 450
book_language: English
book_size: 15 MB
book_format: pdf
book_id: 123456
```

## Troubleshooting

### Error Koneksi WordPress
- Pastikan XML-RPC enabled
- Cek URL, username, dan password
- Pastikan user memiliki permission yang cukup

### Error Download
- Beberapa mirror mungkin tidak tersedia
- File terlalu besar (cek MAX_FILE_SIZE_MB)
- Koneksi internet tidak stabil

### Error Libgen
- Website mungkin sedang down
- Ubah LIBGEN_BASE_URL ke mirror lain
- Tingkatkan SEARCH_DELAY

## Konfigurasi Lanjutan

### Mirror Libgen Alternatif
```env
LIBGEN_BASE_URL=http://libgen.li
# atau
LIBGEN_BASE_URL=http://gen.lib.rus.ec
```

### Optimasi Performance
```env
SEARCH_DELAY=1          # Kurangi delay (hati-hati rate limiting)
MAX_FILE_SIZE_MB=500    # Tingkatkan batas ukuran file
CHUNK_SIZE=2048         # Tingkatkan chunk size download
```

## Catatan Penting

⚠️ **Legal Notice**: Pastikan Anda memiliki hak untuk mendownload dan mendistribusikan file yang diproses oleh bot ini. Hormati hak cipta dan undang-undang yang berlaku.

⚠️ **Rate Limiting**: Gunakan delay yang wajar untuk menghindari rate limiting dari server.

⚠️ **Storage**: Pastikan storage cukup untuk file yang akan didownload.

## Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## License

Distributed under the MIT License. See `LICENSE` for more information.

## Support

Jika mengalami masalah atau punya pertanyaan:
1. Cek troubleshooting guide di atas
2. Lihat log file `libgen_bot.log`
3. Buka issue di GitHub repository