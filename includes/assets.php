<?php
/**
 * Giuliomax Menu Builder — Assets
 * Enqueues frontend and admin scripts/styles.
 *
 * @package GiuliomaxMenuBuilder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'menux_enqueue_frontend' );
function menux_enqueue_frontend() {
    $style = get_option( 'menux_style', array() );
    if ( ! empty( $style['google_font'] ) ) {
        $gf_slug = urlencode( $style['google_font'] );
        wp_enqueue_style( 'menux-gfont', "https://fonts.googleapis.com/css2?family={$gf_slug}:wght@300;400;500;600;700&display=swap", array(), MENUX_VERSION );
    }
    wp_enqueue_style( 'menux-fa6', MENUX_URL . 'assets/fa6/css/all.min.css', array(), '6.5.2' );

    // Dynamic CSS generated from saved options — must run here, before wp_head(), not inside the shortcode callback.
    $generated_css = menux_generate_css( $style );
    wp_add_inline_style( 'menux-fa6', wp_strip_all_tags( $generated_css ) );

    wp_register_script( 'menux-frontend', MENUX_URL . 'assets/js/frontend.js', array(), MENUX_VERSION, true );
}

add_action( 'admin_head', 'menux_remove_admin_footer' );
function menux_remove_admin_footer() {
    $screen = get_current_screen();
    if ( ! $screen ) return;
    if ( in_array( $screen->id, array( 'toplevel_page_menux' ), true ) ) {
        add_filter( 'admin_footer_text', '__return_empty_string', 99 );
        add_filter( 'update_footer',     '__return_empty_string', 99 );
    }
}

add_action( 'admin_enqueue_scripts', 'menux_admin_assets' );
function menux_admin_assets( $hook ) {
    if ( $hook !== 'toplevel_page_menux' ) return;
    wp_enqueue_script( 'jquery-ui-sortable' );
    wp_enqueue_media();
    wp_enqueue_style( 'menux-fa6-admin', MENUX_URL . 'assets/fa6/css/all.min.css', array(), '6.5.2' );
    wp_enqueue_style( 'menux-admin-css', MENUX_URL . 'admin/css/admin.css', array(), MENUX_VERSION );
    wp_enqueue_script( 'menux-admin-js', MENUX_URL . 'admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), MENUX_VERSION, true );
}
