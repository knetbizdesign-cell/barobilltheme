<?php
/**
 * Main배너 인사이트 — 클릭·유입 조회 집계
 *
 * @package borobill_theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function borobill_get_banner_insights_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_banner_insights';
}

/**
 * @return string
 */
function borobill_get_banner_insight_cookie_name() {
	return 'borobill_bn_src';
}

/**
 * @param int $slide_id
 * @return string
 */
function borobill_get_hero_banner_insight_key( $slide_id ) {
	return 'hero_' . (int) $slide_id;
}

/**
 * @param int $slide_id
 * @return string
 */
function borobill_get_bottom_banner_insight_key( $slide_id ) {
	return 'bottom_' . (int) $slide_id;
}

/**
 * @param string $banner_key
 * @return bool
 */
function borobill_is_valid_banner_insight_key( $banner_key ) {
	return (bool) preg_match( '/^(hero|bottom)_\d+$/', (string) $banner_key );
}

/**
 * @param string $banner_key
 * @return string
 */
function borobill_get_banner_target_url_by_key( $banner_key ) {
	$banner_key = (string) $banner_key;

	if ( preg_match( '/^hero_(\d+)$/', $banner_key, $matches ) ) {
		$slide_id = (int) $matches[1];
		if ( $slide_id < 1 ) {
			return '';
		}

		return (string) borobill_get_hero_slide_stored_option( 'borobill_hero_button_url_' . $slide_id, '' );
	}

	if ( preg_match( '/^bottom_(\d+)$/', $banner_key, $matches ) ) {
		$slide_id = (int) $matches[1];
		if ( $slide_id < 1 ) {
			return '';
		}

		$url = function_exists( 'borobill_get_bottom_banner_slide_stored_option' )
			? (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_url_' . $slide_id, '' )
			: (string) get_option( 'borobill_bottom_banner_url_' . $slide_id, '' );

		return $url;
	}

	return '';
}

/**
 * @param string $url
 * @return string
 */
function borobill_normalize_insight_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return $url;
	}

	$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : 'https';
	$host   = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
	$path   = isset( $parts['path'] ) ? untrailingslashit( (string) $parts['path'] ) : '';
	if ( '' === $path ) {
		$path = '/';
	}

	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( (string) $parts['query'], $query );
	}
	unset( $query['bb_bn'] );
	ksort( $query );

	$normalized = $scheme . '://' . $host . $path;
	if ( ! empty( $query ) ) {
		$normalized .= '?' . http_build_query( $query );
	}

	return $normalized;
}

/**
 * @return string
 */
function borobill_get_current_request_url() {
	if ( is_singular() ) {
		return (string) get_permalink( get_queried_object_id() );
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( '' === $request_uri ) {
		return home_url( '/' );
	}

	return home_url( $request_uri );
}

/**
 * @return string
 */
function borobill_get_banner_insights_daily_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_banner_insights_daily';
}

/**
 * 배너 인사이트 일별 테이블 생성
 */
function borobill_ensure_banner_insights_daily_table() {
	global $wpdb;

	$table_name = borobill_get_banner_insights_daily_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_banner_insights_daily_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		banner_key varchar(64) NOT NULL,
		metric_date date NOT NULL,
		clicks bigint(20) unsigned NOT NULL DEFAULT 0,
		landing_views bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (banner_key, metric_date),
		KEY metric_date (metric_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_banner_insights_daily_db_version', $version, false );
}

/**
 * 배너 인사이트 테이블 생성
 */
function borobill_ensure_banner_insights_table() {
	global $wpdb;

	borobill_ensure_banner_insights_daily_table();

	$table_name = borobill_get_banner_insights_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_banner_insights_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		banner_key varchar(64) NOT NULL,
		clicks bigint(20) unsigned NOT NULL DEFAULT 0,
		landing_views bigint(20) unsigned NOT NULL DEFAULT 0,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (banner_key)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_banner_insights_db_version', $version, false );
}
add_action( 'init', 'borobill_ensure_banner_insights_table', 2 );

/**
 * @param string $banner_key
 * @return array{clicks:int,landing_views:int}
 */
function borobill_get_banner_insight_metrics( $banner_key ) {
	global $wpdb;

	$defaults = array(
		'clicks'        => 0,
		'landing_views' => 0,
	);

	if ( ! borobill_is_valid_banner_insight_key( $banner_key ) ) {
		return $defaults;
	}

	$table_name = borobill_get_banner_insights_table_name();
	$row        = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT clicks, landing_views FROM {$table_name} WHERE banner_key = %s",
			$banner_key
		),
		ARRAY_A
	);

	if ( ! is_array( $row ) ) {
		return $defaults;
	}

	return array(
		'clicks'        => max( 0, (int) $row['clicks'] ),
		'landing_views' => max( 0, (int) $row['landing_views'] ),
	);
}

