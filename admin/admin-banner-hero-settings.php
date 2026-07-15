<?php
/**
 * 배너설정 > 히어로 슬라이드 관리 (목록 + 에디터 + 게시/정지)
 *
 * @package borobill_theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array{next_id:int,order:int[]}
 */
function borobill_get_hero_slide_registry() {
	$registry = get_option( 'borobill_hero_slide_registry', null );
	if ( is_array( $registry ) && ! empty( $registry['order'] ) && is_array( $registry['order'] ) ) {
		$order = array_values(
			array_filter(
				array_map( 'intval', $registry['order'] ),
				function ( $id ) {
					return $id > 0;
				}
			)
		);
		$next_id = isset( $registry['next_id'] ) ? (int) $registry['next_id'] : ( max( $order ) + 1 );
		if ( $next_id <= max( $order ) ) {
			$next_id = max( $order ) + 1;
		}
		return array(
			'next_id' => $next_id,
			'order'   => $order,
		);
	}

	$order_string = get_option( 'borobill_hero_order', '1,2,3' );
	$order        = array_values(
		array_filter(
			array_map( 'intval', explode( ',', (string) $order_string ) ),
			function ( $id ) {
				return $id > 0;
			}
		)
	);
	if ( empty( $order ) ) {
		$order = array( 1, 2, 3 );
	}

	$registry = array(
		'next_id' => max( $order ) + 1,
		'order'   => $order,
	);
	update_option( 'borobill_hero_slide_registry', $registry, false );
	borobill_sync_hero_order_option( $registry['order'] );

	return $registry;
}

/**
 * @param array{next_id?:int,order?:int[]} $registry
 */
function borobill_save_hero_slide_registry( $registry ) {
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
	update_option( 'borobill_hero_slide_registry', $registry, false );

	$order_string = implode( ',', array_map( 'strval', $order ) );
	if ( get_option( 'borobill_hero_order', '' ) !== $order_string ) {
		update_option( 'borobill_hero_order', $order_string, false );
	}
}

/**
 * @param int[]|null $order_ids
 */
function borobill_sync_hero_order_option( $order_ids = null ) {
	if ( null === $order_ids ) {
		$order_ids = borobill_get_hero_slide_order_ids();
	}
	update_option( 'borobill_hero_order', implode( ',', array_map( 'strval', $order_ids ) ), false );
}

/**
 * @param int $slide_id
 */
function borobill_ensure_hero_slide_in_registry( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	$registry = borobill_get_hero_slide_registry();
	if ( in_array( $slide_id, $registry['order'], true ) ) {
		return;
	}

	$registry['order'][] = $slide_id;
	if ( $registry['next_id'] <= $slide_id ) {
		$registry['next_id'] = $slide_id + 1;
	}
	borobill_save_hero_slide_registry( $registry );
}

/**
 * @return int
 */
function borobill_peek_next_hero_slide_id() {
	$registry = borobill_get_hero_slide_registry();
	return (int) $registry['next_id'];
}

/**
 * @param int $slide_id
 */
function borobill_delete_hero_slide( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	$registry         = borobill_get_hero_slide_registry();
	$registry['order'] = array_values(
		array_filter(
			$registry['order'],
			function ( $id ) use ( $slide_id ) {
				return (int) $id !== $slide_id;
			}
		)
	);
	borobill_save_hero_slide_registry( $registry );

	$fields = array( 'image', 'badge', 'title', 'bg_color', 'grad_bottom', 'image_width', 'button_text', 'button_url', 'button_color', 'button_text_color', 'button_opacity', 'button_enabled', 'status' );
	foreach ( $fields as $field ) {
		delete_option( 'borobill_hero_' . $field . '_' . $slide_id );
	}
	delete_option( 'borobill_hero_updated_at_' . $slide_id );
}

/**
 * 새 슬라이드 저장 직전 레지스트리 등록 (Settings API 등록 전)
 */
