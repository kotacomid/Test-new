# Kotacom AI Content Generator - Installation & Setup Guide

## Quick Start

This enhanced WordPress plugin now includes **FREE AI providers** and advanced E-E-A-T compliance features. Follow this guide to get up and running quickly.

## 🚀 Installation

### Method 1: WordPress Admin Upload
1. Download the plugin ZIP file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. **Activate** the plugin

### Method 2: FTP Upload
1. Extract the ZIP file
2. Upload the entire folder to `/wp-content/plugins/`
3. Activate via **WordPress Admin → Plugins**

## ⚙️ Basic Configuration

### Step 1: Access Plugin Settings
- Navigate to **WordPress Admin → Kotacom AI**
- You'll see the main dashboard with multiple tabs

### Step 2: Configure Free AI Providers (Recommended First Step)
1. Go to **Kotacom AI → Free AI**
2. Choose from these **FREE** options:

#### 🆓 **Hugging Face (Free Tier)**
- Visit [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens)
- Create a **free account**
- Generate an API token
- Paste in the plugin settings
- **Rate Limit:** 1,000 requests/hour

#### 🚀 **Groq (Free Tier)**
- Visit [console.groq.com](https://console.groq.com)
- Sign up for **free account**
- Get API key from dashboard
- **Rate Limit:** 30 requests/minute
- **Models:** Mixtral 8x7B, Llama 2 70B, Gemma 7B

#### 🤖 **Cohere (Free Tier)**
- Visit [dashboard.cohere.ai](https://dashboard.cohere.ai)
- Create **free account**
- Get API key
- **Rate Limit:** 100 requests/month

#### 🔥 **Together AI (Free Credits)**
- Visit [api.together.xyz](https://api.together.xyz)
- Sign up for **free credits**
- **Rate Limit:** 600 requests/hour
- **Models:** Llama 2, Mixtral, OpenHermes

#### 🔄 **Replicate (Free Credits)**
- Visit [replicate.com](https://replicate.com)
- Sign up for **free credits**
- **Rate Limit:** 200 requests/hour

#### 💻 **Ollama (Local - Unlimited & Free)**
- **Best Option for Unlimited Use**
- Install on your server: [ollama.ai](https://ollama.ai)
- Pull models: `ollama pull llama2`
- Configure URL in plugin (default: `http://localhost:11434`)
- **Rate Limit:** Unlimited (local processing)

### Step 3: Test Connections
- Use the **Test Connection** buttons for each provider
- Ensure at least one provider is working

### Step 4: Configure Rotation Strategy
- Go to **Free AI → Rotation Settings**
- Choose **Smart (Recommended)** for automatic optimization
- Select preferred providers

## 🎯 Content Generation

### Quick Generation
1. Go to **Kotacom AI → Generator**
2. Enter a keyword (e.g., "WordPress SEO tips")
3. Select tone, length, and audience
4. Click **Generate Content**
5. Content will be created with E-E-A-T optimization

### Bulk Generation
1. Go to **Kotacom AI → Keywords**
2. Add multiple keywords (one per line or bulk import)
3. Use **Generate Bulk Content** for mass production

### Advanced Templates
1. Go to **Kotacom AI → Templates**
2. Choose from professional templates:
   - Expert How-To Guides
   - Product Reviews
   - Comparison Articles
   - Case Studies
   - Research-Backed Articles

## 🔧 Advanced Features

### E-E-A-T Enhancement
Your content automatically includes:
- **Experience signals** (first-person narratives)
- **Expertise indicators** (professional credentials)
- **Authority building** (citations, statistics)
- **Trust signals** (disclaimers, author bios)

### Content Quality Scoring
- Each generated content gets a quality score (0-100)
- Automatic improvements for spam detection avoidance
- Readability optimization
- Keyword density optimization

### Schema Markup
- Automatic JSON-LD structured data
- Rich snippets optimization
- Multiple content types supported

### Anti-Spam Protection
- Intelligent pattern detection
- Natural language variation
- Semantic keyword distribution
- Content uniqueness verification

## 📊 Monitoring & Analytics

### Provider Statistics
- Go to **Free AI → Statistics**
- Monitor usage, error rates, response times
- Track provider performance

### Queue Management
- Go to **Kotacom AI → Queue**
- Monitor content generation progress
- Retry failed items

### Logs & Debugging
- Go to **Kotacom AI → Logs**
- View detailed operation logs
- Troubleshoot issues

## 🛠️ Troubleshooting

### Plugin Won't Activate
```
Fatal error: Cannot redeclare...
```
**Solution:**
- Ensure no duplicate plugins
- Check PHP version (requires 7.4+)
- Deactivate conflicting plugins

### No API Providers Available
**Solution:**
- Configure at least one free provider in **Free AI** settings
- Test connections using test buttons
- Check API keys are valid

### Content Generation Fails
**Solution:**
- Check **Free AI → Statistics** for provider status
- Verify API quotas haven't been exceeded
- Try different provider or reset statistics

### Local Ollama Setup Issues
**Solution:**
- Ensure Ollama is running: `ollama serve`
- Check model is downloaded: `ollama pull llama2`
- Verify firewall allows port 11434
- Test URL in browser: `http://localhost:11434/api/tags`

## 💡 Best Practices

### Cost Optimization
1. **Start with free providers** (Ollama for unlimited)
2. **Use rotation** to distribute load
3. **Monitor usage** in statistics tab
4. **Set up Ollama locally** for unlimited generation

### Content Quality
1. **Use specific keywords** ("WordPress SEO for beginners" vs "SEO")
2. **Choose appropriate tone** for your audience
3. **Review and edit** AI content before publishing
4. **Use advanced templates** for better structure

### Performance
1. **Enable queue processing** for bulk operations
2. **Use smart rotation** for optimal provider selection
3. **Monitor provider statistics** for performance insights
4. **Set reasonable batch sizes** (10-20 items)

## 🔐 Security

### API Key Protection
- Keys are stored encrypted in WordPress database
- Never share API keys publicly
- Rotate keys periodically
- Use WordPress security best practices

### Local AI Benefits
- Complete privacy (data never leaves server)
- No API key exposure risks
- Unlimited usage without quotas
- Works offline

## 📈 Scaling Up

### For High Volume Usage
1. **Set up Ollama** on dedicated server
2. **Use multiple free provider accounts**
3. **Implement queue management** for large batches
4. **Monitor and optimize** based on statistics

### Enterprise Features
- Content quality scoring
- Advanced template system
- E-E-A-T compliance automation
- Schema markup generation
- Anti-spam protection

## 🆘 Support

### Common Issues
- **Rate Limiting:** Switch providers or wait for quota reset
- **API Errors:** Check provider status pages
- **Quality Issues:** Adjust prompts and templates
- **Performance:** Optimize rotation settings

### Getting Help
1. Check **Logs** for detailed error messages
2. Test individual providers in **Free AI** settings
3. Review provider documentation for API limits
4. Reset statistics if needed

## 🎉 Success Tips

1. **Start Small:** Test with a few keywords first
2. **Use Free Providers:** No cost for initial testing
3. **Monitor Quality:** Check generated content scores
4. **Optimize Settings:** Adjust based on results
5. **Scale Gradually:** Add more providers as needed

---

## Quick Command Reference

### Ollama Commands
```bash
# Install model
ollama pull llama2

# Start Ollama
ollama serve

# List models
ollama list

# Check status
curl http://localhost:11434/api/tags
```

### WordPress Commands
```bash
# Check plugin status
wp plugin status kotacom-ai-content-generator

# Activate plugin
wp plugin activate kotacom-ai-content-generator

# Check database tables
wp db query "SHOW TABLES LIKE 'wp_kotacom_ai_%'"
```

---

**Happy Content Generating! 🚀**

*This enhanced plugin provides enterprise-level AI content generation with free providers, E-E-A-T compliance, and advanced anti-spam protection - all while keeping costs minimal.*