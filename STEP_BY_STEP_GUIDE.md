# 🚀 Step-by-Step Guide: LibGen to WordPress Scraper (Free Tier)

## 📋 Prerequisites (5 menit)

### ✅ **Yang Anda Butuhkan:**
- Python 3.7+ installed
- Text editor (VS Code recommended)
- Internet connection
- WordPress site (free atau self-hosted)
- Email untuk membuat accounts

---

## 🏁 **STEP 1: Setup Environment (10 menit)**

### 1.1 Create Project Folder
```bash
# Buat folder project
mkdir libgen-scraper
cd libgen-scraper

# Buat virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Mac/Linux:
source venv/bin/activate
```

### 1.2 Create Requirements File
Buat file `requirements.txt`:
```txt
requests==2.31.0
beautifulsoup4==4.12.2
python-dotenv==1.0.0
schedule==1.2.0
```

### 1.3 Install Dependencies
```bash
pip install -r requirements.txt
```

**✅ Checkpoint:** Virtual environment activated dan packages installed

---

## 🏗️ **STEP 2: Setup WordPress (15 menit)**

### 2.1 Option A: WordPress.com (Free - Recommended untuk testing)

1. **Daftar di WordPress.com:**
   - Go to https://wordpress.com
   - Click "Start your website"
   - Choose FREE plan
   - Pilih subdomain: `yourname.wordpress.com`

2. **Enable Application Passwords:**
   - Login ke WordPress.com
   - Go to https://wordpress.com/me/security
   - Scroll ke "Application Passwords"
   - Add application password dengan nama: "LibGen Scraper"
   - **COPY password yang digenerate** (save di notepad)

### 2.2 Option B: Self-hosted WordPress (Free hosting)

1. **Daftar Free Hosting:**
   - Go to https://infinityfree.net atau https://000webhost.com
   - Create free account
   - Create new website

2. **Install WordPress:**
   - Gunakan 1-click WordPress installer
   - Setup admin username/password

3. **Setup Application Password:**
   - Login ke WordPress admin
   - Install plugin "Application Passwords"
   - Users → Your Profile → scroll ke bawah
   - Generate application password

**✅ Checkpoint:** WordPress site ready + application password tersimpan

---

## 💾 **STEP 3: Create Core Files (20 menit)**

### 3.1 Create Environment File
Buat file `.env`:
```bash
# WordPress Configuration
WP_URL=https://yoursite.wordpress.com
WP_USERNAME=your_username
WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx

# Scraping Configuration  
MAX_BOOKS_PER_SEARCH=10
SCRAPE_DELAY_MIN=3
SCRAPE_DELAY_MAX=7
```

**⚠️ IMPORTANT:** Ganti dengan WordPress URL dan credentials Anda!

### 3.2 Create Main Scraper File
Buat file `scraper.py` (copy dari file yang sudah saya buat sebelumnya):

