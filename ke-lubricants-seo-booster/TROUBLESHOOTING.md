# KE Lubricants SEO Booster - Troubleshooting Guide

This guide helps you resolve common issues with the KE Lubricants SEO Booster plugin.

## 🔍 Quick Diagnostic

Run the diagnostic script to check your system:

1. Upload the `diagnostic.php` file to your plugin directory
2. Visit `your-site.com/wp-content/plugins/ke-lubricants-seo-booster/diagnostic.php`
3. Review the diagnostic report

## 🚨 Critical Issues

### Plugin Won't Activate

**Symptoms**: 
- Fatal error on activation
- White screen after activation
- Plugin disappears from admin

**Causes & Solutions**:

#### 1. PHP Version Incompatibility
```
Fatal error: Uncaught Error: Class 'Exception' not found
```

**Solution**: Upgrade to PHP 7.4 or higher
- Contact your hosting provider
- Or use the older plugin version (requires PHP 7.4+)

#### 2. WordPress Version Incompatibility
```
Fatal error: Call to undefined function get_bloginfo()
```

**Solution**: Upgrade WordPress to version 5.0 or higher

#### 3. Memory Limit Issues
```
Fatal error: Allowed memory size exhausted
```

**Solution**: Increase PHP memory limit
```php
// Add to wp-config.php
define('WP_MEMORY_LIMIT', '256M');
```

#### 4. Missing Files
```
Fatal error: require_once(): Failed opening required file
```

**Solution**: 
- Re-upload all plugin files
- Check file permissions (should be 644 for files, 755 for directories)
- Ensure all required files are present

#### 5. Conflicting Plugins
```
Fatal error: Cannot redeclare class
```

**Solution**:
- Deactivate other SEO plugins temporarily
- Check for conflicting class names
- Use only one SEO plugin at a time

### Plugin Crashes WordPress

**Symptoms**:
- White screen on admin pages
- 500 server errors
- Site becomes inaccessible

**Solutions**:

#### 1. Emergency Recovery
```php
// Add to wp-config.php to disable plugins
define('WP_PLUGIN_DIR', '');
```

#### 2. Manual Plugin Deactivation
1. Access your database via phpMyAdmin
2. Go to `wp_options` table
3. Find `active_plugins` option
4. Remove the plugin entry
5. Clear any cached data

#### 3. File System Recovery
1. Connect via FTP/SFTP
2. Navigate to `/wp-content/plugins/`
3. Rename the plugin folder to disable it
4. Check error logs for specific issues

## 🔧 API Issues

### OpenAI API Problems

#### API Key Invalid
**Error**: `Invalid API key. Please check your OpenAI API key.`

**Solutions**:
1. Verify API key format: `sk-` followed by 32+ characters
2. Check API key in OpenAI dashboard
3. Ensure API key has sufficient credits
4. Test API key manually

#### Rate Limit Exceeded
**Error**: `Rate limit exceeded. Please wait before making another request.`

**Solutions**:
1. Wait 1 minute before retrying
2. Reduce API call frequency
3. Check OpenAI usage dashboard
4. Upgrade API plan if needed

#### Connection Timeout
**Error**: `Connection failed: cURL error 28`

**Solutions**:
1. Check internet connectivity
2. Increase server timeout settings
3. Contact hosting provider
4. Use a different network

### Google Ads API Problems

#### Credentials Invalid
**Error**: `Invalid Google Ads credentials`

**Solutions**:
1. Verify all credentials are correct
2. Check OAuth token expiration
3. Ensure proper API permissions
4. Test credentials manually

#### API Quota Exceeded
**Error**: `Quota exceeded for quota group`

**Solutions**:
1. Wait for quota reset
2. Reduce API call frequency
3. Upgrade Google Ads API plan
4. Use cached data when possible

## 🎯 Functionality Issues

### Meta Tags Not Appearing

**Symptoms**:
- SEO meta tags not in page source
- Social media tags missing
- Schema markup not generated

**Solutions**:

#### 1. Check Plugin Settings
- Verify meta tags are enabled
- Check post types are selected
- Ensure API keys are configured

#### 2. Theme Compatibility
```php
// Add to theme's functions.php
add_action('wp_head', 'your_custom_meta_function');
```

#### 3. Hook Priority
```php
// Ensure proper hook priority
remove_action('wp_head', 'wp_generator');
add_action('wp_head', 'your_meta_function', 1);
```

### AI Generation Not Working

**Symptoms**:
- Generate buttons not responding
- No content generated
- JavaScript errors

