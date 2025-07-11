#!/usr/bin/env python3
"""
SEO Link Building Research & Outreach Tool
A legitimate white-hat SEO tool for finding and managing backlink opportunities
"""

import requests
import csv
import json
import time
import re
from urllib.parse import urljoin, urlparse
from bs4 import BeautifulSoup
from dataclasses import dataclass, asdict
from typing import List, Dict, Optional
import sqlite3
from datetime import datetime, timedelta
import random

@dataclass
class LinkOpportunity:
    """Data class for storing link building opportunities"""
    domain: str
    url: str
    page_title: str
    contact_email: str
    domain_authority: int
    page_authority: int
    outreach_status: str
    notes: str
    date_found: str
    last_contacted: str
    response_received: bool

class SEOLinkBuilder:
    """Main class for SEO link building research and outreach"""
    
    def __init__(self, database_file="link_opportunities.db"):
        self.db_file = database_file
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.setup_database()
    
    def setup_database(self):
        """Initialize SQLite database for storing opportunities"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS opportunities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                domain TEXT UNIQUE,
                url TEXT,
                page_title TEXT,
                contact_email TEXT,
                domain_authority INTEGER,
                page_authority INTEGER,
                outreach_status TEXT DEFAULT 'not_contacted',
                notes TEXT,
                date_found TEXT,
                last_contacted TEXT,
                response_received BOOLEAN DEFAULT FALSE
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS outreach_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                template_name TEXT UNIQUE,
                subject_line TEXT,
                email_body TEXT,
                template_type TEXT
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS campaigns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_name TEXT UNIQUE,
                target_keywords TEXT,
                your_content_url TEXT,
                created_date TEXT,
                active BOOLEAN DEFAULT TRUE
            )
        ''')
        
        conn.commit()
        conn.close()
        print("✅ Database initialized successfully")
    
    def find_guest_posting_opportunities(self, keywords: List[str], max_results: int = 50) -> List[Dict]:
        """Find guest posting opportunities using search queries"""
        opportunities = []
        
        # Guest posting search query templates
        search_query_templates = [
            '"{}" "write for us"',
            '"{}" "guest post"',
            '"{}" "submit guest post"',
            '"{}" "contribute"',
            '"{}" "guest author"',
            '"{}" "guest blogger"',
            '"{}" intitle:"write for us"',
            '"{}" intitle:"guest post"'
        ]
        
        print(f"🔍 Searching for guest posting opportunities...")
        
        for keyword in keywords:
            for query_template in search_query_templates[:3]:  # Limit to avoid rate limiting
                query = query_template.format(keyword)
                results = self.search_google(query, max_results=10)
                
                for result in results:
                    opportunity = self.analyze_page_for_opportunity(result['url'], 'guest_posting')
                    if opportunity:
                        opportunities.append(opportunity)
                
                time.sleep(random.uniform(2, 5))  # Respectful delay
        
        return opportunities
    
    def find_broken_link_opportunities(self, competitors: List[str]) -> List[Dict]:
        """Find broken link building opportunities from competitor analysis"""
        opportunities = []
        
        print(f"🔗 Analyzing competitors for broken link opportunities...")
        
        for competitor_url in competitors:
            try:
                response = self.session.get(competitor_url, timeout=10)
                soup = BeautifulSoup(response.content, 'html.parser')
                
                # Find all external links
                links = soup.find_all('a', href=True)
                external_links = [link for link in links if self.is_external_link(link['href'], competitor_url)]
                
                for link in external_links[:20]:  # Limit to avoid overwhelming
                    link_url = link['href']
                    if self.check_if_broken(link_url):
                        # This is a broken link opportunity
                        referring_domain = urlparse(competitor_url).netloc
                        opportunity = {
                            'domain': referring_domain,
                            'url': competitor_url,
                            'broken_link': link_url,
                            'anchor_text': link.get_text().strip(),
                            'opportunity_type': 'broken_link'
                        }
                        opportunities.append(opportunity)
                
                time.sleep(random.uniform(3, 6))
                
            except Exception as e:
                print(f"❌ Error analyzing {competitor_url}: {e}")
        
        return opportunities
    
    def find_resource_page_opportunities(self, keywords: List[str]) -> List[Dict]:
        """Find resource page link opportunities"""
        opportunities = []
        
        resource_query_templates = [
            '"{}" "helpful links"',
            '"{}" "useful resources"',
            '"{}" "recommended sites"',
            '"{}" intitle:"resources"',
            '"{}" intitle:"links"'
        ]
        
        print(f"📚 Searching for resource page opportunities...")
        
        for keyword in keywords:
            for query_template in resource_query_templates[:2]:  # Limit queries
                query = query_template.format(keyword)
                results = self.search_google(query, max_results=10)
                
                for result in results:
                    opportunity = self.analyze_page_for_opportunity(result['url'], 'resource_page')
                    if opportunity:
                        opportunities.append(opportunity)
                
                time.sleep(random.uniform(2, 4))
        
        return opportunities
    
    def search_google(self, query: str, max_results: int = 10) -> List[Dict]:
        """Simulate Google search results (replace with actual API in production)"""
        # This is a placeholder. In production, use Google Custom Search API
        # or other legitimate search APIs
        
        results = []
        
        # Simulated results structure for demonstration
        # In real implementation, integrate with Google Custom Search API
        
        print(f"🔍 Searching: {query}")
        
        # Placeholder results
        example_results = [
            {
                'title': f'Example Site for {query.split()[0]}',
                'url': f'https://example-{len(results)}.com/write-for-us',
                'snippet': 'We welcome guest contributors to write for our blog...'
            }
        ]
        
        return example_results[:max_results]
    
    def analyze_page_for_opportunity(self, url: str, opportunity_type: str) -> Optional[Dict]:
        """Analyze a page to determine if it's a good link opportunity"""
        try:
            response = self.session.get(url, timeout=10)
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Extract page information
            title = soup.find('title')
            title_text = title.get_text().strip() if title else 'No title'
            
            # Look for contact information
            contact_email = self.extract_contact_email(soup, url)
            
            # Calculate basic metrics (replace with real metrics in production)
            domain_authority = random.randint(20, 80)  # Placeholder
            page_authority = random.randint(15, 75)    # Placeholder
            
            opportunity = {
                'domain': urlparse(url).netloc,
                'url': url,
                'page_title': title_text,
                'contact_email': contact_email,
                'domain_authority': domain_authority,
                'page_authority': page_authority,
                'opportunity_type': opportunity_type,
                'date_found': datetime.now().isoformat()
            }
            
            return opportunity
            
        except Exception as e:
            print(f"❌ Error analyzing {url}: {e}")
            return None
    
    def extract_contact_email(self, soup: BeautifulSoup, url: str) -> str:
        """Extract contact email from page"""
        # Look for email patterns in text
        page_text = soup.get_text()
        email_pattern = r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'
        emails = re.findall(email_pattern, page_text)
        
        if emails:
            # Filter out common non-contact emails
            filtered_emails = [email for email in emails if not any(
                skip in email.lower() for skip in ['noreply', 'no-reply', 'example', 'test']
            )]
            if filtered_emails:
                return filtered_emails[0]
        
        # Look for contact page
        contact_links = soup.find_all('a', href=True)
        for link in contact_links:
            if any(word in link.get_text().lower() for word in ['contact', 'email', 'get in touch']):
                contact_url = urljoin(url, link['href'])
                try:
                    contact_response = self.session.get(contact_url, timeout=10)
                    contact_soup = BeautifulSoup(contact_response.content, 'html.parser')
                    contact_text = contact_soup.get_text()
                    contact_emails = re.findall(email_pattern, contact_text)
                    if contact_emails:
                        return contact_emails[0]
                except:
                    pass
        
        return 'No email found'
    
    def is_external_link(self, href: str, base_url: str) -> bool:
        """Check if a link is external"""
        try:
            base_domain = urlparse(base_url).netloc
            link_domain = urlparse(href).netloc
            return link_domain and link_domain != base_domain
        except:
            return False
    
    def check_if_broken(self, url: str) -> bool:
        """Check if a URL is broken (returns 404 or other error)"""
        try:
            response = self.session.head(url, timeout=10, allow_redirects=True)
            return response.status_code >= 400
        except:
            return True
    
    def save_opportunities(self, opportunities: List[Dict]):
        """Save opportunities to database"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        saved_count = 0
        for opp in opportunities:
            try:
                cursor.execute('''
                    INSERT OR REPLACE INTO opportunities 
                    (domain, url, page_title, contact_email, domain_authority, 
                     page_authority, notes, date_found)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ''', (
                    opp['domain'],
                    opp['url'],
                    opp['page_title'],
                    opp['contact_email'],
                    opp['domain_authority'],
                    opp['page_authority'],
                    f"Type: {opp.get('opportunity_type', 'unknown')}",
                    opp['date_found']
                ))
                saved_count += 1
            except Exception as e:
                print(f"❌ Error saving opportunity {opp['domain']}: {e}")
        
        conn.commit()
        conn.close()
        print(f"✅ Saved {saved_count} opportunities to database")
    
    def get_opportunities(self, status: str = None, min_authority: int = 0) -> List[Dict]:
        """Retrieve opportunities from database with filters"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        query = "SELECT * FROM opportunities WHERE domain_authority >= ?"
        params = [min_authority]
        
        if status:
            query += " AND outreach_status = ?"
            params.append(status)
        
        query += " ORDER BY domain_authority DESC"
        
        cursor.execute(query, params)
        rows = cursor.fetchall()
        
        columns = [description[0] for description in cursor.description]
        opportunities = [dict(zip(columns, row)) for row in rows]
        
        conn.close()
        return opportunities
    
    def create_outreach_template(self, name: str, subject: str, body: str, template_type: str):
        """Create email outreach template"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT OR REPLACE INTO outreach_templates
            (template_name, subject_line, email_body, template_type)
            VALUES (?, ?, ?, ?)
        ''', (name, subject, body, template_type))
        
        conn.commit()
        conn.close()
        print(f"✅ Saved outreach template: {name}")
    
    def generate_outreach_email(self, opportunity: Dict, template_name: str, 
                              your_name: str, your_site: str, content_url: str) -> Dict:
        """Generate personalized outreach email"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        cursor.execute("SELECT * FROM outreach_templates WHERE template_name = ?", (template_name,))
        template = cursor.fetchone()
        conn.close()
        
        if not template:
            print(f"❌ Template {template_name} not found")
            return {}
        
        # Personalization variables
        variables = {
            '{site_name}': opportunity['domain'],
            '{page_title}': opportunity['page_title'],
            '{your_name}': your_name,
            '{your_site}': your_site,
            '{content_url}': content_url,
            '{contact_email}': opportunity['contact_email']
        }
        
        # Replace variables in template
        subject = template[2]  # subject_line
        body = template[3]     # email_body
        
        for var, value in variables.items():
            subject = subject.replace(var, value)
            body = body.replace(var, value)
        
        return {
            'to': opportunity['contact_email'],
            'subject': subject,
            'body': body,
            'opportunity_id': opportunity.get('id', '')
        }
    
    def track_outreach(self, opportunity_id: int, status: str, notes: str = ""):
        """Track outreach attempts and responses"""
        conn = sqlite3.connect(self.db_file)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE opportunities 
            SET outreach_status = ?, last_contacted = ?, notes = ?
            WHERE id = ?
        ''', (status, datetime.now().isoformat(), notes, opportunity_id))
        
        conn.commit()
        conn.close()
        print(f"✅ Updated outreach status for opportunity {opportunity_id}")
    
    def export_opportunities(self, filename: str, status: str = None):
        """Export opportunities to CSV"""
        opportunities = self.get_opportunities(status=status)
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            if opportunities:
                fieldnames = opportunities[0].keys()
                writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
                writer.writeheader()
                writer.writerows(opportunities)
        
        print(f"✅ Exported {len(opportunities)} opportunities to {filename}")
    
    def analyze_competitor_backlinks(self, competitor_url: str) -> Dict:
        """Analyze competitor's potential backlink sources"""
        print(f"🔍 Analyzing competitor: {competitor_url}")
        
        # This would integrate with backlink analysis APIs in production
        # For now, provide structure for the analysis
        
        analysis = {
            'competitor_url': competitor_url,
            'total_backlinks': random.randint(100, 5000),
            'referring_domains': random.randint(50, 1000),
            'average_authority': random.randint(30, 70),
            'top_referring_domains': [
                f'example-{i}.com' for i in range(1, 11)
            ],
            'common_anchor_texts': [
                'click here', 'read more', 'visit site', 'learn more'
            ],
            'analyzed_date': datetime.now().isoformat()
        }
        
        return analysis

class OutreachTemplates:
    """Pre-built outreach email templates"""
    
    @staticmethod
    def guest_posting_template():
        return {
            'name': 'guest_posting',
            'subject': 'Guest Post Proposal for {site_name}',
            'body': '''Hi there,

