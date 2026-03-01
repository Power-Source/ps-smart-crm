<?php
/**
 * Agent Dashboard - Tasks Card
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

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
