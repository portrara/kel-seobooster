/**
 * KE SEO Booster Pro Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize character counters
    function initCharacterCounters() {
        $('.kseo-character-count').each(function() {
            var field = $(this).siblings('input, textarea');
            var maxLength = field.attr('maxlength');
            if (maxLength) {
                updateCharacterCount(field, maxLength);
            }
        });
    }
    
    // Update character count
    function updateCharacterCount(field, maxLength) {
        var count = field.val().length;
        var counter = field.siblings('.kseo-character-count');
        counter.text(count + '/' + maxLength);
        
        if (count > maxLength) {
            counter.addClass('over-limit');
        } else {
            counter.removeClass('over-limit');
        }
    }
    
    // Initialize on page load
    initCharacterCounters();
    
    // Character count on input
    $(document).on('input', 'input[maxlength], textarea[maxlength]', function() {
        var maxLength = $(this).attr('maxlength');
        if (maxLength) {
            updateCharacterCount($(this), maxLength);
        }
    });
    
    // Tab switching
    $(document).on('click', '.kseo-tab-button', function() {
        var tab = $(this).data('tab');
        var container = $(this).closest('.kseo-meta-box');
        
        container.find('.kseo-tab-button').removeClass('active');
        container.find('.kseo-tab-content').removeClass('active');
        
        $(this).addClass('active');
        container.find('#' + tab + '-tab').addClass('active');
    });
    
    // Generate with AI
    $(document).on('click', '.kseo-generate-btn', function() {
        var field = $(this).data('field');
        var button = $(this);
        var spinner = button.siblings('.spinner');
        
        button.prop('disabled', true);
        spinner.addClass('is-active');
        
        $.ajax({
            url: kseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kseo_generate_meta',
                nonce: kseo_ajax.nonce,
                field: field
            },
            success: function(response) {
                if (response.success) {
                    // Handle response based on field type
                    if (field === 'title') {
                        $('#kseo_title').val(response.data.title);
                        $('#kseo-preview-title').text(response.data.title);
                        updateCharacterCount($('#kseo_title'), 60);
                    } else if (field === 'description') {
                        $('#kseo_description').val(response.data.description);
                        $('#kseo-preview-description').text(response.data.description);
                        updateCharacterCount($('#kseo_description'), 160);
                    }
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(kseo_ajax.strings.error);
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
    // Generate all with AI
    $(document).on('click', '.kseo-generate-all-btn', function() {
        var button = $(this);
        var spinner = button.siblings('.spinner');
        
        button.prop('disabled', true);
        spinner.addClass('is-active');
        
        $.ajax({
            url: kseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kseo_generate_meta',
                nonce: kseo_ajax.nonce,
                field: 'all'
            },
            success: function(response) {
                if (response.success) {
                    $('#kseo_title').val(response.data.title);
                    $('#kseo_description').val(response.data.description);
                    $('#kseo_focus_keyword').val(response.data.focus_keyword);
                    $('#kseo-preview-title').text(response.data.title);
                    $('#kseo-preview-description').text(response.data.description);
                    updateCharacterCount($('#kseo_title'), 60);
                    updateCharacterCount($('#kseo_description'), 160);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(kseo_ajax.strings.error);
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
    // Test API connections
    $(document).on('click', '.kseo-test-api-btn', function() {
        var api = $(this).data('api');
        var button = $(this);
        
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: kseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kseo_test_api',
                nonce: kseo_ajax.nonce,
                api: api
            },
            success: function(response) {
                if (response.success) {
                    alert('API connection successful!');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('API connection failed. Please check your credentials.');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
    
    // Onboarding wizard
    $(document).on('click', '.kseo-next-step', function() {
        var currentStep = $(this).closest('.kseo-step');
        var nextStep = currentStep.next('.kseo-step');
        
        currentStep.removeClass('active');
        nextStep.addClass('active');
    });
    
    $(document).on('click', '.kseo-prev-step', function() {
        var currentStep = $(this).closest('.kseo-step');
        var prevStep = currentStep.prev('.kseo-step');
        
        currentStep.removeClass('active');
        prevStep.addClass('active');
    });
    
    $(document).on('click', '.kseo-complete-setup', function() {
        var button = $(this);
        
        button.prop('disabled', true).text('Completing...');
        
        $.ajax({
            url: kseo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kseo_complete_onboarding',
                nonce: kseo_ajax.nonce,
                formData: $('.kseo-onboarding-wizard form').serialize()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Setup failed. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false).text('Complete Setup');
            }
        });
    });
    
    // Settings page enhancements
    if ($('.kseo-settings-content').length) {
        // Module toggles
        $('input[name^="kseo_modules"]').on('change', function() {
            var moduleKey = $(this).attr('name').match(/\[([^\]]+)\]/)[1];
            var isEnabled = $(this).is(':checked');
            
            // Show/hide dependent settings
            if (moduleKey === 'ai_generator') {
                $('.kseo-openai-settings').toggle(isEnabled);
            }
            
            if (moduleKey === 'keyword_suggest') {
                $('.kseo-google-ads-settings').toggle(isEnabled);
            }
        });
        
        // Initialize module-dependent settings visibility
        $('input[name^="kseo_modules"]').each(function() {
            $(this).trigger('change');
        });
    }
    
    // Dashboard enhancements
    if ($('.kseo-dashboard').length) {
        // Refresh stats
        function refreshStats() {
            $.ajax({
                url: kseo_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'kseo_get_dashboard_stats',
                    nonce: kseo_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.kseo-stats-posts').text(response.data.posts_optimized);
                        $('.kseo-stats-keywords').text(response.data.total_keywords);
                    }
                }
            });
        }
        
        // Refresh stats every 30 seconds
        setInterval(refreshStats, 30000);
    }
}); 