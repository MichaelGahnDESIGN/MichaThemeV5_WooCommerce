<?php
/**
 * Suchergebnisse.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="mt-container">
    <h1>Suche: <?php echo esc_html(get_search_query()); ?></h1>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('mt-entry'); ?>><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
    <?php endwhile; the_posts_pagination(); else : ?>
        <p>Nichts gefunden. Versuchen Sie einen anderen Suchbegriff.</p>
    <?php endif; ?>
</div>
<?php get_footer();
