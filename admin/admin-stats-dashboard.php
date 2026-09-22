<?php
/**
 * 관리자 > 통계 — 마케팅 지표 대시보드
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 일자별 조회수 테이블명 (레거시 — 차트는 접속인원 테이블 사용)
 */
function borobill_get_daily_views_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_daily_views';
}

/**
 * 시간대별 조회수 테이블명
 */
function borobill_get_hourly_views_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_hourly_views';
}

/**
 * 게시글별 일자 조회수 테이블명
 */
function borobill_get_post_daily_views_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_post_daily_views';
}

/**
 * 일자별 사이트 접속인원 테이블명
 */
function borobill_get_daily_visitors_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_daily_visitors';
}

/**
 * 게시글별 실제 조회수 meta_key
 */
function borobill_get_post_view_count_meta_key() {
	return '_borobill_post_views';
}

/**
 * @param int $post_id
 * @return int
 */
function borobill_get_post_view_count( $post_id ) {
	$views = get_post_meta( (int) $post_id, borobill_get_post_view_count_meta_key(), true );

	return is_numeric( $views ) ? max( 0, (int) $views ) : 0;
}

/**
 * @param int $post_id
 */
function borobill_increment_post_view_count( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}

	update_post_meta( $post_id, borobill_get_post_view_count_meta_key(), borobill_get_post_view_count( $post_id ) + 1 );
}

/**
 * 일자별 조회수 테이블 생성
 */
function borobill_ensure_daily_views_table() {
	global $wpdb;

	$table_name = borobill_get_daily_views_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_daily_views_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		view_date date NOT NULL,
		views bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (view_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_daily_views_db_version', $version, false );
}

/**
 * 시간대별 조회수 테이블 생성
 */
function borobill_ensure_hourly_views_table() {
	global $wpdb;

	$table_name = borobill_get_hourly_views_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_hourly_views_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		view_date date NOT NULL,
		view_hour tinyint(2) unsigned NOT NULL,
		views bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (view_date, view_hour)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_hourly_views_db_version', $version, false );
}

/**
 * 게시글별 일자 조회수 테이블 생성
 */
function borobill_ensure_post_daily_views_table() {
	global $wpdb;

	$table_name = borobill_get_post_daily_views_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_post_daily_views_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		post_id bigint(20) unsigned NOT NULL,
		view_date date NOT NULL,
		views bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (post_id, view_date),
		KEY view_date (view_date),
		KEY views (views)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_post_daily_views_db_version', $version, false );
}

/**
 * 방문자 식별(일자) 로그 테이블명
 */
function borobill_get_visitor_days_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_visitor_days';
}

/**
 * 재방문자 클릭(진입) 누적 테이블명 — 세션 기준 매 진입 +1
 */
function borobill_get_daily_returning_hits_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_daily_returning_hits';
}

/**
 * 일자별 체류시간 집계 테이블명
 */
function borobill_get_daily_engagement_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_daily_engagement';
}

/**
 * 방문자 식별 로그 테이블 생성
 */
function borobill_ensure_visitor_days_table() {
	global $wpdb;

	$table_name = borobill_get_visitor_days_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_visitor_days_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		visitor_key char(32) NOT NULL,
		visit_date date NOT NULL,
		is_returning tinyint(1) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (visitor_key, visit_date),
		KEY visit_date (visit_date),
		KEY is_returning (is_returning)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_visitor_days_db_version', $version, false );
}

/**
 * 재방문자 클릭 누적 테이블 생성
 */
function borobill_ensure_daily_returning_hits_table() {
	global $wpdb;

	$table_name = borobill_get_daily_returning_hits_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_daily_returning_hits_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		visit_date date NOT NULL,
		hits int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (visit_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_daily_returning_hits_db_version', $version, false );
}

/**
 * 재방문자 진입(클릭) 1회 누적 — 당일 합계
 */
function borobill_increment_returning_hit_count() {
	global $wpdb;

	borobill_ensure_daily_returning_hits_table();

	$table = borobill_get_daily_returning_hits_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (visit_date, hits) VALUES (%s, 1)
			ON DUPLICATE KEY UPDATE hits = hits + 1",
			$today
		)
	);
}

/**
 * 기간 재방문자 클릭(진입) 합계
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return int
 */
function borobill_sum_returning_hits_in_range( $from, $to ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return 0;
	}

	borobill_ensure_daily_returning_hits_table();

	$table = borobill_get_daily_returning_hits_table_name();
	$sum   = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(hits), 0) FROM {$table}
			WHERE visit_date >= %s AND visit_date <= %s",
			$from,
			$to
		)
	);

	return max( 0, (int) $sum );
}

/**
 * 일자별 체류시간 집계 테이블 생성
 */
function borobill_ensure_daily_engagement_table() {
	global $wpdb;

	$table_name = borobill_get_daily_engagement_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_daily_engagement_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		visit_date date NOT NULL,
		stay_total_seconds bigint(20) unsigned NOT NULL DEFAULT 0,
		stay_sessions int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (visit_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_daily_engagement_db_version', $version, false );
}

/**
 * 체류 시간 표시 (분/초)
 *
 * @param int $seconds
 * @return string
 */
function borobill_format_stay_duration( $seconds ) {
	$seconds = max( 0, (int) $seconds );
	$minutes = (int) floor( $seconds / 60 );
	$remain  = $seconds % 60;

	return sprintf( '%d분 %d초', $minutes, $remain );
}

/**
 * 장기 방문자 키 쿠키 확보
 *
 * @return array{key:string,is_new:bool}
 */
function borobill_get_or_create_visitor_key() {
	$cookie_name = 'borobill_vid';
	$existing    = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

	if ( preg_match( '/^[a-f0-9]{32}$/', $existing ) ) {
		return array(
			'key'    => $existing,
			'is_new' => false,
		);
	}

	$key = md5( wp_generate_password( 32, true, true ) . microtime( true ) . wp_rand() );

	if ( ! headers_sent() ) {
		setcookie( $cookie_name, $key, time() + ( YEAR_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE[ $cookie_name ] = $key;
	}

	return array(
		'key'    => $key,
		'is_new' => true,
	);
}

/**
 * 오늘 방문자 식별 로그 기록 (하루 1회)
 *
 * @param string $visitor_key
 * @param bool   $is_returning
 * @return bool 신규 삽입 여부
 */
function borobill_record_visitor_day( $visitor_key, $is_returning ) {
	global $wpdb;

	if ( ! preg_match( '/^[a-f0-9]{32}$/', (string) $visitor_key ) ) {
		return false;
	}

	borobill_ensure_visitor_days_table();

	$table = borobill_get_visitor_days_table_name();
	$today = wp_date( 'Y-m-d' );

	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$table} (visitor_key, visit_date, is_returning) VALUES (%s, %s, %d)",
			$visitor_key,
			$today,
			$is_returning ? 1 : 0
		)
	);

	return false !== $result && (int) $result > 0;
}

/**
 * 해당 방문자가 오늘 이전 방문 이력이 있는지
 *
 * @param string $visitor_key
 * @return bool
 */
function borobill_visitor_has_prior_visit( $visitor_key ) {
	global $wpdb;

	if ( ! preg_match( '/^[a-f0-9]{32}$/', (string) $visitor_key ) ) {
		return false;
	}

	borobill_ensure_visitor_days_table();

	$table = borobill_get_visitor_days_table_name();
	$today = wp_date( 'Y-m-d' );
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT 1 FROM {$table} WHERE visitor_key = %s AND visit_date < %s LIMIT 1",
			$visitor_key,
			$today
		)
	);

	return null !== $found;
}

/**
 * 기간 순방문자 / 재방문자
 * - unique: 쿠키 기준 DISTINCT 방문자 수 (기존 유지)
 * - returning: 재방문자의 페이지 진입(클릭) 누적 횟수
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return array{unique:int,returning:int}
 */
function borobill_get_period_visitor_identity_counts( $from, $to ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return array(
			'unique'    => 0,
			'returning' => 0,
		);
	}

	borobill_ensure_visitor_days_table();

	$table  = borobill_get_visitor_days_table_name();
	$unique = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT visitor_key) FROM {$table}
			WHERE visit_date >= %s AND visit_date <= %s",
			$from,
			$to
		)
	);

	// 재방문자: 세션(페이지 진입)마다 누적된 클릭/진입 횟수
	$returning = borobill_sum_returning_hits_in_range( $from, $to );

	return array(
		'unique'    => max( 0, $unique ),
		'returning' => max( 0, $returning ),
	);
}

/**
 * 기간 평균 체류 시간(초)
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return int
 */
function borobill_get_period_avg_stay_seconds( $from, $to ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return 0;
	}

	borobill_ensure_daily_engagement_table();

	$table  = borobill_get_daily_engagement_table_name();
	$row    = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(stay_total_seconds), 0) AS total_seconds,
				COALESCE(SUM(stay_sessions), 0) AS total_sessions
			FROM {$table}
			WHERE visit_date >= %s AND visit_date <= %s",
			$from,
			$to
		),
		ARRAY_A
	);

	$total_seconds  = isset( $row['total_seconds'] ) ? (int) $row['total_seconds'] : 0;
	$total_sessions = isset( $row['total_sessions'] ) ? (int) $row['total_sessions'] : 0;

	if ( $total_sessions <= 0 ) {
		return 0;
	}

	return (int) round( $total_seconds / $total_sessions );
}

/**
 * 체류 시간 세션 기록
 *
 * @param int $seconds
 */
function borobill_increment_daily_stay( $seconds ) {
	global $wpdb;

	$seconds = max( 1, min( 1800, (int) $seconds ) );
	borobill_ensure_daily_engagement_table();

	$table = borobill_get_daily_engagement_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (visit_date, stay_total_seconds, stay_sessions) VALUES (%s, %d, 1)
			ON DUPLICATE KEY UPDATE
				stay_total_seconds = stay_total_seconds + VALUES(stay_total_seconds),
				stay_sessions = stay_sessions + 1",
			$today,
			$seconds
		)
	);
}

/**
 * 체류 시간 AJAX 수신
 */
function borobill_ajax_track_stay() {
	check_ajax_referer( 'borobill_track_stay', 'nonce' );

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		wp_send_json_success();
	}

	$seconds = isset( $_POST['seconds'] ) ? (int) wp_unslash( $_POST['seconds'] ) : 0;
	if ( $seconds >= 3 ) {
		borobill_increment_daily_stay( $seconds );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_borobill_track_stay', 'borobill_ajax_track_stay' );
add_action( 'wp_ajax_nopriv_borobill_track_stay', 'borobill_ajax_track_stay' );

/**
 * 콘텐츠 읽기(스크롤 깊이) 일자 집계 테이블명
 */
function borobill_get_daily_read_stats_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_daily_read_stats';
}

/**
 * 콘텐츠 읽기 집계 테이블 생성
 */
function borobill_ensure_daily_read_stats_table() {
	global $wpdb;

	$table_name = borobill_get_daily_read_stats_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_daily_read_stats_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		read_date date NOT NULL,
		sessions int(10) unsigned NOT NULL DEFAULT 0,
		depth_sum int(10) unsigned NOT NULL DEFAULT 0,
		time_sum int(10) unsigned NOT NULL DEFAULT 0,
		reach_0 int(10) unsigned NOT NULL DEFAULT 0,
		reach_25 int(10) unsigned NOT NULL DEFAULT 0,
		reach_50 int(10) unsigned NOT NULL DEFAULT 0,
		reach_75 int(10) unsigned NOT NULL DEFAULT 0,
		reach_100 int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (read_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_daily_read_stats_db_version', $version, false );
}

/**
 * 게시글별 읽기 집계 테이블명
 */
function borobill_get_post_daily_read_stats_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_post_daily_read_stats';
}

/**
 * 게시글별 읽기 집계 테이블 생성
 */
function borobill_ensure_post_daily_read_stats_table() {
	global $wpdb;

	$table_name = borobill_get_post_daily_read_stats_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_post_daily_read_stats_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		post_id bigint(20) unsigned NOT NULL,
		read_date date NOT NULL,
		sessions int(10) unsigned NOT NULL DEFAULT 0,
		depth_sum int(10) unsigned NOT NULL DEFAULT 0,
		time_sum int(10) unsigned NOT NULL DEFAULT 0,
		reach_0 int(10) unsigned NOT NULL DEFAULT 0,
		reach_25 int(10) unsigned NOT NULL DEFAULT 0,
		reach_50 int(10) unsigned NOT NULL DEFAULT 0,
		reach_75 int(10) unsigned NOT NULL DEFAULT 0,
		reach_100 int(10) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (post_id, read_date),
		KEY read_date (read_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_post_daily_read_stats_db_version', $version, false );
}

/**
 * 도달 수치 → 구간별 이탈(최종 구간) 비율 5개 (합 100)
 *
 * @return array{0:float,1:float,2:float,3:float,4:float}
 */
function borobill_compute_read_dropoff_shares( $reach_0, $reach_25, $reach_50, $reach_75, $reach_100 ) {
	$r0   = max( 0, (int) $reach_0 );
	$r25  = max( 0, (int) $reach_25 );
	$r50  = max( 0, (int) $reach_50 );
	$r75  = max( 0, (int) $reach_75 );
	$r100 = max( 0, (int) $reach_100 );

	$bands = array(
		max( 0, $r0 - $r25 ),
		max( 0, $r25 - $r50 ),
		max( 0, $r50 - $r75 ),
		max( 0, $r75 - $r100 ),
		max( 0, $r100 ),
	);
	$total = array_sum( $bands );
	if ( $total <= 0 ) {
		return array( 0, 0, 0, 0, 0 );
	}

	$shares = array();
	$used   = 0;
	$last   = count( $bands ) - 1;
	foreach ( $bands as $index => $count ) {
		if ( $index === $last ) {
			$shares[] = (float) max( 0, 100 - $used );
			continue;
		}
		$pct      = (int) round( ( $count / $total ) * 100 );
		$shares[] = (float) $pct;
		$used    += $pct;
	}

	return $shares;
}

/**
 * 읽기 세션 1회 기록
 *
 * @param int $depth   0-100
 * @param int $seconds
 * @param int $post_id 게시글 ID (0이면 사이트 전체만)
 */
function borobill_record_read_session( $depth, $seconds, $post_id = 0 ) {
	global $wpdb;

	$depth   = max( 0, min( 100, (int) $depth ) );
	$seconds = max( 1, min( 1800, (int) $seconds ) );
	$post_id = max( 0, (int) $post_id );

	borobill_ensure_daily_read_stats_table();

	$table   = borobill_get_daily_read_stats_table_name();
	$today   = wp_date( 'Y-m-d' );
	$reach_0 = 1;
	$r25     = $depth >= 25 ? 1 : 0;
	$r50     = $depth >= 50 ? 1 : 0;
	$r75     = $depth >= 75 ? 1 : 0;
	$r100    = $depth >= 100 ? 1 : 0;

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table}
				(read_date, sessions, depth_sum, time_sum, reach_0, reach_25, reach_50, reach_75, reach_100)
			VALUES (%s, 1, %d, %d, %d, %d, %d, %d, %d)
			ON DUPLICATE KEY UPDATE
				sessions = sessions + 1,
				depth_sum = depth_sum + VALUES(depth_sum),
				time_sum = time_sum + VALUES(time_sum),
				reach_0 = reach_0 + VALUES(reach_0),
				reach_25 = reach_25 + VALUES(reach_25),
				reach_50 = reach_50 + VALUES(reach_50),
				reach_75 = reach_75 + VALUES(reach_75),
				reach_100 = reach_100 + VALUES(reach_100)",
			$today,
			$depth,
			$seconds,
			$reach_0,
			$r25,
			$r50,
			$r75,
			$r100
		)
	);

	if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	borobill_ensure_post_daily_read_stats_table();
	$post_table = borobill_get_post_daily_read_stats_table_name();

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$post_table}
				(post_id, read_date, sessions, depth_sum, time_sum, reach_0, reach_25, reach_50, reach_75, reach_100)
			VALUES (%d, %s, 1, %d, %d, %d, %d, %d, %d, %d)
			ON DUPLICATE KEY UPDATE
				sessions = sessions + 1,
				depth_sum = depth_sum + VALUES(depth_sum),
				time_sum = time_sum + VALUES(time_sum),
				reach_0 = reach_0 + VALUES(reach_0),
				reach_25 = reach_25 + VALUES(reach_25),
				reach_50 = reach_50 + VALUES(reach_50),
				reach_75 = reach_75 + VALUES(reach_75),
				reach_100 = reach_100 + VALUES(reach_100)",
			$post_id,
			$today,
			$depth,
			$seconds,
			$reach_0,
			$r25,
			$r50,
			$r75,
			$r100
		)
	);
}

