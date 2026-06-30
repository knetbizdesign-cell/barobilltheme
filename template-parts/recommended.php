<?php
/**
 * 추천 게시물 섹션
 *
 * 기본: 전체에서 3개
 * category.php 에서 사용할 때는 $args['borobill_root_term'] 로 루트 카테고리를 넘겨주면,
 * 해당 카테고리(및 자식)에서만 3개를 가져온다.
 */

$borobill_root_term = isset( $args['borobill_root_term'] ) && $args['borobill_root_term'] instanceof WP_Term
    ? $args['borobill_root_term']
    : null;

$featured_args = [
    'posts_per_page'      => 3,
    'ignore_sticky_posts' => true,
];

if ( $borobill_root_term ) {
    $featured_args['cat'] = $borobill_root_term->term_id;
}

$featured_query = new WP_Query( $featured_args );

// 추천 카드 전용 기본 이미지 매핑 (2, 3, 4번 이미지를 순서대로 사용)
$borobill_featured_fallbacks = [
    1 => '2.png',
    2 => '3.png',
    3 => '4.png',
];
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

        <div id="featured-posts" class="featured-grid">
            <?php
            if ( $featured_query->have_posts() ) :
                while ( $featured_query->have_posts() ) :
                    $featured_query->the_post();
                    ?>
                    <article class="featured-card featured-card--feed">
                        <a href="<?php the_permalink(); ?>" class="featured-thumb-wrap">
                            <?php
                            if ( has_post_thumbnail() ) {
                                the_post_thumbnail(
                                    'medium_large', // 자연스러운 비율 유지
                                    [
                                        'class' => 'featured-thumb',
                                        'alt'   => esc_attr( get_the_title() ),
                                    ]
                                );
                            } else {
                                // 지정된 2.png, 3.png, 4.png 이미지를 순서대로 사용
                                $fallback_key = isset( $borobill_featured_fallbacks[ $borobill_featured_index ] )
                                    ? $borobill_featured_index
                                    : 1;
                                $fallback_src = get_template_directory_uri() . '/images/' . $borobill_featured_fallbacks[ $fallback_key ];
                                $borobill_featured_index++;
                                ?>
                                <img src="<?php echo esc_url( $fallback_src ); ?>"
                                     class="featured-thumb"
                                     alt="<?php echo esc_attr( get_the_title() ); ?>" />
                                <?php
                            }
                            ?>
                        </a>

                        <?php
                        $bb_views = get_post_meta( get_the_ID(), '_bb_views', true );
                        $bb_views = is_numeric( $bb_views ) ? (int) $bb_views : 0;
                        ?>
                        <div class="featured-meta article-meta">
                            <span class="badge badge-small"><?php echo esc_html( borobill_get_root_gnb_category_name() ); ?></span>
                            <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
                            <span class="featured-views">
                                <span class="featured-views__label">조회수</span>
                                <span class="featured-views__count"><?php echo esc_html( number_format_i18n( $bb_views ) ); ?></span>
                            </span>
                        </div>
                        <h3 class="featured-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php
                        $borobill_feat_sub = borobill_get_post_subtitle( get_the_ID() );
                        if ( '' !== $borobill_feat_sub ) {
                            echo '<p class="featured-subdesc"><a class="featured-subdesc__link" href="' . esc_url( get_permalink() ) . '">' . esc_html( $borobill_feat_sub ) . '</a></p>';
                        }
                        ?>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
    </div>
</section>

