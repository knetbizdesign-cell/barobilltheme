<?php
/**
 * 배너설정 > 하단 배너 슬라이드 관리 (목록 + 에디터 + 게시/정지)
 *
 * @package borobill_theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── 마이그레이션 ──────────────────────────────────────────────────────────────

/**
 * 기존 단일 하단 배너 옵션을 슬라이드 스토리지(v2)로 한 번만 변환한다.
 */
function borobill_maybe_migrate_bottom_banner_to_v2() {
	if ( get_option( 'borobill_bottom_banner_storage_v2' ) ) {
		return;
	}

	// 이미 레지스트리가 있으면 마이그레이션 불필요
	$existing = get_option( 'borobill_bottom_banner_slide_registry', null );
	if ( is_array( $existing ) && ! empty( $existing['order'] ) && is_array( $existing['order'] ) ) {
		update_option( 'borobill_bottom_banner_storage_v2', '1', false );
		return;
	}

	// 구 옵션 읽기
	$old_image   = (string) get_option( 'borobill_bottom_banner_image', '' );
	$old_title   = (string) get_option( 'borobill_bottom_banner_title', '바로빌이 궁금하시나요?' );
	$old_body    = (string) get_option( 'borobill_bottom_banner_body', '' );
	$old_bg      = (string) get_option( 'borobill_bottom_banner_bg_color', '#7ea354' );
	$old_btn     = (string) get_option( 'borobill_bottom_banner_button_text', '바로빌 바로가기' );
	$old_url     = (string) get_option( 'borobill_bottom_banner_url', '' );
	$old_new_tab = (int) get_option( 'borobill_bottom_banner_new_tab', 1 );
	$old_enabled = (int) get_option( 'borobill_bottom_banner_enabled', 0 );

	$status = ( 1 === $old_enabled ) ? 'published' : 'paused';

	// 슬라이드 1 생성
	update_option( 'borobill_bottom_banner_image_1', $old_image, false );
	update_option( 'borobill_bottom_banner_title_1', $old_title, false );
	update_option( 'borobill_bottom_banner_body_1', $old_body, false );
	update_option( 'borobill_bottom_banner_bg_color_1', $old_bg, false );
	update_option( 'borobill_bottom_banner_button_text_1', $old_btn, false );
	update_option( 'borobill_bottom_banner_url_1', $old_url, false );
	update_option( 'borobill_bottom_banner_new_tab_1', (string) $old_new_tab, false );
	update_option( 'borobill_bottom_banner_status_1', $status, false );
	update_option( 'borobill_bottom_banner_updated_at_1', current_time( 'mysql' ), false );

	// 레지스트리 및 순서 저장
	update_option(
		'borobill_bottom_banner_slide_registry',
		array( 'next_id' => 2, 'order' => array( 1 ) ),
		false
	);
	update_option( 'borobill_bottom_banner_order', '1', false );
	update_option( 'borobill_bottom_banner_storage_v2', '1', false );
}
add_action( 'init', 'borobill_maybe_migrate_bottom_banner_to_v2', 2 );

// ── 레지스트리 ────────────────────────────────────────────────────────────────

/**
 * @return array{next_id:int,order:int[]}
 */
function borobill_get_bottom_banner_slide_registry() {
	$registry = get_option( 'borobill_bottom_banner_slide_registry', null );
	if ( is_array( $registry ) && ! empty( $registry['order'] ) && is_array( $registry['order'] ) ) {
		$order = array_values(
			array_filter(
				array_map( 'intval', $registry['order'] ),
				function ( $id ) {
					return $id > 0;
				}
			)
		);
		if ( ! empty( $order ) ) {
			$next_id = isset( $registry['next_id'] ) ? (int) $registry['next_id'] : ( max( $order ) + 1 );
			if ( $next_id <= max( $order ) ) {
				$next_id = max( $order ) + 1;
			}
			return array( 'next_id' => $next_id, 'order' => $order );
		}
	}

	// 순서 옵션에서 복구
	$order_string = get_option( 'borobill_bottom_banner_order', '1' );
	$order        = array_values(
		array_filter(
			array_map( 'intval', explode( ',', (string) $order_string ) ),
			function ( $id ) {
				return $id > 0;
			}
		)
	);
	if ( empty( $order ) ) {
		$order = array( 1 );
	}

	$registry = array(
		'next_id' => max( $order ) + 1,
		'order'   => $order,
	);
	update_option( 'borobill_bottom_banner_slide_registry', $registry, false );
	borobill_sync_bottom_banner_order_option( $registry['order'] );

	return $registry;
}