```python
#!/usr/bin/env python3
"""
Free Tier LibGen Scraper - Quick Prototype
"""

import requests
from bs4 import BeautifulSoup
import sqlite3
import time
import random
import os
from datetime import datetime

class QuickLibGenScraper:
    def __init__(self, db_path="books.db"):
        self.db_path = db_path
        self.setup_database()
        print("🚀 Quick LibGen Scraper initialized (Free Tier)")
        
    def setup_database(self):
        """Setup SQLite database"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS books (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                author TEXT,
                year TEXT,
                category TEXT,
                download_url TEXT,
                posted_to_wp BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        conn.commit()
        conn.close()
        print("✅ Database setup complete")
    
    def scrape_books(self, search_term, category="general", max_books=10):
        """Scrape books with free tier optimizations"""
        print(f"🔍 Searching for: '{search_term}' (Max: {max_books} books)")
        
        # Conservative delay to avoid blocking
        delay = random.uniform(3, 6)
        print(f"⏳ Waiting {delay:.1f} seconds before request...")
        time.sleep(delay)
        
        url = "http://libgen.is/search.php"
        params = {
            'req': search_term,
            'res': 25,
            'view': 'simple',
            'phrase': 1,
            'column': 'def'
        }
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        try:
            response = requests.get(url, params=params, headers=headers, timeout=15)
            response.raise_for_status()
            
            books = self._parse_results(response.text, category, max_books)
            saved_count = self._save_to_database(books)
            
            print(f"✅ Found {len(books)} books, saved {saved_count} new books")
            return books
            
        except requests.exceptions.RequestException as e:
            print(f"❌ Network error: {e}")
            return []
        except Exception as e:
            print(f"❌ Unexpected error: {e}")
            return []
    
    def _parse_results(self, html, category, max_books):
        """Parse LibGen search results"""
        soup = BeautifulSoup(html, 'html.parser')
        books = []
        
        tables = soup.find_all('table')
        if len(tables) < 3:
            print("⚠️ No results table found")
            return books
            
        results_table = tables[2]
        rows = results_table.find_all('tr')[1:]
        
        for i, row in enumerate(rows):
            if i >= max_books:
                break
                
            try:
                cells = row.find_all('td')
                if len(cells) < 9:
                    continue
                
                title = cells[2].get_text(strip=True)
                author = cells[1].get_text(strip=True)
                year = cells[4].get_text(strip=True)
                
                download_links = []
                if len(cells) > 9:
                    for link in cells[9].find_all('a'):
                        href = link.get('href')
                        if href and ('library.lol' in href or 'libgen' in href):
                            download_links.append(href)
                
                if (len(title) > 3 and 
                    author not in ['', 'Unknown', 'N/A'] and
                    len(title) < 200):
                    
                    book = {
                        'title': title,
                        'author': author,
                        'year': year,
                        'category': category,
                        'download_url': download_links[0] if download_links else ''
                    }
                    books.append(book)
                    
            except Exception as e:
                print(f"⚠️ Error parsing row {i}: {e}")
                continue
        
        return books
    
    def _save_to_database(self, books):
        """Save books to database, avoid duplicates"""
        if not books:
            return 0
            
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        saved_count = 0
        
        for book in books:
            try:
                cursor.execute('''
                    SELECT COUNT(*) FROM books 
                    WHERE title = ? AND author = ?
                ''', (book['title'], book['author']))
                
                if cursor.fetchone()[0] == 0:
                    cursor.execute('''
                        INSERT INTO books (title, author, year, category, download_url)
                        VALUES (?, ?, ?, ?, ?)
                    ''', (
                        book['title'],
                        book['author'],
                        book['year'],
                        book['category'],
                        book['download_url']
                    ))
                    saved_count += 1
                    
            except Exception as e:
                print(f"⚠️ Database error: {e}")
                continue
        
        conn.commit()
        conn.close()
        return saved_count
    
    def get_stats(self):
        """Get database statistics"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('SELECT COUNT(*) FROM books')
        total_books = cursor.fetchone()[0]
        
        cursor.execute('SELECT COUNT(*) FROM books WHERE posted_to_wp = TRUE')
        posted_books = cursor.fetchone()[0]
        
        cursor.execute('SELECT COUNT(*) FROM books WHERE posted_to_wp = FALSE')
        pending_books = cursor.fetchone()[0]
        
        cursor.execute('SELECT DISTINCT category FROM books')
        categories = [row[0] for row in cursor.fetchall()]
        
        conn.close()
        
        return {
            'total_books': total_books,
            'posted_books': posted_books,
            'pending_books': pending_books,
            'categories': categories
        }
    
    def show_recent_books(self, limit=5):
        """Show recently scraped books"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT title, author, year, category 
            FROM books 
            ORDER BY created_at DESC 
            LIMIT ?
        ''', (limit,))
        
        books = cursor.fetchall()
        conn.close()
        
        if books:
            print(f"\n📚 Recent {len(books)} books:")
            for i, (title, author, year, category) in enumerate(books, 1):
                print(f"{i}. {title} - {author} ({year}) [{category}]")
        else:
            print("📚 No books in database yet")

def main():
    """Quick test function"""
    scraper = QuickLibGenScraper()
    
    stats = scraper.get_stats()
    print(f"\n📊 Current Stats:")
    print(f"   Total books: {stats['total_books']}")
    print(f"   Posted to WP: {stats['posted_books']}")
    print(f"   Pending: {stats['pending_books']}")
    print(f"   Categories: {', '.join(stats['categories']) if stats['categories'] else 'None'}")
    
    print(f"\n🧪 Quick test: Scraping 5 Python books...")
    books = scraper.scrape_books("Python programming", "programming", max_books=5)
    
    if books:
        print(f"\n✅ Success! Scraped {len(books)} books")
        scraper.show_recent_books(5)
    else:
        print(f"\n❌ No books found or error occurred")

if __name__ == "__main__":
    main()
```

**✅ Checkpoint:** File scraper.py created

---

## 🧪 **STEP 4: Test Scraping (5 menit)**

### 4.1 First Test Run
```bash
python scraper.py
```

