<?php
/**
 * Device detector analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace POSessions\Plugin\Feature;

use POSessions\Plugin\Feature\Schema;
use POSessions\System\Blog;
use POSessions\System\Cache;
use POSessions\System\Date;
use POSessions\System\Conversion;
use POSessions\System\Role;

use POSessions\System\L10n;
use POSessions\System\Http;
use POSessions\System\Favicon;
use POSessions\System\Timezone;
use POSessions\System\UUID;
use Feather;
use POSessions\System\Environment;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The dashboard type.
	 *
	 * @since  1.0.0
	 * @var    string    $title    The dashboard type.
	 */
	public $type = '';

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in days.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in days.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $type    The type of analytics ().
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @since    1.0.0
	 */
	public function __construct( $type, $start, $end, $reload ) {
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start    = $start;
		$this->end      = $end;
		$this->type     = $type;
		$this->timezone = Timezone::network_get();
		$datetime       = new \DateTime( 'now', $this->timezone );
		$this->is_today = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		$start          = new \DateTime( $this->start, $this->timezone );
		$end            = new \DateTime( $this->end, $this->timezone );
		$start->sub( new \DateInterval( 'P1D' ) );
		$end->sub( new \DateInterval( 'P1D' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration = $delta->days + 1;
		if ( $start === $end ) {
			$this->previous[] = "timestamp='" . $start->format( 'Y-m-d' ) . "'";
		} else {
			$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d' ) . "' and timestamp<='" . $end->format( 'Y-m-d' ) . "'";
		}
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'login':
			case 'clean':
				return $this->query_pie( $query, (int) $queried );
			case 'main-chart':
				return $this->query_chart();
		}
		return [];
	}

	/**
	 * Query statistics pie.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$uuid  = UUID::generate_unique_id( 5 );
		$data  = Schema::get_grouped_list( $this->filter, '', ! $this->is_today );
		$names = [
			'login_success' => esc_html__( 'Successful', 'sessions' ),
			'login_fail'    => esc_html__( 'Failed', 'sessions' ),
			'login_block'   => esc_html__( 'Blocked', 'sessions' ),
			'expired'       => esc_html__( 'Expired', 'sessions' ),
			'idle'          => esc_html__( 'Idle', 'sessions' ),
			'forced'        => esc_html__( 'Overridden', 'sessions' ),
		];
		switch ( $type ) {
			case 'login':
				$selectors = [ 'login_success', 'login_fail', 'login_block' ];
				break;
			case 'clean':
				$selectors = [ 'expired', 'idle', 'forced' ];
				break;
		}
		$val = 0;
		if ( 0 < count( $data ) ) {
			foreach ( $data as $row ) {
				foreach ( $selectors as $selector ) {
					$val += (int) $row[ $selector ];
				}
			}
		}
		if ( 0 < count( $data ) && 0 !== $val ) {
			$total  = 0;
			$other  = 0;
			$values = [];
			foreach ( $selectors as $selector ) {
				$values[ $selector ] = 0;
			}
			foreach ( $data as $row ) {
				foreach ( $selectors as $selector ) {
					$total               = $total + $row[ $selector ];
					$values[ $selector ] = $values[ $selector ] + $row[ $selector ];
				}
			}
			$cpt    = 0;
			$labels = [];
			$series = [];
			while ( $cpt < $limit ) {
				if ( 0 < $total ) {
					$percent = round( 100 * $values[ $selectors[ $cpt ] ] / $total, 1 );
				} else {
					$percent = 100;
				}
				if ( 0.1 > $percent ) {
					$percent = 0.1;
				}
				$labels[] = $names[ $selectors[ $cpt ] ];
				$series[] = [
					'meta'  => $names[ $selectors[ $cpt ] ],
					'value' => (float) $percent,
				];
				++$cpt;
			}
			if ( 0 < $other ) {
				if ( 0 < $total ) {
					$percent = round( 100 * $other / $total, 1 );
				} else {
					$percent = 100;
				}
				if ( 0.1 > $percent ) {
					$percent = 0.1;
				}
				$labels[] = esc_html__( 'Other', 'sessions' );
				$series[] = [
					'meta'  => esc_html__( 'Other', 'sessions' ),
					'value' => (float) $percent,
				];
			}
			$result  = '<div class="pose-pie-box">';
			$result .= '<div class="pose-pie-graph">';
			$result .= '<div class="pose-pie-graph-handler-120" id="pose-pie-' . $type . '"></div>';
			$result .= '</div>';
			$result .= '<div class="pose-pie-legend">';
			foreach ( $labels as $key => $label ) {
				$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
				$result .= '<div class="pose-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
			}
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
				[
					'labels' => $labels,
					'series' => $series,
				]
			) . ';';
			$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
			$result .= ' var option' . $uuid . ' = {width: 120, height: 120, showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
			$result .= ' new Chartist.Pie("#pose-pie-' . $type . '", data' . $uuid . ', option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="pose-pie-box">';
			$result .= '<div class="pose-pie-graph" style="margin:0 !important;">';
			$result .= '<div class="pose-pie-graph-nodata-handler-120" id="pose-pie-' . $type . '"><span style="position: relative; top: 47px;">-&nbsp;' . esc_html__( 'No Data', 'sessions' ) . '&nbsp;-</span></div>';
			$result .= '</div>';
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
		}
		return [ 'pose-' . $type => $result ];
	}

	/**
	 * Query statistics chart.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid             = UUID::generate_unique_id( 5 );
		$query            = Schema::get_time_series( $this->filter, ! $this->is_today );
		$item             = [];
		$item['user']     = [ 'u_ham', 'u_spam' ];
		$item['session']  = [ 'u_active', 'expired', 'forced', 'idle' ];
		$item['turnover'] = [ 'registration', 'delete' ];
		$item['log']      = [ 'logout', 'login_success', 'login_fail', 'login_block' ];
		$item['password'] = [ 'reset' ];
		$data             = [];
		$series           = [];
		$boundaries       = [];
		$json             = [];
		foreach ( $item as $selector => $array ) {
			$boundaries[ $selector ] = [
				'max'    => 0,
				'factor' => 1,
				'order'  => $item[ $selector ],
			];
		}
		// Data normalization.
		if ( 0 !== count( $query ) ) {
			foreach ( $query as $row ) {
				$record = [];
				foreach ( $row as $k => $v ) {
					if ( 0 === strpos( $k, 'u_' ) ) {
						if ( 0 < $row['cnt'] ) {
							$record[ $k ] = (int) round( $v / $row['cnt'], 0 );
						} else {
							$record[ $k ] = 0;
						}
					} elseif ( 'cnt' !== $k && 'timestamp' !== $k ) {
						$record[ $k ] = (int) $v;
					}
				}
				$data[ $row['timestamp'] ] = $record;
			}
			// Boundaries computation.
			foreach ( $data as $datum ) {
				foreach ( array_merge( $item['user'], $item['session'], $item['turnover'], $item['log'], $item['password'] ) as $field ) {
					foreach ( $item as $selector => $array ) {
						if ( in_array( $field, $array, true ) ) {
							if ( $boundaries[ $selector ]['max'] < $datum[ $field ] ) {
								$boundaries[ $selector ]['max'] = $datum[ $field ];
								if ( 1100 < $datum[ $field ] ) {
									$boundaries[ $selector ]['factor'] = 1000;
								}
								if ( 1100000 < $datum[ $field ] ) {
									$boundaries[ $selector ]['factor'] = 1000000;
								}
								$boundaries[ $selector ]['order'] = array_diff( $boundaries[ $selector ]['order'], [ $field ] );
								array_unshift( $boundaries[ $selector ]['order'], $field );
							}
							continue 2;
						}
					}
				}
			}
			// Series computation.
			$init    = strtotime( $this->start ) - 86400;
			for ( $i = 0; $i < $this->duration + 2; $i++ ) {
				$ts = 'new Date(' . (string) ( ( $i * 86400 ) + $init ) . '000)';
				foreach ( array_merge( $item['user'], $item['session'], $item['turnover'], $item['log'], $item['password'] ) as $key ) {
					foreach ( $item as $selector => $array ) {
						if ( in_array( $key, $array, true ) ) {
							$series[ $key ][] = [
								'x' => $ts,
								'y' => 'null',
							];
							continue 2;
						}
					}
				}
			}
			foreach ( $data as $timestamp => $datum ) {
				// Series.
				$ts  = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
				$idx = (int) ( ( strtotime( $timestamp ) - $init ) / 86400 );
				foreach ( array_merge( $item['user'], $item['session'], $item['turnover'], $item['log'], $item['password'] ) as $key ) {
					foreach ( $item as $selector => $array ) {
						if ( in_array( $key, $array, true ) ) {
							$series[ $key ][$idx] = [
								'x' => $ts,
								'y' => round( $datum[ $key ] / $boundaries[ $selector ]['factor'], ( 1 === $boundaries[ $selector ]['factor'] ? 0 : 2 ) ),
							];
							continue 2;
						}
					}
				}
			}
			// Users.
			foreach ( $item as $selector => $array ) {
				$serie = [];
				foreach ( $boundaries[ $selector ]['order'] as $field ) {
					switch ( $field ) {
						case 'u_ham':
							if ( Environment::is_wordpress_multisite() ) {
								$name = esc_html__( 'Legit Users', 'sessions' );
							} else {
								$name = esc_html__( 'Users', 'sessions' );
							}
							break;
						case 'u_spam':
							$name = esc_html__( 'Spam Users', 'sessions' );
							break;
						case 'u_active':
							$name = esc_html__( 'Active Sessions', 'sessions' );
							break;
						case 'forced':
							$name = esc_html__( 'Overridden Sessions', 'sessions' );
							break;
						case 'expired':
							$name = esc_html__( 'Expired Sessions', 'sessions' );
							break;
						case 'idle':
							$name = esc_html__( 'Idle Sessions', 'sessions' );
							break;
						case 'registration':
							$name = esc_html__( 'Created Accounts', 'sessions' );
							break;
						case 'delete':
							$name = esc_html__( 'Deleted Accounts', 'sessions' );
							break;
						case 'logout':
							$name = esc_html__( 'Logouts', 'sessions' );
							break;
						case 'login_success':
							$name = esc_html__( 'Successful Logins', 'sessions' );
							break;
						case 'login_fail':
							$name = esc_html__( 'Failed Logins', 'sessions' );
							break;
						case 'login_block':
							$name = esc_html__( 'Blocked Logins', 'sessions' );
							break;
						case 'reset':
							$name = esc_html__( 'Password Resets', 'sessions' );
							break;
						default:
							$name = esc_html__( 'Unknown', 'sessions' );
					}
					$serie[] = [
						'name' => $name,
						'data' => $series[ $field ],
					];
				}
				$json[ $selector ] = wp_json_encode( [ 'series' => $serie ] );
				$json[ $selector ] = str_replace( '"x":"new', '"x":new', $json[ $selector ] );
				$json[ $selector ] = str_replace( ')","y"', '),"y"', $json[ $selector ] );
				$json[ $selector ] = str_replace( '"null"', 'null', $json[ $selector ] );
			}

			// Rendering.
			$ticks  = (int) ( 1 + ( $this->duration / 15 ) );
			$style  = 'pose-multichart-xlarge-item';
			if ( 20 < $this->duration ) {
				$style  = 'pose-multichart-large-item';
			}
			if ( 40 < $this->duration ) {
				$style  = 'pose-multichart-medium-item';
			}
			if ( 60 < $this->duration ) {
				$style  = 'pose-multichart-small-item';
			}
			if ( 80 < $this->duration ) {
				$style  = 'pose-multichart-xsmall-item';
			}
			$result  = '<div class="pose-multichart-handler">';
			$result .= '<div class="pose-multichart-item active" id="pose-chart-user">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var user_data' . $uuid . ' = ' . $json['user'] . ';';
			$result .= ' var user_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var user_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [user_tooltip' . $uuid . '],';
			$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['user']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#pose-chart-user", user_data' . $uuid . ', user_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="' . $style . '" id="pose-chart-turnover">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var turnover_data' . $uuid . ' = ' . $json['turnover'] . ';';
			$result .= ' var turnover_tooltip' . $uuid . ' = Chartist.plugins.tooltip({justvalue: true, appendToBody: true});';
			$result .= ' var turnover_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  plugins: [turnover_tooltip' . $uuid . '],';
			$result .= '  axisX: {showGrid: false, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['turnover']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#pose-chart-turnover", turnover_data' . $uuid . ', turnover_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-session">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var session_data' . $uuid . ' = ' . $json['session'] . ';';
			$result .= ' var session_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var session_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [session_tooltip' . $uuid . '],';
			$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['session']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#pose-chart-session", session_data' . $uuid . ', session_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="' . $style . '" id="pose-chart-log">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var log_data' . $uuid . ' = ' . $json['log'] . ';';
			$result .= ' var log_tooltip' . $uuid . ' = Chartist.plugins.tooltip({justvalue: true, appendToBody: true});';
			$result .= ' var log_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 0,';
			$result .= '  plugins: [log_tooltip' . $uuid . '],';
			$result .= '  axisX: {showGrid: false, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['log']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#pose-chart-log", log_data' . $uuid . ', log_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-password">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var password_data' . $uuid . ' = ' . $json['password'] . ';';
			$result .= ' var password_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var password_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [password_tooltip' . $uuid . '],';
			$result .= '  axisX: {showGrid: true, scaleMinSpace: 10, type: Chartist.FixedScaleAxis, divisor:' . ( $this->duration + 1 ) . ', labelInterpolationFnc: function skipLabels(value, index, labels) {return 0 === index % ' . $ticks . ' ? moment(value).format("DD") : null;}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['password']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#pose-chart-password", password_data' . $uuid . ', password_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="pose-multichart-handler">';
			$result .= '<div class="pose-multichart-item active" id="pose-chart-user">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-turnover">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-session">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-log">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="pose-multichart-item" id="pose-chart-password">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
		}
		return [ 'pose-main-chart' => $result ];
	}

	/**
	 * Query all kpis in statistics table.
	 *
	 * @param   array   $args   Optional. The needed args.
	 * @return array  The KPIs ready to send.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		$result['meta'] = [
			'plugin' => POSE_PRODUCT_NAME . ' ' . POSE_VERSION,
			'period' => date( 'Y-m-d' ),
		];
		$result['data'] = [];
		$kpi            = new static( 'summary', date( 'Y-m-d' ), date( 'Y-m-d' ), false );
		foreach ( [ 'session', 'cleaned', 'login', 'turnover', 'spam', 'user' ] as $query ) {
			$data = $kpi->query_kpi( $query, false );

			switch ( $query ) {
				case 'session':
					$val                       = Conversion::number_shorten( $data['kpi-main-session'], 0, true );
					$result['data']['session'] = [
						'name'        => esc_html_x( 'Sessions', 'Noun - Active sessions.', 'sessions' ),
						'short'       => esc_html_x( 'Ses.', 'Noun - Short (max 4 char) - Active sessions.', 'sessions' ),
						'description' => esc_html__( 'Number of active sessions.', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => round( $data['kpi-index-session'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-session'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-session'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-session'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'session_active_today_avg',
							'desc'  => 'Average number of active sessions today - [count]',
							'value' => (float) $data['kpi-main-session'],
							'type'  => 'gauge',
						],
					];
					break;
				case 'cleaned':
					$val                       = Conversion::number_shorten( $data['kpi-main-cleaned'], 0, true );
					$result['data']['cleaned'] = [
						'name'        => esc_html_x( 'Cleaned', 'Noun - Cleaned sessions.', 'sessions' ),
						'short'       => esc_html_x( 'Cl.', 'Noun - Short (max 4 char) - Cleaned sessions.', 'sessions' ),
						'description' => esc_html__( 'Number of cleaned sessions (idle, expired or overridden).', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => round( $data['kpi-index-cleaned'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-cleaned'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-cleaned'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-cleaned'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'session_cleaned_today',
							'desc'  => 'Number of cleaned sessions today - [count]',
							'value' => (int) $data['kpi-main-cleaned'],
							'type'  => 'counter',
						],
					];
					break;
				case 'login':
					$val                     = Conversion::number_shorten( $data['kpi-bottom-login'], 0, true );
					$result['data']['login'] = [
						'name'        => esc_html_x( 'Logins', 'Noun - Successful logins.', 'sessions' ),
						'short'       => esc_html_x( 'Log.', 'Noun - Short (max 4 char) - Successful logins.', 'sessions' ),
						'description' => esc_html__( 'Successful logins.', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-login'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-login'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-login'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-login'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-login'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-login'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-login'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'login_success_today',
							'desc'  => 'Ratio of successful logins today - [percent]',
							'value' => (float) ( $data['kpi-main-login'] / 100.0 ),
							'type'  => 'gauge',
						],
					];
					break;
				case 'turnover':
					$val                        = Conversion::number_shorten( $data['kpi-bottom-turnover'], 0, true );
					$result['data']['turnover'] = [
						'name'        => esc_html_x( 'Moves', 'Noun - Moving users.', 'sessions' ),
						'short'       => esc_html_x( 'Mov.', 'Noun - Short (max 4 char) - Moving users.', 'sessions' ),
						'description' => esc_html__( 'Moving users (registered or deleted).', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-turnover'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-turnover'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-turnover'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-turnover'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-turnover'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-turnover'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-turnover'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'user_turnover_today',
							'desc'  => 'Ratio of moving users today - [percent]',
							'value' => (float) ( $data['kpi-main-turnover'] / 100.0 ),
							'type'  => 'counter',
						],
					];
					break;
				case 'user':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-user'], 0, true );
					$result['data']['user'] = [
						'name'        => esc_html_x( 'Users', 'Noun - Active users.', 'sessions' ),
						'short'       => esc_html_x( 'Usr.', 'Noun - Short (max 4 char) - Active users.', 'sessions' ),
						'description' => esc_html__( 'Active users.', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-user'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-user'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-user'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-user'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-user'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-user'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-user'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'user_active_today_avg',
							'desc'  => 'Average ratio of active users today - [percent]',
							'value' => (float) ( $data['kpi-main-user'] / 100.0 ),
							'type'  => 'gauge',
						],
					];
					break;
				case 'spam':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-spam'], 0, true );
					$result['data']['spam'] = [
						'name'        => esc_html_x( 'Spams', 'Noun - Users marked as spam.', 'sessions' ),
						'short'       => esc_html_x( 'Spm.', 'Noun - Short (max 4 char) - Users marked as spam.', 'sessions' ),
						'description' => esc_html__( 'Users marked as spam.', 'sessions' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-spam'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-spam'] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-spam'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-spam'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-spam'] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-spam'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-spam'],
							'human' => $val['value'] . $val['abbreviation'],
						],
						'metrics'     => [
							'name'  => 'user_spam_avg',
							'desc'  => 'Ratio of spam user - [percent]',
							'value' => (float) ( $data['kpi-main-spam'] / 100.0 ),
							'type'  => 'gauge',
						],
					];
					break;
			}
		}
		$result['assets'] = [];
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed       $queried The query params.
	 * @param   boolean     $chart   Optional, return the chart if true, only the data if false;
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query_kpi( $queried, $chart = true ) {
		$result = [];
		$data   = Schema::get_grouped_kpi( $this->filter, '', ! $this->is_today );
		$pdata  = Schema::get_grouped_kpi( $this->previous );
		// COUNTS
		if ( 'session' === $queried || 'cleaned' === $queried ) {
			$current  = 0.0;
			$previous = 0.0;
			switch ( $queried ) {
				case 'session':
					foreach ( $data as $row ) {
						$current = $current + (float) ceil( $row['u_sessions'] / ( 0 < $row['cnt'] ? $row['cnt'] : 1 ) );
					}
					foreach ( $pdata as $row ) {
						$previous = $previous + (float) ceil( $row['u_sessions'] / ( 0 < $row['cnt'] ? $row['cnt'] : 1 ) );
					}
					break;
				case 'cleaned':
					foreach ( $data as $row ) {
						$current = $current + (float) $row['expired'] + (float) $row['idle'] + (float) $row['forced'];
					}
					foreach ( $pdata as $row ) {
						$previous = $previous + (float) $row['expired'] + (float) $row['idle'] + (float) $row['forced'];
					}
					break;
			}
			$current  = (int) ceil( $current );
			$previous = (int) ceil( $previous );
			if ( ! $chart ) {
				$result[ 'kpi-main-' . $queried ] = (int) $current;
				if ( 0 !== $current && 0 !== $previous ) {
					$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
				} else {
					$result[ 'kpi-index-' . $queried ] = null;
				}
				$result[ 'kpi-bottom-' . $queried ] = null;
				return $result;
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( (int) $current, 1, false, '&nbsp;' );
			if ( 0 !== $current && 0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0 === $previous && 0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0 !== $previous && 100 !== $previous && 0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
		}
		// RATIOS
		if ( 'login' === $queried || 'turnover' === $queried || 'spam' === $queried || 'user' === $queried ) {
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			$val         = null;
			switch ( $queried ) {
				case 'login':
					foreach ( $data as $row ) {
						$base_value = $base_value + (float) $row['login_success'] + (float) $row['login_fail'] + (float) $row['login_block'];
						$data_value = $data_value + (float) $row['login_success'];
					}
					foreach ( $pdata as $row ) {
						$pbase_value = $pbase_value + (float) $row['login_success'] + (float) $row['login_fail'] + (float) $row['login_block'];
						$pdata_value = $pdata_value + (float) $row['login_success'];
					}
					$val = (int) $data_value;
					if ( 0 === $val ) {
						$txt = esc_html__( 'no successful login', 'sessions' );
					} else {
						$txt = sprintf( esc_html( _n( '%s successful login', '%s successful logins', $val, 'sessions' ) ), Conversion::number_shorten( $val, 2, false, '&nbsp;' ) );
					}
					$result[ 'kpi-bottom-' . $queried ] = '<span class="pose-kpi-large-bottom-text">' . $txt . '</span>';
					break;
				case 'turnover':
					foreach ( $data as $row ) {
						$base_value = $base_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$data_value = $data_value + ( (float) $row['registration'] + (float) $row['registration'] ) / 2;
					}
					foreach ( $pdata as $row ) {
						$pbase_value = $pbase_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$pdata_value = $pdata_value + ( (float) $row['registration'] + (float) $row['registration'] ) / 2;
					}
					$val = (int) $data_value * 2;
					if ( 0 === $val ) {
						$txt = esc_html__( 'no moves', 'sessions' );
					} else {
						$txt = sprintf( esc_html( _n( '%s move', '%s moves', $val, 'sessions' ) ), Conversion::number_shorten( $val, 2, false, '&nbsp;' ) );
					}
					$result[ 'kpi-bottom-' . $queried ] = '<span class="pose-kpi-large-bottom-text">' . $txt . '</span>';
					break;
				case 'spam':
					foreach ( $data as $row ) {
						$base_value = $base_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$data_value = $data_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_spam'] / (float) $row['cnt'] : 0.0 );
					}
					foreach ( $pdata as $row ) {
						$pbase_value = $pbase_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$pdata_value = $pdata_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_spam'] / (float) $row['cnt'] : 0.0 );
					}
					$val = (int) $data_value;
					if ( 0 === $val ) {
						$txt = esc_html__( 'no spam users', 'sessions' );
					} else {
						$txt = sprintf( esc_html( _n( '%s spam user', '%s spam users', $val, 'sessions' ) ), Conversion::number_shorten( $val, 2, false, '&nbsp;' ) );
					}
					$result[ 'kpi-bottom-' . $queried ] = '<span class="pose-kpi-large-bottom-text">' . $txt . '</span>';
					break;
				case 'user':
					foreach ( $data as $row ) {
						$base_value = $base_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$data_value = $data_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_active'] / (float) $row['cnt'] : 0.0 );
					}
					foreach ( $pdata as $row ) {
						$pbase_value = $pbase_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_total'] / (float) $row['cnt'] : 0.0 );
						$pdata_value = $pdata_value + ( 0 !== (int) $row['cnt'] ? (float) $row['u_active'] / (float) $row['cnt'] : 0.0 );
					}
					$val = (int) $data_value;
					if ( 0 === $val ) {
						$txt = esc_html__( 'no active users', 'sessions' );
					} else {
						$txt = sprintf( esc_html( _n( '%s active user', '%s active users', (int) ( $val / $this->duration ), 'sessions' ) ), Conversion::number_shorten( $val / $this->duration, 2, false, '&nbsp;' ) );
					}
					$result[ 'kpi-bottom-' . $queried ] = '<span class="pose-kpi-large-bottom-text">' . $txt . '</span>';
					break;
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, $chart ? 1 : 4 );
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = 100;
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = 0;
				} else {
					$result[ 'kpi-main-' . $queried ] = null;
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
			} else {
				$result[ 'kpi-index-' . $queried ] = null;
			}
			if ( ! $chart ) {
				$result[ 'kpi-bottom-' . $queried ] = $val;
				return $result;
			}
			if ( isset( $result[ 'kpi-main-' . $queried ] ) ) {
				$result[ 'kpi-main-' . $queried ] = $result[ 'kpi-main-' . $queried ] . '&nbsp;%';
			} else {
				$result[ 'kpi-main-' . $queried ] = '-';
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
		}
		return $result;
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$subtitle = '';
		$title    = esc_html__( 'Main Summary', 'sessions' );
		$result   = '<div class="pose-box pose-box-full-line">';
		$result  .= '<span class="pose-title">' . $title . '</span>';
		$result  .= '<span class="pose-subtitle">' . $subtitle . '</span>';
		$result  .= '<span class="pose-datepicker">' . $this->get_date_box() . '</span>';
		$result  .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="pose-box pose-box-full-line">';
		$result .= '<div class="pose-kpi-bar">';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'login' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'session' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'cleaned' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'user' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'turnover' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'spam' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		if ( 1 < $this->duration ) {
			$help_user     = esc_html__( 'Users variation.', 'sessions' );
			$help_session  = esc_html__( 'Sessions variation.', 'sessions' );
			$help_turnover = esc_html__( 'Moves distribution.', 'sessions' );
			$help_log      = esc_html__( 'Login / logout breakdown.', 'sessions' );
			$help_password = esc_html__( 'Password resets.', 'sessions' );
			$detail        = '<span class="pose-chart-button not-ready left" id="pose-chart-button-user" data-position="left" data-tooltip="' . $help_user . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'users', 'none', '#73879C' ) . '" /></span>';
			$detail       .= '&nbsp;&nbsp;&nbsp;<span class="pose-chart-button not-ready left" id="pose-chart-button-turnover" data-position="left" data-tooltip="' . $help_turnover . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'refresh-cw', 'none', '#73879C' ) . '" /></span>';
			$detail       .= '&nbsp;&nbsp;&nbsp;<span class="pose-chart-button not-ready left" id="pose-chart-button-session" data-position="left" data-tooltip="' . $help_session . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
			$detail       .= '&nbsp;&nbsp;&nbsp;<span class="pose-chart-button not-ready left" id="pose-chart-button-log" data-position="left" data-tooltip="' . $help_log . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'move', 'none', '#73879C' ) . '" /></span>';
			$detail       .= '&nbsp;&nbsp;&nbsp;<span class="pose-chart-button not-ready left" id="pose-chart-button-password" data-position="left" data-tooltip="' . $help_password . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'key', 'none', '#73879C' ) . '" /></span>';
			$result        = '<div class="pose-row">';
			$result       .= '<div class="pose-box pose-box-full-line">';
			$result       .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Metrics Variations', 'sessions' ) . '<span class="pose-module-more">' . $detail . '</span></span></div>';
			$result       .= '<div class="pose-module-content" id="pose-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result       .= '</div>';
			$result       .= '</div>';
			$result       .= $this->get_refresh_script(
				[
					'query'   => 'main-chart',
					'queried' => 0,
				]
			);
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'login':
				$icon  = Feather\Icons::get_base64( 'log-in', 'none', '#73879C' );
				$title = esc_html_x( 'Login Success', 'Noun - Number of successful logins.', 'sessions' );
				$help  = esc_html__( 'Ratio of successful logins.', 'sessions' );
				break;
			case 'session':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Active Sessions', 'Noun - Number of active sessions.', 'sessions' );
				$help  = esc_html__( 'Number of active sessions.', 'sessions' );
				break;
			case 'cleaned':
				$icon  = Feather\Icons::get_base64( 'trash-2', 'none', '#73879C' );
				$title = esc_html_x( 'Cleaned Sessions', 'Noun - Number of cleaned sessions.', 'sessions' );
				$help  = esc_html__( 'Number of cleaned sessions (idle, expired or overridden).', 'sessions' );
				break;
			case 'user':
				$icon  = Feather\Icons::get_base64( 'users', 'none', '#73879C' );
				$title = esc_html_x( 'Active Users', 'Noun - Percentage of active users.', 'sessions' );
				$help  = esc_html__( 'Ratio of active users.', 'sessions' );
				break;
			case 'turnover':
				$icon  = Feather\Icons::get_base64( 'refresh-cw', 'none', '#73879C' );
				$title = esc_html_x( 'Turnover', 'Noun - Users turnover.', 'sessions' );
				$help  = esc_html__( 'Ratio of moving users (registered or deleted).', 'sessions' );
				break;
			case 'spam':
				$icon  = Feather\Icons::get_base64( 'user-x', 'none', '#73879C' );
				$title = esc_html_x( 'Spam', 'Noun - Ratio of users marked as spam.', 'sessions' );
				$help  = esc_html__( 'Ratio of users marked as spam.', 'sessions' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="pose-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="pose-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="pose-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="pose-kpi-large-middle"><div class="pose-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="pose-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="pose-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get the logins pie.
	 *
	 * @return string  The pie box ready to print.
	 * @since    1.0.0
	 */
	public function get_login_pie() {
		$result  = '<div class="pose-50-module-left">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Logins', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-login">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'login',
				'queried' => 3,
			]
		);
		return $result;
	}

	/**
	 * Get the clean pie.
	 *
	 * @return string  The pie box ready to print.
	 * @since    1.0.0
	 */
	public function get_clean_pie() {
		$result  = '<div class="pose-50-module-right">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Cleaned Sessions', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-clean">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'clean',
				'queried' => 3,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . POSE_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for graph with no data.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder_nodata( $height ) {
		return '<p style="color:#73879C;text-align:center;line-height:' . $height . 'px;">' . esc_html__( 'No Data', 'sessions' ) . '</p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . POSE_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"pose_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_pose' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		$result .= '  type:"' . $this->type . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".pose-chart-button").removeClass("not-ready");';
			$result .= '$("#pose-chart-button-user").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array   $exclude Optional. The args to exclude.
	 * @param   array   $replace Optional. The args to replace or add.
	 * @param   boolean $escape  Optional. Forces url escaping.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [], $escape = true ) {
		$params          = [];
		$params['type']  = $this->type;
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=pose-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . rawurlencode( $arg );
			}
		}
		$url = str_replace( '"', '\'\'', $url );
		if ( $escape ) {
			$url = esc_url( $url );
		}
		return $url;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="pose-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.pose-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
		$result .= ' }';
		$result .= ' $(".pose-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'sessions' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'sessions' ) . '", applyLabel: "' . esc_html__( 'Apply', 'sessions' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'sessions' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'sessions' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'sessions' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'sessions' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".pose-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], [], false ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