I hope this email finds you well. I came across your website {site_name} and was impressed by your article "{page_title}".

I'm {your_name} from {your_site}, and I specialize in creating high-quality content about [your niche]. I'd love to contribute a guest post to your site that would provide value to your audience.

Here are a few topic ideas I could write about:
• [Topic 1]
• [Topic 2] 
• [Topic 3]

I've written for several websites in this space and can provide samples of my work. Here's an example of my recent content: {content_url}

Would you be interested in a guest contribution? I'd be happy to send you a detailed outline for any topic that interests you.

Best regards,
{your_name}
{your_site}''',
            'type': 'guest_posting'
        }
    
    @staticmethod
    def broken_link_template():
        return {
            'name': 'broken_link',
            'subject': 'Broken Link on {site_name}',
            'body': '''Hi,

I was browsing your excellent resource page at {page_title} and noticed that one of the links appears to be broken:

[Broken link URL]

I thought you'd want to know since it affects the user experience on your site.

As a replacement, I have a similar resource that your visitors might find helpful: {content_url}

It covers [brief description of your content] and would be a great fit for your resource page.

Thanks for maintaining such a valuable resource!

Best regards,
{your_name}
{your_site}''',
            'type': 'broken_link'
        }
    
    @staticmethod
    def resource_page_template():
        return {
            'name': 'resource_page',
            'subject': 'Resource suggestion for {site_name}',
            'body': '''Hello,

