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

$link = '';
if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
	$args         = [];
	$args['page'] = 'pose-manager';
	$args['id']   = $session->get_user_id();
	$link         = '<br/>' . '<a href="' . add_query_arg( $args, admin_url( 'admin.php' ) ) . '">' . esc_html__( 'Manage', 'sessions' ) . '</a>';
}

$conf = '';
if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
	$args         = [];
	$args['page'] = 'pose-settings';
	$args['tab']  = 'roles';
	$conf         = '<br/>' . '<a href="' . add_query_arg( $args, admin_url( 'admin.php' ) ) . '">' . esc_html__( 'Settings', 'sessions' ) . '</a>';
}

?>

<h2 id="sessions"><?php esc_html_e( 'Sessions Management', 'sessions' ); ?></h2>
<table class="form-table">
    <tbody>
        <tr id="posessions-limit">
            <th><label for="sessions-limits-title"><?php esc_html_e( 'Current limits', 'sessions' ); ?></label></th>
            <td><?php echo $session->get_limits_as_text() . $conf; ?></td>
        </tr>
        <tr id="posessions-count">
            <th><label for="sessions-count-title"><?php esc_html_e( 'Active sessions', 'sessions' ); ?></label></th>
            <td><?php echo '<strong>' . $session->get_sessions_count() . '</strong>' . $link; ?></td>
        </tr>
    </tbody>
</table>
