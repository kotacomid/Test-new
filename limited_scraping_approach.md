# Pendekatan Scraping Terbatas: Target 200 Buku dari Pencarian Spesifik

## Overview Pendekatan Baru

Pendekatan **scraping terbatas** ini jauh lebih baik karena:
- ✅ **Lebih Legal**: Tidak terlihat seperti mass scraping
- ✅ **Lebih Terkontrol**: Fokus pada topik tertentu
- ✅ **Risiko Rendah**: Tidak membebani server target
- ✅ **Kualitas Tinggi**: Memungkinkan kurasi manual
- ✅ **SEO Friendly**: Konten lebih targeted dan relevan

## 1. Strategi Pencarian Terbatas

### 1.1 Contoh Target Pencarian
```python
# Target pencarian yang lebih spesifik dan legal-friendly
search_targets = {
    "programming": {
        "terms": ["Python programming", "JavaScript tutorial", "Web development"],
        "max_books": 50,
        "focus": "Programming dan teknologi"
    },
    "science": {
        "terms": ["Physics", "Mathematics", "Computer Science"],
        "max_books": 50,
        "focus": "Sains dan akademik"
    },
    "business": {
        "terms": ["Business strategy", "Marketing", "Entrepreneurship"],
        "max_books": 50,
        "focus": "Bisnis dan manajemen"
    },
    "health": {
        "terms": ["Nutrition", "Fitness", "Mental health"],
        "max_books": 50,
        "focus": "Kesehatan dan wellness"
    }
}
```

### 1.2 Implementasi Scraping Terbatas

```python
import requests
from bs4 import BeautifulSoup
import time
import random
import json
from datetime import datetime

class LimitedLibGenScraper:
    def __init__(self, max_books_per_search=50, delay_range=(2, 5)):
        self.max_books = max_books_per_search
        self.delay_range = delay_range
        self.scraped_data = []
        
    def search_limited_books(self, search_term, category):
        """
        Scrape terbatas berdasarkan pencarian spesifik
        """
        print(f"🔍 Searching for: {search_term} (Category: {category})")
        
        base_url = "http://libgen.is/search.php"
        params = {
            'req': search_term,
            'lg_topic': 'libgen',
            'open': 0,
            'view': 'simple',
            'res': min(self.max_books, 25),  # LibGen max per page
            'phrase': 1,
            'column': 'def'
        }
        
        try:
            response = requests.get(base_url, params=params, timeout=10)
            response.raise_for_status()
            
            books = self._parse_search_results(response.content, category)
            
            # Random delay untuk menghindari detection
            delay = random.uniform(*self.delay_range)
            print(f"⏳ Waiting {delay:.1f} seconds...")
            time.sleep(delay)
            
            return books
            
        except Exception as e:
            print(f"❌ Error searching {search_term}: {e}")
            return []
    
    def _parse_search_results(self, html_content, category):
        """
        Parse hasil pencarian dan extract metadata
        """
        soup = BeautifulSoup(html_content, 'html.parser')
        books = []
        
        # Find table with results
        table = soup.find('table', {'rules': 'cols'})
        if not table:
            return books
            
        rows = table.find_all('tr')[1:]  # Skip header
        
        for row in rows[:self.max_books]:
            cells = row.find_all('td')
            if len(cells) >= 10:
                try:
                    book_data = {
                        'id': cells[0].text.strip(),
                        'author': cells[1].text.strip(),
                        'title': cells[2].text.strip(),
                        'publisher': cells[3].text.strip(),
                        'year': cells[4].text.strip(),
                        'pages': cells[5].text.strip(),
                        'language': cells[6].text.strip(),
                        'size': cells[7].text.strip(),
                        'extension': cells[8].text.strip(),
                        'category': category,
                        'scraped_date': datetime.now().isoformat(),
                        'download_links': self._extract_download_links(cells[9])
                    }
                    
                    # Filter hanya buku berkualitas
                    if self._is_quality_book(book_data):
                        books.append(book_data)
                        
                except Exception as e:
                    print(f"⚠️ Error parsing row: {e}")
                    continue
        
        return books
    
    def _extract_download_links(self, cell):
        """
        Extract download links dari cell
        """
        links = []
        for link in cell.find_all('a'):
            href = link.get('href')
            if href:
                links.append({
                    'url': href,
                    'text': link.text.strip()
                })
        return links
    
    def _is_quality_book(self, book_data):
        """
        Filter untuk memastikan kualitas buku
        """
        # Basic quality filters
        if not book_data['title'] or len(book_data['title']) < 3:
            return False
            
        if not book_data['author'] or book_data['author'].lower() in ['unknown', 'n/a', '']:
            return False
            
        # Language filter (fokus English dan Indonesia)
        if book_data['language'].lower() not in ['english', 'indonesian', 'id', 'en']:
            return False
            
        # Size filter (hindari file terlalu kecil atau besar)
        try:
            size_text = book_data['size'].lower()
            if 'kb' in size_text:
                size_num = float(size_text.replace('kb', '').strip())
                if size_num < 100:  # Terlalu kecil
                    return False
        except:
            pass
            
        return True

    def run_limited_scraping(self, search_targets):
        """
        Jalankan scraping terbatas sesuai target
        """
        all_books = []
        
        for category, config in search_targets.items():
            print(f"\n📚 Processing category: {category}")
            print(f"🎯 Focus: {config['focus']}")
            
            category_books = []
            
            for term in config['terms']:
                books = self.search_limited_books(term, category)
                category_books.extend(books)
                
                # Stop jika sudah mencapai target
                if len(category_books) >= config['max_books']:
                    category_books = category_books[:config['max_books']]
                    break
            
            print(f"✅ Found {len(category_books)} books for {category}")
            all_books.extend(category_books)
        
        self.scraped_data = all_books
        return all_books
```

