<?php
/**
 * MenuX Free — WP Nav Menu Integration
 * Intercepts wp_nav_menu() calls and replaces them with MenuX output
 * when a mapping is configured in Settings > WP Integration.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Track every pre_wp_nav_menu call for admin-bar diagnostics.
$GLOBALS['menux_nav_log'] = array();

add_filter( 'pre_wp_nav_menu', 'menux_pre_wp_nav_menu', 10, 2 );
function menux_pre_wp_nav_menu( $output, $args ) {
    $replacements   = get_option( 'menux_wp_nav_replacements', array() );
    $theme_location = isset( $args->theme_location ) ? $args->theme_location : '';
    $mapped         = ! empty( $replacements[ $theme_location ] ) ? $replacements[ $theme_location ] : null;

    $log_entry = array( 'location' => $theme_location, 'mapped' => $mapped, 'result' => 'passthrough' );

    if ( $theme_location && $mapped ) {
        $html = menux_render_shortcode( array( 'location' => $mapped ) );
        if ( is_string( $html ) && $html !== '' ) {
            $log_entry['result'] = 'replaced';
            $GLOBALS['menux_nav_log'][] = $log_entry;
            return $html;
        }
        $log_entry['result'] = 'empty-output';
    }

    $GLOBALS['menux_nav_log'][] = $log_entry;
    return $output;
}

// Admin-bar indicator (visible to admins on frontend only).
add_action( 'wp_before_admin_bar_render', 'menux_admin_bar_nav_debug' );
function menux_admin_bar_nav_debug() {
    if ( ! current_user_can( 'manage_options' ) || is_admin() ) return;
    global $wp_admin_bar;

    $log = $GLOBALS['menux_nav_log'];

    if ( empty( $log ) ) {
        $wp_admin_bar->add_node( array(
            'id'    => 'menux-nav-debug',
            'title' => '🔴 MenuX WP: filter never called — theme may bypass wp_nav_menu()',
            'href'  => admin_url( 'admin.php?page=menux' ),
        ) );
        return;
    }

    $has_replaced = false;
    foreach ( $log as $e ) {
        if ( $e['result'] === 'replaced' ) { $has_replaced = true; break; }
    }
    $icon = $has_replaced ? '🟢' : '🟡';
    $wp_admin_bar->add_node( array(
        'id'    => 'menux-nav-debug',
        'title' => $icon . ' MenuX WP (' . count( $log ) . ' calls)',
        'href'  => admin_url( 'admin.php?page=menux' ),
    ) );

    foreach ( $log as $i => $e ) {
        if ( $e['result'] === 'replaced' )          { $label = '✅ replaced'; }
        elseif ( $e['result'] === 'empty-output' )  { $label = '⚠ empty output'; }
        else                                         { $label = '— passthrough'; }
        $loc = ( $e['location'] !== '' ) ? $e['location'] : '(no theme_location)';
        $wp_admin_bar->add_node( array(
            'id'     => 'menux-nav-debug-' . $i,
            'parent' => 'menux-nav-debug',
            'title'  => $loc . ' → ' . ( $e['mapped'] ?? 'not mapped' ) . ' · ' . $label,
        ) );
    }
}
