<?php
/**
 * OpenAI API Handler for KE Lubricants SEO Booster
 * 
 * Handles all OpenAI API interactions with comprehensive error handling and security.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenAI API Handler Class
 * 
 * CRASH PREVENTION: All API calls are wrapped in try-catch blocks
 * SECURITY: All inputs are validated and sanitized
 * LOGGING: All API interactions are logged for debugging
 * RATE LIMITING: Implements basic rate limiting to prevent API abuse
 */
class KESEO_OpenAI {

    /**
     * OpenAI API base URL
     */
    const API_BASE_URL = 'https://api.openai.com/v1';

    /**
     * Default model to use
     */
    const DEFAULT_MODEL = 'gpt-3.5-turbo';

    /**
     * Maximum tokens for responses
     */
    const MAX_TOKENS = 150;

    /**
     * Rate limiting settings
     */
    const RATE_LIMIT_REQUESTS = 10; // requests per minute
    const RATE_LIMIT_WINDOW = 60; // seconds

    /**
     * Initialize the OpenAI handler
     */
    public function __construct() {
        try {
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: OpenAI handler initialized successfully');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: OpenAI handler initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test OpenAI API key
     * 
     * SECURITY: Validates API key format and tests connectivity
     * 
     * @param string $api_key The API key to test
     * @return array Test results
     */
    public function test_api_key($api_key) {
        try {
            // Validate API key format
            if (empty($api_key)) {
                return array(
                    'success' => false,
                    'message' => __('API key is required.', 'ke-seo-booster')
                );
            }

            if (!preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key)) {
                return array(
                    'success' => false,
                    'message' => __('Invalid API key format. Please check your OpenAI API key.', 'ke-seo-booster')
                );
            }

            // Test API connectivity
            $response = $this->make_api_request('chat/completions', array(
                'model' => self::DEFAULT_MODEL,
                'messages' => array(
                    array('role' => 'user', 'content' => 'Hello')
                ),
                'max_tokens' => 10
            ), $api_key);

            if ($response['success']) {
                error_log('KE Lubricants SEO Booster: OpenAI API key test successful');
                return array(
                    'success' => true,
                    'message' => __('API key is valid and working.', 'ke-seo-booster')
                );
            } else {
                error_log('KE Lubricants SEO Booster: OpenAI API key test failed: ' . $response['message']);
                return array(
                    'success' => false,
                    'message' => $response['message']
                );
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: API key test failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => __('API test failed: ' . $e->getMessage(), 'ke-seo-booster')
            );
        }
    }

    /**
     * Generate SEO content using OpenAI
     * 
     * CRASH PREVENTION: Comprehensive error handling prevents fatal errors
     * SECURITY: All inputs are sanitized and validated
     * 
     * @param int $post_id The post ID
     * @param string $field The field to generate (title, description, keywords)
     * @return array Generation results
     */
    public function generate_seo_content($post_id, $field) {
        try {
            // Validate inputs
            $post_id = intval($post_id);
            $field = sanitize_text_field($field);

            if (!$post_id || !$field) {
                throw new Exception('Invalid parameters provided');
            }

            // Get post data
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception('Post not found');
            }

            // Get API key
            $api_key = get_option('keseo_openai_api_key');
            if (empty($api_key)) {
                throw new Exception('OpenAI API key not configured');
            }

            // Check rate limiting
            if (!$this->check_rate_limit()) {
                throw new Exception('Rate limit exceeded. Please wait before making another request.');
            }

            // Generate content based on field type
            $prompt = $this->get_prompt_for_field($post, $field);
            $response = $this->make_api_request('chat/completions', array(
                'model' => self::DEFAULT_MODEL,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => self::MAX_TOKENS,
                'temperature' => 0.7
            ), $api_key);

            if ($response['success']) {
                $content = trim($response['content']);
                
                // Validate generated content
                $content = $this->validate_generated_content($content, $field);
                
                error_log("KE Lubricants SEO Booster: Generated {$field} for post {$post_id}");
                
                return array(
                    'success' => true,
                    'content' => $content,
                    'field' => $field
                );
            } else {
                throw new Exception($response['message']);
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: SEO generation failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Make API request to OpenAI
     * 
     * CRASH PREVENTION: Comprehensive error handling for network requests
     * SECURITY: Validates all responses and handles errors gracefully
     * 
     * @param string $endpoint The API endpoint
     * @param array $data The request data
     * @param string $api_key The API key
     * @return array Response data
     */
    private function make_api_request($endpoint, $data, $api_key) {
        try {
            $url = self::API_BASE_URL . '/' . $endpoint;
            
            $response = wp_remote_post($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'KE-Lubricants-SEO-Booster/2.0.0'
                ),
                'body' => json_encode($data),
                'timeout' => 30,
                'sslverify' => true
            ));

            if (is_wp_error($response)) {
                error_log('KE Lubricants SEO Booster: API request failed: ' . $response->get_error_message());
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message()
                );
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            // Handle different status codes
            switch ($status_code) {
                case 200:
                    if (isset($data['choices'][0]['message']['content'])) {
                        return array(
                            'success' => true,
                            'content' => $data['choices'][0]['message']['content']
                        );
                    } else {
                        return array(
                            'success' => false,
                            'message' => 'Invalid response format from OpenAI'
                        );
                    }
                    break;

                case 401:
                    return array(
                        'success' => false,
                        'message' => 'Invalid API key. Please check your OpenAI API key.'
                    );
                    break;

                case 429:
                    return array(
                        'success' => false,
                        'message' => 'Rate limit exceeded. Please wait before making another request.'
                    );
                    break;

                case 500:
                case 502:
                case 503:
                    return array(
                        'success' => false,
                        'message' => 'OpenAI service temporarily unavailable. Please try again later.'
                    );
                    break;

                default:
                    $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
                    return array(
                        'success' => false,
                        'message' => 'API Error: ' . $error_message
                    );
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: API request exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get prompt for specific field
     * 
     * @param WP_Post $post The post object
     * @param string $field The field to generate
     * @return string The prompt
     */
    private function get_prompt_for_field($post, $field) {
        $title = sanitize_text_field($post->post_title);
        $content = wp_strip_all_tags($post->post_content);
        $content_preview = substr($content, 0, 500);

        switch ($field) {
            case 'title':
                return sprintf(
                    "Generate a compelling SEO title (maximum 60 characters) for this WordPress post:\n\n" .
                    "Post Title: %s\n\n" .
                    "Content Preview: %s\n\n" .
                    "Requirements:\n" .
                    "- Maximum 60 characters\n" .
                    "- Include primary keyword naturally\n" .
                    "- Compelling and click-worthy\n" .
                    "- No special characters or emojis\n\n" .
                    "SEO Title:",
                    $title,
                    $content_preview
                );

            case 'description':
                return sprintf(
                    "Generate a compelling SEO meta description (maximum 160 characters) for this WordPress post:\n\n" .
                    "Post Title: %s\n\n" .
                    "Content Preview: %s\n\n" .
                    "Requirements:\n" .
                    "- Maximum 160 characters\n" .
                    "- Include primary keyword naturally\n" .
                    "- Compelling and descriptive\n" .
                    "- Include call-to-action if appropriate\n" .
                    "- No special characters or emojis\n\n" .
                    "Meta Description:",
                    $title,
                    $content_preview
                );

            case 'keywords':
                return sprintf(
                    "Generate 3-5 relevant SEO keywords for this WordPress post:\n\n" .
                    "Post Title: %s\n\n" .
                    "Content Preview: %s\n\n" .
                    "Requirements:\n" .
                    "- 3-5 relevant keywords\n" .
                    "- Include primary keyword\n" .
                    "- Mix of short and long-tail keywords\n" .
                    "- Comma-separated format\n" .
                    "- No special characters or emojis\n\n" .
                    "Keywords:",
                    $title,
                    $content_preview
                );

            default:
                return sprintf(
                    "Generate SEO content for this WordPress post:\n\n" .
                    "Post Title: %s\n\n" .
                    "Content Preview: %s\n\n" .
                    "Please provide optimized SEO content.",
                    $title,
                    $content_preview
                );
        }
    }

    /**
     * Validate generated content
     * 
     * @param string $content The generated content
     * @param string $field The field type
     * @return string The validated content
     */
    private function validate_generated_content($content, $field) {
        // Remove any extra whitespace and newlines
        $content = trim($content);
        
        // Remove quotes if they wrap the entire content
        if (preg_match('/^["\'](.+)["\']$/s', $content, $matches)) {
            $content = $matches[1];
        }

        // Field-specific validation
        switch ($field) {
            case 'title':
                // Limit to 60 characters
                if (strlen($content) > 60) {
                    $content = substr($content, 0, 57) . '...';
                }
                break;

            case 'description':
                // Limit to 160 characters
                if (strlen($content) > 160) {
                    $content = substr($content, 0, 157) . '...';
                }
                break;

            case 'keywords':
                // Ensure it's a comma-separated list
                $keywords = array_map('trim', explode(',', $content));
                $keywords = array_filter($keywords); // Remove empty items
                $content = implode(', ', $keywords);
                break;
        }

        return $content;
    }

    /**
     * Check rate limiting
     * 
     * @return bool Whether the request is allowed
     */
    private function check_rate_limit() {
        try {
            $transient_key = 'keseo_openai_rate_limit';
            $current_requests = get_transient($transient_key);
            
            if ($current_requests === false) {
                // First request in the window
                set_transient($transient_key, 1, self::RATE_LIMIT_WINDOW);
                return true;
            }
            
            if ($current_requests >= self::RATE_LIMIT_REQUESTS) {
                return false;
            }
            
            // Increment request count
            set_transient($transient_key, $current_requests + 1, self::RATE_LIMIT_WINDOW);
            return true;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Rate limit check failed: ' . $e->getMessage());
            // Allow request if rate limiting fails
            return true;
        }
    }

    /**
     * Get API usage statistics
     * 
     * @return array Usage statistics
     */
    public function get_usage_stats() {
        try {
            $api_key = get_option('keseo_openai_api_key');
            if (empty($api_key)) {
                return array('error' => 'API key not configured');
            }

            $response = wp_remote_get(self::API_BASE_URL . '/usage', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 10
            ));

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                return array('error' => $data['error']['message']);
            }

            return $data;

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Usage stats failed: ' . $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Clear rate limiting cache
     * 
     * @return bool Success status
     */
    public function clear_rate_limit() {
        try {
            delete_transient('keseo_openai_rate_limit');
            error_log('KE Lubricants SEO Booster: Rate limit cache cleared');
            return true;
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Rate limit clear failed: ' . $e->getMessage());
            return false;
        }
    }
} 