/**
 * 읽기 진행 AJAX 수신
 */
function borobill_ajax_track_read() {
	check_ajax_referer( 'borobill_track_stay', 'nonce' );

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		wp_send_json_success();
	}

	$depth   = isset( $_POST['depth'] ) ? (int) wp_unslash( $_POST['depth'] ) : 0;
	$seconds = isset( $_POST['seconds'] ) ? (int) wp_unslash( $_POST['seconds'] ) : 0;
	$post_id = isset( $_POST['post_id'] ) ? (int) wp_unslash( $_POST['post_id'] ) : 0;

	if ( $seconds >= 3 || $depth > 0 ) {
		// 페이지 캐시 등으로 PHP 조회수가 빠진 경우, 읽기 비콘에서 DB 기준으로 보정
		if ( $post_id > 0 ) {
			borobill_record_unique_post_view( $post_id, true );
		}
		borobill_record_read_session( $depth, max( 1, $seconds ), $post_id );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_borobill_track_read', 'borobill_ajax_track_read' );
add_action( 'wp_ajax_nopriv_borobill_track_read', 'borobill_ajax_track_read' );

/**
 * 기간·게시글별 읽기 통계
 *
 * @param array<int, int> $post_ids
 * @param string          $from Y-m-d
 * @param string          $to   Y-m-d
 * @return array<int, array{sessions:int,avg_read_seconds:int,avg_read_label:string,dropoff:array{0:float,1:float,2:float,3:float,4:float}}>
 */
function borobill_get_posts_read_stats_for_period( array $post_ids, $from, $to ) {
	global $wpdb;

	$post_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $post_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		)
	);

	if ( empty( $post_ids ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return array();
	}

	borobill_ensure_post_daily_read_stats_table();

	$table       = borobill_get_post_daily_read_stats_table_name();
	$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$params       = array_merge( $post_ids, array( $from, $to ) );

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT
				post_id,
				COALESCE(SUM(sessions), 0) AS sessions,
				COALESCE(SUM(time_sum), 0) AS time_sum,
				COALESCE(SUM(reach_0), 0) AS reach_0,
				COALESCE(SUM(reach_25), 0) AS reach_25,
				COALESCE(SUM(reach_50), 0) AS reach_50,
				COALESCE(SUM(reach_75), 0) AS reach_75,
				COALESCE(SUM(reach_100), 0) AS reach_100
			FROM {$table}
			WHERE post_id IN ({$placeholders})
				AND read_date >= %s
				AND read_date <= %s
			GROUP BY post_id",
			...$params
		),
		ARRAY_A
	);

	$out = array();
	if ( ! is_array( $results ) ) {
		return $out;
	}

	foreach ( $results as $row ) {
		$post_id  = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
		$sessions = isset( $row['sessions'] ) ? (int) $row['sessions'] : 0;
		$time_sum = isset( $row['time_sum'] ) ? (int) $row['time_sum'] : 0;
		$r0       = isset( $row['reach_0'] ) ? (int) $row['reach_0'] : 0;
		$r25      = isset( $row['reach_25'] ) ? (int) $row['reach_25'] : 0;
		$r50      = isset( $row['reach_50'] ) ? (int) $row['reach_50'] : 0;
		$r75      = isset( $row['reach_75'] ) ? (int) $row['reach_75'] : 0;
		$r100     = isset( $row['reach_100'] ) ? (int) $row['reach_100'] : 0;

		if ( $post_id <= 0 ) {
			continue;
		}

		if ( $sessions <= 0 ) {
			$sessions = max( $r0, 0 );
		}

		$avg_seconds = $sessions > 0 ? (int) round( $time_sum / $sessions ) : 0;

		$out[ $post_id ] = array(
			'sessions'         => $sessions,
			'avg_read_seconds' => $avg_seconds,
			'avg_read_label'   => borobill_format_stay_duration( $avg_seconds ),
			'dropoff'          => borobill_compute_read_dropoff_shares( $r0, $r25, $r50, $r75, $r100 ),
		);
	}

	return $out;
}

/**
 * 기간 콘텐츠 읽기 분석 데이터
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return array{
 *   segments:array<int, array{key:string,label:string,reached:int,reached_rate:float,dropped:int,drop_rate:float}>,
 *   avg_progress:float,
 *   avg_drop_rate:float,
 *   avg_read_seconds:int,
 *   avg_read_label:string,
 *   sessions:int
 * }
 */
function borobill_get_period_read_analysis( $from, $to ) {
	global $wpdb;

	$empty = array(
		'segments'         => array(),
		'avg_progress'     => 0.0,
		'avg_drop_rate'    => 0.0,
		'avg_read_seconds' => 0,
		'avg_read_label'   => borobill_format_stay_duration( 0 ),
		'sessions'         => 0,
	);

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return $empty;
	}

	borobill_ensure_daily_read_stats_table();

	$table = borobill_get_daily_read_stats_table_name();
	$row   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT
				COALESCE(SUM(sessions), 0) AS sessions,
				COALESCE(SUM(depth_sum), 0) AS depth_sum,
				COALESCE(SUM(time_sum), 0) AS time_sum,
				COALESCE(SUM(reach_0), 0) AS reach_0,
				COALESCE(SUM(reach_25), 0) AS reach_25,
				COALESCE(SUM(reach_50), 0) AS reach_50,
				COALESCE(SUM(reach_75), 0) AS reach_75,
				COALESCE(SUM(reach_100), 0) AS reach_100
			FROM {$table}
			WHERE read_date >= %s AND read_date <= %s",
			$from,
			$to
		),
		ARRAY_A
	);

	$sessions  = isset( $row['sessions'] ) ? (int) $row['sessions'] : 0;
	$depth_sum = isset( $row['depth_sum'] ) ? (int) $row['depth_sum'] : 0;
	$time_sum  = isset( $row['time_sum'] ) ? (int) $row['time_sum'] : 0;
	$reaches   = array(
		0   => isset( $row['reach_0'] ) ? (int) $row['reach_0'] : 0,
		25  => isset( $row['reach_25'] ) ? (int) $row['reach_25'] : 0,
		50  => isset( $row['reach_50'] ) ? (int) $row['reach_50'] : 0,
		75  => isset( $row['reach_75'] ) ? (int) $row['reach_75'] : 0,
		100 => isset( $row['reach_100'] ) ? (int) $row['reach_100'] : 0,
	);

	if ( $sessions <= 0 ) {
		$sessions = max( $reaches[0], 0 );
	}

	$labels = array(
		0   => '0~24% 도달',
		25  => '25~49% 도달',
		50  => '50~74% 도달',
		75  => '75~99% 도달',
		100 => '100% 도달',
	);
	$keys   = array( 0, 25, 50, 75, 100 );
	$segments = array();
	$drop_rates_for_avg = array();

	foreach ( $keys as $index => $key ) {
		$reached = $reaches[ $key ];
		$next    = isset( $keys[ $index + 1 ] ) ? $reaches[ $keys[ $index + 1 ] ] : 0;
		$dropped = ( 100 === $key ) ? 0 : max( 0, $reached - $next );
		$drop_rate = $reached > 0 ? round( ( $dropped / $reached ) * 100, 1 ) : 0.0;
		$reached_rate = $sessions > 0 ? round( ( $reached / $sessions ) * 100, 1 ) : 0.0;

		if ( 100 !== $key && $reached > 0 ) {
			$drop_rates_for_avg[] = $drop_rate;
		}

		$segments[] = array(
			'key'          => (string) $key,
			'label'        => $labels[ $key ],
			'reached'      => $reached,
			'reached_rate' => $reached_rate,
			'dropped'      => $dropped,
			'drop_rate'    => $drop_rate,
		);
	}

	$avg_progress     = $sessions > 0 ? round( $depth_sum / $sessions, 1 ) : 0.0;
	$avg_drop_rate    = ! empty( $drop_rates_for_avg )
		? round( array_sum( $drop_rates_for_avg ) / count( $drop_rates_for_avg ), 1 )
		: 0.0;
	$avg_read_seconds = $sessions > 0 ? (int) round( $time_sum / $sessions ) : 0;

	return array(
		'segments'         => $segments,
		'avg_progress'     => $avg_progress,
		'avg_drop_rate'    => $avg_drop_rate,
		'avg_read_seconds' => $avg_read_seconds,
		'avg_read_label'   => borobill_format_stay_duration( $avg_read_seconds ),
		'sessions'         => $sessions,
	);
}

/**
 * 프론트 체류시간·읽기 트래킹 스크립트
 */
function borobill_enqueue_stats_tracking_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}

	$js_path = get_template_directory() . '/js/stats-tracking.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1.0';

	wp_enqueue_script(
		'borobill-stats-tracking',
		get_template_directory_uri() . '/js/stats-tracking.js',
		array(),
		$js_ver,
		true
	);
	wp_localize_script(
		'borobill-stats-tracking',
		'borobillStatsTracking',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'borobill_track_stay' ),
			'trackRead' => is_singular( 'post' ) ? 1 : 0,
			'postId'    => is_singular( 'post' ) ? (int) get_queried_object_id() : 0,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'borobill_enqueue_stats_tracking_assets', 30 );

/**
 * 일자별 사이트 접속인원 테이블 생성
 */
function borobill_ensure_daily_visitors_table() {
	global $wpdb;

	$table_name = borobill_get_daily_visitors_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_daily_visitors_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		visit_date date NOT NULL,
		visitors bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (visit_date)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_daily_visitors_db_version', $version, false );
}

/**
 * 시간대별 사이트 접속인원 테이블명
 */
function borobill_get_hourly_visitors_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'borobill_hourly_visitors';
}

/**
 * 시간대별 사이트 접속인원 테이블 생성
 */
function borobill_ensure_hourly_visitors_table() {
	global $wpdb;

	$table_name = borobill_get_hourly_visitors_table_name();
	$version    = '1.0';
	$installed  = get_option( 'borobill_hourly_visitors_db_version' );

	if ( $installed === $version ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		visit_date date NOT NULL,
		visit_hour tinyint(2) unsigned NOT NULL,
		visitors bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (visit_date, visit_hour)
	) {$charset_collate};";

	dbDelta( $sql );
	update_option( 'borobill_hourly_visitors_db_version', $version, false );
}

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_daily_visitor_rows_range( $start_date, $end_date ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $end_date ) ) {
		return array();
	}

	$start_ts = strtotime( $start_date . ' 00:00:00' );
	$end_ts   = strtotime( $end_date . ' 00:00:00' );
	if ( false === $start_ts || false === $end_ts ) {
		return array();
	}

	if ( $start_ts > $end_ts ) {
		$swap       = $start_date;
		$start_date = $end_date;
		$end_date   = $swap;
		$start_ts   = strtotime( $start_date . ' 00:00:00' );
		$end_ts     = strtotime( $end_date . ' 00:00:00' );
	}

	$max_days = 90;
	$day_span = (int) floor( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) + 1;
	if ( $day_span > $max_days ) {
		$start_ts   = $end_ts - ( ( $max_days - 1 ) * DAY_IN_SECONDS );
		$start_date = wp_date( 'Y-m-d', $start_ts );
	}

	borobill_ensure_daily_visitors_table();

	$table = borobill_get_daily_visitors_table_name();
	$rows  = array();

	for ( $ts = $start_ts; $ts <= $end_ts; $ts += DAY_IN_SECONDS ) {
		$date          = wp_date( 'Y-m-d', $ts );
		$rows[ $date ] = 0;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT visit_date, visitors
			FROM {$table}
			WHERE visit_date >= %s AND visit_date <= %s
			ORDER BY visit_date ASC",
			$start_date,
			$end_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$date_key = isset( $result['visit_date'] ) ? (string) $result['visit_date'] : '';
			if ( '' !== $date_key && array_key_exists( $date_key, $rows ) ) {
				$rows[ $date_key ] = (int) $result['visitors'];
			}
		}
	}

	$daily_rows = array();
	foreach ( $rows as $date => $visitors ) {
		$daily_rows[] = array(
			'date'  => $date,
			'label' => wp_date( 'n/j', strtotime( $date ) ),
			'views' => (int) $visitors,
		);
	}

	return $daily_rows;
}

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_hourly_visitor_rows_today() {
	global $wpdb;

	borobill_ensure_hourly_visitors_table();

	$table = borobill_get_hourly_visitors_table_name();
	$today = wp_date( 'Y-m-d' );
	$rows  = array();

	for ( $hour = 0; $hour <= 23; $hour++ ) {
		$rows[ $hour ] = 0;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT visit_hour, visitors
			FROM {$table}
			WHERE visit_date = %s
			ORDER BY visit_hour ASC",
			$today
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$hour_key = isset( $result['visit_hour'] ) ? (int) $result['visit_hour'] : -1;
			if ( $hour_key >= 0 && $hour_key <= 23 ) {
				$rows[ $hour_key ] = (int) $result['visitors'];
			}
		}
	}

	$hourly_rows = array();
	foreach ( $rows as $hour => $visitors ) {
		$hourly_rows[] = array(
			'date'  => $today . sprintf( ' %02d:00', (int) $hour ),
			'label' => sprintf( '%02d시', (int) $hour ),
			'views' => (int) $visitors,
		);
	}

	return $hourly_rows;
}

/**
 * 기간 내 시간대별 방문자 수 (일자 × 0~23시)
 *
 * @param string $start_date Y-m-d
 * @param string $end_date   Y-m-d
 * @return array<int, array{date:string,hour:int,label:string,views:int,date_label:string}>
 */