**Solutions**:

#### 1. Check JavaScript Console
1. Open browser developer tools
2. Check Console tab for errors
3. Look for AJAX request failures
4. Verify nonce validation

#### 2. AJAX Issues
```php
// Add to functions.php for debugging
add_action('wp_ajax_nopriv_keseo_generate_seo', 'debug_ajax');
add_action('wp_ajax_keseo_generate_seo', 'debug_ajax');
```

#### 3. API Response Issues
- Check API key validity
- Verify internet connectivity
- Review error logs for details

### Bulk Operations Failing

**Symptoms**:
- Bulk operations timeout
- Memory exhausted errors
- Partial processing only

**Solutions**:

#### 1. Increase Memory Limit
```php
// Add to wp-config.php
define('WP_MEMORY_LIMIT', '512M');
ini_set('memory_limit', '512M');
```

#### 2. Reduce Batch Size
- Process fewer posts at once
- Increase timeout settings
- Use background processing

#### 3. Database Optimization
```sql
-- Optimize WordPress database
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
```

## 🔍 Debugging Techniques

### Enable WordPress Debug
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SAVEQUERIES', true);
```

### Check Error Logs
```bash
# Check WordPress debug log
tail -f wp-content/debug.log

# Check server error logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

### Plugin-Specific Logging
The plugin logs all operations to the WordPress error log. Look for entries starting with:
- `KE Lubricants SEO Booster:`

### Database Queries
```php
// Add to functions.php to log queries
add_action('shutdown', function() {
    global $wpdb;
    error_log('Queries: ' . print_r($wpdb->queries, true));
});
```

## 🛠️ Performance Issues

### Slow Page Loads

**Causes**:
- Too many API calls
- Large database queries
- Memory exhaustion
- Plugin conflicts

**Solutions**:

#### 1. Optimize API Calls
- Enable caching
- Reduce API call frequency
- Use background processing
- Implement rate limiting

#### 2. Database Optimization
```sql
-- Clean up old transients
DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_%';
DELETE FROM wp_options WHERE option_name LIKE '_transient_%';
```

#### 3. Caching Implementation
```php
// Add object caching
if (function_exists('wp_cache_add')) {
    wp_cache_add('key', 'value', 'group', 3600);
}
```

### Memory Issues

**Symptoms**:
- `Allowed memory size exhausted`
- Slow performance
- Timeout errors

**Solutions**:

#### 1. Increase Memory Limits
```php
// In wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

#### 2. Optimize Plugin Code
- Reduce database queries
- Implement lazy loading
- Use efficient data structures
- Cache expensive operations

## 🔒 Security Issues

### Nonce Verification Failures

**Error**: `Security check failed`

**Solutions**:
1. Clear browser cache
2. Log out and log back in
3. Check for JavaScript errors
4. Verify nonce generation

### Permission Issues

**Error**: `You do not have sufficient permissions`

**Solutions**:
1. Check user capabilities
2. Verify user roles
3. Update user permissions
4. Check plugin settings

## 📞 Getting Help

### Before Contacting Support

1. **Check this troubleshooting guide**
2. **Review error logs**
3. **Test with default theme**
4. **Disable other plugins**
5. **Check system requirements**

### Information to Provide

When reporting issues, include:
- WordPress version
- PHP version
- Plugin version
- Error messages
- Steps to reproduce
- Server environment details

### Common Support Questions

#### Q: Why isn't my SEO content generating?
**A**: Check API key validity, internet connectivity, and error logs.

#### Q: My site is slow after installing the plugin
**A**: Increase memory limits, enable caching, and optimize database.

#### Q: Meta tags aren't appearing on my site
**A**: Check theme compatibility, plugin settings, and hook priorities.

#### Q: I'm getting API rate limit errors
**A**: Reduce API call frequency, upgrade API plan, or implement caching.

## 🔄 Recovery Procedures

### Complete Plugin Reset

1. **Deactivate plugin**
2. **Delete plugin files**
3. **Clean database**
```sql
DELETE FROM wp_options WHERE option_name LIKE 'keseo_%';
DELETE FROM wp_postmeta WHERE meta_key LIKE '_ke_seo_%';
```
4. **Reinstall plugin**
5. **Reconfigure settings**

### Emergency Mode

If the plugin is causing critical issues:

1. **Rename plugin folder** via FTP
2. **Clear all caches**
3. **Check error logs**
4. **Restore from backup**
5. **Contact support**

---

**Remember**: Always backup your site before making changes, and test in a staging environment first. 