<?php
/**
 * Page template for borobill_theme
 */

get_header();
?>

<div class="layout">
    <main class="page-main">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <section class="section page-section">
                    <div class="section-inner">
                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-entry' ); ?>>
                            <h1 class="page-title"><?php the_title(); ?></h1>

                            <div class="page-content">
                                <?php the_content(); ?>
                            </div>
                        </article>
                    </div>
                </section>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();