/**
 * @param array{next_id?:int,order?:int[]} $registry
 */
function borobill_save_bottom_banner_slide_registry( $registry ) {
	$order = array_values(
		array_filter(
			array_map( 'intval', (array) ( $registry['order'] ?? array() ) ),
			function ( $id ) {
				return $id > 0;
			}
		)
	);
	$next_id = isset( $registry['next_id'] ) ? (int) $registry['next_id'] : ( empty( $order ) ? 1 : max( $order ) + 1 );
	if ( ! empty( $order ) && $next_id <= max( $order ) ) {
		$next_id = max( $order ) + 1;
	}

	$registry = array(
		'next_id' => $next_id,
		'order'   => $order,
	);
	update_option( 'borobill_bottom_banner_slide_registry', $registry, false );

	$order_string = implode( ',', array_map( 'strval', $order ) );
	if ( get_option( 'borobill_bottom_banner_order', '' ) !== $order_string ) {
		update_option( 'borobill_bottom_banner_order', $order_string, false );
	}
}

/**
 * @param int[]|null $order_ids
 */
function borobill_sync_bottom_banner_order_option( $order_ids = null ) {
	if ( null === $order_ids ) {
		$order_ids = borobill_get_bottom_banner_slide_order_ids();
	}
	update_option( 'borobill_bottom_banner_order', implode( ',', array_map( 'strval', $order_ids ) ), false );
}

/**
 * @param int $slide_id
 */
function borobill_ensure_bottom_banner_slide_in_registry( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	$registry = borobill_get_bottom_banner_slide_registry();
	if ( in_array( $slide_id, $registry['order'], true ) ) {
		return;
	}

	$registry['order'][] = $slide_id;
	if ( $registry['next_id'] <= $slide_id ) {
		$registry['next_id'] = $slide_id + 1;
	}
	borobill_save_bottom_banner_slide_registry( $registry );
}

/**
 * @return int
 */
function borobill_peek_next_bottom_banner_slide_id() {
	$registry = borobill_get_bottom_banner_slide_registry();
	return (int) $registry['next_id'];
}

/**
 * @param int $slide_id
 */
function borobill_delete_bottom_banner_slide( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	$registry          = borobill_get_bottom_banner_slide_registry();
	$registry['order'] = array_values(
		array_filter(
			$registry['order'],
			function ( $id ) use ( $slide_id ) {
				return (int) $id !== $slide_id;
			}
		)
	);
	borobill_save_bottom_banner_slide_registry( $registry );

	$fields = array( 'image', 'title', 'body', 'bg_color', 'button_color', 'button_text_color', 'button_text', 'url', 'new_tab', 'status' );
	foreach ( $fields as $field ) {
		delete_option( 'borobill_bottom_banner_' . $field . '_' . $slide_id );
	}
	delete_option( 'borobill_bottom_banner_updated_at_' . $slide_id );
}

// ── 신규 슬라이드 저장 전 레지스트리 등록 ────────────────────────────────────

function borobill_prepare_new_bottom_banner_slide_before_save() {
	if ( ! is_admin() || empty( $_POST['option_page'] ) ) {
		return;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );
	if ( borobill_get_theme_bottom_banner_settings_group() !== $option_page ) {
		return;
	}

	if ( empty( $_POST['borobill_bottom_banner_is_new'] ) || '1' !== (string) wp_unslash( $_POST['borobill_bottom_banner_is_new'] ) ) {
		return;
	}

	$slide_id = isset( $_POST['borobill_bottom_banner_active_slide'] ) ? (int) $_POST['borobill_bottom_banner_active_slide'] : 0;
	if ( $slide_id < 1 ) {
		return;
	}

	borobill_ensure_bottom_banner_slide_in_registry( $slide_id );
}
add_action( 'admin_init', 'borobill_prepare_new_bottom_banner_slide_before_save', 9 );

// ── 상태 / 슬라이드 ID / 업데이트 시각 ──────────────────────────────────────

/**
 * @param int $slide_id
 * @return string published|paused
 */
