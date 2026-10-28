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
            <?php $mtLegal = mt_legal_footer_items(); if ($mtLegal || mt_opt('legal_consent_button')) : ?>
                <ul class="mt-menu mt-menu--footer mt-legal-links">
                    <?php foreach ($mtLegal as $it) : ?>
                        <li><a<?php echo $it['class'] !== '' ? ' class="' . esc_attr($it['class']) . '"' : ''; ?> href="<?php echo esc_url($it['url']); ?>"><?php echo esc_html($it['label']); ?></a></li>
                    <?php endforeach; ?>
                    <?php if (mt_opt('legal_consent_button')) : ?>
                        <li data-mt-consent-item hidden><button type="button" class="mt-linkbtn" data-mt-consent-open>Cookie-Einstellungen</button></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </nav>
        <p class="site-footer__copy">&copy; <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?></p>
        <?php mt_mode_toggle_row('footer'); ?>
        <?php mt_notification_center_render('footer'); ?>
    </div>
</footer>
<?php wp_footer(); ?>
<?php foreach (['floating-top-left', 'floating-top-right', 'floating-bottom-left', 'floating-bottom-right'] as $placement) { mt_notification_center_render($placement); } ?>
</body>
</html>