/**
 * @param string $banner_key
 * @param string $field clicks|landing_views
 */
function borobill_increment_banner_insight_metric( $banner_key, $field ) {
	global $wpdb;

	if ( ! borobill_is_valid_banner_insight_key( $banner_key ) ) {
		return;
	}

	if ( ! in_array( $field, array( 'clicks', 'landing_views' ), true ) ) {
		return;
	}

	borobill_ensure_banner_insights_table();

	$table_name = borobill_get_banner_insights_table_name();
	$now        = current_time( 'mysql' );
	$existing   = borobill_get_banner_insight_metrics( $banner_key );

	if ( 0 === $existing['clicks'] && 0 === $existing['landing_views'] ) {
		$wpdb->insert(
			$table_name,
			array(
				'banner_key'    => $banner_key,
				'clicks'        => 'clicks' === $field ? 1 : 0,
				'landing_views' => 'landing_views' === $field ? 1 : 0,
				'updated_at'    => $now,
			),
			array( '%s', '%d', '%d', '%s' )
		);
		borobill_increment_banner_insight_daily_metric( $banner_key, $field );
		return;
	}

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table_name} SET {$field} = {$field} + 1, updated_at = %s WHERE banner_key = %s",
			$now,
			$banner_key
		)
	);

	borobill_increment_banner_insight_daily_metric( $banner_key, $field );
}

/**
 * @param string $banner_key
 * @param string $field clicks|landing_views
 */
function borobill_increment_banner_insight_daily_metric( $banner_key, $field ) {
	global $wpdb;

	if ( ! borobill_is_valid_banner_insight_key( $banner_key ) ) {
		return;
	}

	if ( ! in_array( $field, array( 'clicks', 'landing_views' ), true ) ) {
		return;
	}

	borobill_ensure_banner_insights_daily_table();

	$table_name = borobill_get_banner_insights_daily_table_name();
	$metric_date = current_time( 'Y-m-d' );
	$existing    = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT {$field} FROM {$table_name} WHERE banner_key = %s AND metric_date = %s",
			$banner_key,
			$metric_date
		)
	);

	if ( null === $existing ) {
		$wpdb->insert(
			$table_name,
			array(
				'banner_key'    => $banner_key,
				'metric_date'   => $metric_date,
				'clicks'        => 'clicks' === $field ? 1 : 0,
				'landing_views' => 'landing_views' === $field ? 1 : 0,
			),
			array( '%s', '%s', '%d', '%d' )
		);
		return;
	}

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$table_name} SET {$field} = {$field} + 1 WHERE banner_key = %s AND metric_date = %s",
			$banner_key,
			$metric_date
		)
	);
}

/**
 * @param int $days
 * @return array<int,array{date:string,label:string,clicks:int,landing_views:int}>
 */
function borobill_get_banner_insight_aggregate_daily_rows( $days = 14 ) {
	global $wpdb;

	$days = max( 1, min( 90, (int) $days ) );
	borobill_ensure_banner_insights_daily_table();

	$timezone = wp_timezone();
	$end      = new DateTimeImmutable( 'today', $timezone );
	$rows     = array();

	for ( $offset = $days - 1; $offset >= 0; $offset-- ) {
		$date = $end->sub( new DateInterval( 'P' . $offset . 'D' ) );
		$key  = $date->format( 'Y-m-d' );
		$rows[ $key ] = array(
			'date'          => $key,
			'label'         => $date->format( 'n/j' ),
			'clicks'        => 0,
			'landing_views' => 0,
		);
	}

	$from_date  = $end->sub( new DateInterval( 'P' . ( $days - 1 ) . 'D' ) )->format( 'Y-m-d' );
	$table_name = borobill_get_banner_insights_daily_table_name();
	$results    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT metric_date, SUM(clicks) AS clicks, SUM(landing_views) AS landing_views
			FROM {$table_name}
			WHERE metric_date >= %s
			GROUP BY metric_date
			ORDER BY metric_date ASC",
			$from_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$metric_date = (string) ( $result['metric_date'] ?? '' );
			if ( ! isset( $rows[ $metric_date ] ) ) {
				continue;
			}
			$rows[ $metric_date ]['clicks']        = max( 0, (int) ( $result['clicks'] ?? 0 ) );
			$rows[ $metric_date ]['landing_views'] = max( 0, (int) ( $result['landing_views'] ?? 0 ) );
		}
	}

	return array_values( $rows );
}

