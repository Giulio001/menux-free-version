<?php
/**
 * MenuX Free — Multilingual Support
 * Auto-detects WPML, Polylang, TranslatePress, Multilingual Press.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_get_supported_languages( $force_refresh = false ) {
    $cached = get_transient( 'menux_supported_languages' );
    if ( $cached !== false && ! $force_refresh ) {
        return $cached;
    }

    $langs = array();

    // WPML
    if ( defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'icl_get_languages' ) ) {
        $wpml = icl_get_languages( 'skip_missing=0' );
        if ( ! empty( $wpml ) && is_array( $wpml ) ) {
            foreach ( $wpml as $l ) {
                if ( empty( $l['language_code'] ) ) continue;
                $langs[] = array(
                    'code'  => sanitize_text_field( $l['language_code'] ),
                    'label' => isset( $l['translated_name'] ) ? sanitize_text_field( $l['translated_name'] ) : $l['language_code'],
                );
            }
        }
    }

    // Polylang
    if ( empty( $langs ) && function_exists( 'pll_languages_list' ) ) {
        $pll = pll_languages_list( array( 'fields' => array() ) );
        if ( ! empty( $pll ) && is_array( $pll ) ) {
            foreach ( $pll as $l ) {
                $langs[] = array(
                    'code'  => isset( $l->locale ) ? sanitize_text_field( $l->locale ) : sanitize_text_field( $l->slug ),
                    'label' => isset( $l->name )   ? sanitize_text_field( $l->name )   : sanitize_text_field( $l->slug ),
                );
            }
        }
    }

    // TranslatePress
    if ( empty( $langs ) && class_exists( 'TRP_Translate_Press' ) ) {
        $trp = get_option( 'trp_settings', array() );
        if ( ! empty( $trp['translation-languages'] ) && is_array( $trp['translation-languages'] ) ) {
            $names = isset( $trp['translation-languages-names'] ) ? $trp['translation-languages-names'] : array();
            foreach ( $trp['translation-languages'] as $i => $locale ) {
                $langs[] = array(
                    'code'  => sanitize_text_field( $locale ),
                    'label' => isset( $names[ $i ] ) ? sanitize_text_field( $names[ $i ] ) : sanitize_text_field( $locale ),
                );
            }
        }
    }

    // Multilingual Press
    if ( empty( $langs ) && function_exists( 'mlp_get_available_languages' ) ) {
        $mlp = mlp_get_available_languages();
        if ( ! empty( $mlp ) && is_array( $mlp ) ) {
            foreach ( $mlp as $locale => $name ) {
                $langs[] = array(
                    'code'  => sanitize_text_field( $locale ),
                    'label' => sanitize_text_field( $name ),
                );
            }
        }
    }

    // Fallback: site locale
    if ( empty( $langs ) ) {
        $locale  = get_locale();
        $langs[] = array( 'code' => $locale, 'label' => $locale );
    }

    set_transient( 'menux_supported_languages', $langs, HOUR_IN_SECONDS );
    return $langs;
}

/* AJAX: force-refresh languages from admin panel */
add_action( 'wp_ajax_menux_reload_languages', 'menux_ajax_reload_languages' );
function menux_ajax_reload_languages() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    check_ajax_referer( 'menux_reload_languages_nonce', 'nonce' );
    delete_transient( 'menux_supported_languages' );
    $langs = menux_get_supported_languages( true );
    wp_send_json_success( $langs );
}