**Expected Output:**
```
🚀 Quick LibGen Scraper initialized (Free Tier)
✅ Database setup complete

📊 Current Stats:
   Total books: 0
   Posted to WP: 0
   Pending: 0
   Categories: None

🧪 Quick test: Scraping 5 Python books...
🔍 Searching for: 'Python programming' (Max: 5 books)
⏳ Waiting 4.2 seconds before request...
✅ Found 5 books, saved 5 new books

✅ Success! Scraped 5 books

📚 Recent 5 books:
1. Learning Python - Mark Lutz (2013) [programming]
2. Python Crash Course - Eric Matthes (2019) [programming]
...
```

### 4.2 Check Database
```bash
# Optional: Install sqlite3 viewer
pip install sqlite-utils

# View data
sqlite-utils query books.db "SELECT title, author FROM books LIMIT 5"
```

**✅ Checkpoint:** Scraping works dan data tersimpan di database

---

## 📤 **STEP 5: Create WordPress Publisher (15 menit)**

### 5.1 Create WordPress Publisher File
Buat file `wp_publisher.py`:

```python
#!/usr/bin/env python3
"""
WordPress Publisher - Free Tier
Posts scraped books to WordPress
"""

import requests
import base64
import sqlite3
import time
import random
import os
from dotenv import load_dotenv

load_dotenv()

class WordPressPublisher:
    def __init__(self, db_path="books.db"):
        self.wp_url = os.getenv('WP_URL').rstrip('/')
        self.db_path = db_path
        self.headers = self._setup_auth()
        print("📤 WordPress Publisher initialized")
        
    def _setup_auth(self):
        """Setup WordPress authentication"""
        username = os.getenv('WP_USERNAME')
        app_password = os.getenv('WP_APP_PASSWORD')
        
        if not username or not app_password:
            raise ValueError("❌ WordPress credentials not found in .env file")
        
        credentials = f"{username}:{app_password}"
        token = base64.b64encode(credentials.encode()).decode('utf-8')
        return {
            'Authorization': f'Basic {token}',
            'Content-Type': 'application/json'
        }
    
    def test_connection(self):
        """Test WordPress API connection"""
        try:
            response = requests.get(
                f"{self.wp_url}/wp-json/wp/v2/posts?per_page=1",
                headers=self.headers,
                timeout=10
            )
            
            if response.status_code == 200:
                print("✅ WordPress connection successful")
                return True
            else:
                print(f"❌ WordPress connection failed: {response.status_code}")
                print(f"Response: {response.text}")
                return False
                
        except Exception as e:
            print(f"❌ WordPress connection error: {e}")
            return False
    
    def publish_pending_books(self, batch_size=3):
        """Publish books in small batches"""
        books = self._get_unpublished_books(batch_size)
        
        if not books:
            print("ℹ️ No pending books to publish")
            return 0
            
        print(f"📤 Publishing {len(books)} books...")
        published_count = 0
        
        for book in books:
            try:
                success = self._create_wordpress_post(book)
                if success:
                    self._mark_as_published(book[0])  # book[0] is ID
                    published_count += 1
                    print(f"✅ Published: {book[1]}")  # book[1] is title
                else:
                    print(f"❌ Failed: {book[1]}")
                    
                # Rate limiting
                delay = random.uniform(2, 4)
                print(f"⏳ Waiting {delay:.1f} seconds...")
                time.sleep(delay)
                
            except Exception as e:
                print(f"❌ Error publishing {book[1]}: {e}")
        
        print(f"🎉 Published {published_count}/{len(books)} books successfully")
        return published_count
    
    def _get_unpublished_books(self, limit=3):
        """Get books that haven't been posted yet"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT id, title, author, year, category, download_url 
            FROM books 
            WHERE posted_to_wp = FALSE 
            LIMIT ?
        ''', (limit,))
        
        books = cursor.fetchall()
        conn.close()
        return books
    
    def _create_wordpress_post(self, book):
        """Create WordPress post"""
        book_id, title, author, year, category, download_url = book
        
        # Generate content
        content = f"""
        <div class="book-info">
            <h2>📚 {title}</h2>
            <div class="book-meta">
                <p><strong>👤 Penulis:</strong> {author}</p>
                <p><strong>📅 Tahun:</strong> {year}</p>
                <p><strong>🏷️ Kategori:</strong> {category}</p>
            </div>
        </div>
        
        <div class="book-description">
            <h3>📖 Tentang Buku Ini</h3>
            <p>"{title}" adalah buku karya <strong>{author}</strong> yang diterbitkan pada tahun {year}. 
            Buku ini masuk dalam kategori <em>{category}</em> dan dapat menjadi referensi yang bermanfaat 
            untuk pembelajaran dan pengembangan pengetahuan.</p>
        </div>
        
        <div class="download-section">
            <h3>📥 Informasi Download</h3>
            <div class="disclaimer">
                <p><strong>⚠️ Disclaimer:</strong> Pastikan penggunaan buku ini sesuai dengan hukum hak cipta 
                yang berlaku di negara Anda. Gunakan hanya untuk keperluan pendidikan dan penelitian yang sah.</p>
            </div>
            
            {f'<p><a href="{download_url}" target="_blank" class="download-button">📥 Download Buku</a></p>' if download_url else '<p>Download link tidak tersedia.</p>'}
        </div>
        
        <div class="book-tags">
            <p><strong>🏷️ Tags:</strong> {category}, {author.split()[0] if author else ''}, ebook, download, {year}</p>
        </div>
        """
        
        # Prepare post data
        post_data = {
            'title': f"{title} - {author}",
            'content': content,
            'status': 'publish',
            'excerpt': f"Download buku {title} karya {author} ({year}) - Kategori: {category}. Referensi untuk pembelajaran dan penelitian.",
            'categories': [1],  # Default category ID
            'tags': [category, author.split()[0] if author else '', 'ebook', 'download', year]
        }
        
        try:
            response = requests.post(
                f"{self.wp_url}/wp-json/wp/v2/posts",
                headers=self.headers,
                json=post_data,
                timeout=30
            )
            
            if response.status_code == 201:
                return True
            else:
                print(f"⚠️ WordPress API error: {response.status_code}")
                print(f"Response: {response.text[:200]}...")
                return False
                
        except Exception as e:
            print(f"❌ Request error: {e}")
            return False
    
    def _mark_as_published(self, book_id):
        """Mark book as published in database"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE books SET posted_to_wp = TRUE WHERE id = ?
        ''', (book_id,))
        
        conn.commit()
        conn.close()

def main():
    """Test WordPress publisher"""
    try:
        publisher = WordPressPublisher()
        
        # Test connection
        if publisher.test_connection():
            print("\n🧪 Testing publication of 1 book...")
            published = publisher.publish_pending_books(batch_size=1)
            
            if published > 0:
                print(f"✅ Test successful! Published {published} book(s)")
            else:
                print("❌ No books were published")
        else:
            print("❌ Cannot test publication due to connection issues")
            
    except Exception as e:
        print(f"❌ Error: {e}")
        print("\n🔧 Troubleshooting:")
        print("1. Check your .env file")
        print("2. Verify WordPress credentials")
        print("3. Make sure WordPress site is accessible")

if __name__ == "__main__":
    main()
```

