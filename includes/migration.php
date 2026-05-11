<?php
/**
 * MenuX Free — Data Migration
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_maybe_migrate() {
    if (get_option('menux_migrated_to_v7')) return;

    $logged = get_option('menux_menu_logged', array());
    $guest  = get_option('menux_menu_guest', array());
    $merged = array();

    if (!empty($logged) && is_array($logged)) {
        foreach ($logged as $item) {
            $item['visibility'] = 'auth';
            $merged[] = $item;
        }
    }
    if (!empty($guest) && is_array($guest)) {
        foreach ($guest as $item) {
            $item['visibility'] = 'non-auth';
            $merged[] = $item;
        }
    }
    if (!empty($merged)) {
        update_option('menux_menu_items', $merged);
    }
    delete_option('menux_menu_logged');
    delete_option('menux_menu_guest');
    update_option('menux_migrated_to_v7', true);
}
add_action('admin_init', 'menux_maybe_migrate');

