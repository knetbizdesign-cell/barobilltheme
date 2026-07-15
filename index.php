<?php get_header(); ?>

<?php
// 메인 슬라이더(히어로 이미지) 옵션값 불러오기
$default_base = get_template_directory_uri() . '/images/';

// 슬라이드별 기본 이미지
$hero_default_images = array(
    1 => 'main.png',
    2 => '17.png',
    3 => '18.png',
);

// 슬라이드 데이터 구성: 이미지 / 배지 / 타이틀 / 배경 색상 / 그라데이션 하단 색
$hero_slides = array();
$all_slide_ids = function_exists( 'borobill_get_hero_slide_order_ids' )
	? borobill_get_hero_slide_order_ids()
	: array( 1, 2, 3 );

foreach ( $all_slide_ids as $i ) {
	$i = (int) $i;
	$image_default = isset( $hero_default_images[ $i ] ) ? $default_base . $hero_default_images[ $i ] : '';
	$title_default = isset( $hero_default_images[ $i ] ) ? "세무·비즈니스 실무 가이드\n2025년 총정리" : '';
	$badge_default = isset( $hero_default_images[ $i ] ) ? '사장님 필독! 전자세금계산서 처음 시작하기' : '';
	$btn_default   = isset( $hero_default_images[ $i ] ) ? '게시글 바로가기' : '';

    $image      = borobill_get_hero_slide_stored_option( 'borobill_hero_image_' . $i, $image_default );
    $badge      = borobill_get_hero_slide_stored_option( 'borobill_hero_badge_' . $i, $badge_default );
    $title_raw  = borobill_get_hero_slide_stored_option( 'borobill_hero_title_' . $i, $title_default );
    $title_html = nl2br( esc_html( $title_raw ) );
    $bg_color   = borobill_get_hero_slide_stored_option( 'borobill_hero_bg_color_' . $i, '#4f7fcb' );
    if ( '' === trim( (string) $bg_color ) ) {
        $bg_color = '#4f7fcb';
    }
    $btn_text   = borobill_get_hero_slide_stored_option( 'borobill_hero_button_text_' . $i, $btn_default );
    $btn_enabled = (int) borobill_get_hero_slide_stored_option( 'borobill_hero_button_enabled_' . $i, isset( $hero_default_images[ $i ] ) ? 1 : 0 );
    $btn_url    = borobill_get_hero_slide_stored_option( 'borobill_hero_button_url_' . $i, '' );
    $btn_color = function_exists( 'borobill_get_hero_button_color_css' )
        ? borobill_get_hero_button_color_css( $i )
        : '';
    $btn_text_color_raw = get_option( 'borobill_hero_button_text_color_' . $i, '#ffffff' );
    $btn_text_color = is_string( $btn_text_color_raw ) ? sanitize_hex_color( trim( $btn_text_color_raw ) ) : '';
    if ( ! is_string( $btn_text_color ) || '' === $btn_text_color ) {
        $btn_text_color = '#ffffff';
    }
    $grad_bottom = borobill_get_hero_slide_stored_option(
        'borobill_hero_grad_bottom_' . $i,
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );
    $grad_enabled = '' !== trim( (string) $grad_bottom ) ? 1 : 0;
    $image_width  = function_exists( 'borobill_get_hero_slide_image_width' )
        ? borobill_get_hero_slide_image_width( $i )
        : (int) get_option( 'borobill_hero_image_width', 380 );

    $hero_slides[ $i ] = array(
        'image'      => $image,
        'badge'      => $badge,
        'title_html' => $title_html,
        'bg_color'   => $bg_color,
        'btn_text'   => $btn_text,
        'btn_enabled'=> $btn_enabled,
        'btn_url'    => $btn_url,
        'btn_color'  => $btn_color,
        'btn_text_color' => $btn_text_color,
        'grad_bottom'=> $grad_bottom,
        'grad_enabled' => $grad_enabled,
        'image_width'=> $image_width,
    );
}