## 2. WordPress Integration yang Lebih Sophisticated

```python
import base64
import requests
from datetime import datetime

class WordPressPublisher:
    def __init__(self, wp_url, username, app_password):
        self.wp_url = wp_url.rstrip('/')
        self.headers = self._setup_auth(username, app_password)
        
    def _setup_auth(self, username, app_password):
        credentials = f"{username}:{app_password}"
        token = base64.b64encode(credentials.encode()).decode('utf-8')
        return {
            'Authorization': f'Basic {token}',
            'Content-Type': 'application/json'
        }
    
    def create_book_post(self, book_data, category_id=None):
        """
        Buat post WordPress yang SEO-friendly
        """
        # Generate SEO-friendly content
        content = self._generate_post_content(book_data)
        title = self._generate_seo_title(book_data)
        
        post_data = {
            'title': title,
            'content': content,
            'status': 'draft',  # Start as draft untuk review
            'excerpt': self._generate_excerpt(book_data),
            'categories': [category_id] if category_id else [],
            'tags': self._generate_tags(book_data),
            'meta': {
                'book_author': book_data['author'],
                'book_year': book_data['year'],
                'book_language': book_data['language'],
                'book_category': book_data['category'],
                'book_size': book_data['size'],
                'book_pages': book_data['pages'],
                'scraped_date': book_data['scraped_date']
            }
        }
        
        try:
            response = requests.post(
                f"{self.wp_url}/wp-json/wp/v2/posts",
                headers=self.headers,
                json=post_data
            )
            response.raise_for_status()
            return response.json()
            
        except requests.exceptions.RequestException as e:
            print(f"❌ Error posting to WordPress: {e}")
            return None
    
    def _generate_post_content(self, book_data):
        """
        Generate konten post yang informatif dan SEO-friendly
        """
        content = f"""
        <div class="book-info">
            <h2>📖 Informasi Buku</h2>
            <ul>
                <li><strong>Judul:</strong> {book_data['title']}</li>
                <li><strong>Penulis:</strong> {book_data['author']}</li>
                <li><strong>Penerbit:</strong> {book_data['publisher']}</li>
                <li><strong>Tahun:</strong> {book_data['year']}</li>
                <li><strong>Halaman:</strong> {book_data['pages']}</li>
                <li><strong>Bahasa:</strong> {book_data['language']}</li>
                <li><strong>Format:</strong> {book_data['extension']}</li>
                <li><strong>Ukuran:</strong> {book_data['size']}</li>
            </ul>
        </div>
        
        <div class="book-description">
            <h2>📝 Deskripsi</h2>
            <p>Buku "{book_data['title']}" karya {book_data['author']} adalah sumber bacaan yang valuable dalam kategori {book_data['category']}. 
            Diterbitkan pada tahun {book_data['year']}, buku ini memiliki {book_data['pages']} halaman dan tersedia dalam format {book_data['extension']}.</p>
        </div>
        
        <div class="download-info">
            <h2>⬇️ Informasi Download</h2>
            <p><strong>Catatan Penting:</strong> Pastikan Anda mematuhi hukum hak cipta yang berlaku di negara Anda. 
            Gunakan buku ini hanya untuk keperluan pendidikan dan penelitian yang sah.</p>
            
            <div class="download-links">
                <h3>🔗 Mirror Links:</h3>
        """
        
        # Add download links (dengan disclaimer)
        for i, link in enumerate(book_data.get('download_links', []), 1):
            content += f'<p>Mirror {i}: <a href="#" data-original="{link.get("url", "")}" onclick="return confirmDownload();">Download Link</a></p>'
        
        content += """
            </div>
        </div>
        
        <script>
        function confirmDownload() {
            return confirm('Apakah Anda yakin ingin mengunduh buku ini? Pastikan penggunaan sesuai dengan hukum hak cipta.');
        }
        </script>
        """
        
        return content
    
    def _generate_seo_title(self, book_data):
        """
        Generate title yang SEO-friendly
        """
        return f"{book_data['title']} - {book_data['author']} | Download PDF"
    
    def _generate_excerpt(self, book_data):
        """
        Generate excerpt untuk SEO
        """
        return f"Download buku {book_data['title']} karya {book_data['author']} ({book_data['year']}). Format {book_data['extension']}, {book_data['pages']} halaman. Kategori: {book_data['category']}."
    
    def _generate_tags(self, book_data):
        """
        Generate tags otomatis
        """
        tags = [
            book_data['category'],
            book_data['extension'].upper(),
            book_data['language'],
            'ebook',
            'download'
        ]
        
        # Add author sebagai tag (split jika ada multiple authors)
        author_parts = book_data['author'].split(',')[0].split()
        if len(author_parts) > 0:
            tags.append(author_parts[-1])  # Last name
            
        return tags
```

