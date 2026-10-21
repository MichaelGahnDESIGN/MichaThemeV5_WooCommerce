<?php
/**
 * Template Name: Seitenbuilder (volle Breite)
 * Template Post Type: page
 *
 * Ohne Rahmen, ideal für Divi 5 und Elementor.
 *
 * @package MichaThemeV5
 */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) {
    the_post();
    the_content();
}
get_footer();
