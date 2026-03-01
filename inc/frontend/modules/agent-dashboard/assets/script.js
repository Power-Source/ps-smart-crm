/**
 * Agent Dashboard JavaScript
 */

jQuery(document).ready(function($) {
    // Support both crmFrontend and crmAjax global objects
    const crmFrontend = window.crmFrontend || {};
    const crmAjax = window.crmAjax || window.crmFrontend || {};
    
    // Expose crmAjax globally if it came from crmFrontend
    if (!window.crmAjax && window.crmFrontend) {
        window.crmAjax = window.crmFrontend;
    }
    
    // Only load dashboard features if they exist
    if ($('#crm-total-hours').length) {
        loadAgentStats();
    }
    
    if ($('#crm-timer-display').length) {
        loadActiveTiming();
    }
    
    // Refresh stats every 30 seconds (only if element exists)
    if ($('#crm-total-hours').length) {
        setInterval(loadAgentStats, 30000);
    }
    
    if ($('#crm-timer-display').length) {
        setInterval(loadActiveTiming, 10000);
    }
    
    /**
     * Load Agent Statistics
     */
    function loadAgentStats() {
        $.ajax({
            type: 'POST',
            url: crmFrontend.ajaxurl,
            data: {
                action: 'crm_agent_get_stats',
                nonce: crmFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#crm-total-hours').text(data.total_hours + ' h');
                    $('#crm-total-earnings').text('€' + data.total_earnings);
                }
            }
        });
    }
    
    /**
     * Load Active Timing
     */
    function loadActiveTiming() {
        $.ajax({
            type: 'POST',
            url: crmFrontend.ajaxurl,
            data: {
                action: 'crm_agent_get_active_tracking',
                nonce: crmFrontend.nonce
            },
            success: function(response) {
                if (response.success && response.data.tracking_id) {
                    startTimerDisplay(response.data.tracking_id, response.data.start_time);
                } else {
                    stopTimerDisplay();
                }
            }
        });
    }
    
    /**
     * Start Timer Button Click
     */
    if ($('#crm-btn-start-timer').length) {
        $('#crm-btn-start-timer').on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                type: 'POST',
                url: crmFrontend.ajaxurl,
                data: {
                    action: 'crm_agent_timetracking_toggle',
                    action_type: 'start',
                    nonce: crmFrontend.nonce,
                    project_id: 0,
                    task_id: 0
                },
                success: function(response) {
                    if (response.success) {
                        startTimerDisplay(response.data.tracking_id, response.data.start_time);
                        loadAgentStats();
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                }
            });
        });
    }
    
    /**
     * Stop Timer Button Click
     */
    if ($('#crm-btn-stop-timer').length) {
        $('#crm-btn-stop-timer').on('click', function(e) {
            e.preventDefault();
            
            const trackingId = $(this).data('tracking-id');
            
            $.ajax({
                type: 'POST',
                url: crmFrontend.ajaxurl,
                data: {
                    action: 'crm_agent_timetracking_toggle',
                    action_type: 'stop',
                    tracking_id: trackingId,
                    nonce: crmFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        stopTimerDisplay();
                        loadAgentStats();
                    }
                }
            });
        });
    }
    
    /**
     * Start Timer Display and Interval
     */
    function startTimerDisplay(trackingId, startTime) {
        if ($('#crm-btn-start-timer').length) $('#crm-btn-start-timer').hide();
        if ($('#crm-btn-stop-timer').length) $('#crm-btn-stop-timer').data('tracking-id', trackingId).show();
        if ($('#crm-current-activity').length) $('#crm-current-activity').show();
        
        // Clear existing interval
        if (window.crmTimerInterval) {
            clearInterval(window.crmTimerInterval);
        }
        
        const startDate = new Date(startTime);
        
        window.crmTimerInterval = setInterval(function() {
            const elapsed = Math.floor((new Date() - startDate) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            
            if ($('#crm-timer-display').length) {
                $('#crm-timer-display').text(
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0')
                );
            }
        }, 1000);
    }
    
    /**
     * Stop Timer Display
     */
    function stopTimerDisplay() {
        if (window.crmTimerInterval) {
            clearInterval(window.crmTimerInterval);
        }
        
        if ($('#crm-timer-display').length) $('#crm-timer-display').text('00:00:00');
        if ($('#crm-btn-start-timer').length) $('#crm-btn-start-timer').show();
        if ($('#crm-btn-stop-timer').length) $('#crm-btn-stop-timer').hide();
        if ($('#crm-current-activity').length) $('#crm-current-activity').hide();
    }
});
