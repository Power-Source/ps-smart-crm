<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$stats_nonce=wp_create_nonce( "CRM_stats" );
do_action('WPsCRM_stats_grid_toolbar');
?>
<div id="statsTabstrip">
	<ul>
		<li class="k-state-active">
			<i class="glyphicon glyphicon-signal"></i> <?php _e('RECHNUNGSSTATISTIK','cpsmartcrm')?>
		</li>
        <li>
            <i class="glyphicon glyphicon-signal"></i> <?php _e('ANGEBOTE STATISTIK','cpsmartcrm')?>
        </li>
		<?php do_action('WPsCRM_add_tabs_to_stats_list'); ?>
	</ul>
	<div>
		<div class="statsGrid" id="grid-2"></div>
		<script>
				var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
            	jQuery(document).ready(function ($) {
            	//set datasource 2
				<?php do_action('WPsCRM_statsDatasource',"2")?>
				//grid output 2
				<?php do_action('WPsCRM_statsGrid',$stats_nonce,"2") ?>
            	});
		</script>
	</div>
    <div>
        <div class="statsGrid" id="grid-1"></div>
        <script>
				var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
            	jQuery(document).ready(function ($) {
            	//set datasource 1
				<?php do_action('WPsCRM_statsDatasource',"1")?>
				//grid output 1
				<?php do_action('WPsCRM_statsGrid',$stats_nonce,"1") ?>
            	});
        </script>
    </div>
	<?php do_action('WPsCRM_add_divs_to_stats_list'); ?>
</div>
<script>
	jQuery(document).ready(function ($) {
		var grid, filter, ds, gridID;
