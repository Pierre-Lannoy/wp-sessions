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
		<?php if ( 'summary' === $analytics->type ) { ?>
            <div class="pose-row">
                <div class="pose-box pose-box-50-50-line">
					<?php echo $analytics->get_top_browser_box() ?>
					<?php echo $analytics->get_top_bot_box() ?>
                </div>
            </div>
            <div class="pose-row">
                <div class="pose-box pose-box-33-33-33-line">
					<?php echo $analytics->get_classes_box() ?>
					<?php echo $analytics->get_types_box() ?>
					<?php echo $analytics->get_clients_box() ?>
                </div>
            </div>
            <div class="pose-row">
                <div class="pose-box pose-box-50-50-line">
					<?php echo $analytics->get_top_device_box() ?>
					<?php echo $analytics->get_top_os_box() ?>
                </div>
            </div>
            <div class="pose-row">
                <div class="pose-box pose-box-25-25-25-25-line">
					<?php echo $analytics->get_libraries_box() ?>
					<?php echo $analytics->get_applications_box() ?>
					<?php echo $analytics->get_feeds_box() ?>
					<?php echo $analytics->get_medias_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'browser' === $analytics->type ) { ?>
            <div class="pose-row">
                <div class="pose-box pose-box-50-50-line">
					<?php echo $analytics->get_simpletop_version_box() ?>
					<?php echo $analytics->get_simpletop_os_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'os' === $analytics->type ) { ?>
            <div class="pose-row">
                <div class="pose-box pose-box-50-50-line">
					<?php echo $analytics->get_simpletop_version_box() ?>
					<?php echo $analytics->get_simpletop_browser_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'device' === $analytics->type ) { ?>
            <div class="pose-row">
                <div class="pose-box pose-box-50-50-line">
					<?php echo $analytics->get_simpletop_os_box() ?>
					<?php echo $analytics->get_simpletop_browser_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'browser' === $analytics->type || 'os' === $analytics->type || 'device' === $analytics->type || 'bot' === $analytics->type ) { ?>
			<?php echo $analytics->get_main_chart() ?>
		<?php } ?>








		<?php if ( in_array( (string) $analytics->type, array_merge( $simple_list, $extended_list ), true ) ) { ?>
            <div class="pose-row">
				<?php echo $analytics->get_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'summary' === $analytics->type && Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site) { ?>
            <div class="pose-row last-row">
	            <?php echo $analytics->get_sites_list() ?>
            </div>
		<?php } ?>
	</div>
</div>
