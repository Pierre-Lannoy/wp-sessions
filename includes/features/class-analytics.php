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
use POSessions\System\Logger;
use POSessions\System\L10n;
use POSessions\System\Http;
use POSessions\System\Favicon;
use POSessions\System\Timezone;
use POSessions\System\UUID;
use POSessions\Plugin\Feature\ClassTypes;
use POSessions\Plugin\Feature\DeviceTypes;
use POSessions\Plugin\Feature\ClientTypes;
use POSessions\Plugin\Feature\ChannelTypes;
use UDD\Parser\Client\Browser;
use UDD\Parser\OperatingSystem;
use UDD\Parser\Device\DeviceParserAbstract;
use Feather;
use Flagiconcss;
use Morpheus;


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
	 * The queried ID.
	 *
	 * @since  1.0.0
	 * @var    string    $id    The queried ID.
	 */
	private $id = '';

	/**
	 * The queried extension.
	 *
	 * @since  1.0.0
	 * @var    string    $extended    The queried extension.
	 */
	private $extended = '';

	/**
	 * The queried site.
	 *
	 * @since  1.0.0
	 * @var    string    $site    The queried site.
	 */
	public $site = 'all';

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
	 * The period duration in seconds.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in seconds.
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
	 * @param   string  $id      The subfilter.
	 * @param   string  $site    The site to analyze (all or ID).
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   string  $id      The extended filter.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @since    1.0.0
	 */
	public function __construct( $type, $id, $site, $start, $end, $extended, $reload ) {
		$this->id       = $id;
		$this->extended = $extended;
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		$this->site = $site;
		if ( 'all' !== $site ) {
			$this->filter[]   = "site='" . $site . "'";
			$this->previous[] = "site='" . $site . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start = $start;
		$this->end   = $end;
		$this->type  = $type;
		switch ( $type ) {
			case 'browsers':
				$this->filter[]   = "client='browser'";
				$this->previous[] = "client='browser'";
				break;
			case 'browser':
				$this->filter[]   = "client_id='" . $id . "'";
				$this->previous[] = "client_id='" . $id . "'";
				break;
			case 'bots':
				$this->filter[]   = "class='bot'";
				$this->previous[] = "class='bot'";
				break;
			case 'bot':
				$this->filter[]   = "class='bot'";
				$this->previous[] = "class='bot'";
				$this->filter[]   = "name='" . $id . "'";
				$this->previous[] = "name='" . $id . "'";
				break;
			case 'oses':
				$this->filter[]   = "os_id<>'-'";
				$this->previous[] = "os_id<>'-'";
				break;
			case 'os':
				$this->filter[]   = "os_id='" . $id . "'";
				$this->previous[] = "os_id='" . $id . "'";
				break;
			case 'devices':
				$this->filter[]   = "brand_id<>'-'";
				$this->previous[] = "brand_id<>'-'";
				break;
			case 'device':
				$this->filter[]   = "brand_id='" . $id . "'";
				$this->previous[] = "brand_id='" . $id . "'";
				$this->filter[]   = "model='" . str_replace( '\'', '\\' . '\'', $extended ) . "'";
				$this->previous[] = "model='" . str_replace( '\'', '\\' . '\'', $extended ) . "'";
				break;
			case 'libraries':
				$this->filter[]   = "client='library'";
				$this->previous[] = "client='library'";
				break;
			case 'applications':
				$this->filter[]   = "client='mobile-app'";
				$this->previous[] = "client='mobile-app'";
				break;
			case 'feeds':
				$this->filter[]   = "client='feed-reader'";
				$this->previous[] = "client='feed-reader'";
				break;
			case 'medias':
				$this->filter[]   = "client='media-player'";
				$this->previous[] = "client='media-player'";
				break;
		}
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
			case 'top-browsers':
				return $this->query_top( 'browsers', (int) $queried );
			case 'top-bots':
				return $this->query_top( 'bots', (int) $queried );
			case 'top-devices':
				return $this->query_top( 'devices', (int) $queried );
			case 'top-oses':
				return $this->query_top( 'oses', (int) $queried );
			case 'top-versions':
				return $this->query_top( 'versions', (int) $queried );
			case 'classes':
			case 'types':
			case 'clients':
			case 'libraries':
			case 'applications':
			case 'feeds':
			case 'medias':
				return $this->query_pie( $query, (int) $queried );
			case 'classes-list':
			case 'types-list':
			case 'clients-list':
			case 'libraries-list':
			case 'applications-list':
			case 'feeds-list':
			case 'medias-list':
				return $this->query_list( $query );
			case 'browsers-list':
			case 'bots-list':
			case 'devices-list':
			case 'oses-list':
				return $this->query_extended_list( $query );
			case 'main-chart':
				return $this->query_chart();
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$uuid = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'classes':
				$data     = Schema::get_grouped_list( $this->filter, 'class', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$selector = 'class';
				$names    = ClassTypes::$class_names;
				$size     = 120;
				break;
			case 'types':
				$data     = Schema::get_grouped_list( $this->filter, 'device', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$selector = 'device';
				$names    = DeviceTypes::$device_names;
				$size     = 120;
				break;
			case 'clients':
				$data     = Schema::get_grouped_list( $this->filter, 'client', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$selector = 'client';
				$names    = ClientTypes::$client_names;
				$size     = 120;
				break;
			case 'libraries':
				$data     = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'client', [ 'library' ], false, 'ORDER BY sum_hit DESC' );
				$selector = 'name';
				$names    = [];
				$size     = 100;
				break;
			case 'applications':
				$data     = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'client', [ 'mobile-app' ], false, 'ORDER BY sum_hit DESC' );
				$selector = 'name';
				$names    = [];
				$size     = 100;
				break;
			case 'feeds':
				$data     = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'client', [ 'feed-reader' ], false, 'ORDER BY sum_hit DESC' );
				$selector = 'name';
				$names    = [];
				$size     = 100;
				break;
			case 'medias':
				$data     = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'client', [ 'media-player' ], false, 'ORDER BY sum_hit DESC' );
				$selector = 'name';
				$names    = [];
				$size     = 100;
				break;
		}
		if ( 0 < count( $data ) ) {
			$total = 0;
			$other = 0;
			foreach ( $data as $key => $row ) {
				$total = $total + $row['sum_hit'];
				if ( $limit <= $key || 'other' === $row[ $selector ] ) {
					$other = $other + $row['sum_hit'];
				}
			}
			$cpt    = 0;
			$labels = [];
			$series = [];
			while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
				if ( 'other' !== $data[ $cpt ][ $selector ] ) {
					if ( 0 < $total ) {
						$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
					} else {
						$percent = 100;
					}
					if ( 0.1 > $percent ) {
						$percent = 0.1;
					}
					if ( 0 < count( $names ) ) {
						$meta = $names[ $data[ $cpt ][ $selector ] ];
					} else {
						$meta = $data[ $cpt ][ $selector ];
					}
					$labels[] = $meta;
					$series[] = [
						'meta'  => $meta,
						'value' => (float) $percent,
					];
				}
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
			$result .= '<div class="pose-pie-graph-handler-' . $size . '" id="pose-pie-' . $type . '"></div>';
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
			$result .= ' var option' . $uuid . ' = {width: ' . $size . ', height: ' . $size . ', showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
			$result .= ' new Chartist.Pie("#pose-pie-' . $type . '", data' . $uuid . ', option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="pose-pie-box">';
			$result .= '<div class="pose-pie-graph" style="margin:0 !important;">';
			$result .= '<div class="pose-pie-graph-nodata-handler-' . $size . '" id="pose-pie-' . $type . '"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'No Data', 'sessions' ) . '&nbsp;-</span></div>';
			$result .= '</div>';
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
		}
		return [ 'pose-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'browsers':
				$data = Schema::get_grouped_list( $this->filter, 'client_id', ! $this->is_today, 'client', [ 'browser' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'bots':
				$data = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'class', [ 'bot' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'devices':
				$data = Schema::get_grouped_list( $this->filter, 'brand, model', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'oses':
				$data = Schema::get_grouped_list( $this->filter, 'os', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'versions':
				switch ( $this->type ) {
					case 'browser':
						$data = Schema::get_grouped_list( $this->filter, 'client_version', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
						break;
				}
				switch ( $this->type ) {
					case 'os':
						$data = Schema::get_grouped_list( $this->filter, 'os_version', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
						break;
				}
				break;
			default:
				$data = [];
				break;
		}
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			switch ( $type ) {
				case 'browsers':
					$text = $data[ $cpt ]['name'];
					$icon = Morpheus\Icons::get_browser_base64( $data[ $cpt ]['client_id'] );
					$url  = $this->get_url(
						[],
						[
							'type' => 'browser',
							'id'   => $data[ $cpt ]['client_id'],
						]
					);
					break;
				case 'bots':
					$text = $data[ $cpt ]['name'];
					$icon = Favicon::get_base64( $data[ $cpt ]['url'] );
					$url  = $this->get_url(
						[],
						[
							'type' => 'bot',
							'id'   => $data[ $cpt ]['name'],
						]
					);
					break;
				case 'devices':
					$text = ( isset( $data[ $cpt ]['brand'] ) && '-' !== $data[ $cpt ]['brand'] ? $data[ $cpt ]['brand'] : esc_html__( 'Generic', 'sessions' ) ) . ( isset( $data[ $cpt ]['model'] ) && '-' !== $data[ $cpt ]['model'] ? ' ' . $data[ $cpt ]['model'] : '' );
					$icon = Morpheus\Icons::get_brand_base64( $data[ $cpt ]['brand'] );
					$url  = $this->get_url(
						[],
						[
							'type'     => 'device',
							'id'       => $data[ $cpt ]['brand_id'],
							'extended' => $data[ $cpt ]['model'],
						]
					);
					break;
				case 'oses':
					switch ( $this->type ) {
						case 'device':
							$text = $data[ $cpt ]['os'] . ' ' . $data[ $cpt ]['os_version'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = '';
							break;
						default:
							$text = $data[ $cpt ]['os'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = $this->get_url(
								[],
								[
									'type' => 'os',
									'id'   => $data[ $cpt ]['os_id'],
								]
							);
							break;
					}
					break;
				case 'versions':
					switch ( $this->type ) {
						case 'browser':
							$text = $data[ $cpt ]['name'] . ' ' . $data[ $cpt ]['client_version'];
							$icon = Morpheus\Icons::get_browser_base64( $data[ $cpt ]['client_id'] );
							$url  = '';
							break;
						case 'os':
							$text = $data[ $cpt ]['os'] . ' ' . $data[ $cpt ]['os_version'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = '';
							break;
					}
					break;
			}
			if ( '' !== $url ) {
				$url = '<a href="' . $url . '">' . $text . '</a>';
			} else {
				$url = $text;
			}
			$result .= '<div class="pose-top-line">';
			$result .= '<div class="pose-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="pose-top-line-title-text">' . $url . '</span>';
			$result .= '</div>';
			$result .= '<div class="pose-top-line-content">';
			$result .= '<div class="pose-bar-graph"><div class="pose-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="pose-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="pose-top-line pose-minor-data">';
		$result .= '<div class="pose-top-line-title">';
		$result .= '<span class="pose-top-line-title-text">' . esc_html__( 'Other', 'sessions' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="pose-top-line-content">';
		$result .= '<div class="pose-bar-graph"><div class="pose-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="pose-bar-detail">' . Conversion::number_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'pose-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		switch ( $type ) {
			case 'classes-list':
				$data     = Schema::get_grouped_list( $this->filter, 'class, channel', ! $this->is_today, '', [], false, 'ORDER BY class DESC' );
				$selector = 'class';
				break;
			case 'types-list':
				$data     = Schema::get_grouped_list( $this->filter, 'device, channel', ! $this->is_today, '', [], false, 'ORDER BY device DESC' );
				$selector = 'device';
				break;
			case 'clients-list':
				$data     = Schema::get_grouped_list( $this->filter, 'client, channel', ! $this->is_today, '', [], false, 'ORDER BY client DESC' );
				$selector = 'client';
				break;
			case 'libraries-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'library' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'applications-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'mobile-app' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'feeds-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'feed-reader' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'medias-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'media-player' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
		}
		if ( 0 < count( $data ) ) {
			$columns = [ 'wfront', 'wback', 'api', 'cron' ];
			$d       = [];
			$current = '';
			$total   = 0;
			foreach ( $data as $row ) {
				if ( $current !== $row[ $selector ] ) {
					$current = $row[ $selector ];
					foreach ( $columns as $column ) {
						$d[ $current ][ $column ] = 0;
					}
					$d[ $current ]['other'] = 0;
					$d[ $current ]['total'] = 0;
					$d[ $current ]['perct'] = 0.0;
				}
				if ( in_array( $row['channel'], $columns, true ) ) {
					$d[ $current ][ $row['channel'] ] = $row['sum_hit'];
				} else {
					$d[ $current ]['other'] += $row['sum_hit'];
				}
				$d[ $current ]['total'] += $row['sum_hit'];
				$total                  += $row['sum_hit'];
			}
			uasort(
				$d,
				function ( $a, $b ) {
					if ( $a['total'] === $b['total'] ) {
						return 0;
					} return ( $a['total'] > $b['total'] ) ? -1 : 1;
				}
			);
			$result  = '<table class="pose-table">';
			$result .= '<tr>';
			$result .= '<th>&nbsp;</th>';
			foreach ( $columns as $column ) {
				$result .= '<th>' . ChannelTypes::$channel_names[ strtoupper( $column ) ] . '</th>';
			}
			$result .= '<th>' . __( 'Other', 'sessions' ) . '</th>';
			$result .= '<th>' . __( 'TOTAL', 'sessions' ) . '</th>';
			$result .= '</tr>';
			foreach ( $d as $name => $item ) {
				$row_str  = '<tr>';
				$row_str .= '<td data-th="name">' . $name . '</td>';
				foreach ( $columns as $column ) {
					$row_str .= '<td data-th="' . $column . '">' . Conversion::number_shorten( $item[ $column ], 2, false, '&nbsp;' ) . '</td>';
				}
				$row_str .= '<td data-th="other">' . Conversion::number_shorten( $item['other'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '<td data-th="total">' . Conversion::number_shorten( $item['total'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '</tr>';
				$result  .= $row_str;
			}
			$result .= '</table>';
		} else {
			$result   = '<table class="pose-table">';
			$result  .= '<tr>';
			$result  .= '<th>&nbsp;</th>';
			$result  .= '</tr>';
			$row_str  = '<tr>';
			$row_str .= '<td data-th="" style="color:#73879C;text-align:center;">' . esc_html__( 'No Data', 'sessions' ) . '</td>';
			$row_str .= '</tr>';
			$result  .= $row_str;
			$result  .= '</table>';
		}
		return [ 'pose-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_extended_list( $type ) {
		switch ( $type ) {
			case 'browsers-list':
				$data      = Schema::get_grouped_list( $this->filter, 'client_id, client_version, channel', ! $this->is_today, 'client', [ 'browser' ], false, 'ORDER BY client_id DESC' );
				$selector  = 'client_id';
				$sub       = 'client_version';
				$name      = 'name';
				$icon      = 'client_id';
				$icon_list = 'browser';
				$extra     = 'engine';
				$link      = 'browser';
				$elink     = '';
				break;
			case 'bots-list':
				$data      = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'class', [ 'bot' ], false, 'ORDER BY brand_id DESC' );
				$selector  = 'name';
				$sub       = '';
				$name      = 'name';
				$icon      = 'url';
				$icon_list = '';
				$extra     = '';
				$link      = 'bot';
				$elink     = '';
				break;
			case 'devices-list':
				$data      = Schema::get_grouped_list( $this->filter, 'brand_id, model, channel', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY brand_id DESC' );
				$selector  = 'brand_id';
				$sub       = 'model';
				$name      = 'brand';
				$icon      = 'brand';
				$icon_list = 'brand';
				$extra     = '';
				$link      = 'device';
				$elink     = 'model';
				break;
			case 'oses-list':
				$data      = Schema::get_grouped_list( $this->filter, 'os_id, os_version, channel', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY os_id DESC' );
				$selector  = 'os_id';
				$sub       = 'os_version';
				$name      = 'os';
				$icon      = 'os_id';
				$icon_list = 'os';
				$extra     = '';
				$link      = 'os';
				$elink     = '';
				break;

		}
		if ( 0 < count( $data ) ) {
			$columns = [ 'wfront', 'wback', 'api', 'cron' ];
			$d       = [];
			$current = '';
			$total   = 0;
			foreach ( $data as $row ) {
				if ( $current !== $row[ $selector ] ) {
					$current = $row[ $selector ];
					if ( '-' === $row[ $name ] ) {
						$row[ $name ] = __( 'Generic', 'sessions' );
					}
					$d[ $current ]['name'] = $row[ $name ] . ( '' !== $extra ? ' / ' . $row[ $extra ] : '' );
					$d[ $current ]['id']   = $row[ $selector ];
					$d[ $current ]['icon'] = ( '' !== $icon ? $row[ $icon ] : '' );
					foreach ( $columns as $column ) {
						$d[ $current ][ $column ] = 0;
					}
					$d[ $current ]['other'] = 0;
					$d[ $current ]['total'] = 0;
					$d[ $current ]['perct'] = 0.0;
					$d[ $current ]['data']  = [];
				}
				if ( '' !== $sub && ! array_key_exists( $row[ $sub ], $d[ $current ]['data'] ) ) {
					$d[ $current ]['data'][ $row[ $sub ] ]['name'] = $row[ $name ] . ( 1 < strlen( $row[ $sub ] ) ? ' ' . $row[ $sub ] : '' );
					if ( '' !== $elink ) {
						$d[ $current ]['data'][ $row[ $sub ] ]['id'] = $row[ $elink ];
					}
					if ( '-' === $row[ $name ] ) {
						$d[ $current ]['data'][ $row[ $sub ] ]['name'] = __( 'Generic', 'sessions' );
					}
					foreach ( $columns as $column ) {
						$d[ $current ]['data'][ $row[ $sub ] ][ $column ] = 0;
					}
					$d[ $current ]['data'][ $row[ $sub ] ]['other'] = 0;
					$d[ $current ]['data'][ $row[ $sub ] ]['total'] = 0;
					$d[ $current ]['data'][ $row[ $sub ] ]['perct'] = 0.0;
				}
				if ( in_array( $row['channel'], $columns, true ) ) {
					$d[ $current ][ $row['channel'] ] += $row['sum_hit'];
					if ( '' !== $sub ) {
						$d[ $current ]['data'][ $row[ $sub ] ][ $row['channel'] ] = $row['sum_hit'];
					}
				} else {
					$d[ $current ]['other'] += $row['sum_hit'];
					if ( '' !== $sub ) {
						$d[ $current ]['data'][ $row[ $sub ] ]['other'] = $row['sum_hit'];
					}
				}
				if ( '' !== $sub ) {
					$d[ $current ]['data'][ $row[ $sub ] ]['total'] += $row['sum_hit'];
				}
				$d[ $current ]['total'] += $row['sum_hit'];
				$total                  += $row['sum_hit'];
			}
			uasort(
				$d,
				function ( $a, $b ) {
					if ( $a['total'] === $b['total'] ) {
						return 0;
					} return ( $a['total'] > $b['total'] ) ? -1 : 1;
				}
			);
			$result  = '<table class="pose-table">';
			$result .= '<tr>';
			$result .= '<th>&nbsp;</th>';
			foreach ( $columns as $column ) {
				$result .= '<th>' . ChannelTypes::$channel_names[ strtoupper( $column ) ] . '</th>';
			}
			$result .= '<th>' . __( 'Other', 'sessions' ) . '</th>';
			$result .= '<th>' . __( 'TOTAL', 'sessions' ) . '</th>';
			$result .= '</tr>';
			foreach ( $d as $item ) {
				if ( 0 < count( $item['data'] ) ) {
					uasort(
						$item['data'],
						function ( $a, $b ) {
							if ( $a['total'] === $b['total'] ) {
								return 0;
							} return ( $a['total'] > $b['total'] ) ? -1 : 1;
						}
					);
				}
				if ( '' === $icon_list ) {
					$icon = Favicon::get_base64( $item['icon'] );
				} else {
					$icon = Morpheus\Icons::get_base64( $item['icon'], $icon_list );
				}
				$l = [
					'type' => $link,
					'id'   => $item['id'],
				];
				if ( '' !== $elink ) {
					$name = $item['name'];
				} else {
					$name = '<a href="' . $this->get_url( [], $l ) . '">' . $item['name'] . '</a>';
				}
				$row_str  = '<tr style="' . ( '' !== $sub ? 'font-weight: 600;' : '' ) . '">';
				$row_str .= '<td data-th="name"><img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="pose-list-text">' . $name . '</span></td>';
				foreach ( $columns as $column ) {
					$row_str .= '<td data-th="' . $column . '">' . Conversion::number_shorten( $item[ $column ], 2, false, '&nbsp;' ) . '</td>';
				}
				$row_str .= '<td data-th="other">' . Conversion::number_shorten( $item['other'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '<td data-th="total">' . Conversion::number_shorten( $item['total'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '</tr>';
				$result  .= $row_str;
				foreach ( $item['data'] as $datum ) {
					if ( '' !== $elink ) {
						$l    = [
							'type'     => $link,
							'id'       => $item['id'],
							'extended' => $datum['id'],
						];
						$name = '<a href="' . $this->get_url( [], $l ) . '">' . $datum['name'] . '</a>';
					} else {
						$name = $datum['name'];
					}
					$row_str  = '<tr>';
					$row_str .= '<td data-th="name">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="pose-list-text">' . $name . '</span></td>';
					foreach ( $columns as $column ) {
						$row_str .= '<td data-th="' . $column . '">' . Conversion::number_shorten( $datum[ $column ], 2, false, '&nbsp;' ) . '</td>';
					}
					$row_str .= '<td data-th="other">' . Conversion::number_shorten( $datum['other'], 2, false, '&nbsp;' ) . '</td>';
					$row_str .= '<td data-th="total">' . Conversion::number_shorten( $datum['total'], 2, false, '&nbsp;' ) . '</td>';
					$row_str .= '</tr>';
					$result  .= $row_str;
				}
			}
			$result .= '</table>';
		} else {
			$result   = '<table class="pose-table">';
			$result  .= '<tr>';
			$result  .= '<th>&nbsp;</th>';
			$result  .= '</tr>';
			$row_str  = '<tr>';
			$row_str .= '<td data-th="" style="color:#73879C;text-align:center;">' . esc_html__( 'No Data', 'sessions' ) . '</td>';
			$row_str .= '</tr>';
			$result  .= $row_str;
			$result  .= '</table>';
		}
		return [ 'pose-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid       = UUID::generate_unique_id( 5 );
		$data_total = Schema::get_time_series( $this->filter, ! $this->is_today );
		$call_max   = 0;
		$hits       = [];
		$start      = '';
		if ( 0 < count( $data_total ) ) {
			foreach ( $data_total as $timestamp => $row ) {
				if ( '' === $start ) {
					$start = $timestamp;
				}
				$ts  = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
				$val = $row['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$hits[] = [
					'x' => $ts,
					'y' => $val,
				];
			}
			$before = [
				'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
				'y' => 'null',
			];
			$after  = [
				'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
				'y' => 'null',
			];
			// Hits.
			$short       = Conversion::number_shorten( $call_max, 2, true );
			$call_max    = 0.5 + floor( $call_max / $short['divisor'] );
			$call_abbr   = $short['abbreviation'];
			$series_hits = [];
			foreach ( $hits as $item ) {
				$item['y']     = $item['y'] / $short['divisor'];
				$series_hits[] = $item;
			}
			array_unshift( $series_hits, $before );
			$series_hits[] = $after;
			$json_call     = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Hits', 'sessions' ),
							'data' => $series_hits,
						],
					],
				]
			);
			$json_call     = str_replace( '"x":"new', '"x":new', $json_call );
			$json_call     = str_replace( ')","y"', '),"y"', $json_call );
			$json_call     = str_replace( '"null"', 'null', $json_call );

			// Rendering.
			if ( 4 < $this->duration ) {
				if ( 1 === $this->duration % 2 ) {
					$divisor = 6;
				} else {
					$divisor = 5;
				}
			} else {
				$divisor = $this->duration + 1;
			}
			$result  = '<div class="pose-multichart-handler">';
			$result .= '<div class="pose-multichart-item active" id="pose-chart-calls">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var call_data' . $uuid . ' = ' . $json_call . ';';
			$result .= ' var call_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var call_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [call_tooltip' . $uuid . '],';
			$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $call_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $call_abbr . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#pose-chart-calls", call_data' . $uuid . ', call_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '</div>';
		} else {
			$result  = '<div class="pose-multichart-handler">';
			$result .= '<div class="pose-multichart-item active" id="pose-chart-calls">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
		}
		return [ 'pose-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		switch ( $queried ) {
			case 'hit':
				$data  = Schema::get_grouped_kpi( $this->filter, '', ! $this->is_today );
				$pdata = Schema::get_grouped_kpi( $this->previous );
				break;
			case 'mobile':
			case 'desktop':
			case 'bot':
				$data  = Schema::get_grouped_kpi( $this->filter, 'class', ! $this->is_today );
				$pdata = Schema::get_grouped_kpi( $this->previous, 'class' );
				break;
			case 'client':
				$data  = Schema::get_distinct_kpi( $this->filter, [ 'client', 'brand_id', 'model', 'client_id', 'client_version', 'os_id', 'os_version' ], ! $this->is_today );
				$pdata = Schema::get_distinct_kpi( $this->previous, [ 'client', 'brand_id', 'model', 'client_id', 'client_version', 'os_id', 'os_version' ] );
				break;
			case 'engine':
				$data  = Schema::get_distinct_kpi( $this->filter, [ 'engine' ], ! $this->is_today );
				$pdata = Schema::get_distinct_kpi( $this->previous, [ 'engine' ] );
				break;
		}
		if ( 'hit' === $queried || 'client' === $queried || 'engine' === $queried ) {
			$current  = (int) count( $data );
			$previous = (int) count( $pdata );
			if ( 0 < count( $data ) && 'hit' === $queried ) {
				$current = (int) $data[0]['sum_hit'];
			}
			if ( 0 < count( $pdata ) && 'hit' === $queried ) {
				$previous = (int) $pdata[0]['sum_hit'];
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
		if ( 'mobile' === $queried || 'desktop' === $queried || 'bot' === $queried ) {
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			foreach ( $data as $row ) {
				$base_value = $base_value + (float) $row['sum_hit'];
				if ( $row['class'] === $queried ) {
					$data_value = (float) $row['sum_hit'];
				}
			}
			foreach ( $pdata as $row ) {
				$pbase_value = $pbase_value + (float) $row['sum_hit'];
				if ( $row['class'] === $queried ) {
					$pdata_value = (float) $row['sum_hit'];
				}
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '&nbsp;%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100&nbsp;%';
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0&nbsp;%';
				} else {
					$result[ 'kpi-main-' . $queried ] = '-';
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
			$result[ 'kpi-bottom-' . $queried ] = '<span class="pose-kpi-large-bottom-text">' . sprintf( esc_html__( '%s hits', 'sessions' ), Conversion::number_shorten( (int) $data_value, 2, false, '&nbsp;' ) ) . '</span>';
		}
		return $result;
	}

	/**
	 * Get the title selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_title_selector() {
		switch ( $this->type ) {
			case 'classes':
				$title = esc_html__( 'Classes', 'sessions' );
				break;
			case 'types':
				$title = esc_html__( 'Device Types', 'sessions' );
				break;
			case 'clients':
				$title = esc_html__( 'Client Types', 'sessions' );
				break;
			case 'libraries':
				$title = esc_html__( 'Libraries', 'sessions' );
				break;
			case 'applications':
				$title = esc_html__( 'Mobile Applications', 'sessions' );
				break;
			case 'feeds':
				$title = esc_html__( 'Feed Readers', 'sessions' );
				break;
			case 'medias':
				$title = esc_html__( 'Media Players', 'sessions' );
				break;
			case 'browsers':
				$title = esc_html__( 'Browsers', 'sessions' );
				break;
			case 'bots':
				$title = esc_html__( 'Bots', 'sessions' );
				break;
			case 'devices':
				$title = esc_html__( 'Devices', 'sessions' );
				break;
			case 'oses':
				$title = esc_html__( 'Operating Systems', 'sessions' );
				break;
			case 'browser':
				$title = esc_html__( 'Browser Details', 'sessions' );
				break;
			case 'bot':
				$title = esc_html__( 'Bot Details', 'sessions' );
				break;
			case 'device':
				$title = esc_html__( 'Device Details', 'sessions' );
				break;
			case 'os':
				$title = esc_html__( 'Operating System Details', 'sessions' );
				break;

		}
		$breadcrumbs[] = [
			'title'    => esc_html__( 'Main Summary', 'sessions' ),
			'subtitle' => sprintf( esc_html__( 'Return to Sessions main page.', 'sessions' ) ),
			'url'      => $this->get_url( [ 'id', 'type', 'extended' ] ),
		];
		$result        = '<select name="sources" id="sources" class="pose-select sources" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '~-' . $breadcrumb['subtitle'] . '-~</span></option>';
		}
		$result .= '</select>';
		$result .= '';
		return $result;
	}

	/**
	 * Get the site selection bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_site_bar() {
		if ( Role::SINGLE_ADMIN === Role::admin_type() ) {
			return '';
		}
		if ( 'all' === $this->site ) {
			$result = '<span class="pose-site-text">' . esc_html__( 'All Sites', 'sessions' ) . '</span>';
		} else {
			if ( Role::SUPER_ADMIN === Role::admin_type() ) {
				$quit   = '<a href="' . $this->get_url( [ 'site' ] ) . '"><img style="width:12px;vertical-align:text-top;" src="' . Feather\Icons::get_base64( 'x-circle', 'none', '#FFFFFF' ) . '" /></a>';
				$result = '<span class="pose-site-button">' . sprintf( esc_html__( 'Site ID %s', 'sessions' ), $this->site ) . $quit . '</span>';
			} else {
				$result = '<span class="pose-site-text">' . sprintf( esc_html__( 'Site ID %s', 'sessions' ), $this->site ) . '</span>';
			}
		}
		return '<span class="pose-site">' . $result . '</span>';
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$subtitle = '';
		switch ( $this->type ) {
			case 'summary':
				$title = esc_html__( 'Main Summary', 'sessions' );
				break;
			case 'classes':
			case 'types':
			case 'clients':
			case 'libraries':
			case 'applications':
			case 'feeds':
			case 'medias':
			case 'browsers':
			case 'bots':
			case 'devices':
			case 'oses':
				$title = $this->get_title_selector();
				break;
			case 'browser':
				$browsers = Browser::getAvailableBrowsers();
				if ( array_key_exists( $this->id, $browsers ) ) {
					$subtitle = $browsers[ $this->id ];
				} else {
					$subtitle = __( 'Generic', 'sessions' );
				}
				$title = $this->get_title_selector();
				break;
			case 'bot':
				$subtitle = $this->id;
				$title    = $this->get_title_selector();
				break;
			case 'device':
				if ( array_key_exists( $this->id, DeviceParserAbstract::$deviceBrands ) ) {
					$subtitle = DeviceParserAbstract::$deviceBrands[ $this->id ] . ( '-' !== $this->extended ? ' ' . $this->extended : '' );
				} else {
					$subtitle = __( 'Generic', 'sessions' );
				}
				$title = $this->get_title_selector();
				break;
			case 'os':
				$os = OperatingSystem::getAvailableOperatingSystems();
				if ( array_key_exists( $this->id, $os ) ) {
					$subtitle = $os[ $this->id ];
				} else {
					$subtitle = __( 'Generic', 'sessions' );
				}
				$title = $this->get_title_selector();
				break;
		}
		$result  = '<div class="pose-box pose-box-full-line">';
		$result .= $this->get_site_bar();
		$result .= '<span class="pose-title">' . $title . '</span>';
		$result .= '<span class="pose-subtitle">' . $subtitle . '</span>';
		$result .= '<span class="pose-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the box_title.
	 *
	 * @param string $id The box or page id.
	 * @return string  The box title.
	 * @since 1.0.0
	 */
	public function get_box_title( $id ) {
		$result = '';
		switch ( $id ) {
			case 'classes-list':
				$result = esc_html__( 'All Classes', 'sessions' );
				break;
			case 'types-list':
				$result = esc_html__( 'All Device Types', 'sessions' );
				break;
			case 'clients-list':
				$result = esc_html__( 'All Client Types', 'sessions' );
				break;
			case 'libraries-list':
				$result = esc_html__( 'All Libraries', 'sessions' );
				break;
			case 'applications-list':
				$result = esc_html__( 'All Mobile Applications', 'sessions' );
				break;
			case 'feeds-list':
				$result = esc_html__( 'All Feed Readers', 'sessions' );
				break;
			case 'medias-list':
				$result = esc_html__( 'All Media Players', 'sessions' );
				break;
			case 'browsers-list':
				$result = esc_html__( 'All Browsers', 'sessions' );
				break;
			case 'bots-list':
				$result = esc_html__( 'All Bots', 'sessions' );
				break;
			case 'devices-list':
				$result = esc_html__( 'All Devices', 'sessions' );
				break;
			case 'oses-list':
				$result = esc_html__( 'All Operating Systems', 'sessions' );
				break;
		}
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
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'hit' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'mobile' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'desktop' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'bot' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'client' ) . '</div>';
		$result .= '<div class="pose-kpi-large">' . $this->get_large_kpi( 'engine' ) . '</div>';
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
			$help_calls = esc_html__( 'Hits variation.', 'sessions' );
			$detail     = '<span class="pose-chart-button not-ready left" id="pose-chart-button-calls" data-position="left" data-tooltip="' . $help_calls . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
			$result     = '<div class="pose-row">';
			$result    .= '<div class="pose-box pose-box-full-line">';
			$result    .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Metrics Variations', 'sessions' ) . '<span class="pose-module-more">' . $detail . '</span></span></div>';
			$result    .= '<div class="pose-module-content" id="pose-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result    .= '</div>';
			$result    .= '</div>';
			$result    .= $this->get_refresh_script(
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
	 * Get the a simple list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_list() {
		$result  = '<div class="pose-box pose-box-full-line">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . $this->get_box_title( $this->type . '-list' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-' . $this->type . '-list">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->type . '-list',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the sites list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_sites_list() {
		$result  = '<div class="pose-box pose-box-full-line">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'All Sites', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-sites">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'sites',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the top browser box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_browser_box() {
		$url     = $this->get_url( [ 'browser' ], [ 'type' => 'browsers' ] );
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all browsers.', 'sessions' );
		$result  = '<div class="pose-50-module-left">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Browsers', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-browsers">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-browsers',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top bot box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_bot_box() {
		$url     = $this->get_url( [ 'bot' ], [ 'type' => 'bots' ] );
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all bots.', 'sessions' );
		$result  = '<div class="pose-50-module-right">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Bots', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-bots">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-bots',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top device box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_device_box() {
		$url     = $this->get_url( [ 'device' ], [ 'type' => 'devices' ] );
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all devices.', 'sessions' );
		$result  = '<div class="pose-50-module-left">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Devices', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-devices">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-devices',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top oses box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_os_box() {
		$url     = $this->get_url( [ 'os' ], [ 'type' => 'oses' ] );
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all operating systems.', 'sessions' );
		$result  = '<div class="pose-50-module-right">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Operating Systems', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-oses">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-oses',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the simple top oses box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_simpletop_os_box() {
		if ( 'browser' === $this->type ) {
			$position = 'right';
		} else {
			$position = 'left';
		}
		$result  = '<div class="pose-50-module-' . $position . '">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Operating Systems', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-oses">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-oses',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the simple top browser box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_simpletop_browser_box() {
		if ( 'os' === $this->type || 'device' === $this->type ) {
			$position = 'right';
		} else {
			$position = 'left';
		}
		$result  = '<div class="pose-50-module-' . $position . '">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Browsers', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-browsers">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-browsers',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the simple top version box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_simpletop_version_box() {
		if ( 'browser' === $this->type || 'os' === $this->type ) {
			$position = 'left';
		} else {
			$position = 'right';
		}
		$result  = '<div class="pose-50-module-left">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Top Versions', 'sessions' ) . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-top-versions">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-versions',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the classes box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_classes_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'classes',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all classes.', 'sessions' );
		$result  = '<div class="pose-33-module pose-33-left-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Classes', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-classes">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'classes',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the types box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_types_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'types',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all device types.', 'sessions' );
		$result  = '<div class="pose-33-module pose-33-center-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Device Types', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-types">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'types',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the clients box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_clients_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'clients',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all client types.', 'sessions' );
		$result  = '<div class="pose-33-module pose-33-right-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Client Types', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-clients">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'clients',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the libraries box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_libraries_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'libraries',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all libraries.', 'sessions' );
		$result  = '<div class="pose-25-module pose-25-left-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Libraries', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-libraries">' . $this->get_graph_placeholder( 70 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'libraries',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the applications box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_applications_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'applications',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all applications.', 'sessions' );
		$result  = '<div class="pose-25-module pose-25-center-left-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Mobile Applications', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-applications">' . $this->get_graph_placeholder( 70 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'applications',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the feeds box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_feeds_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'feeds',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all feed-readers.', 'sessions' );
		$result  = '<div class="pose-25-module pose-25-center-right-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Feed Readers', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-feeds">' . $this->get_graph_placeholder( 70 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'feeds',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the medias box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_medias_box() {
		$url     = $this->get_url(
			[],
			[
				'type' => 'medias',
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$help    = esc_html__( 'View the details of all media players.', 'sessions' );
		$result  = '<div class="pose-25-module pose-25-right-module">';
		$result .= '<div class="pose-module-title-bar"><span class="pose-module-title">' . esc_html__( 'Media Players', 'sessions' ) . '</span><span class="pose-module-more left" data-position="left" data-tooltip="' . $help . '">' . $detail . '</span></div>';
		$result .= '<div class="pose-module-content" id="pose-medias">' . $this->get_graph_placeholder( 70 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'medias',
				'queried' => 4,
			]
		);
		return $result;
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
			case 'hit':
				$icon  = Feather\Icons::get_base64( 'hash', 'none', '#73879C' );
				$title = esc_html_x( 'Hits Number', 'Noun - Number of hits.', 'sessions' );
				$help  = esc_html__( 'Number of hits.', 'sessions' );
				break;
			case 'mobile':
				$icon  = Feather\Icons::get_base64( 'smartphone', 'none', '#73879C' );
				$title = esc_html_x( 'Mobile', 'Noun - Percentage of mobile hits.', 'sessions' );
				$help  = esc_html__( 'Ratio of hits done by mobiles.', 'sessions' );
				break;
			case 'desktop':
				$icon  = Feather\Icons::get_base64( 'monitor', 'none', '#73879C' );
				$title = esc_html_x( 'Desktop', 'Noun - Percentage of desktop hits', 'sessions' );
				$help  = esc_html__( 'Ratio of hits done by desktops.', 'sessions' );
				break;
			case 'bot':
				$icon  = Feather\Icons::get_base64( 'server', 'none', '#73879C' );
				$title = esc_html_x( 'Bot', 'Noun - Percentage of bot hits', 'sessions' );
				$help  = esc_html__( 'Ratio of hits done by bots.', 'sessions' );
				break;
			case 'client':
				$icon  = Feather\Icons::get_base64( 'users', 'none', '#73879C' );
				$title = esc_html_x( 'Clients', 'Noun - Number of distinct clients.', 'sessions' );
				$help  = esc_html__( 'Number of distinct clients.', 'sessions' );
				break;
			case 'engine':
				$icon  = Feather\Icons::get_base64( 'settings', 'none', '#73879C' );
				$title = esc_html_x( 'Engines', 'Noun - Number of distinct engines.', 'sessions' );
				$help  = esc_html__( 'Number of distinct engines.', 'sessions' );
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
		if ( '' !== $this->id ) {
			$result .= '  id:"' . $this->id . '",';
		}
		if ( '' !== $this->extended ) {
			$result .= '  extended:"' . rawurlencode( $this->extended ) . '",';
		}
		$result .= '  type:"' . $this->type . '",';
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".pose-chart-button").removeClass("not-ready");';
			$result .= '$("#pose-chart-button-calls").addClass("active");';
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
		$params         = [];
		$params['type'] = $this->type;
		$params['site'] = $this->site;
		if ( '' !== $this->id ) {
			$params['id'] = $this->id;
		}
		if ( '' !== $this->extended ) {
			$params['extended'] = rawurlencode( $this->extended );
		}
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'tools.php?page=pose-viewer' );
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
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], ( '' !== $this->extended ? [ 'extended' => $this->extended ] : [] ), false ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
