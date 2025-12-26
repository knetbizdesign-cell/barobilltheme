<?php
/**
 * 메인 게시글 리스트
 *
 * 메인 쿼리(loop)를 사용하여 이미지와 같이 카드 + 썸네일 형태로 출력.
 */
?>

<section class="section section-list">
    <div class="section-inner">
        <div class="post-list">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $cats      = get_the_category();
                    $data_attr = 'etc';

                    /**
                     * 이 글이 속한 카테고리 + 그 상위 카테고리들의 slug 를 모두 모은다.
                     *
                     * 예)
                     *  - 카테고리: 사업자 등록(부모: 초보사업자), 세무·비즈니스
                     *    => data-category="사업자등록 초보사업자 세무-비즈니스"
                     *
                     * 이렇게 하면 글에 직접 설정한 카테고리(or 부모 카테고리) 이름과
                     * 상단 필터 버튼의 slug 가 확실히 매칭된다.
                     */
                    if ( $cats ) {
                        $slugs = [];

                        foreach ( $cats as $cat ) {
                            // 현재 카테고리부터 루트까지 모두 포함
                            $current = $cat;
                            while ( $current ) {
                                if ( ! is_wp_error( $current ) ) {
                                    $slugs[] = $current->slug;
                                }

                                if ( $current->parent ) {
                                    $current = get_category( $current->parent );
                                } else {
                                    break;
                                }
                            }
                        }

                        if ( $slugs ) {
                            $slugs     = array_unique( $slugs );
                            $data_attr = implode( ' ', $slugs );
                        }
                    }
                    ?>
                    <article class="article-card"
                             data-category="<?php echo esc_attr( $data_attr ); ?>">
                        <div class="article-info">
                            <div class="article-meta">
                                <span class="badge badge-small">
                                    <?php echo $cats ? esc_html( $cats[0]->name ) : '카테고리'; ?>
                                </span>
                                <span class="meta-date">18시간 전</span>
                            </div>
                            <h3 class="article-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                        <p class="featured-subdesc" style="margin-top:8px; color:#111; font-size:16px; font-style:normal; line-height:150%;">
    정발행과 역발행 각각의 개념과 차이를 정리하고, 거래 유형별로 세금계산서 발행 주체를 명확히 구분할 수 있는 가이드를 제공합니다.
</p>
                    </div>

                        <div class="article-thumb-wrap">
                            <a href="<?php the_permalink(); ?>">
                                <?php
                                // featured image from post (list card)
                                if ( has_post_thumbnail() ) :
                                    ?>
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
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php
            the_posts_pagination( [
                'mid_size'           => 1,
                'prev_text'          => '&lsaquo;',
                'next_text'          => '&rsaquo;',
                'screen_reader_text' => '게시글 페이지',
            ] );
            ?>
        </div>
    </div>
</section>


