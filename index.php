<?php
/**
 * Standard-Vorlage (Blog, Archive).
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="mt-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('mt-entry'); ?>>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <?php the_excerpt(); ?>
        </article>
    <?php endwhile; the_posts_pagination(); else : ?>
        <p>Keine Inhalte gefunden.</p>
    <?php endif; ?>
</div>
<?php get_footer();
