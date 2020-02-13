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

?>

<h2 id="sessions"><?php esc_html_e( 'Sessions Management', 'sessions' ); ?></h2>
<table class="form-table">
    <tbody>
        <tr id="posessions">
        <th><label for="sessions-limits-title"><?php esc_html_e( 'Current limits', 'sessions' ); ?></label></th>
        <td><?php echo $session->get_limits_as_text(); ?></td>
        </tr>
    </tbody>
</table>