function borobill_prepare_new_hero_slide_before_save() {
	if ( ! is_admin() || empty( $_POST['option_page'] ) ) {
		return;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );
	if ( borobill_get_theme_hero_slides_settings_group() !== $option_page ) {
		return;
	}

	if ( empty( $_POST['borobill_hero_is_new'] ) || '1' !== (string) wp_unslash( $_POST['borobill_hero_is_new'] ) ) {
		return;
	}

	$slide_id = isset( $_POST['borobill_hero_active_slide'] ) ? (int) $_POST['borobill_hero_active_slide'] : 0;
	if ( $slide_id < 1 ) {
		return;
	}

	borobill_ensure_hero_slide_in_registry( $slide_id );
}
add_action( 'admin_init', 'borobill_prepare_new_hero_slide_before_save', 9 );

/**
 * @param int $slide_id
 * @return string published|paused
 */
function borobill_get_hero_slide_status( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return 'published';
	}
	$status = get_option( 'borobill_hero_status_' . $slide_id, 'published' );
	return in_array( $status, array( 'published', 'paused' ), true ) ? $status : 'published';
}

/**
 * @return int[]
 */
function borobill_get_hero_slide_order_ids() {
	$registry = borobill_get_hero_slide_registry();
	return array_values( array_map( 'intval', $registry['order'] ) );
}

/**
 * @param int $slide_id
 * @return string
 */
