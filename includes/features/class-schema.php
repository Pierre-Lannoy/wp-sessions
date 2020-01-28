<?php
/**
 * POSessions schema
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\System\Option;
use POSessions\System\Database;
use POSessions\System\Environment;
use POSessions\System\Favicon;
use POSessions\System\Logger;
use POSessions\System\Cache;
use POSessions\System\Timezone;
use POSessions\Plugin\Feature\Detector;
use POSessions\Plugin\Feature\ClassTypes;
use POSessions\Plugin\Feature\DeviceTypes;
use POSessions\Plugin\Feature\ClientTypes;

/**
 * Define the schema functionality.
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Schema {

	/**
	 * Statistics table name.
	 *
	 * @since  1.0.0
	 * @var    string    $statistics    The statistics table name.
	 */
	private static $statistics = 'Sessions_statistics';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'shutdown', [ 'POSessions\Plugin\Feature\Schema', 'write' ], 10, 0 );
	}

	/**
	 * Write all buffers to database.
	 *
	 * @since    1.0.0
	 */
	public static function write() {
		if ( Option::network_get( 'analytics' ) ) {
			self::write_current_to_database();
		}
		self::purge();
	}

	/**
	 * Get the current channel tag.
	 *
	 * @return  string The current channel tag.
	 * @since 1.0.0
	 */
	private static function current_channel_tag() {
		return self::channel_tag( Environment::exec_mode() );
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 1.0.0
	 */
	public static function channel_tag( $id = 0 ) {
		if ( $id >= count( ChannelTypes::$channels ) ) {
			$id = 0;
		}
		return ChannelTypes::$channels[ $id ];
	}

	/**
	 * Effectively write a buffer element in the database.
	 *
	 * @since    1.0.0
	 */
	private static function write_current_to_database() {
		if ( ! Option::site_get( 'analytics') || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		$device              = Detector::new();
		$record              = [];
		$datetime            = new \DateTime( 'now', Timezone::network_get() );
		$record['timestamp'] = $datetime->format( 'Y-m-d' );
		$record['site']      = get_current_blog_id();
		$record['channel']   = strtolower( self::current_channel_tag() );
		$record['class']     = Detector::get_element( 'class', $device );
		$record['device']    = Detector::get_element( 'device', $device );
		$record['client']    = Detector::get_element( 'client', $device );
		if ( $device->class_is_bot && '' !== $device->bot_producer_name ) {
			$record['brand'] = substr( $device->bot_producer_name, 0, 40 );
		}
		if ( $device->class_is_bot && '' !== $device->bot_producer_url ) {
			$url_parts = wp_parse_url( $device->bot_producer_url );
			if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) ) {
				$record['url'] = substr( $url_parts['host'], 0, 2083 );
			}
		}
		if ( $device->class_is_bot && '' !== $device->bot_name ) {
			$record['name'] = substr( $device->bot_name, 0, 40 );
		}
		if ( ! $device->class_is_bot && '' !== $device->brand_short_name ) {
			$record['brand_id'] = substr( $device->brand_short_name, 0, 2 );
		}
		if ( ! $device->class_is_bot && '' !== $device->brand_name ) {
			$record['brand'] = substr( $device->brand_name, 0, 40 );
		}
		if ( ! $device->class_is_bot && '' !== $device->model_name ) {
			$record['model'] = substr( $device->model_name, 0, 40 );
		}
		if ( ! $device->class_is_bot && '' !== $device->client_short_name && 'UN' !== $device->client_short_name ) {
			$record['client_id'] = substr( $device->client_short_name, 0, 2 );
		}
		if ( ! $device->class_is_bot && '' !== $device->client_name ) {
			$record['name'] = substr( $device->client_name, 0, 40 );
		}
		if ( ! $device->class_is_bot && '' !== $device->client_version ) {
			$record['client_version'] = substr( $device->client_version, 0, 20 );
		}
		if ( ! $device->class_is_bot && 'UNK' !== $device->client_engine ) {
			$record['engine'] = substr( $device->client_engine, 0, 10 );
		}
		if ( ! $device->class_is_bot && 'UNK' !== $device->os_short_name ) {
			$record['os_id'] = substr( $device->os_short_name, 0, 3 );
		}
		if ( ! $device->class_is_bot && 'UNK' !== $device->os_name ) {
			$record['os'] = substr( $device->os_name, 0, 25 );
		}
		if ( ! $device->class_is_bot && 'UNK' !== $device->os_version ) {
			$record['os_version'] = substr( $device->os_version, 0, 20 );
		}
		$field_insert = [];
		$value_insert = [];
		$value_update = [];
		foreach ( $record as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
		}
		$value_update[] = '`hit`=hit + 1';
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . self::$statistics . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
		if ( array_key_exists( 'url', $record ) && '' !== $record['url'] ) {
			Favicon::get_raw( $record['url'], true );
		}
	}

	/**
	 * Initialize the schema.
	 *
	 * @since    1.1.0
	 */
	public function initialize() {
		global $wpdb;
		try {
			$this->create_table();
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$statistics ) );
			Logger::info( 'Schema installed.' );
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to create "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), $e->getCode() );
			Logger::alert( 'Schema not installed.', $e->getCode() );
		}
	}

	/**
	 * Update the schema.
	 *
	 * @since    1.1.0
	 */
	public function update() {
		global $wpdb;
		try {
			$this->create_table();
			Logger::debug( sprintf( 'Table "%s" updated.', $wpdb->base_prefix . self::$statistics ) );
			Logger::info( 'Schema updated.' );
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to update "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), $e->getCode() );
		}
	}

	/**
	 * Purge old records.
	 *
	 * @since    1.0.0
	 */
	private static function purge() {
		$days = (int) Option::network_get( 'history' );
		if ( ! is_numeric( $days ) || 30 > $days ) {
			$days = 30;
			Option::network_set( 'history', $days );
		}
		$database = new Database();
		$count    = $database->purge( self::$statistics, 'timestamp', 24 * $days );
		if ( 0 === $count ) {
			Logger::debug( 'No old records to delete.' );
		} elseif ( 1 === $count ) {
			Logger::debug( '1 old record deleted.' );
			Cache::delete_global( 'data/oldestdate' );
		} else {
			Logger::debug( sprintf( '%1$s old records deleted.', $count ) );
			Cache::delete_global( 'data/oldestdate' );
		}
	}

	/**
	 * Create the table.
	 *
	 * @since    1.0.0
	 */
	private function create_table() {
		global $wpdb;
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$statistics;
		$sql            .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql            .= " `site` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `hit` int(11) UNSIGNED NOT NULL DEFAULT '1',";
		$sql            .= " `class` enum('" . implode( "','", ClassTypes::$classes ) . "') NOT NULL DEFAULT 'other',";
		$sql            .= " `device` enum('" . implode( "','", DeviceTypes::$devices ) . "') NOT NULL DEFAULT 'other',";
		$sql            .= " `client` enum('" . implode( "','", ClientTypes::$clients ) . "') NOT NULL DEFAULT 'other',";
		$sql            .= " `brand_id` varchar(2) NOT NULL DEFAULT '-',";
		$sql            .= " `brand` varchar(40) NOT NULL DEFAULT '-',";  // May be device brand or bot producer.
		$sql            .= " `model` varchar(40) NOT NULL DEFAULT '-',";
		$sql            .= " `client_id` varchar(2) NOT NULL DEFAULT '-',";
		$sql            .= " `name` varchar(40) NOT NULL DEFAULT '-',";  // May be client name or bot name.
		$sql            .= " `client_version` varchar(20) NOT NULL DEFAULT '-',";
		$sql            .= " `engine` varchar(20) NOT NULL DEFAULT '-',";
		$sql            .= " `os_id` varchar(3) NOT NULL DEFAULT '-',";
		$sql            .= " `os` varchar(25) NOT NULL DEFAULT '-',";
		$sql            .= " `os_version` varchar(20) NOT NULL DEFAULT '-',";
		$sql            .= " `url` varchar(2083) NOT NULL DEFAULT '-',";
		$sql            .= ' UNIQUE KEY u_stat (timestamp, site, channel, class, device, client, brand_id, model, name, client_version, os_id, os_version)';
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Finalize the schema.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
		Logger::debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$statistics ) );
		Logger::debug( 'Schema destroyed.' );
	}

	/**
	 * Get "where" clause of a query.
	 *
	 * @param array $filters Optional. An array of filters.
	 * @return string The "where" clause.
	 * @since 1.0.0
	 */
	private static function get_where_clause( $filters = [] ) {
		$result = '';
		if ( 0 < count( $filters ) ) {
			$w = [];
			foreach ( $filters as $key => $filter ) {
				if ( is_array( $filter ) ) {
					$w[] = '`' . $key . '` IN (' . implode( ',', $filter ) . ')';
				} else {
					$w[] = '`' . $key . '`="' . $filter . '"';
				}
			}
			$result = 'WHERE (' . implode( ' AND ', $w ) . ')';
		}
		return $result;
	}

	/**
	 * Get the oldest date.
	 *
	 * @return  string   The oldest timestamp in the statistics table.
	 * @since    1.0.0
	 */
	public static function get_oldest_date() {
		$result = Cache::get_global( 'data/oldestdate' );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . self::$statistics . ' ORDER BY `timestamp` ASC LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) && array_key_exists( 'timestamp', $result[0] ) ) {
			Cache::set_global( 'data/oldestdate', $result[0]['timestamp'], 'infinite' );
			return $result[0]['timestamp'];
		}
		return '';
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   string  $group       Optional. The group of the query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @return  array   The grouped KPIs.
	 * @since    1.0.0
	 */
	public static function get_grouped_kpi( $filter, $group = '', $cache = true ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $group );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		global $wpdb;
		$sql = 'SELECT sum(hit) as sum_hit, class FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $group;
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   array   $distinct    Optional. The distinct fields to query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @return  array   The distinct KPIs.
	 * @since    1.0.0
	 */
	public static function get_distinct_kpi( $filter, $distinct = [], $cache = true ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . serialize( $distinct ) );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( 0 < count( $distinct ) ) {
			$select = ' DISTINCT ' . implode( ', ', $distinct );
		} else {
			$select = '*';
		}
		global $wpdb;
		$sql = 'SELECT ' . $select . ' FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get a time series.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The time series.
	 * @since    1.0.0
	 */
	public static function get_time_series( $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $limit = 0 ) {
		$data   = self::get_grouped_list( $filter, 'timestamp', $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
		$result = [];
		foreach ( $data as $datum ) {
			$result[ $datum['timestamp'] ] = $datum;
		}
		return $result;
	}

	/**
	 * Get the a grouped list.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   string  $group       Optional. The group of the query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   string  $order       Optional. The sort order of results.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The grouped list.
	 * @since    1.0.0
	 */
	public static function get_grouped_list( $filter, $group = '', $cache = true, $extra_field = '', $extras = [], $not = false, $order = '', $limit = 0 ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $group . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') . $order . (string) $limit);
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( $extras, "', '" ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT `timestamp`, sum(hit) as sum_hit, site, channel, class, device, client, brand_id, brand, model, client_id, name, client_version, engine, os_id, os, os_version, url FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $where_extra . ' ' . $group . ' ' . $order . ( $limit > 0 ? ' LIMIT ' . $limit : '') .';';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}
}
