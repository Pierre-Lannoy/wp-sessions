<?php
/**
 * Provide a admin-facing tools for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use POSessions\Plugin\Feature\Sessions;

$scripts = new Sessions();
$scripts->prepare_items();

wp_enqueue_script( POSE_ASSETS_ID );
wp_enqueue_style( POSE_ASSETS_ID );

?>

<div class="wrap">
	<h2><?php echo esc_html__( 'Active Sessions Management', 'sessions' ); ?></h2>
	<?php settings_errors(); ?>
	<?php $scripts->views(); ?>
    <form id="sessions-tools" method="post" action="<?php echo $scripts->get_url(); ?>">
        <input type="hidden" name="page" value="sessions-tools" />
	    <?php $scripts->display(); ?>
    </form>
</div>
