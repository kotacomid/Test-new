# API Key Rotator - User Guide

## Overview

The API Key Rotator is a powerful feature that allows you to configure multiple API keys per AI provider and automatically rotate them when rate limits are hit. This ensures continuous operation and maximizes your API usage efficiency.

## Features

### ✅ Automatic Key Rotation
- Detects rate limit errors automatically
- Switches to the next available API key instantly
- Supports all major AI providers (Google AI, OpenAI, Groq, Anthropic, etc.)

### ✅ Cooldown Protection
- Rate-limited keys are put on cooldown (default: 1 hour)
- Prevents immediate reuse of problematic keys
- Automatic recovery when cooldown expires

### ✅ Smart Key Management
- Multiple keys per provider
- Active key tracking
- Masked key display for security
- Easy add/remove interface

### ✅ Comprehensive Monitoring
- Rotation statistics and logs
- Per-provider rotation tracking
- Test all keys functionality
- Real-time status updates

## How It Works

1. **Normal Operation**: The rotator uses the currently active API key for requests
2. **Rate Limit Detection**: When a rate limit error is detected, the system automatically rotates to the next key
3. **Cooldown Period**: The rate-limited key is put on cooldown to prevent immediate reuse
4. **Retry Logic**: The request is retried with the new key (up to 3 attempts)
5. **Fallback**: If all keys are rate-limited, the system reports the issue

## Setup Instructions

### 1. Access the API Rotator
- Go to **Kotacom AI > API Rotator** in your WordPress admin
- Select a provider from the dropdown
- Click "Load Keys" to view current configuration

### 2. Add Multiple API Keys
- Enter your API key in the "Add New API Key" field
- Click "Add Key" to save
- Repeat for additional keys

### 3. Test Your Keys
- Click "Test All Keys" to verify all keys are working
- Review test results to identify any problematic keys
- Remove or replace non-working keys

### 4. Monitor Performance
- Check rotation statistics regularly
- Monitor for excessive rotations (may indicate issues)
- Review failed attempts in the logs

## Best Practices

### For Free Tier Providers
- **Google AI**: Create multiple Google accounts for more free quota
- **Groq**: Multiple accounts provide more free requests
- **Anthropic**: Each account gets $5 free credit

### For Paid Providers
- **OpenAI**: Use multiple API keys from same account for redundancy
- **Multiple Accounts**: Consider separate accounts for higher limits

### General Tips
- **Start Small**: Begin with 2-3 keys per provider
- **Monitor Usage**: Watch rotation frequency to identify issues
- **Regular Testing**: Test all keys weekly to ensure they're working
- **Keep Backups**: Always have at least one working key as backup

## Rate Limit Errors Detected

The rotator automatically detects these common rate limit patterns:
- "quota exceeded"
- "rate limit"
- "too many requests"
- "limit exceeded"
- "billing_hard_limit_reached"
- "requests per minute"
- "monthly quota exceeded"

## Configuration

### Cooldown Duration
- Default: 1 hour (3600 seconds)
- Can be modified in the code if needed
- Prevents immediate reuse of rate-limited keys

### Retry Logic
- Maximum 3 attempts per request
- 1-second delay between retries
- Automatic fallback to error reporting

## Monitoring & Troubleshooting

### Rotation Statistics
- **Total Rotations**: Overall rotation count
- **Last 24 Hours**: Recent rotation activity
- **Last 7 Days**: Weekly rotation trends
- **By Provider**: Provider-specific statistics

### Common Issues

**High Rotation Frequency**
- May indicate insufficient API quotas
- Consider upgrading to paid tiers
- Add more API keys

**All Keys Failing**
- Check API key validity
- Verify provider service status
- Review error messages in logs

**Keys Stuck in Cooldown**
- Normal behavior for rate-limited keys
- Wait for cooldown to expire
- Add more keys to reduce load per key

## API Integration

### Legacy Compatibility
The rotator maintains backward compatibility with existing single API key configurations. Old keys are automatically migrated to the new multi-key system.

### Automatic Migration
- Single keys are converted to multi-key arrays
- No manual intervention required
- Original keys remain as backup

## Security Features

### Key Masking
- API keys are masked in the interface (first 8 + last 4 characters)
- Full keys are never displayed in the admin
- Secure storage in WordPress options

### Access Control
- Requires `manage_options` capability
- Admin-only access to key management
- AJAX requests use WordPress nonces

## Technical Details

### Database Storage
- Keys stored as arrays in WordPress options
- Format: `kotacom_ai_{provider}_api_keys`
- Active key index: `kotacom_ai_{provider}_active_key_index`

### Rotation Logging
- Last 100 rotation events stored
- Includes timestamp, provider, reason
- Accessible via rotation statistics

### Error Handling
- Graceful fallback when no keys available
- Clear error messages for debugging
- Comprehensive logging for troubleshooting

## Supported Providers

✅ **Google AI (Gemini)** - Free tier available  
✅ **Groq** - Fast inference, free tier  
✅ **OpenAI** - GPT models, paid service  
✅ **Anthropic** - Claude models, free credits  
✅ **Cohere** - Free tier available  
✅ **Hugging Face** - Free inference API  
✅ **Together AI** - Free credits for new users  
✅ **Replicate** - Free credits available  
✅ **OpenRouter** - Aggregated models  
✅ **Perplexity AI** - Search-enhanced AI  

## Future Enhancements

- **Load Balancing**: Distribute requests across multiple keys
- **Usage Tracking**: Monitor quota consumption per key
- **Smart Rotation**: Prefer keys with higher remaining quotas
- **Provider Fallback**: Automatic fallback to different providers
- **Custom Cooldown**: Per-provider cooldown settings

## Support

If you encounter issues with the API Key Rotator:

1. Check the rotation statistics for patterns
2. Test individual keys to identify problems
3. Review WordPress error logs
4. Verify API key validity with providers
5. Ensure sufficient quotas on all accounts

The API Key Rotator significantly improves the reliability and efficiency of your AI content generation workflow by eliminating single points of failure and maximizing your API usage potential.