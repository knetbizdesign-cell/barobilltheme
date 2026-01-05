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
    $grad_bottom = get_option(
        'borobill_hero_grad_bottom_' . $i,
        get_option( 'borobill_hero_grad_bottom', '#0c2041' )
    );

    $hero_slides[ $i ] = array(
        'image'      => $image,
        'badge'      => $badge,
        'title_html' => $title_html,
        'bg_color'   => $bg_color,
        'grad_bottom'=> $grad_bottom,
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

// 첫 슬라이드 데이터 (초기 표시용)
$first_id        = $order_ids[0];
$first_slide     = $hero_slides[ $first_id ];
$image_width_px   = (int) get_option( 'borobill_hero_image_width', 380 );
?>

<section class="hero-section">
    <div class="hero-wrapper" style="--hero-bg-color: <?php echo esc_attr( $first_slide['bg_color'] ); ?>; --hero-image-width: <?php echo esc_attr( $image_width_px ); ?>px; --hero-grad-bottom: <?php echo esc_attr( $first_slide['grad_bottom'] ); ?>;">
        <div class="hero-bg"></div>

        <!-- 메인 슬라이더: 최대 3장의 히어로 이미지 -->
        <div class="hero-carousel">
            <?php foreach ( $order_ids as $i ) :
                $slide     = $hero_slides[ $i ];
                $image_url = $slide['image'];
                if ( empty( $image_url ) ) {
                    continue;
                }

                $is_active = ( 1 === $i ) ? ' is-active' : '';
                ?>
                <div class="hero-slide<?php echo esc_attr( $is_active ); ?>"
                     data-badge="<?php echo esc_attr( $slide['badge'] ); ?>"
                     data-title-html="<?php echo esc_attr( $slide['title_html'] ); ?>"
                     data-bg-color="<?php echo esc_attr( $slide['bg_color'] ); ?>"
                     data-grad-bottom="<?php echo esc_attr( $slide['grad_bottom'] ); ?>">
                    <img src="<?php echo esc_url( $image_url ); ?>"
                         class="hero-illust"
                         alt="<?php echo esc_attr( '바로빌 히어로 이미지 ' . $i ); ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="hero-content">
            <div class="hero-badge"><?php echo esc_html( $first_slide['badge'] ); ?></div>

            <h1 class="hero-title"><?php echo $first_slide['title_html']; ?></h1>

            <div class="hero-indicators" style="margin-top:8px;">
                <span class="dot is-active" data-index="0"></span>
                <span class="dot" data-index="1"></span>
                <span class="dot" data-index="2"></span>
            </div>
        </div>
    </div>
</section>

<div class="layout">

    <?php get_template_part('template-parts/recommended'); ?>
    <?php get_template_part('template-parts/category-filter'); ?>
    <?php get_template_part('template-parts/main-list'); ?>
    <?php get_template_part('template-parts/banner'); ?>

</div>

<?php get_footer(); ?>