function borobill_get_hero_slide_list_title( $slide_id ) {
	$slide_id  = (int) $slide_id;
	$title_raw = (string) borobill_get_hero_slide_stored_option( 'borobill_hero_title_' . $slide_id, '' );
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
function borobill_get_hero_slide_updated_label( $slide_id ) {
	$updated = get_option( 'borobill_hero_updated_at_' . (int) $slide_id, '' );
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
function borobill_get_hero_slide_updated_timestamp( $slide_id ) {
	$updated = get_option( 'borobill_hero_updated_at_' . (int) $slide_id, '' );
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
function borobill_get_hero_slide_admin_list_ids() {
	$list_ids = borobill_get_hero_slide_order_ids();

	usort(
		$list_ids,
		function ( $a, $b ) {
			$time_a = borobill_get_hero_slide_updated_timestamp( $a );
			$time_b = borobill_get_hero_slide_updated_timestamp( $b );
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
function borobill_touch_hero_slide_updated_at( $slide_id ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}
	update_option( 'borobill_hero_updated_at_' . $slide_id, current_time( 'mysql' ) );
}

/**
 * 히어로 슬라이드 옵션명인지 확인 (options.php 부분 저장 보호용)
 *
 * @param string $option
 * @return bool
 */
function borobill_is_theme_hero_slide_option( $option ) {
	if ( ! is_string( $option ) ) {
		return false;
	}

	$shared = array(
		'borobill_hero_order',
		'borobill_hero_grad_bottom',
		'borobill_hero_bg_color',
	);

	if ( in_array( $option, $shared, true ) ) {
		return true;
	}

	return (bool) preg_match( '/^borobill_hero_(image|badge|title|bg_color|grad_bottom|image_width|button_text|button_url|button_color|button_text_color|button_opacity|button_enabled|status)_\d+$/', $option );
}

/**
 * 슬라이드별 저장 폼에서 POST되지 않은 옵션은 기존 값 유지
 *
 * @param mixed  $value
 * @param string $option
 * @param mixed  $old_value
 * @return mixed
 */
function borobill_preserve_unposted_hero_slide_options( $value, $option, $old_value ) {
	if ( ! is_admin() || empty( $_POST['option_page'] ) ) {
		return $value;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );
	if ( borobill_get_theme_hero_slides_settings_group() !== $option_page ) {
		return $value;
	}

	if ( ! borobill_is_theme_hero_slide_option( $option ) ) {
		return $value;
	}

	if ( ! array_key_exists( $option, $_POST ) ) {
		return $old_value;
	}

	return $value;
}
add_filter( 'pre_update_option', 'borobill_preserve_unposted_hero_slide_options', 10, 3 );

/**
 * 빈 문자열로 저장된 히어로 옵션은 기본값으로 처리
 *
 * @param string $option_name
 * @param mixed  $default
 * @return mixed
 */
function borobill_get_hero_slide_stored_option( $option_name, $default = '' ) {
	$value = get_option( $option_name, null );
	if ( null === $value ) {
		return $default;
	}
	if ( is_string( $value ) && '' === trim( $value ) ) {
		return $default;
	}
	return $value;
}

/**
 * 슬라이드 2·3 데이터가 빈 값으로 덮인 경우 옵션 삭제 후 기본값 복구
 */
function borobill_repair_wiped_hero_slide_storage() {
	if ( get_option( 'borobill_hero_storage_repair_v1' ) ) {
		return;
	}

	$fields = array( 'image', 'badge', 'title', 'bg_color', 'grad_bottom', 'image_width', 'button_text', 'button_url', 'button_color', 'button_text_color', 'button_opacity', 'button_enabled' );
	foreach ( array( 2, 3 ) as $slide_id ) {
		$image = (string) get_option( 'borobill_hero_image_' . $slide_id, '' );
		$title = (string) get_option( 'borobill_hero_title_' . $slide_id, '' );
		if ( '' !== trim( $image ) || '' !== trim( $title ) ) {
			continue;
		}

		foreach ( $fields as $field ) {
			delete_option( 'borobill_hero_' . $field . '_' . $slide_id );
		}
	}

	update_option( 'borobill_hero_storage_repair_v1', '1', false );
}
add_action( 'admin_init', 'borobill_repair_wiped_hero_slide_storage', 5 );

function borobill_register_hero_slide_status_settings() {
	$slide_ids = borobill_get_hero_slide_order_ids();
	foreach ( $slide_ids as $slide_id ) {
		$slide_id = (int) $slide_id;
		register_setting(
			borobill_get_theme_hero_slides_settings_group(),
			'borobill_hero_status_' . $slide_id,
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $value ) use ( $slide_id ) {
					$status = in_array( $value, array( 'published', 'paused' ), true ) ? $value : 'published';
					borobill_touch_hero_slide_updated_at( $slide_id );
					return $status;
				},
				'default'           => 'published',
			)
		);
	}
}
add_action( 'admin_init', 'borobill_register_hero_slide_status_settings', 11 );

function borobill_register_hero_slide_updated_hooks() {
	static $registered = array();
	$fields    = array( 'title', 'badge', 'image', 'bg_color', 'grad_bottom', 'image_width', 'button_text', 'button_url', 'button_color', 'button_text_color', 'button_opacity', 'button_enabled' );
	$slide_ids = borobill_get_hero_slide_order_ids();
	foreach ( $slide_ids as $slide_id ) {
		$slide_id = (int) $slide_id;
		foreach ( $fields as $field ) {
			$hook = 'update_option_borobill_hero_' . $field . '_' . $slide_id;
			if ( isset( $registered[ $hook ] ) ) {
				continue;
			}
			$registered[ $hook ] = true;
			add_action(
				$hook,
				function () use ( $slide_id ) {
					borobill_touch_hero_slide_updated_at( $slide_id );
				}
			);
		}
	}
}
add_action( 'admin_init', 'borobill_register_hero_slide_updated_hooks' );

/**
 * 프론트: 게시 상태 슬라이드 ID만 반환
 *
 * @return int[]
 */
function borobill_get_published_hero_slide_order_ids() {
	$order_ids = borobill_get_hero_slide_order_ids();
	return array_values(
		array_filter(
			$order_ids,
			function ( $id ) {
				return 'published' === borobill_get_hero_slide_status( (int) $id );
			}
		)
	);
}

/**
 * @param int         $slide_id
 * @param string      $default_base
 * @param string      $default_bg
 * @param string      $grad_bottom
 * @param int         $image_width_px
 * @param bool        $is_active
 * @param string      $hero_order
 * @param bool        $is_draft
 */
function borobill_render_hero_slide_editor_card( $slide_id, $default_base, $default_bg, $grad_bottom, $image_width_px, $is_active = false, $hero_order = '1,2,3', $is_draft = false ) {
	$slide_id = (int) $slide_id;
	if ( $slide_id < 1 ) {
		return;
	}

	$image_width_px = $is_draft
		? max( 200, min( 600, absint( $image_width_px ? $image_width_px : get_option( 'borobill_hero_image_width', 380 ) ) ) )
		: ( function_exists( 'borobill_get_hero_slide_image_width' )
			? borobill_get_hero_slide_image_width( $slide_id )
			: max( 200, min( 600, absint( get_option( 'borobill_hero_image_width', 380 ) ) ) ) );

	$default_images = array(
		1 => 'main.png',
		2 => '17.png',
		3 => '18.png',
	);

	$image_default  = isset( $default_images[ $slide_id ] ) ? $default_base . $default_images[ $slide_id ] : '';
	$title_default  = isset( $default_images[ $slide_id ] ) ? "세무·비즈니스 실무 가이드\n2025년 총정리" : '';
	$badge_default  = isset( $default_images[ $slide_id ] ) ? '사장님 필독! 전자세금계산서 처음 시작하기' : '';
	$btn_default    = isset( $default_images[ $slide_id ] ) ? '게시글 바로가기' : '';
	$bg_default     = isset( $default_images[ $slide_id ] ) ? $default_bg : '';
	$grad_default   = get_option( 'borobill_hero_grad_bottom', '#0c2041' );

	if ( $is_draft ) {
		$img         = '';
		$badge       = '';
		$title       = '';
		$btn_text    = '';
		$btn_enabled = 0;
		$bg_color    = '';
		$btn_url     = '';
		$status      = 'paused';
		$grad_bottom = '';
	} else {
		$img            = borobill_get_hero_slide_stored_option( 'borobill_hero_image_' . $slide_id, $image_default );
		$badge          = borobill_get_hero_slide_stored_option( 'borobill_hero_badge_' . $slide_id, $badge_default );
		$title          = borobill_get_hero_slide_stored_option( 'borobill_hero_title_' . $slide_id, $title_default );
		$btn_text       = borobill_get_hero_slide_stored_option( 'borobill_hero_button_text_' . $slide_id, $btn_default );
		$btn_enabled    = (int) borobill_get_hero_slide_stored_option( 'borobill_hero_button_enabled_' . $slide_id, isset( $default_images[ $slide_id ] ) ? 1 : 0 );
		$bg_color       = borobill_get_hero_slide_stored_option( 'borobill_hero_bg_color_' . $slide_id, $bg_default );
		$btn_url        = borobill_get_hero_slide_stored_option( 'borobill_hero_button_url_' . $slide_id, '' );
		$status         = borobill_get_hero_slide_status( $slide_id );
		$grad_bottom    = '' !== trim( (string) $grad_bottom ) ? (string) $grad_bottom : borobill_get_hero_slide_stored_option( 'borobill_hero_grad_bottom_' . $slide_id, $grad_default );
	}
	$grad_on        = '' !== trim( (string) $grad_bottom ) ? 1 : 0;
	$status_label   = 'published' === $status ? '게시' : '정지';
	$list_title     = borobill_get_hero_slide_list_title( $slide_id );
	$preview_image  = '' !== trim( (string) $img ) ? $img : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
	$carousel_order = borobill_get_hero_slide_order_ids();
	$position_index = array_search( $slide_id, $carousel_order, true );
	$slide_position = false === $position_index ? count( $carousel_order ) + 1 : $position_index + 1;
	$max_position   = $is_draft ? count( $carousel_order ) + 1 : max( 1, count( $carousel_order ) );
	?>
	<form
		class="borobill-banner-settings-form borobill-hero-slide-form"
		method="post"
		action="options.php"
		data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"
		<?php echo $is_draft ? ' data-is-draft="1"' : ''; ?>
		<?php echo $is_active ? '' : ' hidden'; ?>
	>
		<?php settings_fields( borobill_get_theme_hero_slides_settings_group() ); ?>
		<input type="hidden" name="borobill_hero_order" class="borobill-hero-order-input" value="<?php echo esc_attr( $hero_order ); ?>">
		<input type="hidden" name="borobill_hero_active_slide" class="borobill-hero-active-slide-input" value="<?php echo esc_attr( (string) $slide_id ); ?>">
		<?php if ( $is_draft ) : ?>
			<input type="hidden" name="borobill_hero_is_new" value="1">
		<?php endif; ?>

	<div class="borobill-hero-slide-card<?php echo $is_active ? ' is-active' : ''; ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"<?php echo $is_active ? '' : ' hidden'; ?>>
		<div class="borobill-hero-slide-status" data-hero-status-bar>
			<div class="borobill-hero-slide-status__meta">
				<span class="borobill-hero-slide-status__label">상태</span>
				<span class="borobill-hero-slide-status__badge is-<?php echo esc_attr( $status ); ?>" data-hero-status-badge><?php echo esc_html( $status_label ); ?></span>
			</div>
			<div class="borobill-hero-slide-status__actions">
				<button type="button" class="button button-primary borobill-hero-status-publish" data-hero-status-action="published" <?php disabled( 'published' === $status ); ?>>게시</button>
				<button type="button" class="button borobill-hero-status-pause" data-hero-status-action="paused" <?php disabled( 'paused' === $status ); ?>>정지</button>
			</div>
			<input type="hidden" id="borobill_hero_status_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_status_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $status ); ?>" data-hero-status-input>
		</div>

		<h2 class="borobill-hero-slide-card__title"><?php echo esc_html( $list_title ); ?></h2>

		<div class="bb-field">
			<div class="borobill-hero-admin-preview" data-bb-hero-preview="<?php echo esc_attr( (string) $slide_id ); ?>" style="--bb-hero-bg: <?php echo esc_attr( $bg_color ); ?>; --bb-hero-grad: <?php echo esc_attr( $grad_on ? $grad_bottom : 'rgba(0,0,0,0)' ); ?>; --bb-hero-grad-opacity: <?php echo esc_attr( (string) $grad_on ); ?>;">
				<div class="borobill-hero-admin-preview__bg"></div>
				<div class="borobill-hero-admin-preview__content">
					<div class="borobill-hero-admin-preview__badge" data-bb-hero-preview-badge><?php echo esc_html( $badge ); ?></div>
					<div class="borobill-hero-admin-preview__title" data-bb-hero-preview-title><?php echo nl2br( esc_html( $title ) ); ?></div>
					<button type="button" class="borobill-hero-admin-preview__btn" data-bb-hero-preview-btn <?php echo 1 === $btn_enabled ? '' : 'hidden'; ?>>
						<span data-bb-hero-preview-btn-text><?php echo esc_html( $btn_text ); ?></span>
					</button>
				</div>
				<img id="borobill_hero_image_<?php echo esc_attr( (string) $slide_id ); ?>_preview" class="borobill-hero-admin-preview__illust" src="<?php echo esc_url( $preview_image ); ?>" alt="">
			</div>
			<input type="hidden" class="borobill-image-input" id="borobill_hero_image_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_image_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $img ); ?>">
			<div class="bb-actions">
				<button type="button" class="button borobill-image-select" data-target-input="#borobill_hero_image_<?php echo esc_attr( (string) $slide_id ); ?>" data-target-preview="#borobill_hero_image_<?php echo esc_attr( (string) $slide_id ); ?>_preview">이미지 불러오기</button>
			</div>
		</div>

		<?php
		$btn_color = $is_draft ? '' : borobill_get_hero_button_color_for_editor( $slide_id );
		$btn_text_color_raw = get_option( 'borobill_hero_button_text_color_' . $slide_id, '#ffffff' );
		$btn_text_color     = is_string( $btn_text_color_raw ) ? trim( $btn_text_color_raw ) : '#ffffff';
		if ( '' === $btn_text_color ) {
			$btn_text_color = '#ffffff';
		}
		if ( $is_draft ) {
			$btn_text_color = '#ffffff';
		}
		?>
		<div class="bb-field">
			<div class="borobill-color-pair">
				<div>
					<label for="borobill_hero_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>배경 색상</strong></label>
					<input type="text" class="regular-text borobill-color-field" id="borobill_hero_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_bg_color_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $bg_color ); ?>">
				</div>
				<div>
					<label for="borobill_hero_grad_bottom_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>그라데이션 하단 색</strong></label>
					<input type="text" class="regular-text borobill-color-field" id="borobill_hero_grad_bottom_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_grad_bottom_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $grad_bottom ); ?>">
				</div>
				<div class="borobill-hero-image-width-field">
					<label for="borobill_hero_image_width_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>이미지 크기</strong></label>
					<div class="borobill-hero-image-width-control">
						<input type="number" min="200" max="600" step="10" id="borobill_hero_image_width_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_image_width_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( (string) $image_width_px ); ?>">
						<span class="borobill-hero-image-width-unit">px</span>
					</div>
				</div>
			</div>
		</div>

		<div class="bb-field">
			<div class="borobill-color-pair borobill-hero-row-2">
				<div>
					<label for="borobill_hero_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 색상</strong></label>
					<input
						type="text"
						class="regular-text borobill-color-field borobill-color-field--alpha"
						id="borobill_hero_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"
						name="borobill_hero_button_color_<?php echo esc_attr( (string) $slide_id ); ?>"
						value="<?php echo esc_attr( $btn_color ); ?>"
						data-default-color=""
						data-alpha-enabled="true"
						data-alpha-color-type="rgba"
					>
					<p class="description">비워두면 배경색 기준으로 자동 지정됩니다. 색상표에서 투명도도 조절할 수 있습니다.</p>
				</div>
				<div>
					<label for="borobill_hero_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 글자색</strong></label>
					<input type="text" class="regular-text borobill-color-field" id="borobill_hero_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_button_text_color_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $btn_text_color ); ?>" data-default-color="#ffffff">
				</div>
				<div class="borobill-hero-slide-order-field">
					<label for="borobill_hero_slide_order_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>배너 순서</strong></label>
					<div class="borobill-hero-slide-order-control">
						<input type="number" min="1" max="<?php echo esc_attr( (string) $max_position ); ?>" step="1" id="borobill_hero_slide_order_<?php echo esc_attr( (string) $slide_id ); ?>" class="borobill-hero-slide-order-input" value="<?php echo esc_attr( (string) $slide_position ); ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>">
					</div>
				</div>
			</div>
		</div>

		<div class="bb-field">
			<label for="borobill_hero_badge_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>배지 텍스트</strong></label>
			<input type="text" class="regular-text" id="borobill_hero_badge_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_badge_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $badge ); ?>">
		</div>

		<div class="bb-field">
			<label for="borobill_hero_title_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>메인 타이틀</strong></label>
			<textarea id="borobill_hero_title_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_title_<?php echo esc_attr( (string) $slide_id ); ?>" rows="3"><?php echo esc_textarea( $title ); ?></textarea>
		</div>

		<div class="bb-field bb-stack">
			<div>
				<label for="borobill_hero_button_text_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 텍스트</strong></label>
				<input type="text" class="regular-text" id="borobill_hero_button_text_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_button_text_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $btn_text ); ?>">
			</div>
			<div>
				<label for="borobill_hero_button_url_<?php echo esc_attr( (string) $slide_id ); ?>"><strong>버튼 링크(URL)</strong></label>
				<input type="url" class="regular-text" id="borobill_hero_button_url_<?php echo esc_attr( (string) $slide_id ); ?>" name="borobill_hero_button_url_<?php echo esc_attr( (string) $slide_id ); ?>" value="<?php echo esc_attr( $btn_url ); ?>" placeholder="https://example.com">
			</div>
			<div>
				<input type="hidden" name="borobill_hero_button_enabled_<?php echo esc_attr( (string) $slide_id ); ?>" value="0">
				<label>
					<input type="checkbox" name="borobill_hero_button_enabled_<?php echo esc_attr( (string) $slide_id ); ?>" value="1" <?php checked( $btn_enabled, 1 ); ?>>
					버튼 사용
				</label>
			</div>
		</div>

		<p class="borobill-banner-settings-form__actions borobill-hero-slide-form__actions">
			<?php submit_button( '저장', 'primary', 'submit', false ); ?>
			<button type="button" class="button borobill-hero-slide-delete">삭제</button>
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
function borobill_render_hero_slide_list_item( $slide_id, $index, $is_active = false, $is_draft = false ) {
	$slide_id     = (int) $slide_id;
	$status       = $is_draft ? 'paused' : borobill_get_hero_slide_status( $slide_id );
	$list_title   = $is_draft ? '새 슬라이드' : borobill_get_hero_slide_list_title( $slide_id );
	$updated      = $is_draft ? '' : borobill_get_hero_slide_updated_label( $slide_id );
	$status_label = 'published' === $status ? '게시' : '정지';
	?>
	<li class="borobill-hero-manager__item<?php echo $is_active ? ' is-active' : ''; ?>" data-slide-id="<?php echo esc_attr( (string) $slide_id ); ?>"<?php echo $is_draft ? ' data-is-draft="1"' : ''; ?>>
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

/**
 * @param string $default_base
 * @param string $default_bg
 * @param int    $image_width_px
 */
function borobill_render_hero_slides_manager( $default_base, $default_bg, $image_width_px ) {
	$order_ids    = borobill_get_hero_slide_order_ids();
	$list_ids     = borobill_get_hero_slide_admin_list_ids();
	$hero_order   = implode( ',', array_map( 'strval', $order_ids ) );
	$active_slide = isset( $_GET['slide'] ) ? (int) $_GET['slide'] : 0;
	if ( ! in_array( $active_slide, $order_ids, true ) ) {
		$active_slide = ! empty( $list_ids ) ? (int) $list_ids[0] : 0;
	}
	?>
	<div class="borobill-hero-manager" data-active-slide="<?php echo esc_attr( (string) $active_slide ); ?>">
		<aside class="borobill-hero-manager__list-panel">
			<div class="borobill-hero-manager__list-head">
				<h2>히어로 슬라이드</h2>
				<button type="button" class="button button-primary" id="borobill-hero-create">작성하기</button>
			</div>
			<ul class="borobill-hero-manager__list" id="borobill-hero-slides-list">
				<?php foreach ( $list_ids as $index => $slide_id ) : ?>
					<?php borobill_render_hero_slide_list_item( (int) $slide_id, (int) $index, $active_slide === (int) $slide_id ); ?>
				<?php endforeach; ?>
			</ul>
		</aside>

		<div class="borobill-hero-manager__editor-panel">
			<div class="borobill-hero-manager__editor-empty" id="borobill-hero-editor-empty"<?php echo $active_slide ? ' hidden' : ''; ?>>
				<p>왼쪽 목록에서 슬라이드를 선택하거나 <strong>작성하기</strong>를 눌러 편집을 시작하세요.</p>
			</div>
			<div id="borobill-hero-slides" class="borobill-hero-manager__editor-cards">
				<?php
				foreach ( $order_ids as $slide_id ) {
					$slide_id     = (int) $slide_id;
					$grad_bottom  = borobill_get_hero_slide_stored_option(
						'borobill_hero_grad_bottom_' . $slide_id,
						get_option( 'borobill_hero_grad_bottom', '#0c2041' )
					);
					borobill_render_hero_slide_editor_card(
						$slide_id,
						$default_base,
						$default_bg,
						(string) $grad_bottom,
						$image_width_px,
						$active_slide === $slide_id,
						$hero_order
					);
				}
				?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * 배너설정 저장 후 리다이렉트 (선택 슬라이드 유지 + 저장 구역 표시)
 *
 * @param string $location
 * @return string
 */
function borobill_banner_settings_save_redirect( $location ) {
	if ( ! is_admin() || ! isset( $_POST['option_page'] ) ) {
		return $location;
	}

	$option_page = sanitize_key( wp_unslash( $_POST['option_page'] ) );
	$allowed     = array(
		borobill_get_theme_hero_slides_settings_group(),
		borobill_get_theme_hero_transition_settings_group(),
		borobill_get_theme_bottom_banner_settings_group(),
		borobill_get_theme_bottom_banner_transition_settings_group(),
	);

	if ( ! in_array( $option_page, $allowed, true ) ) {
		return $location;
	}

	$settings_url = admin_url( 'admin.php?page=' . borobill_get_banner_settings_menu_slug() );

	// AJAX로 만든 폼의 referer가 admin-ajax.php면 흰 화면(0)으로 떨어짐 → 설정 페이지로 교정
	if ( ! is_string( $location ) || '' === $location || false !== stripos( $location, 'admin-ajax.php' ) ) {
		$location = $settings_url;
	}

	$location = add_query_arg( 'settings-updated', 'true', $location );
	$location = add_query_arg( 'saved', $option_page, $location );

	$is_bottom = in_array(
		$option_page,
		array(
			borobill_get_theme_bottom_banner_settings_group(),
			borobill_get_theme_bottom_banner_transition_settings_group(),
		),
		true
	);

	if ( $is_bottom ) {
		$location = add_query_arg( 'tab', 'bottom', $location );
	} else {
		$location = add_query_arg( 'tab', 'top', $location );
	}

	if ( borobill_get_theme_hero_slides_settings_group() === $option_page && isset( $_POST['borobill_hero_active_slide'] ) ) {
		$slide = (int) $_POST['borobill_hero_active_slide'];
		if ( $slide > 0 ) {
			$location = add_query_arg( 'slide', $slide, $location );
		}
	}

	return $location;
}
add_filter( 'wp_redirect', 'borobill_banner_settings_save_redirect' );

/**
 * 작성하기: 새 슬라이드 UI만 추가 (DB 저장은 [저장] 클릭 시)
 */
function borobill_ajax_create_hero_slide() {
	check_ajax_referer( 'borobill_hero_manager', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => '권한이 없습니다.' ), 403 );
	}

	$default_base    = get_template_directory_uri() . '/images/';
	$default_bg      = '#4f7fcb';
	$image_width_px  = (int) get_option( 'borobill_hero_image_width', 450 );
	$new_id          = borobill_peek_next_hero_slide_id();
	$order_ids       = borobill_get_hero_slide_order_ids();
	$draft_order     = array_merge( $order_ids, array( $new_id ) );
	$hero_order      = implode( ',', array_map( 'strval', $draft_order ) );

	// AJAX 렌더 시 referer가 admin-ajax.php로 잡히지 않게
	$_REQUEST['_wp_http_referer'] = admin_url( 'admin.php?page=' . borobill_get_banner_settings_menu_slug() . '&tab=top' );

	ob_start();
	borobill_render_hero_slide_list_item( $new_id, count( $order_ids ), false, true );
	$list_item_html = ob_get_clean();

	ob_start();
	borobill_render_hero_slide_editor_card(
		$new_id,
		$default_base,
		$default_bg,
		'',
		$image_width_px,
		true,
		$hero_order,
		true
	);
	$editor_html = ob_get_clean();

	wp_send_json_success(
		array(
			'slide_id'       => $new_id,
			'list_item_html' => $list_item_html,
			'editor_html'    => $editor_html,
			'hero_order'     => $hero_order,
		)
	);
}
add_action( 'wp_ajax_borobill_create_hero_slide', 'borobill_ajax_create_hero_slide' );

/**
 * 슬라이드 삭제
 */
function borobill_ajax_delete_hero_slide() {
	check_ajax_referer( 'borobill_hero_manager', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => '권한이 없습니다.' ), 403 );
	}

	$slide_id = isset( $_POST['slide_id'] ) ? (int) $_POST['slide_id'] : 0;
	if ( $slide_id < 1 ) {
		wp_send_json_error( array( 'message' => '잘못된 슬라이드입니다.' ), 400 );
	}

	borobill_delete_hero_slide( $slide_id );

	wp_send_json_success(
		array(
			'slide_id' => $slide_id,
		)
	);
}
add_action( 'wp_ajax_borobill_delete_hero_slide', 'borobill_ajax_delete_hero_slide' );

/**
 * 슬라이드 순서 저장 시 레지스트리 동기화
 *
 * @param mixed $value
 * @return mixed
 */
function borobill_sync_hero_slide_registry_from_order_option( $value ) {
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

	$registry = borobill_get_hero_slide_registry();
	if ( $registry['order'] === $order_ids ) {
		return $value;
	}

	$registry['order'] = $order_ids;
	if ( $registry['next_id'] <= max( $order_ids ) ) {
		$registry['next_id'] = max( $order_ids ) + 1;
	}
	update_option( 'borobill_hero_slide_registry', $registry, false );

	return $value;
}
add_filter( 'pre_update_option_borobill_hero_order', 'borobill_sync_hero_slide_registry_from_order_option' );

/**
 * 배너설정 저장 완료 안내
 */
function borobill_render_banner_settings_admin_notices() {
	if ( ! isset( $_GET['page'] ) || 'borobill-theme-options' !== $_GET['page'] ) {
		return;
	}
	if ( ! isset( $_GET['settings-updated'] ) || ! $_GET['settings-updated'] ) {
		return;
	}

	$messages = array(
		borobill_get_theme_hero_slides_settings_group()              => '히어로 슬라이드 설정이 저장되었습니다.',
		borobill_get_theme_hero_transition_settings_group()          => '슬라이드 전환 옵션이 저장되었습니다.',
		borobill_get_theme_bottom_banner_settings_group()            => '하단 배너 설정이 저장되었습니다.',
		borobill_get_theme_bottom_banner_transition_settings_group() => '하단 배너 전환 옵션이 저장되었습니다.',
	);

	$saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : '';
	$text  = isset( $messages[ $saved ] ) ? $messages[ $saved ] : '설정이 저장되었습니다.';

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html( $text )
	);
}
