<?php
/**
 * MenuX Free — Admin Page
 * Registers admin menu and renders the main configuration page.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'menux_register_admin_page');
function menux_register_admin_page() {
    add_menu_page('Giuliomax Menu Builder', 'Menu Builder', 'manage_options', 'menux', 'menux_render_admin_html', 'dashicons-menu-alt3', 100);
}

function menux_render_admin_html() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['menux_save_all']) && check_admin_referer('menux_save_action', 'menux_nonce')) {

        // Detect active languages from installed multilingual plugins
        $supported_langs = menux_get_supported_languages();
        $valid_lang_codes = array_column($supported_langs, 'code');

        $menu_items = array();
        if (!empty($_POST['menu_items']) && is_array($_POST['menu_items'])) {
            foreach ( wp_unslash( $_POST['menu_items'] ) as $item ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $type = sanitize_text_field($item['type']);
                $visibility = sanitize_text_field($item['visibility'] ?? 'all');
                // Accept 'all', 'logged_in', 'logged_out', or any valid WP role slug
                $valid_vis = array_merge(array('all','logged_in','logged_out'), array_keys(wp_roles()->roles));
                if (!in_array($visibility, $valid_vis, true)) {
                    $visibility = 'all';
                }

                $menu_item_data = array(
                    'type'       => $type,
                    'icon'       => sanitize_text_field($item['icon'] ?? ''),
                    'visibility' => $visibility,
                    // Badge
                    'badge'      => sanitize_text_field($item['badge'] ?? ''),
                    'badge_color'=> !empty($item['badge_color']) ? sanitize_hex_color($item['badge_color']) : '',
                    'badge_bg'   => !empty($item['badge_bg'])    ? sanitize_hex_color($item['badge_bg'])    : '',
                    // Link target
                    'target'     => in_array($item['target'] ?? '', array('','_blank'), true) ? ($item['target'] ?? '') : '',
                    // Item key univoco per statistiche
                    'item_key'   => !empty($item['item_key']) ? sanitize_key($item['item_key']) : '',
                    // Submenu (JSON-encoded array di voci figlie)
                    'children'   => array(),
                    // ──── FEATURE: Notification Dot ────
                    'notif_dot'  => !empty($item['notif_dot']) ? '1' : '0',
                    // ──── FEATURE: Scheduled Items ────
                    'schedule_start' => sanitize_text_field($item['schedule_start'] ?? ''),
                    'schedule_end'   => sanitize_text_field($item['schedule_end']   ?? ''),
                    // ──── FEATURE: Condizionali avanzati ────
                    'cond_roles'    => sanitize_text_field($item['cond_roles']   ?? ''),  // comma-separated WP roles
                    'cond_devices'  => sanitize_text_field($item['cond_devices'] ?? ''),  // mobile,tablet,desktop
                    'cond_pages'    => sanitize_text_field($item['cond_pages']   ?? ''),  // page IDs comma-separated
                    'cond_time_from'=> sanitize_text_field($item['cond_time_from']?? ''), // HH:MM
                    'cond_time_to'  => sanitize_text_field($item['cond_time_to'] ?? ''),  // HH:MM
                    'cond_utm'      => sanitize_text_field($item['cond_utm']     ?? ''),  // utm_source value
                    // ──── FEATURE: Multi-menu location ────
                    'menu_location' => sanitize_key($item['menu_location'] ?? 'primary'),
                    // ──── Mega Menu ────
                    'mega_menu'       => (!empty($item['mega_menu']) && $item['mega_menu'] === '1') ? '1' : '0',
                    'mega_full_width' => (!empty($item['mega_full_width'])) ? '1' : '0',
                    'mega_columns'    => Menux_MegaMenu::sanitize_columns($item['mega_columns_json'] ?? ''),
                );

                // Salva le etichette per ogni lingua disponibile (codici dinamici)
                if (!empty($valid_lang_codes)) {
                    foreach ($valid_lang_codes as $code) {
                        $safe_key = menux_code_to_key($code);
                        $menu_item_data[$safe_key] = sanitize_text_field($item[$safe_key] ?? '');
                    }
                } else {
                    // Fallback: salva campi generici se l'API non è configurata
                    foreach ($item as $k => $v) {
                        if (preg_match('/^lang_[a-zA-Z0-9_]+$/', $k)) {
                            $menu_item_data[$k] = sanitize_text_field($v);
                        }
                    }
                }

                if ($type === 'page') {
                    $menu_item_data['id'] = intval($item['id']);
                    if (empty($menu_item_data['item_key'])) $menu_item_data['item_key'] = 'page_' . intval($item['id']);
                    // Salva figli
                    if (!empty($item['children']) && is_array($item['children'])) {
                        foreach ($item['children'] as $child) {
                            $ch = array(
                                'type'     => sanitize_text_field($child['type'] ?? 'custom'),
                                'icon'     => sanitize_text_field($child['icon'] ?? ''),
                                'target'   => in_array($child['target'] ?? '', array('','_blank'), true) ? ($child['target'] ?? '') : '',
                                'children' => array(),
                            );
                            foreach ($child as $ck => $cv) {
                                if (preg_match('/^lang_[a-zA-Z0-9_]+$/', $ck)) $ch[$ck] = sanitize_text_field($cv);
                            }
                            if ($ch['type'] === 'page')   { $ch['id']  = intval($child['id'] ?? 0); }
                            else                           { $ch['url'] = esc_url_raw($child['url'] ?? ''); }
                            // 3° livello
                            if (!empty($child['children']) && is_array($child['children'])) {
                                foreach ($child['children'] as $gc) {
                                    $gch = array(
                                        'type'   => sanitize_text_field($gc['type'] ?? 'custom'),
                                        'icon'   => sanitize_text_field($gc['icon'] ?? ''),
                                        'target' => in_array($gc['target'] ?? '', array('','_blank'), true) ? ($gc['target'] ?? '') : '',
                                    );
                                    foreach ($gc as $gk => $gv) {
                                        if (preg_match('/^lang_[a-zA-Z0-9_]+$/', $gk)) $gch[$gk] = sanitize_text_field($gv);
                                    }
                                    if ($gch['type'] === 'page') $gch['id']  = intval($gc['id'] ?? 0);
                                    else                          $gch['url'] = esc_url_raw($gc['url'] ?? '');
                                    $ch['children'][] = $gch;
                                }
                            }
                            $menu_item_data['children'][] = $ch;
                        }
                    }
                    $menu_items[] = $menu_item_data;
                } elseif ($type === 'custom') {
                    if (empty($item['url'])) continue;
                    $menu_item_data['url'] = esc_url_raw($item['url']);
                    if (empty($menu_item_data['item_key'])) $menu_item_data['item_key'] = 'custom_' . md5($item['url']);
                    if (!empty($item['children']) && is_array($item['children'])) {
                        foreach ($item['children'] as $child) {
                            $ch = array(
                                'type'     => sanitize_text_field($child['type'] ?? 'custom'),
                                'icon'     => sanitize_text_field($child['icon'] ?? ''),
                                'target'   => in_array($child['target'] ?? '', array('','_blank'), true) ? ($child['target'] ?? '') : '',
                                'children' => array(),
                            );
                            foreach ($child as $ck => $cv) {
                                if (preg_match('/^lang_[a-zA-Z0-9_]+$/', $ck)) $ch[$ck] = sanitize_text_field($cv);
                            }
                            if ($ch['type'] === 'page') $ch['id']  = intval($child['id'] ?? 0);
                            else                         $ch['url'] = esc_url_raw($child['url'] ?? '');
                            $menu_item_data['children'][] = $ch;
                        }
                    }
                    $menu_items[] = $menu_item_data;
                }
            }
        }
        update_option('menux_menu_items', $menu_items);

        // Salva stile
        $raw_style  = isset($_POST['menux_style']) && is_array($_POST['menux_style']) ? wp_unslash( $_POST['menux_style'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $use_flags  = isset($_POST['menux_style_use']) && is_array($_POST['menux_style_use']) ? wp_unslash( $_POST['menux_style_use'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        $color_keys = array(
            'container_bg', 'container_border',
            'link_color', 'link_hover_color', 'link_hover_bg',
            'link_active_color', 'link_active_border', 'link_active_bg',
            'hamburger_color', 'hamburger_bg', 'mobile_menu_bg', 'mobile_overlay_bg', 'sticky_bg',
            'submenu_bg', 'submenu_border', 'submenu_link_color',
            'last_item_color', 'last_item_hover_color', 'last_item_bg',
        );
        
        $saved_style = array();
        foreach ($color_keys as $k) {
            $saved_style[$k] = (!empty($use_flags[$k]) && !empty($raw_style[$k])) ? sanitize_hex_color($raw_style[$k]) : '';
        }
        $saved_style['container_bg_gradient']   = isset( $raw_style['container_bg_gradient'] )   ? sanitize_text_field( $raw_style['container_bg_gradient'] )   : '';
        $saved_style['link_hover_bg_gradient']  = isset( $raw_style['link_hover_bg_gradient'] )  ? sanitize_text_field( $raw_style['link_hover_bg_gradient'] )  : '';
        $saved_style['link_active_bg_gradient'] = isset( $raw_style['link_active_bg_gradient'] ) ? sanitize_text_field( $raw_style['link_active_bg_gradient'] ) : '';
        $saved_style['font_size']               = isset($raw_style['font_size'])          && $raw_style['font_size']         !== '' ? intval($raw_style['font_size'])              : '';
        $saved_style['font_weight']             = isset($raw_style['font_weight'])        && $raw_style['font_weight']       !== '' ? sanitize_text_field($raw_style['font_weight']) : '';
        $saved_style['gap']                     = isset($raw_style['gap'])                && $raw_style['gap']               !== '' ? intval($raw_style['gap'])                    : '20';
        $saved_style['padding_x']               = isset($raw_style['padding_x'])          && $raw_style['padding_x']         !== '' ? intval($raw_style['padding_x'])              : '0';
        $saved_style['padding_y']               = isset($raw_style['padding_y'])          && $raw_style['padding_y']         !== '' ? intval($raw_style['padding_y'])              : '0';
        $saved_style['link_border_radius']      = isset($raw_style['link_border_radius']) && $raw_style['link_border_radius'] !== '' ? intval($raw_style['link_border_radius'])     : '';
        $saved_style['mobile_breakpoint']       = isset($raw_style['mobile_breakpoint'])  && $raw_style['mobile_breakpoint'] !== '' ? intval($raw_style['mobile_breakpoint'])      : '768';
        $saved_style['mobile_breakpoint_mode']  = in_array($raw_style['mobile_breakpoint_mode'] ?? 'manual', array('manual','auto'), true) ? $raw_style['mobile_breakpoint_mode'] : 'manual';
        $saved_style['hamburger_style']         = isset($raw_style['hamburger_style'])    ? sanitize_text_field($raw_style['hamburger_style'])    : 'classic';
        $saved_style['hamburger_align']         = isset($raw_style['hamburger_align'])    ? sanitize_text_field($raw_style['hamburger_align'])    : 'flex-end';
        $saved_style['mobile_menu_pad']         = isset($raw_style['mobile_menu_pad'])    ? intval($raw_style['mobile_menu_pad'])                 : '0';
        $saved_style['mobile_menu_shadow']      = isset($raw_style['mobile_menu_shadow']) ? '1' : '0';
        // ──── Nuovi: modalità apertura ────
        $saved_style['mobile_open_style']        = in_array($raw_style['mobile_open_style'] ?? 'dropdown', array('dropdown','fullscreen','drawer-left','drawer-right'), true) ? $raw_style['mobile_open_style'] : 'dropdown';
        $saved_style['mobile_overlay_bg']        = !empty($use_flags['mobile_overlay_bg']) && !empty($raw_style['mobile_overlay_bg']) ? sanitize_hex_color($raw_style['mobile_overlay_bg']) : '#000000';
        $saved_style['mobile_overlay_opacity']   = isset($raw_style['mobile_overlay_opacity']) ? min(1, max(0, floatval($raw_style['mobile_overlay_opacity']))) : '0.5';
        $saved_style['mobile_overlay_blur']      = isset($raw_style['mobile_overlay_blur'])    ? intval($raw_style['mobile_overlay_blur'])    : '0';
        $saved_style['mobile_fullscreen_align']  = in_array($raw_style['mobile_fullscreen_align'] ?? 'center', array('center','flex-start','flex-end'), true) ? $raw_style['mobile_fullscreen_align'] : 'center';
        $saved_style['mobile_drawer_width']      = isset($raw_style['mobile_drawer_width'])    ? intval($raw_style['mobile_drawer_width'])     : '280';
        $saved_style['mobile_open_animation']    = in_array($raw_style['mobile_open_animation'] ?? 'fade', array('fade','slide','scale'), true) ? $raw_style['mobile_open_animation'] : 'fade';
        // Typography avanzata
        $saved_style['google_font']             = isset($raw_style['google_font'])        ? sanitize_text_field($raw_style['google_font'])        : '';
        $saved_style['font_family']             = isset($raw_style['font_family'])        ? sanitize_text_field($raw_style['font_family'])        : '';
        $saved_style['letter_spacing']          = isset($raw_style['letter_spacing'])     ? floatval($raw_style['letter_spacing'])                : '';
        $saved_style['text_transform']          = in_array($raw_style['text_transform'] ?? '', array('none','uppercase','lowercase','capitalize'), true) ? $raw_style['text_transform'] : 'none';
        $saved_style['link_transition']         = isset($raw_style['link_transition'])    && is_numeric($raw_style['link_transition']) ? floatval($raw_style['link_transition']) : '0.3';
        // Link avanzato
        $saved_style['link_active_font_weight'] = isset($raw_style['link_active_font_weight']) ? sanitize_text_field($raw_style['link_active_font_weight']) : '';
        // Animazioni
        $saved_style['link_animation']          = in_array($raw_style['link_animation'] ?? 'none', array('none','pulse','shake','bounce','glow','lift','scale','underline'), true) ? $raw_style['link_animation'] : 'none';
        // Layout avanzato
        $saved_style['nav_justify']             = in_array($raw_style['nav_justify'] ?? 'flex-end', array('flex-start','center','flex-end','space-between','space-evenly'), true) ? $raw_style['nav_justify'] : 'flex-end';
        $saved_style['push_last_item']          = isset($raw_style['push_last_item'])     ? '1' : '0';
        // Entrance animation
        $saved_style['entrance_animation']      = in_array($raw_style['entrance_animation'] ?? 'none', array('none','fadeIn','slideDown','slideUp','slideLeft','slideRight','zoomIn','flipX'), true) ? $raw_style['entrance_animation'] : 'none';
        $saved_style['entrance_duration']       = isset($raw_style['entrance_duration'])   && is_numeric($raw_style['entrance_duration']) ? round(floatval($raw_style['entrance_duration']), 2) : '0.5';
        $saved_style['entrance_delay']          = isset($raw_style['entrance_delay'])      && is_numeric($raw_style['entrance_delay'])    ? round(floatval($raw_style['entrance_delay']), 2)    : '0';
        $saved_style['entrance_stagger']        = isset($raw_style['entrance_stagger'])    && is_numeric($raw_style['entrance_stagger'])  ? round(floatval($raw_style['entrance_stagger']), 3)  : '0';
        // Sticky
        $saved_style['sticky']                  = isset($raw_style['sticky'])             ? '1' : '0';
        $saved_style['sticky_shadow']           = isset($raw_style['sticky_shadow'])      ? '1' : '0';
        $saved_style['sticky_z_index']          = isset($raw_style['sticky_z_index'])     ? intval($raw_style['sticky_z_index'])                  : '9999';
        $saved_style['sticky_justify']          = in_array($raw_style['sticky_justify'] ?? 'flex-start', array('flex-start','center','flex-end','space-between'), true) ? $raw_style['sticky_justify'] : 'flex-start';
        $saved_style['sticky_align_items']      = in_array($raw_style['sticky_align_items'] ?? 'center', array('flex-start','center','flex-end'), true) ? $raw_style['sticky_align_items'] : 'center';
        $saved_style['sticky_padding_x']        = isset($raw_style['sticky_padding_x']) && $raw_style['sticky_padding_x'] !== '' ? intval($raw_style['sticky_padding_x']) : '';
        $saved_style['sticky_padding_y']        = isset($raw_style['sticky_padding_y']) && $raw_style['sticky_padding_y'] !== '' ? intval($raw_style['sticky_padding_y']) : '';
        // Dark mode
        $saved_style['dark_mode']               = in_array($raw_style['dark_mode'] ?? 'light', array('light','dark','auto'), true) ? $raw_style['dark_mode'] : 'light';
        // Submenu
        $saved_style['submenu_shadow']          = isset($raw_style['submenu_shadow'])     ? '1' : '0';
        $saved_style['submenu_animation']       = in_array($raw_style['submenu_animation'] ?? 'fade', array('fade','slide','none'), true) ? $raw_style['submenu_animation'] : 'fade';
        // ── Mega Menu panel appearance ──
        $saved_style['mega_bg']             = ! empty( $use_flags['mega_bg'] ) && ! empty( $raw_style['mega_bg'] ) ? sanitize_hex_color( $raw_style['mega_bg'] ) : '';
        $saved_style['mega_padding_y']      = isset( $raw_style['mega_padding_y'] )  && is_numeric( $raw_style['mega_padding_y'] )  ? (int) $raw_style['mega_padding_y']  : 24;
        $saved_style['mega_padding_x']      = isset( $raw_style['mega_padding_x'] )  && is_numeric( $raw_style['mega_padding_x'] )  ? (int) $raw_style['mega_padding_x']  : 32;
        $saved_style['mega_max_width']      = isset( $raw_style['mega_max_width'] )  && is_numeric( $raw_style['mega_max_width'] )  ? (int) $raw_style['mega_max_width']  : 0;
        $saved_style['mega_col_gap']        = isset( $raw_style['mega_col_gap'] )    && is_numeric( $raw_style['mega_col_gap'] )    ? (int) $raw_style['mega_col_gap']    : 16;
        $saved_style['mega_mobile_disable'] = isset( $raw_style['mega_mobile_disable'] ) ? '1' : '0';

        update_option('menux_style', $saved_style);
        // Invalida font cache
        delete_transient('menux_gfont_loaded');

        // ── Save Logo settings ──
        if ( isset( $_POST['menux_logo'] ) && is_array( $_POST['menux_logo'] ) ) {
            Menux_Logo::save( wp_unslash( $_POST['menux_logo'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        // Save WP Nav replacements
        $raw_replacements = isset( $_POST['menux_wp_nav_replacements'] ) && is_array( $_POST['menux_wp_nav_replacements'] )
            ? wp_unslash( $_POST['menux_wp_nav_replacements'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : array();
        $saved_replacements = array();
        foreach ( $raw_replacements as $theme_loc => $menux_loc ) {
            $theme_loc = sanitize_key( $theme_loc );
            $menux_loc = sanitize_key( $menux_loc );
            if ( $theme_loc && $menux_loc ) $saved_replacements[ $theme_loc ] = $menux_loc;
        }
        update_option( 'menux_wp_nav_replacements', $saved_replacements );

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }

    // Handle full reset
    if ( isset( $_POST['menux_reset_all'] ) && check_admin_referer( 'menux_reset_action', 'menux_reset_nonce' ) && current_user_can( 'manage_options' ) ) {
        delete_option( 'menux_menu_items' );
        delete_option( 'menux_style' );
        delete_option( 'menux_logo' );
        delete_option( 'menux_wp_nav_replacements' );
        delete_transient( 'menux_gfont_loaded' );
        echo '<div class="notice notice-warning is-dismissible"><p><strong>Reset complete.</strong> All menu items, styles and settings have been deleted.</p></div>';
    }

    $menu_items   = get_option('menux_menu_items', array());
    $menux_style = get_option('menux_style', array());
    $all_wp_pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'ASC'));
    $supported_langs = menux_get_supported_languages();
    // Build WP roles array for visibility dropdown
    $menux_wp_roles_for_select = array_merge(
        array('all' => 'Everyone', 'logged_in' => 'Any logged-in user', 'logged_out' => 'Guests only (not logged in)'),
        array_map(function($r){ return translate_user_role($r['name']) . ' only'; }, wp_roles()->roles)
    );
    ?>
    <div class="wrap bm-admin-modern">
        <h1 class="wp-heading-inline" style="display:none;">MenuX Configuration</h1>

        <form method="POST" id="menux-form" novalidate>
            <?php wp_nonce_field('menux_save_action', 'menux_nonce'); ?>

            <!-- ============== TOPBAR ============== -->
            <div class="bm-topbar">
                <div class="bm-topbar-brand">
                    <span class="bm-topbar-icon">🐝</span>
                    <span class="bm-topbar-title">MenuX</span>
                </div>
                <div class="bm-topbar-actions">
                    <input type="submit" name="menux_save_all" class="bm-topbar-btn bm-topbar-btn-save" value="💾 Save Menu">
                </div>
            </div>
            <!-- ============== FINE TOPBAR ============== -->

            <!-- ============== STICKY PREVIEW BAR ============== -->
            <div class="bm-preview-sticky">
                <div class="bm-preview-sticky-inner">
                    <div class="bm-preview-toolbar">
                        <div class="bm-device-switcher" id="bm-device-switcher">
                            <button type="button" class="bm-device-btn active" data-device="desktop" onclick="menux_setDevice('desktop')" title="Desktop">🖥️ Desktop</button>
                            <button type="button" class="bm-device-btn" data-device="tablet" onclick="menux_setDevice('tablet')" title="Tablet (768px)">📱 Tablet</button>
                            <button type="button" class="bm-device-btn" data-device="mobile" onclick="menux_setDevice('mobile')" title="Mobile (375px)">📲 Mobile</button>
                        </div>
                        <span style="color:#d1d5db;">|</span>
                        <span style="font-size:12px;color:#6b7280;">👤</span>
                        <select id="preview-auth-toggle" onchange="menux_updatePreviewAuth()" class="bm-select bm-select-sm">
                            <option value="guest">Guest</option>
                            <option value="logged">Logged in</option>
                        </select>
                        <span id="bm-active-theme-badge" style="display:none;" class="bm-active-theme-badge"></span>
                        <button type="button" onclick="bmThemeModal.open()" class="bm-btn bm-btn-primary bm-btn-sm">🎨 Themes</button>
                        <span id="bm-preview-device-label" class="bm-preview-tip">🖥️ Desktop — full width</span>
                    </div>
                    <div id="bm-device-frame-wrap" class="bm-device-frame-wrap bm-device-frame-compact">
                        <div id="bm-device-outer" class="bm-device-outer bm-device-desktop">
                            <div id="bm-device-chrome" class="bm-device-chrome" style="display:none;">
                                <div class="bm-device-chrome-bar">
                                    <span class="bm-chrome-dot" style="background:#ff5f57;"></span>
                                    <span class="bm-chrome-dot" style="background:#febc2e;"></span>
                                    <span class="bm-chrome-dot" style="background:#28c840;"></span>
                                    <span class="bm-chrome-urlbar"></span>
                                </div>
                            </div>
                            <div class="bm-preview-frame">
                                <div id="menux-preview-wrap" class="bm-preview-canvas">
                                    <?php echo wp_kses_post( menux_get_preview_markup( $menu_items, $supported_langs ) ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ============== FINE STICKY PREVIEW BAR ============== -->

            <!-- ============== MAIN LAYOUT ============== -->
            <div class="bm-admin-layout">

                <!-- SIDEBAR -->
                <aside class="bm-admin-sidebar">
                    <nav class="bm-sidebar-nav">
                        <div class="bm-sidebar-group">
                            <div class="bm-sidebar-group-label">Structure</div>
                            <button type="button" class="bm-sidebar-item active" data-section="structure" onclick="menuxGoSection('structure')">
                                🧩 Menu Structure
                            </button>
                        </div>
                        <div class="bm-sidebar-group">
                            <div class="bm-sidebar-group-label">Logo & Mega Menu</div>
                            <button type="button" class="bm-sidebar-item" data-section="logo"     onclick="menuxGoSection('logo')">🖼️ Logo</button>
                            <button type="button" class="bm-sidebar-item" data-section="megamenu" onclick="menuxGoSection('megamenu')">⚡ Mega Menu</button>
                        </div>
                        <div class="bm-sidebar-group">
                            <div class="bm-sidebar-group-label">Style</div>
                            <button type="button" class="bm-sidebar-item" data-section="colors"   onclick="menuxGoSection('colors')">🎨 Colors</button>
                            <button type="button" class="bm-sidebar-item" data-section="typo"     onclick="menuxGoSection('typo')">🔤 Typography</button>
                            <button type="button" class="bm-sidebar-item" data-section="layout"   onclick="menuxGoSection('layout')">📐 Layout</button>
                            <button type="button" class="bm-sidebar-item" data-section="mobile"   onclick="menuxGoSection('mobile')">📱 Mobile</button>
                            <button type="button" class="bm-sidebar-item" data-section="darkmode" onclick="menuxGoSection('darkmode')">🌙 Dark Mode</button>
                            <button type="button" class="bm-sidebar-item" data-section="css"      onclick="menuxGoSection('css')">⚙️ Advanced</button>
                        </div>
                        <div class="bm-sidebar-group">
                            <div class="bm-sidebar-group-label">Tools</div>
                            <button type="button" class="bm-sidebar-item" data-section="wpnav" onclick="menuxGoSection('wpnav')">🔗 WP Integration</button>
                            <button type="button" class="bm-sidebar-item" data-section="import-export" onclick="menuxGoSection('import-export')">📦 Import / Export</button>
                            <?php if (!empty($supported_langs)): ?>
                            <button type="button" class="bm-sidebar-item" data-section="multilingual" onclick="menuxGoSection('multilingual')">🌐 Multilingual</button>
                            <?php endif; ?>
                        </div>
                    </nav>
                    <div class="bm-sidebar-footer">
                        <input type="submit" name="menux_save_all" class="bm-btn bm-btn-primary bm-btn-block" value="💾 Save Menu">
                        <button type="button" onclick="menuxGoSection('reset')" style="margin-top:8px;width:100%;background:none;border:1px solid #fca5a5;color:#ef4444;border-radius:8px;padding:8px;font-size:12px;cursor:pointer;">🗑 Reset Everything</button>
                    </div>
                </aside>
                <!-- /SIDEBAR -->

                <!-- CONTENT -->
                <main class="bm-admin-content">

                    <!-- Panel: Menu Structure -->
                    <div id="panel-structure" class="bm-panel">
                        <?php menux_render_builder($menu_items, $all_wp_pages, $supported_langs, $menux_wp_roles_for_select); ?>
                    </div>

                    <!-- Panel: Style (all style tabs) -->
                    <div id="panel-style" class="bm-panel" style="display:none;">
                        <?php menux_render_style_panel($menux_style); ?>
                    </div>



                    <!-- Panel: Logo -->
                    <div id="panel-logo" class="bm-panel" style="display:none;">
                        <?php menux_render_logo_panel(); ?>
                    </div>

                    <!-- Panel: Mega Menu -->
                    <div id="panel-megamenu" class="bm-panel" style="display:none;">
                        <?php menux_render_megamenu_panel( $menu_items ); ?>
                    </div>

                    <!-- Panel: WP Nav Integration -->
                    <div id="panel-wpnav" class="bm-panel" style="display:none;">
                        <?php
                        $theme_locations    = get_registered_nav_menus();
                        $saved_replacements = get_option( 'menux_wp_nav_replacements', array() );
                        $menux_locations    = array( 'primary' );
                        $menux_loc_counts   = array( 'primary' => 0 );
                        foreach ( get_option( 'menux_menu_items', array() ) as $item ) {
                            $loc = ! empty( $item['menu_location'] ) ? $item['menu_location'] : 'primary';
                            if ( ! in_array( $loc, $menux_locations, true ) ) { $menux_locations[] = $loc; $menux_loc_counts[ $loc ] = 0; }
                            $menux_loc_counts[ $loc ] = ( $menux_loc_counts[ $loc ] ?? 0 ) + 1;
                        }
                        ?>
                        <div class="bm-card">
                            <div class="bm-card-header">
                                <span class="bm-card-icon">🔗</span>
                                <div class="bm-card-titles">
                                    <h2 class="bm-card-title">WP Menu Integration</h2>
                                    <p class="bm-card-subtitle">Replace your theme's native menus with MenuX — works with classic PHP themes that call wp_nav_menu()</p>
                                </div>
                            </div>
                            <div class="bm-card-body">
                                <?php if ( empty( $theme_locations ) ) : ?>
                                    <p style="color:#6b7280;font-size:13px;">No theme menu locations registered. Your active theme must call <code>register_nav_menus()</code>.</p>
                                <?php else : ?>
                                <p style="color:#6b7280;font-size:13px;margin-bottom:16px;">Map each WordPress theme location to a MenuX location. Leave <em>— Don't replace —</em> to keep the theme's original menu.</p>
                                <table style="width:100%;border-collapse:collapse;">
                                    <thead><tr>
                                        <th style="text-align:left;padding:8px 12px 8px 0;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #e5e7eb;">Theme Location</th>
                                        <th style="text-align:left;padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #e5e7eb;">Replace with MenuX location</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php foreach ( $theme_locations as $slug => $label ) :
                                            $current = $saved_replacements[ $slug ] ?? ''; ?>
                                        <tr>
                                            <td style="padding:10px 12px 10px 0;font-size:13px;font-weight:500;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                                                <code><?php echo esc_html( $slug ); ?></code>
                                                <span style="display:block;color:#9ca3af;font-size:11px;margin-top:2px;"><?php echo esc_html( $label ); ?></span>
                                            </td>
                                            <td style="padding:10px 0;vertical-align:middle;border-bottom:1px solid #f3f4f6;">
                                                <select name="menux_wp_nav_replacements[<?php echo esc_attr($slug); ?>]" class="bm-select" style="min-width:180px;">
                                                    <option value="">— Don't replace —</option>
                                                    <?php foreach ( $menux_locations as $mloc ) :
                                                        $cnt = $menux_loc_counts[ $mloc ] ?? 0; ?>
                                                    <option value="<?php echo esc_attr($mloc); ?>"<?php selected($current,$mloc); ?>><?php echo esc_html($mloc . ($cnt > 0 ? ' ('.$cnt.' items)' : ' ⚠ no items')); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p style="margin-top:12px;font-size:12px;color:#9ca3af;">Save after making changes. A location marked <strong>⚠ no items</strong> will keep the theme's original menu.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Import / Export -->
                    <div id="panel-import-export" class="bm-panel" style="display:none;">
                        <div class="bm-card">
                            <div class="bm-card-header">
                                <span class="bm-card-icon">📦</span>
                                <div class="bm-card-titles">
                                    <h2 class="bm-card-title">Import / Export Configuration</h2>
                                    <p class="bm-card-subtitle">Backup or transfer your setup between sites</p>
                                </div>
                            </div>
                            <div class="bm-card-body">
                                <div class="bm-impexp-grid">
                                    <div class="bm-impexp-action">
                                        <div class="bm-impexp-action-icon">⬇️</div>
                                        <div class="bm-impexp-action-content">
                                            <strong>Export configuration</strong>
                                            <p>Download a JSON file with API URL, menu items and style.</p>
                                            <button type="button" class="bm-btn bm-btn-secondary" onclick="menux_exportConfig()">⬇️ Export</button>
                                        </div>
                                    </div>
                                    <div class="bm-impexp-action">
                                        <div class="bm-impexp-action-icon">⬆️</div>
                                        <div class="bm-impexp-action-content">
                                            <strong>Import configuration</strong>
                                            <p>From a JSON file. Overwrites current settings and reloads the page.</p>
                                            <label class="bm-btn bm-btn-secondary" style="cursor:pointer;">
                                                ⬆️ Choose file
                                                <input type="file" id="menux-import-file" accept=".json" style="display:none;" onchange="menux_importConfig(this)">
                                            </label>
                                            <span id="menux-import-msg" style="display:none; margin-left:10px; font-size:13px;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Multilingual -->
                    <?php if (!empty($supported_langs)): ?>
                    <div id="panel-multilingual" class="bm-panel" style="display:none;">
                        <div class="bm-card">
                            <div class="bm-card-header">
                                <span class="bm-card-icon">🌐</span>
                                <div class="bm-card-titles">
                                    <h2 class="bm-card-title">Multilingual Support</h2>
                                    <p class="bm-card-subtitle">Languages detected from your installed multilingual plugin</p>
                                </div>
                            </div>
                            <div class="bm-card-body">
                                <div class="bm-lang-chips">
                                    <?php foreach ($supported_langs as $lang): ?>
                                        <span class="bm-lang-chip">
                                            <strong><?php echo esc_html($lang['code']); ?></strong>
                                            <span class="bm-lang-chip-label"><?php echo esc_html($lang['label']); ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                    <button type="button" id="menux-reload-langs-btn" class="bm-btn bm-btn-secondary bm-btn-sm" onclick="menux_reloadLanguages()">🔄 Refresh</button>
                                </div>
                                <span id="menux-reload-langs-msg" style="display:none; margin-left:10px; font-size:13px;"></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Panel: Reset -->
                    <div id="panel-reset" class="bm-panel" style="display:none;">
                        <div class="bm-card" style="border:1px solid #fca5a5;">
                            <div class="bm-card-header" style="background:#fff5f5;">
                                <span class="bm-card-icon">🗑</span>
                                <div class="bm-card-titles">
                                    <h2 class="bm-card-title" style="color:#dc2626;">Reset Everything</h2>
                                    <p class="bm-card-subtitle">Permanently delete all saved data — this cannot be undone</p>
                                </div>
                            </div>
                            <div class="bm-card-body">
                                <p style="font-size:13px;color:#374151;margin-bottom:16px;">This will permanently delete:</p>
                                <ul style="font-size:13px;color:#374151;margin:0 0 20px 20px;line-height:1.8;">
                                    <li>All menu items and locations</li>
                                    <li>All style settings (colors, fonts, layout)</li>
                                    <li>WP Integration mappings</li>
                                </ul>
                                <form method="POST" onsubmit="return confirm('Are you sure? This will delete ALL menu items, styles and settings. This cannot be undone.');">
                                    <?php wp_nonce_field( 'menux_reset_action', 'menux_reset_nonce' ); ?>
                                    <button type="submit" name="menux_reset_all" value="1" style="background:#dc2626;color:#fff;border:none;border-radius:8px;padding:10px 24px;font-size:14px;font-weight:600;cursor:pointer;">🗑 Delete all data and reset</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </main>
                <!-- /CONTENT -->

            </div>
            <!-- ============== FINE MAIN LAYOUT ============== -->

            <!-- ===== THEME MODAL ===== -->
            <div id="bm-theme-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999999;align-items:center;justify-content:center;padding:16px;">
                <div style="background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.35);width:min(1100px,98vw);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
                    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                        <div>
                            <div style="color:rgba(255,255,255,.75);font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;margin-bottom:2px;">Theme Gallery</div>
                            <div style="color:#fff;font-size:17px;font-weight:700;">🎨 Choose a Preset Theme</div>
                        </div>
                        <button onclick="bmThemeModal.close()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;">&times;</button>
                    </div>
                    <div style="overflow-y:auto;flex:1;padding:20px;">
                        <p style="font-size:12px;color:#6b7280;margin:0 0 16px;">Hover over a theme to see the live preview above. Click to select it, then press <strong>Apply</strong>.</p>
                        <div id="bm-modal-theme-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;"></div>
                    </div>
                    <div style="padding:14px 24px;border-top:1px solid #f0f0f0;background:#fafafa;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;border-radius:0 0 16px 16px;">
                        <div id="bm-modal-selected-info" style="font-size:13px;color:#6b7280;">No theme selected</div>
                        <div style="display:flex;gap:10px;">
                            <button type="button" onclick="bmThemeModal.close()" style="background:#fff;border:1px solid #d1d5db;color:#374151;padding:8px 20px;border-radius:8px;font-size:13px;cursor:pointer;">Cancel</button>
                            <button type="button" id="bm-modal-apply-btn" onclick="bmThemeModal.apply()" disabled style="background:linear-gradient(135deg,#667eea,#764ba2);border:none;color:#fff;padding:8px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;opacity:.45;box-shadow:0 2px 8px rgba(102,126,234,.3);">✅ Apply Theme</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===== FINE THEME MODAL ===== -->

        </form>
    </div>

    <?php /* Admin CSS and JS are enqueued via menux_admin_assets() in includes/assets.php. */ ?>
    <?php
    /* Inject PHP-dynamic data for admin.js via the WordPress enqueue API */
    $menux_admin_data = 'var menux_index = ' . (int) count( $menu_items ) . ';'
        . 'var menux_supported_langs = ' . wp_json_encode( $supported_langs ) . ';'
        . 'var menux_wp_roles = ' . wp_json_encode( $menux_wp_roles_for_select ) . ';'
        . 'var menux_all_pages = ' . wp_json_encode( array_map( function( $p ) { return array( 'id' => $p->ID, 'title' => $p->post_title ); }, $all_wp_pages ) ) . ';'
        . 'var menux_reload_langs_nonce = ' . wp_json_encode( wp_create_nonce( 'menux_reload_languages_nonce' ) ) . ';'
        . 'var menux_export_config_nonce = ' . wp_json_encode( wp_create_nonce( 'menux_export_config_nonce' ) ) . ';'
        . 'var menux_import_config_nonce = ' . wp_json_encode( wp_create_nonce( 'menux_import_config_nonce' ) ) . ';';
    wp_add_inline_script( 'menux-admin-js', $menux_admin_data, 'before' );
    ?>
    <?php
}