/**
 * @param array<int,array<string,mixed>> $rows
 * @param string                         $metric clicks|landing_views
 * @return array<int,array{title:string,views:int,edit_url:string}>
 */
function borobill_get_banner_insight_bar_chart_rows( array $rows, $metric ) {
	$chart_rows = array();

	foreach ( $rows as $row ) {
		$chart_rows[] = array(
			'title'    => (string) ( $row['label'] ?? '' ),
			'views'    => max( 0, (int) ( $row[ $metric ] ?? 0 ) ),
			'edit_url' => '',
		);
	}

	return $chart_rows;
}

/**
 * @param array<int,array{date:string,label:string,clicks:int,landing_views:int}> $daily_rows
 * @param string                                                                  $metric clicks|landing_views
 * @return array<int,array{date:string,label:string,views:int}>
 */
function borobill_map_banner_insight_daily_rows_for_line_chart( array $daily_rows, $metric ) {
	$mapped = array();

	foreach ( $daily_rows as $row ) {
		$mapped[] = array(
			'date'  => (string) ( $row['date'] ?? '' ),
			'label' => (string) ( $row['label'] ?? '' ),
			'views' => max( 0, (int) ( $row[ $metric ] ?? 0 ) ),
		);
	}

	return $mapped;
}

/**
 * @return void
 */
function borobill_clear_banner_src_cookie() {
	if ( headers_sent() ) {
		return;
	}

	setcookie( borobill_get_banner_insight_cookie_name(), '', time() - HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}

/**
 * 배너 링크 도착 집계
 */
function borobill_track_banner_landing_view() {
	if ( is_admin() ) {
		return;
	}

	$cookie_name = borobill_get_banner_insight_cookie_name();
	$banner_key  = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_key( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';
	if ( '' === $banner_key || ! borobill_is_valid_banner_insight_key( $banner_key ) ) {
		return;
	}

	$target_url  = borobill_get_banner_target_url_by_key( $banner_key );
	$target_norm = borobill_normalize_insight_url( $target_url );
	$current_norm = borobill_normalize_insight_url( borobill_get_current_request_url() );

	if ( '' === $target_norm || $target_norm !== $current_norm ) {
		return;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		borobill_clear_banner_src_cookie();
		return;
	}

	$view_cookie = 'borobill_bn_lv_' . md5( $banner_key . '|' . $current_norm );
	if ( isset( $_COOKIE[ $view_cookie ] ) ) {
		borobill_clear_banner_src_cookie();
		return;
	}

	borobill_increment_banner_insight_metric( $banner_key, 'landing_views' );

	$expires = strtotime( 'tomorrow', (int) current_time( 'timestamp' ) );
	if ( false === $expires ) {
		$expires = time() + DAY_IN_SECONDS;
	}

	if ( ! headers_sent() ) {
		setcookie( $view_cookie, '1', $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}

	borobill_clear_banner_src_cookie();
}
add_action( 'template_redirect', 'borobill_track_banner_landing_view', 25 );

/**
 * 인사이트 조회 기간 → 시작·종료일
 *
 * @param array $context borobill_get_stats_visitor_chart_context() 결과
 * @return array{from:string,to:string}
 */
function borobill_get_banner_insight_period_date_range( array $context ) {
	if ( function_exists( 'borobill_resolve_stats_period_dates' ) ) {
		list( $from, $to ) = borobill_resolve_stats_period_dates( $context );

		return array(
			'from' => $from,
			'to'   => $to,
		);
	}

	$today = wp_date( 'Y-m-d' );

	if ( 'custom' === ( $context['period'] ?? '' ) ) {
		$from = isset( $context['from'] ) ? (string) $context['from'] : $today;
		$to   = isset( $context['to'] ) ? (string) $context['to'] : $today;

		if ( $from > $to ) {
			$swap = $from;
			$from = $to;
			$to   = $swap;
		}

		return array(
			'from' => $from,
			'to'   => $to,
		);
	}

	if ( ! empty( $context['from'] ) && ! empty( $context['to'] ) ) {
		return array(
			'from' => (string) $context['from'],
			'to'   => (string) $context['to'],
		);
	}

	if ( '1' === ( $context['period'] ?? '1' ) ) {
		return array(
			'from' => $today,
			'to'   => $today,
		);
	}

	$days = isset( $context['days'] ) ? max( 1, (int) $context['days'] ) : 1;

	return array(
		'from' => wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) ),
		'to'   => $today,
	);
}

/**
 * @param string $from_date
 * @param string $to_date
 * @return array<string,array{clicks:int,landing_views:int}>
 */
function borobill_get_banner_insight_period_metrics_map( $from_date, $to_date ) {
	global $wpdb;

	borobill_ensure_banner_insights_daily_table();

	$table_name = borobill_get_banner_insights_daily_table_name();
	$results    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT banner_key, SUM(clicks) AS clicks, SUM(landing_views) AS landing_views
			FROM {$table_name}
			WHERE metric_date >= %s AND metric_date <= %s
			GROUP BY banner_key",
			$from_date,
			$to_date
		),
		ARRAY_A
	);

	$map = array();
	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$key = (string) ( $result['banner_key'] ?? '' );
			if ( '' === $key ) {
				continue;
			}
			$map[ $key ] = array(
				'clicks'        => max( 0, (int) ( $result['clicks'] ?? 0 ) ),
				'landing_views' => max( 0, (int) ( $result['landing_views'] ?? 0 ) ),
			);
		}
	}

	return $map;
}