function borobill_get_bottom_banner_slide_status( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return 'published';
	}
	$status = get_option( 'borobill_bottom_banner_status_' . $slide_id, 'published' );
	return in_array( $status, array( 'published', 'paused' ), true ) ? $status : 'published';
}

/**
 * @return int[]
 */
function borobill_get_bottom_banner_slide_order_ids() {
	$registry = borobill_get_bottom_banner_slide_registry();
	return array_values( array_map( 'intval', $registry['order'] ) );
}

/**
 * 프론트: 게시 상태 슬라이드 ID만 반환
 *
 * @return int[]
 */
function borobill_get_published_bottom_banner_slide_order_ids() {
	$order_ids = borobill_get_bottom_banner_slide_order_ids();
	return array_values(
		array_filter(
			$order_ids,
			function ( $id ) {
				return 'published' === borobill_get_bottom_banner_slide_status( (int) $id );
			}
		)
	);
}

/**
 * @param int $slide_id
 * @return string
 */
function borobill_get_bottom_banner_slide_list_title( $slide_id ) {
	$slide_id  = (int) $slide_id;
	$title_raw = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_title_' . $slide_id, '' );
	$title_raw = trim( preg_replace( '/\s+/u', ' ', str_replace( array( "\r\n", "\r", "\n" ), ' ', $title_raw ) ) );
	if ( '' !== $title_raw ) {
		return $title_raw;
	}
	return '슬라이드 ' . $slide_id;
}

/**
 * @param int $slide_id
 * @return string
 */
function borobill_get_bottom_banner_slide_updated_label( $slide_id ) {
	$updated = get_option( 'borobill_bottom_banner_updated_at_' . (int) $slide_id, '' );
	if ( ! is_string( $updated ) || '' === trim( $updated ) ) {
		return '';
	}
	$time = strtotime( $updated );
	if ( ! $time ) {
		return '';
	}
	return date_i18n( 'Y.m.d H:i', $time );
}

/**
 * @param int $slide_id
 * @return int
 */
function borobill_get_bottom_banner_slide_updated_timestamp( $slide_id ) {
	$updated = get_option( 'borobill_bottom_banner_updated_at_' . (int) $slide_id, '' );
	if ( ! is_string( $updated ) || '' === trim( $updated ) ) {
		return 0;
	}
	$time = strtotime( $updated );
	return $time ? (int) $time : 0;
}

/**
 * 관리자 왼쪽 목록용: 수정일 최신순
 *
 * @return int[]
 */
function borobill_get_bottom_banner_slide_admin_list_ids() {
	$list_ids = borobill_get_bottom_banner_slide_order_ids();

	usort(
		$list_ids,
		function ( $a, $b ) {
			$time_a = borobill_get_bottom_banner_slide_updated_timestamp( $a );
			$time_b = borobill_get_bottom_banner_slide_updated_timestamp( $b );
			if ( $time_a === $time_b ) {
				return (int) $b <=> (int) $a;
			}
			return $time_b <=> $time_a;
		}
	);

	return array_values( array_map( 'intval', $list_ids ) );
}

/**
 * @param int $slide_id
 */
function borobill_touch_bottom_banner_slide_updated_at( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}
	update_option( 'borobill_bottom_banner_updated_at_' . $slide_id, current_time( 'mysql' ) );
}

// ── 부분저장 보호 필터 ────────────────────────────────────────────────────────

/**
 * 하단 배너 슬라이드 옵션명인지 확인 (options.php 부분 저장 보호용)
 *
 * @param string $option
 * @return bool
 */
function borobill_is_theme_bottom_banner_slide_option( $option ) {
	if ( ! is_string( $option ) ) {
		return false;
	}

	$shared = array( 'borobill_bottom_banner_order' );
	if ( in_array( $option, $shared, true ) ) {
		return true;
	}

	return (bool) preg_match(
		'/^borobill_bottom_banner_(image|title|body|bg_color|button_color|button_text_color|button_text|url|new_tab|status)_\d+$/',
		$option
	);
}

/**
 * 슬라이드별 저장 폼에서 POST되지 않은 옵션은 기존 값 유지
 *
 * @param mixed  $value
 * @param string $option
 * @param mixed  $old_value
 * @return mixed
 */
