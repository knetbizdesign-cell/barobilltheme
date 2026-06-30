<?php
/**
 * 상위 카테고리(초보사업자 / 세무·비즈니스 / 사업자 뉴스룸 / 바로빌 가이드 / 고객사례·인사이트)
 * 전용 카테고리 템플릿
 *
 * - 좌측: LNB (상위 5개 카테고리 + 하위 카테고리 목록)
 * - 우측: 상단 히어로 카드 3개 (최신 글 1 + 그 다음 2개)
 * - 하단: 하위 카테고리 필터 + 검색 + 리스트
 */

get_header();

// 현재 쿼리된 카테고리 객체 (현재 보고 있는 카테고리)
$term = get_queried_object();

// 루트(부모) 카테고리 계산
$root_term = $term;
if ( $term instanceof WP_Term && $term->parent ) {
    while ( $root_term->parent ) {
        $root_term = get_term( $root_term->parent, 'category' );
        if ( is_wp_error( $root_term ) ) {
            break;
        }
    }
}

// 루트 카테고리 이름/슬러그
$root_name = $root_term instanceof WP_Term ? $root_term->name : single_cat_title( '', false );
$root_slug = $root_term instanceof WP_Term ? $root_term->slug : '';

// LNB 에서 사용할 상위 카테고리 5개 (GNB 와 동일한 순서)
$want_parents = [
    '초보사업자',
    '세무·비즈니스',
    '사업자 뉴스룸',
    '바로빌 가이드',
    '고객사례·인사이트',
];

$parent_terms = [];
foreach ( $want_parents as $parent_name ) {
    $t = get_term_by( 'name', $parent_name, 'category' );
    if ( $t && ! is_wp_error( $t ) ) {
        $parent_terms[] = $t;
    }
}

// 상단 히어로 영역용 쿼리 (현재 루트 카테고리 기준 최신 글 3개)
$hero_ids  = [];
$hero_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
];
if ( $root_term instanceof WP_Term ) {
    $hero_args['cat'] = $root_term->term_id;
}

$hero_query = new WP_Query( $hero_args );
?>

