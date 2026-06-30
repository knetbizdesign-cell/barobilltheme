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
                        <p class="article-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
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
        <div class="pagination">
            <?php
            echo paginate_links(
                [
                    'prev_text' => '&lsaquo;',
                    'next_text' => '&rsaquo;',
                    'type'      => 'list',
                ]
            );
            ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>




