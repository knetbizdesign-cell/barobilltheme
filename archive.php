<?php
/**
 * Archive Template
 */

get_header();
?>

<section class="archive-hero">
    <div class="container">
        <h1 class="archive-title">
            <?php
            if (is_category()) {
                single_cat_title();
            } elseif (is_tag()) {
                single_tag_title();
            } elseif (is_author()) {
                the_author();
            } elseif (is_date()) {
                echo get_the_date('Y년 m월');
            } else {
                echo '아카이브';
            }
            ?>
        </h1>
        <?php
        if (is_category() || is_tag()) {
            $description = term_description();
            if ($description) {
                echo '<p class="archive-description">' . $description . '</p>';
            }
        }
        ?>
    </div>
</section>

<section class="main-articles">
    <div class="container">
        <div class="articles-grid">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                ?>
                <article class="article-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium', array('class' => 'article-thumbnail')); ?>
                        </a>
                    <?php else : ?>
                        <div class="article-thumbnail"></div>
                    <?php endif; ?>
                    <div class="article-content">
                        <?php
                        $categories = get_the_category();
                        if (!empty($categories)) {
                            echo '<span class="article-category">' . esc_html($categories[0]->name) . '</span>';
                        }
                        ?>
                        <h3 class="article-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php
                        $subtitle = borobill_get_post_subtitle( get_the_ID() );
                        $subtext  = '' !== $subtitle ? $subtitle : borobill_get_post_summary_text( get_the_ID(), 15 );
                        ?>
                        <?php if ( '' !== $subtext ) : ?>
                            <p class="article-subtitle"><a class="article-subtitle__link" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( $subtext ); ?></a></p>
                        <?php endif; ?>

                        <?php
                        $tags = get_the_tags();
                        if ( $tags && is_array( $tags ) ) :
                            $tags = array_slice( $tags, 0, 3 );
                            ?>
                            <div class="article-tags" aria-label="태그">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <span class="article-tag" draggable="false">
                                        <span class="article-tag__text"><?php echo esc_html( $tag->name ); ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="article-meta">
                            <span class="article-date"><?php echo borobill_post_date(); ?></span>
                        </div>
                    </div>
                </article>
                <?php
                endwhile;
            else :
                ?>
                <div class="empty-state">
                    <h2>포스트가 없습니다</h2>
                    <p>이 카테고리에 게시된 포스트가 없습니다.</p>
                </div>
                <?php
            endif;
            ?>
        </div>
        
        <!-- Pagination -->
        <?php
        global $wp_query;
        $total_pages = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1;

        // 페이지 수가 5 이하이면 1~마지막까지 모두 노출 (… 없이)
        $mid_size = ( $total_pages <= 5 ) ? 5 : 1;
        $end_size = ( $total_pages <= 5 ) ? 5 : 1;

        $archive_links = paginate_links(
            [
                'mid_size'  => $mid_size,
                'end_size'  => $end_size,
                'prev_text' => '<span class="page-icon page-icon--prev" aria-hidden="true"></span>',
                'next_text' => '<span class="page-icon page-icon--next" aria-hidden="true"></span>',
            ]
        );
        if ( $archive_links ) :
            ?>
            <div class="pagination">
                <?php echo $archive_links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>

        <?php get_template_part( 'template-parts/bottom-cta-banner' ); ?>
    </div>
</section>

<?php get_footer(); ?>