function borobill_get_hourly_visitor_rows_range( $start_date, $end_date ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $end_date ) ) {
		return array();
	}

	$start_ts = strtotime( $start_date . ' 00:00:00' );
	$end_ts   = strtotime( $end_date . ' 00:00:00' );
	if ( false === $start_ts || false === $end_ts ) {
		return array();
	}

	if ( $start_ts > $end_ts ) {
		$swap       = $start_date;
		$start_date = $end_date;
		$end_date   = $swap;
		$start_ts   = strtotime( $start_date . ' 00:00:00' );
		$end_ts     = strtotime( $end_date . ' 00:00:00' );
	}

	$max_days = 90;
	$day_span = (int) floor( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) + 1;
	if ( $day_span > $max_days ) {
		$start_ts   = $end_ts - ( ( $max_days - 1 ) * DAY_IN_SECONDS );
		$start_date = wp_date( 'Y-m-d', $start_ts );
	}

	borobill_ensure_hourly_visitors_table();

	$table = borobill_get_hourly_visitors_table_name();
	$map   = array();

	for ( $ts = $start_ts; $ts <= $end_ts; $ts += DAY_IN_SECONDS ) {
		$date = wp_date( 'Y-m-d', $ts );
		for ( $hour = 0; $hour <= 23; $hour++ ) {
			$map[ $date ][ $hour ] = 0;
		}
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT visit_date, visit_hour, visitors
			FROM {$table}
			WHERE visit_date >= %s AND visit_date <= %s
			ORDER BY visit_date ASC, visit_hour ASC",
			$start_date,
			$end_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$date_key = isset( $result['visit_date'] ) ? (string) $result['visit_date'] : '';
			$hour_key = isset( $result['visit_hour'] ) ? (int) $result['visit_hour'] : -1;
			if ( '' !== $date_key && isset( $map[ $date_key ][ $hour_key ] ) ) {
				$map[ $date_key ][ $hour_key ] = (int) $result['visitors'];
			}
		}
	}

	$hourly_rows = array();
	foreach ( $map as $date => $hours ) {
		foreach ( $hours as $hour => $visitors ) {
			$hour = (int) $hour;
			$hourly_rows[] = array(
				'date'       => $date,
				'hour'       => $hour,
				'label'      => sprintf( '%02d시', $hour ),
				'views'      => (int) $visitors,
				'date_label' => ( 0 === $hour ) ? wp_date( 'y.m.d', strtotime( $date ) ) : '',
			);
		}
	}

	return $hourly_rows;
}

/**
 * 오늘 시간대별 접속인원 +1
 *
 * @param int $hour 0-23
 */
function borobill_increment_hourly_visitor_count( $hour ) {
	global $wpdb;

	$hour = max( 0, min( 23, (int) $hour ) );
	borobill_ensure_hourly_visitors_table();

	$table = borobill_get_hourly_visitors_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (visit_date, visit_hour, visitors) VALUES (%s, %d, 1)
			ON DUPLICATE KEY UPDATE visitors = visitors + 1",
			$today,
			$hour
		)
	);
}

/**
 * 당일 방문자 수
 *
 * @return int
 */
function borobill_get_today_visitor_count() {
	global $wpdb;

	borobill_ensure_daily_visitors_table();

	$table = borobill_get_daily_visitors_table_name();
	$today = wp_date( 'Y-m-d' );
	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT visitors FROM {$table} WHERE visit_date = %s LIMIT 1",
			$today
		)
	);

	return is_numeric( $count ) ? max( 0, (int) $count ) : 0;
}

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_daily_visitor_rows( $days = 30 ) {
	global $wpdb;

	$days = max( 1, min( 90, (int) $days ) );
	borobill_ensure_daily_visitors_table();

	$table = borobill_get_daily_visitors_table_name();
	$rows  = array();

	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$date          = wp_date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
		$rows[ $date ] = 0;
	}

	$start_date = wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) );
	$results    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT visit_date, visitors
			FROM {$table}
			WHERE visit_date >= %s
			ORDER BY visit_date ASC",
			$start_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$date_key = isset( $result['visit_date'] ) ? (string) $result['visit_date'] : '';
			if ( '' !== $date_key && array_key_exists( $date_key, $rows ) ) {
				$rows[ $date_key ] = (int) $result['visitors'];
			}
		}
	}

	$daily_rows = array();
	foreach ( $rows as $date => $visitors ) {
		$daily_rows[] = array(
			'date'  => $date,
			'label' => wp_date( 'n/j', strtotime( $date ) ),
			'views' => (int) $visitors,
		);
	}

	return $daily_rows;
}

/**
 * 오늘 사이트 접속인원 +1
 */
function borobill_increment_daily_visitor_count() {
	global $wpdb;

	borobill_ensure_daily_visitors_table();

	$table = borobill_get_daily_visitors_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (visit_date, visitors) VALUES (%s, 1)
			ON DUPLICATE KEY UPDATE visitors = visitors + 1",
			$today
		)
	);
}

/**
 * 프론트 접속 시 일자별 접속인원 집계 (방문자당 하루 1회)
 */
function borobill_track_daily_site_visitor() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}

	$cookie_name = 'borobill_site_visitor';
	$today       = wp_date( 'Y-m-d' );
	$hour        = (int) wp_date( 'G' );
	$hour_cookie = 'borobill_site_visitor_h_' . $today . '_' . $hour;

	$visitor      = borobill_get_or_create_visitor_key();
	$visitor_key  = $visitor['key'];
	$is_returning = ! $visitor['is_new'] && borobill_visitor_has_prior_visit( $visitor_key );
	if ( ! $visitor['is_new'] && ! $is_returning ) {
		// 쿠키는 있으나 로그가 없는 경우(마이그레이션 등)는 재방문으로 간주
		$is_returning = true;
	}

	// 재방문자: 페이지 진입(클릭)마다 +1 누적 (당일 쿠키 1회 제한 없음)
	if ( $is_returning ) {
		borobill_increment_returning_hit_count();
	}

	$count_hour = ! isset( $_COOKIE[ $hour_cookie ] );
	if ( $count_hour ) {
		borobill_increment_hourly_visitor_count( $hour );
	}

	$count_day = ! ( isset( $_COOKIE[ $cookie_name ] ) && (string) wp_unslash( $_COOKIE[ $cookie_name ] ) === $today );
	if ( $count_day ) {
		borobill_increment_daily_visitor_count();
		borobill_record_visitor_day( $visitor_key, $is_returning );
	}

	if ( ! $count_hour && ! $count_day ) {
		return;
	}

	$expires = strtotime( 'tomorrow', (int) current_time( 'timestamp' ) );
	if ( false === $expires ) {
		$expires = time() + DAY_IN_SECONDS;
	}

	if ( ! headers_sent() ) {
		if ( $count_day ) {
			setcookie( $cookie_name, $today, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
		if ( $count_hour ) {
			setcookie( $hour_cookie, '1', $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}
}
add_action( 'template_redirect', 'borobill_track_daily_site_visitor', 5 );

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_daily_views_rows( $days = 30 ) {
	global $wpdb;

	$days = max( 1, min( 90, (int) $days ) );
	borobill_ensure_daily_views_table();

	$table = borobill_get_daily_views_table_name();
	$rows  = array();

	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$date          = wp_date( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
		$rows[ $date ] = 0;
	}

	$start_date = wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) );
	$results    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT view_date, views
			FROM {$table}
			WHERE view_date >= %s
			ORDER BY view_date ASC",
			$start_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$date_key = isset( $result['view_date'] ) ? (string) $result['view_date'] : '';
			if ( '' !== $date_key && array_key_exists( $date_key, $rows ) ) {
				$rows[ $date_key ] = (int) $result['views'];
			}
		}
	}

	$daily_rows = array();
	foreach ( $rows as $date => $views ) {
		$daily_rows[] = array(
			'date'  => $date,
			'label' => wp_date( 'n/j', strtotime( $date ) ),
			'views' => (int) $views,
		);
	}

	return $daily_rows;
}

/**
 * 오늘 일자 조회수 +1
 */
function borobill_increment_daily_view_count() {
	global $wpdb;

	borobill_ensure_daily_views_table();

	$table = borobill_get_daily_views_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (view_date, views) VALUES (%s, 1)
			ON DUPLICATE KEY UPDATE views = views + 1",
			$today
		)
	);
}

/**
 * 오늘 시간대별 조회수 +1
 *
 * @param int $hour 0-23
 */
function borobill_increment_hourly_view_count( $hour ) {
	global $wpdb;

	$hour = max( 0, min( 23, (int) $hour ) );
	borobill_ensure_hourly_views_table();

	$table = borobill_get_hourly_views_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (view_date, view_hour, views) VALUES (%s, %d, 1)
			ON DUPLICATE KEY UPDATE views = views + 1",
			$today,
			$hour
		)
	);
}

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_hourly_views_rows_today() {
	global $wpdb;

	borobill_ensure_hourly_views_table();

	$table = borobill_get_hourly_views_table_name();
	$today = wp_date( 'Y-m-d' );
	$rows  = array();

	for ( $hour = 0; $hour <= 23; $hour++ ) {
		$rows[ $hour ] = 0;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT view_hour, views
			FROM {$table}
			WHERE view_date = %s
			ORDER BY view_hour ASC",
			$today
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$hour_key = isset( $result['view_hour'] ) ? (int) $result['view_hour'] : -1;
			if ( $hour_key >= 0 && $hour_key <= 23 ) {
				$rows[ $hour_key ] = (int) $result['views'];
			}
		}
	}

	$hourly_rows = array();
	foreach ( $rows as $hour => $views ) {
		$hourly_rows[] = array(
			'date'  => $today . sprintf( ' %02d:00', (int) $hour ),
			'label' => sprintf( '%02d시', (int) $hour ),
			'views' => (int) $views,
		);
	}

	return $hourly_rows;
}

/**
 * 게시글 오늘 일자 조회수 +1
 *
 * @param int $post_id
 */
function borobill_increment_post_daily_view_count( $post_id ) {
	global $wpdb;

	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}

	borobill_ensure_post_daily_views_table();

	$table = borobill_get_post_daily_views_table_name();
	$today = wp_date( 'Y-m-d' );

	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (post_id, view_date, views) VALUES (%d, %s, 1)
			ON DUPLICATE KEY UPDATE views = views + 1",
			$post_id,
			$today
		)
	);
}

/**
 * 기간 조회수 기준 게시글 목록
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @param int    $limit       0 이하면 전체(상한 5000)
 * @param int    $offset
 * @param bool   $only_viewed true면 기간 내 조회 있는 글만
 * @return array<int, array{id:int,title:string,views:int,date:string,edit_url:string,category:string}>
 */
function borobill_get_top_posts_by_period_views( $from, $to, $limit = 10, $offset = 0, $only_viewed = true ) {
	global $wpdb;

	$offset = max( 0, (int) $offset );
	$limit  = (int) $limit;
	if ( $limit <= 0 ) {
		$limit = 5000;
	} else {
		$limit = max( 1, min( 200, $limit ) );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return array();
	}

	borobill_ensure_post_daily_views_table();

	$table = borobill_get_post_daily_views_table_name();

	// 세무 사전 용어는 글이 아니라 사전 항목이므로 조회수 순위에서 제외한다.
	$exclude_sql = '';
	if ( function_exists( 'borobill_get_glossary_term' ) ) {
		$g_term = borobill_get_glossary_term();
		if ( $g_term ) {
			$exclude_sql = $wpdb->prepare(
				"AND p.ID NOT IN (
					SELECT tr.object_id
					FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt
						ON tt.term_taxonomy_id = tr.term_taxonomy_id
					WHERE tt.taxonomy = 'category' AND tt.term_id = %d
				)",
				(int) $g_term->term_id
			);
		}
	}

	if ( $only_viewed ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.post_id, SUM(v.views) AS period_views
				FROM {$table} v
				INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id
				WHERE v.view_date >= %s
					AND v.view_date <= %s
					AND p.post_type = 'post'
					AND p.post_status = 'publish'
					{$exclude_sql}
				GROUP BY v.post_id
				ORDER BY period_views DESC, v.post_id DESC
				LIMIT %d OFFSET %d",
				$from,
				$to,
				$limit,
				$offset
			),
			ARRAY_A
		);
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID AS post_id, COALESCE(SUM(v.views), 0) AS period_views
				FROM {$wpdb->posts} p
				LEFT JOIN {$table} v
					ON v.post_id = p.ID
					AND v.view_date >= %s
					AND v.view_date <= %s
				WHERE p.post_type = 'post'
					AND p.post_status = 'publish'
					{$exclude_sql}
				GROUP BY p.ID
				ORDER BY period_views DESC, p.ID DESC
				LIMIT %d OFFSET %d",
				$from,
				$to,
				$limit,
				$offset
			),
			ARRAY_A
		);
	}

	if ( ! is_array( $results ) || empty( $results ) ) {
		return array();
	}

	$top_posts = array();
	foreach ( $results as $row ) {
		$post_id = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
		if ( $post_id <= 0 ) {
			continue;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$top_posts[] = array(
			'id'       => $post_id,
			'title'    => get_the_title( $post ),
			'views'    => isset( $row['period_views'] ) ? (int) $row['period_views'] : 0,
			'date'     => get_the_date( 'y.m.d', $post ),
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'category' => borobill_get_root_gnb_category_name( $post_id ),
		);
	}

	return $top_posts;
}

/**
 * 기간 조회수 목록 총 건수
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @param bool   $only_viewed
 * @return int
 */
function borobill_count_posts_by_period_views( $from, $to, $only_viewed = true ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ) {
		return 0;
	}

	borobill_ensure_post_daily_views_table();
	$table = borobill_get_post_daily_views_table_name();

	if ( $only_viewed ) {
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT v.post_id
					FROM {$table} v
					INNER JOIN {$wpdb->posts} p ON p.ID = v.post_id
					WHERE v.view_date >= %s
						AND v.view_date <= %s
						AND p.post_type = 'post'
						AND p.post_status = 'publish'
					GROUP BY v.post_id
				) AS counted",
				$from,
				$to
			)
		);
	} else {
		$count = $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
				AND post_status = 'publish'"
		);
	}

	return is_numeric( $count ) ? max( 0, (int) $count ) : 0;
}

/**
 * 특정일 게시글 조회수
 *
 * @param int    $post_id
 * @param string $date Y-m-d
 * @return int
 */
function borobill_get_post_views_on_date( $post_id, $date ) {
	global $wpdb;

	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
		return 0;
	}

	borobill_ensure_post_daily_views_table();
	$table = borobill_get_post_daily_views_table_name();
	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT views FROM {$table} WHERE post_id = %d AND view_date = %s LIMIT 1",
			$post_id,
			$date
		)
	);

	return is_numeric( $count ) ? max( 0, (int) $count ) : 0;
}

/**
 * 게시글 조회수 1회 기록 (글·당일 쿠키 기준 중복 방지)
 *
 * @param int  $post_id
 * @param bool $backfill_if_missing 쿠키가 있어도 DB 조회수가 0이면 보정
 * @return bool 실제 집계했으면 true
 */
