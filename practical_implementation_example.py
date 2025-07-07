#!/usr/bin/env python3
"""
Practical LibGen Metadata Scraper - Safe Implementation
Focus: Metadata-only approach untuk minimize legal risk
Target: 200 books dengan rich metadata untuk WordPress
"""

import requests
from bs4 import BeautifulSoup
import sqlite3
import json
import time
import random
from datetime import datetime
from urllib.parse import urljoin, quote

class SafeLibGenMetadataScraper:
    def __init__(self, db_path="books_metadata.db"):
        self.base_urls = [
            "http://libgen.is",
            "http://libgen.rs", 
            "http://libgen.st"
        ]
        self.current_url = self.base_urls[0]
        self.db_path = db_path
        self.setup_database()
        
        # Headers untuk avoid blocking
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
        }
        
    def setup_database(self):
        """Setup SQLite database untuk store metadata"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS books (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                libgen_id TEXT UNIQUE,
                title TEXT NOT NULL,
                author TEXT,
                year INTEGER,
                publisher TEXT,
                isbn TEXT,
                language TEXT,
                pages INTEGER,
                file_format TEXT,
                file_size_mb REAL,
                category TEXT,
                subjects TEXT,
                description TEXT,
                cover_image_url TEXT,
                mirror_links TEXT,
                legal_sources TEXT,
                scraped_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                posted_to_wp BOOLEAN DEFAULT FALSE,
                post_id INTEGER,
                view_count INTEGER DEFAULT 0
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS search_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                search_term TEXT,
                results_found INTEGER,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        conn.commit()
        conn.close()
        print("✅ Database setup complete")
    
    def search_books_metadata(self, search_term, max_results=50):
        """
        Search LibGen dan extract metadata only (no file downloads)
        """
        print(f"🔍 Searching for: '{search_term}'")
        
        search_url = f"{self.current_url}/search.php"
        params = {
            'req': search_term,
            'lg_topic': 'libgen',
            'open': '0',
            'view': 'simple',
            'res': '25',
            'phrase': '1',
            'column': 'def'
        }
        
        try:
            response = requests.get(search_url, params=params, headers=self.headers, timeout=10)
            response.raise_for_status()
            
            soup = BeautifulSoup(response.content, 'html.parser')
            books_metadata = self.extract_book_metadata(soup, max_results)
            
            # Log search
            self.log_search(search_term, len(books_metadata))
            
            return books_metadata
            
        except Exception as e:
            print(f"❌ Search failed: {e}")
            return []
    
    def extract_book_metadata(self, soup, max_results):
        """Extract book metadata from search results"""
        books = []
        
        # Find the results table
        table = soup.find('table', {'class': 'c'})
        if not table:
            print("❌ No results table found")
            return books
        
        rows = table.find_all('tr')[1:]  # Skip header row
        
        for i, row in enumerate(rows[:max_results]):
            if i >= max_results:
                break
                
            try:
                cells = row.find_all('td')
                if len(cells) < 10:
                    continue
                
                # Extract basic info (sesuai dengan structure LibGen)
                book_data = {
                    'libgen_id': cells[0].get_text(strip=True),
                    'title': cells[2].get_text(strip=True),
                    'author': cells[1].get_text(strip=True),
                    'year': self.safe_int(cells[4].get_text(strip=True)),
                    'pages': self.safe_int(cells[5].get_text(strip=True)),
                    'language': cells[6].get_text(strip=True),
                    'file_size': self.parse_file_size(cells[7].get_text(strip=True)),
                    'file_format': cells[8].get_text(strip=True),
                    'category': cells[9].get_text(strip=True) if len(cells) > 9 else None
                }
                
                # Get additional metadata dari detail page
                detail_link = cells[2].find('a')
                if detail_link:
                    detail_url = urljoin(self.current_url, detail_link.get('href'))
                    enhanced_data = self.get_enhanced_metadata(detail_url)
                    book_data.update(enhanced_data)
                
                # Add legal alternatives
                book_data['legal_sources'] = self.generate_legal_sources(book_data)
                
                books.append(book_data)
                print(f"📚 Extracted: {book_data['title'][:50]}...")
                
                # Rate limiting
                time.sleep(random.uniform(1, 3))
                
            except Exception as e:
                print(f"⚠️ Error processing row {i}: {e}")
                continue
        
        return books
    
    def get_enhanced_metadata(self, detail_url):
        """Get enhanced metadata from book detail page"""
        enhanced_data = {}
        
        try:
            response = requests.get(detail_url, headers=self.headers, timeout=10)
            response.raise_for_status()
            
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Extract description
            desc_elem = soup.find('textarea', {'name': 'descr'})
            if desc_elem:
                enhanced_data['description'] = desc_elem.get_text(strip=True)
            
            # Extract ISBN
            isbn_elem = soup.find('input', {'name': 'identifier'})
            if isbn_elem:
                enhanced_data['isbn'] = isbn_elem.get('value', '')
            
            # Extract publisher
            publisher_elem = soup.find('input', {'name': 'publisher'})
            if publisher_elem:
                enhanced_data['publisher'] = publisher_elem.get('value', '')
            
            # Extract cover image URL (if available)
            img_elem = soup.find('img', src=True)
            if img_elem and 'covers' in img_elem.get('src', ''):
                enhanced_data['cover_image_url'] = urljoin(self.current_url, img_elem['src'])
            
            # Extract mirror download links (metadata only, not for downloading)
            mirror_links = []
            download_links = soup.find_all('a', href=True)
            for link in download_links:
                href = link.get('href', '')
                if 'download' in href or 'get.php' in href:
                    mirror_links.append(urljoin(self.current_url, href))
            
            enhanced_data['mirror_links'] = json.dumps(mirror_links)
            
        except Exception as e:
            print(f"⚠️ Enhanced metadata failed: {e}")
        
        return enhanced_data
    
    def generate_legal_sources(self, book_data):
        """Generate links to legal sources untuk book"""
        legal_sources = []
        
        title = book_data.get('title', '')
        author = book_data.get('author', '')
        isbn = book_data.get('isbn', '')
        
        if title:
            # Google Books
            google_query = quote(f"{title} {author}")
            legal_sources.append({
                'name': 'Google Books',
                'url': f'https://books.google.com/books?q={google_query}',
                'type': 'preview'
            })
            
            # Internet Archive
            ia_query = quote(f"{title} {author}")
            legal_sources.append({
                'name': 'Internet Archive',
                'url': f'https://archive.org/search.php?query={ia_query}',
                'type': 'free_access'
            })
            
            # WorldCat
            worldcat_query = quote(title)
            legal_sources.append({
                'name': 'WorldCat',
                'url': f'https://worldcat.org/search?q={worldcat_query}',
                'type': 'library_search'
            })
            
            # Amazon
            amazon_query = quote(f"{title} {author}")
            legal_sources.append({
                'name': 'Amazon',
                'url': f'https://amazon.com/s?k={amazon_query}&i=stripbooks',
                'type': 'purchase'
            })
        
        return json.dumps(legal_sources)
    
    def save_to_database(self, books_data):
        """Save book metadata to database"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        saved_count = 0
        
        for book in books_data:
            try:
                cursor.execute('''
                    INSERT OR REPLACE INTO books 
                    (libgen_id, title, author, year, publisher, isbn, language, pages, 
                     file_format, file_size_mb, category, description, cover_image_url, 
                     mirror_links, legal_sources)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ''', (
                    book.get('libgen_id'),
                    book.get('title'),
                    book.get('author'),
                    book.get('year'),
                    book.get('publisher'),
                    book.get('isbn'),
                    book.get('language'),
                    book.get('pages'),
                    book.get('file_format'),
                    book.get('file_size'),
                    book.get('category'),
                    book.get('description'),
                    book.get('cover_image_url'),
                    book.get('mirror_links'),
                    book.get('legal_sources')
                ))
                saved_count += 1
                
            except Exception as e:
                print(f"❌ Failed to save book: {e}")
        
        conn.commit()
        conn.close()
        
        print(f"✅ Saved {saved_count} books to database")
        return saved_count
    
    def log_search(self, search_term, results_found):
        """Log search activity"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO search_logs (search_term, results_found)
            VALUES (?, ?)
        ''', (search_term, results_found))
        
        conn.commit()
        conn.close()
    
    def get_books_for_wordpress(self, limit=10, posted=False):
        """Get books for WordPress posting"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT * FROM books 
            WHERE posted_to_wp = ? 
            ORDER BY scraped_date DESC 
            LIMIT ?
        ''', (posted, limit))
        
        columns = [desc[0] for desc in cursor.description]
        books = []
        
        for row in cursor.fetchall():
            book = dict(zip(columns, row))
            # Parse JSON fields
            if book['mirror_links']:
                book['mirror_links'] = json.loads(book['mirror_links'])
            if book['legal_sources']:
                book['legal_sources'] = json.loads(book['legal_sources'])
            books.append(book)
        
        conn.close()
        return books
    
    def mark_as_posted(self, book_id, post_id):
        """Mark book as posted to WordPress"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE books 
            SET posted_to_wp = TRUE, post_id = ?
            WHERE id = ?
        ''', (post_id, book_id))
        
        conn.commit()
        conn.close()
    
    @staticmethod
    def safe_int(value):
        """Safely convert to integer"""
        try:
            return int(value)
        except:
            return None
    
    @staticmethod
    def parse_file_size(size_str):
        """Parse file size string to MB"""
        try:
            size_str = size_str.lower().strip()
            if 'mb' in size_str:
                return float(size_str.replace('mb', '').strip())
            elif 'kb' in size_str:
                return float(size_str.replace('kb', '').strip()) / 1024
            elif 'gb' in size_str:
                return float(size_str.replace('gb', '').strip()) * 1024
            else:
                return None
        except:
            return None

def main():
    """Example usage"""
    scraper = SafeLibGenMetadataScraper()
    
    # Define search targets
    search_targets = [
        "Python programming",
        "Machine learning",
        "Web development",
        "Data science",
        "JavaScript tutorial"
    ]
    
    all_books = []
    
    for search_term in search_targets:
        print(f"\n🎯 Searching: {search_term}")
        books = scraper.search_books_metadata(search_term, max_results=40)
        all_books.extend(books)
        
        # Save to database
        if books:
            scraper.save_to_database(books)
        
        # Rest between searches
        print(f"😴 Waiting before next search...")
        time.sleep(random.uniform(10, 20))
    
    print(f"\n🎉 Total books collected: {len(all_books)}")
    print(f"📊 Database: {scraper.db_path}")
    
    # Show sample book data
    if all_books:
        sample_book = all_books[0]
        print(f"\n📖 Sample book data:")
        for key, value in sample_book.items():
            if value and len(str(value)) < 100:
                print(f"   {key}: {value}")

if __name__ == "__main__":
    main()