<?php
/**
 * Service Loader for KE SEO Booster Pro
 * 
 * Manages all modules and their initialization based on settings.
 * 
 * @package KSEO\SEO_Booster
 */

namespace KSEO\SEO_Booster;

/**
 * Service Loader Class
 * 
 * @since 2.0.0
 */
class Service_Loader {
    
    /**
     * Available modules
     * 
     * @var array
     */
    private $modules = array(
        'meta_box' => 'Module\Meta_Box',
        'meta_output' => 'Module\Meta_Output',
        'social_tags' => 'Module\Social_Tags',
        'schema' => 'Module\Schema',
        'sitemap' => 'Module\Sitemap',
        'robots' => 'Module\Robots',
        'keyword_suggest' => 'Module\Keyword_Suggest',
        'ai_generator' => 'Module\AI_Generator',
        'bulk_audit' => 'Module\Bulk_Audit',
        'internal_link' => 'Module\Internal_Link',
        'api' => 'Module\API',
        'settings' => 'Module\Settings'
    );
    
    /**
     * Loaded module instances
     * 
     * @var array
     */
    private $loaded_modules = array();
    
    /**
     * Initialize the service loader
     * 
     * @since 2.0.0
     */
    public function __construct() {
        $this->load_enabled_modules();
        $this->register_wp_cli_commands();
    }
    
    /**
     * Load enabled modules based on settings
     * 
     * @since 2.0.0
     */
    private function load_enabled_modules() {
        $enabled_modules = get_option('kseo_modules', array());
        
        foreach ($this->modules as $module_key => $module_class) {
            // Always load settings module
            if ($module_key === 'settings') {
                $this->load_module($module_key, $module_class);
                continue;
            }
            
            // Load other modules only if enabled
            if (isset($enabled_modules[$module_key]) && $enabled_modules[$module_key]) {
                $this->load_module($module_key, $module_class);
            }
        }
    }
    
    /**
     * Register WP-CLI commands if WP_CLI exists
     * 
     * @since 2.0.0
     */
    private function register_wp_cli_commands() {
        if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
            $commands = new \KSEO\SEO_Booster\CLI\Commands();
            
            \WP_CLI::add_command('kseo regenerate_meta', array($commands, 'regenerate_meta'));
            \WP_CLI::add_command('kseo regenerate_sitemap', array($commands, 'regenerate_sitemap'));
            
            error_log('KE SEO Booster Pro: WP-CLI commands registered');
        }
    }
    
    /**
     * Load a specific module
     * 
     * @since 2.0.0
     * @param string $module_key The module key.
     * @param string $module_class The module class name.
     */
    private function load_module($module_key, $module_class) {
        try {
            $full_class_name = 'KSEO\\SEO_Booster\\' . $module_class;
            
            if (class_exists($full_class_name)) {
                $this->loaded_modules[$module_key] = new $full_class_name();
                error_log("KE SEO Booster Pro: Loaded module {$module_key}");
            } else {
                error_log("KE SEO Booster Pro: Module class {$full_class_name} not found");
            }
        } catch (\Exception $e) {
            error_log("KE SEO Booster Pro: Failed to load module {$module_key}: " . $e->getMessage());
        }
    }
    
    /**
     * Get a loaded module instance
     * 
     * @since 2.0.0
     * @param string $module_key The module key.
     * @return object|null The module instance or null if not loaded.
     */
    public function get_module($module_key) {
        return isset($this->loaded_modules[$module_key]) ? $this->loaded_modules[$module_key] : null;
    }
    
    /**
     * Get all loaded modules
     * 
     * @since 2.0.0
     * @return array The loaded modules array.
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }
    
    /**
     * Check if a module is loaded
     * 
     * @since 2.0.0
     * @param string $module_key The module key.
     * @return bool True if module is loaded, false otherwise.
     */
    public function is_module_loaded($module_key) {
        return isset($this->loaded_modules[$module_key]);
    }
    
    /**
     * Get available modules
     * 
     * @since 2.0.0
     * @return array The available modules array.
     */
    public function get_available_modules() {
        return $this->modules;
    }
} 