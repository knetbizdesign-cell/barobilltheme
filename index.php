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
for ( $i = 1; $i <= 3; $i++ ) {
    $image      = get_option( 'borobill_hero_image_' . $i, $default_base . $hero_default_images[ $i ] );
    $badge      = get_option( 'borobill_hero_badge_' . $i, '사장님 필독! 전자세금계산서 처음 시작하기' );
    $title_raw  = get_option( 'borobill_hero_title_' . $i, "세무·비즈니스 실무 가이드\n2025년 총정리" );
    $title_html = nl2br( esc_html( $title_raw ) );
    $bg_color   = get_option( 'borobill_hero_bg_color_' . $i, '#4f7fcb' );
    if ( '' === trim( (string) $bg_color ) ) {
        $bg_color = '#4f7fcb';
    }
    $btn_text   = get_option( 'borobill_hero_button_text_' . $i, '게시글 바로가기' );
    $btn_enabled = (int) get_option( 'borobill_hero_button_enabled_' . $i, 1 );
    $btn_url    = get_option( 'borobill_hero_button_url_' . $i, '' );
    $grad_bottom = get_option(
        'borobill_hero_grad_bottom_' . $i,
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );
    $grad_enabled = '' !== trim( (string) $grad_bottom ) ? 1 : 0;

    $hero_slides[ $i ] = array(
        'image'      => $image,
        'badge'      => $badge,
        'title_html' => $title_html,
        'bg_color'   => $bg_color,
        'btn_text'   => $btn_text,
        'btn_enabled'=> $btn_enabled,
        'btn_url'    => $btn_url,
        'grad_bottom'=> $grad_bottom,
        'grad_enabled' => $grad_enabled,
    );
}

// 슬라이드 순서 (예: "1,2,3")
$order_string = get_option( 'borobill_hero_order', '1,2,3' );
$order_ids    = array_map( 'intval', explode( ',', $order_string ) );
$order_ids    = array_values(
    array_filter(
        $order_ids,
        function ( $id ) {
            return in_array( $id, array( 1, 2, 3 ), true );
        }
    )
);
if ( empty( $order_ids ) ) {
    $order_ids = array( 1, 2, 3 );
}

// 실제 출력되는 슬라이드 수(이미지 URL이 있는 항목만) — 모바일 슬라이드 번호 배지용
$hero_rendered_count = 0;
foreach ( $order_ids as $oid ) {
    if ( ! empty( $hero_slides[ $oid ]['image'] ) ) {
        $hero_rendered_count++;
    }
}

// 첫 슬라이드 데이터 (초기 표시용)
$first_id        = $order_ids[0];
$first_slide     = $hero_slides[ $first_id ];
$image_width_px   = (int) get_option( 'borobill_hero_image_width', 380 );
?>

<section class="hero-section">
    <div class="hero-wrapper" style="--hero-bg-color: <?php echo esc_attr( $first_slide['bg_color'] ); ?>; --hero-image-width: <?php echo esc_attr( $image_width_px ); ?>px; --hero-grad-bottom: <?php echo esc_attr( (int) $first_slide['grad_enabled'] === 1 ? $first_slide['grad_bottom'] : 'rgba(0,0,0,0)' ); ?>; --hero-grad-opacity: <?php echo esc_attr( (int) $first_slide['grad_enabled'] ); ?>;">
        <div class="hero-bg"></div>

        <!-- 메인 슬라이더: 최대 3장의 히어로 이미지 -->
        <div class="hero-carousel">
            <?php foreach ( $order_ids as $i ) :
                $slide     = $hero_slides[ $i ];
                $image_url = $slide['image'];
                if ( empty( $image_url ) ) {
                    continue;
                }

                $is_active = ( $first_id === $i ) ? ' is-active' : '';
                ?>
                <div class="hero-slide<?php echo esc_attr( $is_active ); ?>"
                     data-badge="<?php echo esc_attr( $slide['badge'] ); ?>"
                     data-title-html="<?php echo esc_attr( $slide['title_html'] ); ?>"
                     data-bg-color="<?php echo esc_attr( $slide['bg_color'] ); ?>"
                     data-btn-text="<?php echo esc_attr( $slide['btn_text'] ); ?>"
                     data-btn-enabled="<?php echo esc_attr( (int) $slide['btn_enabled'] ); ?>"
                     data-btn-url="<?php echo esc_attr( $slide['btn_url'] ); ?>"
                     data-grad-bottom="<?php echo esc_attr( $slide['grad_bottom'] ); ?>"
                     data-grad-enabled="<?php echo esc_attr( (int) $slide['grad_enabled'] ); ?>">
                    <img src="<?php echo esc_url( $image_url ); ?>"
                         class="hero-illust"
                         alt="<?php echo esc_attr( '바로빌 히어로 이미지 ' . $i ); ?>">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ( $hero_rendered_count >= 2 ) : ?>
            <div class="hero-slide-counter" aria-live="polite" aria-atomic="true" aria-label="<?php echo esc_attr( sprintf( '슬라이드 %1$d / %2$d', 1, (int) $hero_rendered_count ) ); ?>">
                <span class="hero-slide-counter__current">1</span>
                <span class="hero-slide-counter__sep" aria-hidden="true">|</span>
                <span class="hero-slide-counter__total"><?php echo (int) $hero_rendered_count; ?></span>
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

                <div class="hero-indicators" role="tablist" aria-label="히어로 슬라이드 선택">
                    <?php for ( $dot_i = 0; $dot_i < 3; $dot_i++ ) : ?>
                        <button
                            class="dot<?php echo 0 === $dot_i ? ' is-active' : ''; ?>"
                            type="button"
                            data-index="<?php echo esc_attr( $dot_i ); ?>"
                            aria-label="<?php echo esc_attr( ( $dot_i + 1 ) . '번째 슬라이드' ); ?>"
                            <?php echo 0 === $dot_i ? 'aria-current="true"' : ''; ?>
                        ></button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="layout">

    <?php get_template_part('template-parts/recommended'); ?>
    <?php get_template_part('template-parts/category-filter'); ?>
    <?php get_template_part('template-parts/main-list'); ?>
    <?php get_template_part( 'template-parts/bottom-cta-banner' ); ?>

</div>

<?php get_footer(); ?>
