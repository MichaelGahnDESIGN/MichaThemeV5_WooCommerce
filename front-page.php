<?php
/**
 * Startseite: Hero-Bereich (Option) und Inhalt der Seite (Divi 5 und Elementor möglich).
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
$img = mt_opt('hero_image');
?>
<?php if (mt_opt('hero_style') !== 'none') : ?>
    <section class="mt-container">
        <div class="mt-hero<?php echo $img && mt_opt('hero_style') === 'image' ? ' mt-hero--image' : ''; ?>"<?php echo $img && mt_opt('hero_style') === 'image' ? ' style="' . esc_attr('background-image:linear-gradient(90deg,var(--mt-surface) 30%,transparent),url(' . esc_url($img) . ');background-size:cover;background-position:center') . '"' : ''; ?>>
            <div>
                <h1 class="mt-hero__title"><?php echo esc_html(mt_opt('hero_title')); ?></h1>
                <?php if (mt_opt('hero_text') !== '') : ?><p class="mt-hero__text"><?php echo esc_html(mt_opt('hero_text')); ?></p><?php endif; ?>
                <?php if (mt_opt('hero_cta_label') !== '') : ?><a class="mt-btn" href="<?php echo esc_url(mt_opt('hero_cta_url')); ?>"><?php echo esc_html(mt_opt('hero_cta_label')); ?></a><?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<div class="mt-container">
    <?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
</div>
<?php get_footer();
