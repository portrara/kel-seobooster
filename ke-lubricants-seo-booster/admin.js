jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Hide all tab content
        $('.tab-content').hide();
        
        // Show selected tab content
        var target = $(this).attr('href');
        $(target).show();
    });

    // API Key Test
    $('#test-api-key').on('click', function() {
        var $button = $(this);
        var $result = $('#api-test-result');
        var apiKey = $('input[name="keseo_openai_api_key"]').val();
        
        if (!apiKey) {
            $result.html('<span class="error">Please enter an API key first</span>');
            return;
        }
        
        $button.prop('disabled', true);
        $result.html('<span class="testing">Testing API connection...</span>');
        
        $.ajax({
            url: keseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'keseo_test_api',
                api_key: apiKey,
                nonce: keseo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span class="success">✓ API key is valid and working</span>');
                } else {
                    $result.html('<span class="error">✗ ' + response.data + '</span>');
                }
            },
            error: function() {
                $result.html('<span class="error">✗ Connection failed</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Google API Test
    $('#test-google-api').on('click', function() {
        var $button = $(this);
        var $result = $('#google-api-test-result');
        
        $button.prop('disabled', true);
        $result.html('<span class="testing">Testing Google API connection...</span>');
        
        $.ajax({
            url: keseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'keseo_test_google_api',
                nonce: keseo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span class="success">✓ ' + response.data + '</span>');
                } else {
                    $result.html('<span class="error">✗ ' + response.data + '</span>');
                }
            },
            error: function() {
                $result.html('<span class="error">✗ Connection failed</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Bulk Preview
    $('#bulk-preview').on('click', function() {
        var postTypes = $('#bulk-post-types').val() || ['post', 'page', 'product'];
        var mode = $('#bulk-mode').val();

        $.ajax({
            url: keseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'keseo_bulk_preview',
                post_types: postTypes,
                mode: mode,
                nonce: keseo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayBulkPreview(response.data.posts, response.data.total);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Connection failed');
            }
        });
    });

    // Generate SEO Content
    $('.generate-btn').on('click', function() {
        var $button = $(this);
        var $field = $button.closest('td').find('input, textarea');
        var fieldType = $button.data('field');
        var postId = $('#post_ID').val() || 0;
        
        if (!postId) {
            alert('Please save the post first before generating SEO content.');
            return;
        }
        
        $button.prop('disabled', true);
        $button.text('Generating...');
        
        $.ajax({
            url: keseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'keseo_generate_seo',
                post_id: postId,
                field: fieldType,
                nonce: keseo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $field.val(response.data.content);
                    updateCharacterCount($field);
                    $button.text('Generated ✓');
                    setTimeout(function() {
                        $button.text('Generate with AI');
                    }, 2000);
                } else {
                    alert('Error: ' + response.data);
                    $button.text('Generate with AI');
                }
            },
            error: function() {
                alert('Connection failed');
                $button.text('Generate with AI');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Generate All SEO Content
    $('#ke_seo_generate').on('click', function() {
        var $button = $(this);
        var $spinner = $button.siblings('.spinner');
        var postId = $('#post_ID').val() || 0;
        
        if (!postId) {
            alert('Please save the post first before generating SEO content.');
            return;
        }
        
        $button.prop('disabled', true);
        $spinner.show();
        
        // Generate title
        generateField('title', function() {
            // Generate description
            generateField('description', function() {
                // Generate keywords
                generateField('keywords', function() {
                    $button.prop('disabled', false);
                    $spinner.hide();
                    $button.text('Generated ✓');
                    setTimeout(function() {
                        $button.text('Generate All SEO Content');
                    }, 2000);
                });
            });
        });
        
        function generateField(fieldType, callback) {
            $.ajax({
                url: keseo_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'keseo_generate_seo',
                    post_id: postId,
                    field: fieldType,
                    nonce: keseo_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $field;
                        switch(fieldType) {
                            case 'title':
                                $field = $('#ke_seo_title');
                                break;
                            case 'description':
                                $field = $('#ke_seo_description');
                                break;
                            case 'keywords':
                                $field = $('#ke_seo_keywords');
                                break;
                        }
                        if ($field.length) {
                            $field.val(response.data.content);
                            updateCharacterCount($field);
                        }
                    }
                    if (callback) callback();
                },
                error: function() {
                    if (callback) callback();
                }
            });
        }
    });

    // Character counters
    $('input[maxlength], textarea[maxlength]').each(function() {
        addCharacterCounter($(this), $(this).attr('maxlength'));
    });

    // Update character counts on input
    $('input, textarea').on('input', function() {
        updateCharacterCount($(this));
    });

    // Helper functions
    function displayBulkPreview(posts, total) {
        var $container = $('#bulk-preview-results');
        var html = '<h3>Preview (' + total + ' posts)</h3><ul>';
        
        posts.forEach(function(postId) {
            html += '<li>Post ID: ' + postId + '</li>';
        });
        
        html += '</ul>';
        $container.html(html);
    }

    function updateCharacterCount($field) {
        var maxLength = $field.attr('maxlength');
        if (maxLength) {
            var currentLength = $field.val().length;
            var $counter = $field.siblings('.character-count');
            $counter.text(currentLength + '/' + maxLength);
            
            if (currentLength > maxLength * 0.9) {
                $counter.addClass('warning');
            } else {
                $counter.removeClass('warning');
            }
        }
    }

    function addCharacterCounter($field, maxLength) {
        if (!$field.siblings('.character-count').length) {
            $field.after('<span class="character-count">0/' + maxLength + '</span>');
        }
        updateCharacterCount($field);
    }

    // Initialize character counters
    updateCharacterCount($('#ke_seo_title'));
    updateCharacterCount($('#ke_seo_description'));
});