<?php
/**
 * KE SEO Booster Pro - Diagnostic Script
 * 
 * This script helps diagnose potential issues with the plugin installation.
 * Run this script in your browser to check for compatibility issues.
 * 
 * @package KSEO\SEO_Booster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, define basic constants
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/../../../');
    }
}

// Start output buffering
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KE SEO Booster Pro - Diagnostic Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: #0073aa; color: white; padding: 20px; margin: -20px -20px 20px -20px; border-radius: 5px 5px 0 0; }
        .test { margin: 10px 0; padding: 15px; border-left: 4px solid #ddd; }
        .test.pass { border-left-color: #46b450; background: #f7fcf7; }
        .test.fail { border-left-color: #dc3232; background: #fcf7f7; }
        .test.warning { border-left-color: #ffb900; background: #fcfcf7; }
        .test h3 { margin: 0 0 10px 0; }
        .test.pass h3 { color: #46b450; }
        .test.fail h3 { color: #dc3232; }
        .test.warning h3 { color: #ffb900; }
        .details { font-family: monospace; background: #f1f1f1; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .recommendation { background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #0073aa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KE SEO Booster Pro - Diagnostic Report</h1>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <?php
        $issues = array();
        $warnings = array();
        $recommendations = array();

        // Test 1: PHP Version
        echo '<div class="test ' . (version_compare(PHP_VERSION, '8.0', '>=') ? 'pass' : 'fail') . '">';
        echo '<h3>PHP Version Check</h3>';
        echo '<p>Current PHP Version: <strong>' . PHP_VERSION . '</strong></p>';
        echo '<p>Required: PHP 8.0 or higher</p>';
        
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $issues[] = 'PHP version ' . PHP_VERSION . ' is below the required 8.0';
            echo '<div class="details">This plugin requires PHP 8.0 or higher to function properly.</div>';
        } else {
            echo '<div class="details">✓ PHP version is compatible</div>';
        }
        echo '</div>';

        // Test 2: WordPress Version
        if (function_exists('get_bloginfo')) {
            $wp_version = get_bloginfo('version');
            echo '<div class="test ' . (version_compare($wp_version, '6.0', '>=') ? 'pass' : 'fail') . '">';
            echo '<h3>WordPress Version Check</h3>';
            echo '<p>Current WordPress Version: <strong>' . $wp_version . '</strong></p>';
            echo '<p>Required: WordPress 6.0 or higher</p>';
            
            if (version_compare($wp_version, '6.0', '<')) {
                $issues[] = 'WordPress version ' . $wp_version . ' is below the required 6.0';
                echo '<div class="details">This plugin requires WordPress 6.0 or higher to function properly.</div>';
            } else {
                echo '<div class="details">✓ WordPress version is compatible</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="test warning">';
            echo '<h3>WordPress Version Check</h3>';
            echo '<p>Could not determine WordPress version (not running in WordPress context)</p>';
            echo '</div>';
        }

        // Test 3: Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = function_exists('wp_convert_hr_to_bytes') ? wp_convert_hr_to_bytes($memory_limit) : 0;
        
        echo '<div class="test ' . ($memory_limit_bytes >= 256 * 1024 * 1024 ? 'pass' : 'warning') . '">';
        echo '<h3>Memory Limit Check</h3>';
        echo '<p>Current Memory Limit: <strong>' . $memory_limit . '</strong></p>';
        echo '<p>Recommended: 256M or higher</p>';
        
        if ($memory_limit_bytes < 256 * 1024 * 1024) {
            $warnings[] = 'Memory limit is low: ' . $memory_limit;
            echo '<div class="details">Low memory limit may cause performance issues with AI features.</div>';
        } else {
            echo '<div class="details">✓ Memory limit is sufficient</div>';
        }
        echo '</div>';

        // Test 4: Required Files
        $plugin_dir = dirname(__FILE__);
        $required_files = array(
            'kseo-seo-booster.php' => 'Main plugin file',
            'autoload.php' => 'Autoloader',
            'inc/Service_Loader.php' => 'Service loader',
            'inc/Core/Plugin.php' => 'Core plugin class',
            'inc/Module/Meta_Box.php' => 'Meta box module',
            'inc/Module/Meta_Output.php' => 'Meta output module'
        );

        echo '<div class="test ' . (count(array_filter(array_map('file_exists', array_map(function($file) use ($plugin_dir) { return $plugin_dir . '/' . $file; }, array_keys($required_files))))) === count($required_files) ? 'pass' : 'fail') . '">';
        echo '<h3>Required Files Check</h3>';
        
        $missing_files = array();
        foreach ($required_files as $file => $description) {
            $file_path = $plugin_dir . '/' . $file;
            if (file_exists($file_path)) {
                echo '<p>✓ ' . $file . ' - ' . $description . '</p>';
            } else {
                $missing_files[] = $file;
                echo '<p>✗ ' . $file . ' - ' . $description . ' (MISSING)</p>';
            }
        }
        
        if (!empty($missing_files)) {
            $issues[] = 'Missing required files: ' . implode(', ', $missing_files);
        }
        echo '</div>';

        // Test 5: Directory Permissions
        $writable_dirs = array(
            'inc/' => 'Module directory',
            'assets/' => 'Assets directory',
            'languages/' => 'Languages directory'
        );

        echo '<div class="test pass">';
        echo '<h3>Directory Permissions Check</h3>';
        
        foreach ($writable_dirs as $dir => $description) {
            $dir_path = $plugin_dir . '/' . $dir;
            if (is_dir($dir_path)) {
                if (is_writable($dir_path)) {
                    echo '<p>✓ ' . $dir . ' - ' . $description . ' (writable)</p>';
                } else {
                    echo '<p>⚠ ' . $dir . ' - ' . $description . ' (not writable)</p>';
                    $warnings[] = 'Directory not writable: ' . $dir;
                }
            } else {
                echo '<p>⚠ ' . $dir . ' - ' . $description . ' (does not exist)</p>';
                $warnings[] = 'Directory missing: ' . $dir;
            }
        }
        echo '</div>';

        // Test 6: Conflicting Plugins
        if (function_exists('get_option')) {
            $active_plugins = get_option('active_plugins', array());
            $conflicting_plugins = array();
            
            foreach ($active_plugins as $plugin) {
                if (strpos($plugin, 'yoast') !== false || 
                    strpos($plugin, 'rankmath') !== false || 
                    strpos($plugin, 'all-in-one-seo') !== false ||
                    strpos($plugin, 'ke-lubricants-seo-booster') !== false) {
                    $conflicting_plugins[] = $plugin;
                }
            }
            
            echo '<div class="test ' . (empty($conflicting_plugins) ? 'pass' : 'warning') . '">';
            echo '<h3>Conflicting Plugins Check</h3>';
            
            if (empty($conflicting_plugins)) {
                echo '<p>✓ No conflicting SEO plugins detected</p>';
            } else {
                echo '<p>⚠ Conflicting plugins detected:</p>';
                foreach ($conflicting_plugins as $plugin) {
                    echo '<p>• ' . $plugin . '</p>';
                }
                $warnings[] = 'Conflicting SEO plugins detected';
            }
            echo '</div>';
        }

        // Summary
        echo '<div class="recommendation">';
        echo '<h3>Diagnostic Summary</h3>';
        
        if (empty($issues) && empty($warnings)) {
            echo '<p><strong>✓ All checks passed!</strong> Your system is ready to run KE SEO Booster Pro.</p>';
        } else {
            if (!empty($issues)) {
                echo '<p><strong>❌ Critical Issues Found:</strong></p>';
                echo '<ul>';
                foreach ($issues as $issue) {
                    echo '<li>' . esc_html($issue) . '</li>';
                }
                echo '</ul>';
            }
            
            if (!empty($warnings)) {
                echo '<p><strong>⚠ Warnings:</strong></p>';
                echo '<ul>';
                foreach ($warnings as $warning) {
                    echo '<li>' . esc_html($warning) . '</li>';
                }
                echo '</ul>';
            }
        }
        
        echo '<p><strong>Recommendations:</strong></p>';
        echo '<ul>';
        if (!empty($issues)) {
            echo '<li>Fix all critical issues before activating the plugin</li>';
        }
        if (!empty($warnings)) {
            echo '<li>Address warnings to ensure optimal performance</li>';
        }
        echo '<li>Backup your WordPress site before making any changes</li>';
        echo '<li>Test the plugin on a staging site first</li>';
        echo '</ul>';
        echo '</div>';
        ?>

        <div class="recommendation">
            <h3>Next Steps</h3>
            <ol>
                <li>If any critical issues are found, resolve them before proceeding</li>
                <li>Deactivate any conflicting SEO plugins</li>
                <li>Ensure your server meets the minimum requirements</li>
                <li>Activate the plugin through WordPress admin</li>
                <li>Configure the plugin settings</li>
            </ol>
        </div>
    </div>
</body>
</html>

<?php
// Flush output buffer
ob_end_flush();
?> 