function borobill_record_unique_post_view( $post_id, $backfill_if_missing = false ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) || 'post' !== get_post_type( $post_id ) ) {
		return false;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}

	$today       = wp_date( 'Y-m-d' );
	$cookie_name = 'borobill_view_' . $post_id;
	$has_cookie  = isset( $_COOKIE[ $cookie_name ] );
	$today_views = borobill_get_post_views_on_date( $post_id, $today );

	// 이미 당일 조회수가 있으면 쿠키만 맞추고 종료
	if ( $today_views > 0 ) {
		if ( ! $has_cookie && ! headers_sent() ) {
			$expires = strtotime( 'tomorrow', (int) current_time( 'timestamp' ) );
			if ( false === $expires ) {
				$expires = time() + DAY_IN_SECONDS;
			}
			setcookie( $cookie_name, '1', $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE[ $cookie_name ] = '1';
		}
		return false;
	}

	// 쿠키만 있고 DB는 0인 깨진 상태 → 보정 허용 시에만 통과
	if ( $has_cookie && ! $backfill_if_missing ) {
		return false;
	}

	borobill_increment_post_view_count( $post_id );
	borobill_increment_post_daily_view_count( $post_id );
	borobill_increment_daily_view_count();
	borobill_increment_hourly_view_count( (int) wp_date( 'G' ) );

	$expires = strtotime( 'tomorrow', (int) current_time( 'timestamp' ) );
	if ( false === $expires ) {
		$expires = time() + DAY_IN_SECONDS;
	}

	if ( ! headers_sent() ) {
		setcookie( $cookie_name, '1', $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}
	$_COOKIE[ $cookie_name ] = '1';

	return true;
}

/**
 * 읽기 집계는 있는데 조회수가 없는 날짜를 보정
 *
 * @param array<int, int> $post_ids 비우면 전체
 * @return int 보정한 행 수
 */
function borobill_backfill_views_from_read_stats( array $post_ids = array() ) {
	global $wpdb;

	borobill_ensure_post_daily_read_stats_table();
	borobill_ensure_post_daily_views_table();

	$read_table  = borobill_get_post_daily_read_stats_table_name();
	$views_table = borobill_get_post_daily_views_table_name();

	$post_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $post_ids ),
				static function ( $id ) {
					return $id > 0;
				}
			)
		)
	);

	if ( ! empty( $post_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$rows         = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.post_id, r.read_date, r.sessions
				FROM {$read_table} r
				LEFT JOIN {$views_table} v
					ON v.post_id = r.post_id AND v.view_date = r.read_date
				WHERE r.post_id IN ({$placeholders})
					AND r.sessions > 0
					AND (v.views IS NULL OR v.views = 0)",
				...$post_ids
			),
			ARRAY_A
		);
	} else {
		$rows = $wpdb->get_results(
			"SELECT r.post_id, r.read_date, r.sessions
			FROM {$read_table} r
			LEFT JOIN {$views_table} v
				ON v.post_id = r.post_id AND v.view_date = r.read_date
			WHERE r.sessions > 0
				AND (v.views IS NULL OR v.views = 0)
			LIMIT 500",
			ARRAY_A
		);
	}

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return 0;
	}

	$fixed = 0;
	foreach ( $rows as $row ) {
		$post_id  = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;
		$date     = isset( $row['read_date'] ) ? (string) $row['read_date'] : '';
		$sessions = isset( $row['sessions'] ) ? max( 1, (int) $row['sessions'] ) : 1;
		if ( $post_id <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			continue;
		}

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$views_table} (post_id, view_date, views) VALUES (%d, %s, %d)
				ON DUPLICATE KEY UPDATE views = GREATEST(views, VALUES(views))",
				$post_id,
				$date,
				$sessions
			)
		);
		$fixed++;
	}

	return $fixed;
}

/**
 * 게시글 상세 조회 시 일자·시간대별 조회수 집계
 */
function borobill_track_daily_post_view() {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}

	borobill_record_unique_post_view( (int) get_queried_object_id() );
}
add_action( 'template_redirect', 'borobill_track_daily_post_view', 20 );

/**
 * 직접선택 날짜 입력(yy-mm-dd / Y-m-d)을 Y-m-d 로 정규화
 *
 * @param string $value
 * @return string
 */
function borobill_parse_stats_custom_date( $value ) {
	$value = trim( (string) $value );

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '/^(\d{2})-(\d{2})-(\d{2})$/', $value, $matches ) ) {
		$year  = 2000 + (int) $matches[1];
		$month = (int) $matches[2];
		$day   = (int) $matches[3];
		if ( checkdate( $month, $day, $year ) ) {
			return sprintf( '%04d-%02d-%02d', $year, $month, $day );
		}
	}

	return '';
}

/**
 * 사이트 접속인원 차트 조회 기간 설정
 *
 * @return array{
 *   period:string,
 *   mode:string,
 *   label:string,
 *   summary_label:string,
 *   days?:int,
 *   from?:string,
 *   to?:string
 * }
 */
function borobill_get_stats_visitor_chart_context() {
	$allowed = array( '1', '5', '10', '15', '20', '25', '30', 'custom' );
	$period  = isset( $_GET['daily_period'] ) ? sanitize_key( wp_unslash( $_GET['daily_period'] ) ) : '1';

	if ( ! in_array( $period, $allowed, true ) ) {
		$period = '1';
	}

	$labels = array(
		'1'      => '1일(당일)',
		'5'      => '5일',
		'10'     => '10일',
		'15'     => '15일',
		'20'     => '20일',
		'25'     => '25일',
		'30'     => '30일',
		'custom' => '직접선택',
	);

	$today = wp_date( 'Y-m-d' );

	if ( 'custom' === $period ) {
		$from_raw = isset( $_GET['daily_from'] ) ? sanitize_text_field( wp_unslash( $_GET['daily_from'] ) ) : '';
		$to_raw   = isset( $_GET['daily_to'] ) ? sanitize_text_field( wp_unslash( $_GET['daily_to'] ) ) : '';
		$from     = borobill_parse_stats_custom_date( $from_raw );
		$to       = borobill_parse_stats_custom_date( $to_raw );

		if ( '' === $from ) {
			$from = wp_date( 'Y-m-d', strtotime( '-6 days' ) );
		}
		if ( '' === $to ) {
			$to = $today;
		}

		return array(
			'period'        => 'custom',
			'mode'          => 'daily',
			'label'         => $labels['custom'],
			'summary_label' => wp_date( 'Y.m.d', strtotime( $from ) ) . ' ~ ' . wp_date( 'Y.m.d', strtotime( $to ) ),
			'from'          => $from,
			'to'            => $to,
		);
	}

	if ( '1' === $period ) {
		return array(
			'period'        => '1',
			'mode'          => 'hourly',
			'label'         => $labels['1'],
			'summary_label' => '당일',
			'from'          => $today,
			'to'            => $today,
		);
	}

	$days = (int) $period;
	$from = wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) );

	return array(
		'period'        => (string) $days,
		'mode'          => 'daily',
		'label'         => $labels[ (string) $days ],
		'summary_label' => '최근 ' . $days . '일',
		'days'          => $days,
		'from'          => $from,
		'to'            => $today,
	);
}

/**
 * @param array $context borobill_get_stats_visitor_chart_context() 결과
 * @return array{0:string,1:string} [from, to] Y-m-d
 */
function borobill_resolve_stats_period_dates( array $context ) {
	$today = wp_date( 'Y-m-d' );
	$from  = isset( $context['from'] ) ? (string) $context['from'] : '';
	$to    = isset( $context['to'] ) ? (string) $context['to'] : '';

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
		$from = $today;
	}
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
		$to = $today;
	}

	return array( $from, $to );
}

/**
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_daily_views_rows_range( $start_date, $end_date ) {
	global $wpdb;

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $end_date ) ) {
		return array();
	}

	$start_ts = strtotime( $start_date . ' 00:00:00' );
	$end_ts   = strtotime( $end_date . ' 00:00:00' );
	if ( false === $start_ts || false === $end_ts ) {
		return array();
	}

	if ( $start_ts > $end_ts ) {
		$swap       = $start_date;
		$start_date = $end_date;
		$end_date   = $swap;
		$start_ts   = strtotime( $start_date . ' 00:00:00' );
		$end_ts     = strtotime( $end_date . ' 00:00:00' );
	}

	$max_days = 90;
	$day_span = (int) floor( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) + 1;
	if ( $day_span > $max_days ) {
		$start_ts   = $end_ts - ( ( $max_days - 1 ) * DAY_IN_SECONDS );
		$start_date = wp_date( 'Y-m-d', $start_ts );
	}

	borobill_ensure_daily_views_table();

	$table = borobill_get_daily_views_table_name();
	$rows  = array();

	for ( $ts = $start_ts; $ts <= $end_ts; $ts += DAY_IN_SECONDS ) {
		$date          = wp_date( 'Y-m-d', $ts );
		$rows[ $date ] = 0;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT view_date, views
			FROM {$table}
			WHERE view_date >= %s AND view_date <= %s
			ORDER BY view_date ASC",
			$start_date,
			$end_date
		),
		ARRAY_A
	);

	if ( is_array( $results ) ) {
		foreach ( $results as $result ) {
			$date_key = isset( $result['view_date'] ) ? (string) $result['view_date'] : '';
			if ( '' !== $date_key && array_key_exists( $date_key, $rows ) ) {
				$rows[ $date_key ] = (int) $result['views'];
			}
		}
	}

	$daily_rows = array();
	foreach ( $rows as $date => $views ) {
		$daily_rows[] = array(
			'date'  => $date,
			'label' => wp_date( 'm.d', strtotime( $date ) ),
			'views' => (int) $views,
		);
	}

	return $daily_rows;
}

/**
 * @param array $context borobill_get_stats_visitor_chart_context() 결과
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_views_chart_rows( array $context ) {
	if ( 'hourly' === $context['mode'] ) {
		return borobill_get_hourly_views_rows_today();
	}

	list( $from, $to ) = borobill_resolve_stats_period_dates( $context );

	return borobill_get_daily_views_rows_range( $from, $to );
}

/**
 * @param array $context borobill_get_stats_visitor_chart_context() 결과
 * @return array<int, array{date:string,label:string,views:int}>
 */
function borobill_get_visitor_chart_rows( array $context ) {
	if ( 'hourly' === $context['mode'] ) {
		return borobill_get_hourly_visitor_rows_today();
	}

	if ( 'custom' === $context['period'] ) {
		return borobill_get_daily_visitor_rows_range( $context['from'], $context['to'] );
	}

	return borobill_get_daily_visitor_rows( (int) $context['days'] );
}

/**
 * 일자별 조회수 조회 기간(일) — 레거시
 */
function borobill_get_stats_daily_period_days() {
	$context = borobill_get_stats_visitor_chart_context();

	if ( 'custom' === $context['period'] ) {
		return 7;
	}

	if ( '1' === $context['period'] ) {
		return 1;
	}

	return isset( $context['days'] ) ? (int) $context['days'] : 1;
}

/**
 * 일자별 조회수 라인 그래프(SVG) 출력
 *
 * @param array<int, array{date:string,label:string,views:int}> $daily_rows
 * @param bool                                               $compact 사이드 패널용 축소 그래프
 */
function borobill_render_daily_views_line_chart( array $daily_rows, $compact = false ) {
	$count = count( $daily_rows );
	if ( $compact ) {
		$width      = 420;
		$height     = 240;
		$pad_left   = 42;
		$pad_right  = 12;
		$pad_top    = 18;
		$pad_bottom = 38;
	} else {
		$width      = 960;
		$height     = 330;
		$pad_left   = 36;
		$pad_right  = 12;
		$pad_top    = 24;
		$pad_bottom = 44;
	}
	$plot_width  = $width - $pad_left - $pad_right;
	$plot_height = $height - $pad_top - $pad_bottom;
	$base_y      = $pad_top + $plot_height;
	$chart_class = $compact ? 'borobill-stats-daily-line-chart borobill-stats-daily-line-chart--compact' : 'borobill-stats-daily-line-chart';

	if ( $count <= 0 ) {
		?>
		<div class="<?php echo esc_attr( $chart_class . ' chart_dg borobill-stats-daily-line-chart--placeholder' ); ?>" role="img" aria-label="일자별 접속인원 라인 그래프">
			<svg class="borobill-stats-daily-line-chart__svg" viewBox="0 0 <?php echo (int) $width; ?> <?php echo (int) $height; ?>" preserveAspectRatio="xMidYMid meet">
				<line
					class="borobill-stats-daily-line-chart__grid-line"
					x1="<?php echo (int) $pad_left; ?>"
					y1="<?php echo (int) $base_y; ?>"
					x2="<?php echo (int) ( $width - $pad_right ); ?>"
					y2="<?php echo (int) $base_y; ?>"
				/>
				<text
					class="borobill-stats-daily-line-chart__tick"
					x="<?php echo $compact ? (int) ( $pad_left - 10 ) : 0; ?>"
					y="<?php echo (int) ( $base_y + 4 ); ?>"
					text-anchor="<?php echo $compact ? 'end' : 'start'; ?>"
				>0</text>
			</svg>
		</div>
		<?php
		return;
	}

	$max_views = 0;
	foreach ( $daily_rows as $row ) {
		$max_views = max( $max_views, (int) $row['views'] );
	}
	$chart_max = max( 1, $max_views );

	if ( $count <= 7 ) {
		$label_every = 1;
	} elseif ( $count <= 14 ) {
		$label_every = 2;
	} elseif ( $count <= 30 ) {
		$label_every = 5;
	} else {
		$label_every = (int) max( 1, ceil( $count / 6 ) );
	}

	$dot_radius = $compact ? 3.5 : 4;

	$ticks = array(
		$chart_max,
		(int) round( $chart_max * 0.75 ),
		(int) round( $chart_max * 0.5 ),
		(int) round( $chart_max * 0.25 ),
		0,
	);
	$ticks = array_values( array_unique( $ticks ) );
	rsort( $ticks, SORT_NUMERIC );

	$points     = array();
	$line_parts = array();
	$last_index = $count - 1;

	foreach ( $daily_rows as $index => $row ) {
		$views = (int) $row['views'];
		$x     = $pad_left + ( $last_index > 0 ? ( $index / $last_index ) * $plot_width : 0 );
		$y     = $pad_top + $plot_height - ( $views / $chart_max ) * $plot_height;
		$points[] = array(
			'x'     => $x,
			'y'     => $y,
			'views' => $views,
			'label' => (string) $row['label'],
			'date'  => (string) $row['date'],
		);
		$line_parts[] = ( 0 === $index ? 'M' : 'L' ) . round( $x, 2 ) . ' ' . round( $y, 2 );
	}

	$first_x = round( $points[0]['x'], 2 );
	$last_x  = round( $points[ $last_index ]['x'], 2 );
	$area_path = implode( ' ', $line_parts )
		. ' L ' . $last_x . ' ' . $base_y
		. ' L ' . $first_x . ' ' . $base_y
		. ' Z';
	$line_path = implode( ' ', $line_parts );
	$chart_wrap_class = $chart_class . ' chart_dg borobill-stats-daily-line-chart--interactive';
	?>
	<div class="<?php echo esc_attr( $chart_wrap_class ); ?>">
		<svg class="borobill-stats-daily-line-chart__svg" viewBox="0 0 <?php echo (int) $width; ?> <?php echo (int) $height; ?>" preserveAspectRatio="xMidYMid meet">
			<defs>
				<linearGradient id="borobillDailyViewsFill" x1="0" y1="0" x2="0" y2="1">
					<stop offset="0%" stop-color="#60a5fa" stop-opacity="0.35" />
					<stop offset="100%" stop-color="#2563eb" stop-opacity="0.04" />
				</linearGradient>
			</defs>
			<?php foreach ( $ticks as $tick ) : ?>
				<?php
				$tick_y = $pad_top + $plot_height - ( (int) $tick / $chart_max ) * $plot_height;
				?>
				<line
					class="borobill-stats-daily-line-chart__grid-line"
					x1="<?php echo (int) $pad_left; ?>"
					y1="<?php echo esc_attr( (string) round( $tick_y, 2 ) ); ?>"
					x2="<?php echo (int) ( $width - $pad_right ); ?>"
					y2="<?php echo esc_attr( (string) round( $tick_y, 2 ) ); ?>"
				/>
				<text
					class="borobill-stats-daily-line-chart__tick"
					x="<?php echo $compact ? (int) ( $pad_left - 10 ) : 0; ?>"
					y="<?php echo esc_attr( (string) round( $tick_y + 4, 2 ) ); ?>"
					text-anchor="<?php echo $compact ? 'end' : 'start'; ?>"
				><?php echo esc_html( number_format_i18n( (int) $tick ) ); ?></text>
			<?php endforeach; ?>
			<path class="borobill-stats-daily-line-chart__area" d="<?php echo esc_attr( $area_path ); ?>" fill="url(#borobillDailyViewsFill)" />
			<path class="borobill-stats-daily-line-chart__line" d="<?php echo esc_attr( $line_path ); ?>" fill="none" />
			<?php foreach ( $points as $point_index => $point ) : ?>
				<?php if ( 0 === $point_index % $label_every || $point_index === $last_index ) : ?>
					<text
						class="borobill-stats-daily-line-chart__label"
						x="<?php echo esc_attr( (string) round( $point['x'], 2 ) ); ?>"
						y="<?php echo (int) ( $height - 12 ); ?>"
						text-anchor="middle"
					><?php echo esc_html( $point['label'] ); ?></text>
				<?php endif; ?>
				<?php
				$tooltip_label = (string) $point['label'];
				if ( false === strpos( $tooltip_label, '시' ) && ! empty( $point['date'] ) ) {
					$tooltip_date = strtok( $point['date'], ' ' );
					if ( is_string( $tooltip_date ) && '' !== $tooltip_date ) {
						$tooltip_label = wp_date( 'y.m.d', strtotime( $tooltip_date ) );
					}
				}
				?>
				<g
					class="borobill-stats-daily-line-chart__point"
					data-point-label="<?php echo esc_attr( $tooltip_label ); ?>"
					data-point-value="<?php echo esc_attr( (string) (int) $point['views'] ); ?>"
					tabindex="0"
					role="button"
					aria-label="<?php echo esc_attr( $tooltip_label . ' ' . number_format_i18n( (int) $point['views'] ) . '명' ); ?>"
				>
					<circle
						class="borobill-stats-daily-line-chart__dot-hit"
						cx="<?php echo esc_attr( (string) round( $point['x'], 2 ) ); ?>"
						cy="<?php echo esc_attr( (string) round( $point['y'], 2 ) ); ?>"
						r="<?php echo esc_attr( (string) ( $dot_radius + 6 ) ); ?>"
					/>
					<circle
						class="borobill-stats-daily-line-chart__dot"
						cx="<?php echo esc_attr( (string) round( $point['x'], 2 ) ); ?>"
						cy="<?php echo esc_attr( (string) round( $point['y'], 2 ) ); ?>"
						r="<?php echo esc_attr( (string) $dot_radius ); ?>"
					/>
				</g>
			<?php endforeach; ?>
		</svg>
		<div class="borobill-stats-daily-line-chart__tooltip" hidden>
			<span class="borobill-stats-daily-line-chart__tooltip-label"></span>
			<strong class="borobill-stats-daily-line-chart__tooltip-value"></strong>
		</div>
	</div>
	<?php
}

