<?php
/**
 * 리스트 페이지 페이지네이션 하단 CTA 배너 (멀티 슬라이드)
 */

// 게시된 슬라이드 ID 배열 로드
$published_ids = function_exists( 'borobill_get_published_bottom_banner_slide_order_ids' )
    ? borobill_get_published_bottom_banner_slide_order_ids()
    : array();

if ( empty( $published_ids ) ) {
    return;
}

// 전환 설정
$auto_delay    = (int) get_option( 'borobill_bottom_banner_auto_delay', 6 );
$anim_duration = (float) get_option( 'borobill_bottom_banner_anim_duration', 0.6 );
$anim_effect   = (string) get_option( 'borobill_bottom_banner_anim_effect', 'default' );

$slide_count = count( $published_ids );
$single      = 1 === $slide_count;

?>
<section class="section section-bottom-cta" aria-label="하단 배너" data-bottom-cta-carousel>
    <div class="section-inner">
        <div class="bottom-cta-carousel<?php echo $single ? ' bottom-cta-carousel--single' : ''; ?>"
             style="--cta-duration: <?php echo esc_attr( number_format( $anim_duration, 1 ) ); ?>s;"
             data-auto-delay="<?php echo esc_attr( (string) $auto_delay ); ?>"
             data-anim-duration="<?php echo esc_attr( number_format( $anim_duration, 1 ) ); ?>"
             data-anim-effect="<?php echo esc_attr( $anim_effect ); ?>">

            <?php foreach ( $published_ids as $idx => $slide_id ) :
                $slide_id = (int) $slide_id;
                $image    = (string) get_option( 'borobill_bottom_banner_image_' . $slide_id, '' );
                $title    = (string) get_option( 'borobill_bottom_banner_title_' . $slide_id, '' );
                $body     = (string) get_option( 'borobill_bottom_banner_body_' . $slide_id, '' );
                $bg       = (string) get_option( 'borobill_bottom_banner_bg_color_' . $slide_id, '#7ea354' );
                $btn_text = (string) get_option( 'borobill_bottom_banner_button_text_' . $slide_id, '바로빌 바로가기' );
                $url      = (string) get_option( 'borobill_bottom_banner_url_' . $slide_id, '' );
                $new_tab  = (int) get_option( 'borobill_bottom_banner_new_tab_' . $slide_id, 1 );
                $btn_color = function_exists( 'borobill_get_bottom_banner_button_color_css' )
                    ? borobill_get_bottom_banner_button_color_css( $slide_id )
                    : '';
                $btn_text_color_raw = get_option( 'borobill_bottom_banner_button_text_color_' . $slide_id, '#ffffff' );
                $btn_text_color     = is_string( $btn_text_color_raw ) ? sanitize_hex_color( trim( $btn_text_color_raw ) ) : '';
                if ( ! is_string( $btn_text_color ) || '' === $btn_text_color ) {
                    $btn_text_color = '#ffffff';
                }

                $bg = sanitize_hex_color( $bg );
                if ( ! $bg ) {
                    $bg = '#7ea354';
                }
                $url    = borobill_sanitize_http_url( $url );
                $target = ( 1 === $new_tab ) ? '_blank' : '_self';
                $rel    = ( 1 === $new_tab ) ? 'noopener noreferrer' : '';
                $is_first = ( 0 === $idx );

                $cta_style = '--cta-bg: ' . $bg . '; --cta-btn-color: ' . $btn_text_color . ';';
                if ( '' !== $btn_color ) {
                    $cta_style .= ' --cta-btn-bg: ' . $btn_color . ';';
                }
            ?>
            <div class="bottom-cta-slide<?php echo $is_first ? ' is-active' : ''; ?>"
                 aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>"
                 data-banner-key="<?php echo esc_attr( 'bottom_' . $slide_id ); ?>">
                <div class="bottom-cta<?php echo $image ? ' bottom-cta--has-media' : ''; ?>" style="<?php echo esc_attr( $cta_style ); ?>">
                    <?php if ( $image ) : ?>
                        <div class="bottom-cta__media" aria-hidden="true">
                            <img class="bottom-cta__illust" src="<?php echo esc_url( $image ); ?>" alt="">
                        </div>
                    <?php endif; ?>

                    <div class="bottom-cta__content">
                        <?php if ( $title ) : ?>
                            <h2 class="bottom-cta__title"><?php echo esc_html( $title ); ?></h2>
                        <?php endif; ?>

                        <?php if ( $body ) : ?>
                            <p class="bottom-cta__desc"><?php echo nl2br( esc_html( $body ) ); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ( $btn_text && $url ) : ?>
                        <div class="bottom-cta__action">
                            <a class="bottom-cta__button" href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="<?php echo esc_attr( $rel ); ?>">
                                <span class="bottom-cta__button-text"><?php echo esc_html( $btn_text ); ?></span>
                                <span class="bottom-cta__button-icon" aria-hidden="true"></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ( $slide_count >= 2 ) : ?>
                <div class="bottom-cta-carousel-pagination" aria-label="<?php esc_attr_e( '하단 배너 슬라이드 탐색', 'borobill_theme' ); ?>">
                    <button type="button"
                            class="bottom-cta-carousel-pagination__btn bottom-cta-carousel-pagination__btn--prev"
                            data-bottom-cta-pagination-prev
                            aria-label="<?php esc_attr_e( '이전 슬라이드', 'borobill_theme' ); ?>">
                        <span aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                    <button type="button"
                            class="bottom-cta-carousel-pagination__btn bottom-cta-carousel-pagination__btn--next"
                            data-bottom-cta-pagination-next
                            aria-label="<?php esc_attr_e( '다음 슬라이드', 'borobill_theme' ); ?>">
                        <span aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M10 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
