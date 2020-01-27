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

use POSessions\API\Device;
use POSessions\System\Role;

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
if ( 'misc' === $active_tab && ! ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) {
	$active_tab = 'core';
}
$url  = esc_url(
	add_query_arg(
		[
			'page' => 'pose-viewer',
		],
		admin_url( 'tools.php' )
	)
);
$note = sprintf( __( 'Note: analytics reports are available via the <a href="%s">tools menu</a>.', 'sessions' ), $url );

?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'sessions' ), POSE_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>
	<h2 class="nav-tab-wrapper">
		<?php if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) { ?>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'misc',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'sessions' ); ?></a>
        <?php } ?>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'core',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'core' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'WordPress core', 'sessions' ); ?></a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'css',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'css' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'CSS', 'sessions' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'about',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'sessions' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'devices',
				),
				admin_url( 'options-general.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'devices' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'Devices', 'sessions' ); ?></a>
	</h2>
	<?php if ( 'misc' === $active_tab && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'core' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-core.php'; ?>
	<?php } ?>
	<?php if ( 'css' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-css.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-about.php'; ?>
	<?php } ?>
	<?php if ( 'devices' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-devices.php'; ?>
	<?php } ?>
    <p>&nbsp;</p>
    <em><?php echo $note;?></em>

</div>