/**
 * 조회수 막대 그래프 (게시글 TOP / 일자별)
 *
 * @param array<int, array{title?:string,label?:string,views:int,edit_url?:string}> $rows
 * @param string                                                                     $mode posts|daily
 */
function borobill_render_top_posts_views_bar_chart( array $rows, $mode = 'posts' ) {
	$mode            = ( 'daily' === $mode ) ? 'daily' : 'posts';
	$chart_max_views = 0;
	foreach ( $rows as $chart_row ) {
		$chart_max_views = max( $chart_max_views, (int) $chart_row['views'] );
	}

	if ( $chart_max_views > 0 ) {
		$chart_ticks = array(
			$chart_max_views,
			(int) round( $chart_max_views * 0.75 ),
			(int) round( $chart_max_views * 0.5 ),
			(int) round( $chart_max_views * 0.25 ),
			0,
		);
		$chart_ticks = array_values( array_unique( $chart_ticks ) );
		rsort( $chart_ticks, SORT_NUMERIC );
	} else {
		$chart_ticks = array( 0 );
	}

	$col_count   = max( 1, count( $rows ) );
	$grid_style  = 'grid-template-columns: repeat(' . (int) $col_count . ', minmax(0, 1fr));';
	$is_hourly   = false;
	if ( 'daily' === $mode && ! empty( $rows[0]['label'] ) && false !== strpos( (string) $rows[0]['label'], '시' ) ) {
		$is_hourly = true;
	}
	$aria_label  = $is_hourly ? '시간대별 조회수 막대 그래프' : ( ( 'daily' === $mode ) ? '일자별 조회수 막대 그래프' : '조회수 상위 게시글 막대 그래프' );
	$chart_class = 'borobill-stats-views-chart';
	if ( empty( $rows ) ) {
		$chart_class .= ' borobill-stats-views-chart--empty';
	}
	if ( 'daily' === $mode ) {
		$chart_class .= ' borobill-stats-views-chart--daily';
	}
	if ( $is_hourly ) {
		$chart_class .= ' borobill-stats-views-chart--hourly';
	}
	$label_every = $is_hourly ? 3 : 1;
	$show_values = $col_count <= 14;
	?>
	<div class="<?php echo esc_attr( $chart_class ); ?>" role="img" aria-label="<?php echo esc_attr( $aria_label ); ?>">
		<div class="borobill-stats-views-chart__y-axis">
			<?php foreach ( $chart_ticks as $tick ) : ?>
				<span class="borobill-stats-views-chart__tick"><?php echo esc_html( number_format_i18n( $tick ) ); ?></span>
			<?php endforeach; ?>
		</div>
		<div class="borobill-stats-views-chart__body">
			<div class="borobill-stats-views-chart__plot">
				<div class="borobill-stats-views-chart__grid" aria-hidden="true">
					<?php foreach ( $chart_ticks as $tick ) : ?>
						<span></span>
					<?php endforeach; ?>
				</div>
				<div class="borobill-stats-views-chart__bars" style="<?php echo esc_attr( $grid_style ); ?>">
					<?php foreach ( $rows as $chart_row ) : ?>
						<?php
						$views      = (int) $chart_row['views'];
						$bar_height = $chart_max_views > 0 ? round( ( $views / $chart_max_views ) * 100, 1 ) : 0;
						$row_label  = isset( $chart_row['title'] ) ? (string) $chart_row['title'] : (string) ( $chart_row['label'] ?? '' );
						?>
						<div class="borobill-stats-views-chart__bar-col">
							<div class="borobill-stats-views-chart__bar-wrap">
								<span
									class="borobill-stats-views-chart__bar"
									style="height: <?php echo esc_attr( (string) $bar_height ); ?>%;"
									title="<?php echo esc_attr( $row_label . ' — ' . number_format_i18n( $views ) . ' 조회' ); ?>"
								>
									<?php if ( $show_values ) : ?>
										<span class="borobill-stats-views-chart__bar-value"><?php echo esc_html( number_format_i18n( $views ) ); ?></span>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="borobill-stats-views-chart__x-axis" style="<?php echo esc_attr( $grid_style ); ?>">
				<?php foreach ( $rows as $index => $chart_row ) : ?>
					<?php
					if ( 'daily' === $mode ) {
						$axis_label = isset( $chart_row['label'] ) ? (string) $chart_row['label'] : (string) ( $chart_row['title'] ?? '' );
					} else {
						$axis_label = wp_html_excerpt( (string) ( $chart_row['title'] ?? '' ), 10, '…' );
					}
					$show_label = ( 0 === ( $index % $label_every ) ) || ( $index === $col_count - 1 );
					?>
					<div class="borobill-stats-views-chart__x-col">
						<?php if ( 'posts' === $mode ) : ?>
							<span class="borobill-stats-views-chart__rank"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
						<?php endif; ?>
						<span class="borobill-stats-views-chart__label" title="<?php echo esc_attr( $axis_label ); ?>">
							<?php if ( 'posts' === $mode && ! empty( $chart_row['edit_url'] ) ) : ?>
								<a href="<?php echo esc_url( $chart_row['edit_url'] ); ?>"><?php echo esc_html( $axis_label ); ?></a>
							<?php elseif ( $show_label ) : ?>
								<?php echo esc_html( $axis_label ); ?>
							<?php endif; ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * 선택 기간과 동일한 길이의 직전 기간 [from, to]
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return array{0:string,1:string}
 */
function borobill_get_previous_period_dates( $from, $to ) {
	$from_ts = strtotime( $from . ' 00:00:00' );
	$to_ts   = strtotime( $to . ' 00:00:00' );

	if ( false === $from_ts || false === $to_ts ) {
		$today = wp_date( 'Y-m-d' );
		return array( $today, $today );
	}

	if ( $from_ts > $to_ts ) {
		$swap    = $from_ts;
		$from_ts = $to_ts;
		$to_ts   = $swap;
	}

	$span_days = (int) floor( ( $to_ts - $from_ts ) / DAY_IN_SECONDS ) + 1;
	$prev_to   = wp_date( 'Y-m-d', $from_ts - DAY_IN_SECONDS );
	$prev_from = wp_date( 'Y-m-d', $from_ts - ( $span_days * DAY_IN_SECONDS ) );

	return array( $prev_from, $prev_to );
}

/**
 * 기간 대비 증감률
 *
 * @param float|int $current
 * @param float|int $previous
 * @return array{percent:float,dir:string}
 */
function borobill_stats_percent_change( $current, $previous ) {
	$current  = (float) $current;
	$previous = (float) $previous;

	if ( abs( $previous ) < 0.0001 ) {
		if ( abs( $current ) < 0.0001 ) {
			return array(
				'percent' => 0.0,
				'dir'     => 'flat',
			);
		}

		return array(
			'percent' => 100.0,
			'dir'     => 'up',
		);
	}

	$percent = round( ( ( $current - $previous ) / abs( $previous ) ) * 100, 1 );
	$dir     = 'flat';
	if ( $percent > 0 ) {
		$dir = 'up';
	} elseif ( $percent < 0 ) {
		$dir = 'down';
	}

	return array(
		'percent' => abs( $percent ),
		'dir'     => $dir,
	);
}

/**
 * 지난 기간 대비 메타 문구 HTML
 *
 * @param array{percent:float,dir:string} $change
 * @param string                            $prefix
 * @return string
 */
function borobill_format_kpi_change_meta( array $change, $prefix = '지난 기간 대비' ) {
	$dir     = isset( $change['dir'] ) ? (string) $change['dir'] : 'flat';
	$percent = isset( $change['percent'] ) ? (float) $change['percent'] : 0.0;
	$arrow   = '';
	$class   = 'borobill-stats-kpi-card__change is-flat';

	if ( 'up' === $dir ) {
		$arrow = '▲ ';
		$class = 'borobill-stats-kpi-card__change is-up';
	} elseif ( 'down' === $dir ) {
		$arrow = '▼ ';
		$class = 'borobill-stats-kpi-card__change is-down';
	}

	return sprintf(
		'%s <span class="%s">%s%s%%</span>',
		esc_html( $prefix ),
		esc_attr( $class ),
		esc_html( $arrow ),
		esc_html( number_format_i18n( $percent, 1 ) )
	);
}

/**
 * 기간 내 발행 글 수
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return int
 */
function borobill_count_published_posts_in_range( $from, $to ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'date_query'             => array(
				array(
					'after'     => $from . ' 00:00:00',
					'before'    => $to . ' 23:59:59',
					'inclusive' => true,
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/**
 * 기간 내 일자별 조회수 합
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return int
 */
function borobill_sum_views_in_range( $from, $to ) {
	$rows  = borobill_get_daily_views_rows_range( $from, $to );
	$total = 0;
	foreach ( $rows as $row ) {
		$total += (int) $row['views'];
	}

	return $total;
}

/**
 * 기간 내 일자별 방문자 합
 *
 * @param string $from Y-m-d
 * @param string $to   Y-m-d
 * @return int
 */
function borobill_sum_visitors_in_range( $from, $to ) {
	$rows  = borobill_get_daily_visitor_rows_range( $from, $to );
	$total = 0;
	foreach ( $rows as $row ) {
		$total += (int) $row['views'];
	}

	return $total;
}

/**
 * @param array|null $chart_context borobill_get_stats_visitor_chart_context() 결과
 * @return array{
 *   published_count:int,
 *   total_published:int,
 *   total_views:int,
 *   views_change:array{percent:float,dir:string},
 *   avg_views:float,
 *   avg_views_change:array{percent:float,dir:string},
 *   unique_visitors:int,
 *   unique_share:float,
 *   returning_visitors:int,
 *   returning_of_total:float,
 *   returning_change:array{percent:float,dir:string},
 *   avg_stay_seconds:int,
 *   avg_stay_label:string,
 *   stay_change:array{percent:float,dir:string},
 *   period_visitors:int,
 *   top_posts:array<int, array{id:int,title:string,views:int,date:string,edit_url:string,category:string}>,
 *   category_rows:array<int, array{label:string,post_count:int,views:int}>,
 *   read_analysis:array,
 *   period_range_label:string
 * }
 */
function borobill_get_marketing_stats_dashboard_data( $chart_context = null ) {
	if ( null === $chart_context ) {
		$chart_context = borobill_get_stats_visitor_chart_context();
	}

	list( $from, $to )           = borobill_resolve_stats_period_dates( $chart_context );
	list( $prev_from, $prev_to ) = borobill_get_previous_period_dates( $from, $to );
	$total_published             = (int) wp_count_posts( 'post' )->publish;

	$published_count      = borobill_count_published_posts_in_range( $from, $to );
	$prev_published_count = borobill_count_published_posts_in_range( $prev_from, $prev_to );

	$total_views      = borobill_sum_views_in_range( $from, $to );
	$prev_total_views = borobill_sum_views_in_range( $prev_from, $prev_to );
	$views_change     = borobill_stats_percent_change( $total_views, $prev_total_views );

	$avg_views          = $published_count > 0 ? round( $total_views / $published_count, 1 ) : 0.0;
	$prev_avg_views     = $prev_published_count > 0 ? round( $prev_total_views / $prev_published_count, 1 ) : 0.0;
	$avg_views_change   = borobill_stats_percent_change( $avg_views, $prev_avg_views );

	$period_visitors = borobill_sum_visitors_in_range( $from, $to );
	if ( 'hourly' === $chart_context['mode'] ) {
		$period_visitors = 0;
		foreach ( borobill_get_visitor_chart_rows( $chart_context ) as $visitor_row ) {
			$period_visitors += (int) $visitor_row['views'];
		}
	}

	$identity_counts    = borobill_get_period_visitor_identity_counts( $from, $to );
	$unique_visitors    = (int) $identity_counts['unique'];
	$returning_visitors = (int) $identity_counts['returning'];

	if ( $unique_visitors <= 0 && $period_visitors > 0 ) {
		$unique_visitors = $period_visitors;
	}

	$prev_identity          = borobill_get_period_visitor_identity_counts( $prev_from, $prev_to );
	$prev_returning         = (int) $prev_identity['returning'];
	$prev_unique            = (int) $prev_identity['unique'];
	$prev_period_visitors   = borobill_sum_visitors_in_range( $prev_from, $prev_to );
	if ( $prev_unique <= 0 && $prev_period_visitors > 0 ) {
		$prev_unique = $prev_period_visitors;
	}

	$total_visitors_base = $period_visitors > 0 ? $period_visitors : $unique_visitors;
	$unique_share        = $period_visitors > 0
		? min( 100.0, round( ( $unique_visitors / $period_visitors ) * 100, 1 ) )
		: ( $unique_visitors > 0 ? 100.0 : 0.0 );
	$returning_of_total  = $total_visitors_base > 0
		? round( ( $returning_visitors / $total_visitors_base ) * 100, 1 )
		: 0.0;
	$returning_change    = borobill_stats_percent_change( $returning_visitors, $prev_returning );

	$avg_stay_seconds = borobill_get_period_avg_stay_seconds( $from, $to );
	$avg_stay_label   = borobill_format_stay_duration( $avg_stay_seconds );
	$prev_stay        = borobill_get_period_avg_stay_seconds( $prev_from, $prev_to );
	$stay_change      = borobill_stats_percent_change( $avg_stay_seconds, $prev_stay );

	borobill_backfill_views_from_read_stats();
	$top_posts = borobill_get_top_posts_by_period_views( $from, $to, 10 );

	$category_rows = array();
	foreach ( borobill_get_header_gnb_lnb_groups() as $group ) {
		if ( empty( $group['term_id'] ) ) {
			continue;
		}
		$cat_ids = borobill_get_category_tree_ids( (int) $group['term_id'] );
		if ( empty( $cat_ids ) ) {
			continue;
		}

		$cat_posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'category__in'   => $cat_ids,
				'date_query'     => array(
					array(
						'after'     => $from . ' 00:00:00',
						'before'    => $to . ' 23:59:59',
						'inclusive' => true,
					),
				),
			)
		);

		$cat_views = 0;
		foreach ( $cat_posts as $post_id ) {
			$cat_views += borobill_get_post_view_count( (int) $post_id );
		}

		$category_rows[] = array(
			'label'      => (string) $group['title'],
			'post_count' => count( $cat_posts ),
			'views'      => $cat_views,
		);
	}

	usort(
		$category_rows,
		static function ( $a, $b ) {
			return (int) $b['views'] <=> (int) $a['views'];
		}
	);

	$read_analysis      = borobill_get_period_read_analysis( $from, $to );
	$period_range_label = wp_date( 'Y.m.d', strtotime( $from ) ) . ' ~ ' . wp_date( 'Y.m.d', strtotime( $to ) );

	return array(
		'published_count'      => $published_count,
		'total_published'      => $total_published,
		'total_views'          => $total_views,
		'views_change'         => $views_change,
		'avg_views'            => $avg_views,
		'avg_views_change'     => $avg_views_change,
		'unique_visitors'      => $unique_visitors,
		'unique_share'         => $unique_share,
		'returning_visitors'   => $returning_visitors,
		'returning_of_total'   => $returning_of_total,
		'returning_change'     => $returning_change,
		'avg_stay_seconds'     => $avg_stay_seconds,
		'avg_stay_label'       => $avg_stay_label,
		'stay_change'          => $stay_change,
		'period_visitors'      => $period_visitors,
		'top_posts'            => $top_posts,
		'category_rows'        => $category_rows,
		'read_analysis'        => $read_analysis,
		'period_range_label'   => $period_range_label,
	);
}

/**
 * 관리자 통계 대시보드 렌더
 */
function borobill_render_stats_dashboard_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( '이 페이지에 접근할 권한이 없습니다.' ) );
	}

	$chart_context = borobill_get_stats_visitor_chart_context();
	$stats         = borobill_get_marketing_stats_dashboard_data( $chart_context );
	$views_rows    = borobill_get_views_chart_rows( $chart_context );
	$daily_rows    = borobill_get_visitor_chart_rows( $chart_context );
	$daily_period_options = array(
		'1'      => '1일(당일)',
		'5'      => '5일',
		'10'     => '10일',
		'15'     => '15일',
		'20'     => '20일',
		'25'     => '25일',
		'30'     => '30일',
		'custom' => '직접선택',
	);
	$daily_period             = $chart_context['period'];
	$daily_custom_from        = isset( $chart_context['from'] ) ? $chart_context['from'] : wp_date( 'Y-m-d', strtotime( '-6 days' ) );
	$daily_custom_to          = isset( $chart_context['to'] ) ? $chart_context['to'] : wp_date( 'Y-m-d' );
	$is_custom_period  = ( 'custom' === $daily_period );
	$generated_at      = wp_date( 'Y.m.d H:i' );
	list( $period_from, $period_to ) = borobill_resolve_stats_period_dates( $chart_context );
	$hourly_visitor_rows             = borobill_get_hourly_visitor_rows_range( $period_from, $period_to );
	?>
	<div class="wrap borobill-stats-dashboard">
		<div class="borobill-stats-header">
			<div class="borobill-stats-header__text">
				<h1>통계</h1>
				<p class="borobill-stats-dashboard__desc">
					블로그 게시글의 성과와 방문자 데이터를 분석해 콘텐츠 개선에 활용해보세요.
					<span class="borobill-stats-dashboard__updated">기준 <?php echo esc_html( $generated_at ); ?></span>
				</p>
			</div>
			<form method="get" class="borobill-stats-daily-period-form borobill-stats-period-global" aria-label="기간 설정">
				<input type="hidden" name="page" value="borobill-stats" />
				<span class="borobill-stats-period-global__range" aria-hidden="true">
					<span class="borobill-stats-period-global__icon"></span>
					<?php echo esc_html( $stats['period_range_label'] ); ?>
				</span>
				<label class="screen-reader-text" for="borobill-daily-period">조회 기간</label>
				<span class="borobill-stats-daily-period-form__select-wrap">
					<select id="borobill-daily-period" name="daily_period">
						<?php foreach ( $daily_period_options as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $daily_period, $option_key ); ?>>
								<?php echo esc_html( $option_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="borobill-stats-daily-period-form__arrow" aria-hidden="true"></span>
				</span>
				<span class="borobill-stats-daily-period-form__custom<?php echo $is_custom_period ? ' is-visible' : ''; ?>"<?php echo $is_custom_period ? '' : ' hidden'; ?>>
					<label class="screen-reader-text" for="borobill-daily-from">시작일</label>
					<input
						type="date"
						id="borobill-daily-from"
						name="daily_from"
						class="borobill-stats-daily-period-form__date-input"
						value="<?php echo esc_attr( $daily_custom_from ); ?>"
					/>
					<span class="borobill-stats-daily-period-form__range-sep" aria-hidden="true">~</span>
					<label class="screen-reader-text" for="borobill-daily-to">종료일</label>
					<input
						type="date"
						id="borobill-daily-to"
						name="daily_to"
						class="borobill-stats-daily-period-form__date-input"
						value="<?php echo esc_attr( $daily_custom_to ); ?>"
					/>
				</span>
			</form>
		</div>

		<div class="borobill-stats-kpi-grid">
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">발행 글</span>
				<strong class="borobill-stats-kpi-card__value"><?php echo esc_html( number_format_i18n( $stats['published_count'] ) ); ?></strong>
				<span class="borobill-stats-kpi-card__meta">전체 <?php echo esc_html( number_format_i18n( $stats['total_published'] ) ); ?>건</span>
			</div>
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">총 조회수</span>
				<strong class="borobill-stats-kpi-card__value"><?php echo esc_html( number_format_i18n( $stats['total_views'] ) ); ?></strong>
				<span class="borobill-stats-kpi-card__meta"><?php echo wp_kses_post( borobill_format_kpi_change_meta( $stats['views_change'], '지난 기간 대비' ) ); ?></span>
			</div>
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">평균 조회수</span>
				<strong class="borobill-stats-kpi-card__value"><?php echo esc_html( number_format_i18n( $stats['avg_views'], 1 ) ); ?></strong>
				<span class="borobill-stats-kpi-card__meta"><?php echo wp_kses_post( borobill_format_kpi_change_meta( $stats['avg_views_change'], '지난 기간 대비' ) ); ?></span>
			</div>
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">순방문자 수</span>
				<strong class="borobill-stats-kpi-card__value"><?php echo esc_html( number_format_i18n( $stats['unique_visitors'] ) ); ?></strong>
				<span class="borobill-stats-kpi-card__meta">전체 대비 <?php echo esc_html( number_format_i18n( $stats['unique_share'], 1 ) ); ?>%</span>
			</div>
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">재방문자 수</span>
				<strong class="borobill-stats-kpi-card__value">
					<?php echo esc_html( number_format_i18n( $stats['returning_visitors'] ) ); ?>명
					<span class="borobill-stats-kpi-card__ratio">(<?php echo esc_html( number_format_i18n( $stats['returning_of_total'], 1 ) ); ?>%)</span>
				</strong>
				<span class="borobill-stats-kpi-card__meta"><?php echo wp_kses_post( borobill_format_kpi_change_meta( $stats['returning_change'], '지난 기간 대비' ) ); ?></span>
			</div>
			<div class="borobill-stats-kpi-card">
				<span class="borobill-stats-kpi-card__label">평균 체류 시간</span>
				<strong class="borobill-stats-kpi-card__value"><?php echo esc_html( $stats['avg_stay_label'] ); ?></strong>
				<span class="borobill-stats-kpi-card__meta"><?php echo wp_kses_post( borobill_format_kpi_change_meta( $stats['stay_change'], '지난 기간 대비' ) ); ?></span>
			</div>
		</div>

		<div class="borobill-stats-charts-row">
			<section class="borobill-stats-panel borobill-stats-views-chart-panel">
				<h2 class="borobill-stats-panel__title">조회수 그래프</h2>
				<?php borobill_render_top_posts_views_bar_chart( $views_rows, 'daily' ); ?>
			</section>

			<section class="borobill-stats-panel borobill-stats-daily-chart-panel">
				<h2 class="borobill-stats-panel__title">방문자 수 그래프</h2>
				<?php borobill_render_daily_views_line_chart( $daily_rows, false ); ?>
			</section>
		</div>

		<div class="borobill-stats-panels borobill-stats-panels--triple">
			<section class="borobill-stats-panel borobill-stats-top10-panel">
				<div class="borobill-stats-panel__head">
					<h2 class="borobill-stats-panel__title">조회수 TOP 10</h2>
					<?php
					$top10_detail_args = array( 'page' => 'borobill-stats-top10' );
					if ( ! empty( $daily_period ) ) {
						$top10_detail_args['daily_period'] = $daily_period;
					}
					if ( 'custom' === $daily_period ) {
						$top10_detail_args['daily_from'] = $daily_custom_from;
						$top10_detail_args['daily_to']   = $daily_custom_to;
					}
					$top10_detail_url = add_query_arg( $top10_detail_args, admin_url( 'admin.php' ) );
					?>
					<a class="borobill-stats-panel__more" href="<?php echo esc_url( $top10_detail_url ); ?>">더보기 &gt;</a>
				</div>
				<div class="borobill-stats-panel__scroll<?php echo empty( $stats['top_posts'] ) ? ' borobill-stats-panel__scroll--empty' : ''; ?>">
					<table class="widefat striped borobill-stats-table">
					<thead>
						<tr>
							<th scope="col" class="borobill-stats-table__col-rank">순위</th>
							<th scope="col" class="borobill-stats-table__col-title">타이틀</th>
							<th scope="col">카테고리</th>
							<th scope="col" class="borobill-stats-table__col-views">조회수</th>
							<th scope="col" class="borobill-stats-table__col-date">발행일</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $stats['top_posts'] ) ) : ?>
							<tr class="borobill-stats-table__empty-row">
								<td colspan="5"><span class="borobill-stats-empty">선택한 기간에 조회된 게시글이 없습니다.</span></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $stats['top_posts'] as $index => $post_row ) : ?>
								<tr>
									<td class="borobill-stats-table__col-rank"><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
									<td>
										<?php if ( ! empty( $post_row['edit_url'] ) ) : ?>
											<a href="<?php echo esc_url( $post_row['edit_url'] ); ?>"><?php echo esc_html( $post_row['title'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $post_row['title'] ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $post_row['category'] ); ?></td>
									<td class="borobill-stats-table__col-views"><strong><?php echo esc_html( number_format_i18n( $post_row['views'] ) ); ?></strong></td>
									<td class="borobill-stats-table__col-date"><?php echo esc_html( $post_row['date'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				</div>
			</section>

			<section class="borobill-stats-panel borobill-stats-hourly-visitors-panel">
				<h2 class="borobill-stats-panel__title">시간대별 방문자 수</h2>
				<div class="borobill-stats-panel__scroll<?php echo empty( $hourly_visitor_rows ) ? ' borobill-stats-panel__scroll--empty' : ''; ?>">
					<table class="widefat striped borobill-stats-table borobill-stats-table--hourly">
						<thead>
							<tr>
								<th scope="col" class="borobill-stats-table__col-day">날짜</th>
								<th scope="col" class="borobill-stats-table__col-hour">시간</th>
								<th scope="col" class="borobill-stats-table__col-views">방문자 수</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $hourly_visitor_rows ) ) : ?>
								<tr class="borobill-stats-table__empty-row">
									<td colspan="3"><span class="borobill-stats-empty">표시할 방문자 데이터가 없습니다.</span></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $hourly_visitor_rows as $visitor_row ) : ?>
									<tr>
										<td class="borobill-stats-table__col-day"><?php echo esc_html( (string) $visitor_row['date_label'] ); ?></td>
										<td class="borobill-stats-table__col-hour"><?php echo esc_html( (string) $visitor_row['label'] ); ?></td>
										<td class="borobill-stats-table__col-views"><strong><?php echo esc_html( number_format_i18n( (int) $visitor_row['views'] ) ); ?></strong></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</section>

			<section class="borobill-stats-panel borobill-stats-read-panel">
				<div class="borobill-stats-panel__head">
					<h2 class="borobill-stats-panel__title">콘텐츠 읽기 분석</h2>
				</div>
				<p class="borobill-stats-read-panel__desc">전체 시청(최초 보기 1회)을 포함</p>
				<?php
				$read = isset( $stats['read_analysis'] ) && is_array( $stats['read_analysis'] )
					? $stats['read_analysis']
					: array(
						'segments'       => array(),
						'avg_progress'   => 0,
						'avg_drop_rate'  => 0,
						'avg_read_label' => borobill_format_stay_duration( 0 ),
						'sessions'       => 0,
					);
				$segments = isset( $read['segments'] ) && is_array( $read['segments'] ) ? $read['segments'] : array();
				$funnel_colors = array( '#3b82f6', '#22c55e', '#eab308', '#f97316', '#a855f7' );
				?>
				<div class="borobill-stats-read-panel__body">
					<div class="borobill-stats-read-funnel" aria-hidden="true">
						<?php if ( empty( $segments ) ) : ?>
							<div class="borobill-stats-read-funnel__empty">데이터 없음</div>
						<?php else : ?>
							<?php foreach ( $segments as $index => $segment ) : ?>
								<?php
								$width = isset( $segment['reached_rate'] ) ? max( 18, (float) $segment['reached_rate'] ) : 18;
								$color = isset( $funnel_colors[ $index ] ) ? $funnel_colors[ $index ] : '#94a3b8';
								?>
								<div
									class="borobill-stats-read-funnel__step"
									style="width: <?php echo esc_attr( (string) $width ); ?>%; background: <?php echo esc_attr( $color ); ?>;"
									title="<?php echo esc_attr( $segment['label'] . ' ' . number_format_i18n( (int) $segment['reached'] ) . '명' ); ?>"
								></div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="borobill-stats-read-panel__table-wrap">
						<p class="borobill-stats-read-panel__table-title">구간별 이탈 현황</p>
						<table class="widefat striped borobill-stats-table borobill-stats-table--read">
							<thead>
								<tr>
									<th scope="col">구간</th>
									<th scope="col">도달 사용자</th>
									<th scope="col">이탈 사용자</th>
									<th scope="col">이탈률</th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $segments ) ) : ?>
									<tr class="borobill-stats-table__empty-row">
										<td colspan="4"><span class="borobill-stats-empty">선택한 기간 읽기 데이터가 없습니다.</span></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $segments as $segment ) : ?>
										<tr>
											<td><?php echo esc_html( (string) $segment['label'] ); ?></td>
											<td>
												<?php
												echo esc_html(
													number_format_i18n( (int) $segment['reached'] ) . '명 (' . number_format_i18n( (float) $segment['reached_rate'], 1 ) . '%)'
												);
												?>
											</td>
											<td><?php echo esc_html( number_format_i18n( (int) $segment['dropped'] ) . '명' ); ?></td>
											<td><?php echo esc_html( number_format_i18n( (float) $segment['drop_rate'], 1 ) . '%' ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="borobill-stats-read-summary">
					<div class="borobill-stats-read-summary__item">
						<span class="borobill-stats-read-summary__label">평균 읽기 진행률</span>
						<strong class="borobill-stats-read-summary__value is-accent"><?php echo esc_html( number_format_i18n( (float) $read['avg_progress'], 1 ) ); ?>%</strong>
					</div>
					<div class="borobill-stats-read-summary__item">
						<span class="borobill-stats-read-summary__label">평균 이탈률</span>
						<strong class="borobill-stats-read-summary__value"><?php echo esc_html( number_format_i18n( (float) $read['avg_drop_rate'], 1 ) ); ?>%</strong>
					</div>
					<div class="borobill-stats-read-summary__item">
						<span class="borobill-stats-read-summary__label">평균 읽는 시간</span>
						<strong class="borobill-stats-read-summary__value"><?php echo esc_html( (string) $read['avg_read_label'] ); ?></strong>
					</div>
				</div>
				<p class="borobill-stats-read-panel__note">* 읽기 진행률은 사용자의 스크롤 깊이를 기준으로 계산됩니다.</p>
			</section>
		</div>
	</div>
	<?php
}