I discovered your fantastic resource page "{page_title}" and found it incredibly helpful. Thank you for curating such valuable information!

I recently created a comprehensive resource that I think would be a great addition to your page: {content_url}

It covers [brief description] and includes [key features/benefits]. I believe your visitors would find it valuable because [specific reason].

Would you consider adding it to your resource list? I'd be happy to provide more information if needed.

Keep up the great work!

Best regards,
{your_name}
{your_site}''',
            'type': 'resource_page'
        }

def main():
    """Main function to demonstrate the tool"""
    print("🚀 SEO Link Building Research Tool")
    print("==================================")
    
    # Initialize the tool
    link_builder = SEOLinkBuilder()
    
    # Setup default templates
    templates = OutreachTemplates()
    
    # Create default templates
    guest_template = templates.guest_posting_template()
    link_builder.create_outreach_template(
        guest_template['name'],
        guest_template['subject'],
        guest_template['body'],
        guest_template['type']
    )
    
    broken_template = templates.broken_link_template()
    link_builder.create_outreach_template(
        broken_template['name'],
        broken_template['subject'],
        broken_template['body'],
        broken_template['type']
    )
    
    resource_template = templates.resource_page_template()
    link_builder.create_outreach_template(
        resource_template['name'],
        resource_template['subject'],
        resource_template['body'],
        resource_template['type']
    )
    
    # Example usage
    keywords = ["digital marketing", "SEO", "content marketing"]
    competitors = ["https://example-competitor.com"]
    
    print("\n🔍 Finding Guest Posting Opportunities...")
    guest_opportunities = link_builder.find_guest_posting_opportunities(keywords, max_results=5)
    
    print("\n📚 Finding Resource Page Opportunities...")
    resource_opportunities = link_builder.find_resource_page_opportunities(keywords)
    
    print("\n🔗 Finding Broken Link Opportunities...")
    broken_opportunities = link_builder.find_broken_link_opportunities(competitors)
    
    # Combine all opportunities
    all_opportunities = guest_opportunities + resource_opportunities + broken_opportunities
    
    if all_opportunities:
        print(f"\n✅ Found {len(all_opportunities)} total opportunities")
        link_builder.save_opportunities(all_opportunities)
        
        # Export to CSV
        link_builder.export_opportunities("link_opportunities.csv")
        
        # Show example of generating outreach email
        if all_opportunities:
            example_opportunity = all_opportunities[0]
            email = link_builder.generate_outreach_email(
                example_opportunity,
                'guest_posting',
                'Your Name',
                'YourWebsite.com',
                'https://yoursite.com/amazing-content'
            )
            
            print("\n📧 Example Outreach Email Generated:")
            print(f"To: {email.get('to', 'N/A')}")
            print(f"Subject: {email.get('subject', 'N/A')}")
            print("Body preview:", email.get('body', 'N/A')[:200] + "...")
    
    print("\n🎯 Link Building Strategy Recommendations:")
    print("1. Focus on high-authority sites (DA 40+)")
    print("2. Personalize each outreach email")
    print("3. Provide genuine value in your content")
    print("4. Follow up politely after 1-2 weeks")
    print("5. Track all outreach attempts")
    print("6. Build relationships, not just links")
    
    print("\n✅ Tool setup complete! Check link_opportunities.db for your data.")

if __name__ == "__main__":
    main()