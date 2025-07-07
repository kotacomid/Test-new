#!/usr/bin/env python3
"""
Free Tier LibGen Scraper - Quick Prototype
Scrapes limited books from LibGen with conservative rate limiting
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
        """
        Scrape books with free tier optimizations
        """
        print(f"🔍 Searching for: '{search_term}' (Max: {max_books} books)")
        
        # Conservative delay to avoid blocking
        delay = random.uniform(3, 6)
        print(f"⏳ Waiting {delay:.1f} seconds before request...")
        time.sleep(delay)
        
        url = "http://libgen.is/search.php"
        params = {
            'req': search_term,
            'res': 25,  # LibGen default
            'view': 'simple',
            'phrase': 1,
            'column': 'def'
        }
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
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
        
        # Find the results table (usually the 3rd table)
        tables = soup.find_all('table')
        if len(tables) < 3:
            print("⚠️ No results table found")
            return books
            
        results_table = tables[2]
        rows = results_table.find_all('tr')[1:]  # Skip header row
        
        for i, row in enumerate(rows):
            if i >= max_books:
                break
                
            try:
                cells = row.find_all('td')
                if len(cells) < 9:
                    continue
                
                # Extract book information
                title = cells[2].get_text(strip=True)
                author = cells[1].get_text(strip=True)
                year = cells[4].get_text(strip=True)
                
                # Extract download links
                download_links = []
                if len(cells) > 9:
                    for link in cells[9].find_all('a'):
                        href = link.get('href')
                        if href and ('library.lol' in href or 'libgen' in href):
                            download_links.append(href)
                
                # Basic quality filters
                if (len(title) > 3 and 
                    author not in ['', 'Unknown', 'N/A'] and
                    len(title) < 200):  # Avoid super long titles
                    
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
                # Check if book already exists
                cursor.execute('''
                    SELECT COUNT(*) FROM books 
                    WHERE title = ? AND author = ?
                ''', (book['title'], book['author']))
                
                if cursor.fetchone()[0] == 0:
                    # Insert new book
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
    
    # Show current stats
    stats = scraper.get_stats()
    print(f"\n📊 Current Stats:")
    print(f"   Total books: {stats['total_books']}")
    print(f"   Posted to WP: {stats['posted_books']}")
    print(f"   Pending: {stats['pending_books']}")
    print(f"   Categories: {', '.join(stats['categories']) if stats['categories'] else 'None'}")
    
    # Quick test scraping
    print(f"\n🧪 Quick test: Scraping 5 Python books...")
    books = scraper.scrape_books("Python programming", "programming", max_books=5)
    
    if books:
        print(f"\n✅ Success! Scraped {len(books)} books")
        scraper.show_recent_books(5)
    else:
        print(f"\n❌ No books found or error occurred")

if __name__ == "__main__":
    main()