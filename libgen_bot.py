#!/usr/bin/env python3
"""
Libgen to WordPress Bot
Scrapes book metadata from Library Genesis and posts to WordPress
"""

import os
import sys
import logging
import argparse
from dotenv import load_dotenv
from typing import List, Dict
import json
import time

from libgen_scraper import LibgenScraper
from wordpress_uploader import WordPressUploader

class LibgenBot:
    def __init__(self, config_file: str = ".env"):
        """Initialize the bot with configuration"""
        load_dotenv(config_file)
        
        # Setup logging
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler('libgen_bot.log'),
                logging.StreamHandler(sys.stdout)
            ]
        )
        
        # Initialize components
        self.scraper = LibgenScraper(
            base_url=os.getenv('LIBGEN_BASE_URL', 'http://libgen.rs'),
            delay=int(os.getenv('SEARCH_DELAY', 2))
        )
        
        self.uploader = WordPressUploader(
            wp_url=os.getenv('WP_URL'),
            username=os.getenv('WP_USERNAME'),
            password=os.getenv('WP_PASSWORD')
        )
        
        self.download_dir = os.getenv('DOWNLOAD_DIR', './downloads')
        self.max_file_size_mb = int(os.getenv('MAX_FILE_SIZE_MB', 100))
        
        # Ensure download directory exists
        os.makedirs(self.download_dir, exist_ok=True)
        
    def search_and_process_books(self, query: str, search_type: str = "title", 
                                max_results: int = 5, download_files: bool = True) -> List[Dict]:
        """Search for books and process them"""
        logging.info(f"Searching for books with query: '{query}' (type: {search_type})")
        
        # Search for books
        books = self.scraper.search_books(query, search_type)
        
        if not books:
            logging.warning("No books found")
            return []
        
        logging.info(f"Found {len(books)} books")
        
        # Process limited number of results
        processed_books = []
        for i, book in enumerate(books[:max_results]):
            logging.info(f"Processing book {i+1}/{min(len(books), max_results)}: {book['title']}")
            
            try:
                # Download file if requested
                file_path = None
                if download_files:
                    file_path = self._download_book_safely(book)
                
                # Create WordPress post
                post_id = self.uploader.create_book_post(book, file_path)
                
                if post_id:
                    book['wp_post_id'] = post_id
                    book['file_path'] = file_path
                    processed_books.append(book)
                    logging.info(f"Successfully processed: {book['title']}")
                else:
                    logging.error(f"Failed to create WordPress post for: {book['title']}")
                
                # Small delay between processing
                time.sleep(2)
                
            except Exception as e:
                logging.error(f"Error processing book '{book['title']}': {e}")
                continue
        
        return processed_books
    
    def _download_book_safely(self, book: Dict) -> str:
        """Download book with safety checks"""
        try:
            # Check file size
            size_str = book.get('size', '0')
            size_mb = self._parse_file_size(size_str)
            
            if size_mb > self.max_file_size_mb:
                logging.warning(f"Book '{book['title']}' is too large ({size_mb}MB), skipping download")
                return None
            
            # Download the book
            file_path = self.scraper.download_book(book, self.download_dir)
            
            if file_path:
                logging.info(f"Downloaded: {file_path}")
                return file_path
            else:
                logging.warning(f"Failed to download: {book['title']}")
                return None
                
        except Exception as e:
            logging.error(f"Error downloading book '{book['title']}': {e}")
            return None
    
    def _parse_file_size(self, size_str: str) -> float:
        """Parse file size string to MB"""
        try:
            size_str = size_str.lower().strip()
            
            if 'kb' in size_str:
                return float(size_str.replace('kb', '').strip()) / 1024
            elif 'mb' in size_str:
                return float(size_str.replace('mb', '').strip())
            elif 'gb' in size_str:
                return float(size_str.replace('gb', '').strip()) * 1024
            else:
                # Assume bytes
                return float(size_str) / (1024 * 1024)
        except:
            return 0
    
    def batch_process_from_file(self, queries_file: str, download_files: bool = True) -> List[Dict]:
        """Process multiple queries from a file"""
        all_processed = []
        
        try:
            with open(queries_file, 'r', encoding='utf-8') as f:
                queries = [line.strip() for line in f if line.strip()]
            
            for query in queries:
                logging.info(f"Processing query: {query}")
                processed = self.search_and_process_books(query, download_files=download_files)
                all_processed.extend(processed)
                
                # Delay between queries
                time.sleep(5)
            
            return all_processed
            
        except Exception as e:
            logging.error(f"Error processing batch file: {e}")
            return []
    
    def save_results(self, processed_books: List[Dict], output_file: str = "processed_books.json"):
        """Save processing results to JSON file"""
        try:
            # Remove non-serializable fields
            clean_books = []
            for book in processed_books:
                clean_book = book.copy()
                # Remove any non-serializable fields if needed
                clean_books.append(clean_book)
            
            with open(output_file, 'w', encoding='utf-8') as f:
                json.dump(clean_books, f, indent=2, ensure_ascii=False)
            
            logging.info(f"Results saved to {output_file}")
            
        except Exception as e:
            logging.error(f"Error saving results: {e}")
    
    def test_connections(self) -> bool:
        """Test all connections"""
        logging.info("Testing connections...")
        
        # Test WordPress connection
        wp_ok = self.uploader.test_connection()
        
        # Test libgen by doing a simple search
        libgen_ok = False
        try:
            test_results = self.scraper.search_books("python", "title")
            libgen_ok = len(test_results) > 0
            logging.info("Libgen connection successful")
        except Exception as e:
            logging.error(f"Libgen connection failed: {e}")
        
        return wp_ok and libgen_ok

def main():
    parser = argparse.ArgumentParser(description='Libgen to WordPress Bot')
    parser.add_argument('--query', '-q', type=str, help='Search query')
    parser.add_argument('--search-type', '-t', type=str, default='title', 
                       choices=['title', 'author', 'isbn', 'publisher'],
                       help='Search type')
    parser.add_argument('--max-results', '-m', type=int, default=5,
                       help='Maximum number of results to process')
    parser.add_argument('--no-download', '-n', action='store_true',
                       help='Skip file downloads')
    parser.add_argument('--batch-file', '-b', type=str,
                       help='File containing multiple search queries')
    parser.add_argument('--test', action='store_true',
                       help='Test connections only')
    parser.add_argument('--config', '-c', type=str, default='.env',
                       help='Configuration file path')
    
    args = parser.parse_args()
    
    # Initialize bot
    bot = LibgenBot(args.config)
    
    # Test connections if requested
    if args.test:
        if bot.test_connections():
            print("✅ All connections working!")
            return 0
        else:
            print("❌ Connection test failed!")
            return 1
    
    processed_books = []
    
    # Process batch file
    if args.batch_file:
        processed_books = bot.batch_process_from_file(
            args.batch_file, 
            download_files=not args.no_download
        )
    
    # Process single query
    elif args.query:
        processed_books = bot.search_and_process_books(
            args.query,
            args.search_type,
            args.max_results,
            download_files=not args.no_download
        )
    
    else:
        print("Please provide either --query or --batch-file")
        return 1
    
    # Save results
    if processed_books:
        bot.save_results(processed_books)
        print(f"✅ Processed {len(processed_books)} books successfully!")
    else:
        print("❌ No books were processed successfully")
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())