function borobill_preserve_unposted_bottom_banner_slide_options( $value, $option, $old_value ) {
	if ( ! is_admin() || empty( $_POST['option_page'] ) ) {
		return $value;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );
	if ( borobill_get_theme_bottom_banner_settings_group() !== $option_page ) {
		return $value;
	}

	if ( ! borobill_is_theme_bottom_banner_slide_option( $option ) ) {
		return $value;
	}

	if ( ! array_key_exists( $option, $_POST ) ) {
		return $old_value;
	}

	return $value;
}
add_filter( 'pre_update_option', 'borobill_preserve_unposted_bottom_banner_slide_options', 10, 3 );

/**
 * 빈 문자열로 저장된 옵션은 기본값으로 처리
 *
 * @param string $option_name
 * @param mixed  $default
 * @return mixed
 */
function borobill_get_bottom_banner_slide_stored_option( $option_name, $default = '' ) {
	$value = get_option( $option_name, null );
	if ( null === $value ) {
		return $default;
	}
	if ( is_string( $value ) && '' === trim( $value ) ) {
		return $default;
	}
	return $value;
}

// ── 상태 설정 등록 ────────────────────────────────────────────────────────────

function borobill_register_bottom_banner_slide_status_settings() {
	$slide_ids = borobill_get_bottom_banner_slide_order_ids();
	foreach ( $slide_ids as $slide_id ) {
		$slide_id = (int) $slide_id;
		register_setting(
			borobill_get_theme_bottom_banner_settings_group(),
			'borobill_bottom_banner_status_' . $slide_id,
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) use ( $slide_id ) {
					$status = in_array( $value, array( 'published', 'paused' ), true ) ? $value : 'published';
					borobill_touch_bottom_banner_slide_updated_at( $slide_id );
					return $status;
				},
				'default'           => 'published',
			)
		);
	}
}
add_action( 'admin_init', 'borobill_register_bottom_banner_slide_status_settings', 11 );

function borobill_register_bottom_banner_slide_updated_hooks() {
	static $registered = array();
	$fields    = array( 'image', 'title', 'body', 'bg_color', 'button_color', 'button_text_color', 'button_text', 'url', 'new_tab' );
	$slide_ids = borobill_get_bottom_banner_slide_order_ids();
	foreach ( $slide_ids as $slide_id ) {
		$slide_id = (int) $slide_id;
		foreach ( $fields as $field ) {
			$hook = 'update_option_borobill_bottom_banner_' . $field . '_' . $slide_id;
			if ( isset( $registered[ $hook ] ) ) {
				continue;
			}
			$registered[ $hook ] = true;
			add_action(
				$hook,
				function () use ( $slide_id ) {
					borobill_touch_bottom_banner_slide_updated_at( $slide_id );
				}
			);
		}
	}
}
add_action( 'admin_init', 'borobill_register_bottom_banner_slide_updated_hooks' );

// ── 순서 저장 시 레지스트리 동기화 ───────────────────────────────────────────

/**
 * @param mixed $value
 * @return mixed
 */
function borobill_sync_bottom_banner_slide_registry_from_order_option( $value ) {
	$order_ids = array_values(
		array_filter(
			array_map( 'intval', explode( ',', (string) $value ) ),
			function ( $id ) {
				return $id > 0;
			}
		)
	);
	if ( empty( $order_ids ) ) {
		return $value;
	}

	$registry = borobill_get_bottom_banner_slide_registry();
	if ( $registry['order'] === $order_ids ) {
		return $value;
	}

	$registry['order'] = $order_ids;
	if ( $registry['next_id'] <= max( $order_ids ) ) {
		$registry['next_id'] = max( $order_ids ) + 1;
	}
	update_option( 'borobill_bottom_banner_slide_registry', $registry, false );

	return $value;
}
add_filter( 'pre_update_option_borobill_bottom_banner_order', 'borobill_sync_bottom_banner_slide_registry_from_order_option' );

// ── 저장 리다이렉트 (슬라이드 유지 + 탭 유지) ────────────────────────────────

