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
<form role="search"
      method="get"
      class="search-form section-search"
      action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="screen-reader-text" for="s">검색어</label>
    <input type="search"
           id="s"
           class="search-field"
           placeholder="검색어를 입력해주세요"
           value="<?php echo get_search_query(); ?>"
           name="s" />
    <button type="submit" class="search-icon-btn search-btn-text" aria-label="검색">검색</button>
</form>
        </div>

        <div class="featured-grid">
            <?php
            // 실제 출력된 카드 개수 카운트
            $borobill_card_count = 0;

            if ( $featured_query->have_posts() ) :
                while ( $featured_query->have_posts() ) :
                    $featured_query->the_post();
                    $borobill_card_count++;
                    ?>
                    <article class="featured-card">
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

                        <div class="featured-meta">
                            <span class="badge badge-small">
                                <?php
                                $cats = get_the_category();
                                echo $cats ? esc_html( $cats[0]->name ) : '카테고리';
                                ?>
                            </span>
                            <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
                        </div>

                        <h3 class="featured-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <p class="featured-subdesc" style="margin-top:8px; color:#111; font-size:16px; font-style:normal; line-height:150%;">
                            <?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?>
                        </p>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
    </div>
</section>


