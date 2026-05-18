<?php
/**
 * MenuX Free — WP Nav Menu Integration
 * Intercepts wp_nav_menu() calls and replaces them with MenuX output
 * when a mapping is configured in Settings > WP Integration.
 * Note: only works with classic PHP themes that call wp_nav_menu() directly.
 * Themes using custom header builders (e.g. Astra Header Builder) bypass this.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'pre_wp_nav_menu', 'menux_pre_wp_nav_menu', 10, 2 );
function menux_pre_wp_nav_menu( $output, $args ) {
    $replacements   = get_option( 'menux_wp_nav_replacements', array() );
    if ( empty( $replacements ) ) return $output;

    $theme_location = isset( $args->theme_location ) ? $args->theme_location : '';
    if ( $theme_location && ! empty( $replacements[ $theme_location ] ) ) {
        $html = menux_render_shortcode( array( 'location' => $replacements[ $theme_location ] ) );
        if ( is_string( $html ) && $html !== '' ) return $html;
    }

    return $output;
}
