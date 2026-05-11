<?php
/**
 * MenuX Pro — Statistics
 * Database table, click tracking, and query helpers.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_stats_create_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'menux_stats';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        item_key VARCHAR(255) NOT NULL,
        item_label VARCHAR(255) NOT NULL DEFAULT '',
        item_url VARCHAR(500) NOT NULL DEFAULT '',
        clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_lang VARCHAR(10) NOT NULL DEFAULT '',
        is_logged_in TINYINT(1) NOT NULL DEFAULT 0,
        device_type VARCHAR(10) NOT NULL DEFAULT '',
        referrer VARCHAR(500) NOT NULL DEFAULT '',
        user_role VARCHAR(50) NOT NULL DEFAULT '',
        country VARCHAR(5) NOT NULL DEFAULT '',
        PRIMARY KEY (id),
        KEY item_key (item_key(191)),
        KEY clicked_at (clicked_at),
        KEY device_type (device_type),
        KEY country (country)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // ──── MIGRAZIONE: aggiungi colonne se mancano (upgrade da versione precedente) ────
    $existing_columns = $wpdb->get_col("DESCRIBE $table", 0);
    $new_cols = array(
        'device_type' => "VARCHAR(10) NOT NULL DEFAULT ''",
        'referrer'    => "VARCHAR(500) NOT NULL DEFAULT ''",
        'user_role'   => "VARCHAR(50) NOT NULL DEFAULT ''",
        'country'     => "VARCHAR(5) NOT NULL DEFAULT ''",
    );
    foreach ($new_cols as $col => $def) {
        if (!in_array($col, $existing_columns)) {
            $wpdb->query("ALTER TABLE $table ADD COLUMN $col $def");
        }
    }
}
add_action('admin_init', 'menux_stats_create_table');

add_action('wp_ajax_menux_track_click',        'menux_ajax_track_click');
add_action('wp_ajax_nopriv_menux_track_click', 'menux_ajax_track_click');
function menux_ajax_track_click() {
    // ─── VALIDAZIONE NONCE INTELLIGENTE ───
    // Per gli utenti loggati il nonce funziona sempre (no cache pubblica → token fresco).
    // Per gli ospiti il nonce viene cachato dai plugin di cache (WP Rocket, LiteSpeed, ecc.):
    // dopo 12-24h diventa invalido per tutti i visitatori e il tracking fallisce silenziosamente.
    // → Validiamo nonce solo se loggati. Per ospiti applichiamo rate-limit + validazione input.
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (is_user_logged_in()) {
        if (!wp_verify_nonce($nonce, 'menux_track_nonce')) {
            wp_send_json_error('invalid_nonce', 403);
        }
    } else {
        // Verifica soft: se il nonce è valido ok, altrimenti applichiamo rate-limit per IP
        $nonce_ok = $nonce && wp_verify_nonce($nonce, 'menux_track_nonce');
        if (!$nonce_ok) {
            $ip       = menux_get_client_ip();
            $rl_key   = 'menux_rl_' . md5($ip);
            $hits     = (int) get_transient($rl_key);
            // Max 30 click/minuto per IP per gli ospiti senza nonce valido (anti-flood)
            if ($hits >= 30) {
                wp_send_json_error('rate_limited', 429);
            }
            set_transient($rl_key, $hits + 1, MINUTE_IN_SECONDS);
        }
    }

    // ─── VALIDAZIONE INPUT ───
    $item_key = isset($_POST['item_key']) ? sanitize_text_field($_POST['item_key']) : '';
    if (empty($item_key) || strlen($item_key) > 255) {
        wp_send_json_error('invalid_item_key', 400);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';

    // ──── FEATURE 13: device detection ────
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $device_type = 'desktop';
    if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $ua)) {
        $device_type = 'mobile';
    } elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) {
        $device_type = 'tablet';
    }

    // ──── FEATURE 13: referrer ────
    $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
    if (strlen($referrer) > 500) $referrer = substr($referrer, 0, 500);

    // ──── FEATURE 13: user role ────
    $user_role = '';
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $user_role = !empty($user->roles) ? $user->roles[0] : '';
    }

    // ──── FEATURE 13: country via GeoIP (lightweight) ────
    $country = sanitize_text_field($_POST['country'] ?? '');
    if (strlen($country) > 5) $country = substr($country, 0, 5);

    $wpdb->insert($table, array(
        'item_key'     => $item_key,
        'item_label'   => sanitize_text_field($_POST['item_label'] ?? ''),
        'item_url'     => esc_url_raw($_POST['item_url']           ?? ''),
        'user_lang'    => sanitize_text_field($_POST['user_lang']  ?? ''),
        'is_logged_in' => is_user_logged_in() ? 1 : 0,
        'device_type'  => $device_type,
        'referrer'     => $referrer,
        'user_role'    => $user_role,
        'country'      => $country,
    ), array('%s','%s','%s','%s','%d','%s','%s','%s','%s'));
    wp_send_json_success();
}

/**
 * Recupera l'IP del client in modo sicuro (gestisce reverse-proxy comuni).
 */
function menux_get_client_ip() {
    $candidates = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // X-Forwarded-For può contenere una lista, prendi il primo
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $ip = filter_var($ip, FILTER_VALIDATE_IP);
            if ($ip) return $ip;
        }
    }
    return '0.0.0.0';
}

add_action('wp_ajax_menux_reset_stats', 'menux_ajax_reset_stats');
function menux_ajax_reset_stats() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_ajax_referer('menux_reset_stats_nonce', 'nonce');
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}menux_stats");
    wp_send_json_success();
}

function menux_get_top_clicks($limit = 20, $days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $wpdb->get_results($wpdb->prepare(
        "SELECT item_key, item_label, item_url,
                COUNT(*) as total_clicks,
                SUM(is_logged_in) as logged_clicks,
                COUNT(*)-SUM(is_logged_in) as guest_clicks,
                MAX(clicked_at) as last_click
         FROM $table WHERE clicked_at >= %s
         GROUP BY item_key ORDER BY total_clicks DESC LIMIT %d",
        $since, $limit
    ));
}

function menux_get_daily_clicks($days = 14) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(clicked_at) as day, COUNT(*) as clicks FROM $table WHERE clicked_at >= %s GROUP BY DATE(clicked_at) ORDER BY day ASC",
        $since
    ));
}

function menux_get_total_clicks($days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE clicked_at >= %s", $since));
}
