<?php
/**
 * 카테고리 아카이브 템플릿 (서브페이지 / 자녀페이지 공통)
 *
 * 용어:
 * - 서브페이지: GNB/부모메뉴 페이지 (초보사업자, 세무·비즈니스, 사업자 뉴스룸, 바로빌 가이드, 고객사례·인사이트)
 * - 자녀페이지: 그 아래 자식 카테고리 페이지
 *
 * - 좌측: LNB (상위 5개 카테고리 + 하위 카테고리 목록)
 * - 우측: 서브페이지 시 추천 아티클(히어로) → 전체게시글(검색창 + 최신순/추천순) → 리스트 (자녀페이지도 동일 영역 적용)
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

// 현재 페이지가 루트 허브 페이지인지 여부
$is_root_page = ( $term instanceof WP_Term && $root_term instanceof WP_Term && (int) $term->term_id === (int) $root_term->term_id );

// LNB — header-menu(GNB)와 동일 구조
$lnb_groups = borobill_get_header_gnb_lnb_groups();

// 상단 히어로 영역용 데이터 (최근 아티클 기준)
// 규칙
// - 글이 "많은" 경우(해당 카테고리 트리 안의 글이 5개 이상):
//     * 루트 카테고리 페이지: 루트 아래 각 하위 카테고리별 최신 글 1개씩(최대 3개)
//     * 하위 카테고리 페이지: 해당 하위 카테고리 기준 최신 글 3개
// - 글이 "적은" 경우(4개 이하):
//     * 현재 페이지 기준 "최근 아티클" 순서 그대로 위로 올려서, 최신 글부터 최대 3개까지 사용
//     * 아래쪽 카테고리 필터(subcat)를 클릭해도 히어로 영역은 이 기준에 영향을 받지 않음
$hero_ids        = [];
$hero_posts      = [];
$hero_total_post = 0;

// 관리자가 카테고리 편집에서 지정한 추천 아티클 1/2/3이 있으면 우선 사용 (루트 카테고리 페이지만)
// 저장한 글을 그대로 노출한다. (다른 1차·2차에서 고른 글도 포함)
if ( $is_root_page && $root_term instanceof WP_Term ) {
    $rid1 = (int) get_term_meta( $root_term->term_id, 'borobill_recommended_post_1', true );
    $rid2 = (int) get_term_meta( $root_term->term_id, 'borobill_recommended_post_2', true );
    $rid3 = (int) get_term_meta( $root_term->term_id, 'borobill_recommended_post_3', true );
    if ( $rid1 > 0 || $rid2 > 0 || $rid3 > 0 ) {
        foreach ( array( $rid1, $rid2, $rid3 ) as $pid ) {
            if ( $pid <= 0 || in_array( $pid, $hero_ids, true ) ) {
                continue;
            }
            $p = get_post( $pid );
            if ( ! $p || 'publish' !== $p->post_status || 'post' !== $p->post_type ) {
                continue;
            }
            $hero_posts[] = $p;
            $hero_ids[]   = (int) $p->ID;
        }
    }
}

// 1단계: 추천이 3개 미만이면 자동(최근 아티클)으로 나머지 슬롯 채움
$hero_recent_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 4, // 표시용은 최대 4개만 필요하지만 found_posts 는 전체 건수 기준
];

if ( $term instanceof WP_Term && $root_term instanceof WP_Term && (int) $term->term_id !== (int) $root_term->term_id ) {
    // 하위 카테고리 페이지: 해당 카테고리만 대상
    $hero_recent_args['cat'] = $term->term_id;
} elseif ( $root_term instanceof WP_Term ) {
    // 루트 카테고리 페이지: 루트 + 루트의 직계 하위 카테고리들을 모두 포함
    $cat_ids   = [ (int) $root_term->term_id ];
    $children2 = get_categories(
        [
            'hide_empty' => false,
            'parent'     => $root_term->term_id,
        ]
    );
    foreach ( $children2 as $c2 ) {
        $cat_ids[] = (int) $c2->term_id;
    }
    $hero_recent_args['category__in'] = $cat_ids;
}

// 관리자 추천이 하나도 없을 때만 자동 규칙으로 전체 구성.
// 1~2개만 지정된 경우에도 나머지 자리는 아래에서 채운다.
$hero_needs_auto = ( count( $hero_posts ) < 3 )
    && ( isset( $hero_recent_args['cat'] ) || isset( $hero_recent_args['category__in'] ) );

if ( $hero_needs_auto ) {
    // 관리자 추천이 전혀 없을 때: 기존 자동 규칙
    if ( empty( $hero_ids ) ) {
        $hero_recent_query = new WP_Query( $hero_recent_args );

        // 이 카테고리 트리 안의 전체 글 수
        $hero_total_post = (int) $hero_recent_query->found_posts;

        if ( $hero_total_post <= 4 ) {
            // 글이 4개 이하일 때: "최근 아티클" 순서 그대로 최신 글부터 최대 3개까지 노출
            if ( $hero_recent_query->have_posts() ) {
                while ( $hero_recent_query->have_posts() && count( $hero_posts ) < 3 ) {
                    $hero_recent_query->the_post();
                    $hero_posts[] = get_post();
                    $hero_ids[]   = get_the_ID();
                }
            }
            wp_reset_postdata();
        } else {
            // 글이 충분히 많을 때는 기존 규칙 유지
            wp_reset_postdata();

            if ( $term instanceof WP_Term && $root_term instanceof WP_Term && (int) $term->term_id !== (int) $root_term->term_id ) {
                // 하위 카테고리 페이지: 해당 카테고리에서 최신 글 3개
                $hero_query = new WP_Query(
                    [
                        'post_type'           => 'post',
                        'post_status'         => 'publish',
                        'posts_per_page'      => 3,
                        'cat'                 => $term->term_id,
                        'no_found_rows'       => true,
                        'ignore_sticky_posts' => true,
                    ]
                );

                if ( $hero_query->have_posts() ) {
                    while ( $hero_query->have_posts() ) {
                        $hero_query->the_post();
                        $hero_posts[] = get_post();
                        $hero_ids[]   = get_the_ID();
                    }
                    wp_reset_postdata();
                }
            } elseif ( $root_term instanceof WP_Term ) {
                // 루트 카테고리 페이지: 하위 카테고리별 최신 글 1개씩
                $hero_children = get_categories(
                    [
                        'hide_empty' => false,
                        'parent'     => $root_term->term_id,
                    ]
                );

                foreach ( $hero_children as $child_cat ) {
                    if ( count( $hero_posts ) >= 3 ) {
                        break;
                    }
                    $child_query = new WP_Query([
                        'post_type'           => 'post',
                        'post_status'         => 'publish',
                        'posts_per_page'      => 1,
                        'cat'                 => $child_cat->term_id,
                        'no_found_rows'       => true,
                        'ignore_sticky_posts' => true,
                    ]);
                    if ( $child_query->have_posts() ) {
                        $child_query->the_post();
                        $hero_posts[] = get_post();
                        $hero_ids[]   = get_the_ID();
                        wp_reset_postdata();
                    }
                }
                // 3개 미만이면, 루트 카테고리+자식 전체에서 중복 없는 최신글로 채워넣기
                if ( count( $hero_posts ) < 3 ) {
                    $fill_needed = 3 - count( $hero_posts );
                    // 루트+자식 최신글 쿼리 (이미 $hero_ids 에 있는 게시글은 제외)
                    $cat_ids = [ (int) $root_term->term_id ];
                    foreach ( $hero_children as $c2 ) {
                        $cat_ids[] = (int) $c2->term_id;
                    }
                    $fill_query = new WP_Query([
                        'post_type'           => 'post',
                        'post_status'         => 'publish',
                        'posts_per_page'      => $fill_needed * 2, // 여유있게 쿼리
                        'category__in'        => $cat_ids,
                        'post__not_in'        => $hero_ids,
                        'no_found_rows'       => true,
                        'ignore_sticky_posts' => true,
                    ]);
                    if ( $fill_query->have_posts() ) {
                        while ( $fill_query->have_posts() && count( $hero_posts ) < 3 ) {
                            $fill_query->the_post();
                            $hero_posts[] = get_post();
                            $hero_ids[]   = get_the_ID();
                        }
                        wp_reset_postdata();
                    }
                }
            }
        }
    } else {
        // 관리자 추천이 1~2개만 있을 때: 같은 카테고리 트리 최신글로 나머지 채움
        $fill_args = $hero_recent_args;
        $fill_args['posts_per_page']      = ( 3 - count( $hero_posts ) ) * 2;
        $fill_args['post__not_in']        = $hero_ids;
        $fill_args['no_found_rows']       = true;
        $fill_args['ignore_sticky_posts'] = true;
        $fill_query = new WP_Query( $fill_args );
        if ( $fill_query->have_posts() ) {
            while ( $fill_query->have_posts() && count( $hero_posts ) < 3 ) {
                $fill_query->the_post();
                $hero_posts[] = get_post();
                $hero_ids[]   = get_the_ID();
            }
            wp_reset_postdata();
        }
    }
}
?>

<div class="layout layout--category" data-category-root-page="<?php echo $is_root_page ? '1' : '0'; ?>">
    <div class="category-shell">
        <!-- LNB -->
        <aside class="category-lnb">
            <nav class="category-lnb-inner">
                <?php foreach ( $lnb_groups as $group ) : ?>
                    <?php
                    $is_current_root = ( $root_term instanceof WP_Term && (int) $group['term_id'] > 0 && (int) $root_term->term_id === (int) $group['term_id'] );
                    $is_current_parent = $is_current_root && $is_root_page;
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
                                    $is_current_child = ( $term instanceof WP_Term && (int) $child['term_id'] > 0 && (int) $term->term_id === (int) $child['term_id'] );
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

        <!-- 우측 메인 영역 (자녀 페이지에서는 상단 타이틀 비노출) -->
        <main class="category-main<?php echo ! $is_root_page ? ' category-main--child' : ''; ?>">
            <?php if ( $is_root_page ) : ?>
            <header class="category-main-header">
                <h1 class="category-main-title"><?php echo esc_html( '추천 아티클' ); ?></h1>
            </header>
            <?php endif; ?>

            <?php if ( $is_root_page && ! empty( $hero_posts ) ) : ?>
                <section class="category-hero-posts">
                    <?php
                    $category_hero_mobile_slides = array_slice( $hero_posts, 0, 3 );
                    $category_hero_mobile_n      = count( $category_hero_mobile_slides );
                    ?>
                    <div class="category-hero-grid category-hero-grid--desktop">
                        <?php if ( ! empty( $hero_posts[0] ) ) : ?>
                            <?php
                            $p      = $hero_posts[0];
                            $cat_nm = borobill_get_child_category_name_for_root( $p->ID, $root_term );
                            ?>
                            <article class="category-hero-main">
                                <span class="category-hero-main-thumb">
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
                                </span>
                                <div class="category-hero-main-text">
                                    <div class="category-hero-main-meta">
                                        <span class="badge badge-small"><?php echo esc_html( $cat_nm ); ?></span>
                                        <span class="meta-date"><?php echo esc_html( borobill_post_date( $p->ID ) ); ?></span>
                                    </div>
                                    <h2 class="category-hero-main-title">
                                        <?php echo esc_html( get_the_title( $p ) ); ?>
                                    </h2>
                                    <?php /* 서브페이지 추천 아티클: 태그 미노출 */ ?>
                                </div>
                                <a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="category-hero-main-link" aria-label="<?php echo esc_attr( get_the_title( $p ) ); ?> 글 보기"></a>
                            </article>
                        <?php endif; ?>

                        <div class="category-hero-side">
                            <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
                                <?php if ( empty( $hero_posts[ $i ] ) ) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <?php
                                $p      = $hero_posts[ $i ];
                                $cat_nm = borobill_get_child_category_name_for_root( $p->ID, $root_term );
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
                                            <span class="meta-date"><?php echo esc_html( borobill_post_date( $p->ID ) ); ?></span>
                                        </div>
                                        <h3 class="category-hero-side-title">
                                            <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
                                                <?php echo esc_html( get_the_title( $p ) ); ?>
                                            </a>
                                        </h3>
                                        <?php /* 서브페이지 추천 아티클: 태그 미노출 */ ?>
                                    </div>
                                </article>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php /* 모바일 768px 미만: 메인 히어로형 단일 영역 슬라이더 */ ?>
                    <?php if ( $category_hero_mobile_n > 0 ) : ?>
                        <div class="category-hero-carousel-shell"
                             role="region"
                             aria-roledescription="carousel"
                             aria-label="<?php echo esc_attr( sprintf( '추천 아티클 슬라이드 %d개', (int) $category_hero_mobile_n ) ); ?>">
                            <div class="category-hero-carousel-viewport">
                                <div id="category-hero-carousel-track" class="category-hero-carousel-track">
                                    <?php foreach ( $category_hero_mobile_slides as $ci => $p_car ) : ?>
                                        <?php
                                        if ( ! $p_car instanceof WP_Post ) {
                                            continue;
                                        }
                                        $cat_nm_car = borobill_get_child_category_name_for_root( $p_car->ID, $root_term );
                                        ?>
                                        <article class="category-hero-main category-hero-carousel-slide<?php echo 0 === (int) $ci ? ' is-active' : ''; ?>" data-slide-index="<?php echo (int) $ci; ?>">
                                            <span class="category-hero-main-thumb">
                                                <?php
                                                if ( has_post_thumbnail( $p_car ) ) {
                                                    echo get_the_post_thumbnail(
                                                        $p_car,
                                                        'large',
                                                        [
                                                            'class' => 'category-hero-main-img',
                                                            'alt'   => esc_attr( get_the_title( $p_car ) ),
                                                        ]
                                                    );
                                                } else {
                                                    ?>
                                                    <img src="<?php echo esc_url( get_template_directory_uri() . '/images/default.png' ); ?>"
                                                         class="category-hero-main-img"
                                                         alt="<?php esc_attr_e( '기본 이미지', 'borobill_theme' ); ?>" />
                                                    <?php
                                                }
                                                ?>
                                            </span>
                                            <div class="category-hero-main-text">
                                                <div class="category-hero-main-meta">
                                                    <span class="badge badge-small"><?php echo esc_html( $cat_nm_car ); ?></span>
                                                    <span class="meta-date"><?php echo esc_html( borobill_post_date( $p_car->ID ) ); ?></span>
                                                </div>
                                                <h2 class="category-hero-main-title">
                                                    <?php echo esc_html( get_the_title( $p_car ) ); ?>
                                                </h2>
                                            </div>
                                            <a href="<?php echo esc_url( get_permalink( $p_car ) ); ?>" class="category-hero-main-link" aria-label="<?php echo esc_attr( get_the_title( $p_car ) ); ?> 글 보기"></a>
                                        </article>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ( $category_hero_mobile_n >= 2 ) : ?>
                                    <div class="category-hero-carousel-pagination" aria-label="<?php esc_attr_e( '추천 아티클 슬라이드 탐색', 'borobill_theme' ); ?>">
                                        <button type="button"
                                                class="category-hero-carousel-pagination__btn category-hero-carousel-pagination__btn--prev"
                                                data-category-hero-prev
                                                aria-controls="category-hero-carousel-track"
                                                aria-label="<?php esc_attr_e( '이전 추천 글', 'borobill_theme' ); ?>">
                                            <span aria-hidden="true">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </span>
                                        </button>
                                        <div class="category-hero-carousel-pagination__counter"
                                             aria-live="polite"
                                             aria-atomic="true"
                                             aria-label="<?php echo esc_attr( sprintf( '슬라이드 %d / %d', 1, (int) $category_hero_mobile_n ) ); ?>">
                                            <span class="category-hero-carousel-pagination__current">1</span>
                                            <span class="category-hero-carousel-pagination__sep" aria-hidden="true">|</span>
                                            <span class="category-hero-carousel-pagination__total"><?php echo (int) $category_hero_mobile_n; ?></span>
                                        </div>
                                        <button type="button"
                                                class="category-hero-carousel-pagination__btn category-hero-carousel-pagination__btn--next"
                                                data-category-hero-next
                                                aria-controls="category-hero-carousel-track"
                                                aria-label="<?php esc_attr_e( '다음 추천 글', 'borobill_theme' ); ?>">
                                            <span aria-hidden="true">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M10 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php
            // 서브/자녀페이지: 타이틀(검색창 자리) + 최신순/추천순 우측 (검색창 제거)
            $is_child_page = ! $is_root_page;
            $child_page_title = '';
            if ( $is_child_page && $term instanceof WP_Term ) {
                $child_page_title = borobill_get_gnb_menu_title_for_term( $term->term_id );
                if ( '' === $child_page_title ) {
                    $child_page_title = $term->name;
                }
            }
            ?>
            <section class="section section-all-posts-header<?php echo $is_child_page ? ' all-posts-header--child' : ''; ?>" aria-labelledby="all-posts-title">
                <div class="section-inner">
                    <div class="all-posts-toolbar">
                        <h2 id="all-posts-title" class="all-posts-title"><?php echo $is_root_page ? esc_html( '전체게시글' ) : esc_html( $child_page_title ?: '전체게시글' ); ?></h2>
                        <div class="section-search section-search--modal all-posts-search">
                            <label class="screen-reader-text" for="all-posts-search-input">검색어</label>
                            <input type="search"
                                   id="all-posts-search-input"
                                   class="search-field"
                                   placeholder="검색어를 입력해주세요"
                                   readonly
                                   data-open-search
                                   aria-label="검색 모달 열기" />
                            <button type="button" class="search-icon-btn search-btn-text" aria-label="검색" data-open-search>검색</button>
                        </div>
                        <div class="all-posts-toolbar-actions">
                            <div class="all-posts-sort" role="group" aria-label="정렬">
                                <button type="button" class="sort-btn active" data-sort="latest" aria-pressed="true">최신순</button>
                                <button type="button" class="sort-btn" data-sort="recommended" aria-pressed="false">추천순</button>
                            </div>
                            <!-- <div class="post-view-mode" role="group" aria-label="게시글 보기 방식">
                                <button type="button" class="post-view-mode__btn" data-view-mode="photo" aria-pressed="false" aria-label="사진만 보기">
                                    <iconify-icon class="post-view-mode__icon" icon="duo-icons:app" width="1em" height="1em" aria-hidden="true"></iconify-icon>
                                </button>
                                <button type="button" class="post-view-mode__btn is-active" data-view-mode="list" aria-pressed="true" aria-label="목록형 보기">
                                    <iconify-icon class="post-view-mode__icon post-view-mode__icon--flip-x" icon="fa:th-list" width="1em" height="1em" aria-hidden="true"></iconify-icon>
                                </button>
                                <button type="button" class="post-view-mode__btn" data-view-mode="card" aria-pressed="false" aria-label="카드형 보기">
                                    <iconify-icon class="post-view-mode__icon" icon="ic:baseline-view-stream" width="1em" height="1em" aria-hidden="true"></iconify-icon>
                                </button>
                            </div> //-->

                            <div class="post-view-mode" role="group" aria-label="게시글 보기 방식">
                                 <button type="button" class="post-view-mode__btn<?php echo $is_root_page ? ' is-active' : ''; ?>" data-view-mode="list" aria-pressed="<?php echo $is_root_page ? 'true' : 'false'; ?>" aria-label="목록형 보기">
                                      <iconify-icon class="post-view-mode__icon post-view-mode__icon--flip-x" icon="fa:th-list" width="1em" height="1em" aria-hidden="true"></iconify-icon>
                                </button>
                                 <button type="button" class="post-view-mode__btn<?php echo $is_root_page ? '' : ' is-active'; ?>" data-view-mode="card" aria-pressed="<?php echo $is_root_page ? 'false' : 'true'; ?>" aria-label="카드형 보기">
                                    <iconify-icon class="post-view-mode__icon" icon="ic:baseline-view-stream" width="1em" height="1em" aria-hidden="true"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php
            // GNB 루트 기준 필터 + 검색 (해당 카테고리 트리 글만 리스트에 노출되도록 데이터 전달)
            $filter_items = array();
            if ( $root_term instanceof WP_Term ) {
                foreach ( $lnb_groups as $group ) {
                    if ( (int) $group['term_id'] !== (int) $root_term->term_id ) {
                        continue;
                    }
                    foreach ( $group['children'] as $child ) {
                        if ( empty( $child['term_id'] ) ) {
                            continue;
                        }
                        $child_term = get_term( (int) $child['term_id'], 'category' );
                        $slug       = ( $child_term && ! is_wp_error( $child_term ) ) ? $child_term->slug : sanitize_title( $child['title'] );
                        $filter_items[] = array(
                            'label'   => $child['title'],
                            'slug'    => $slug,
                            'cat_ids' => array( (int) $child['term_id'] ),
                        );
                    }
                    break;
                }
            }
            $filter_args = [
                'root_term'    => $root_term,
                'filter_items' => $filter_items,
            ];
            if ( ! $is_root_page && $term instanceof WP_Term ) {
                $filter_args['initial_group'] = $term->slug;
            }
            get_template_part( 'template-parts/category-filter', null, $filter_args );
            ?>
            <?php get_template_part( 'template-parts/main-list' ); ?>

        </main>
    </div>

    <?php get_template_part( 'template-parts/bottom-cta-banner' ); ?>
</div>

<?php get_footer(); ?>