**✅ Checkpoint:** WordPress publisher created

---

## 🧪 **STEP 6: Test WordPress Integration (10 menit)**

### 6.1 Test WordPress Connection
```bash
python wp_publisher.py
```

**Expected Output:**
```
📤 WordPress Publisher initialized
✅ WordPress connection successful

🧪 Testing publication of 1 book...
📤 Publishing 1 books...
⏳ Waiting 3.2 seconds...
✅ Published: Learning Python - Mark Lutz
🎉 Published 1/1 books successfully
✅ Test successful! Published 1 book(s)
```

### 6.2 Check Your WordPress Site
- Login ke WordPress admin
- Go to Posts → All Posts
- Cek apakah ada post baru yang terbuat

**✅ Checkpoint:** WordPress integration working

---

## 🔄 **STEP 7: Create Automation Script (10 menit)**

### 7.1 Create Main Automation Script
Buat file `automation.py`:

```python
#!/usr/bin/env python3
"""
LibGen to WordPress Automation - Free Tier
Complete workflow for scraping and publishing
"""

import time
from scraper import QuickLibGenScraper
from wp_publisher import WordPressPublisher

class LibGenAutomation:
    def __init__(self):
        self.scraper = QuickLibGenScraper()
        self.publisher = WordPressPublisher()
        
        # Search queue for different categories
        self.search_queue = [
            ("Python programming", "programming"),
            ("JavaScript tutorial", "programming"),
            ("Web development", "programming"),
            ("Digital marketing", "business"),
            ("Data science", "science"),
            ("Machine learning", "science"),
            ("Web design", "design"),
            ("Graphic design", "design"),
            ("Business strategy", "business"),
            ("Entrepreneurship", "business")
        ]
        
        print("🤖 LibGen Automation initialized")
    
    def run_daily_workflow(self, max_searches=2, books_per_search=5, publish_batch=3):
        """Run daily automation workflow"""
        print(f"\n🚀 Starting Daily Workflow")
        print(f"📋 Plan: {max_searches} searches, {books_per_search} books each, publish {publish_batch}")
        print("=" * 50)
        
        # Step 1: Scraping
        total_scraped = 0
        for i in range(max_searches):
            if i >= len(self.search_queue):
                print("⚠️ No more search terms available")
                break
                
            search_term, category = self.search_queue[i]
            print(f"\n🔍 Search {i+1}/{max_searches}: {search_term}")
            
            books = self.scraper.scrape_books(search_term, category, books_per_search)
            total_scraped += len(books)
            
            # Delay between searches
            if i < max_searches - 1:
                print("⏳ Waiting 30 seconds before next search...")
                time.sleep(30)
        
        print(f"\n📊 Scraping Summary: {total_scraped} books scraped")
        
        # Step 2: Publishing
        print(f"\n📤 Publishing Phase...")
        if self.publisher.test_connection():
            published = self.publisher.publish_pending_books(publish_batch)
            print(f"📊 Publishing Summary: {published} books published")
        else:
            print("❌ Skipping publishing due to connection issues")
        
        # Step 3: Final Stats
        stats = self.scraper.get_stats()
        print(f"\n📈 Final Stats:")
        print(f"   Total books in DB: {stats['total_books']}")
        print(f"   Published to WP: {stats['posted_books']}")
        print(f"   Pending: {stats['pending_books']}")
        print(f"   Categories: {len(stats['categories'])}")
        
        return {
            'scraped': total_scraped,
            'published': published if 'published' in locals() else 0,
            'total_in_db': stats['total_books']
        }
    
    def run_small_test(self):
        """Run small test workflow"""
        print("🧪 Running Small Test Workflow")
        print("=" * 30)
        
        # Test scrape 3 books
        books = self.scraper.scrape_books("Python", "programming", 3)
        print(f"📥 Scraped: {len(books)} books")
        
        # Test publish 1 book
        if books and self.publisher.test_connection():
            published = self.publisher.publish_pending_books(1)
            print(f"📤 Published: {published} books")
        
        # Show recent books
        self.scraper.show_recent_books(3)

def main():
    """Main automation function"""
    automation = LibGenAutomation()
    
    print("🎯 LibGen to WordPress Automation")
    print("=" * 40)
    print("1. Small Test (3 books)")
    print("2. Daily Workflow (10 books)")
    print("3. Custom Workflow")
    
    choice = input("\nChoose option (1-3): ").strip()
    
    if choice == "1":
        automation.run_small_test()
    elif choice == "2":
        result = automation.run_daily_workflow(max_searches=2, books_per_search=5, publish_batch=3)
        print(f"\n🎉 Daily workflow completed!")
        print(f"Results: {result}")
    elif choice == "3":
        max_searches = int(input("How many searches? (1-5): "))
        books_per_search = int(input("Books per search? (3-10): "))
        publish_batch = int(input("How many to publish? (1-5): "))
        
        result = automation.run_daily_workflow(max_searches, books_per_search, publish_batch)
        print(f"\n🎉 Custom workflow completed!")
        print(f"Results: {result}")
    else:
        print("❌ Invalid choice")

if __name__ == "__main__":
    main()
```

