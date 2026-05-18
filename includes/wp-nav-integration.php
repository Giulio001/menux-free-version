<?php
/**
 * MenuX Free — WP Nav Menu Integration
 * Intercepts wp_nav_menu() calls and replaces them with MenuX output
 * when a mapping is configured in Settings > WP Integration.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'pre_wp_nav_menu', 'menux_pre_wp_nav_menu', 10, 2 );
function menux_pre_wp_nav_menu( $output, $args ) {
    $replacements = get_option( 'menux_wp_nav_replacements', array() );
    if ( empty( $replacements ) ) return $output;

    $theme_location = isset( $args->theme_location ) ? $args->theme_location : '';
    if ( $theme_location && ! empty( $replacements[ $theme_location ] ) ) {
        $html = menux_render_shortcode( array( 'location' => $replacements[ $theme_location ] ) );
        // Only replace if MenuX produced actual output — avoids hiding the original menu
        // when the mapped location has no items configured yet.
        if ( is_string( $html ) && $html !== '' ) return $html;
    }

    return $output;
}
