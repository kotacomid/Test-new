# 📚 LibGen to WordPress Automation (FREE TIER)

Automatically scrape book metadata from LibGen and publish to WordPress with **100% FREE** infrastructure. This is a **metadata-only approach** that minimizes legal risks while providing valuable content.

## 🎯 **What This Does**

- ✅ **Scrapes metadata** from LibGen (titles, authors, descriptions, etc.)
- ✅ **Auto-publishes** rich content to WordPress
- ✅ **Links to legal sources** (Google Books, Amazon, etc.)
- ✅ **SEO optimized** posts with proper tags and structure
- ✅ **Rate-limited** and respectful scraping
- ❌ **NO file downloads** (minimizes legal risk)
- ❌ **NO file hosting** (safe approach)

## 🛡️ **Legal & Ethical Approach**

This project focuses on **metadata aggregation** rather than file distribution:

- **Metadata Only**: Extract book information, not the files themselves
- **Legal Links**: Direct users to legitimate purchase/preview sources
- **Educational Purpose**: Information for research and discovery
- **Copyright Respectful**: Includes disclaimers and encourages legal purchases
- **Rate Limited**: Respectful scraping practices

## 📋 **Data Yang Diambil**

### ✅ **Metadata Aman (Yang Kita Ambil)**
- 📖 Judul, author, tahun publikasi
- 📊 Halaman, ukuran file, format
- 🏷️ Kategori dan subject tags  
- 📝 Deskripsi buku (jika ada)
- 🖼️ URL cover image
- 🔗 Links ke sumber legal (Google Books, Amazon, dll)

### ❌ **Yang TIDAK Kita Ambil**
- ❌ File PDF/EPUB actual
- ❌ Direct download links
- ❌ Copyrighted content
- ❌ Mass file downloads

## 🚀 **Quick Start (15 Minutes)**

### 1. **Setup Environment**

```bash
# Clone/download project
git clone <your-repo> libgen-scraper
cd libgen-scraper

# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Mac/Linux:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

### 2. **Configure WordPress**

1. **Get WordPress Application Password:**
   - Login to your WordPress admin
   - Go to: Users → Profile → Application Passwords
   - Create new app password: "LibGen Scraper"
   - **Copy the generated password** (not your login password!)

2. **Setup Environment:**
```bash
# Copy configuration template
cp .env.example .env

# Edit .env with your details
nano .env
```

```env
# Update these values in .env
WP_URL=https://yourwordpresssite.com
WP_USERNAME=your_username
WP_APP_PASSWORD=xxxx xxxx xxxx xxxx  # Application password from step 1
```

### 3. **Test Connection**

```bash
# Test WordPress connection
python main.py --status
```

### 4. **Run First Scrape & Publish**

```bash
# Scrape metadata and publish to WordPress
python main.py --batch

# Or run separately:
python main.py --scrape       # Only scrape
python main.py --publish      # Only publish
```

## 📁 **Project Structure**

```
libgen-scraper/
├── practical_implementation_example.py  # Metadata scraper
├── wordpress_publisher.py               # WordPress auto-poster  
├── main.py                             # Main orchestrator
├── requirements.txt                    # Python dependencies
├── .env.example                       # Configuration template
├── .env                              # Your actual config (create this)
├── books_metadata.db                 # SQLite database (auto-created)
└── README.md                         # This file
```

## 🎛️ **Usage Examples**

### **Basic Operations**

```bash
# Check current status
python main.py --status

# Scrape metadata only
python main.py --scrape

# Publish existing metadata to WordPress
python main.py --publish --max-posts 3

# Full automation (scrape + publish)
python main.py --batch
```

### **Advanced Options**

```bash
# Custom search terms
python main.py --scrape --search-terms "React" "Vue.js" "Angular"

# Publish specific number of posts
python main.py --publish --max-posts 10

# Scrape then publish in one command
python main.py --scrape --publish
```

## 📊 **Sample Output**

### **WordPress Post Content**
Each book becomes a rich WordPress post with:

```html
📚 Book Information
👤 Author: John Doe
📅 Year: 2023
🏢 Publisher: Tech Books Inc
📖 Pages: 350
🗣️ Language: English
📄 Format: PDF
💾 Size: 15.2 MB

📝 Description
[Book description from LibGen metadata]

🔗 Find This Book Legally
🔍 Google Books - Preview and purchase
📚 Internet Archive - Free access
🏛️ WorldCat - Find in libraries  
🛒 Amazon - Purchase new/used

⚠️ Important Disclaimer
Support authors by purchasing books legally...
```

### **Console Output**
```
🚀 Starting metadata scraping for 5 search terms...

🎯 Scraping 1/5: 'Python programming'
📚 Extracted: Learning Python: Powerful Object-Oriented Programming...
📚 Extracted: Python Crash Course: A Hands-On, Project-Based Intro...
✅ Saved 25 books from 'Python programming'

📝 Publishing 3 books to WordPress...
✅ Published: Learning Python by Mark Lutz (2013) - Book Information...
   📝 Post ID: 123
   🔗 URL: https://yoursite.com/learning-python-book-info

🎉 Bulk publish complete!
   ✅ Successful: 3
   ❌ Failed: 0
