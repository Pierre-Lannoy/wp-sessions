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

use POSessions\System\Cache;
use POSessions\System\Timezone;
use POSessions\Plugin\Feature\Capture;

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
	private static $statistics = 'sessions_statistics';

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
		add_action( 'shutdown', [ self::class, 'write' ], 11, 0 );
	}

	/**
	 * Write all buffers to database.
	 *
	 * @param boolean   $purge Optional. Purge od dates too.
	 * @since    1.0.0
	 */
	public static function write( $purge = true ) {
		if ( Option::network_get( 'analytics', false ) ) {
			self::write_current_to_database( Capture::get_stats() );
		}
		if ( $purge ) {
			self::purge();
		}
	}

	/**
	 * Effectively write a buffer element in the database.
	 *
	 * @param array $record The buffer to write.
	 * @since    1.0.0
	 */
	private static function write_current_to_database( $record ) {
		$record = self::maybe_add_stats( $record );
		if ( 0 === count( $record ) ) {
			return;
		}
		$datetime            = new \DateTime( 'now', Timezone::network_get() );
		$record['timestamp'] = $datetime->format( 'Y-m-d' );
		$field_insert        = [];
		$value_insert        = [];
		$value_update        = [];
		foreach ( $record as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			if ( 'timestamp' === $k ) {
				$value_insert[] = "'" . $v . "'";
			} else {
				$value_insert[] = (int) $v;
				$value_update[] = '`' . $k . '`=`' . $k . '` + ' . (int) $v;
			}
		}
		if ( count( $field_insert ) > 1 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . self::$statistics . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Adds misc stats to a buffer, if needed.
	 *
	 * @param array $record The buffer to write.
	 * @return  array   The completed buffer if needed.
	 * @since    1.0.0
	 */
	private static function maybe_add_stats( $record ) {
		$check = Cache::get_global( 'data/statcheck' );
		if ( isset( $check ) && $check && (int) $check + 6 * HOUR_IN_SECONDS > time() ) {
			return $record;
		}
		$record['cnt']        = 1;
		$record['u_ham']      = 0;
		$record['u_total']    = 0;
		$record['u_spam']     = 0;
		$record['u_active']   = 0;
		$record['u_sessions'] = 0;
		global $wpdb;
		$sql = 'SELECT COUNT(*) as u_cnt, user_status FROM ' . $wpdb->users . ' GROUP BY user_status';
		// phpcs:ignore
		$query = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $query ) && 0 < count( $query ) ) {
			$record['u_total'] = 0;
			foreach ( $query as $row ) {
				if ( 0 === (int) $row['user_status'] ) {
					$record['u_ham']    = $row['u_cnt'];
					$record['u_total'] += $row['u_cnt'];
				}
				if ( 1 === (int) $row['user_status'] ) {
					$record['u_spam']   = $row['u_cnt'];
					$record['u_total'] += $row['u_cnt'];
				}
			}
		}
		$sql = "SELECT COUNT(*) AS users, SUM( CAST( SUBSTRING(`meta_value`,3,POSITION('{' IN `meta_value`) - 4) AS UNSIGNED)) AS sessions FROM " . $wpdb->usermeta . " WHERE `meta_key`='session_tokens' and `meta_value`<>'' and `meta_value`<>'a:0:{}'";
		// phpcs:ignore
		$query = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $query ) && 0 < count( $query ) ) {
			$record['u_active']   = $query[0]['users'];
			$record['u_sessions'] = $query[0]['sessions'];
		}
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( 'Misc stats added.' );
		Cache::set_global( 'data/statcheck', time(), 'infinite' );
		return $record;
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
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$statistics ) );
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( 'Schema installed.' );
		} catch ( \Throwable $e ) {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( sprintf( 'Unable to create "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), [ 'code' => $e->getCode() ] );
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( 'Schema not installed.', $e->getCode() );
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
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( 'Table "%s" updated.', $wpdb->base_prefix . self::$statistics ) );
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->info( 'Schema updated.' );
		} catch ( \Throwable $e ) {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->alert( sprintf( 'Unable to update "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), [ 'code' => $e->getCode() ] );
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
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( 'No old records to delete.' );
		} elseif ( 1 === $count ) {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( '1 old record deleted.' );
			Cache::delete_global( 'data/oldestdate' );
		} else {
			\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( '%1$s old records deleted.', $count ) );
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
		$sql            .= " `cnt` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_total` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_ham` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_spam` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_active` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_suspended` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_banned` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `u_sessions` bigint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `expired` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `idle` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `forced` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `registration` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `delete` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `reset` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `logout` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `login_success` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `login_fail` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `login_block` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= ' UNIQUE KEY u_stat (timestamp)';
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
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$statistics ) );
		\DecaLog\Engine::eventsLogger( POSE_SLUG )->debug( 'Schema destroyed.' );
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
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $group;
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
		return self::get_grouped_list( $filter, 'timestamp', $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
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
		$result = Cache::get_global( $id );
		if ( $result ) {
			return $result;
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $where_extra . ' ' . $group . ' ' . $order . ( $limit > 0 ? ' LIMIT ' . $limit : '') .';';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			Cache::set_global( $id, $result, $cache ? 'infinite' : 'ephemeral' );
			return $result;
		}
		return [];
	}
}