function borobill_bottom_banner_save_redirect( $location ) {
	if ( ! is_admin() || ! isset( $_POST['option_page'] ) ) {
		return $location;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );

	// 전환 옵션 저장 시 tab=bottom 리다이렉트
	if ( borobill_get_theme_bottom_banner_transition_settings_group() === $option_page ) {
		$location = add_query_arg( 'saved', $option_page, $location );
		$location = add_query_arg( 'tab', 'bottom', $location );
		return $location;
	}

	// 슬라이드 저장 시 slide=X 유지
	if ( borobill_get_theme_bottom_banner_settings_group() === $option_page ) {
		if ( isset( $_POST['borobill_bottom_banner_active_slide'] ) ) {
			$slide = (int) $_POST['borobill_bottom_banner_active_slide'];
			if ( $slide > 0 ) {
				$location = add_query_arg( 'slide', $slide, $location );
			}
		}
	}

	return $location;
}
add_filter( 'wp_redirect', 'borobill_bottom_banner_save_redirect', 11 );

// ── 관리자 UI 렌더링 ─────────────────────────────────────────────────────────

/**
 * @param int  $slide_id
 * @param bool $is_active
 * @param string $bottom_order
 * @param bool $is_draft
 */
function borobill_render_bottom_banner_slide_editor_card( $slide_id, $is_active = false, $bottom_order = '1', $is_draft = false ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	if ( $is_draft ) {
		$img            = '';
		$title          = '';
		$body           = '';
		$bg_color       = '#7ea354';
		$btn_color      = '';
		$btn_text_color = '#ffffff';
		$btn_text       = '';
		$url            = '';
		$new_tab        = 1;
		$status         = 'paused';
	} else {
		$img            = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_image_' . $slide_id, '' );
		$title          = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_title_' . $slide_id, '' );
		$body           = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_body_' . $slide_id, '' );
		$bg_color       = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_bg_color_' . $slide_id, '#7ea354' );
		$btn_color      = function_exists( 'borobill_get_bottom_banner_button_color_for_editor' )
			? borobill_get_bottom_banner_button_color_for_editor( $slide_id )
			: '';
		$btn_text_color_raw = get_option( 'borobill_bottom_banner_button_text_color_' . $slide_id, '#ffffff' );
		$btn_text_color     = is_string( $btn_text_color_raw ) ? trim( $btn_text_color_raw ) : '#ffffff';
		if ( '' === $btn_text_color ) {
			$btn_text_color = '#ffffff';
		}
		$btn_text = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_button_text_' . $slide_id, '바로빌 바로가기' );
		$url      = (string) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_url_' . $slide_id, '' );
		$new_tab  = (int) borobill_get_bottom_banner_slide_stored_option( 'borobill_bottom_banner_new_tab_' . $slide_id, 1 );
		$status   = borobill_get_bottom_banner_slide_status( $slide_id );
	}

	$status_label   = 'published' === $status ? '게시' : '정지';
	$list_title     = borobill_get_bottom_banner_slide_list_title( $slide_id );
	$preview_image  = '' !== trim( $img ) ? $img : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
	$carousel_order = borobill_get_bottom_banner_slide_order_ids();
	$position_index = array_search( $slide_id, $carousel_order, true );
	$slide_position = false === $position_index ? count( $carousel_order ) + 1 : $position_index + 1;
	$max_position   = $is_draft ? count( $carousel_order ) + 1 : max( 1, count( $carousel_order ) );
	?>
	<form
		class="borobill-banner-settings-form borobill-bottom-slide-form"
		method="post"
		action="options.php"
		data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"
		<?php echo $is_draft ? ' data-is-draft="1"' : ''; ?>
		<?php echo $is_active ? '' : ' hidden'; ?>
	>
		<?php settings_fields( borobill_get_theme_bottom_banner_settings_group() ); ?>
		<input type="hidden" name="borobill_bottom_banner_order" class="borobill-bottom-order-input" value="<?php echo esc_attr( $bottom_order ); ?>">
		<input type="hidden" name="borobill_bottom_banner_active_slide" class="borobill-bottom-active-slide-input" value="<?php echo esc_attr( (string) $slide_id ); ?>">
		<?php if ( $is_draft ) : ?>
			<input type="hidden" name="borobill_bottom_banner_is_new" value="1">
		<?php endif; ?>

	<div class="borobill-bottom-slide-card<?php echo $is_active ? ' is-active' : ''; ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
		<div class="borobill-hero-slide-status" data-bottom-status-bar>
			<div class="borobill-hero-slide-status__meta">
				<span class="borobill-hero-slide-status__label">상태</span>
				<span class="borobill-hero-slide-status__badge is-<?php echo esc_attr( $status ); ?>" data-bottom-status-badge><?php echo esc_html( $status_label ); ?></span>
			</div>
			<div class="borobill-hero-slide-status__actions">
				<button type="button" class="button button-primary borobill-bottom-status-publish" data-bottom-status-action="published" <?php disabled( 'published' === $status ); ?>>게시</button>
				<button type="button" class="button borobill-bottom-status-pause" data-bottom-status-action="paused" <?php disabled( 'paused' === $status ); ?>>정지</button>
			</div>
			<input type="hidden" id="borobill_bottom_banner_status_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_status_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $status ); ?>" data-bottom-status-input>
		</div>

		<h2 class="borobill-hero-slide-card__title"><?php echo esc_html( $list_title ); ?></h2>

		<div class="bb-field">
			<div
				class="borobill-bottom-admin-preview<?php echo trim( $img ) ? ' has-media' : ''; ?>"
				data-bb-bottom-preview="<?php echo esc_attr( (string) $slide_id ); ?>"
				data-bb-bottom-preview-cta
				style="--cta-bg: <?php echo esc_attr( $bg_color ? $bg_color : '#7ea354' ); ?>; --cta-btn-color: <?php echo esc_attr( $btn_text_color ); ?>;<?php echo '' !== trim( (string) $btn_color ) ? ' --cta-btn-bg: ' . esc_attr( $btn_color ) . ';' : ''; ?>"
			>
				<div class="borobill-bottom-admin-preview__media" data-bb-bottom-preview-media<?php echo trim( $img ) ? '' : ' hidden'; ?> aria-hidden="true">
					<img
						id="borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>_preview"
						class="borobill-bottom-admin-preview__illust"
						src="<?php echo esc_url( $preview_image ); ?>"
						alt=""
					>
				</div>

				<div class="borobill-bottom-admin-preview__content">
					<div class="borobill-bottom-admin-preview__title" data-bb-bottom-preview-title><?php echo esc_html( $title ); ?></div>
					<div class="borobill-bottom-admin-preview__desc" data-bb-bottom-preview-body><?php echo nl2br( esc_html( $body ) ); ?></div>
				</div>

				<div class="borobill-bottom-admin-preview__action" data-bb-bottom-preview-action<?php echo trim( $btn_text ) ? '' : ' hidden'; ?>>
					<span class="borobill-bottom-admin-preview__button">
						<span class="borobill-bottom-admin-preview__button-text" data-bb-bottom-preview-btn><?php echo esc_html( $btn_text ); ?></span>
						<span class="borobill-bottom-admin-preview__button-icon" aria-hidden="true"></span>
					</span>
				</div>
			</div>

			<input type="hidden" class="borobill-image-input" id="borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $img ); ?>">
			<div class="bb-actions">
				<button type="button" class="button borobill-image-select" data-target-input="#borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>" data-target-preview="#borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>_preview">이미지 불러오기</button>
				<button type="button" class="button borobill-image-remove" data-target-input="#borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>" data-target-preview="#borobill_bottom_banner_image_<?php echo esc_attr( (string) $slide_id ); ?>_preview">제거</button>
			</div>
			<p class="description">권장: 600×240px 이상. 이미지는 왼쪽 영역에 표시됩니다.</p>
		</div>

		<div class="bb-field">
			<div class="borobill-color-pair">
				<div>
					<label for="borobill_bottom_banner_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>배경 색상</strong></label>
					<input type="text" class="regular-text borobill-color-field" id="borobill_bottom_banner_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $bg_color ); ?>">
				</div>
				<div class="borobill-hero-slide-order-field">
					<label for="borobill_bottom_banner_slide_order_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>배너 순서</strong></label>
					<div class="borobill-hero-slide-order-control">
						<input type="number" min="1" max="<?php echo esc_attr( (string) $max_position ); ?>" step="1" id="borobill_bottom_banner_slide_order_<?php echo esc_attr( (string) $slide_id ); ?>" class="borobill-bottom-slide-order-input" value="<?php echo esc_attr( (string) $slide_position ); ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>">
					</div>
				</div>
			</div>
		</div>

		<div class="bb-field">
			<div class="borobill-color-pair">
				<div>
					<label for="borobill_bottom_banner_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 색상</strong></label>
					<input
						type="text"
						class="regular-text borobill-color-field borobill-color-field--alpha"
						id="borobill_bottom_banner_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"
						name="borobill_bottom_banner_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"
						value="<?php echo esc_attr( $btn_color ); ?>"
						data-default-color=""
						data-alpha-enabled="true"
						data-alpha-color-type="rgba"
					>
					<p class="description">비워두면 기본 버튼 색이 적용됩니다. 색상표에서 투명도도 조절할 수 있습니다.</p>
				</div>
				<div>
					<label for="borobill_bottom_banner_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 글자색</strong></label>
					<input type="text" class="regular-text borobill-color-field" id="borobill_bottom_banner_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $btn_text_color ); ?>" data-default-color="#ffffff">
				</div>
			</div>
		</div>

		<div class="bb-field">
			<label for="borobill_bottom_banner_title_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>타이틀</strong></label>
			<input type="text" class="regular-text" id="borobill_bottom_banner_title_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_title_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $title ); ?>">
		</div>

		<div class="bb-field">
			<label for="borobill_bottom_banner_body_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>본문</strong></label>
			<textarea id="borobill_bottom_banner_body_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_body_<?php echo esc_attr( (string) $slide_id ); ?>" rows="3"><?php echo esc_textarea( $body ); ?></textarea>
			<p class="description">줄바꿈은 그대로 반영됩니다.</p>
		</div>

		<div class="bb-field bb-stack">
			<div>
				<label for="borobill_bottom_banner_button_text_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 텍스트</strong></label>
				<input type="text" class="regular-text" id="borobill_bottom_banner_button_text_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_button_text_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $btn_text ); ?>">
			</div>
			<div>
				<label for="borobill_bottom_banner_url_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>이동 링크(URL)</strong></label>
				<input type="url" class="regular-text" id="borobill_bottom_banner_url_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_bottom_banner_url_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com">
				<p class="description">http/https만 허용됩니다.</p>
			</div>
			<div>
				<input type="hidden" name="borobill_bottom_banner_new_tab_<?php echo esc_attr( (string) $slide_id ); ?>" value="0">
				<label>
					<input type="checkbox" name="borobill_bottom_banner_new_tab_<?php echo esc_attr( (string) $slide_id ); ?>" value="1" <?php checked( $new_tab, 1 ); ?>>
					새 창으로 열기
				</label>
			</div>
		</div>

		<p class="borobill-banner-settings-form__actions borobill-bottom-slide-form__actions">
			<?php submit_button( '저장', 'primary', 'submit', false ); ?>
			<button type="button" class="button borobill-bottom-slide-delete">삭제</button>
		</p>
	</div>
	</form>
	<?php
}

