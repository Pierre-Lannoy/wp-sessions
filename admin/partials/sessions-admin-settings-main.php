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


// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
$url        = esc_url(
	add_query_arg(
		[
			'page' => 'pose-viewer',
		],
		admin_url( 'admin.php' )
	)
);
?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'sessions' ), POSE_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>
	<h2 class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'roles',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'roles' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings by Roles', 'sessions' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'misc',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'sessions' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'pose-settings',
					'tab'  => 'about',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'sessions' ); ?></a>
		<?php if ( class_exists( 'POSessions\Plugin\Feature\Wpcli' ) ) { ?>
            <a href="
            <?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'pose-settings',
						'tab'  => 'wpcli',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
            " class="nav-tab <?php echo 'wpcli' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;">WP-CLI</a>
		<?php } ?>
	</h2>
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'roles' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-roles.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/sessions-admin-settings-about.php'; ?>
	<?php } ?>
	<?php if ( 'wpcli' === $active_tab ) { ?>
		<?php wp_enqueue_style( POSE_ASSETS_ID ); ?>
		<?php echo do_shortcode( '[pose-wpcli]' ); ?>
	<?php } ?>
    <div style="min-height: 100px; position: fixed; bottom: 4vh; right: 4vw; z-index: 10000">
        <div style="background-color: #FFF; padding: 20px; border-radius: 4px; box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2)">
            <img style="width:60px; margin-right: 20px;" src="<?php echo \PerfOpsOne\Resources::get_sponsor_base64_logo(); ?>"/><div style="float: right; text-align: center;padding-top:10px">The PerfOps One plugins suite is sponsored by <br/><a href="https://hosterra.eu">Hosterra - Ethical & Sustainable Internet Hosting</a></div>
        </div>
    </div>
</div>
