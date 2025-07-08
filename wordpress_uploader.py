from wordpress_xmlrpc import Client, WordPressPost, WordPressMedia
from wordpress_xmlrpc.methods.posts import NewPost
from wordpress_xmlrpc.methods.media import UploadFile
import os
import mimetypes
import logging
from typing import Dict, Optional

class WordPressUploader:
    def __init__(self, wp_url: str, username: str, password: str):
        """
        Initialize WordPress client
        wp_url: WordPress XML-RPC endpoint (e.g., https://yoursite.com/xmlrpc.php)
        """
        self.client = Client(wp_url, username, password)
        
    def upload_media(self, file_path: str) -> Optional[Dict]:
        """Upload media file to WordPress"""
        try:
            if not os.path.exists(file_path):
                logging.error(f"File not found: {file_path}")
                return None
            
            # Determine MIME type
            mime_type, _ = mimetypes.guess_type(file_path)
            if not mime_type:
                mime_type = 'application/octet-stream'
            
            # Read file
            with open(file_path, 'rb') as file:
                file_data = file.read()
            
            # Create media object
            media = WordPressMedia()
            media.name = os.path.basename(file_path)
            media.type = mime_type
            media.bits = file_data
            
            # Upload to WordPress
            response = self.client.call(UploadFile(media))
            
            logging.info(f"Media uploaded successfully: {response['url']}")
            return response
            
        except Exception as e:
            logging.error(f"Error uploading media: {e}")
            return None
    
    def create_book_post(self, book_metadata: Dict, file_path: str = None) -> Optional[str]:
        """Create WordPress post for a book with metadata"""
        try:
            # Upload file if provided
            media_url = None
            if file_path and os.path.exists(file_path):
                media_response = self.upload_media(file_path)
                if media_response:
                    media_url = media_response['url']
            
            # Create post content
            content = self._generate_post_content(book_metadata, media_url)
            
            # Create WordPress post
            post = WordPressPost()
            post.title = book_metadata['title']
            post.content = content
            post.post_status = 'draft'  # Change to 'publish' if you want to publish immediately
            
            # Add custom fields/metadata
            post.custom_fields = self._create_custom_fields(book_metadata)
            
            # Set categories and tags
            post.terms_names = {
                'post_tag': self._generate_tags(book_metadata),
                'category': ['Books', 'Library']
            }
            
            # Publish post
            post_id = self.client.call(NewPost(post))
            
            logging.info(f"Post created successfully with ID: {post_id}")
            return post_id
            
        except Exception as e:
            logging.error(f"Error creating WordPress post: {e}")
            return None
    
    def _generate_post_content(self, book_metadata: Dict, media_url: str = None) -> str:
        """Generate HTML content for the post"""
        content = f"""
        <div class="book-details">
            <h2>Book Information</h2>
            
            <div class="book-metadata">
                <p><strong>Title:</strong> {book_metadata.get('title', 'N/A')}</p>
                <p><strong>Author:</strong> {book_metadata.get('author', 'N/A')}</p>
                <p><strong>Publisher:</strong> {book_metadata.get('publisher', 'N/A')}</p>
                <p><strong>Year:</strong> {book_metadata.get('year', 'N/A')}</p>
                <p><strong>Pages:</strong> {book_metadata.get('pages', 'N/A')}</p>
                <p><strong>Language:</strong> {book_metadata.get('language', 'N/A')}</p>
                <p><strong>Size:</strong> {book_metadata.get('size', 'N/A')}</p>
                <p><strong>Format:</strong> {book_metadata.get('extension', 'N/A')}</p>
            </div>
        """
        
        if media_url:
            content += f"""
            <div class="download-section">
                <h3>Download</h3>
                <p><a href="{media_url}" class="download-button" target="_blank">Download Book</a></p>
            </div>
            """
        
        content += """
        </div>
        
        <style>
        .book-details {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .book-metadata p {
            margin: 10px 0;
        }
        .download-button {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
        }
        .download-button:hover {
            background-color: #005a87;
            color: white;
        }
        </style>
        """
        
        return content
    
    def _create_custom_fields(self, book_metadata: Dict) -> list:
        """Create custom fields for WordPress post"""
        custom_fields = []
        
        field_mapping = {
            'book_author': 'author',
            'book_publisher': 'publisher',
            'book_year': 'year',
            'book_pages': 'pages',
            'book_language': 'language',
            'book_size': 'size',
            'book_format': 'extension',
            'book_id': 'id'
        }
        
        for wp_field, metadata_key in field_mapping.items():
            if book_metadata.get(metadata_key):
                custom_fields.append({
                    'key': wp_field,
                    'value': book_metadata[metadata_key]
                })
        
        return custom_fields
    
    def _generate_tags(self, book_metadata: Dict) -> list:
        """Generate tags for the post"""
        tags = ['book', 'ebook', 'library']
        
        # Add author as tag
        if book_metadata.get('author'):
            tags.append(book_metadata['author'])
        
        # Add publisher as tag
        if book_metadata.get('publisher'):
            tags.append(book_metadata['publisher'])
        
        # Add language as tag
        if book_metadata.get('language'):
            tags.append(book_metadata['language'])
        
        # Add format as tag
        if book_metadata.get('extension'):
            tags.append(book_metadata['extension'].upper())
        
        # Add year as tag
        if book_metadata.get('year'):
            tags.append(book_metadata['year'])
        
        return list(set(tags))  # Remove duplicates
    
    def test_connection(self) -> bool:
        """Test WordPress connection"""
        try:
            # Try to get blog info
            blog_info = self.client.call(WordPressPost())
            logging.info("WordPress connection successful")
            return True
        except Exception as e:
            logging.error(f"WordPress connection failed: {e}")
            return False