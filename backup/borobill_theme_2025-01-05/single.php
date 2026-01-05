<?php
/**
 * Single Post Template (Figma 스타일)
 */

get_header();

// 상위 5개 카테고리 (LNB용)
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

// 현재 글의 루트 카테고리 및 실제 소속 카테고리
$root_term    = null;
$current_term = null;
$cats         = get_the_category();
if ( $cats ) {
    // 글이 속한 첫 번째 카테고리를 "현재 카테고리"로 사용
    $current_term = $cats[0];

    // 루트 카테고리 계산
    $root_term = $current_term;
    while ( $root_term->parent ) {
        $root_term = get_category( $root_term->parent );
    }
}
?>

<div class="layout layout--single">
    <div class="single-shell">

        <!-- LNB : 상위/하위 카테고리 목록 -->
        <aside class="single-lnb">
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
                                    // 현재 글의 카테고리가 자식 항목이면 강조 표시
                                    $is_current_child = ( $current_term instanceof WP_Term && (int) $current_term->term_id === (int) $child->term_id );
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

        <!-- 메인 본문 -->
        <main class="single-main">
            <?php while ( have_posts() ) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>

                    <header class="post-header">
                        <?php
                        $categories = get_the_category();
                        if ( ! empty( $categories ) ) {
                            $category_name = $categories[0]->name;

                            $bg   = '#e4e8ed';
                            $text = '#2d4a62';

                            if ( $category_name === '사업자 뉴스룸' ) {
                                $bg   = '#e4e9ff';
                                $text = '#5659e1';
                            } elseif ( $category_name === '세무회계' ) {
                                $bg   = '#e1eaf3';
                                $text = '#336fae';
                            }

                            echo '<span class="post-category" style="background:' . $bg . '; color:' . $text . ';">'
                                 . esc_html( $category_name ) .
                                 '</span>';
                        }
                        ?>

                        <h1 class="post-title"><?php the_title(); ?></h1>

                        <div class="post-meta">
                            <span class="post-date"><?php echo borobill_post_date(); ?></span>
                            <span class="post-author">작성자: <?php the_author(); ?></span>
                        </div>
                    </header>

                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="post-thumbnail post-thumbnail--hero">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>

                    <footer class="post-footer">
                        <div class="post-tags">
                            <?php
                            $tags = get_the_tags();
                            if ( $tags ) {
                                foreach ( $tags as $tag ) {
                                    echo '<a href="' . get_tag_link( $tag->term_id ) . '" class="tag"># ' . esc_html( $tag->name ) . '</a>';
                                }
                            }
                            ?>
                        </div>
                    </footer>

                </article>

                <!-- 관련 글 리스트 (같은 루트 카테고리 기준) -->
                <?php
                if ( $root_term instanceof WP_Term ) {
                    $related_query = new WP_Query(
                        [
                            'post_type'      => 'post',
                            'post_status'    => 'publish',
                            'posts_per_page' => 3,
                            'post__not_in'   => [ get_the_ID() ],
                            'cat'            => $root_term->term_id,
                        ]
                    );
                    if ( $related_query->have_posts() ) :
                        ?>
                        <section class="single-related">
                            <h2 class="single-related-title"><?php echo esc_html( $root_term->name ); ?> 최신 글</h2>
                            <div class="single-related-list">
                                <?php
                                while ( $related_query->have_posts() ) :
                                    $related_query->the_post();
                                    ?>
                                    <article class="single-related-card">
                                        <a href="<?php the_permalink(); ?>" class="single-related-thumb">
                                            <?php
                                            if ( has_post_thumbnail() ) {
                                                the_post_thumbnail(
                                                    'medium_large',
                                                    [
                                                        'class' => 'single-related-img',
                                                        'alt'   => esc_attr( get_the_title() ),
                                                    ]
                                                );
                                            } else {
                                                ?>
                                                <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                                     class="single-related-img"
                                                     alt="기본 이미지" />
                                                <?php
                                            }
                                            ?>
                                        </a>
                                        <div class="single-related-body">
                                            <span class="badge badge-small">
                                                <?php
                                                $rcats = get_the_category();
                                                echo $rcats ? esc_html( $rcats[0]->name ) : '카테고리';
                                                ?>
                                            </span>
                                            <h3 class="single-related-post-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                            <span class="meta-date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></span>
                                        </div>
                                    </article>
                                <?php endwhile; ?>
                                <?php wp_reset_postdata(); ?>
                            </div>
                        </section>
                        <?php
                    endif;
                }
                ?>

                <!-- 이전 글 / 다음 글 -->
                <nav class="post-navigation">
                    <div class="nav-previous"><?php previous_post_link( '%link', '← 이전 글' ); ?></div>
                    <div class="nav-next"><?php next_post_link( '%link', '다음 글 →' ); ?></div>
                </nav>

            <?php endwhile; ?>
        </main>

        <!-- 우측 TOC 영역 -->
        <aside class="single-toc">
            <div class="single-toc-box">
                <h2 class="single-toc-title">CONTENTS</h2>
                <p class="single-toc-desc">본문의 소제목(H2/H3)에 HTML 앵커를 달아 연결해서 사용하세요.</p>
                <ul class="single-toc-list">
                    <!-- 필요하다면 여기 항목을 직접 추가해서 사용 -->
                </ul>
            </div>
        </aside>

    </div>
</div>

<?php get_footer(); ?>