/**
 * @param int  $slide_id
 * @param int  $index
 * @param bool $is_active
 * @param bool $is_draft
 */
function borobill_render_bottom_banner_slide_list_item( $slide_id, $index, $is_active = false, $is_draft = false ) {
	$slide_id     = (int) $slide_id;
	$status       = $is_draft ? 'paused' : borobill_get_bottom_banner_slide_status( $slide_id );
	$list_title   = $is_draft ? '새 슬라이드' : borobill_get_bottom_banner_slide_list_title( $slide_id );
	$updated      = $is_draft ? '' : borobill_get_bottom_banner_slide_updated_label( $slide_id );
	$status_label = 'published' === $status ? '게시' : '정지';
	?>
	<li class="borobill-hero-manager__item borobill-bottom-manager__item<?php echo $is_active ? ' is-active' : ''; ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"<?php echo $is_draft ? ' data-is-draft="1"' : ''; ?>>
		<button type="button" class="borobill-hero-manager__item-btn">
			<span class="borobill-hero-manager__item-handle" aria-hidden="true"></span>
			<span class="borobill-hero-manager__item-body">
				<span class="borobill-hero-manager__item-top">
					<strong class="borobill-hero-manager__item-title"><?php echo esc_html( $list_title ); ?></strong>
					<span class="borobill-hero-manager__item-status is-<?php echo esc_attr( $status ); ?>" data-list-status><?php echo esc_html( $status_label ); ?></span>
				</span>
				<span class="borobill-hero-manager__item-meta">
					<span>슬라이드 <?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
					<?php if ( '' !== $updated ) : ?>
						<span><?php echo esc_html( $updated ); ?></span>
					<?php endif; ?>
				</span>
			</span>
		</button>
	</li>
	<?php
}