/**
 * @param array|null $context borobill_get_stats_visitor_chart_context() 결과
 * @return array{from:string,to:string,metrics_map:array<string,array{clicks:int,landing_views:int}>}
 */
function borobill_get_banner_insight_report_context( $context = null ) {
	if ( null === $context && function_exists( 'borobill_get_stats_visitor_chart_context' ) ) {
		$context = borobill_get_stats_visitor_chart_context();
	}

	$range = is_array( $context ) ? borobill_get_banner_insight_period_date_range( $context ) : array(
		'from' => wp_date( 'Y-m-d' ),
		'to'   => wp_date( 'Y-m-d' ),
	);

	return array(
		'from'        => $range['from'],
		'to'          => $range['to'],
		'metrics_map' => borobill_get_banner_insight_period_metrics_map( $range['from'], $range['to'] ),
	);
}

/**
 * @param array|null $context borobill_get_stats_visitor_chart_context() 결과
 * @return array<int,array<string,mixed>>
 */
function borobill_get_banner_insight_report_rows( $context = null ) {
	$report      = borobill_get_banner_insight_report_context( $context );
	$metrics_map = $report['metrics_map'];
	$defaults    = array(
		'clicks'        => 0,
		'landing_views' => 0,
	);
	$rows        = array();

	if ( function_exists( 'borobill_get_hero_slide_order_ids' ) ) {
		$published_order = function_exists( 'borobill_get_published_hero_slide_order_ids' )
			? borobill_get_published_hero_slide_order_ids()
			: borobill_get_hero_slide_order_ids();

		foreach ( borobill_get_hero_slide_order_ids() as $slide_id ) {
			$slide_id = (int) $slide_id;
			$key      = borobill_get_hero_banner_insight_key( $slide_id );
			$metrics  = isset( $metrics_map[ $key ] ) ? $metrics_map[ $key ] : $defaults;
			$title    = function_exists( 'borobill_get_hero_slide_list_title' )
				? borobill_get_hero_slide_list_title( $slide_id )
				: ( '슬라이드 ' . $slide_id );
			$url      = (string) borobill_get_hero_slide_stored_option( 'borobill_hero_button_url_' . $slide_id, '' );

			$carousel_index = array_search( $slide_id, $published_order, true );
			$carousel_order = false !== $carousel_index ? (int) $carousel_index + 1 : 0;

			$rows[] = array(
				'key'            => $key,
				'label'          => $title,
				'type_label'     => '히어로 슬라이드',
				'url'            => $url,
				'carousel_order' => $carousel_order,
				'clicks'         => $metrics['clicks'],
				'landing_views'  => $metrics['landing_views'],
			);
		}
	}

	return $rows;
}

/**
 * 하단배너 인사이트 행 (슬라이드 타이틀 + 클릭/방문 수)
 *
 * @param array|null $context borobill_get_stats_visitor_chart_context() 결과
 * @return array<int,array<string,mixed>>
 */