```

## 🔧 **Configuration Options**

### **Environment Variables (.env)**

| Variable | Description | Default |
|----------|-------------|---------|
| `WP_URL` | WordPress site URL | Required |
| `WP_USERNAME` | WordPress username | Required |
| `WP_APP_PASSWORD` | Application password | Required |
| `DB_PATH` | Database file path | `books_metadata.db` |
| `MAX_BOOKS_PER_SEARCH` | Books per search term | `40` |
| `SEARCH_DELAY_MIN` | Min delay between searches (sec) | `10` |
| `SEARCH_DELAY_MAX` | Max delay between searches (sec) | `20` |
| `POST_DELAY_SECONDS` | Delay between WordPress posts | `60` |

### **Search Targets**

Default search terms (you can customize):
- Python programming
- Machine learning  
- Web development
- Data science
- JavaScript tutorial
- React programming
- Node.js development
- Database design

## 💰 **Cost Breakdown (FREE)**

| Component | Service | Cost |
|-----------|---------|------|
| **Hosting** | Railway/Render free tier | $0 |
| **Database** | SQLite (local) | $0 |
| **WordPress** | Self-hosted or WP.com free | $0 |
| **Domain** | Freenom or existing | $0 |
| **SSL** | Let's Encrypt | $0 |
| **Development** | GitHub + VS Code | $0 |
| **Total** | | **$0/month** |

## 📈 **Scaling Strategy**

### **Phase 1: Foundation (Week 1-2)**
- ✅ 200 books metadata
- ✅ Basic WordPress posts
- ✅ Legal source links
- **Target**: 5-10 posts/day

### **Phase 2: Enhancement (Week 3-4)**  
- ✅ Better content formatting
- ✅ SEO optimization
- ✅ Category organization
- **Target**: 10-20 posts/day

### **Phase 3: Growth (Month 2+)**
- ✅ User engagement features
- ✅ Search functionality
- ✅ Affiliate monetization
- **Target**: Sustainable content site

## ⚠️ **Important Limitations**

### **Free Tier Constraints**
- **Memory**: 512MB (suitable for metadata scraping)
- **Storage**: 1GB (good for SQLite database)
- **Bandwidth**: Limited (perfect for our use case)
- **Uptime**: 550-750 hours/month

### **Rate Limiting**
- **LibGen**: 1-3 second delays between requests
- **WordPress**: 60 second delays between posts
- **Database**: SQLite (single-user, perfect for this scale)

### **Legal Considerations**
- **Metadata Only**: We only scrape bibliographic information
- **No File Hosting**: We don't store or serve copyrighted files
- **Legal Links**: We direct users to legitimate sources
- **Disclaimers**: All posts include copyright disclaimers

## 🛟 **Troubleshooting**

### **Common Issues**

#### ❌ "WordPress connection failed"
```bash
# Check your credentials
python -c "import requests; print(requests.get('https://yoursite.com/wp-json/wp/v2').status_code)"

# Should return 200
# If not, check WP_URL in .env
```

#### ❌ "No books found"
```bash
# LibGen might be blocked, try different mirror
# Edit practical_implementation_example.py line 15-19
self.base_urls = [
    "http://libgen.rs",  # Try this first
    "http://libgen.is",
    "http://libgen.st"
]
```

#### ❌ "Database locked"
```bash
# Close any other Python processes
pkill -f python
# Restart the script
```

### **Performance Tips**

1. **Optimize Search Terms**: Use specific, technical topics
2. **Batch Processing**: Run in off-peak hours
3. **Monitor Resources**: Check memory usage with `htop`
4. **Regular Cleanup**: Delete old logs periodically

## 🔐 **Security Best Practices**

1. **Environment Variables**: Never commit `.env` to git
2. **Application Passwords**: Use WP app passwords, not login passwords
3. **Rate Limiting**: Built-in delays prevent IP blocking
4. **User Agents**: Proper browser headers included
5. **Error Handling**: Graceful failures, no crashes

## 📊 **Expected Results**

### **After 1 Week**
- 📚 50-100 books in database
- 📝 20-30 WordPress posts
- 🔍 Indexed by search engines
- 👥 Initial organic traffic

### **After 1 Month**  
- 📚 200-400 books in database
- 📝 100-150 WordPress posts
- 📈 Growing search traffic
- 💰 Ready for monetization

### **SEO Benefits**
- **Long-tail Keywords**: Book titles and authors
- **Rich Content**: Detailed metadata and descriptions
- **Internal Linking**: Related books and categories
- **User Value**: Genuine book discovery resource

## 🤝 **Contributing**

Contributions welcome! Areas for improvement:

- **Enhanced Metadata**: Extract more book details
- **Better Formatting**: Improve WordPress post templates  
- **More Sources**: Add other legal book databases
- **UI Dashboard**: Web interface for management
- **Analytics**: Track post performance

## 📜 **License & Disclaimer**

This tool is for **educational and research purposes only**:

- ✅ **Metadata aggregation** is generally legal
- ✅ **Bibliographic information** is factual data
- ✅ **Linking to legal sources** supports authors
- ⚠️ **Respect copyright laws** in your jurisdiction
- ⚠️ **Use responsibly** and ethically

**We encourage supporting authors by purchasing books through legal channels.**

## 🆘 **Support**

Need help? 

1. **Check Issues**: Review common problems above
2. **Configuration**: Verify your `.env` file
3. **Test Components**: Run `--status` to diagnose
4. **Start Small**: Begin with `--scrape` only
5. **Monitor Logs**: Check console output for errors

---

## 🎉 **Ready to Start?**

1. **Set up your environment** (5 minutes)
2. **Configure WordPress** (5 minutes)  
3. **Run your first test** (2 minutes)
4. **Start scraping & publishing** (3 minutes)

**Total setup time: ~15 minutes** ⏱️

```bash
# Your journey starts here:
python main.py --batch
```

**Happy scraping! 📚→📝→🚀**
