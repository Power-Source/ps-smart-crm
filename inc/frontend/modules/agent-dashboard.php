<?php
/**
 * Agent Dashboard Frontend Module
 * Zeiterfassung, Inbox, Aufgaben, Profil
 * 
 * Daten unterscheiden sich basierend auf User-Typ:
 * - Agent: Volle Funktionalität
 * - Nicht angemeldet: Leeres Dashboard
 * - Kunde: Zeigt Kundenportal-Hinweis
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Determine user type
$is_agent = is_user_logged_in() && (
    (function_exists('wpscrm_is_user_agent') && wpscrm_is_user_agent($user_id)) ||
    current_user_can('manage_options')
);

$is_customer = is_user_logged_in() ? !empty(wpscrm_get_customer_by_email($current_user->user_email)) : false;

// Get agent info (nur wenn Agent)
global $wpdb;
$agents_table = WPsCRM_TABLE . 'agents';
$agent = null;

if ($is_agent) {
    $agent = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $agents_table WHERE user_id = %d",
        $user_id
    ));
}
?>

<div class="crm-agent-dashboard" style="padding: 20px; background: #f5f5f5; min-height: 100vh;">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <?php if ($is_agent) : ?>
                <h1 style="margin: 0; color: #333;">👋 Willkommen, <?php echo esc_html($current_user->display_name); ?></h1>
            <?php elseif ($is_customer) : ?>
                <h1 style="margin: 0; color: #333;">🏢 Kundenzone</h1>
            <?php else : ?>
                <h1 style="margin: 0; color: #333;">💼 Agent Dashboard</h1>
            <?php endif; ?>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                <?php echo date_i18n('l, d. F Y'); ?>
            </p>
        </div>
        <div>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary">🚪 Logout</a>
            <?php else : ?>
                <a href="<?php echo wp_login_url(); ?>" class="button button-primary">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_agent) : ?>
        
        <!-- Agent Dashboard Content -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <!-- Time Tracking Card -->
            <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #2196F3;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3; display: flex; align-items: center;">
                    <span style="font-size: 24px; margin-right: 10px;">⏱️</span>
                    <span>Zeiterfassung</span>
                </h3>
                
                <!-- Timer Display -->
                <div id="crm-timer-display" style="font-size: 36px; font-weight: bold; color: #2196F3; text-align: center; margin: 20px 0; font-family: monospace;">
                    00:00:00
                </div>
                
                <!-- Current Activity -->
                <div id="crm-current-activity" style="background: #e3f2fd; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none;">
                    <div style="font-size: 12px; color: #666;">Aktuelle Tätigkeit</div>
                    <div id="crm-activity-name" style="font-weight: bold; color: #2196F3;"></div>
                </div>
                
                <!-- Controls -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button id="crm-btn-start-timer" class="button button-primary" style="width: 100%; cursor: pointer;">
                        ▶ Start
                    </button>
                    <button id="crm-btn-stop-timer" class="button button-secondary" style="width: 100%; cursor: pointer; display: none;">
                        ⏸ Stop
                    </button>
                </div>
                
                <!-- Statistics -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px;">
                        <div>
                            <div style="color: #666;">Stunden (Monat)</div>
                            <div id="crm-total-hours" style="font-weight: bold; font-size: 18px; color: #2196F3;">
                                0 h
                            </div>
                        </div>
                        <div>
                            <div style="color: #666;">Verdienst</div>
                            <div id="crm-total-earnings" style="font-weight: bold; font-size: 18px; color: #4caf50;">
                                €0,00
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Card -->
            <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ff9800;">
                <h3 style="margin: 0 0 15px 0; color: #ff9800; display: flex; align-items: center;">
                    <span style="font-size: 24px; margin-right: 10px;">✅</span>
                    <span>Meine Aufgaben</span>
                </h3>
                
                <div id="crm-tasks-list" style="max-height: 300px; overflow-y: auto;">
                    <!-- Tasks loaded via AJAX -->
                    <p style="color: #999; text-align: center; padding: 20px;">Laden...</p>
                </div>
                
                <a href="#" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #ff9800; text-decoration: none; border-top: 1px solid #eee;">
                    Alle Aufgaben →
                </a>
            </div>

            <!-- Inbox Card -->
            <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #4caf50;">
                <h3 style="margin: 0 0 15px 0; color: #4caf50; display: flex; align-items: center;">
                    <span style="font-size: 24px; margin-right: 10px;">📬</span>
                    <span>Mein Postfach</span>
                    <span id="crm-inbox-badge" class="crm-badge" style="background: #f44336; color: white; border-radius: 12px; padding: 2px 8px; font-size: 12px; margin-left: 10px;">0</span>
                </h3>
                
                <div id="crm-inbox-list" style="max-height: 300px; overflow-y: auto;">
                    <!-- Messages loaded via AJAX -->
                    <p style="color: #999; text-align: center; padding: 20px;">Laden...</p>
                </div>
                
                <a href="#" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #4caf50; text-decoration: none; border-top: 1px solid #eee;">
                    Vollständiges Postfach →
                </a>
            </div>
        </div>

        <!-- Profile Section -->
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h3 style="margin: 0 0 20px 0; color: #333;">👤 Mein Profil</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Name</label>
                        <p style="margin: 0; color: #333; font-weight: 500;">
                            <?php echo esc_html($current_user->display_name); ?>
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">E-Mail</label>
                        <p style="margin: 0; color: #333;">
                            <?php echo esc_html($current_user->user_email); ?>
                        </p>
                    </div>
                </div>
                
                <div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Stundensatz</label>
                        <p style="margin: 0; color: #333; font-weight: 500;">
                            €<?php echo esc_html($agent->hourly_rate ?? '—'); ?>/h
                        </p>
                    </div>
                    
                    <div>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_id); ?>" class="button button-small">
                            🔧 Profil bearbeiten
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($is_customer) : ?>
        
        <!-- Customer notice: Redirects to customer portal -->
        <div class="crm-card" style="background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1565c0;">🏢 Kundenbereich</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Du bist angemeldet als <strong>Kunde</strong>. Bitte nutze das Kundenportal für deine Rechnungen und Angebote.
            </p>
        </div>

    <?php else : ?>
        
        <!-- Guest/Non-logged-in user -->
        <div class="crm-card" style="background: #fff9c4; border-left: 4px solid #fbc02d; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #f57f17;">🔐 Mitarbeiter-Bereich</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Dieser Bereich ist nur für angemeldete Mitarbeiter sichtbar.
            </p>
            <a href="<?php echo wp_login_url(); ?>" class="button button-primary">
                🔐 Jetzt anmelden
            </a>
        </div>

    <?php endif; ?>
</div>

<style>
.crm-agent-dashboard {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.crm-card {
    transition: box-shadow 0.3s ease;
}

.crm-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.crm-badge {
    display: inline-block;
}
</style>

<script>
jQuery(document).ready(function($) {
    const crmFrontend = window.crmFrontend || {};
    
    <?php if ($is_agent) : ?>
        // Load initial stats (nur für Agenten)
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
                        $('#crm-total-earnings').text('€' + parseFloat(data.total_earnings).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    }
                }
            });
        }
        
        /**
         * Load or Get Active Timing
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
                        // Timer läuft
                        startTimerDisplay(response.data.tracking_id, response.data.start_time);
                    } else {
                        // Kein Timer aktiv
                        stopTimerDisplay();
                    }
                }
            });
        }
        
        /**
         * Start Timer Button
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
         * Stop Timer Button
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
         * Start Timer Display
         */
        function startTimerDisplay(trackingId, startTime) {
            $('#crm-btn-start-timer').hide();
            $('#crm-btn-stop-timer').data('tracking-id', trackingId).show();
            $('#crm-current-activity').show();
            
            // Timer aktualisieren
            const startDate = new Date(startTime);
            const timerInterval = setInterval(function() {
                const elapsed = Math.floor((new Date() - startDate) / 1000);
                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                const seconds = elapsed % 60;
                
                $('#crm-timer-display').text(
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0')
                );
                
                window.crmTimerInterval = timerInterval;
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
    <?php endif; ?>
});
</script>

<div class="crm-agent-dashboard" style="padding: 20px; background: #f5f5f5; min-height: 100vh;">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="margin: 0; color: #333;">👋 Willkommen, <?php echo esc_html($current_user->display_name); ?></h1>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                <?php echo date_i18n('l, d. F Y'); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary">🚪 Logout</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Time Tracking Card -->
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #2196F3;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3; display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 10px;">⏱️</span>
                <span>Zeiterfassung</span>
            </h3>
            
            <!-- Timer Display -->
            <div id="crm-timer-display" style="font-size: 36px; font-weight: bold; color: #2196F3; text-align: center; margin: 20px 0; font-family: monospace;">
                00:00:00
            </div>
            
            <!-- Current Activity -->
            <div id="crm-current-activity" style="background: #e3f2fd; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none;">
                <div style="font-size: 12px; color: #666;">Aktuelle Tätigkeit</div>
                <div id="crm-activity-name" style="font-weight: bold; color: #2196F3;"></div>
            </div>
            
            <!-- Controls -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button id="crm-btn-start-timer" class="button button-primary" style="width: 100%; cursor: pointer;">
                    ▶ Start
                </button>
                <button id="crm-btn-stop-timer" class="button button-secondary" style="width: 100%; cursor: pointer; display: none;">
                    ⏸ Stop
                </button>
            </div>
            
            <!-- Statistics -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px;">
                    <div>
                        <div style="color: #666;">Stunden (Monat)</div>
                        <div id="crm-total-hours" style="font-weight: bold; font-size: 18px; color: #2196F3;">
                            0 h
                        </div>
                    </div>
                    <div>
                        <div style="color: #666;">Verdienst</div>
                        <div id="crm-total-earnings" style="font-weight: bold; font-size: 18px; color: #4caf50;">
                            €0,00
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Card -->
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ff9800;">
            <h3 style="margin: 0 0 15px 0; color: #ff9800; display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 10px;">✅</span>
                <span>Meine Aufgaben</span>
            </h3>
            
            <div id="crm-tasks-list" style="max-height: 300px; overflow-y: auto;">
                <!-- Tasks loaded via AJAX -->
                <p style="color: #999; text-align: center; padding: 20px;">Laden...</p>
            </div>
            
            <a href="#" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #ff9800; text-decoration: none; border-top: 1px solid #eee;">
                Alle Aufgaben →
            </a>
        </div>

        <!-- Inbox Card -->
        <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #4caf50;">
            <h3 style="margin: 0 0 15px 0; color: #4caf50; display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 10px;">📬</span>
                <span>Mein Postfach</span>
                <span id="crm-inbox-badge" class="crm-badge" style="background: #f44336; color: white; border-radius: 12px; padding: 2px 8px; font-size: 12px; margin-left: 10px;">0</span>
            </h3>
            
            <div id="crm-inbox-list" style="max-height: 300px; overflow-y: auto;">
                <!-- Messages loaded via AJAX -->
                <p style="color: #999; text-align: center; padding: 20px;">Laden...</p>
            </div>
            
            <a href="#" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #4caf50; text-decoration: none; border-top: 1px solid #eee;">
                Vollständiges Postfach →
            </a>
        </div>
    </div>

    <!-- Profile Section -->
    <div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h3 style="margin: 0 0 20px 0; color: #333;">👤 Mein Profil</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Name</label>
                    <p style="margin: 0; color: #333; font-weight: 500;">
                        <?php echo esc_html($current_user->display_name); ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">E-Mail</label>
                    <p style="margin: 0; color: #333;">
                        <?php echo esc_html($current_user->user_email); ?>
                    </p>
                </div>
            </div>
            
            <div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Stundensatz</label>
                    <p style="margin: 0; color: #333; font-weight: 500;">
                        €<?php echo esc_html($agent->hourly_rate ?? '—'); ?>/h
                    </p>
                </div>
                
                <div>
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $user_id); ?>" class="button button-small">
                        🔧 Profil bearbeiten
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.crm-agent-dashboard {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.crm-card {
    transition: box-shadow 0.3s ease;
}

.crm-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.crm-badge {
    display: inline-block;
}
</style>

<script>
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
                    $('#crm-total-earnings').text('€' + parseFloat(data.total_earnings).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                }
            }
        });
    }
    
    /**
     * Load or Get Active Timing
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
                    // Timer läuft
                    startTimerDisplay(response.data.tracking_id, response.data.start_time);
                } else {
                    // Kein Timer aktiv
                    stopTimerDisplay();
                }
            }
        });
    }
    
    /**
     * Start Timer Button
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
     * Stop Timer Button
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
     * Start Timer Display
     */
    function startTimerDisplay(trackingId, startTime) {
        $('#crm-btn-start-timer').hide();
        $('#crm-btn-stop-timer').data('tracking-id', trackingId).show();
        $('#crm-current-activity').show();
        
        // Timer aktualisieren
        const startDate = new Date(startTime);
        const timerInterval = setInterval(function() {
            const elapsed = Math.floor((new Date() - startDate) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            
            $('#crm-timer-display').text(
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0')
            );
            
            window.crmTimerInterval = timerInterval;
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
</script>
