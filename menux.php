<?php
/**
 * Plugin Name: MenuX Pro
 * Plugin URI:  
 * Description: Advanced menu management via Shortcode [menux]. Supports hamburger style, icons, role-based visibility, multilingual (WPML, Polylang, TranslatePress) and advanced layouts.
 * Version:     2.1.1
 * Author:      Max software
 * Author URI:  
 * Text Domain: menux
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MENUX_VERSION',  '2.1.1' );
define( 'MENUX_DIR',      plugin_dir_path( __FILE__ ) );
define( 'MENUX_URL',      plugin_dir_url( __FILE__ ) );
define( 'MENUX_BASENAME', plugin_basename( __FILE__ ) );

register_activation_hook( __FILE__, 'menux_maybe_migrate' );
register_activation_hook( __FILE__, 'menux_stats_create_table' );

require_once MENUX_DIR . 'includes/helpers.php';
require_once MENUX_DIR . 'includes/migration.php';
require_once MENUX_DIR . 'includes/multilingual.php';
require_once MENUX_DIR . 'includes/style-defaults.php';
require_once MENUX_DIR . 'includes/css-generator.php';
require_once MENUX_DIR . 'includes/assets.php';
require_once MENUX_DIR . 'includes/stats.php';
require_once MENUX_DIR . 'includes/import-export.php';
require_once MENUX_DIR . 'includes/shortcode.php';

if ( is_admin() ) {
    require_once MENUX_DIR . 'admin/admin-page.php';
    require_once MENUX_DIR . 'admin/builder.php';
    require_once MENUX_DIR . 'admin/style-panel.php';
    require_once MENUX_DIR . 'admin/stats-page.php';
}
