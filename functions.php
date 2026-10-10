<?php
/**
 * MichaThemeV5 – Einstiegspunkt.
 *
 * @package MichaThemeV5
 */

defined('ABSPATH') || exit;

define('MT_VERSION', '0.2.0');

require_once __DIR__ . '/inc/options.php';
require_once __DIR__ . '/inc/license.php';
require_once __DIR__ . '/inc/customizer.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/modules.php';
