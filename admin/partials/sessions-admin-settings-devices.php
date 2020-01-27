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

use POSessions\Plugin\Feature\Detector;
use UDD\DeviceDetector;

$intro = sprintf( esc_html__( '%1$s (engine version %2$s) allows to detect:', 'sessions' ), '<em>' . POSE_PRODUCT_NAME . '</em>', DeviceDetector::VERSION );

?>
<p><?php echo $intro; ?></p>

<h3><?php esc_html_e( 'Classes', 'sessions' ); ?></h3>
<p><?php echo Detector::get_definition( 'class' ) ?></p>
<h3><?php esc_html_e( 'Device Types', 'sessions' ); ?></h3>
<p><?php echo Detector::get_definition( 'device' ) ?></p>
<h3><?php esc_html_e( 'Client Types', 'sessions' ); ?></h3>
<p><?php echo Detector::get_definition( 'client' ) ?></p>
<h3><?php esc_html_e( 'Details', 'sessions' ); ?></h3>
<h4><?php esc_html_e( 'Operating Systems', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'os' ) ?></p>
<h4><?php esc_html_e( 'Browsers', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'browser' ) ?></p>
<h4><?php esc_html_e( 'Browser Engines', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'engine' ) ?></p>
<h4><?php esc_html_e( 'Application Libraries', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'library' ) ?></p>
<h4><?php esc_html_e( 'Media Players', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'player' ) ?></p>
<h4><?php esc_html_e( 'Mobile Applications', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'app' ) ?></p>
<h4><?php esc_html_e( 'PIMs', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'pim' ) ?></p>
<h4><?php esc_html_e( 'Feed Readers', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'reader' ) ?></p>
<h4><?php esc_html_e( 'Brands', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'brand' ) ?></p>
<h4><?php esc_html_e( 'Bots', 'sessions' ); ?></h4>
<p><?php echo Detector::get_definition( 'bot' ) ?></p>