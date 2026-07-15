<?php
/**
 * Single Post Template (Figma 스타일)
 */

get_header();

// LNB — header-menu(GNB)와 동일 구조
$lnb_groups = borobill_get_header_gnb_lnb_groups();

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
                <?php foreach ( $lnb_groups as $group ) : ?>
                    <?php
                    $is_current_root = ( $root_term instanceof WP_Term && (int) $group['term_id'] > 0 && (int) $root_term->term_id === (int) $group['term_id'] );
                    $is_current_parent = $is_current_root
                        && $current_term instanceof WP_Term
                        && $root_term instanceof WP_Term
                        && (int) $current_term->term_id === (int) $root_term->term_id;
                    ?>
                    <div class="category-lnb-group<?php echo $is_current_root ? ' is-current' : ''; ?>">
                        <div class="category-lnb-parent-row">
                            <a class="category-lnb-parent<?php echo $is_current_parent ? ' is-current' : ''; ?>"
                               href="<?php echo esc_url( $group['url'] ); ?>">
                                <?php echo esc_html( $group['title'] ); ?>
                            </a>
                        </div>
                        <?php if ( ! empty( $group['children'] ) ) : ?>
                            <ul class="category-lnb-children">
                                <?php foreach ( $group['children'] as $child ) : ?>
                                    <?php
                                    $is_current_child = ( $current_term instanceof WP_Term && (int) $child['term_id'] > 0 && (int) $current_term->term_id === (int) $child['term_id'] );
                                    ?>
                                    <li>
                                        <a class="<?php echo $is_current_child ? 'is-current' : ''; ?>"
                                           href="<?php echo esc_url( $child['url'] ); ?>">
                                            <?php echo esc_html( $child['title'] ); ?>
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
                        <div class="post-header-meta">
                            <span class="badge badge-small"><?php echo esc_html( borobill_get_root_gnb_category_name() ); ?></span>
                        </div>
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        <div class="post-subtitle-line">
                            <div class="post-subtitle-line__left">
                                <?php
                                $post_subtitle = borobill_get_post_subtitle( get_the_ID() );
                                if ( '' !== $post_subtitle ) {
                                    echo '<p class="post-subtitle">' . esc_html( $post_subtitle ) . '</p>';
                                }
                                ?>
                            </div>
                            <div class="post-share-actions" aria-label="공유">
                                <div class="post-share-dropdown">
                                    <button type="button" class="post-share-btn post-share-btn--label" data-open-share data-post-url="<?php echo esc_url( get_permalink() ); ?>" aria-label="공유하기" aria-expanded="false" aria-controls="share-modal" title="공유하기">
                                        <span class="post-share-btn-icon" aria-hidden="true">
                                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/share.svg" width="20" height="20" alt="">
                                        </span>
                                        <span class="post-share-btn-text">공유하기</span>
                                    </button>
                                    <?php get_template_part( 'template-parts/share-modal' ); ?>
                                </div>
                            </div>
                        </div>

                        <!-- 날짜, 작성자 meta 숨김 -->
                    </header>

                    <?php
                    $featured_youtube_url = borobill_get_post_featured_youtube_url( get_the_ID() );
                    $youtube_video_id     = borobill_get_youtube_video_id( $featured_youtube_url );
                    if ( $youtube_video_id ) :
                        $embed_src = 'https://www.youtube.com/embed/' . esc_attr( $youtube_video_id );
                        ?>
                        <div class="post-thumbnail post-thumbnail--hero post-thumbnail--video" style="margin-bottom:32px;">
                            <div class="post-thumbnail__video-wrap">
                                <iframe
                                    src="<?php echo esc_url( $embed_src ); ?>"
                                    title="<?php esc_attr_e( 'YouTube 영상', 'borobill_theme' ); ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                    loading="lazy"
                                ></iframe>
                            </div>
                        </div>
                    <?php elseif ( has_post_thumbnail() ) : ?>
                        <div class="post-thumbnail post-thumbnail--hero" style="margin-bottom:32px;">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>

                    <?php
                    $post_banner_image = borobill_get_post_banner_image( get_the_ID() );
                    if ( '' !== $post_banner_image ) :
                        $post_banner_url = borobill_get_post_banner_url( get_the_ID() );
                        $post_banner_alt = borobill_get_post_banner_alt( get_the_ID() );
                        ?>
                        <div class="post-bottom-banner">
                            <?php if ( '' !== $post_banner_url ) : ?>
                                <a href="<?php echo esc_url( $post_banner_url ); ?>" class="post-bottom-banner__link">
                                    <img
                                        src="<?php echo esc_url( $post_banner_image ); ?>"
                                        alt="<?php echo esc_attr( $post_banner_alt ); ?>"
                                        class="post-bottom-banner__image"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </a>
                            <?php else : ?>
                                <img
                                    src="<?php echo esc_url( $post_banner_image ); ?>"
                                    alt="<?php echo esc_attr( $post_banner_alt ); ?>"
                                    class="post-bottom-banner__image"
                                    loading="lazy"
                                    decoding="async"
                                />
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <footer class="post-footer">
                        <div class="post-tags">
                            <?php
                            $tags = get_the_tags();
                            if ( $tags ) {
                                foreach ( $tags as $tag ) {
                                    echo '<span class="tag" draggable="false"># ' . esc_html( $tag->name ) . '</span>';
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
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'posts_per_page'      => 3,
                            'post__not_in'        => [ get_the_ID() ],
                            'cat'                 => $root_term->term_id,
                            'no_found_rows'       => true,
                            'ignore_sticky_posts' => true,
                        ]
                    );
                    if ( $related_query->have_posts() ) :
                        ?>
                        <section class="single-related section-list">
                            <div class="single-related-header">
                                <h2 class="single-related-title"><?php echo esc_html( $root_term->name ); ?> 최신 글</h2>
                                <a class="single-related-more"
                                   href="<?php echo esc_url( get_category_link( $root_term ) ); ?>">
                                    더보기
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                            <div class="post-list single-related-list">
                                <?php
                                while ( $related_query->have_posts() ) :
                                    $related_query->the_post();
                                    ?>
                                    <article class="article-card article-card--feed">
                                        <div class="article-info">
                                            <h3 class="article-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                            <span class="meta-date"><?php echo esc_html( borobill_post_date() ); ?></span>
                                        </div>
                                        <div class="article-thumb-wrap">
                                            <a href="<?php the_permalink(); ?>">
                                                <?php
                                                if ( has_post_thumbnail() ) {
                                                    the_post_thumbnail(
                                                        'medium_large',
                                                        [
                                                            'class' => 'article-thumb',
                                                            'alt'   => esc_attr( get_the_title() ),
                                                        ]
                                                    );
                                                } else {
                                                    ?>
                                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                                         class="article-thumb"
                                                         alt="" />
                                                    <?php
                                                }
                                                ?>
                                            </a>
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

            <?php endwhile; ?>
        </main>

        <!-- 우측 고정 영역: 읽기 진행률 UX + 추천게시물 + 목차박스 -->
        <aside class="single-aside sticky-group">
            <?php
            $reading_time = (int) get_post_meta( get_queried_object_id(), '_borobill_reading_time', true );
            if ( $reading_time <= 0 ) {
                $reading_time = 3;
            }
            $reading_messages = function_exists( 'borobill_get_reading_messages' )
                ? borobill_get_reading_messages()
                : array(
                    'start' => '시작이 반이에요, 천천히 읽어보세요.',
                    '25'    => '조금만 더 읽으면 핵심내용!',
                    '50'    => '이미 절반을 읽었어요.',
                    '75'    => '거의 다 왔어요!',
                    '100'   => '끝까지 읽으셨네요!',
                );
            ?>
            <div class="single-reading-time" aria-label="읽기 진행률" role="region">
                <div class="single-reading-time__head">
                    <span class="single-reading-time__duration">읽는 시간 <?php echo (int) $reading_time; ?>분</span>
                    <span class="single-reading-time__percent" aria-live="polite">0% 읽는 중</span>
                </div>
                <div class="single-reading-time__bar-wrap" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="읽기 진행률">
                    <div class="single-reading-time__bar-track">
                        <div class="single-reading-time__bar-fill" style="width: 0%;"></div>
                    </div>
                </div>
                <p class="single-reading-time__message"
                   data-msg-start="<?php echo esc_attr( $reading_messages['start'] ); ?>"
                   data-msg-25="<?php echo esc_attr( $reading_messages['25'] ); ?>"
                   data-msg-50="<?php echo esc_attr( $reading_messages['50'] ); ?>"
                   data-msg-75="<?php echo esc_attr( $reading_messages['75'] ); ?>"
                   data-msg-100="<?php echo esc_attr( $reading_messages['100'] ); ?>"><?php echo esc_html( $reading_messages['start'] ); ?></p>
            </div>
            <section class="single-aside-related">
                <h2 class="single-aside-related-title">추천게시물</h2>
                <div class="single-related-list">
                <?php
                // 추천게시글: 2차 카테고리 관리자 1~3순위 우선, 미설정·결과없음 시 전체 조회수순
                $current_cat_ids   = array_map( 'intval', wp_get_post_categories( get_the_ID() ) );
                $secondary_term_id = 0;
                $recommended_posts = array();
                $used_ids          = array( (int) get_the_ID() );

                foreach ( borobill_get_gnb_parent_menu_items() as $parent_item ) {
                    foreach ( borobill_get_gnb_child_category_items( (int) $parent_item['id'] ) as $child_item ) {
                        $child_id = (int) $child_item['id'];
                        if ( $child_id > 0 && in_array( $child_id, $current_cat_ids, true ) ) {
                            $secondary_term_id = $child_id;
                            break 2;
                        }
                    }
                }

                $has_admin_rec = false;
                if ( $secondary_term_id > 0 ) {
                    foreach ( array( 1, 2, 3 ) as $rank ) {
                        if ( (int) get_term_meta( $secondary_term_id, 'borobill_recommended_post_' . $rank, true ) > 0 ) {
                            $has_admin_rec = true;
                            break;
                        }
                    }
                }

                if ( $has_admin_rec && $secondary_term_id > 0 ) {
                    foreach ( array( 1, 2, 3 ) as $rank ) {
                        $pid = (int) get_term_meta( $secondary_term_id, 'borobill_recommended_post_' . $rank, true );
                        if ( $pid <= 0 || in_array( $pid, $used_ids, true ) ) {
                            continue;
                        }
                        $rec_post = get_post( $pid );
                        if ( ! $rec_post || 'publish' !== $rec_post->post_status || 'post' !== $rec_post->post_type ) {
                            continue;
                        }
                        $recommended_posts[] = $rec_post;
                        $used_ids[]          = $pid;
                        if ( count( $recommended_posts ) >= 3 ) {
                            break;
                        }
                    }
                }

                if ( empty( $recommended_posts ) ) {
                    $recommended_posts = borobill_get_recommended_posts(
                        array(
                            'exclude_post_id' => get_the_ID(),
                            'limit'           => 3,
                        )
                    );
                }

                // 조회수 메타 없는 경우에도 비지 않도록 최신글 폴백
                if ( empty( $recommended_posts ) ) {
                    $recommended_posts = get_posts(
                        array(
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'numberposts'         => 3,
                            'post__not_in'        => array( (int) get_the_ID() ),
                            'orderby'             => 'date',
                            'order'               => 'DESC',
                            'ignore_sticky_posts' => true,
                        )
                    );
                }

                foreach ( $recommended_posts as $post ) :
                    setup_postdata( $post );
                ?>
                  <article class="single-related-card">
                      <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="single-related-thumb">
                        <?php
                        if ( has_post_thumbnail( $post ) ) {
                            echo get_the_post_thumbnail(
                                $post,
                                'medium_large',
                                array(
                                    'class' => 'single-related-img',
                                    'alt'   => esc_attr( get_the_title( $post ) ),
                                )
                            );
                        } else {
                        ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>" class="single-related-img" alt="기본 이미지" />
                        <?php } ?>
                      </a>
                      <div class="single-related-body">
                        <h3 class="single-related-post-title"><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
                        <span class="meta-date"><?php echo esc_html( borobill_post_date( $post->ID ) ); ?></span>
                      </div>
                  </article>
                <?php
                endforeach;
                wp_reset_postdata();
                ?>
                </div>
            </section>
            <div class="single-toc-box">
                <h2 class="single-toc-title">INDEX</h2>
                <ul class="single-toc-list"></ul>
            </div>
        </aside>

    </div>
</div>

<?php get_footer(); ?>


