#!/usr/bin/env python3
"""
WordPress Publisher for LibGen Metadata
Automatically creates rich WordPress posts from scraped book metadata
"""

import requests
import json
import base64
from datetime import datetime
import sqlite3
import time
import random
from urllib.parse import quote

class WordPressPoster:
    def __init__(self, wp_url, username, app_password, db_path="books_metadata.db"):
        """
        Initialize WordPress poster
        
        Args:
            wp_url: Your WordPress site URL (e.g., 'https://yoursite.com')
            username: WordPress username
            app_password: WordPress application password (not regular password!)
            db_path: Path to books database
        """
        self.wp_url = wp_url.rstrip('/')
        self.username = username
        self.app_password = app_password
        self.db_path = db_path
        
        # Create auth header
        credentials = f"{username}:{app_password}"
        token = base64.b64encode(credentials.encode()).decode('utf-8')
        
        self.headers = {
            'Authorization': f'Basic {token}',
            'Content-Type': 'application/json',
            'User-Agent': 'LibGen-Metadata-Publisher/1.0'
        }
        
        self.api_url = f"{self.wp_url}/wp-json/wp/v2"
        
        # Test connection
        self.test_connection()
    
    def test_connection(self):
        """Test WordPress API connection"""
        try:
            response = requests.get(f"{self.api_url}/users/me", headers=self.headers)
            if response.status_code == 200:
                user_data = response.json()
                print(f"✅ Connected to WordPress as: {user_data.get('name', 'Unknown')}")
                return True
            else:
                print(f"❌ WordPress connection failed: {response.status_code}")
                print(f"Response: {response.text}")
                return False
        except Exception as e:
            print(f"❌ WordPress connection error: {e}")
            return False
    
    def get_unpublished_books(self, limit=5):
        """Get books that haven't been posted to WordPress yet"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT * FROM books 
            WHERE posted_to_wp = FALSE 
            AND title IS NOT NULL 
            AND title != ''
            ORDER BY scraped_date DESC 
            LIMIT ?
        ''', (limit,))
        
        columns = [desc[0] for desc in cursor.description]
        books = []
        
        for row in cursor.fetchall():
            book = dict(zip(columns, row))
            # Parse JSON fields
            if book['mirror_links']:
                try:
                    book['mirror_links'] = json.loads(book['mirror_links'])
                except:
                    book['mirror_links'] = []
            if book['legal_sources']:
                try:
                    book['legal_sources'] = json.loads(book['legal_sources'])
                except:
                    book['legal_sources'] = []
            books.append(book)
        
        conn.close()
        return books
    
    def generate_post_content(self, book):
        """Generate rich HTML content for WordPress post"""
        title = book.get('title', 'Unknown Title')
        author = book.get('author', 'Unknown Author')
        year = book.get('year')
        publisher = book.get('publisher', 'Unknown Publisher')
        pages = book.get('pages')
        language = book.get('language', 'Unknown')
        file_format = book.get('file_format', 'Unknown')
        file_size = book.get('file_size_mb')
        description = book.get('description', '')
        cover_url = book.get('cover_image_url', '')
        legal_sources = book.get('legal_sources', [])
        category = book.get('category', 'General')
        
        # Build rich HTML content
        content = f"""
