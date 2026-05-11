<?php
/**
 * MenuX Pro — Import / Export
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_menux_export_config', 'menux_ajax_export_config');
function menux_ajax_export_config() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_ajax_referer('menux_export_config_nonce', 'nonce');

    $config = array(
        'version'      => '8.1',
        'menu_items'   => get_option('menux_menu_items', array()),
        'style'        => get_option('menux_style', array()),
    );
    wp_send_json_success($config);
}

add_action('wp_ajax_menux_import_config', 'menux_ajax_import_config');
function menux_ajax_import_config() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_ajax_referer('menux_import_config_nonce', 'nonce');

    $raw = isset($_POST['config']) ? base64_decode(sanitize_text_field($_POST['config'])) : '';
    if (empty($raw)) { wp_send_json_error('No data received.'); }

    $config = json_decode($raw, true);
    if (!is_array($config)) { wp_send_json_error('Invalid JSON file.'); }

    if (isset($config['menu_items']) && is_array($config['menu_items'])) {
        $sanitized = array();
        foreach ($config['menu_items'] as $item) {
            $type = sanitize_text_field($item['type'] ?? '');
            if (!in_array($type, array('page', 'custom'), true)) continue;
            $s = array(
                'type'       => $type,
                'icon'       => sanitize_text_field($item['icon'] ?? ''),
                'visibility' => in_array($item['visibility'] ?? 'all', array('all','auth','non-auth'), true) ? $item['visibility'] : 'all',
            );
            foreach ($item as $k => $v) {
                if (preg_match('/^lang_[a-zA-Z0-9_]+$/', $k)) {
                    $s[$k] = sanitize_text_field($v);
                }
            }
            if ($type === 'page')   $s['id']  = intval($item['id'] ?? 0);
            if ($type === 'custom') $s['url'] = esc_url_raw($item['url'] ?? '');
            $sanitized[] = $s;
        }
        update_option('menux_menu_items', $sanitized);
    }
    if (isset($config['style']) && is_array($config['style'])) {
        $rs = $config['style'];
        $color_keys = array('container_bg','container_border','link_color','link_hover_color','link_active_color','link_active_border','hamburger_color','hamburger_bg','mobile_menu_bg','sticky_bg','search_color','search_bg');
        $ss = array();
        foreach ($color_keys as $k) {
            $ss[$k] = !empty($rs[$k]) ? sanitize_hex_color($rs[$k]) : '';
        }
        $ss['font_size']          = !empty($rs['font_size'])         ? intval($rs['font_size'])                   : '';
        $ss['font_weight']        = !empty($rs['font_weight'])       ? sanitize_text_field($rs['font_weight'])    : '';
        $ss['gap']                = isset($rs['gap'])                ? intval($rs['gap'])                         : '20';
        $ss['padding_x']          = isset($rs['padding_x'])          ? intval($rs['padding_x'])                  : '0';
        $ss['padding_y']          = isset($rs['padding_y'])          ? intval($rs['padding_y'])                  : '0';
        $ss['mobile_breakpoint']  = isset($rs['mobile_breakpoint'])  ? intval($rs['mobile_breakpoint'])          : '768';
        $ss['hamburger_style']    = isset($rs['hamburger_style'])    ? sanitize_text_field($rs['hamburger_style']) : 'classic';
        $ss['hamburger_align']    = isset($rs['hamburger_align'])    ? sanitize_text_field($rs['hamburger_align']) : 'flex-end';
        $ss['mobile_menu_pad']    = isset($rs['mobile_menu_pad'])    ? intval($rs['mobile_menu_pad'])             : '0';
        $ss['mobile_menu_shadow'] = isset($rs['mobile_menu_shadow']) ? sanitize_text_field($rs['mobile_menu_shadow']) : '0';
        $ss['custom_css']         = isset($rs['custom_css'])         ? wp_strip_all_tags($rs['custom_css'])       : '';
        $ss['dark_mode']          = in_array($rs['dark_mode'] ?? 'light', array('light','dark','auto'), true) ? $rs['dark_mode'] : 'light';
        $ss['search_enabled']     = isset($rs['search_enabled'])     ? ($rs['search_enabled'] === '1' ? '1' : '0') : '0';
        $ss['search_placeholder'] = isset($rs['search_placeholder']) ? sanitize_text_field($rs['search_placeholder']) : 'Search...';
        $ss['sticky_justify']     = isset($rs['sticky_justify'])     ? sanitize_text_field($rs['sticky_justify'])    : 'flex-start';
        $ss['sticky_align_items'] = isset($rs['sticky_align_items']) ? sanitize_text_field($rs['sticky_align_items']) : 'center';
        $ss['sticky_padding_x']   = isset($rs['sticky_padding_x'])  ? intval($rs['sticky_padding_x'])               : '';
        $ss['sticky_padding_y']   = isset($rs['sticky_padding_y'])  ? intval($rs['sticky_padding_y'])               : '';
        update_option('menux_style', $ss);
    }
    wp_send_json_success(array(
        'message'    => 'Configuration imported successfully.',
        'items_count'=> count(get_option('menux_menu_items', array())),
    ));
}
