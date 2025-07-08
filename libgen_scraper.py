import requests
from bs4 import BeautifulSoup
import time
import os
import re
from urllib.parse import urljoin, urlparse
from tqdm import tqdm
import logging
from typing import Dict, List, Optional

class LibgenScraper:
    def __init__(self, base_url: str = "http://libgen.rs", delay: int = 2):
        self.base_url = base_url
        self.delay = delay
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        
    def search_books(self, query: str, search_type: str = "title") -> List[Dict]:
        """
        Search for books on libgen
        search_type: 'title', 'author', 'isbn', 'publisher'
        """
        search_url = f"{self.base_url}/search.php"
        params = {
            'req': query,
            'lg_topic': 'libgen',
            'open': '0',
            'view': 'simple',
            'res': '25',
            'phrase': '1',
            'column': search_type
        }
        
        try:
            response = self.session.get(search_url, params=params)
            response.raise_for_status()
            
            soup = BeautifulSoup(response.content, 'html.parser')
            books = []
            
            # Find the table with search results
            table = soup.find('table', {'rules': 'cols'})
            if not table:
                logging.warning("No search results table found")
                return books
                
            rows = table.find_all('tr')[1:]  # Skip header row
            
            for row in rows:
                cells = row.find_all('td')
                if len(cells) >= 10:
                    book_data = self._extract_book_metadata(cells)
                    if book_data:
                        books.append(book_data)
            
            time.sleep(self.delay)
            return books
            
        except requests.exceptions.RequestException as e:
            logging.error(f"Error searching libgen: {e}")
            return []
    
    def _extract_book_metadata(self, cells) -> Optional[Dict]:
        """Extract metadata from table cells"""
        try:
            # Extract basic information
            id_cell = cells[0].get_text(strip=True)
            author = cells[1].get_text(strip=True)
            title_cell = cells[2]
            publisher = cells[3].get_text(strip=True)
            year = cells[4].get_text(strip=True)
            pages = cells[5].get_text(strip=True)
            language = cells[6].get_text(strip=True)
            size = cells[7].get_text(strip=True)
            extension = cells[8].get_text(strip=True)
            
            # Extract title and get download links
            title_link = title_cell.find('a')
            title = title_link.get_text(strip=True) if title_link else title_cell.get_text(strip=True)
            
            # Get download links from the mirrors column
            mirrors_cell = cells[9] if len(cells) > 9 else None
            download_links = []
            
            if mirrors_cell:
                links = mirrors_cell.find_all('a')
                for link in links:
                    href = link.get('href')
                    if href:
                        download_links.append(urljoin(self.base_url, href))
            
            return {
                'id': id_cell,
                'title': title,
                'author': author,
                'publisher': publisher,
                'year': year,
                'pages': pages,
                'language': language,
                'size': size,
                'extension': extension,
                'download_links': download_links
            }
            
        except Exception as e:
            logging.error(f"Error extracting book metadata: {e}")
            return None
    
    def get_direct_download_link(self, mirror_url: str) -> Optional[str]:
        """Get direct download link from mirror page"""
        try:
            response = self.session.get(mirror_url)
            response.raise_for_status()
            
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Look for download link patterns
            download_link = None
            
            # Method 1: Look for direct download links
            for link in soup.find_all('a'):
                href = link.get('href', '')
                if any(domain in href for domain in ['cloudflare-ipfs.com', 'gateway.ipfs.io', 'download.library.lol']):
                    download_link = href
                    break
            
            # Method 2: Look for GET link
            if not download_link:
                get_link = soup.find('a', string=re.compile(r'GET', re.IGNORECASE))
                if get_link:
                    download_link = get_link.get('href')
            
            time.sleep(self.delay)
            return download_link
            
        except requests.exceptions.RequestException as e:
            logging.error(f"Error getting direct download link: {e}")
            return None
    
    def download_file(self, download_url: str, filename: str, download_dir: str = "./downloads") -> bool:
        """Download file from direct URL"""
        try:
            os.makedirs(download_dir, exist_ok=True)
            
            response = self.session.get(download_url, stream=True)
            response.raise_for_status()
            
            # Get file size
            total_size = int(response.headers.get('content-length', 0))
            
            filepath = os.path.join(download_dir, filename)
            
            with open(filepath, 'wb') as file, tqdm(
                desc=filename,
                total=total_size,
                unit='iB',
                unit_scale=True,
                unit_divisor=1024,
            ) as progress_bar:
                for chunk in response.iter_content(chunk_size=1024):
                    if chunk:
                        file.write(chunk)
                        progress_bar.update(len(chunk))
            
            logging.info(f"Downloaded: {filepath}")
            return True
            
        except Exception as e:
            logging.error(f"Error downloading file: {e}")
            return False
    
    def download_book(self, book_metadata: Dict, download_dir: str = "./downloads") -> Optional[str]:
        """Download a book using its metadata"""
        if not book_metadata.get('download_links'):
            logging.error("No download links available")
            return None
        
        # Create filename
        safe_title = re.sub(r'[^\w\s-]', '', book_metadata['title'])
        safe_title = re.sub(r'[-\s]+', '-', safe_title)
        filename = f"{safe_title}.{book_metadata['extension']}"
        
        # Try each download link
        for mirror_url in book_metadata['download_links']:
            logging.info(f"Trying mirror: {mirror_url}")
            
            direct_url = self.get_direct_download_link(mirror_url)
            if direct_url:
                logging.info(f"Found direct download URL: {direct_url}")
                
                if self.download_file(direct_url, filename, download_dir):
                    return os.path.join(download_dir, filename)
            
            time.sleep(self.delay)
        
        logging.error("Failed to download from all mirrors")
        return None