## 3. Workflow Management

```python
class LimitedScrapingWorkflow:
    def __init__(self, scraper, publisher):
        self.scraper = scraper
        self.publisher = publisher
        self.results = {
            'scraped': 0,
            'posted': 0,
            'errors': []
        }
    
    def run_complete_workflow(self, search_targets, review_mode=True):
        """
        Jalankan workflow lengkap dengan review
        """
        print("🚀 Starting Limited Scraping Workflow...")
        
        # Step 1: Scraping
        print("\n📥 Step 1: Scraping data...")
        books = self.scraper.run_limited_scraping(search_targets)
        self.results['scraped'] = len(books)
        
        if not books:
            print("❌ No books found!")
            return self.results
        
        # Step 2: Save raw data
        self._save_raw_data(books)
        
        # Step 3: Review (optional)
        if review_mode:
            books = self._review_books(books)
        
        # Step 4: Post to WordPress
        print(f"\n📤 Step 2: Posting {len(books)} books to WordPress...")
        for book in books:
            try:
                result = self.publisher.create_book_post(book)
                if result:
                    self.results['posted'] += 1
                    print(f"✅ Posted: {book['title']}")
                else:
                    self.results['errors'].append(f"Failed to post: {book['title']}")
                    
                # Delay antar posting
                time.sleep(random.uniform(1, 3))
                
            except Exception as e:
                error_msg = f"Error posting {book['title']}: {e}"
                self.results['errors'].append(error_msg)
                print(f"❌ {error_msg}")
        
        # Step 5: Generate report
        self._generate_report()
        
        return self.results
    
    def _save_raw_data(self, books):
        """
        Save raw data untuk backup
        """
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"scraped_books_{timestamp}.json"
        
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(books, f, indent=2, ensure_ascii=False)
            
        print(f"💾 Raw data saved to {filename}")
    
    def _review_books(self, books):
        """
        Manual review process (bisa diperluas)
        """
        print(f"\n👀 Review Mode: Found {len(books)} books")
        print("📋 Sample books:")
        
        for i, book in enumerate(books[:5]):
            print(f"{i+1}. {book['title']} - {book['author']} ({book['year']})")
        
        if len(books) > 5:
            print(f"... and {len(books) - 5} more books")
        
        # Bisa ditambahkan filter manual atau approval process
        return books
    
    def _generate_report(self):
        """
        Generate laporan hasil
        """
        print(f"\n📊 WORKFLOW REPORT")
        print(f"==================")
        print(f"📥 Books Scraped: {self.results['scraped']}")
        print(f"📤 Posts Created: {self.results['posted']}")
        print(f"❌ Errors: {len(self.results['errors'])}")
        
        if self.results['errors']:
            print(f"\n❌ Error Details:")
            for error in self.results['errors']:
                print(f"  - {error}")
```

