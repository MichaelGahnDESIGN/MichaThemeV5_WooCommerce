<?php
/**
 * Rahmen für alle WooCommerce-Seiten.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
?>
<div class="mt-container mt-woo">
    <?php woocommerce_content(); ?>
</div>
<?php get_footer();
