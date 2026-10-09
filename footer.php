<?php
/**
 * Fußbereich.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
?>
</main>
<?php
$trust = array_filter([mt_opt('trust_1'), mt_opt('trust_2'), mt_opt('trust_3')]);
if (mt_opt('footer_trust') && $trust) : ?>
    <section class="mt-section" aria-label="Unsere Versprechen">
        <ul class="mt-trust"><?php foreach ($trust as $t) : ?><li><?php echo esc_html($t); ?></li><?php endforeach; ?></ul>
    </section>
<?php endif; ?>
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <nav aria-label="Rechtliches">
            <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'menu_class' => 'mt-menu mt-menu--footer', 'fallback_cb' => false]); ?>
        </nav>
        <p class="site-footer__copy">&copy; <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?></p>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
