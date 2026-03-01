<?php
/**
 * Customer Portal - Invoices Tab
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="crm-invoices-list">
    <p style="color: #999; text-align: center; padding: 40px;">
        <span style="font-size: 48px; display: block; margin-bottom: 10px;">📄</span>
        Rechnungen werden geladen...
    </p>
</div>

<script>
jQuery(document).ready(function($) {
    // Load invoices via AJAX
    $.ajax({
        type: 'POST',
        url: crmFrontend.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
        data: {
            action: 'crm_customer_get_invoices',
            nonce: crmFrontend.nonce || '<?php echo wp_create_nonce('crm_frontend_nonce'); ?>'
        },
        success: function(response) {
            if (response.success && response.data) {
                // Display invoices
                $('#crm-invoices-list').html(response.data);
            } else {
                $('#crm-invoices-list').html('<p style="color: #999; text-align: center; padding: 40px;">Keine Rechnungen gefunden.</p>');
            }
        }
    });
});
</script>
