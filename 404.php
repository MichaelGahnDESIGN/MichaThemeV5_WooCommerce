<?php
/**
 * Seite nicht gefunden.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="mt-container">
    <h1>Seite nicht gefunden</h1>
    <p>Die Seite gibt es nicht mehr oder die Adresse ist falsch.</p>
    <?php get_search_form(); ?>
    <p><a class="mt-btn" href="<?php echo esc_url(home_url('/')); ?>">Zur Startseite</a></p>
</div>
<?php get_footer();
