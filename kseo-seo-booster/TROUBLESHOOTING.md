# KE SEO Booster Pro - Troubleshooting Guide

## WordPress Crash Prevention and Resolution

### Common Causes of WordPress Crashes

#### 1. **PHP Version Incompatibility**
- **Problem**: Plugin requires PHP 8.0+, but server runs older version
- **Symptoms**: White screen, 500 errors, fatal errors
- **Solution**: 
  - Check PHP version: `<?php echo PHP_VERSION; ?>`
  - Contact hosting provider to upgrade to PHP 8.0+
  - Or use the older `ke-lubricants-seo-booster` plugin (PHP 7.4+)

#### 2. **WordPress Version Incompatibility**
- **Problem**: Plugin requires WordPress 6.0+, but site runs older version
- **Symptoms**: Plugin activation fails, admin errors
- **Solution**: Update WordPress to version 6.0 or higher

#### 3. **Memory Limit Issues**
- **Problem**: Low PHP memory limit causes timeouts
- **Symptoms**: Slow loading, timeouts, partial functionality
- **Solution**:
  - Increase memory limit in `wp-config.php`: `define('WP_MEMORY_LIMIT', '256M');`
  - Contact hosting provider to increase server memory limit

#### 4. **Plugin Conflicts**
- **Problem**: Multiple SEO plugins active simultaneously
- **Symptoms**: Database conflicts, duplicate meta tags, errors
- **Solution**:
  - Deactivate all other SEO plugins (Yoast, RankMath, etc.)
  - Clear database transients
  - Use only one SEO plugin at a time

#### 5. **Missing Files or Corrupted Installation**
- **Problem**: Incomplete plugin upload or missing files
- **Symptoms**: Fatal errors, missing functionality
- **Solution**:
  - Re-upload plugin files via FTP
  - Check file permissions (755 for directories, 644 for files)
  - Run the diagnostic script: `diagnostic.php`

#### 6. **Database Issues**
- **Problem**: Corrupted plugin options or conflicting data
- **Symptoms**: Settings not saving, unexpected behavior
- **Solution**:
  - Clear plugin transients: `DELETE FROM wp_options WHERE option_name LIKE '_transient_kseo_%'`
  - Reset plugin options if needed
  - Check database integrity

### Emergency Recovery Steps

#### If WordPress is Completely Down:

1. **Access via FTP/File Manager**
   - Navigate to `/wp-content/plugins/`
   - Rename the plugin folder to disable it
   - Example: `kseo-seo-booster` → `kseo-seo-booster-disabled`

2. **Via Database (if you have access)**
   ```sql
   UPDATE wp_options SET option_value = '' WHERE option_name = 'active_plugins';
   ```

3. **Via wp-config.php**
   - Add this line to disable all plugins:
   ```php
   define('DISABLE_PLUGINS', true);
   ```

#### If Admin is Accessible but Plugin is Causing Issues:

1. **Deactivate via Admin**
   - Go to Plugins → Installed Plugins
   - Deactivate the problematic plugin

2. **Clear Cache**
   - Clear any caching plugins
   - Clear browser cache
   - Clear server cache if applicable

3. **Check Error Logs**
   - Check WordPress debug log: `/wp-content/debug.log`
   - Check server error logs
   - Enable debugging in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

### Prevention Measures

#### Before Installing:

1. **Check System Requirements**
   - PHP 8.0 or higher
   - WordPress 6.0 or higher
   - 256MB+ memory limit
   - Run diagnostic script first

2. **Backup Everything**
   - Full site backup
   - Database backup
   - Plugin settings export

3. **Test on Staging**
   - Install on staging site first
   - Test all functionality
   - Check for conflicts

#### During Installation:

1. **Deactivate Other SEO Plugins**
   - Yoast SEO
   - RankMath
   - All in One SEO
   - Any other SEO plugins

2. **Clear Caches**
   - WordPress cache
   - Server cache
   - CDN cache

3. **Monitor Error Logs**
   - Keep debug logging enabled
   - Monitor server logs
   - Watch for any errors

### Diagnostic Tools

#### Built-in Diagnostic Script
Run `diagnostic.php` in your browser to check:
- PHP version compatibility
- WordPress version compatibility
- Memory limits
- Required files
- Directory permissions
- Conflicting plugins

#### Manual Checks

1. **PHP Version Check**
   ```php
   <?php echo PHP_VERSION; ?>
   ```

2. **Memory Limit Check**
   ```php
   <?php echo ini_get('memory_limit'); ?>
   ```

3. **WordPress Version Check**
   ```php
   <?php echo get_bloginfo('version'); ?>
   ```

4. **Active Plugins Check**
   ```php
   <?php print_r(get_option('active_plugins')); ?>
   ```

### Common Error Messages and Solutions

#### "Fatal error: Uncaught Error: Class 'KSEO\SEO_Booster\Core\Plugin' not found"
- **Cause**: Missing or corrupted plugin files
- **Solution**: Re-upload plugin files, check autoloader

#### "Allowed memory size exhausted"
- **Cause**: Low memory limit
- **Solution**: Increase memory limit to 256M or higher

#### "Plugin could not be activated due to an error"
- **Cause**: PHP version incompatibility or missing dependencies
- **Solution**: Check PHP version, ensure all files are present

#### "Database connection error"
- **Cause**: Plugin database operations failing
- **Solution**: Check database permissions, repair database

### Getting Help

#### Before Contacting Support:

1. **Gather Information**
   - WordPress version
   - PHP version
   - Server information
   - Error messages
   - Debug log contents

2. **Document the Issue**
   - When did it start?
   - What were you doing?
   - What error messages appeared?
   - What steps have you tried?

3. **Check Known Issues**
   - Review this troubleshooting guide
   - Check plugin documentation
   - Search for similar issues

#### Support Information to Provide:

- WordPress version
- PHP version
- Server type (Apache/Nginx)
- Memory limit
- Active plugins list
- Error log excerpts
- Steps to reproduce the issue

### Safe Mode Installation

If you're having persistent issues, try this safe installation method:

1. **Clean Installation**
   - Remove all plugin files
   - Clear database options
   - Fresh upload of plugin files

2. **Minimal Configuration**
   - Activate with default settings only
   - Test basic functionality
   - Gradually enable features

3. **Incremental Testing**
   - Test one module at a time
   - Monitor for errors
   - Document any issues

### Emergency Contact

If you're experiencing a critical WordPress crash:

1. **Immediate Action**: Disable the plugin via FTP
2. **Document**: Take screenshots of any error messages
3. **Contact**: Provide detailed information about your setup
4. **Recovery**: Follow the emergency recovery steps above

Remember: Always backup your site before making any changes! 