function borobill_render_bottom_banner_slides_manager() {
	$order_ids    = borobill_get_bottom_banner_slide_order_ids();
	$list_ids     = borobill_get_bottom_banner_slide_admin_list_ids();
	$bottom_order = implode( ',', array_map( 'strval', $order_ids ) );
	$active_slide = isset( $_GET['slide'] ) ? (int) $_GET['slide'] : 0;
	if ( ! in_array( $active_slide, $order_ids, true ) ) {
		$active_slide = ! empty( $list_ids ) ? (int) $list_ids[0] : 0;
	}
	?>
	<div class="borobill-hero-manager borobill-bottom-banner-manager" data-active-slide="<?php echo esc_attr( (string) $active_slide ); ?>">
		<aside class="borobill-hero-manager__list-panel">
			<div class="borobill-hero-manager__list-head">
				<h2>하단 배너 슬라이드</h2>
				<button type="button" class="button button-primary" id="borobill-bottom-create">작성하기</button>
			</div>
			<ul class="borobill-hero-manager__list" id="borobill-bottom-slides-list">
				<?php foreach ( $list_ids as $index => $slide_id ) : ?>
					<?php borobill_render_bottom_banner_slide_list_item( (int) $slide_id, (int) $index, $active_slide === (int) $slide_id ); ?>
				<?php endforeach; ?>
			</ul>
		</aside>

		<div class="borobill-hero-manager__editor-panel">
			<div class="borobill-hero-manager__editor-empty" id="borobill-bottom-editor-empty"<?php echo $active_slide ? ' hidden' : ''; ?>>
				<p>왼쪽 목록에서 슬라이드를 선택하거나 <strong>작성하기</strong>를 눌러 편집을 시작하세요.</p>
			</div>
			<div id="borobill-bottom-slides" class="borobill-hero-manager__editor-cards">
				<?php
				foreach ( $order_ids as $slide_id ) {
					$slide_id = (int) $slide_id;
					borobill_render_bottom_banner_slide_editor_card(
						$slide_id,
						$active_slide === $slide_id,
						$bottom_order
					);
				}
				?>
			</div>
		</div>
	</div>
	<?php
}