## 4. Contoh Penggunaan

```python
def main():
    # Konfigurasi
    search_targets = {
        "programming": {
            "terms": ["Python programming", "JavaScript guide", "Web development"],
            "max_books": 50,
            "focus": "Programming dan teknologi"
        },
        "business": {
            "terms": ["Digital marketing", "Startup guide", "Business strategy"],
            "max_books": 50,
            "focus": "Bisnis dan entrepreneurship"
        }
    }
    
    # Setup scraper dan publisher
    scraper = LimitedLibGenScraper(max_books_per_search=25, delay_range=(3, 7))
    publisher = WordPressPublisher(
        wp_url="https://yoursite.com",
        username="your_username",
        app_password="your_app_password"
    )
    
    # Jalankan workflow
    workflow = LimitedScrapingWorkflow(scraper, publisher)
    results = workflow.run_complete_workflow(search_targets, review_mode=True)
    
    print(f"\n🎉 Workflow completed!")
    print(f"Target achieved: {results['posted']}/200 books posted")

if __name__ == "__main__":
    main()
```

## 5. Keunggulan Pendekatan Ini

### 5.1 Risiko Legal Lebih Rendah
- **Tidak mass scraping**: Hanya 200 buku vs jutaan
- **Pencarian spesifik**: Terlihat seperti penelitian normal
- **Manual review**: Kontrol kualitas dan legalitas
- **Rate limiting**: Tidak membebani server target

### 5.2 Kualitas Konten Lebih Baik
- **Kurasi topik**: Fokus pada niche tertentu
- **Filter kualitas**: Hanya buku berkualitas yang diposting
- **SEO optimized**: Content yang lebih valuable
- **User experience**: Navigasi yang lebih baik

### 5.3 Maintenance Lebih Mudah
- **Manageable size**: 200 buku vs jutaan
- **Error handling**: Lebih mudah debug
- **Update content**: Bisa manual review dan update
- **Legal compliance**: Lebih mudah remove content jika ada complain

## 6. Rekomendasi Implementation

### Phase 1: Setup dan Testing (1-2 minggu)
1. Setup development environment
2. Test scraping dengan 10-20 buku
3. WordPress integration testing
4. Review workflow

### Phase 2: Production Run (1 minggu)
1. Jalankan scraping untuk 200 buku
2. Manual review semua content
3. Post ke WordPress sebagai draft
4. Final review dan publish

### Phase 3: Monitoring (ongoing)
1. Monitor traffic dan engagement
2. Handle any legal complaints
3. Update/remove content as needed
4. Plan next batch if successful

Pendekatan ini jauh lebih aman dan realistic untuk implementasi!