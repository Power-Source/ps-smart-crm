/**
 * Agent Dashboard JavaScript
 */

jQuery(document).ready(function($) {
    const crmFrontend = window.crmFrontend || {};
    
    // Load initial stats
    loadAgentStats();
    loadActiveTiming();
    
    // Refresh stats every 30 seconds
    setInterval(loadAgentStats, 30000);
    setInterval(loadActiveTiming, 10000);
    
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
    
    /**
     * Stop Timer Button Click
     */
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
    
    /**
     * Start Timer Display and Interval
     */
    function startTimerDisplay(trackingId, startTime) {
        $('#crm-btn-start-timer').hide();
        $('#crm-btn-stop-timer').data('tracking-id', trackingId).show();
        $('#crm-current-activity').show();
        
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
            
            $('#crm-timer-display').text(
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0')
            );
        }, 1000);
    }
    
    /**
     * Stop Timer Display
     */
    function stopTimerDisplay() {
        if (window.crmTimerInterval) {
            clearInterval(window.crmTimerInterval);
        }
        
        $('#crm-timer-display').text('00:00:00');
        $('#crm-btn-start-timer').show();
        $('#crm-btn-stop-timer').hide();
        $('#crm-current-activity').hide();
    }
});