<div class="layout layout--category">
    <div class="category-shell">
        <!-- LNB -->
        <aside class="category-lnb">
            <nav class="category-lnb-inner">
                <?php foreach ( $parent_terms as $parent ) : ?>
                    <?php
                    $is_current_root = ( $root_term instanceof WP_Term && (int) $root_term->term_id === (int) $parent->term_id );
                    $children        = get_categories(
                        [
                            'hide_empty' => false,
                            'parent'     => $parent->term_id,
                        ]
                    );
                    ?>
                    <div class="category-lnb-group<?php echo $is_current_root ? ' is-current' : ''; ?>">
                        <div class="category-lnb-parent-row">
                            <a class="category-lnb-parent"
                               href="<?php echo esc_url( get_category_link( $parent ) ); ?>">
                                <?php echo esc_html( $parent->name ); ?>
                            </a>
                            <?php if ( $children ) : ?>
                                <button type="button"
                                        class="category-lnb-toggle"
                                        aria-label="하위 메뉴 열기/닫기"></button>
                            <?php endif; ?>
                        </div>
                        <?php if ( $children ) : ?>
                            <ul class="category-lnb-children">
                                <?php foreach ( $children as $child ) : ?>
                                    <?php
                                    // 현재 보고 있는 카테고리가 자식 항목이면 강조 표시
                                    $is_current_child = ( $term instanceof WP_Term && (int) $term->term_id === (int) $child->term_id );
                                    ?>
                                    <li>
                                        <a class="<?php echo $is_current_child ? 'is-current' : ''; ?>"
                                           href="<?php echo esc_url( get_category_link( $child ) ); ?>">
                                            <?php echo esc_html( $child->name ); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- 우측 메인 영역 -->
        <main class="category-main">
            <header class="category-main-header">
                <h1 class="category-main-title"><?php echo esc_html( $root_name ); ?></h1>
            </header>

            <?php if ( $hero_query->have_posts() ) : ?>
                <?php
                $hero_posts = [];
                while ( $hero_query->have_posts() ) :
                    $hero_query->the_post();
                    $hero_posts[] = get_post();
                    $hero_ids[]   = get_the_ID();
                endwhile;
                wp_reset_postdata();
                ?>

                <section class="category-hero-posts">
                    <div class="category-hero-grid">
                        <?php if ( ! empty( $hero_posts[0] ) ) : ?>
                            <?php
                            $p      = $hero_posts[0];
                            $cats   = get_the_category( $p->ID );
                            $cat_nm = $cats ? $cats[0]->name : '카테고리';
                            ?>
                            <article class="category-hero-main">
                                <a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="category-hero-main-thumb">
                                    <?php
                                    if ( has_post_thumbnail( $p ) ) {
                                        echo get_the_post_thumbnail(
                                            $p,
                                            'large',
                                            [
                                                'class' => 'category-hero-main-img',
                                                'alt'   => esc_attr( get_the_title( $p ) ),
                                            ]
                                        );
                                    } else {
                                        ?>
                                        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                             class="category-hero-main-img"
                                             alt="기본 이미지" />
                                        <?php
                                    }
                                    ?>
                                </a>
                                <div class="category-hero-main-meta">
                                    <span class="badge badge-small"><?php echo esc_html( $cat_nm ); ?></span>
                                    <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d', $p ) ); ?></span>
                                </div>
                                <h2 class="category-hero-main-title">
                                    <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
                                        <?php echo esc_html( get_the_title( $p ) ); ?>
                                    </a>
                                </h2>
                                <p class="category-hero-main-desc">
                                    <?php echo esc_html( wp_trim_words( get_the_excerpt( $p ), 28 ) ); ?>
                                </p>
                            </article>
                        <?php endif; ?>

                        <div class="category-hero-side">
                            <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
                                <?php if ( empty( $hero_posts[ $i ] ) ) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <?php
                                $p      = $hero_posts[ $i ];
                                $cats   = get_the_category( $p->ID );
                                $cat_nm = $cats ? $cats[0]->name : '카테고리';
                                ?>
                                <article class="category-hero-side-card">
                                    <a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="category-hero-side-thumb">
                                        <?php
                                        if ( has_post_thumbnail( $p ) ) {
                                            echo get_the_post_thumbnail(
                                                $p,
                                                'medium_large',
                                                [
                                                    'class' => 'category-hero-side-img',
                                                    'alt'   => esc_attr( get_the_title( $p ) ),
                                                ]
                                            );
                                        } else {
                                            ?>
                                            <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                                 class="category-hero-side-img"
                                                 alt="기본 이미지" />
                                            <?php
                                        }
                                        ?>
                                    </a>
                                    <div class="category-hero-side-body">
                                        <div class="featured-meta">
                                            <span class="badge badge-small"><?php echo esc_html( $cat_nm ); ?></span>
                                            <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d', $p ) ); ?></span>
                                        </div>
                                        <h3 class="category-hero-side-title">
                                            <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
                                                <?php echo esc_html( get_the_title( $p ) ); ?>
                                            </a>
                                        </h3>
                                    </div>
                                </article>
                            <?php endfor; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php
            // 하위 카테고리 필터 + 검색
            get_template_part(
                'template-parts/category-subfilter',
                null,
                [
                    'borobill_root_term' => $root_term instanceof WP_Term ? $root_term : null,
                ]
            );
            ?>

            <!-- 리스트 영역 -->
            <section class="section section-list section-list--category">
                <div class="section-inner">
                    <div class="post-list">
                        <?php
                        // 메인 리스트 쿼리: 루트 카테고리 기준, 상단 히어로에서 사용된 글은 제외
                        $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

                        $list_args = [
                            'post_type'      => 'post',
                            'post_status'    => 'publish',
                            'posts_per_page' => 5,
                            'paged'          => $paged,
                        ];

                        if ( $root_term instanceof WP_Term ) {
                            $list_args['cat'] = $root_term->term_id;
                        }
                        if ( $hero_ids ) {
                            $list_args['post__not_in'] = array_map( 'intval', $hero_ids );
                        }

                        $borobill_query         = new WP_Query( $list_args );
                        $borobill_max_cards     = 5;
                        $borobill_card_rendered = 0;

                        if ( $borobill_query->have_posts() ) :
                            while ( $borobill_query->have_posts() && $borobill_card_rendered < $borobill_max_cards ) :
                                $borobill_query->the_post();
                                $borobill_card_rendered++;

                                $cats       = get_the_category();
                                $child_slug = 'etc';

                                if ( $cats ) {
                                    $cat = $cats[0];
                                    if ( $root_term instanceof WP_Term ) {
                                        // 루트와 동일한 계열인 경우, 자식 슬러그를 data-subcat 로 사용
                                        $root_for_post = $cat;
                                        while ( $root_for_post->parent ) {
                                            $root_for_post = get_category( $root_for_post->parent );
                                        }
                                        if ( (int) $root_for_post->term_id === (int) $root_term->term_id ) {
                                            $child_slug = $cat->slug;
                                        }
                                    }
                                }
                                ?>
                                <article class="article-card"
                                         data-subcat="<?php echo esc_attr( $child_slug ); ?>">
                                    <div class="article-info">
                                        <div class="article-meta">
                                            <span class="badge badge-small">
                                                <?php echo $cats ? esc_html( $cats[0]->name ) : '카테고리'; ?>
                                            </span>
                                            <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
                                        </div>
                                        <h3 class="article-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                        <p class="featured-subdesc" style="margin-top:8px; color:#111; font-size:16px; font-style:normal; line-height:150%;">
                                            <?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?>
                                        </p>
                                    </div>

                                    <div class="article-thumb-wrap">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <img src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'medium_large' ) ); ?>"
                                                     class="article-thumb"
                                                     alt="<?php echo esc_attr( get_the_title() ); ?>" />
                                            <?php else : ?>
                                                <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                                     class="article-thumb"
                                                     alt="기본 이미지" />
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php endif; ?>
                    </div>

                    <div class="pagination">
                        <?php
                        echo paginate_links(
                            [
                                'mid_size'  => 1,
                                'prev_text' => '<span class="page-icon page-icon--prev" aria-hidden="true"></span>',
                                'next_text' => '<span class="page-icon page-icon--next" aria-hidden="true"></span>',
                            ]
                        );
                        ?>
                    </div>
                </div>
            </section>

            <?php get_template_part( 'template-parts/banner' ); ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>