function borobill_get_bottom_banner_insight_report_rows( $context = null ) {
	$report      = borobill_get_banner_insight_report_context( $context );
	$metrics_map = $report['metrics_map'];
	$defaults    = array(
		'clicks'        => 0,
		'landing_views' => 0,
	);
	$rows        = array();

	if ( ! function_exists( 'borobill_get_bottom_banner_slide_order_ids' ) ) {
		return $rows;
	}

	$published_order = function_exists( 'borobill_get_published_bottom_banner_slide_order_ids' )
		? borobill_get_published_bottom_banner_slide_order_ids()
		: borobill_get_bottom_banner_slide_order_ids();

	foreach ( borobill_get_bottom_banner_slide_order_ids() as $slide_id ) {
		$slide_id = (int) $slide_id;
		$key      = borobill_get_bottom_banner_insight_key( $slide_id );
		$metrics  = isset( $metrics_map[ $key ] ) ? $metrics_map[ $key ] : $defaults;
		$title    = function_exists( 'borobill_get_bottom_banner_slide_list_title' )
			? borobill_get_bottom_banner_slide_list_title( $slide_id )
			: ( '슬라이드 ' . $slide_id );
		$url      = function_exists( 'borobill_get_bottom_banner_slide_stored_option' )
			? (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_url_' . $slide_id, '' )
			: (string) get_option( 'borobill_bottom_banner_url_' . $slide_id, '' );

		$carousel_index = array_search( $slide_id, $published_order, true );
		$carousel_order = false !== $carousel_index ? (int) $carousel_index + 1 : 0;

		$rows[] = array(
			'key'            => $key,
			'label'          => $title,
			'type_label'     => '하단 배너',
			'url'            => $url,
			'carousel_order' => $carousel_order,
			'clicks'         => $metrics['clicks'],
			'landing_views'  => $metrics['landing_views'],
		);
	}

	return $rows;
}

/**
 * 프론트: 배너 클릭 AJAX
 */
function borobill_ajax_track_banner_click() {
	check_ajax_referer( 'borobill_banner_insight', 'nonce' );

	$banner_key = isset( $_POST['banner_key'] ) ? sanitize_key( wp_unslash( $_POST['banner_key'] ) ) : '';
	if ( ! borobill_is_valid_banner_insight_key( $banner_key ) ) {
		wp_send_json_error( array( 'message' => 'invalid banner' ), 400 );
	}

	borobill_increment_banner_insight_metric( $banner_key, 'clicks' );

	wp_send_json_success(
		array(
			'banner_key' => $banner_key,
		)
	);
}
add_action( 'wp_ajax_borobill_track_banner_click', 'borobill_ajax_track_banner_click' );
add_action( 'wp_ajax_nopriv_borobill_track_banner_click', 'borobill_ajax_track_banner_click' );

/**
 * 인사이트 페이지: 기간 선택 폼
 */
function borobill_banner_insights_footer_script() {
	if ( ! function_exists( 'borobill_is_main_banner_stats_admin_page' ) || ! borobill_is_main_banner_stats_admin_page() ) {
		return;
	}
	?>
	<script>
	(function () {
		function initDailyPeriodForm() {
			var form = document.querySelector('.borobill-banner-insights__period-form');
			if (!form) {
				return;
			}

			var select = form.querySelector('#borobill-banner-insight-period');
			var customWrap = form.querySelector('.borobill-stats-daily-period-form__custom');
			var fromInput = form.querySelector('#borobill-banner-insight-from');
			var toInput = form.querySelector('#borobill-banner-insight-to');

			function toggleCustom(show) {
				if (!customWrap) {
					return;
				}
				var isCustom = typeof show === 'boolean' ? show : (select && select.value === 'custom');
				customWrap.classList.toggle('is-visible', isCustom);
				customWrap.hidden = !isCustom;
			}

			if (select) {
				select.addEventListener('change', function () {
					toggleCustom();
					form.submit();
				});
			}

			[fromInput, toInput].forEach(function (input) {
				if (!input) {
					return;
				}
				input.addEventListener('change', function () {
					if (select && select.value === 'custom') {
						form.submit();
					}
				});
			});

			toggleCustom();
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initDailyPeriodForm);
		} else {
			initDailyPeriodForm();
		}
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'borobill_banner_insights_footer_script' );