/**
 * 하단 3열 카드: 콘텐츠 읽기 분석 높이에 맞춰 나머지 카드 높이 동기화
 */
function borobill_stats_dashboard_footer_script() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'toplevel_page_borobill-stats' !== $screen->id ) {
		return;
	}
	?>
	<script>
	(function () {
		function syncTriplePanelHeights() {
			var grid = document.querySelector('.borobill-stats-panels--triple');
			if (!grid) {
				return;
			}

			var panels = grid.querySelectorAll('.borobill-stats-panel');
			if (panels.length < 2) {
				return;
			}

			var reference = grid.querySelector('.borobill-stats-read-panel') || panels[panels.length - 1];

			for (var i = 0; i < panels.length; i++) {
				panels[i].style.height = '';
				panels[i].style.maxHeight = '';
				panels[i].classList.remove('is-height-matched');
			}

			var height = reference.offsetHeight;
			if (height <= 0) {
				return;
			}

			for (var j = 0; j < panels.length; j++) {
				if (panels[j] === reference) {
					continue;
				}
				panels[j].style.height = height + 'px';
				panels[j].style.maxHeight = height + 'px';
				panels[j].classList.add('is-height-matched');
			}
		}

		function scheduleSync() {
			syncTriplePanelHeights();
			window.requestAnimationFrame(syncTriplePanelHeights);
		}

		function initTriplePanelHeights() {
			scheduleSync();
			window.addEventListener('load', scheduleSync);

			var reference = document.querySelector('.borobill-stats-panels--triple .borobill-stats-read-panel')
				|| document.querySelector('.borobill-stats-panels--triple .borobill-stats-panel:last-child');
			if (reference && typeof ResizeObserver !== 'undefined') {
				new ResizeObserver(scheduleSync).observe(reference);
			} else {
				window.addEventListener('resize', scheduleSync);
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initTriplePanelHeights);
		} else {
			initTriplePanelHeights();
		}

		function initDailyPeriodForm() {
			var form = document.querySelector('.borobill-stats-daily-period-form');
			if (!form) {
				return;
			}

			var select = form.querySelector('#borobill-daily-period');
			var customWrap = form.querySelector('.borobill-stats-daily-period-form__custom');
			var fromInput = form.querySelector('#borobill-daily-from');
			var toInput = form.querySelector('#borobill-daily-to');

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

		function initDailyLineChartTooltips() {
			document.querySelectorAll('.borobill-stats-daily-line-chart--interactive').forEach(function (chart) {
				var tooltip = chart.querySelector('.borobill-stats-daily-line-chart__tooltip');
				var tooltipLabel = chart.querySelector('.borobill-stats-daily-line-chart__tooltip-label');
				var tooltipValue = chart.querySelector('.borobill-stats-daily-line-chart__tooltip-value');
				var points = chart.querySelectorAll('.borobill-stats-daily-line-chart__point');

				if (!tooltip || !tooltipLabel || !tooltipValue || !points.length) {
					return;
				}

				var activePoint = null;

				function hideTooltip() {
					activePoint = null;
					tooltip.hidden = true;
					tooltip.classList.remove('is-visible');
				}

				function showTooltip(point) {
					var label = point.getAttribute('data-point-label') || '';
					var value = point.getAttribute('data-point-value') || '0';
					var chartRect = chart.getBoundingClientRect();
					var dot = point.querySelector('.borobill-stats-daily-line-chart__dot') || point;
					var dotRect = dot.getBoundingClientRect();

					tooltipLabel.textContent = label;
					tooltipValue.textContent = Number(value).toLocaleString('ko-KR') + '명';
					tooltip.style.left = (dotRect.left + dotRect.width / 2 - chartRect.left) + 'px';
					tooltip.style.top = (dotRect.top - chartRect.top) + 'px';
					tooltip.hidden = false;
					tooltip.classList.add('is-visible');
					activePoint = point;
				}

				points.forEach(function (point) {
					point.addEventListener('mouseenter', function () {
						showTooltip(point);
					});
					point.addEventListener('focus', function () {
						showTooltip(point);
					});
					point.addEventListener('mouseleave', function () {
						hideTooltip();
					});
					point.addEventListener('blur', function () {
						hideTooltip();
					});
				});
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initDailyLineChartTooltips);
		} else {
			initDailyLineChartTooltips();
		}
	})();
	</script>
	<?php
}
add_action( 'admin_footer', 'borobill_stats_dashboard_footer_script' );

/**
 * 최근 N일 방문자 합계
 *
 * @param int $days
 * @return int
 */
function borobill_get_visitors_last_n_days( $days ) {
	$days = max( 1, (int) $days );
	$to   = wp_date( 'Y-m-d' );
	$from = wp_date( 'Y-m-d', strtotime( '-' . ( $days - 1 ) . ' days' ) );

	return borobill_sum_visitors_in_range( $from, $to );
}

/**
 * TOP10 상세용 미니 라인 차트 (Y축 우측)
 *
 * @param array<int, array{date:string,label:string,views:int}> $daily_rows
 * @param string $aria_label
 */
function borobill_render_top10_mini_visitor_chart( array $daily_rows, $aria_label = '방문자 수 추이' ) {
	$aria_label = (string) $aria_label;
	if ( '' === $aria_label ) {
		$aria_label = '방문자 수 추이';
	}

	$width      = 360;
	$height     = 120;
	$pad_left   = 8;
	$pad_right  = 36;
	$pad_top    = 10;
	$pad_bottom = 22;
	$plot_width  = $width - $pad_left - $pad_right;
	$plot_height = $height - $pad_top - $pad_bottom;
	$base_y      = $pad_top + $plot_height;
	$count       = count( $daily_rows );

	if ( $count <= 0 ) {
		?>
		<div class="borobill-top10-mini-chart borobill-top10-mini-chart--empty" role="img" aria-label="<?php echo esc_attr( $aria_label ); ?>">
			<svg viewBox="0 0 <?php echo (int) $width; ?> <?php echo (int) $height; ?>" preserveAspectRatio="none">
				<line class="borobill-top10-mini-chart__grid" x1="<?php echo (int) $pad_left; ?>" y1="<?php echo (int) $base_y; ?>" x2="<?php echo (int) ( $width - $pad_right ); ?>" y2="<?php echo (int) $base_y; ?>" />
				<text class="borobill-top10-mini-chart__tick" x="<?php echo (int) ( $width - 4 ); ?>" y="<?php echo (int) ( $base_y + 3 ); ?>" text-anchor="end">0</text>
			</svg>
		</div>
		<?php
		return;
	}

	$max_views = 0;
	foreach ( $daily_rows as $row ) {
		$max_views = max( $max_views, (int) $row['views'] );
	}
	$chart_max = max( 1, $max_views );
	$ticks     = array( $chart_max, (int) round( $chart_max * 2 / 3 ), (int) round( $chart_max / 3 ), 0 );
	$ticks     = array_values( array_unique( $ticks ) );
	rsort( $ticks, SORT_NUMERIC );

	$last_index = $count - 1;
	$points     = array();
	$line_parts = array();
	foreach ( $daily_rows as $index => $row ) {
		$views = (int) $row['views'];
		$x     = $pad_left + ( $last_index > 0 ? ( $index / $last_index ) * $plot_width : 0 );
		$y     = $pad_top + $plot_height - ( $views / $chart_max ) * $plot_height;
		$points[]     = array( 'x' => $x, 'y' => $y, 'label' => (string) $row['label'] );
		$line_parts[] = ( 0 === $index ? 'M' : 'L' ) . round( $x, 2 ) . ' ' . round( $y, 2 );
	}

	$first_x   = round( $points[0]['x'], 2 );
	$last_x    = round( $points[ $last_index ]['x'], 2 );
	$area_path = implode( ' ', $line_parts ) . ' L ' . $last_x . ' ' . $base_y . ' L ' . $first_x . ' ' . $base_y . ' Z';
	$line_path = implode( ' ', $line_parts );

	// 포인트가 많으면 X축 라벨을 균등 간격으로만 표시
	$label_step = $count > 10 ? (int) max( 1, ceil( $count / 7 ) ) : 1;
	?>
	<div class="borobill-top10-mini-chart" role="img" aria-label="<?php echo esc_attr( $aria_label ); ?>">
		<svg viewBox="0 0 <?php echo (int) $width; ?> <?php echo (int) $height; ?>" preserveAspectRatio="none">
			<defs>
				<linearGradient id="borobillTop10MiniFill" x1="0" y1="0" x2="0" y2="1">
					<stop offset="0%" stop-color="#60a5fa" stop-opacity="0.3" />
					<stop offset="100%" stop-color="#2563eb" stop-opacity="0.02" />
				</linearGradient>
			</defs>
			<?php foreach ( $ticks as $tick ) : ?>
				<?php $tick_y = $pad_top + $plot_height - ( (int) $tick / $chart_max ) * $plot_height; ?>
				<line
					class="borobill-top10-mini-chart__grid"
					x1="<?php echo (int) $pad_left; ?>"
					y1="<?php echo esc_attr( (string) round( $tick_y, 2 ) ); ?>"
					x2="<?php echo (int) ( $width - $pad_right ); ?>"
					y2="<?php echo esc_attr( (string) round( $tick_y, 2 ) ); ?>"
				/>
				<text
					class="borobill-top10-mini-chart__tick"
					x="<?php echo (int) ( $width - 4 ); ?>"
					y="<?php echo esc_attr( (string) round( $tick_y + 3, 2 ) ); ?>"
					text-anchor="end"
				><?php echo esc_html( number_format_i18n( (int) $tick ) ); ?></text>
			<?php endforeach; ?>
			<path class="borobill-top10-mini-chart__area" d="<?php echo esc_attr( $area_path ); ?>" fill="url(#borobillTop10MiniFill)" />
			<path class="borobill-top10-mini-chart__line" d="<?php echo esc_attr( $line_path ); ?>" fill="none" />
			<?php foreach ( $points as $point_index => $point ) : ?>
				<circle class="borobill-top10-mini-chart__dot" cx="<?php echo esc_attr( (string) round( $point['x'], 2 ) ); ?>" cy="<?php echo esc_attr( (string) round( $point['y'], 2 ) ); ?>" r="3" />
				<?php if ( 0 === ( $point_index % $label_step ) || $point_index === $last_index ) : ?>
					<text class="borobill-top10-mini-chart__label" x="<?php echo esc_attr( (string) round( $point['x'], 2 ) ); ?>" y="<?php echo (int) ( $height - 4 ); ?>" text-anchor="middle"><?php echo esc_html( $point['label'] ); ?></text>
				<?php endif; ?>
			<?php endforeach; ?>
		</svg>
	</div>
	<?php
}

/**
 * 구간별 이탈 스택 바
 *
 * @param array<int, float|int> $segments 5구간 비율(합 100 또는 0)
 */
function borobill_render_top10_dropoff_bar( array $segments ) {
	$colors = array( '#1d4ed8', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd' );
	$total  = 0.0;
	foreach ( $segments as $value ) {
		$total += (float) $value;
	}
	?>
	<div class="borobill-top10-dropoff-bar" aria-hidden="true">
		<?php if ( $total <= 0 ) : ?>
			<span class="borobill-top10-dropoff-bar__empty"></span>
		<?php else : ?>
			<?php foreach ( $segments as $index => $value ) : ?>
				<?php
				$pct   = max( 0, (float) $value );
				$color = isset( $colors[ $index ] ) ? $colors[ $index ] : '#94a3b8';
				if ( $pct <= 0 ) {
					continue;
				}
				?>
				<span
					class="borobill-top10-dropoff-bar__seg"
					style="width: <?php echo esc_attr( (string) $pct ); ?>%; background: <?php echo esc_attr( $color ); ?>;"
				><?php echo esc_html( number_format_i18n( $pct, 0 ) ); ?>%</span>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * TOP10 상세 CSV 다운로드 (admin HTML 출력 전)
 */
function borobill_handle_top10_csv_download() {
	if ( ! is_admin() ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'borobill-stats-top10' !== $page ) {
		return;
	}

	if ( ! isset( $_GET['download'] ) || 'csv' !== sanitize_key( wp_unslash( $_GET['download'] ) ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( '이 페이지에 접근할 권한이 없습니다.' ) );
	}

	borobill_backfill_views_from_read_stats();

	$chart_context = borobill_get_stats_visitor_chart_context();
	list( $period_from, $period_to ) = borobill_resolve_stats_period_dates( $chart_context );

	$top_posts = borobill_get_top_posts_by_period_views( $period_from, $period_to, 0, 0, false );
	$post_ids  = array();
	foreach ( $top_posts as $post_row ) {
		$post_ids[] = isset( $post_row['id'] ) ? (int) $post_row['id'] : 0;
	}
	$posts_read = borobill_get_posts_read_stats_for_period( $post_ids, $period_from, $period_to );

	$rows = array();
	foreach ( $top_posts as $post_row ) {
		$post_id    = isset( $post_row['id'] ) ? (int) $post_row['id'] : 0;
		$read_stats = ( $post_id > 0 && isset( $posts_read[ $post_id ] ) ) ? $posts_read[ $post_id ] : null;
		$dropoff    = $read_stats ? $read_stats['dropoff'] : array( 0, 0, 0, 0, 0 );
		$rows[]     = array(
			'title'      => isset( $post_row['title'] ) ? (string) $post_row['title'] : '',
			'category'   => isset( $post_row['category'] ) ? (string) $post_row['category'] : '',
			'views'      => isset( $post_row['views'] ) ? (int) $post_row['views'] : 0,
			'date'       => $post_id > 0 ? get_the_date( 'Y.m.d', $post_id ) : '',
			'read_label' => $read_stats ? (string) $read_stats['avg_read_label'] : '—',
			'dropoff'    => $dropoff,
		);
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="borobill-top10-' . wp_date( 'Ymd' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) {
		exit;
	}

	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv(
		$out,
		array(
			'순위',
			'제목',
			'카테고리',
			'조회수',
			'발행일',
			'평균 읽기 시간',
			'이탈 0~24%',
			'이탈 25~49%',
			'이탈 50~74%',
			'이탈 75~99%',
			'이탈 100%',
		)
	);

	foreach ( $rows as $index => $row ) {
		$dropoff = isset( $row['dropoff'] ) && is_array( $row['dropoff'] ) ? $row['dropoff'] : array( 0, 0, 0, 0, 0 );
		fputcsv(
			$out,
			array(
				(string) ( $index + 1 ),
				isset( $row['title'] ) ? (string) $row['title'] : '',
				isset( $row['category'] ) ? (string) $row['category'] : '',
				isset( $row['views'] ) ? (string) (int) $row['views'] : '0',
				isset( $row['date'] ) ? (string) $row['date'] : '',
				isset( $row['read_label'] ) ? (string) $row['read_label'] : '',
				isset( $dropoff[0] ) ? (string) $dropoff[0] : '0',
				isset( $dropoff[1] ) ? (string) $dropoff[1] : '0',
				isset( $dropoff[2] ) ? (string) $dropoff[2] : '0',
				isset( $dropoff[3] ) ? (string) $dropoff[3] : '0',
				isset( $dropoff[4] ) ? (string) $dropoff[4] : '0',
			)
		);
	}

	fclose( $out );
	exit;
}
add_action( 'admin_init', 'borobill_handle_top10_csv_download' );

/**
 * 조회수 TOP 상세 (메뉴 미노출)
 */
function borobill_render_stats_top10_detail_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( '이 페이지에 접근할 권한이 없습니다.' ) );
	}

	// 읽기만 있고 조회수가 0인 깨진 데이터 보정
	borobill_backfill_views_from_read_stats();

	$chart_context = borobill_get_stats_visitor_chart_context();
	list( $period_from, $period_to ) = borobill_resolve_stats_period_dates( $chart_context );

	$daily_period_options = array(
		'1'      => '1일(당일)',
		'5'      => '5일',
		'10'     => '10일',
		'15'     => '15일',
		'20'     => '20일',
		'25'     => '25일',
		'30'     => '30일',
		'custom' => '직접선택',
	);
	$daily_period      = $chart_context['period'];
	$daily_custom_from = isset( $chart_context['from'] ) ? $chart_context['from'] : wp_date( 'Y-m-d', strtotime( '-6 days' ) );
	$daily_custom_to   = isset( $chart_context['to'] ) ? $chart_context['to'] : wp_date( 'Y-m-d' );
	$is_custom_period  = ( 'custom' === $daily_period );
	$period_range_label = wp_date( 'Y.m.d', strtotime( $period_from ) ) . ' – ' . wp_date( 'Y.m.d', strtotime( $period_to ) );
	$period_summary_label = isset( $chart_context['summary_label'] ) ? (string) $chart_context['summary_label'] : $period_range_label;

	$trend_rows = borobill_get_visitor_chart_rows( $chart_context );
	if ( 'hourly' !== $chart_context['mode'] ) {
		foreach ( $trend_rows as &$trend_row ) {
			$trend_row['label'] = wp_date( 'm.d', strtotime( $trend_row['date'] ) );
		}
		unset( $trend_row );
	}
	$trend_aria_label = $period_summary_label . ' 방문자 수 추이';

	if ( 'hourly' === $chart_context['mode'] ) {
		$period_visitors = 0;
		foreach ( $trend_rows as $visitor_row ) {
			$period_visitors += (int) $visitor_row['views'];
		}
	} else {
		$period_visitors = borobill_sum_visitors_in_range( $period_from, $period_to );
	}

	$week_visitors  = borobill_get_visitors_last_n_days( 7 );
	$month_visitors = borobill_get_visitors_last_n_days( 30 );

	$read_analysis  = borobill_get_period_read_analysis( $period_from, $period_to );
	$avg_read_label = isset( $read_analysis['avg_read_label'] ) ? (string) $read_analysis['avg_read_label'] : borobill_format_stay_duration( 0 );

	$per_page    = 10;
	$total_posts = borobill_count_posts_by_period_views( $period_from, $period_to, false );
	$total_pages = max( 1, (int) ceil( $total_posts / $per_page ) );
	$paged       = isset( $_GET['paged'] ) ? max( 1, (int) wp_unslash( $_GET['paged'] ) ) : 1;
	if ( $paged > $total_pages ) {
		$paged = $total_pages;
	}
	$offset    = ( $paged - 1 ) * $per_page;
	$top_posts = borobill_get_top_posts_by_period_views( $period_from, $period_to, $per_page, $offset, false );

	$post_ids = array();
	foreach ( $top_posts as $post_row ) {
		$post_ids[] = isset( $post_row['id'] ) ? (int) $post_row['id'] : 0;
	}
	$posts_read = borobill_get_posts_read_stats_for_period( $post_ids, $period_from, $period_to );

	$table_rows = array();
	foreach ( $top_posts as $post_row ) {
		$post_id    = isset( $post_row['id'] ) ? (int) $post_row['id'] : 0;
		$read_stats = ( $post_id > 0 && isset( $posts_read[ $post_id ] ) ) ? $posts_read[ $post_id ] : null;
		$table_rows[] = array(
			'id'         => $post_id,
			'title'      => isset( $post_row['title'] ) ? (string) $post_row['title'] : '',
			'category'   => isset( $post_row['category'] ) ? (string) $post_row['category'] : '',
			'views'      => isset( $post_row['views'] ) ? (int) $post_row['views'] : 0,
			'date'       => $post_id > 0 ? get_the_date( 'Y.m.d', $post_id ) : '',
			'edit_url'   => isset( $post_row['edit_url'] ) ? (string) $post_row['edit_url'] : '',
			'read_label' => $read_stats ? (string) $read_stats['avg_read_label'] : '—',
			'dropoff'    => $read_stats ? $read_stats['dropoff'] : array( 0, 0, 0, 0, 0 ),
		);
	}

	$back_args = array( 'page' => 'borobill-stats' );
	if ( ! empty( $daily_period ) ) {
		$back_args['daily_period'] = $daily_period;
	}
	if ( $is_custom_period ) {
		$back_args['daily_from'] = $daily_custom_from;
		$back_args['daily_to']   = $daily_custom_to;
	}
	$back_url = add_query_arg( $back_args, admin_url( 'admin.php' ) );

	$base_list_args = array( 'page' => 'borobill-stats-top10' );
	if ( ! empty( $daily_period ) ) {
		$base_list_args['daily_period'] = $daily_period;
	}
	if ( $is_custom_period ) {
		$base_list_args['daily_from'] = $daily_custom_from;
		$base_list_args['daily_to']   = $daily_custom_to;
	}

	$csv_args            = $base_list_args;
	$csv_args['download'] = 'csv';
	$csv_url             = add_query_arg( $csv_args, admin_url( 'admin.php' ) );

	$prev_url = $paged > 1
		? add_query_arg( array_merge( $base_list_args, array( 'paged' => $paged - 1 ) ), admin_url( 'admin.php' ) )
		: '';
	$next_url = $paged < $total_pages
		? add_query_arg( array_merge( $base_list_args, array( 'paged' => $paged + 1 ) ), admin_url( 'admin.php' ) )
		: '';

	$legend = array(
		array( 'label' => '0~24%', 'color' => '#1d4ed8' ),
		array( 'label' => '25~49%', 'color' => '#2563eb' ),
		array( 'label' => '50~74%', 'color' => '#3b82f6' ),
		array( 'label' => '75~99%', 'color' => '#60a5fa' ),
		array( 'label' => '100% 도달', 'color' => '#93c5fd' ),
	);
	?>
	<div class="wrap borobill-stats-top10-detail">
		<header class="borobill-top10-detail__header">
			<div class="borobill-top10-detail__header-main">
				<div class="borobill-top10-detail__title-row">
					<a class="borobill-top10-detail__back" href="<?php echo esc_url( $back_url ); ?>" aria-label="통계로 돌아가기">
						<span class="borobill-top10-detail__back-icon" aria-hidden="true"></span>
					</a>
					<h1 class="borobill-top10-detail__title">게시글 조회수 전체 보기</h1>
				</div>
				<p class="borobill-top10-detail__desc">조회수가 높은 콘텐츠의 상세 통계를 확인하세요.</p>
			</div>
			<form method="get" class="borobill-stats-daily-period-form borobill-stats-period-global borobill-top10-detail__period" aria-label="기간 설정">
				<input type="hidden" name="page" value="borobill-stats-top10" />
				<span class="borobill-stats-period-global__range" aria-hidden="true">
					<span class="borobill-stats-period-global__icon"></span>
					<?php echo esc_html( $period_range_label ); ?>
				</span>
				<label class="screen-reader-text" for="borobill-top10-daily-period">조회 기간</label>
				<span class="borobill-stats-daily-period-form__select-wrap">
					<select id="borobill-top10-daily-period" name="daily_period" onchange="this.form.submit()">
						<?php foreach ( $daily_period_options as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $daily_period, $option_key ); ?>>
								<?php echo esc_html( $option_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="borobill-stats-daily-period-form__arrow" aria-hidden="true"></span>
				</span>
				<span class="borobill-stats-daily-period-form__custom<?php echo $is_custom_period ? ' is-visible' : ''; ?>">
					<input
						type="date"
						class="borobill-stats-daily-period-form__date-input"
						name="daily_from"
						value="<?php echo esc_attr( $daily_custom_from ); ?>"
						onchange="this.form.submit()"
					/>
					<span class="borobill-stats-daily-period-form__range-sep">~</span>
					<input
						type="date"
						class="borobill-stats-daily-period-form__date-input"
						name="daily_to"
						value="<?php echo esc_attr( $daily_custom_to ); ?>"
						onchange="this.form.submit()"
					/>
				</span>
			</form>
		</header>

		<section class="borobill-top10-summary" aria-label="요약 지표">
			<div class="borobill-top10-summary__card borobill-top10-summary__card--today">
				<div class="borobill-top10-summary__card-head">
					<span class="borobill-top10-summary__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/></svg>
					</span>
					<span class="borobill-top10-summary__label">방문자 수</span>
				</div>
				<strong class="borobill-top10-summary__value"><?php echo esc_html( number_format_i18n( $period_visitors ) ); ?>명</strong>
				<span class="borobill-top10-summary__meta"><?php echo esc_html( $period_range_label ); ?></span>
			</div>

			<div class="borobill-top10-summary__card borobill-top10-summary__card--trend">
				<span class="borobill-top10-summary__label"><?php echo esc_html( $trend_aria_label ); ?></span>
				<?php borobill_render_top10_mini_visitor_chart( $trend_rows, $trend_aria_label ); ?>
			</div>

			<div class="borobill-top10-summary__card">
				<span class="borobill-top10-summary__label">주간 방문자 수</span>
				<strong class="borobill-top10-summary__value borobill-top10-summary__value--plain"><?php echo esc_html( number_format_i18n( $week_visitors ) ); ?>명</strong>
				<span class="borobill-top10-summary__meta">지난 7일</span>
			</div>

			<div class="borobill-top10-summary__card">
				<span class="borobill-top10-summary__label">월간 방문자 수</span>
				<strong class="borobill-top10-summary__value borobill-top10-summary__value--plain"><?php echo esc_html( number_format_i18n( $month_visitors ) ); ?>명</strong>
				<span class="borobill-top10-summary__meta">지난 30일</span>
			</div>

			<div class="borobill-top10-summary__card">
				<span class="borobill-top10-summary__label">평균 읽기 시간</span>
				<strong class="borobill-top10-summary__value borobill-top10-summary__value--plain"><?php echo esc_html( $avg_read_label ); ?></strong>
				<span class="borobill-top10-summary__meta"><?php echo esc_html( $period_range_label ); ?></span>
			</div>
		</section>

		<section class="borobill-top10-table-panel">
			<div class="borobill-top10-table-panel__head">
				<h2 class="borobill-top10-table-panel__title">게시글 조회수 전체 보기</h2>
				<a class="borobill-top10-table-panel__csv" href="<?php echo esc_url( $csv_url ); ?>">
					<iconify-icon class="borobill-top10-table-panel__csv-icon" icon="tabler:download" width="16" height="16" aria-hidden="true"></iconify-icon>
					CSV 다운로드
				</a>
			</div>

			<div class="borobill-top10-table-wrap">
				<table class="borobill-top10-table">
					<thead>
						<tr>
							<th scope="col" class="borobill-top10-table__col-rank">순위</th>
							<th scope="col" class="borobill-top10-table__col-title">제목</th>
							<th scope="col">카테고리</th>
							<th scope="col" class="borobill-top10-table__col-views">조회수</th>
							<th scope="col">발행일</th>
							<th scope="col">평균 읽기 시간</th>
							<th scope="col" class="borobill-top10-table__col-dropoff">
								<span class="borobill-top10-table__dropoff-title">평균 사용자 구간별 이탈 현황</span>
								<span class="borobill-top10-table__legend">
									<?php foreach ( $legend as $item ) : ?>
										<span class="borobill-top10-table__legend-item">
											<span class="borobill-top10-table__legend-swatch" style="background: <?php echo esc_attr( $item['color'] ); ?>;"></span>
											<?php echo esc_html( $item['label'] ); ?>
										</span>
									<?php endforeach; ?>
								</span>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $table_rows ) ) : ?>
							<tr>
								<td colspan="7" class="borobill-top10-table__empty">선택한 기간에 표시할 게시글이 없습니다.</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $table_rows as $index => $row ) : ?>
								<tr>
									<td class="borobill-top10-table__col-rank"><?php echo esc_html( (string) ( $offset + $index + 1 ) ); ?></td>
									<td class="borobill-top10-table__col-title">
										<?php if ( ! empty( $row['edit_url'] ) ) : ?>
											<a href="<?php echo esc_url( $row['edit_url'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $row['title'] ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $row['category'] !== '' ? $row['category'] : '—' ); ?></td>
									<td class="borobill-top10-table__col-views"><?php echo esc_html( number_format_i18n( (int) $row['views'] ) ); ?></td>
									<td><?php echo esc_html( $row['date'] ); ?></td>
									<td><?php echo esc_html( $row['read_label'] ); ?></td>
									<td class="borobill-top10-table__col-dropoff">
										<?php borobill_render_top10_dropoff_bar( $row['dropoff'] ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<nav class="borobill-top10-pagination" aria-label="페이지 탐색">
				<?php if ( $prev_url ) : ?>
					<a class="borobill-top10-pagination__btn" href="<?php echo esc_url( $prev_url ); ?>" aria-label="이전 페이지">&lt;</a>
				<?php else : ?>
					<span class="borobill-top10-pagination__btn is-disabled" aria-disabled="true">&lt;</span>
				<?php endif; ?>

				<?php
				$page_window = 5;
				$start_page  = max( 1, $paged - (int) floor( $page_window / 2 ) );
				$end_page    = min( $total_pages, $start_page + $page_window - 1 );
				$start_page  = max( 1, $end_page - $page_window + 1 );
				for ( $page_num = $start_page; $page_num <= $end_page; $page_num++ ) :
					$page_url = add_query_arg( array_merge( $base_list_args, array( 'paged' => $page_num ) ), admin_url( 'admin.php' ) );
					?>
					<?php if ( $page_num === $paged ) : ?>
						<span class="borobill-top10-pagination__page is-current" aria-current="page"><?php echo esc_html( (string) $page_num ); ?></span>
					<?php else : ?>
						<a class="borobill-top10-pagination__page" href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html( (string) $page_num ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $next_url ) : ?>
					<a class="borobill-top10-pagination__btn" href="<?php echo esc_url( $next_url ); ?>" aria-label="다음 페이지">&gt;</a>
				<?php else : ?>
					<span class="borobill-top10-pagination__btn is-disabled" aria-disabled="true">&gt;</span>
				<?php endif; ?>
			</nav>
		</section>
	</div>
	<script>
	(function () {
		var select = document.getElementById('borobill-top10-daily-period');
		var custom = document.querySelector('.borobill-top10-detail__period .borobill-stats-daily-period-form__custom');
		if (!select || !custom) return;
		select.addEventListener('change', function () {
			custom.classList.toggle('is-visible', select.value === 'custom');
		});
	})();
	</script>
	<?php
}