// 슬라이드 순서 + 게시 상태(정지 슬라이드 제외)
$order_ids = function_exists( 'borobill_get_published_hero_slide_order_ids' )
	? borobill_get_published_hero_slide_order_ids()
	: array( 1, 2, 3 );

// 실제 출력되는 슬라이드 수(이미지 URL이 있는 항목만) — 모바일 슬라이드 번호 배지용
$hero_rendered_count = 0;
foreach ( $order_ids as $oid ) {
	if ( ! empty( $hero_slides[ $oid ]['image'] ) ) {
		$hero_rendered_count++;
	}
}

if ( ! empty( $order_ids ) && isset( $hero_slides[ $order_ids[0] ] ) ) :
	$first_id    = $order_ids[0];
	$first_slide = $hero_slides[ $first_id ];
	$image_width_px = isset( $first_slide['image_width'] )
		? (int) $first_slide['image_width']
		: (int) get_option( 'borobill_hero_image_width', 380 );
?>

<section class="hero-section">
    <div class="hero-wrapper" style="--hero-bg-color: <?php echo esc_attr( $first_slide['bg_color'] ); ?>; --hero-image-width: <?php echo esc_attr( $image_width_px ); ?>px; --hero-grad-bottom: <?php echo esc_attr( (int) $first_slide['grad_enabled'] === 1 ? $first_slide['grad_bottom'] : 'rgba(0,0,0,0)' ); ?>; --hero-grad-opacity: <?php echo esc_attr( (int) $first_slide['grad_enabled'] ); ?>; --hero-btn-color: <?php echo esc_attr( $first_slide['btn_text_color'] ); ?>;">
        <div class="hero-bg"></div>

        <!-- 메인 슬라이더: 최대 3장의 히어로 이미지 -->
        <div class="hero-carousel">
            <?php foreach ( $order_ids as $i ) :
                if ( ! isset( $hero_slides[ $i ] ) ) {
                    continue;
                }
                $slide     = $hero_slides[ $i ];
                $image_url = $slide['image'];
                if ( empty( $image_url ) ) {
                    continue;
                }

                $is_active = ( $first_id === $i ) ? ' is-active' : '';
                ?>
                <div class="hero-slide<?php echo esc_attr( $is_active ); ?>"
                     style="--hero-image-width: <?php echo esc_attr( (string) (int) $slide['image_width'] ); ?>px;"
                     data-slide-id="<?php echo esc_attr( (string) $i ); ?>"
                     data-banner-key="<?php echo esc_attr( 'hero_' . $i ); ?>"
                     data-badge="<?php echo esc_attr( $slide['badge'] ); ?>"
                     data-title-html="<?php echo esc_attr( $slide['title_html'] ); ?>"
                     data-bg-color="<?php echo esc_attr( $slide['bg_color'] ); ?>"
                     data-btn-text="<?php echo esc_attr( $slide['btn_text'] ); ?>"
                     data-btn-enabled="<?php echo esc_attr( (int) $slide['btn_enabled'] ); ?>"
                     data-btn-url="<?php echo esc_attr( $slide['btn_url'] ); ?>"
                     data-btn-color="<?php echo esc_attr( $slide['btn_color'] ); ?>"
                     data-btn-text-color="<?php echo esc_attr( $slide['btn_text_color'] ); ?>"
                     data-grad-bottom="<?php echo esc_attr( $slide['grad_bottom'] ); ?>"
                     data-grad-enabled="<?php echo esc_attr( (int) $slide['grad_enabled'] ); ?>"
                     data-image-width="<?php echo esc_attr( (string) (int) $slide['image_width'] ); ?>">
                    <img src="<?php echo esc_url( $image_url ); ?>"
                         class="hero-illust"
                         alt="<?php echo esc_attr( '바로빌 히어로 이미지 ' . $i ); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ( $hero_rendered_count >= 2 ) : ?>
            <div class="hero-carousel-pagination" aria-label="<?php esc_attr_e( '히어로 슬라이드 탐색', 'borobill_theme' ); ?>">
                <button type="button"
                        class="hero-carousel-pagination__btn hero-carousel-pagination__btn--prev"
                        data-hero-pagination-prev
                        aria-label="<?php esc_attr_e( '이전 슬라이드', 'borobill_theme' ); ?>">
                    <span aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
                <div class="hero-carousel-pagination__counter"
                     aria-live="polite"
                     aria-atomic="true"
                     aria-label="<?php echo esc_attr( sprintf( '슬라이드 %1$d / %2$d', 1, (int) $hero_rendered_count ) ); ?>">
                    <span class="hero-carousel-pagination__current">1</span>
                    <span class="hero-carousel-pagination__sep" aria-hidden="true">|</span>
                    <span class="hero-carousel-pagination__total"><?php echo (int) $hero_rendered_count; ?></span>
                </div>
                <button type="button"
                        class="hero-carousel-pagination__btn hero-carousel-pagination__btn--next"
                        data-hero-pagination-next
                        aria-label="<?php esc_attr_e( '다음 슬라이드', 'borobill_theme' ); ?>">
                    <span aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M10 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
            </div>
        <?php endif; ?>

        <div class="hero-content">
            <div class="hero-badge"><?php echo esc_html( $first_slide['badge'] ); ?></div>

            <h1 class="hero-title"><?php echo $first_slide['title_html']; ?></h1>

            <div class="hero-actions" aria-label="히어로 캐러셀 컨트롤">
                <button class="hero-next-btn<?php echo (int) $first_slide['btn_enabled'] === 1 ? '' : ' is-hidden'; ?>"
                        type="button"
                        data-hero-next
                        <?php echo (int) $first_slide['btn_enabled'] === 1 ? '' : 'aria-hidden="true" tabindex="-1"'; ?>>
                    <span class="hero-next-btn__label"><?php echo esc_html( $first_slide['btn_text'] ); ?></span>
                    <span class="hero-next-btn__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="layout">

    <?php get_template_part('template-parts/recommended'); ?>
    <?php
    $index_filter_items     = array();
    $index_gnb_badge_labels = array();

    foreach ( borobill_get_header_gnb_lnb_groups() as $group ) {
        if ( empty( $group['term_id'] ) ) {
            continue;
        }

        $root_id    = (int) $group['term_id'];
        $root_label = (string) $group['title'];
        $term       = get_term( $root_id, 'category' );
        $slug       = ( $term && ! is_wp_error( $term ) ) ? $term->slug : sanitize_title( $root_label );

        $index_filter_items[] = array(
            'label'   => $root_label,
            'slug'    => $slug,
            'cat_ids' => array( $root_id ),
        );

        $index_gnb_badge_labels[ $root_id ] = $root_label;

        $descendants = get_term_children( $root_id, 'category' );
        if ( is_array( $descendants ) ) {
            foreach ( $descendants as $term_id ) {
                $index_gnb_badge_labels[ (int) $term_id ] = $root_label;
            }
        }
    }

    $index_gnb_root_ids = array_map(
        static function ( $item ) {
            return (int) $item['cat_ids'][0];
        },
        $index_filter_items
    );
    ?>
    <div id="borobill-index-gnb-badge-labels"
         class="screen-reader-text"
         hidden
         data-gnb-badge-labels="<?php echo esc_attr( wp_json_encode( $index_gnb_badge_labels ) ); ?>"
         data-gnb-root-ids="<?php echo esc_attr( wp_json_encode( array_values( $index_gnb_root_ids ) ) ); ?>"></div>
    <?php
    get_template_part(
        'template-parts/category-filter',
        null,
        array(
            'filter_items'   => $index_filter_items,
            'show_view_mode' => true,
        )
    );
    ?>
    <?php get_template_part('template-parts/main-list'); ?>
    <?php get_template_part( 'template-parts/bottom-cta-banner' ); ?>

</div>

<?php get_footer(); ?>
