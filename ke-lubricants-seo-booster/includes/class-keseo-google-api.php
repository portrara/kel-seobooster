<?php
/**
 * Google API Handler for KE Lubricants SEO Booster
 * 
 * Handles Google Ads API and Google Keyword Planner interactions.
 * 
 * @package KE_Lubricants_SEO_Booster
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google API Handler Class
 * 
 * CRASH PREVENTION: All API calls are wrapped in try-catch blocks
 * SECURITY: All inputs are validated and sanitized
 * LOGGING: All API interactions are logged for debugging
 * RATE LIMITING: Implements basic rate limiting to prevent API abuse
 */
class KESEO_Google_API {

    /**
     * Google Ads API base URL
     */
    const GOOGLE_ADS_API_URL = 'https://googleads.googleapis.com/v14';

    /**
     * Google Keyword Planner API URL
     */
    const KEYWORD_PLANNER_URL = 'https://googleads.googleapis.com/v14/customers';

    /**
     * Rate limiting settings
     */
    const RATE_LIMIT_REQUESTS = 5; // requests per minute
    const RATE_LIMIT_WINDOW = 60; // seconds

    /**
     * Initialize the Google API handler
     */
    public function __construct() {
        try {
            // Log successful initialization
            error_log('KE Lubricants SEO Booster: Google API handler initialized successfully');
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Google API handler initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test Google API connection
     * 
     * SECURITY: Validates credentials and tests connectivity
     * 
     * @return array Test results
     */
    public function test_connection() {
        try {
            // Get Google Ads credentials
            $credentials = $this->get_google_credentials();
            
            if (empty($credentials)) {
                return array(
                    'success' => false,
                    'message' => __('Google Ads credentials not configured.', 'ke-seo-booster')
                );
            }

            // Test API connectivity
            $response = $this->make_google_api_request('customers', array(), 'GET');
            
            if ($response['success']) {
                error_log('KE Lubricants SEO Booster: Google API connection test successful');
                return array(
                    'success' => true,
                    'message' => __('Google API connection is working.', 'ke-seo-booster')
                );
            } else {
                error_log('KE Lubricants SEO Booster: Google API connection test failed: ' . $response['message']);
                return array(
                    'success' => false,
                    'message' => $response['message']
                );
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Google API connection test failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => __('Google API test failed: ' . $e->getMessage(), 'ke-seo-booster')
            );
        }
    }

    /**
     * Get keyword suggestions from Google Keyword Planner
     * 
     * CRASH PREVENTION: Comprehensive error handling prevents fatal errors
     * SECURITY: All inputs are sanitized and validated
     * 
     * @param string $keyword The seed keyword
     * @param string $language The language code (default: en)
     * @param string $location The location ID (default: 2840 for US)
     * @return array Keyword suggestions
     */
    public function get_keyword_suggestions($keyword, $language = 'en', $location = '2840') {
        try {
            // Validate inputs
            $keyword = sanitize_text_field($keyword);
            $language = sanitize_text_field($language);
            $location = sanitize_text_field($location);

            if (empty($keyword)) {
                throw new Exception('Keyword is required');
            }

            // Check rate limiting
            if (!$this->check_rate_limit()) {
                throw new Exception('Rate limit exceeded. Please wait before making another request.');
            }

            // Get Google Ads credentials
            $credentials = $this->get_google_credentials();
            if (empty($credentials)) {
                throw new Exception('Google Ads credentials not configured');
            }

            // Create keyword plan
            $plan_data = array(
                'keywordPlanNetwork' => 'GOOGLE_SEARCH_AND_PARTNERS',
                'keywordPlanHistoricalMetrics' => array(
                    'searchVolume' => true,
                    'averageCpc' => true,
                    'competition' => true
                ),
                'keywordAnnotations' => array(
                    'concepts' => true
                ),
                'keywordIdeas' => array(
                    'keywordTexts' => array($keyword),
                    'language' => $language,
                    'geoTargetConstants' => array('geoTargetConstants/' . $location)
                )
            );

            $response = $this->make_google_api_request('keywordPlanIdea', $plan_data, 'POST');
            
            if ($response['success']) {
                $suggestions = $this->parse_keyword_suggestions($response['data']);
                
                error_log("KE Lubricants SEO Booster: Retrieved keyword suggestions for '{$keyword}'");
                
                return array(
                    'success' => true,
                    'suggestions' => $suggestions,
                    'keyword' => $keyword
                );
            } else {
                throw new Exception($response['message']);
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Keyword suggestions failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Get search volume data for keywords
     * 
     * @param array $keywords Array of keywords
     * @return array Search volume data
     */
    public function get_search_volume($keywords) {
        try {
            if (!is_array($keywords) || empty($keywords)) {
                throw new Exception('Keywords array is required');
            }

            // Sanitize keywords
            $keywords = array_map('sanitize_text_field', $keywords);
            $keywords = array_filter($keywords); // Remove empty items

            if (empty($keywords)) {
                throw new Exception('No valid keywords provided');
            }

            // Check rate limiting
            if (!$this->check_rate_limit()) {
                throw new Exception('Rate limit exceeded. Please wait before making another request.');
            }

            // Get Google Ads credentials
            $credentials = $this->get_google_credentials();
            if (empty($credentials)) {
                throw new Exception('Google Ads credentials not configured');
            }

            // Create keyword plan for search volume
            $plan_data = array(
                'keywordPlanNetwork' => 'GOOGLE_SEARCH_AND_PARTNERS',
                'keywordPlanHistoricalMetrics' => array(
                    'searchVolume' => true,
                    'averageCpc' => true,
                    'competition' => true
                ),
                'keywordIdeas' => array(
                    'keywordTexts' => $keywords
                )
            );

            $response = $this->make_google_api_request('keywordPlanIdea', $plan_data, 'POST');
            
            if ($response['success']) {
                $volume_data = $this->parse_search_volume($response['data']);
                
                error_log("KE Lubricants SEO Booster: Retrieved search volume for " . count($keywords) . " keywords");
                
                return array(
                    'success' => true,
                    'volume_data' => $volume_data
                );
            } else {
                throw new Exception($response['message']);
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Search volume failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Make Google API request
     * 
     * CRASH PREVENTION: Comprehensive error handling for network requests
     * SECURITY: Validates all responses and handles errors gracefully
     * 
     * @param string $endpoint The API endpoint
     * @param array $data The request data
     * @param string $method The HTTP method
     * @return array Response data
     */
    private function make_google_api_request($endpoint, $data = array(), $method = 'GET') {
        try {
            $credentials = $this->get_google_credentials();
            if (empty($credentials)) {
                return array(
                    'success' => false,
                    'message' => 'Google Ads credentials not configured'
                );
            }

            $url = self::GOOGLE_ADS_API_URL . '/' . $endpoint;
            
            $args = array(
                'method' => $method,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $credentials['access_token'],
                    'Content-Type' => 'application/json',
                    'developer-token' => $credentials['developer_token'],
                    'User-Agent' => 'KE-Lubricants-SEO-Booster/2.0.0'
                ),
                'timeout' => 30,
                'sslverify' => true
            );

            if ($method === 'POST' && !empty($data)) {
                $args['body'] = json_encode($data);
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                error_log('KE Lubricants SEO Booster: Google API request failed: ' . $response->get_error_message());
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message()
                );
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $response_data = json_decode($body, true);

            // Handle different status codes
            switch ($status_code) {
                case 200:
                    return array(
                        'success' => true,
                        'data' => $response_data
                    );
                    break;

                case 401:
                    return array(
                        'success' => false,
                        'message' => 'Invalid Google Ads credentials. Please check your configuration.'
                    );
                    break;

                case 403:
                    return array(
                        'success' => false,
                        'message' => 'Access denied. Please check your Google Ads permissions.'
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
                        'message' => 'Google Ads service temporarily unavailable. Please try again later.'
                    );
                    break;

                default:
                    $error_message = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'Unknown error';
                    return array(
                        'success' => false,
                        'message' => 'Google API Error: ' . $error_message
                    );
            }

        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Google API request exception: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get Google Ads credentials
     * 
     * @return array|false Credentials array or false if not configured
     */
    private function get_google_credentials() {
        try {
            $credentials = get_option('keseo_google_ads_credentials', array());
            
            if (empty($credentials) || 
                empty($credentials['client_id']) || 
                empty($credentials['client_secret']) || 
                empty($credentials['developer_token']) ||
                empty($credentials['refresh_token'])) {
                return false;
            }
            
            // Check if access token is expired and refresh if needed
            if (empty($credentials['access_token']) || 
                (isset($credentials['token_expiry']) && time() > $credentials['token_expiry'])) {
                $credentials = $this->refresh_access_token($credentials);
            }
            
            return $credentials;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to get Google credentials: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh Google access token
     * 
     * @param array $credentials Current credentials
     * @return array Updated credentials
     */
    private function refresh_access_token($credentials) {
        try {
            $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
                'body' => array(
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'refresh_token' => $credentials['refresh_token'],
                    'grant_type' => 'refresh_token'
                ),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['access_token'])) {
                $credentials['access_token'] = $data['access_token'];
                $credentials['token_expiry'] = time() + $data['expires_in'];
                
                // Update stored credentials
                update_option('keseo_google_ads_credentials', $credentials);
                
                error_log('KE Lubricants SEO Booster: Google access token refreshed successfully');
            } else {
                throw new Exception('Failed to refresh access token');
            }
            
            return $credentials;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to refresh access token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse keyword suggestions from API response
     * 
     * @param array $response_data API response data
     * @return array Parsed suggestions
     */
    private function parse_keyword_suggestions($response_data) {
        try {
            $suggestions = array();
            
            if (isset($response_data['results']) && is_array($response_data['results'])) {
                foreach ($response_data['results'] as $result) {
                    if (isset($result['text'])) {
                        $suggestion = array(
                            'keyword' => sanitize_text_field($result['text']),
                            'search_volume' => isset($result['keywordIdeaMetrics']['avgMonthlySearches']) ? 
                                intval($result['keywordIdeaMetrics']['avgMonthlySearches']) : 0,
                            'competition' => isset($result['keywordIdeaMetrics']['competition']) ? 
                                sanitize_text_field($result['keywordIdeaMetrics']['competition']) : 'UNKNOWN',
                            'avg_cpc' => isset($result['keywordIdeaMetrics']['avgCpc']['microAmount']) ? 
                                floatval($result['keywordIdeaMetrics']['avgCpc']['microAmount']) / 1000000 : 0
                        );
                        $suggestions[] = $suggestion;
                    }
                }
            }
            
            return $suggestions;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to parse keyword suggestions: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Parse search volume data from API response
     * 
     * @param array $response_data API response data
     * @return array Parsed volume data
     */
    private function parse_search_volume($response_data) {
        try {
            $volume_data = array();
            
            if (isset($response_data['results']) && is_array($response_data['results'])) {
                foreach ($response_data['results'] as $result) {
                    if (isset($result['text']) && isset($result['keywordIdeaMetrics'])) {
                        $volume_data[sanitize_text_field($result['text'])] = array(
                            'search_volume' => isset($result['keywordIdeaMetrics']['avgMonthlySearches']) ? 
                                intval($result['keywordIdeaMetrics']['avgMonthlySearches']) : 0,
                            'competition' => isset($result['keywordIdeaMetrics']['competition']) ? 
                                sanitize_text_field($result['keywordIdeaMetrics']['competition']) : 'UNKNOWN',
                            'avg_cpc' => isset($result['keywordIdeaMetrics']['avgCpc']['microAmount']) ? 
                                floatval($result['keywordIdeaMetrics']['avgCpc']['microAmount']) / 1000000 : 0
                        );
                    }
                }
            }
            
            return $volume_data;
            
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Failed to parse search volume: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Check rate limiting
     * 
     * @return bool Whether the request is allowed
     */
    private function check_rate_limit() {
        try {
            $transient_key = 'keseo_google_api_rate_limit';
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
            error_log('KE Lubricants SEO Booster: Google API rate limit check failed: ' . $e->getMessage());
            // Allow request if rate limiting fails
            return true;
        }
    }

    /**
     * Clear rate limiting cache
     * 
     * @return bool Success status
     */
    public function clear_rate_limit() {
        try {
            delete_transient('keseo_google_api_rate_limit');
            error_log('KE Lubricants SEO Booster: Google API rate limit cache cleared');
            return true;
        } catch (Exception $e) {
            error_log('KE Lubricants SEO Booster: Google API rate limit clear failed: ' . $e->getMessage());
            return false;
        }
    }
} 