<?php
/**
 * Agent Dashboard - Timetracking Card
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

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
