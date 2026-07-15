<?php
/**
 * 추천 게시물 섹션
 *
 * 기본: 전체에서 3개 (관리자 미지정 시 조회수 추천순)
 * category.php 에서 사용할 때는 $args['borobill_root_term'] 로 루트 카테고리를 넘겨주면,
 * 해당 카테고리(및 자식)에서만 3개를 가져온다.
 */

$borobill_root_term = isset( $args['borobill_root_term'] ) && $args['borobill_root_term'] instanceof WP_Term
    ? $args['borobill_root_term']
    : null;

$recommended_args = array(
    'limit' => 3,
);

if ( $borobill_root_term ) {
    $recommended_args['root_term_id'] = (int) $borobill_root_term->term_id;
}

$recommended_posts = borobill_get_recommended_posts( $recommended_args );

// 추천 카드 전용 기본 이미지 매핑 (2, 3, 4번 이미지를 순서대로 사용)
$borobill_featured_fallbacks = array(
    1 => '2.png',
    2 => '3.png',
    3 => '4.png',
);
$borobill_featured_index = 1;
?>

<section class="section section-featured">
    <div class="section-inner">
        <div class="section-header">
            <div class="featured-head">
                <h2 class="section-title">추천 게시물</h2>
                <p class="section-desc">바로빌과 함께 다양한 추천 게시물을 확인해보세요.</p>
            </div>
        </div>

        <div id="featured-posts" class="featured-grid" data-php-rendered="1">
            <?php foreach ( $recommended_posts as $post ) : ?>
                <?php setup_postdata( $post ); ?>
                <article class="featured-card featured-card--feed">
                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="featured-thumb-wrap">
                        <?php
                        if ( has_post_thumbnail( $post ) ) {
                            echo get_the_post_thumbnail(
                                $post,
                                'medium_large',
                                array(
                                    'class' => 'featured-thumb',
                                    'alt'   => esc_attr( get_the_title( $post ) ),
                                )
                            );
                        } else {
                            $fallback_key = isset( $borobill_featured_fallbacks[ $borobill_featured_index ] )
                                ? $borobill_featured_index
                                : 1;
                            $fallback_src = get_template_directory_uri() . '/images/' . $borobill_featured_fallbacks[ $fallback_key ];
                            $borobill_featured_index++;
                            ?>
                            <img src="<?php echo esc_url( $fallback_src ); ?>"
                                 class="featured-thumb"
                                 alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" />
                            <?php
                        }
                        ?>
                    </a>

                    <div class="featured-meta article-meta">
                        <span class="badge badge-small"><?php echo esc_html( borobill_get_root_gnb_category_name( $post->ID ) ); ?></span>
                        <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d', $post ) ); ?></span>
                    </div>
                    <h3 class="featured-title">
                        <a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
                    </h3>
                    <?php
                    $borobill_feat_sub = borobill_get_post_subtitle( $post->ID );
                    if ( '' !== $borobill_feat_sub ) {
                        echo '<p class="featured-subdesc"><a class="featured-subdesc__link" href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $borobill_feat_sub ) . '</a></p>';
                    }
                    ?>
                </article>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</section>