// ── AJAX: 작성하기 ────────────────────────────────────────────────────────────

function borobill_ajax_create_bottom_banner_slide() {
	check_ajax_referer( 'borobill_bottom_banner_manager', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => '권한이 없습니다.' ), 403 );
	}

	$new_id      = borobill_peek_next_bottom_banner_slide_id();
	$order_ids   = borobill_get_bottom_banner_slide_order_ids();
	$draft_order = array_merge( $order_ids, array( $new_id ) );
	$bottom_order = implode( ',', array_map( 'strval', $draft_order ) );

	// AJAX 렌더 시 referer가 admin-ajax.php로 잡히지 않게
	$_REQUEST['_wp_http_referer'] = admin_url( 'admin.php?page=' . borobill_get_banner_settings_menu_slug() . '&tab=bottom' );

	ob_start();
	borobill_render_bottom_banner_slide_list_item( $new_id, count( $order_ids ), false, true );
	$list_item_html = ob_get_clean();

	ob_start();
	borobill_render_bottom_banner_slide_editor_card( $new_id, true, $bottom_order, true );
	$editor_html = ob_get_clean();

	wp_send_json_success(
		array(
			'slide_id'       => $new_id,
			'list_item_html' => $list_item_html,
			'editor_html'    => $editor_html,
			'bottom_order'   => $bottom_order,
		)
	);
}
add_action( 'wp_ajax_borobill_create_bottom_banner_slide', 'borobill_ajax_create_bottom_banner_slide' );

// ── AJAX: 삭제 ───────────────────────────────────────────────────────────────

function borobill_ajax_delete_bottom_banner_slide() {
	check_ajax_referer( 'borobill_bottom_banner_manager', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => '권한이 없습니다.' ), 403 );
	}

	$slide_id = isset( $_POST['slide_id'] ) ? (int) $_POST['slide_id'] : 0;
	if ( $slide_id < 1 ) {
		wp_send_json_error( array( 'message' => '잘못된 슬라이드입니다.' ), 400 );
	}

	borobill_delete_bottom_banner_slide( $slide_id );

	wp_send_json_success(
		array(
			'slide_id' => $slide_id,
		)
	);
}
add_action( 'wp_ajax_borobill_delete_bottom_banner_slide', 'borobill_ajax_delete_bottom_banner_slide' );
