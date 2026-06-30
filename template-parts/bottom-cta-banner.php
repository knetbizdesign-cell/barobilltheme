<?php
/**
 * 리스트 페이지 페이지네이션 하단 CTA 배너
 */

$enabled = (int) get_option( 'borobill_bottom_banner_enabled', 0 );
if ( 1 !== $enabled ) {
    return;
}

$image    = (string) get_option( 'borobill_bottom_banner_image', '' );
$title    = (string) get_option( 'borobill_bottom_banner_title', '바로빌이 궁금하시나요?' );
$body     = (string) get_option( 'borobill_bottom_banner_body', '' );
$bg       = (string) get_option( 'borobill_bottom_banner_bg_color', '#7ea354' );
$btn_text = (string) get_option( 'borobill_bottom_banner_button_text', '바로빌 바로가기' );
$url      = (string) get_option( 'borobill_bottom_banner_url', '' );
$new_tab  = (int) get_option( 'borobill_bottom_banner_new_tab', 1 );

$bg = sanitize_hex_color( $bg );
if ( ! $bg ) {
    $bg = '#7ea354';
}

// URL은 이중 방어(저장 시 sanitize + 렌더 시 validate)
$url = borobill_sanitize_http_url( $url );

$target = ( 1 === $new_tab ) ? '_blank' : '_self';
$rel    = ( 1 === $new_tab ) ? 'noopener noreferrer' : '';

?>

<section class="section section-bottom-cta" aria-label="하단 배너">
    <div class="section-inner">
        <div class="bottom-cta<?php echo $image ? ' bottom-cta--has-media' : ''; ?>" style="--cta-bg: <?php echo esc_attr( $bg ); ?>;">
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
</section>