**✅ Checkpoint:** Automation script created

---

## 🎯 **STEP 8: Run Complete Test (15 menit)**

### 8.1 Run Small Test First
```bash
python automation.py
```

Choose option `1` (Small Test):

**Expected Output:**
```
🤖 LibGen Automation initialized
🎯 LibGen to WordPress Automation
========================================
1. Small Test (3 books)
2. Daily Workflow (10 books)  
3. Custom Workflow

Choose option (1-3): 1

🧪 Running Small Test Workflow
==============================
🔍 Searching for: 'Python' (Max: 3 books)
⏳ Waiting 4.5 seconds before request...
✅ Found 3 books, saved 2 new books
📥 Scraped: 3 books

📤 WordPress Publisher initialized
✅ WordPress connection successful
📤 Publishing 1 books...
✅ Published: Python Tricks - Dan Bader
🎉 Published 1/1 books successfully
📤 Published: 1 books

📚 Recent 3 books:
1. Python Tricks - Dan Bader (2017) [programming]
2. Effective Python - Brett Slatkin (2019) [programming]
3. Clean Code - Robert C. Martin (2008) [programming]
```

### 8.2 Check WordPress Site
- Login ke WordPress admin
- Verify post baru sudah terbuat
- Check formatting dan content

### 8.3 Run Daily Workflow
```bash
python automation.py
```

