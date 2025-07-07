#!/usr/bin/env python3
"""
LibGen to WordPress Automation - Main Orchestrator
Combines scraping and publishing in a safe, controlled manner

Usage:
    python main.py --scrape                    # Only scrape metadata
    python main.py --publish                   # Only publish to WordPress  
    python main.py --scrape --publish          # Scrape then publish
    python main.py --batch                     # Run in batch mode
"""

import argparse
import os
import sys
import time
from dotenv import load_dotenv
import random

# Load our custom modules
from practical_implementation_example import SafeLibGenMetadataScraper
from wordpress_publisher import WordPressPoster

# Load environment variables
load_dotenv()

class LibGenToWordPressOrchestrator:
    def __init__(self):
        """Initialize the orchestrator with configuration from environment"""
        
        # WordPress configuration
        self.wp_url = os.getenv('WP_URL')
        self.wp_username = os.getenv('WP_USERNAME') 
        self.wp_app_password = os.getenv('WP_APP_PASSWORD')
        
        # Database configuration
        self.db_path = os.getenv('DB_PATH', 'books_metadata.db')
        
        # Scraping configuration
        self.max_books_per_search = int(os.getenv('MAX_BOOKS_PER_SEARCH', 40))
        self.search_delay_min = int(os.getenv('SEARCH_DELAY_MIN', 10))
        self.search_delay_max = int(os.getenv('SEARCH_DELAY_MAX', 20))
        self.post_delay = int(os.getenv('POST_DELAY_SECONDS', 60))
        
        # Validate configuration
        self.validate_config()
        
        # Initialize components
        self.scraper = None
        self.publisher = None
    
    def validate_config(self):
        """Validate that all required configuration is present"""
        required_fields = {
            'WP_URL': self.wp_url,
            'WP_USERNAME': self.wp_username,
            'WP_APP_PASSWORD': self.wp_app_password
        }
        
        missing_fields = [field for field, value in required_fields.items() if not value]
        
        if missing_fields:
            print("❌ Configuration Error: Missing required environment variables:")
            for field in missing_fields:
                print(f"   - {field}")
            print("\n💡 Please check your .env file or set these environment variables.")
            print("   Copy .env.example to .env and update the values.")
            sys.exit(1)
    
    def initialize_scraper(self):
        """Initialize the metadata scraper"""
        if not self.scraper:
            print("🔧 Initializing LibGen metadata scraper...")
            self.scraper = SafeLibGenMetadataScraper(self.db_path)
        return self.scraper
    
    def initialize_publisher(self):
        """Initialize the WordPress publisher"""
        if not self.publisher:
            print("🔧 Initializing WordPress publisher...")
            try:
                self.publisher = WordPressPoster(
                    wp_url=self.wp_url,
                    username=self.wp_username,
                    app_password=self.wp_app_password,
                    db_path=self.db_path
                )
            except Exception as e:
                print(f"❌ Failed to initialize WordPress publisher: {e}")
                return None
        return self.publisher
    
    def scrape_metadata(self, search_terms=None):
        """Scrape metadata from LibGen"""
        if not search_terms:
            # Default search terms for diverse content
            search_terms = [
                "Python programming",
                "Machine learning", 
                "Web development",
                "Data science",
                "JavaScript tutorial",
                "React programming",
                "Node.js development",
                "Database design"
            ]
        
        scraper = self.initialize_scraper()
        if not scraper:
            return False
        
        print(f"🚀 Starting metadata scraping for {len(search_terms)} search terms...")
        all_books = []
        
        for i, search_term in enumerate(search_terms):
            print(f"\n🎯 Scraping {i+1}/{len(search_terms)}: '{search_term}'")
            
            books = scraper.search_books_metadata(search_term, self.max_books_per_search)
            
            if books:
                saved_count = scraper.save_to_database(books)
                all_books.extend(books)
                print(f"✅ Saved {saved_count} books from '{search_term}'")
            else:
                print(f"⚠️ No books found for '{search_term}'")
            
            # Delay between searches
            if i < len(search_terms) - 1:
                delay = random.randint(self.search_delay_min, self.search_delay_max)
                print(f"⏳ Waiting {delay} seconds before next search...")
                time.sleep(delay)
        
        print(f"\n🎉 Scraping complete! Total books collected: {len(all_books)}")
        return len(all_books) > 0
    
    def publish_to_wordpress(self, max_posts=5):
        """Publish scraped metadata to WordPress"""
        publisher = self.initialize_publisher()
        if not publisher:
            return False
        
        print(f"📝 Publishing up to {max_posts} books to WordPress...")
        
        results = publisher.bulk_publish(
            max_posts=max_posts, 
            delay_between_posts=self.post_delay
        )
        
        successful = sum(1 for r in results if r.get('success'))
        
        if successful > 0:
            print(f"\n✅ Successfully published {successful} books!")
            return True
        else:
            print(f"\n❌ No books were published successfully")
            return False
    
    def run_batch_mode(self):
        """Run in automated batch mode: scrape then publish"""
        print("🚀 Running in batch mode: scrape → publish")
        
        # Step 1: Scrape metadata
        scrape_success = self.scrape_metadata()
        
        if not scrape_success:
            print("❌ Scraping failed, aborting batch mode")
            return False
        
        # Wait between scraping and publishing
        print("⏳ Waiting 30 seconds between scraping and publishing...")
        time.sleep(30)
        
        # Step 2: Publish to WordPress
        publish_success = self.publish_to_wordpress(max_posts=3)
        
        return scrape_success and publish_success
    
    def show_status(self):
        """Show current database status"""
        import sqlite3
        
        try:
            conn = sqlite3.connect(self.db_path)
            cursor = conn.cursor()
            
            # Total books
            cursor.execute('SELECT COUNT(*) FROM books')
            total_books = cursor.fetchone()[0]
            
            # Published books
            cursor.execute('SELECT COUNT(*) FROM books WHERE posted_to_wp = TRUE')
            published_books = cursor.fetchone()[0]
            
            # Unpublished books  
            unpublished_books = total_books - published_books
            
            # Recent searches
            cursor.execute('SELECT COUNT(*) FROM search_logs WHERE timestamp > datetime("now", "-24 hours")')
            recent_searches = cursor.fetchone()[0]
            
            conn.close()
            
            print(f"\n📊 Current Status:")
            print(f"   📚 Total books in database: {total_books}")
            print(f"   ✅ Published to WordPress: {published_books}")
            print(f"   ⏳ Awaiting publication: {unpublished_books}")
            print(f"   🔍 Searches in last 24h: {recent_searches}")
            
        except Exception as e:
            print(f"❌ Error checking status: {e}")

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='LibGen to WordPress Automation Tool',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python main.py --scrape                    # Only scrape metadata
  python main.py --publish                   # Only publish to WordPress
  python main.py --scrape --publish          # Scrape then publish  
  python main.py --batch                     # Automated batch mode
  python main.py --status                    # Show current status
        """
    )
    
    parser.add_argument('--scrape', action='store_true', help='Scrape metadata from LibGen')
    parser.add_argument('--publish', action='store_true', help='Publish to WordPress')
    parser.add_argument('--batch', action='store_true', help='Run in batch mode (scrape + publish)')
    parser.add_argument('--status', action='store_true', help='Show current database status')
    parser.add_argument('--max-posts', type=int, default=5, help='Maximum posts to publish (default: 5)')
    parser.add_argument('--search-terms', nargs='+', help='Custom search terms for scraping')
    
    args = parser.parse_args()
    
    # Show help if no arguments
    if not any([args.scrape, args.publish, args.batch, args.status]):
        parser.print_help()
        return
    
    # Initialize orchestrator
    orchestrator = LibGenToWordPressOrchestrator()
    
    try:
        # Show status
        if args.status:
            orchestrator.show_status()
            return
        
        # Batch mode
        if args.batch:
            success = orchestrator.run_batch_mode()
            sys.exit(0 if success else 1)
        
        # Individual operations
        success = True
        
        if args.scrape:
            success &= orchestrator.scrape_metadata(args.search_terms)
        
        if args.publish:
            success &= orchestrator.publish_to_wordpress(args.max_posts)
        
        # Show final status
        orchestrator.show_status()
        
        sys.exit(0 if success else 1)
        
    except KeyboardInterrupt:
        print("\n⏹️ Operation cancelled by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ Unexpected error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()