<div class="book-showcase" style="border: 1px solid #e1e5e9; border-radius: 8px; padding: 20px; margin: 20px 0; background: #fafbfc;">
    
    <div class="book-header" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        """
        
        # Add book cover if available
        if cover_url:
            content += f"""
        <div class="book-cover" style="flex: 0 0 150px;">
            <img src="{cover_url}" alt="Cover of {title}" style="width: 100%; max-width: 150px; border-radius: 5px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" />
        </div>
            """
        
        content += f"""
        <div class="book-details" style="flex: 1; min-width: 300px;">
            <h3 style="margin-top: 0; color: #1e73be;">📚 Book Information</h3>
            <div class="detail-grid" style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; font-size: 14px;">
                <strong>👤 Author:</strong> <span>{author}</span>
                <strong>📅 Year:</strong> <span>{year if year else 'N/A'}</span>
                <strong>🏢 Publisher:</strong> <span>{publisher}</span>
                <strong>📖 Pages:</strong> <span>{pages if pages else 'N/A'}</span>
                <strong>🗣️ Language:</strong> <span>{language}</span>
                <strong>📄 Format:</strong> <span>{file_format}</span>
                <strong>💾 Size:</strong> <span>{f"{file_size:.1f} MB" if file_size else "N/A"}</span>
                <strong>🏷️ Category:</strong> <span>{category}</span>
            </div>
        </div>
    </div>
        """
        
        # Add description if available
        if description and len(description.strip()) > 0:
            content += f"""
    <div class="book-description" style="margin: 20px 0;">
        <h3 style="color: #1e73be;">📝 Description</h3>
        <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #1e73be;">
            <p style="margin: 0; line-height: 1.6;">{description[:500]}{'...' if len(description) > 500 else ''}</p>
        </div>
    </div>
            """
        
        # Add legal sources
        if legal_sources:
            content += f"""
    <div class="legal-sources" style="margin: 20px 0;">
        <h3 style="color: #1e73be;">🔗 Find This Book Legally</h3>
        <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; border: 1px solid #4caf50;">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #2e7d2e;">Support authors by purchasing from legal sources:</p>
            <ul style="margin: 0; padding-left: 20px;">
            """
            
            for source in legal_sources:
                icon_map = {
                    'preview': '🔍',
                    'free_access': '📚',
                    'library_search': '🏛️',
                    'purchase': '🛒'
                }
                icon = icon_map.get(source.get('type', ''), '🔗')
                content += f'<li><a href="{source["url"]}" target="_blank" rel="noopener">{icon} {source["name"]}</a></li>'
            
            content += """
            </ul>
        </div>
    </div>
            """
        
        # Add important disclaimer
        content += f"""
    <div class="disclaimer" style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
        <h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ Important Disclaimer</h4>
        <p style="margin: 0; font-size: 13px; color: #856404;">
            This information is provided for educational and research purposes only. 
            We strongly encourage supporting authors and publishers by purchasing books through legal channels. 
            Respect copyright laws and intellectual property rights.
        </p>
    </div>

    <div class="metadata-source" style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
        <p style="margin: 0;">📊 Metadata sourced on {datetime.now().strftime('%Y-%m-%d')} | 
        For educational and informational purposes only</p>
    </div>