Choose option `2` (Daily Workflow)

**✅ Checkpoint:** Complete workflow working end-to-end

---

## 🚀 **STEP 9: Deploy to Free Hosting (20 menit)**

### 9.1 Setup Railway Account (Free)

1. **Sign up di Railway:**
   - Go to https://railway.app
   - Sign up dengan GitHub account

2. **Create New Project:**
   - Click "New Project"
   - Choose "Deploy from GitHub repo"
   - Connect your GitHub account

### 9.2 Prepare for Deployment

1. **Create GitHub Repository:**
```bash
git init
git add .
git commit -m "Initial commit: LibGen WordPress scraper"

# Push ke GitHub (create repo dulu di github.com)
git remote add origin https://github.com/yourusername/libgen-scraper.git
git push -u origin main
```

2. **Create railway.toml:**
```toml
[build]
builder = "NIXPACKS"

[deploy]
startCommand = "python automation.py"
healthcheckPath = "/"
healthcheckTimeout = 300
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10
```

3. **Add Environment Variables di Railway:**
   - Go to Railway dashboard
   - Select your project
   - Go to Variables tab
   - Add semua variables dari .env file

### 9.3 Alternative: Run Locally with Scheduler

Buat file `scheduler.py`:
```python
import schedule
import time
from automation import LibGenAutomation

def daily_job():
    """Daily automation job"""
    print("🔄 Running daily automation...")
    automation = LibGenAutomation()
    automation.run_daily_workflow(max_searches=2, books_per_search=5, publish_batch=3)

# Schedule daily at 9 AM
schedule.every().day.at("09:00").do(daily_job)

print("⏰ Scheduler started. Daily job at 09:00")
print("Press Ctrl+C to stop")

while True:
    schedule.run_pending()
    time.sleep(60)  # Check every minute
```

Run scheduler:
```bash
python scheduler.py
```

**✅ Checkpoint:** Automation deployed atau running locally

---

## 📊 **STEP 10: Monitor & Optimize (10 menit)**

### 10.1 Check Results Daily
```bash
# Check database stats
python -c "
from scraper import QuickLibGenScraper
s = QuickLibGenScraper()
stats = s.get_stats()
print(f'Books: {stats[\"total_books\"]}, Published: {stats[\"posted_books\"]}, Pending: {stats[\"pending_books\"]}')
"
```

### 10.2 View Recent Activity
```bash
# Show recent books
python -c "
from scraper import QuickLibGenScraper  
s = QuickLibGenScraper()
s.show_recent_books(10)
"
```

### 10.3 Manual Publish if Needed
```bash
# Manually publish pending books
python -c "
from wp_publisher import WordPressPublisher
p = WordPressPublisher()
p.publish_pending_books(5)
"
```

---

## 🎉 **SUCCESS! Complete Setup Done**

### 📈 **Expected Results (Free Tier):**
- **Daily scraping:** 10-20 buku
- **Daily publishing:** 3-5 posts
- **Total target:** 200 buku dalam 2-4 minggu
- **Cost:** $0 (completely free)

### 🔧 **Troubleshooting Common Issues:**

**1. "No books found" Error:**
```bash
# Check if LibGen domain is accessible
curl -I http://libgen.is
# Try alternative: http://libgen.rs
```

**2. WordPress Connection Failed:**
```bash
# Test WordPress API manually
curl -H "Authorization: Basic YOUR_TOKEN" https://yoursite.com/wp-json/wp/v2/posts?per_page=1
```

**3. Database Locked Error:**
```bash
# Close any Python processes
pkill -f python
# Remove database lock
rm books.db-wal books.db-shm
```

### 📱 **Next Steps & Improvements:**
1. **Add more search terms** di automation.py
2. **Customize WordPress post formatting**
3. **Add error notifications** via email
4. **Implement retry logic** untuk failed requests
5. **Add content filters** untuk quality control

### 📞 **Support:**
Jika ada masalah, check:
1. `.env` file credentials benar
2. WordPress site accessible
3. Internet connection stable
4. LibGen domain tidak diblokir ISP

**🎊 Congratulations!** Anda sekarang memiliki sistem automasi scraping LibGen ke WordPress yang berjalan 100% gratis!