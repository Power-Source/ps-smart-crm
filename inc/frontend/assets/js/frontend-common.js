/**
 * Frontend Common JavaScript
 * Shared utilities for Agent Dashboard & Customer Portal
 */

jQuery(document).ready(function($) {
    
    /**
     * Initialize Frontend
     */
    console.log('CRM Frontend initialized');
    
    /**
     * Format currency (German format)
     */
    window.formatCurrency = function(amount) {
        return parseFloat(amount).toLocaleString('de-DE', {
            style: 'currency',
            currency: 'EUR'
        });
    };
    
    /**
     * Format date (German format)
     */
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    };
    
    /**
     * Show notification
     */
    window.showNotification = function(message, type = 'success') {
        const alertClass = type === 'success' ? 'crm-alert success' : 
                          type === 'error' ? 'crm-alert error' : 
                          'crm-alert warning';
        
        const $alert = $(`
            <div class="${alertClass}" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px; animation: slideInRight 0.3s ease;">
                ${message}
            </div>
        `);
        
        $('body').append($alert);
        
        setTimeout(function() {
            $alert.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    };
    
    /**
     * Escape HTML
     */
    window.escapeHtml = function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };
    
    /**
     * Calculate elapsed time
     */
    window.calculateElapsedTime = function(startTime) {
        const startDate = new Date(startTime);
        const elapsed = Math.floor((new Date() - startDate) / 1000);
        
        const hours = Math.floor(elapsed / 3600);
        const minutes = Math.floor((elapsed % 3600) / 60);
        const seconds = elapsed % 60;
        
        return {
            hours: hours,
            minutes: minutes,
            seconds: seconds,
            formatted: String(hours).padStart(2, '0') + ':' + 
                      String(minutes).padStart(2, '0') + ':' + 
                      String(seconds).padStart(2, '0')
        };
    };
    
    /**
     * API Request Handler
     */
    window.crmApi = {
        call: function(action, data = {}, callback = null) {
            const crmFrontend = window.crmFrontend || {};
            
            $.ajax({
                type: 'POST',
                url: crmFrontend.ajaxurl,
                data: $.extend({
                    action: action,
                    nonce: crmFrontend.nonce
                }, data),
                success: function(response) {
                    if (callback) {
                        callback(response.success, response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('API Error:', error);
                    if (callback) {
                        callback(false, { message: 'API Error: ' + error });
                    }
                }
            });
        }
    };
});

/**
 * Animation: Slide In Right
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
