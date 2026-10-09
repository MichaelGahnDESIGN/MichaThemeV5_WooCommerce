<?php
/**
 * Seiten (Divi 5 und Elementor rendern über the_content()).
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="mt-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('mt-entry'); ?>>
            <h1><?php the_title(); ?></h1>
            <?php the_content(); ?>
        </article>
    <?php endwhile; ?>
</div>
<?php get_footer();
