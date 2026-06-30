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
                                <button type="button" class="post-share-btn post-share-btn--label post-share-copy" data-post-url="<?php echo esc_url( get_permalink() ); ?>" aria-label="공유하기" title="링크 복사">
                                    <span class="post-share-btn-icon" aria-hidden="true">
                                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/share.svg" width="20" height="20" alt="">
                                    </span>
                                    <span class="post-share-btn-text">공유하기</span>
                                </button>
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
                            'post_type'      => 'post',
                            'post_status'    => 'publish',
                            'posts_per_page' => 3,
                            'post__not_in'   => [ get_the_ID() ],
                            'cat'            => $root_term->term_id,
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
                <p class="single-reading-time__message" data-msg-start="시작이 반이에요, 천천히 읽어보세요." data-msg-25="조금만 더 읽으면 핵심내용!" data-msg-50="이미 절반을 읽었어요." data-msg-75="거의 다 왔어요!" data-msg-100">시작이 반이에요, 천천히 읽어보세요.</p>
            </div>
            <section class="single-aside-related">
                <h2 class="single-aside-related-title">추천게시물</h2>
                <div class="single-related-list">
                <?php
                // 추천게시물: 해당 카테고리 내 조회수(관리자 입력 _bb_views) 상위 3개
                $current_cat_ids = wp_get_post_categories( get_the_ID() );
                $recommended_args = [
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in'   => array( get_the_ID() ),
                    'meta_key'       => '_bb_views',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                ];
                if ( ! empty( $current_cat_ids ) ) {
                    $recommended_args['category__in'] = $current_cat_ids;
                }
                $recommended_query = new WP_Query( $recommended_args );
                $recommended_ids   = wp_list_pluck( $recommended_query->posts, 'ID' );
                // 조회수 있는 글이 3개 미만이면 같은 카테고리 최신글으로 부족분 채움
                if ( $recommended_query->post_count < 3 && ! empty( $current_cat_ids ) ) {
                    $fill_args = [
                        'post_type'      => 'post',
                        'post_status'    => 'publish',
                        'posts_per_page' => 3 - $recommended_query->post_count,
                        'post__not_in'   => array_merge( array( get_the_ID() ), $recommended_ids ),
                        'category__in'   => $current_cat_ids,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ];
                    $fill_query = new WP_Query( $fill_args );
                    if ( $fill_query->have_posts() ) {
                        $recommended_query->posts = array_merge( $recommended_query->posts, $fill_query->posts );
                        $recommended_query->post_count = count( $recommended_query->posts );
                        wp_reset_postdata();
                    }
                }
                if ( $recommended_query->have_posts() ) :
                    while ( $recommended_query->have_posts() ) :
                        $recommended_query->the_post();
                  setup_postdata($post);
                ?>
                  <article class="single-related-card">
                      <a href="<?php the_permalink(); ?>" class="single-related-thumb">
                        <?php
                        if ( has_post_thumbnail() ) {
                          the_post_thumbnail('medium_large', ['class' => 'single-related-img', 'alt' => esc_attr(get_the_title()) ]);
                        } else {
                        ?>
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>" class="single-related-img" alt="기본 이미지" />
                        <?php } ?>
                      </a>
                      <div class="single-related-body">
                        <h3 class="single-related-post-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <span class="meta-date"><?php echo esc_html( borobill_post_date() ); ?></span>
                      </div>
                  </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
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

<script>
(function() {
    var btn = document.querySelector('.post-share-copy');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var url = btn.getAttribute('data-post-url') || window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                var orig = btn.getAttribute('title');
                btn.setAttribute('title', '복사되었습니다');
                setTimeout(function() { btn.setAttribute('title', orig || '링크 복사'); }, 1500);
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                var orig = btn.getAttribute('title');
                btn.setAttribute('title', '복사되었습니다');
                setTimeout(function() { btn.setAttribute('title', orig || '링크 복사'); }, 1500);
            } catch (e) {}
            document.body.removeChild(ta);
        }
    });
})();
</script>

<?php get_footer(); ?>


