<?php
/**
 * Suchformular mit sichtbarem Label für Screenreader.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
$id = wp_unique_id('mt-search-');
?>
<form role="search" method="get" class="mt-search" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="mt-visually-hidden" for="<?php echo esc_attr($id); ?>">Suchen</label>
    <input id="<?php echo esc_attr($id); ?>" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Suchen">
    <?php if (class_exists('WooCommerce')) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
    <button type="submit" class="mt-btn">Suchen</button>
</form>
