<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use POSessions\System\Role;

wp_enqueue_script( 'pose-moment-with-locale' );
wp_enqueue_script( 'pose-daterangepicker' );
wp_enqueue_script( 'pose-chartist' );
wp_enqueue_script( 'pose-chartist-tooltip' );
wp_enqueue_script( POSE_ASSETS_ID );
wp_enqueue_style( POSE_ASSETS_ID );
wp_enqueue_style( 'pose-daterangepicker' );
wp_enqueue_style( 'pose-tooltip' );
wp_enqueue_style( 'pose-chartist' );
wp_enqueue_style( 'pose-chartist-tooltip' );

$simple_list   = [ 'classes', 'types', 'clients', 'libraries', 'applications', 'feeds', 'medias' ];
$extended_list = [ 'browsers', 'bots', 'devices', 'oses' ];

?>

<div class="wrap">
	<div class="pose-dashboard">
		<div class="pose-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="pose-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <div class="pose-row">
			<?php echo $analytics->get_main_chart() ?>
        </div>
        <div class="pose-row">
            <div class="pose-box pose-box-50-50-line">
				<?php echo $analytics->get_login_pie() ?>
				<?php echo $analytics->get_clean_pie() ?>
            </div>
        </div>
	</div>
</div>