</div>
        """
        
        return content.strip()
    
    def generate_post_title(self, book):
        """Generate SEO-friendly post title"""
        title = book.get('title', 'Unknown Title')
        author = book.get('author', 'Unknown Author')
        year = book.get('year')
        
        # Clean title
        title = title.strip()
        if len(title) > 80:
            title = title[:77] + "..."
        
        # Create SEO-friendly title
        if year:
            post_title = f"{title} by {author} ({year}) - Book Information & Legal Sources"
        else:
            post_title = f"{title} by {author} - Book Information & Legal Sources"
        
        return post_title
    
    def generate_post_tags(self, book):
        """Generate relevant tags for the post"""
        tags = []
        
        # Add category-based tags
        category = book.get('category', '').lower()
        if 'computer' in category or 'programming' in category:
            tags.extend(['programming', 'computer science', 'technology'])
        elif 'science' in category:
            tags.extend(['science', 'research', 'academic'])
        elif 'business' in category:
            tags.extend(['business', 'management', 'entrepreneurship'])
        elif 'math' in category:
            tags.extend(['mathematics', 'math', 'academic'])
        
        # Add format-based tags
        file_format = book.get('file_format', '').lower()
        if file_format:
            tags.append(f"{file_format} book")
        
        # Add language tag
        language = book.get('language', '')
        if language and language.lower() != 'english':
            tags.append(f"{language} language")
        
        # Add author tag
        author = book.get('author', '')
        if author and len(author) < 30:
            tags.append(author)
        
        # Add year-based tag
        year = book.get('year')
        if year and year > 1900:
            if year >= 2020:
                tags.append('recent books')
            elif year >= 2010:
                tags.append('modern books')
            else:
                tags.append('classic books')
        
        # Add generic tags
        tags.extend(['book information', 'book metadata', 'reading recommendations'])
        
        return list(set(tags))  # Remove duplicates
    
    def create_wordpress_post(self, book):
        """Create a WordPress post from book metadata"""
        try:
            title = self.generate_post_title(book)
            content = self.generate_post_content(book)
            tags = self.generate_post_tags(book)
            
            # Prepare post data
            post_data = {
                'title': title,
                'content': content,
                'status': 'publish',  # or 'draft' for review first
                'author': 1,  # Usually the admin user
                'excerpt': f"Detailed information about '{book.get('title', 'Unknown')}' by {book.get('author', 'Unknown Author')}. Find legal sources to purchase this book.",
                'meta': {
                    'book_title': book.get('title', ''),
                    'book_author': book.get('author', ''),
                    'book_year': book.get('year'),
                    'book_isbn': book.get('isbn', ''),
                    'book_publisher': book.get('publisher', ''),
                    'book_pages': book.get('pages'),
                    'book_language': book.get('language', ''),
                    'book_format': book.get('file_format', ''),
                    'book_size_mb': book.get('file_size_mb'),
                    'book_category': book.get('category', ''),
                    'scraped_date': book.get('scraped_date', ''),
                    'libgen_id': book.get('libgen_id', '')
                }
            }
            
            # Add tags if any
            if tags:
                # First, get or create tags
                tag_ids = self.get_or_create_tags(tags)
                if tag_ids:
                    post_data['tags'] = tag_ids
            
            # Create the post
            response = requests.post(
                f"{self.api_url}/posts",
                headers=self.headers,
                json=post_data
            )
            
            if response.status_code == 201:
                post_data = response.json()
                post_id = post_data['id']
                post_url = post_data['link']
                
                print(f"✅ Published: {title[:50]}...")
                print(f"   📝 Post ID: {post_id}")
                print(f"   🔗 URL: {post_url}")
                
                # Mark as posted in database
                self.mark_book_as_posted(book['id'], post_id)
                
                return {
                    'success': True,
                    'post_id': post_id,
                    'post_url': post_url,
                    'title': title
                }
            else:
                print(f"❌ Failed to create post: {response.status_code}")
                print(f"   Response: {response.text}")
                return {'success': False, 'error': response.text}
                
        except Exception as e:
            print(f"❌ Error creating post: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_or_create_tags(self, tag_names):
        """Get existing tags or create new ones"""
        tag_ids = []
        
        for tag_name in tag_names[:10]:  # Limit to 10 tags
            try:
                # Search for existing tag
                search_response = requests.get(
                    f"{self.api_url}/tags",
                    headers=self.headers,
                    params={'search': tag_name}
                )
                
                if search_response.status_code == 200:
                    existing_tags = search_response.json()
                    
                    # Check if exact match exists
                    exact_match = None
                    for tag in existing_tags:
                        if tag['name'].lower() == tag_name.lower():
                            exact_match = tag
                            break
                    
                    if exact_match:
                        tag_ids.append(exact_match['id'])
                    else:
                        # Create new tag
                        create_response = requests.post(
                            f"{self.api_url}/tags",
                            headers=self.headers,
                            json={'name': tag_name}
                        )
                        
                        if create_response.status_code == 201:
                            new_tag = create_response.json()
                            tag_ids.append(new_tag['id'])
                
                # Small delay to avoid rate limiting
                time.sleep(0.5)
                
            except Exception as e:
                print(f"⚠️ Error handling tag '{tag_name}': {e}")
                continue
        
        return tag_ids
    
    def mark_book_as_posted(self, book_id, post_id):
        """Mark book as posted in database"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE books 
            SET posted_to_wp = TRUE, post_id = ?
            WHERE id = ?
        ''', (post_id, book_id))
        
        conn.commit()
        conn.close()
    
    def bulk_publish(self, max_posts=5, delay_between_posts=60):
        """Publish multiple books to WordPress with delays"""
        books = self.get_unpublished_books(max_posts)
        
        if not books:
            print("📝 No unpublished books found")
            return []
        
        print(f"🚀 Publishing {len(books)} books to WordPress...")
        results = []
        
        for i, book in enumerate(books):
            print(f"\n📖 Publishing book {i+1}/{len(books)}: {book.get('title', 'Unknown')[:50]}...")
            
            result = self.create_wordpress_post(book)
            results.append(result)
            
            # Delay between posts to avoid overwhelming server
            if i < len(books) - 1:  # Don't delay after the last post
                print(f"⏳ Waiting {delay_between_posts} seconds before next post...")
                time.sleep(delay_between_posts)
        
        # Summary
        successful = sum(1 for r in results if r.get('success'))
        print(f"\n🎉 Bulk publish complete!")
        print(f"   ✅ Successful: {successful}")
        print(f"   ❌ Failed: {len(results) - successful}")
        
        return results

def main():
    """Example usage"""
    # Configuration - UPDATE THESE VALUES!
    wp_config = {
        'wp_url': 'https://yourwordpresssite.com',  # Your WordPress URL
        'username': 'your_username',               # Your WordPress username
        'app_password': 'your_app_password',       # WordPress application password
        'db_path': 'books_metadata.db'             # Database path
    }
    
    print("🌟 WordPress Publisher for LibGen Metadata")
    print("⚠️  Please update the configuration above before running!")
    
    # Uncomment and update configuration to run
    """
    try:
        # Initialize publisher
        publisher = WordPressPoster(
            wp_url=wp_config['wp_url'],
            username=wp_config['username'],
            app_password=wp_config['app_password'],
            db_path=wp_config['db_path']
        )
        
        # Publish books in batches
        results = publisher.bulk_publish(max_posts=3, delay_between_posts=30)
        
        # Show results
        for result in results:
            if result.get('success'):
                print(f"✅ {result['title']}")
                print(f"   🔗 {result['post_url']}")
            else:
                print(f"❌ Failed: {result.get('error', 'Unknown error')}")
                
    except Exception as e:
        print(f"❌ Error: {e}")
    """

if __name__ == "__main__":
    main()