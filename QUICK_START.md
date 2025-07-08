# Quick Start Guide

## Setup Cepat (5 Menit)

### 1. Install Dependencies
```bash
python setup.py
```

### 2. Konfigurasi WordPress
Edit file `.env`:
```env
WP_URL=https://yourwordpress.com/xmlrpc.php
WP_USERNAME=your_username
WP_PASSWORD=your_password
```

### 3. Test Koneksi
```bash
python libgen_bot.py --test
```

### 4. Jalankan Bot
```bash
# Cari 3 buku tentang Python
python libgen_bot.py --query "python programming" --max-results 3

# Cari tanpa download file
python libgen_bot.py --query "machine learning" --no-download

# Batch processing
python libgen_bot.py --batch-file queries_example.txt --max-results 2
```

## Contoh Lengkap

```bash
# 1. Setup
python setup.py

# 2. Edit .env dengan kredensial WordPress Anda

# 3. Test
python libgen_bot.py --test

# 4. Cari dan proses buku
python libgen_bot.py --query "artificial intelligence" --search-type title --max-results 5

# 5. Lihat hasil di WordPress Anda!
```

## Tips

- **Mulai kecil**: Test dengan `--max-results 1` dulu
- **Monitor log**: Lihat `libgen_bot.log` untuk debug
- **Storage**: Pastikan ada space untuk download
- **Rate limiting**: Jangan terlalu agresif dengan delay

## Troubleshooting Cepat

### WordPress Error
```bash
# Cek kredensial di .env
# Pastikan XML-RPC enabled di WordPress
```

### Download Error
```bash
# Coba mirror lain di .env:
LIBGEN_BASE_URL=http://libgen.li
```

### Connection Error
```bash
# Tingkatkan delay di .env:
SEARCH_DELAY=5
```

## File yang Dihasilkan

- `downloads/` - File buku yang didownload
- `libgen_bot.log` - Log aktivitas
- `processed_books.json` - Hasil processing
- WordPress posts dengan metadata lengkap

Selamat menggunakan! 🎉