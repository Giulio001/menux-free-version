<?php
/**
 * MenuX Pro — Admin Page
 * Registers admin menu and renders the main configuration page.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'menux_register_admin_page');
function menux_register_admin_page() {
    add_menu_page('MenuX', 'MenuX', 'manage_options', 'menux', 'menux_render_admin_html', 'dashicons-menu-alt3', 100);
    add_submenu_page('menux', 'MenuX - Statistics', '📊 Statistics', 'manage_options', 'menux-stats', 'menux_render_stats_page');
}

function menux_render_admin_html() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['menux_save_all']) && check_admin_referer('menux_save_action', 'menux_nonce')) {

        // Detect active languages from installed multilingual plugins
        $supported_langs = menux_get_supported_languages();
        $valid_lang_codes = array_column($supported_langs, 'code');

        $menu_items = array();
        if (!empty($_POST['menu_items']) && is_array($_POST['menu_items'])) {
            foreach ($_POST['menu_items'] as $item) {
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
        $raw_style  = isset($_POST['menux_style']) && is_array($_POST['menux_style']) ? $_POST['menux_style'] : array();
        $use_flags  = isset($_POST['menux_style_use']) && is_array($_POST['menux_style_use']) ? $_POST['menux_style_use'] : array();
        
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
        $saved_style['custom_css']              = isset($raw_style['custom_css'])         ? wp_unslash(strip_tags($raw_style['custom_css']))       : '';
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
        $saved_style['nav_justify']             = in_array($raw_style['nav_justify'] ?? 'flex-start', array('flex-start','center','flex-end','space-between','space-evenly'), true) ? $raw_style['nav_justify'] : 'flex-start';
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
        // Search
        $saved_style['search_enabled']          = isset($raw_style['search_enabled'])          ? '1' : '0';
        $saved_style['search_placeholder']      = isset($raw_style['search_placeholder'])      ? sanitize_text_field($raw_style['search_placeholder']) : 'Search...';
        $saved_style['search_color']            = (!empty($use_flags['search_color']) && !empty($raw_style['search_color'])) ? sanitize_hex_color($raw_style['search_color']) : '';
        $saved_style['search_bg']               = (!empty($use_flags['search_bg'])    && !empty($raw_style['search_bg']))    ? sanitize_hex_color($raw_style['search_bg'])    : '';
        // Logo
        $saved_style['logo_url']                = isset($raw_style['logo_url'])           ? esc_url_raw($raw_style['logo_url'])                   : '';
        $saved_style['logo_width']              = isset($raw_style['logo_width'])         ? intval($raw_style['logo_width'])                      : '120';
        $saved_style['logo_height']             = isset($raw_style['logo_height'])        && $raw_style['logo_height'] !== '' ? intval($raw_style['logo_height']) : '';
        $saved_style['logo_alt']                = isset($raw_style['logo_alt'])           ? sanitize_text_field($raw_style['logo_alt'])           : '';
        $saved_style['logo_link']               = isset($raw_style['logo_link'])          ? esc_url_raw($raw_style['logo_link'])                  : '';
        $saved_style['logo_position']           = in_array($raw_style['logo_position'] ?? 'left', array('left','right','center-split'), true) ? $raw_style['logo_position'] : 'left';
        // Submenu
        $saved_style['submenu_shadow']          = isset($raw_style['submenu_shadow'])     ? '1' : '0';
        $saved_style['submenu_animation']       = in_array($raw_style['submenu_animation'] ?? 'fade', array('fade','slide','none'), true) ? $raw_style['submenu_animation'] : 'fade';
        // Accessibility
        $saved_style['a11y_focus_visible']    = isset($raw_style['a11y_focus_visible'])    ? '1' : '0';
        $saved_style['a11y_focus_color']      = !empty($raw_style['a11y_focus_color'])     ? sanitize_hex_color($raw_style['a11y_focus_color'])       : '#2271b1';
        $saved_style['a11y_focus_width']      = isset($raw_style['a11y_focus_width'])      ? intval($raw_style['a11y_focus_width'])                   : '2';
        $saved_style['a11y_focus_offset']     = isset($raw_style['a11y_focus_offset'])     ? intval($raw_style['a11y_focus_offset'])                  : '2';
        $saved_style['a11y_skip_link']        = isset($raw_style['a11y_skip_link'])        ? '1' : '0';
        $saved_style['a11y_skip_target']      = isset($raw_style['a11y_skip_target'])      ? sanitize_html_class($raw_style['a11y_skip_target'])      : 'main';
        $saved_style['a11y_reduced_motion']   = isset($raw_style['a11y_reduced_motion'])   ? '1' : '0';
        $saved_style['a11y_aria_label']       = isset($raw_style['a11y_aria_label'])       ? sanitize_text_field($raw_style['a11y_aria_label'])       : 'Main menu';
        $saved_style['a11y_min_touch_target'] = isset($raw_style['a11y_min_touch_target']) ? '1' : '0';
        $saved_style['a11y_link_underline']   = isset($raw_style['a11y_link_underline'])   ? '1' : '0';
        $saved_style['a11y_high_contrast']    = isset($raw_style['a11y_high_contrast'])    ? '1' : '0';
        
        update_option('menux_style', $saved_style);
        // Invalida font cache
        delete_transient('menux_gfont_loaded');

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
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

        <!-- ===================== WIZARD OVERLAY ===================== -->
        <div id="bw-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:99999; align-items:center; justify-content:center;">
            <div id="bw-modal" style="background:#fff; border-radius:16px; box-shadow:0 24px 60px rgba(0,0,0,.3); width:min(680px,96vw); max-height:90vh; display:flex; flex-direction:column; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

                <!-- Header modale -->
                <div id="bw-header" style="background:linear-gradient(135deg,#667eea,#764ba2); padding:20px 24px; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                    <div>
                        <div style="color:rgba(255,255,255,.7); font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; margin-bottom:2px;">Setup Wizard</div>
                        <div id="bw-step-title" style="color:#fff; font-size:17px; font-weight:700;"></div>
                    </div>
                    <button onclick="menuxWizard.close()" style="background:rgba(255,255,255,.2); border:none; color:#fff; width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:18px; line-height:1; display:flex; align-items:center; justify-content:center;">&times;</button>
                </div>

                <!-- Progress bar -->
                <div style="height:4px; background:#e5e7eb; flex-shrink:0;">
                    <div id="bw-progress" style="height:100%; background:linear-gradient(90deg,#667eea,#764ba2); transition:width .4s ease; width:0%;"></div>
                </div>

                <!-- Corpo step -->
                <div id="bw-body" style="padding:28px 28px 16px; overflow-y:auto; flex:1;"></div>

                <!-- Footer navigatezione -->
                <div style="padding:16px 28px 20px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid #f0f0f0; flex-shrink:0; background:#fafafa; border-radius:0 0 16px 16px;">
                    <button id="bw-btn-back" onclick="menuxWizard.prev()" style="background:#fff; border:1px solid #d1d5db; color:#374151; padding:9px 22px; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer;">← Back</button>
                    <div id="bw-dots" style="display:flex; gap:7px;"></div>
                    <button id="bw-btn-next" onclick="menuxWizard.next()" style="background:linear-gradient(135deg,#667eea,#764ba2); border:none; color:#fff; padding:9px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(102,126,234,.35);">Next →</button>
                </div>
            </div>
        </div>
        <!-- ============== FINE WIZARD OVERLAY ============== -->

        <form method="POST" id="menux-form">
            <?php wp_nonce_field('menux_save_action', 'menux_nonce'); ?>

            <!-- ============== TOPBAR ============== -->
            <div class="bm-topbar">
                <div class="bm-topbar-brand">
                    <span class="bm-topbar-icon">🐝</span>
                    <span class="bm-topbar-title">MenuX</span>
                </div>
                <div class="bm-topbar-actions">
                    <a href="<?php echo admin_url('admin.php?page=menux-stats'); ?>" class="bm-topbar-btn bm-topbar-btn-ghost">📊 Stats</a>
                    <button type="button" onclick="menuxWizard.open()" class="bm-topbar-btn bm-topbar-btn-ghost">✨ Wizard</button>
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
                                    <?php echo menux_get_preview_markup($menu_items, $supported_langs); ?>
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
                            <div class="bm-sidebar-group-label">Style</div>
                            <button type="button" class="bm-sidebar-item" data-section="colors"   onclick="menuxGoSection('colors')">🎨 Colors</button>
                            <button type="button" class="bm-sidebar-item" data-section="typo"     onclick="menuxGoSection('typo')">🔤 Typography</button>
                            <button type="button" class="bm-sidebar-item" data-section="layout"   onclick="menuxGoSection('layout')">📐 Layout</button>
                            <button type="button" class="bm-sidebar-item" data-section="mobile"   onclick="menuxGoSection('mobile')">📱 Mobile</button>
                            <button type="button" class="bm-sidebar-item" data-section="logo"     onclick="menuxGoSection('logo')">🖼️ Logo</button>
                            <button type="button" class="bm-sidebar-item" data-section="search"   onclick="menuxGoSection('search')">🔍 Search</button>
                            <button type="button" class="bm-sidebar-item" data-section="darkmode" onclick="menuxGoSection('darkmode')">🌙 Dark Mode</button>
                            <button type="button" class="bm-sidebar-item" data-section="a11y"     onclick="menuxGoSection('a11y')">♿ Accessibility</button>
                            <button type="button" class="bm-sidebar-item" data-section="css"      onclick="menuxGoSection('css')">⚙️ Advanced</button>
                        </div>
                        <div class="bm-sidebar-group">
                            <div class="bm-sidebar-group-label">Tools</div>
                            <button type="button" class="bm-sidebar-item" data-section="import-export" onclick="menuxGoSection('import-export')">📦 Import / Export</button>
                            <?php if (!empty($supported_langs)): ?>
                            <button type="button" class="bm-sidebar-item" data-section="multilingual" onclick="menuxGoSection('multilingual')">🌐 Multilingual</button>
                            <?php endif; ?>
                        </div>
                    </nav>
                    <div class="bm-sidebar-footer">
                        <input type="submit" name="menux_save_all" class="bm-btn bm-btn-primary bm-btn-block" value="💾 Save Menu">
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

    <style>
        /* ════════════════════════════════════════════════════
           MENUX ADMIN DESIGN SYSTEM
           ════════════════════════════════════════════════════ */
        .bm-admin-modern { max-width: 1400px; }
        .bm-admin-modern * { box-sizing: border-box; }
        .bm-admin-modern .wp-heading-inline { display: none !important; }

        /* ─── TOPBAR ─── */
        .bm-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            margin: 12px 0 0;
            box-shadow: 0 4px 20px -4px rgba(102,126,234,.4);
        }
        .bm-topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bm-topbar-icon {
            width: 36px; height: 36px;
            background: rgba(255,255,255,.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 1px solid rgba(255,255,255,.3);
            flex-shrink: 0;
        }
        .bm-topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
        }
        .bm-topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .bm-topbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            border: none;
            white-space: nowrap;
            font-family: inherit;
            line-height: 1.2;
        }
        .bm-topbar-btn-ghost {
            background: rgba(255,255,255,.15);
            color: #fff;
            border: 1px solid rgba(255,255,255,.25);
        }
        .bm-topbar-btn-ghost:hover { background: rgba(255,255,255,.25); color: #fff; }
        .bm-topbar-btn-save {
            background: #fff;
            color: #4f46e5;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .bm-topbar-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }

        /* ─── STICKY PREVIEW BAR ─── */
        .bm-preview-sticky {
            position: sticky;
            top: 32px;
            z-index: 200;
            background: #fff;
            border-radius: 0 0 12px 12px;
            border: 1px solid #e5e7eb;
            border-top: none;
            box-shadow: 0 4px 16px -4px rgba(0,0,0,.1);
            margin-bottom: 0;
        }
        .bm-preview-sticky-inner {
            padding: 12px 16px 14px;
        }
        .bm-device-frame-compact {
            max-height: 140px;
            overflow: hidden;
            min-height: 60px !important;
            padding: 10px !important;
        }
        .bm-device-frame-compact .bm-preview-canvas {
            min-height: 40px;
        }

        /* ─── MAIN LAYOUT: SIDEBAR + CONTENT ─── */
        .bm-admin-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }

        /* ─── SIDEBAR ─── */
        .bm-admin-sidebar {
            background: #f8fafc;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            min-height: 500px;
        }
        .bm-sidebar-nav {
            flex: 1;
            padding: 16px 0 8px;
            overflow-y: auto;
        }
        .bm-sidebar-group {
            margin-bottom: 4px;
        }
        .bm-sidebar-group-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .8px;
            padding: 8px 16px 4px;
        }
        .bm-sidebar-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 16px;
            background: none;
            border: none;
            border-left: 3px solid transparent;
            font-size: 13px;
            font-weight: 500;
            color: #4b5563;
            cursor: pointer;
            text-align: left;
            transition: all .12s;
            font-family: inherit;
            line-height: 1.3;
        }
        .bm-sidebar-item:hover {
            background: #eef2ff;
            color: #4f46e5;
            border-left-color: #c7d2fe;
        }
        .bm-sidebar-item.active {
            background: linear-gradient(90deg, #eef2ff, #f5f3ff);
            color: #4f46e5;
            border-left-color: #667eea;
            font-weight: 600;
        }
        .bm-sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        /* ─── CONTENT AREA ─── */
        .bm-admin-content {
            padding: 24px;
            min-height: 500px;
            background: #fff;
        }

        /* ─── PANELS ─── */
        .bm-panel { display: block; }

        /* Hide the style panel's horizontal tab nav — sidebar replaces it */
        .bm-admin-layout .bm-tabs-nav { display: none; }
        /* Remove the style card outer wrapper top border since nav is gone */
        .bm-admin-layout .menux-style-panel > .bm-card-header { display: none; }

        /* ─── CARD UNIVERSAL ─── */
        .bm-admin-modern .bm-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.03);
            border: 1px solid #f1f5f9;
            margin-bottom: 20px;
            overflow: hidden;
            transition: box-shadow .2s;
        }
        .bm-admin-modern .bm-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.06); }

        .bm-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        }
        .bm-card-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: #eef2ff;
            flex-shrink: 0;
        }
        .bm-card-icon-blue   { background: #dbeafe; }
        .bm-card-icon-pink   { background: #fce7f3; }
        .bm-card-icon-purple { background: #ede9fe; }
        .bm-card-icon-amber  { background: #fef3c7; }
        .bm-card-icon-green  { background: #d1fae5; }
        .bm-card-titles { flex: 1; min-width: 0; }
        .bm-card-title {
            margin: 0 0 2px;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            padding: 0;
        }
        .bm-card-subtitle {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        .bm-card-body { padding: 22px; }

        /* ─── FORM ELEMENTS ─── */
        .bm-form-row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 16px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f9fafb;
        }
        .bm-form-row:last-child { border-bottom: none; padding-bottom: 0; }
        .bm-form-row:first-child { padding-top: 0; }
        .bm-form-label-text {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            display: block;
            padding-top: 7px;
        }
        .bm-form-help {
            font-size: 12px;
            color: #6b7280;
            margin: 6px 0 0;
            line-height: 1.5;
        }
        .bm-form-help code {
            background: #f3f4f6;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 11px;
            color: #4b5563;
        }
        .bm-admin-modern .bm-input {
            width: 100%;
            max-width: 520px;
            padding: 8px 12px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #111827;
            transition: border-color .15s, box-shadow .15s;
            font-family: inherit;
        }
        .bm-admin-modern .bm-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,.15);
        }
        .bm-admin-modern .bm-select { padding-right: 32px; cursor: pointer; }
        .bm-select-sm { font-size: 12px !important; padding: 5px 8px !important; max-width: 150px !important; }

        /* ─── BUTTONS ─── */
        .bm-admin-modern .bm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
            font-family: inherit;
            line-height: 1.2;
        }
        .bm-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 2px 6px rgba(102,126,234,.3);
        }
        .bm-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,.4); color: #fff; }
        .bm-btn-secondary {
            background: #fff;
            color: #374151;
            border-color: #d1d5db;
        }
        .bm-btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }
        .bm-btn-sm { padding: 5px 12px; font-size: 11px; }
        .bm-btn-block { width: 100%; }

        /* ─── ALERT ─── */
        .bm-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }
        .bm-alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .bm-alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .bm-alert-icon { font-size: 16px; flex-shrink: 0; line-height: 1.4; }

        /* ─── LANGUAGE CHIPS ─── */
        .bm-lang-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .bm-lang-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            color: #4338ca;
        }
        .bm-lang-chip strong { font-family: monospace; font-weight: 700; }
        .bm-lang-chip-label { color: #6366f1; opacity: .8; font-size: 11px; }

        /* ─── IMPORT/EXPORT GRID ─── */
        .bm-impexp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) { .bm-impexp-grid { grid-template-columns: 1fr; } }
        .bm-impexp-action {
            display: flex;
            gap: 14px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
        }
        .bm-impexp-action-icon {
            width: 40px; height: 40px;
            background: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .bm-impexp-action-content { flex: 1; min-width: 0; }
        .bm-impexp-action-content strong {
            display: block;
            font-size: 13px;
            color: #111827;
            margin-bottom: 4px;
        }
        .bm-impexp-action-content p {
            margin: 0 0 10px;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }

        /* ─── PREVIEW FRAME ─── */
        .bm-preview-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f9fafb;
            border-radius: 8px;
            flex-wrap: wrap;
        }
        .bm-preview-tip {
            font-size: 11px;
            color: #6b7280;
            margin-left: auto;
            font-style: italic;
        }
        .bm-active-theme-badge {
            background: linear-gradient(135deg,#eef2ff,#e0e7ff);
            color: #4338ca;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid #c7d2fe;
        }
        /* ─── DEVICE SWITCHER ─── */
        .bm-device-switcher {
            display: flex;
            gap: 2px;
            background: #f1f3f5;
            border-radius: 8px;
            padding: 3px;
        }
        .bm-device-btn {
            background: none;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .bm-device-btn:hover { color: #374151; background: rgba(255,255,255,.6); }
        .bm-device-btn.active { background: #fff; color: #4f46e5; box-shadow: 0 1px 3px rgba(0,0,0,.12); }

        /* ─── DEVICE FRAME WRAP ─── */
        .bm-device-frame-wrap {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 16px;
            min-height: 80px;
            transition: background .3s;
        }
        .bm-device-outer {
            transition: width .3s cubic-bezier(.4,0,.2,1), max-width .3s;
            width: 100%;
        }
        .bm-device-outer.bm-device-desktop { width: 100%; }
        .bm-device-outer.bm-device-tablet  { width: 768px; max-width: 100%; }
        .bm-device-outer.bm-device-mobile  { width: 375px; max-width: 100%; }

        /* Device chrome (browser bar for tablet/mobile) */
        .bm-device-chrome { background: #e5e7eb; border-radius: 10px 10px 0 0; padding: 8px 12px; }
        .bm-device-chrome-bar { display: flex; align-items: center; gap: 6px; }
        .bm-chrome-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .bm-chrome-urlbar { flex: 1; height: 18px; background: #fff; border-radius: 9px; margin-left: 8px; opacity: .7; }

        .bm-preview-frame {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .bm-device-chrome + .bm-preview-frame { border-radius: 0 0 10px 10px; border-top: none; }
        .bm-preview-canvas {
            background: linear-gradient(45deg, #f3f4f6 25%, transparent 25%, transparent 75%, #f3f4f6 75%),
                        linear-gradient(45deg, #f3f4f6 25%, #fafafa 25%, #fafafa 75%, #f3f4f6 75%);
            background-size: 16px 16px;
            background-position: 0 0, 8px 8px;
            min-height: 60px;
            padding: 14px;
        }

        /* ─── BUILDER GRID ─── */
        .bm-add-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) { .bm-add-grid { grid-template-columns: 1fr; } }

        /* ─── STRUTTURA MENU ─── */
        .bm-tip-inline {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .bm-tip-divider { color: #d1d5db; margin: 0 6px; }
        .bm-lang-mini {
            background: #eef2ff;
            color: #4338ca;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-family: monospace;
            font-weight: 600;
        }
        .bm-items-count {
            background: #f3f4f6;
            color: #4b5563;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            margin-left: auto;
        }
        .bm-empty-msg {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #9ca3af;
            font-size: 13px;
            padding: 32px 20px;
            background: #f9fafb;
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            margin: 0;
        }
        .bm-empty-msg-icon { font-size: 22px; opacity: .6; }

        /* ─── DRAG & DROP ITEMS (modernizzato, mantiene .menux-item) ─── */
        .menux-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 12px 14px;
            margin-bottom: 8px;
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: all .15s;
        }
        .menux-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            border-color: #c7d2fe;
        }
        .menux-item.ui-sortable-helper {
            box-shadow: 0 8px 24px rgba(102,126,234,.25);
            background: #fff;
            border-color: #667eea;
        }
        .menux-item .dashicons-menu {
            cursor: move;
            color: #9ca3af;
            font-size: 20px;
            margin-top: 4px;
            flex-shrink: 0;
            transition: color .15s;
        }
        .menux-item:hover .dashicons-menu { color: #667eea; }
        .menux-item-has-subs { background: linear-gradient(180deg, #fafbff 0%, #fff 100%); border-color: #c7d2fe; }
        .menux-sortable-placeholder {
            background: linear-gradient(135deg,#eef2ff,#e0e7ff) !important;
            border: 2px dashed #667eea !important;
            visibility: visible !important;
            height: 60px !important;
            margin-bottom: 8px !important;
            border-radius: 10px !important;
        }

        /* ─── TABS NAV (style panel) ─── */
        .bm-tabs-nav {
            display: flex;
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 22px;
            overflow-x: auto;
            background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        }
        .bm-tabs-nav::-webkit-scrollbar { height: 4px; }
        .bm-tabs-nav::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
        .bm-tab-btn {
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -1px;
            padding: 12px 18px;
            font-size: 13px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            white-space: nowrap;
            transition: all .15s;
            position: relative;
        }
        .bm-tab-btn:hover { color: #4f46e5; background: rgba(102,126,234,.04); }
        .bm-tab-btn.active {
            color: #4f46e5;
            border-bottom-color: #667eea;
            font-weight: 600;
            background: transparent;
        }

        /* ─── TAB PANE (tabelle interne) ─── */
        .bm-tab-pane h4,
        .bm-tab-pane .bm-section-title,
        .bm-tab-pane > div > div[style*="text-transform:uppercase"] {
            position: relative;
        }

        /* ─── BUTTON SAVE FIXED ─── */
        .bm-admin-modern .button-primary,
        .bm-admin-modern input[type="submit"].button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            text-shadow: none !important;
            box-shadow: 0 2px 6px rgba(102,126,234,.3) !important;
            padding: 8px 22px !important;
            height: auto !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
        }
        .bm-admin-modern .button-primary:hover {
            box-shadow: 0 4px 12px rgba(102,126,234,.45) !important;
            transform: translateY(-1px);
        }
        /* Sidebar & topbar submit buttons — override WP defaults */
        .bm-admin-modern input[type="submit"].bm-btn-primary {
            -webkit-appearance: none;
            appearance: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: #fff !important;
            border: none !important;
            text-shadow: none !important;
            box-shadow: 0 2px 6px rgba(102,126,234,.3) !important;
            height: auto !important;
            cursor: pointer;
            border-radius: 8px !important;
            font-weight: 600 !important;
        }
        .bm-admin-modern input[type="submit"].bm-btn-primary:hover {
            box-shadow: 0 4px 12px rgba(102,126,234,.45) !important;
            transform: translateY(-1px);
        }
        .bm-admin-modern input[type="submit"].bm-topbar-btn-save {
            -webkit-appearance: none;
            appearance: none;
            background: #fff !important;
            color: #4f46e5 !important;
            border: none !important;
            text-shadow: none !important;
            height: auto !important;
            cursor: pointer;
        }
        .bm-admin-modern input[type="submit"].bm-topbar-btn-save:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.2) !important;
        }

        /* ─── MOBILE RESPONSIVE ─── */
        @media (max-width: 900px) {
            .bm-admin-layout { grid-template-columns: 1fr; }
            .bm-admin-sidebar { border-right: none; border-bottom: 1px solid #e5e7eb; min-height: auto; }
            .bm-sidebar-nav { display: flex; flex-wrap: wrap; padding: 8px; }
            .bm-sidebar-group { margin-bottom: 0; }
            .bm-sidebar-group-label { display: none; }
            .bm-sidebar-item { width: auto; border-left: none; border-bottom: 3px solid transparent; padding: 6px 12px; }
            .bm-sidebar-item.active { border-bottom-color: #667eea; border-left-color: transparent; }
        }
        @media (max-width: 782px) {
            .bm-topbar { flex-wrap: wrap; gap: 8px; }
            .bm-card-body { padding: 16px; }
            .bm-card-header { padding: 14px 16px; }
            .bm-form-row { grid-template-columns: 1fr; gap: 8px; }
            .bm-form-label-text { padding-top: 0; }
            .bm-admin-content { padding: 16px; }
        }

        /* ════════════════════════════════════════════════════
           STILI ORIGINALI (preservati)
           ════════════════════════════════════════════════════ */
        .menux-panel .postbox { padding:15px; margin-bottom:20px; }
        .menux-panel h3 { margin-top:0; }
        /* Input lingua compatti nei drag'n drop */
        .bm-lang-input { flex:1; min-width:80px; max-width:150px; font-size:11px !important; padding:3px 5px !important; height:26px !important; box-sizing:border-box; }
        /* Input lingua nei pannelli Aggiungi */
        .bm-add-lang-inputs { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:6px; }
        .bm-add-lang-input  { flex:1; min-width:80px; max-width:150px; font-size:12px; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; }
        .bm-add-lang-input:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.15); }
        /* Riga input — tutto su una riga senza wrap */
        .bm-inputs-row { display:flex; gap:5px; align-items:center; flex-wrap:nowrap; margin-top:4px; overflow-x:auto; }
        /* Select visibilità */
        .menux-visibility { min-width:110px; font-size:12px; height:26px; padding:2px 4px; }
        .menux-user-logged-in .bs-show-only-when-non-auth, .menux-user-guest .bs-show-only-when-auth { display: none !important; }

        /* ---- Theme Modal Cards ---- */
        .bm-modal-card { background:#fff; border:2px solid #e5e7eb; border-radius:8px; cursor:pointer; transition:all .15s; position:relative; overflow:hidden; }
        .bm-modal-card:hover { border-color:#667eea; transform:translateY(-2px); box-shadow:0 6px 20px rgba(102,126,234,.15); }
        .bm-modal-card.bm-mc-selected { border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.25); }

        /* ---- Submenu zone ---- */
        .bm-sm-item { transition: background .15s; }
        .bm-sm-item:hover { background: #f0f6fc !important; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        try {
            var $ul = $('#menux-sortable');
            if ($ul.length && typeof $.fn.sortable === 'function') {
                $ul.sortable({
                    handle: '.dashicons-menu',
                    axis: 'y',
                    placeholder: 'menux-sortable-placeholder',
                    forcePlaceholderSize: true,
                    helper: 'clone',
                    opacity: 0.9,
                    start: function(e, ui) { ui.placeholder.height(ui.item.outerHeight()); },
                    update: function() { menux_reindex(); menux_updatePreview(); }
                });
            }
        } catch(e) {}

        $(document).on('input change', '.menux-panel input, .menux-panel select', function() { menux_updatePreview(); });
        $(document).on('input change', '.menux-style-panel input, .menux-style-panel select, .menux-style-panel textarea', function() { menux_liveStylePreview(); });

        // Inizializzazione al caricamento
        menux_liveStylePreview();
        menux_updatePreview();
    });

    var menux_index = <?php echo (int) count($menu_items); ?>;
    var menux_supported_langs = <?php echo json_encode($supported_langs); ?>;
    var menux_wp_roles = <?php echo wp_json_encode($menux_wp_roles_for_select); ?>;

    function menux_build_vis_options(selectedVal) {
        var html = '';
        for (var val in menux_wp_roles) {
            html += '<option value="' + val + '"' + (val === selectedVal ? ' selected' : '') + '>' + menux_wp_roles[val] + '</option>';
        }
        return html;
    }
    var menux_all_pages = <?php echo json_encode(array_map(function($p){ return array('id'=>$p->ID,'title'=>$p->post_title); }, $all_wp_pages)); ?>;
    var menux_reload_langs_nonce  = '<?php echo wp_create_nonce('menux_reload_languages_nonce'); ?>';
    var menux_export_config_nonce = '<?php echo wp_create_nonce('menux_export_config_nonce'); ?>';
    var menux_import_config_nonce = '<?php echo wp_create_nonce('menux_import_config_nonce'); ?>';

    function menux_buildPageOptions() {
        var html = '<option value="">📄 WP Page...</option>';
        menux_all_pages.forEach(function(p) {
            html += '<option value="' + p.id + '" data-title="' + menux_esc(p.title) + '">' + menux_esc(p.title) + '</option>';
        });
        return html;
    }

    var menuxStyleSections = ['colors','typo','layout','mobile','logo','search','darkmode','a11y','css'];

    function menuxGoSection(section) {
        // Hide all panels
        document.querySelectorAll('.bm-panel').forEach(function(p) { p.style.display = 'none'; });
        // Update sidebar active state
        document.querySelectorAll('.bm-sidebar-item').forEach(function(b) { b.classList.remove('active'); });
        var activeBtn = document.querySelector('.bm-sidebar-item[data-section="' + section + '"]');
        if (activeBtn) activeBtn.classList.add('active');
        // Show the correct panel
        if (menuxStyleSections.indexOf(section) !== -1) {
            var stylePanel = document.getElementById('panel-style');
            if (stylePanel) stylePanel.style.display = '';
            if (typeof menux_switchTab === 'function') menux_switchTab('bm-tab-' + section);
        } else {
            var panel = document.getElementById('panel-' + section);
            if (panel) panel.style.display = '';
        }
    }

    /* ================================================================
       PRESET THEMES
       ================================================================ */
    var bmThemes = {

    /* ── EXISTING THEMES (refined) ── */

    'style_1': {
        title: 'Executive Blue',
        badge: 'Corporate',
        colors: ['linear-gradient(135deg,#1e3a8a,#1e40af)', '#bfdbfe', '#ffffff'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#1e3a8a,#1e40af)', container_border: '',
            link_color: '#bfdbfe', link_hover_color: '#ffffff', link_hover_bg: '#3b5da8',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#2956b0', link_active_font_weight: '600',
            hamburger_color: '#ffffff', hamburger_bg: '', mobile_menu_bg: '#1e3a8a',
            font_size: '14', font_weight: '500', gap: '4', padding_x: '18', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '12', mobile_menu_shadow: '1',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#fca5a5', last_item_hover_color: '#fecaca', last_item_bg: '',
            link_transition: '0.25', link_animation: 'none', text_transform: '', letter_spacing: '0.2', link_border_radius: '4'
        }
    },
    'style_2': {
        title: 'Clean Minimal',
        badge: 'Minimal',
        colors: ['#ffffff', '#475569', '#6366f1'],
        data: {
            container_bg: '#ffffff', container_bg_gradient: '', container_border: '#e2e8f0',
            link_color: '#475569', link_hover_color: '#0f172a', link_hover_bg: '',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#6366f1', link_active_border: '#6366f1', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#475569', hamburger_bg: '', mobile_menu_bg: '#ffffff',
            font_size: '14', font_weight: '500', gap: '12', padding_x: '20', padding_y: '26',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '20', mobile_menu_shadow: '1',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#6366f1', last_item_hover_color: '#4f46e5', last_item_bg: '#eef2ff',
            link_transition: '0.25', link_animation: 'underline', text_transform: '', letter_spacing: '', link_border_radius: '6'
        }
    },
    'style_3': {
        title: 'Lavender Pill',
        badge: 'Soft',
        colors: ['#f5f3ff', '#7c3aed', '#ffffff'],
        data: {
            container_bg: '#f5f3ff', container_bg_gradient: '', container_border: '',
            link_color: '#7c3aed', link_hover_color: '#5b21b6', link_hover_bg: '#ede9fe',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#7c3aed', link_active_font_weight: '600',
            hamburger_color: '#7c3aed', hamburger_bg: '#ede9fe', mobile_menu_bg: '#f5f3ff',
            font_size: '14', font_weight: '500', gap: '6', padding_x: '18', padding_y: '10',
            hamburger_style: 'modern', hamburger_align: 'center', mobile_menu_pad: '15', mobile_menu_shadow: '1',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#dc2626', last_item_hover_color: '#b91c1c', last_item_bg: '#fee2e2',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '', link_border_radius: '20'
        }
    },
    'style_4': {
        title: 'Carbon Dark',
        badge: 'Tech',
        colors: ['#111827', '#6b7280', '#00d4ff'],
        data: {
            container_bg: '#111827', container_bg_gradient: '', container_border: '#1f2937',
            link_color: '#6b7280', link_hover_color: '#00d4ff', link_hover_bg: '#1f2937',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(0,212,255,0.1),rgba(0,212,255,0))',
            link_active_bg_gradient: '',
            link_active_color: '#00d4ff', link_active_border: '', link_active_bg: '#1a2e3a', link_active_font_weight: '500',
            hamburger_color: '#9ca3af', hamburger_bg: '', mobile_menu_bg: '#111827',
            font_size: '13', font_weight: '400', gap: '2', padding_x: '20', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '0', mobile_menu_shadow: '1',
            font_family: 'Roboto Mono, monospace', google_font: 'Roboto Mono',
            push_last_item: '1', last_item_color: '#f87171', last_item_hover_color: '#fca5a5', last_item_bg: '',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '0.4', link_border_radius: '3'
        }
    },
    'style_5': {
        title: 'Midnight Aurora',
        badge: 'Premium',
        colors: ['linear-gradient(135deg,#13001e,#1a1a2e)', '#c4b5fd', '#a3e635'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#13001e,#1a1a2e,#162032)', container_border: '',
            link_color: '#c4b5fd', link_hover_color: '#a3e635', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(163,230,53,0.12),rgba(163,230,53,0))',
            link_active_bg_gradient: '',
            link_active_color: '#a3e635', link_active_border: '#a3e635', link_active_bg: '', link_active_font_weight: '700',
            hamburger_color: '#c4b5fd', hamburger_bg: '', mobile_menu_bg: '#0d0010',
            font_size: '13', font_weight: '500', gap: '4', padding_x: '16', padding_y: '24',
            hamburger_style: 'minimal', hamburger_align: 'flex-end', mobile_menu_pad: '20', mobile_menu_shadow: '1',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#f87171', last_item_hover_color: '#fca5a5', last_item_bg: '',
            link_transition: '0.3', link_animation: 'none', text_transform: 'uppercase', letter_spacing: '0.8', link_border_radius: '4'
        }
    },
    'style_6': {
        title: 'Swiss Corporate',
        badge: 'Management',
        colors: ['#ffffff', '#334155', '#2563eb'],
        data: {
            container_bg: '#ffffff', container_bg_gradient: '', container_border: '#e2e8f0',
            link_color: '#334155', link_hover_color: '#0f172a', link_hover_bg: '#f8fafc',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#2563eb', link_active_border: '#2563eb', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#334155', hamburger_bg: '', mobile_menu_bg: '#ffffff',
            font_size: '14', font_weight: '500', gap: '0', padding_x: '24', padding_y: '20',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '0', mobile_menu_shadow: '1',
            font_family: 'system-ui, -apple-system, sans-serif', google_font: '',
            push_last_item: '1', last_item_color: '#dc2626', last_item_hover_color: '#b91c1c', last_item_bg: '#fff5f5',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '', link_border_radius: ''
        }
    },
    'style_7': {
        title: 'Indigo Tabs',
        badge: 'ERP/CRM',
        colors: ['#f1f5f9', '#475569', '#4f46e5'],
        data: {
            container_bg: '#f1f5f9', container_bg_gradient: '', container_border: '#818cf8',
            link_color: '#475569', link_hover_color: '#3730a3', link_hover_bg: '#e0e7ff',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#4f46e5', link_active_font_weight: '600',
            hamburger_color: '#4f46e5', hamburger_bg: '', mobile_menu_bg: '#f1f5f9',
            font_size: '14', font_weight: '500', gap: '4', padding_x: '20', padding_y: '12',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '10', mobile_menu_shadow: '0',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#dc2626', last_item_hover_color: '#dc2626', last_item_bg: '',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '', link_border_radius: '8'
        }
    },
    'style_8': {
        title: 'Electric Gradient',
        badge: 'Modern',
        colors: ['linear-gradient(135deg,#0052D4,#4364F7,#6FB1FC)', '#dbeafe', '#ffffff'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#0052D4,#4364F7,#6FB1FC)', container_border: '',
            link_color: '#dbeafe', link_hover_color: '#ffffff', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(255,255,255,0.18),rgba(255,255,255,0))',
            link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#1d4ed8', link_active_font_weight: '600',
            hamburger_color: '#ffffff', hamburger_bg: '', mobile_menu_bg: '#1e40af',
            font_size: '14', font_weight: '500', gap: '4', padding_x: '16', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '12', mobile_menu_shadow: '1',
            font_family: 'Raleway, sans-serif', google_font: 'Raleway',
            push_last_item: '1', last_item_color: '#fde68a', last_item_hover_color: '#fef3c7', last_item_bg: '',
            link_transition: '0.3', link_animation: 'none', text_transform: '', letter_spacing: '0.3', link_border_radius: '6'
        }
    },
    'style_9': {
        title: 'Terminal',
        badge: 'Developer',
        colors: ['#0d1117', '#8b949e', '#58a6ff'],
        data: {
            container_bg: '#0d1117', container_bg_gradient: '', container_border: '#30363d',
            link_color: '#8b949e', link_hover_color: '#58a6ff', link_hover_bg: '#161b22',
            link_hover_bg_gradient: '',  link_active_bg_gradient: '',
            link_active_color: '#c9d1d9', link_active_border: '#388bfd', link_active_bg: '', link_active_font_weight: '500',
            hamburger_color: '#8b949e', hamburger_bg: '', mobile_menu_bg: '#0d1117',
            font_size: '13', font_weight: '400', gap: '6', padding_x: '18', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '12', mobile_menu_shadow: '1',
            font_family: 'JetBrains Mono, monospace', google_font: 'JetBrains Mono',
            push_last_item: '1', last_item_color: '#f85149', last_item_hover_color: '#ff7b72', last_item_bg: '',
            link_transition: '0.15', link_animation: 'none', text_transform: '', letter_spacing: '0.3', link_border_radius: '4'
        }
    },
    'style_10': {
        title: 'Poppins Analytics',
        badge: 'Dashboard',
        colors: ['#ffffff', '#64748b', '#6366f1'],
        data: {
            container_bg: '#ffffff', container_bg_gradient: '', container_border: '#e2e8f0',
            link_color: '#64748b', link_hover_color: '#0f172a', link_hover_bg: '',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#6366f1', link_active_border: '#6366f1', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#64748b', hamburger_bg: '', mobile_menu_bg: '#ffffff',
            font_size: '13', font_weight: '400', gap: '20', padding_x: '4', padding_y: '10',
            hamburger_style: 'modern', hamburger_align: 'center', mobile_menu_pad: '16', mobile_menu_shadow: '1',
            font_family: 'Poppins, sans-serif', google_font: 'Poppins',
            push_last_item: '1', last_item_color: '#6366f1', last_item_hover_color: '#4f46e5', last_item_bg: '#eef2ff',
            link_transition: '0.2', link_animation: 'underline', text_transform: '', letter_spacing: '', link_border_radius: '4'
        }
    },

    /* ── NEW THEMES ── */

    'style_11': {
        title: 'Aurora Borealis',
        badge: 'Vibrant',
        colors: ['linear-gradient(135deg,#0f0c29,#302b63)', '#a78bfa', '#a3e635'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#0f0c29,#302b63,#24243e)', container_border: '',
            link_color: '#a78bfa', link_hover_color: '#c4b5fd', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(167,139,250,0.18),rgba(167,139,250,0))',
            link_active_bg_gradient: '',
            link_active_color: '#a3e635', link_active_border: '#a3e635', link_active_bg: '', link_active_font_weight: '700',
            hamburger_color: '#a78bfa', hamburger_bg: '', mobile_menu_bg: '#100c25',
            font_size: '13', font_weight: '500', gap: '6', padding_x: '16', padding_y: '26',
            hamburger_style: 'minimal', hamburger_align: 'flex-end', mobile_menu_pad: '20', mobile_menu_shadow: '1',
            font_family: 'Nunito, sans-serif', google_font: 'Nunito',
            push_last_item: '1', last_item_color: '#fb923c', last_item_hover_color: '#fdba74', last_item_bg: '',
            link_transition: '0.3', link_animation: 'none', text_transform: 'uppercase', letter_spacing: '1', link_border_radius: '6'
        }
    },
    'style_12': {
        title: 'Obsidian',
        badge: 'Premium',
        colors: ['#09090b', '#71717a', '#fafafa'],
        data: {
            container_bg: '#09090b', container_bg_gradient: '', container_border: '#18181b',
            link_color: '#71717a', link_hover_color: '#fafafa', link_hover_bg: '#27272a',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#fafafa', link_active_border: '', link_active_bg: '#27272a', link_active_font_weight: '500',
            hamburger_color: '#71717a', hamburger_bg: '', mobile_menu_bg: '#09090b',
            font_size: '14', font_weight: '400', gap: '2', padding_x: '20', padding_y: '22',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '0', mobile_menu_shadow: '0',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#e4e4e7', last_item_hover_color: '#fafafa', last_item_bg: '#3f3f46',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '-0.2', link_border_radius: '6'
        }
    },
    'style_13': {
        title: 'Coral Sunset',
        badge: 'Energetic',
        colors: ['linear-gradient(135deg,#ff6b6b,#feca57)', '#ffffff', '#1a1a2e'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#ff6b6b,#ee5a24,#feca57)', container_border: '',
            link_color: '#ffffff', link_hover_color: '#ffffff', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(255,255,255,0.2),rgba(255,255,255,0))',
            link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#ee5a24', link_active_font_weight: '700',
            hamburger_color: '#ffffff', hamburger_bg: '', mobile_menu_bg: '#ee5a24',
            font_size: '15', font_weight: '600', gap: '6', padding_x: '18', padding_y: '20',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '16', mobile_menu_shadow: '1',
            font_family: 'Nunito, sans-serif', google_font: 'Nunito',
            push_last_item: '1', last_item_color: '#fef3c7', last_item_hover_color: '#ffffff', last_item_bg: '',
            link_transition: '0.25', link_animation: 'none', text_transform: '', letter_spacing: '0.2', link_border_radius: '8'
        }
    },
    'style_14': {
        title: 'Forest Walk',
        badge: 'Nature',
        colors: ['linear-gradient(135deg,#134e5e,#71b280)', '#d1fae5', '#ffffff'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#134e5e,#71b280)', container_border: '',
            link_color: '#d1fae5', link_hover_color: '#ffffff', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(255,255,255,0.18),rgba(255,255,255,0))',
            link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '#a7f3d0', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#d1fae5', hamburger_bg: '', mobile_menu_bg: '#134e5e',
            font_size: '14', font_weight: '500', gap: '6', padding_x: '18', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'flex-end', mobile_menu_pad: '14', mobile_menu_shadow: '1',
            font_family: 'Nunito, sans-serif', google_font: 'Nunito',
            push_last_item: '1', last_item_color: '#fde68a', last_item_hover_color: '#fef3c7', last_item_bg: '',
            link_transition: '0.3', link_animation: 'none', text_transform: '', letter_spacing: '0.3', link_border_radius: '6'
        }
    },
    'style_15': {
        title: 'Clean SaaS',
        badge: 'Startup',
        colors: ['#ffffff', '#64748b', '#6366f1'],
        data: {
            container_bg: '#ffffff', container_bg_gradient: '', container_border: '#e2e8f0',
            link_color: '#64748b', link_hover_color: '#0f172a', link_hover_bg: '#f1f5f9',
            link_hover_bg_gradient: '', link_active_bg_gradient: 'linear-gradient(90deg,#eef2ff,#e0e7ff)',
            link_active_color: '#6366f1', link_active_border: '', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#6366f1', hamburger_bg: '#eef2ff', mobile_menu_bg: '#ffffff',
            font_size: '14', font_weight: '500', gap: '8', padding_x: '14', padding_y: '24',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '18', mobile_menu_shadow: '1',
            font_family: 'Inter, sans-serif', google_font: 'Inter',
            push_last_item: '1', last_item_color: '#6366f1', last_item_hover_color: '#4f46e5', last_item_bg: '#eef2ff',
            link_transition: '0.2', link_animation: 'none', text_transform: '', letter_spacing: '', link_border_radius: '8'
        }
    },
    'style_16': {
        title: 'Neon Night',
        badge: 'Cyberpunk',
        colors: ['#0a0a0a', '#444', '#00ff88'],
        data: {
            container_bg: '#0a0a0a', container_bg_gradient: '', container_border: '#00ff88',
            link_color: '#555555', link_hover_color: '#00ff88', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(0,255,136,0.1),rgba(0,255,136,0))',
            link_active_bg_gradient: '',
            link_active_color: '#00ff88', link_active_border: '#00ff88', link_active_bg: '', link_active_font_weight: '700',
            hamburger_color: '#00ff88', hamburger_bg: '', mobile_menu_bg: '#0a0a0a',
            font_size: '12', font_weight: '400', gap: '4', padding_x: '18', padding_y: '22',
            hamburger_style: 'minimal', hamburger_align: 'flex-end', mobile_menu_pad: '12', mobile_menu_shadow: '1',
            font_family: 'Space Mono, monospace', google_font: 'Space Mono',
            push_last_item: '1', last_item_color: '#ff4757', last_item_hover_color: '#ff6b81', last_item_bg: '',
            link_transition: '0.15', link_animation: 'none', text_transform: 'uppercase', letter_spacing: '1.5', link_border_radius: '2'
        }
    },
    'style_17': {
        title: 'Rose Garden',
        badge: 'Elegant',
        colors: ['#fff0f3', '#be185d', '#ffffff'],
        data: {
            container_bg: '#fff0f3', container_bg_gradient: '', container_border: '#ffd6e0',
            link_color: '#be185d', link_hover_color: '#9d174d', link_hover_bg: '#fce7f3',
            link_hover_bg_gradient: '', link_active_bg_gradient: 'linear-gradient(135deg,#ec4899,#be185d)',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#be185d', hamburger_bg: '#fce7f3', mobile_menu_bg: '#fff0f3',
            font_size: '15', font_weight: '500', gap: '8', padding_x: '16', padding_y: '20',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '16', mobile_menu_shadow: '1',
            font_family: 'Lora, serif', google_font: 'Lora',
            push_last_item: '1', last_item_color: '#9d174d', last_item_hover_color: '#831843', last_item_bg: '#fce7f3',
            link_transition: '0.25', link_animation: 'none', text_transform: '', letter_spacing: '0.2', link_border_radius: '6'
        }
    },
    'style_18': {
        title: 'Startup Fire',
        badge: 'Bold',
        colors: ['linear-gradient(135deg,#667eea,#764ba2)', '#e0d7ff', '#ffffff'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(135deg,#667eea,#764ba2)', container_border: '',
            link_color: '#e0d7ff', link_hover_color: '#ffffff', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(255,255,255,0.2),rgba(255,255,255,0))',
            link_active_bg_gradient: '',
            link_active_color: '#ffffff', link_active_border: '', link_active_bg: '#5b4fcf', link_active_font_weight: '700',
            hamburger_color: '#ffffff', hamburger_bg: '', mobile_menu_bg: '#5b21b6',
            font_size: '15', font_weight: '600', gap: '6', padding_x: '18', padding_y: '24',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '16', mobile_menu_shadow: '1',
            font_family: 'Nunito, sans-serif', google_font: 'Nunito',
            push_last_item: '1', last_item_color: '#fde68a', last_item_hover_color: '#ffffff', last_item_bg: '',
            link_transition: '0.25', link_animation: 'underline', text_transform: '', letter_spacing: '', link_border_radius: '8'
        }
    },
    'style_19': {
        title: 'Editorial',
        badge: 'Classic',
        colors: ['#fafaf9', '#44403c', '#1c1917'],
        data: {
            container_bg: '#fafaf9', container_bg_gradient: '', container_border: '#1c1917',
            link_color: '#44403c', link_hover_color: '#1c1917', link_hover_bg: '#f5f5f4',
            link_hover_bg_gradient: '', link_active_bg_gradient: '',
            link_active_color: '#1c1917', link_active_border: '', link_active_bg: '', link_active_font_weight: '900',
            hamburger_color: '#1c1917', hamburger_bg: '', mobile_menu_bg: '#fafaf9',
            font_size: '15', font_weight: '500', gap: '0', padding_x: '22', padding_y: '22',
            hamburger_style: 'classic', hamburger_align: 'center', mobile_menu_pad: '16', mobile_menu_shadow: '0',
            font_family: 'Playfair Display, serif', google_font: 'Playfair Display',
            push_last_item: '1', last_item_color: '#44403c', last_item_hover_color: '#1c1917', last_item_bg: '',
            link_transition: '0.15', link_animation: 'none', text_transform: 'uppercase', letter_spacing: '2', link_border_radius: ''
        }
    },
    'style_20': {
        title: 'Ice & Sky',
        badge: 'Fresh',
        colors: ['linear-gradient(180deg,#e0f2fe,#f0f9ff)', '#0369a1', '#0ea5e9'],
        data: {
            container_bg: '', container_bg_gradient: 'linear-gradient(180deg,#e0f2fe,#f0f9ff)', container_border: '#bae6fd',
            link_color: '#0369a1', link_hover_color: '#0284c7', link_hover_bg: '',
            link_hover_bg_gradient: 'linear-gradient(90deg,rgba(186,230,253,0.5),rgba(186,230,253,0))',
            link_active_bg_gradient: '',
            link_active_color: '#0c4a6e', link_active_border: '#0ea5e9', link_active_bg: '', link_active_font_weight: '600',
            hamburger_color: '#0369a1', hamburger_bg: '#bae6fd', mobile_menu_bg: '#e0f2fe',
            font_size: '14', font_weight: '500', gap: '8', padding_x: '16', padding_y: '20',
            hamburger_style: 'modern', hamburger_align: 'flex-end', mobile_menu_pad: '16', mobile_menu_shadow: '0',
            font_family: 'Poppins, sans-serif', google_font: 'Poppins',
            push_last_item: '1', last_item_color: '#0369a1', last_item_hover_color: '#0284c7', last_item_bg: '#bae6fd',
            link_transition: '0.25', link_animation: 'none', text_transform: '', letter_spacing: '', link_border_radius: '8'
        }
    }

};

    // Renderizza le card
    // ================================================================
    // MODALE TEMI PREDEFINITI
    // ================================================================
    var bmThemeModal = (function() {
        var selectedKey = null;
        var snapshot    = null;

        function captureSnapshot() {
            var snap = {colors:{}, vals:{}};
            ['container_bg','container_border','link_color','link_hover_color','link_hover_bg','link_active_color','link_active_border','link_active_bg','hamburger_color','hamburger_bg','mobile_menu_bg','last_item_color','last_item_hover_color','last_item_bg'].forEach(function(f) {
                var cb = document.querySelector('input[name="menux_style_use['+f+']"]');
                var cp = document.querySelector('input[name="menux_style['+f+']"]');
                snap.colors['use_'+f] = cb ? cb.checked : false;
                snap.colors[f]        = cp ? cp.value   : '';
            });
            ['font_size','font_weight','gap','padding_x','padding_y','hamburger_style','hamburger_align','mobile_menu_pad','custom_css','link_animation','google_font','font_family','push_last_item','text_transform','letter_spacing','link_transition','link_active_font_weight','link_border_radius'].forEach(function(f) {
                var el = document.querySelector('[name="menux_style['+f+']"]');
                snap.vals[f] = el ? el.value : '';
            });
            var ms = document.querySelector('[name="menux_style[mobile_menu_shadow]"]');
            snap.vals.mobile_menu_shadow = ms ? ms.checked : false;
            return snap;
        }

        function restoreSnapshot(snap) {
            if (!snap) return;
            ['container_bg','container_border','link_color','link_hover_color','link_hover_bg','link_active_color','link_active_border','link_active_bg','hamburger_color','hamburger_bg','mobile_menu_bg','last_item_color','last_item_hover_color','last_item_bg'].forEach(function(f) {
                var cb = document.querySelector('input[name="menux_style_use['+f+']"]');
                var cp = document.querySelector('input[name="menux_style['+f+']"]');
                if (cb) { cb.checked = snap.colors['use_'+f]; menux_toggleColor(cb); }
                if (cp) cp.value = snap.colors[f];
            });
            ['font_size','font_weight','gap','padding_x','padding_y','hamburger_style','hamburger_align','mobile_menu_pad','custom_css','link_animation','google_font','font_family','push_last_item','text_transform','letter_spacing','link_transition','link_active_font_weight','link_border_radius'].forEach(function(f) {
                var el = document.querySelector('[name="menux_style['+f+']"]');
                if (el) el.value = snap.vals[f];
            });
            var ms = document.querySelector('[name="menux_style[mobile_menu_shadow]"]');
            if (ms) ms.checked = snap.vals.mobile_menu_shadow;
            menux_liveStylePreview();
        }

        function applyTheme(key) {
            var p = bmThemes[key].data;
            function setC(n,v){var cb=document.querySelector('input[name="menux_style_use['+n+']"]');var cp=document.querySelector('input[name="menux_style['+n+']"]');if(cb){cb.checked=(v!=='');menux_toggleColor(cb);}if(cp&&v!=='')cp.value=v;}
            function setV(n,v){var el=document.querySelector('[name="menux_style['+n+']"]');if(el){if(el.type==='checkbox')el.checked=(v==='1');else el.value=v;}}
            setC('container_bg',p.container_bg);setC('container_border',p.container_border);
            setC('link_color',p.link_color);setC('link_hover_color',p.link_hover_color);setC('link_hover_bg',p.link_hover_bg);
            setC('link_active_color',p.link_active_color);setC('link_active_border',p.link_active_border);setC('link_active_bg',p.link_active_bg);
            setC('hamburger_color',p.hamburger_color);setC('hamburger_bg',p.hamburger_bg);setC('mobile_menu_bg',p.mobile_menu_bg);
            setC('last_item_color',p.last_item_color);setC('last_item_hover_color',p.last_item_hover_color);setC('last_item_bg',p.last_item_bg);
            setV('font_size',p.font_size);setV('font_weight',p.font_weight);setV('gap',p.gap);
            setV('padding_x',p.padding_x);setV('padding_y',p.padding_y);
            setV('hamburger_style',p.hamburger_style);setV('hamburger_align',p.hamburger_align);
            setV('mobile_menu_pad',p.mobile_menu_pad);setV('mobile_menu_shadow',p.mobile_menu_shadow);
            setV('font_family',p.font_family);setV('google_font',p.google_font);
            setV('push_last_item',p.push_last_item);
            setV('link_animation',p.link_animation);setV('link_transition',p.link_transition);
            setV('text_transform',p.text_transform);setV('letter_spacing',p.letter_spacing);
            setV('link_active_font_weight',p.link_active_font_weight);
            setV('link_border_radius',p.link_border_radius);
            menux_liveStylePreview();
        }

        function buildGrid() {
            var grid = document.getElementById('bm-modal-theme-grid');
            if (!grid) return;
            grid.innerHTML = Object.keys(bmThemes).map(function(k) {
                var t = bmThemes[k];
                var strip = t.colors.map(function(c){ return '<div style="flex:1;background:'+c+';height:100%;"></div>'; }).join('');
                return '<div class="bm-modal-card" data-key="'+k+'" id="bm-mc-'+k+'"'
                    + ' onmouseenter="bmThemeModal._hover(\''+k+'\')"'
                    + ' onmouseleave="bmThemeModal._hoverEnd()"'
                    + ' onclick="bmThemeModal._select(\''+k+'\')">'
                    + '<div style="height:8px;display:flex;border-radius:6px 6px 0 0;overflow:hidden;">'+strip+'</div>'
                    + '<div style="padding:10px 12px 12px;">'
                    + '<div style="font-size:13px;font-weight:700;color:#1a1a2e;margin-bottom:3px;">'+t.title+'</div>'
                    + '<div style="font-size:10px;background:#f1f5f9;color:#64748b;padding:2px 7px;border-radius:10px;display:inline-block;">'+t.badge+'</div>'
                    + '</div>'
                    + '<div class="bm-mc-check" id="bm-mc-chk-'+k+'" style="display:none;position:absolute;top:8px;right:8px;background:#667eea;color:#fff;width:22px;height:22px;border-radius:50%;font-size:13px;display:none;align-items:center;justify-content:center;">✓</div>'
                    + '</div>';
            }).join('');
        }

        return {
            open: function() {
                snapshot = captureSnapshot();
                selectedKey = null;
                var overlay = document.getElementById('bm-theme-modal-overlay');
                overlay.style.display = 'flex';
                buildGrid();
                // reset apply btn
                var btn = document.getElementById('bm-modal-apply-btn');
                if (btn) { btn.disabled = true; btn.style.opacity = '.45'; }
                document.getElementById('bm-modal-selected-info').textContent = 'No theme selected — hover to preview';
            },
            close: function() {
                if (!selectedKey) restoreSnapshot(snapshot);
                document.getElementById('bm-theme-modal-overlay').style.display = 'none';
                snapshot = null;
            },
            apply: function() {
                if (!selectedKey) return;
                applyTheme(selectedKey);
                // Update badge
                var badge = document.getElementById('bm-active-theme-badge');
                if (badge) { badge.textContent = '🎨 Tema: '+bmThemes[selectedKey].title; badge.style.display='inline-block'; }
                document.getElementById('bm-theme-modal-overlay').style.display = 'none';
                snapshot = null;
                // scroll to preview
                document.getElementById('menux-preview-wrap').scrollIntoView({behavior:'smooth',block:'nearest'});
            },
            _hover: function(key) {
                applyTheme(key);
                document.querySelectorAll('.bm-modal-card').forEach(function(c){ c.style.boxShadow=''; c.style.borderColor=''; });
                var card = document.getElementById('bm-mc-'+key);
                if (card) { card.style.boxShadow='0 0 0 2px #667eea'; card.style.borderColor='#667eea'; }
            },
            _hoverEnd: function() {
                if (selectedKey) { applyTheme(selectedKey); }
                else { restoreSnapshot(snapshot); }
                document.querySelectorAll('.bm-modal-card').forEach(function(c){ c.style.boxShadow=''; c.style.borderColor=''; });
                if (selectedKey) {
                    var card = document.getElementById('bm-mc-'+selectedKey);
                    if (card) { card.style.borderColor='#667eea'; }
                }
            },
            _select: function(key) {
                // Deseleziona precedente
                if (selectedKey) {
                    var prev = document.getElementById('bm-mc-'+selectedKey);
                    if (prev) { prev.classList.remove('bm-mc-selected'); prev.style.borderColor=''; }
                    var prevChk = document.getElementById('bm-mc-chk-'+selectedKey);
                    if (prevChk) prevChk.style.display = 'none';
                }
                selectedKey = key;
                var card = document.getElementById('bm-mc-'+key);
                if (card) { card.classList.add('bm-mc-selected'); card.style.borderColor='#667eea'; }
                var chk = document.getElementById('bm-mc-chk-'+key);
                if (chk) chk.style.display = 'flex';
                applyTheme(key);
                // Abilita bottone applica
                var btn = document.getElementById('bm-modal-apply-btn');
                if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                document.getElementById('bm-modal-selected-info').innerHTML = '✅ Selected: <strong>'+bmThemes[key].title+'</strong> — press Apply to confirm';
            }
        };
    })();

    // Chiudi modale cliccando overlay
    document.addEventListener('click', function(e) {
        var overlay = document.getElementById('bm-theme-modal-overlay');
        if (e.target === overlay) bmThemeModal.close();
    });

    // Alias per menux_applyPreset (usato da wizard e import/export)
    function menux_applyPreset(presetKey) {
        var p = bmThemes[presetKey].data;
        function wizSetC(n,v){var cb=document.querySelector('input[name="menux_style_use['+n+']"]');var cp=document.querySelector('input[name="menux_style['+n+']"]');if(cb){cb.checked=(v!=='');menux_toggleColor(cb);}if(cp&&v!=='')cp.value=v;}
        function wizSetV(n,v){var el=document.querySelector('[name="menux_style['+n+']"]');if(el){if(el.type==='checkbox')el.checked=(v==='1');else el.value=v;}}
        wizSetC('container_bg',p.container_bg);wizSetC('container_border',p.container_border);
        wizSetC('link_color',p.link_color);wizSetC('link_hover_color',p.link_hover_color);wizSetC('link_hover_bg',p.link_hover_bg);
        wizSetC('link_active_color',p.link_active_color);wizSetC('link_active_border',p.link_active_border);wizSetC('link_active_bg',p.link_active_bg);
        wizSetC('hamburger_color',p.hamburger_color);wizSetC('hamburger_bg',p.hamburger_bg);wizSetC('mobile_menu_bg',p.mobile_menu_bg);
        wizSetC('last_item_color',p.last_item_color);wizSetC('last_item_hover_color',p.last_item_hover_color);wizSetC('last_item_bg',p.last_item_bg);
        wizSetV('font_size',p.font_size);wizSetV('font_weight',p.font_weight);wizSetV('gap',p.gap);
        wizSetV('padding_x',p.padding_x);wizSetV('padding_y',p.padding_y);
        wizSetV('hamburger_style',p.hamburger_style);wizSetV('hamburger_align',p.hamburger_align);
        wizSetV('mobile_menu_pad',p.mobile_menu_pad);wizSetV('mobile_menu_shadow',p.mobile_menu_shadow);
        wizSetV('font_family',p.font_family);wizSetV('google_font',p.google_font);
        wizSetV('push_last_item',p.push_last_item);
        wizSetV('link_animation',p.link_animation);wizSetV('link_transition',p.link_transition);
        wizSetV('text_transform',p.text_transform);wizSetV('letter_spacing',p.letter_spacing);
        wizSetV('link_active_font_weight',p.link_active_font_weight);
        wizSetV('link_border_radius',p.link_border_radius);
        menux_liveStylePreview();
    }

    // ================================================================
    // FUNZIONI SOTTOMENU nel drag'n drop
    // ================================================================
    function menux_toggleSubmenuZone(idx) {
        var body = document.getElementById('bm-sm-body-' + idx);
        var btn  = document.getElementById('bm-sm-toggle-' + idx);
        if (!body) return;
        var open = body.style.display !== 'none';
        body.style.display = open ? 'none' : 'block';
        if (!open) btn.style.background = '#e0f2fe';
        else       btn.style.background = '#f0f6fc';
    }

    function menux_updateSubCount(idx) {
        var list = document.getElementById('bm-sm-list-' + idx);
        var btn  = document.getElementById('bm-sm-toggle-' + idx);
        if (!list || !btn) return;
        var count = list.querySelectorAll('li.bm-sm-item').length;
        btn.textContent = count > 0 ? '📂 '+count+' sub-items ▾' : '📂 Add submenu ▾';
    }

    function menux_getSubIndex(idx) {
        var list = document.getElementById('bm-sm-list-' + idx);
        if (!list) return 0;
        return list.querySelectorAll('li.bm-sm-item').length;
    }

    function menux_buildSubItem(parentIdx, ci, type, idOrUrl, label) {
        var list = document.getElementById('bm-sm-list-' + parentIdx);
        if (!list) return;
        var li = document.createElement('li');
        li.className = 'bm-sm-item';
        li.style.cssText = 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 8px;margin-bottom:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;';

        var base = 'menu_items['+parentIdx+'][children]['+ci+']';

        var inner = '<span style="font-size:16px;color:#9ca3af;cursor:move;" title="Trascina">⠿</span>';

        // Type selector
        var selPageSel = type === 'page' ? ' selected' : '';
        var selCustSel = type !== 'page' ? ' selected' : '';
        inner += '<select name="'+base+'[type]" class="bm-child-type-sel" onchange="menux_toggleChildType(this)" style="font-size:10px;height:24px;padding:1px 4px;border-radius:4px;border:1px solid #c3d4e8;color:#1d4ed8;background:#dbeafe;font-weight:600;max-width:90px;">'
            + '<option value="page"'+selPageSel+'>📄 Page</option>'
            + '<option value="custom"'+selCustSel+'>🔗 Link</option>'
            + '</select>';

        // Page select wrapper
        var pageDisplay = type === 'page' ? 'flex' : 'none';
        var pageOpts = '<option value="">-- Page --</option>';
        menux_all_pages.forEach(function(p) {
            var sel2 = (type === 'page' && String(p.id) === String(idOrUrl)) ? ' selected' : '';
            pageOpts += '<option value="'+p.id+'"'+sel2+'>'+menux_esc(p.title)+'</option>';
        });
        inner += '<div class="bm-child-page-wrap" style="display:'+pageDisplay+';align-items:center;gap:4px;">'
            + '<select name="'+base+'[id]" style="font-size:11px;height:24px;padding:1px 4px;max-width:160px;">'+pageOpts+'</select>'
            + '</div>';

        // URL wrapper
        var urlDisplay = type !== 'page' ? 'flex' : 'none';
        var urlVal = type !== 'page' ? menux_esc(idOrUrl) : '';
        inner += '<div class="bm-child-url-wrap" style="display:'+urlDisplay+';align-items:center;gap:4px;">'
            + '<input type="text" name="'+base+'[url]" value="'+urlVal+'" placeholder="https://..." style="font-size:11px;width:130px;height:24px;padding:2px 5px;">'
            + '</div>';

        // Campi lingua
        if (menux_supported_langs.length > 0) {
            menux_supported_langs.forEach(function(lang) {
                var key = 'lang_' + lang.code.replace(/-/g,'_');
                inner += '<input type="text" name="'+base+'['+key+']" value="'+menux_esc(label)+'" placeholder="'+lang.code+'" title="'+lang.label+'" style="font-size:11px;width:80px;height:24px;padding:2px 5px;">';
            });
        } else {
            inner += '<input type="text" name="'+base+'[lang_it_IT]" value="'+menux_esc(label)+'" placeholder="Label" style="font-size:11px;width:110px;height:24px;padding:2px 5px;">';
        }

        inner += '<div style="display:inline-flex;align-items:center;gap:2px;">';
        inner += '<input type="text" name="'+base+'[icon]" value="" placeholder="icona" class="bm-icon-input" style="font-size:11px;width:90px;height:24px;padding:2px 5px;">';
        inner += '<button type="button" onclick="mxIconPicker.open(this.previousElementSibling)" style="padding:1px 5px;font-size:11px;height:24px;cursor:pointer;background:#f9fafb;border:1px solid #d1d5db;border-radius:3px;" tabindex="-1">🎨</button>';
        inner += '</div>';
        inner += '<select name="'+base+'[target]" style="font-size:11px;height:24px;padding:1px 3px;"><option value="">= tab</option><option value="_blank">↗ nuova</option></select>';
        inner += '<button type="button" onclick="this.closest(\'li\').remove();menux_updateSubCount('+parentIdx+');" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;border-radius:3px;padding:1px 7px;font-size:12px;cursor:pointer;margin-left:auto;">&times;</button>';

        li.innerHTML = inner;
        list.appendChild(li);
        menux_updateSubCount(parentIdx);
    }

    function menux_esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Toggle campo pagina/URL nei sottomenu
    function menux_toggleChildType(sel) {
        var li = sel.closest('li.bm-sm-item');
        if (!li) return;
        var pageWrap = li.querySelector('.bm-child-page-wrap');
        var urlWrap  = li.querySelector('.bm-child-url-wrap');
        if (sel.value === 'page') {
            if (pageWrap) pageWrap.style.display = 'flex';
            if (urlWrap)  urlWrap.style.display  = 'none';
        } else {
            if (pageWrap) pageWrap.style.display = 'none';
            if (urlWrap)  urlWrap.style.display  = 'flex';
        }
    }

    function menux_addSubBlank(parentIdx) {
        var ci = menux_getSubIndex(parentIdx);
        menux_buildSubItem(parentIdx, ci, 'custom', '', '');
        var body = document.getElementById('bm-sm-body-'+parentIdx);
        if (body) body.style.display = 'block';
    }

    function menux_addSubPage(parentIdx) {
        var ci = menux_getSubIndex(parentIdx);
        menux_buildSubItem(parentIdx, ci, 'page', '', '');
        var body = document.getElementById('bm-sm-body-'+parentIdx);
        if (body) body.style.display = 'block';
    }

    function menux_addSubCustom(parentIdx) {
        var urlInp   = document.getElementById('bm-sm-url-'   + parentIdx);
        var labelInp = document.getElementById('bm-sm-label-' + parentIdx);
        var url   = urlInp   ? urlInp.value.trim()   : '';
        var label = labelInp ? labelInp.value.trim() : '';
        if (!url) { alert('Please enter a URL'); return; }
        var ci = menux_getSubIndex(parentIdx);
        menux_buildSubItem(parentIdx, ci, 'custom', url, label || url);
        if (urlInp)   urlInp.value   = '';
        if (labelInp) labelInp.value = '';
        var body = document.getElementById('bm-sm-body-'+parentIdx);
        if (body) body.style.display = 'block';
    }
    // ---- FINE SOTTOMENU ----

    function menux_exportConfig() {
        jQuery.post(ajaxurl, {
            action: 'menux_export_config',
            nonce: menux_export_config_nonce
        }, function(response) {
            if (!response.success) { alert('Export error.'); return; }
            var json = JSON.stringify(response.data, null, 2);
            var blob = new Blob([json], {type: 'application/json'});
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = 'menux-config-' + new Date().toISOString().slice(0,10) + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }).fail(function() { alert('Communication error.'); });
    }

    function menux_importConfig(input) {
        var file = input.files[0];
        if (!file) return;
        var msg = document.getElementById('menux-import-msg');
        var reader = new FileReader();
        reader.onload = function(e) {
            var raw = e.target.result;
            // Verifica validità JSON prima di inviare
            try { JSON.parse(raw); } catch(err) {
                if (msg) { msg.style.display='inline'; msg.style.color='#d63638'; msg.textContent='❌ Invalid JSON file.'; }
                input.value = '';
                return;
            }
            if (msg) { msg.style.display='inline'; msg.style.color='#666'; msg.textContent='⏳ Importing...'; }
            // Base64-encode per evitare problemi di escaping con wp_magic_quotes
            var encoded = btoa(unescape(encodeURIComponent(raw)));
            jQuery.post(ajaxurl, {
                action: 'menux_import_config',
                nonce:  menux_import_config_nonce,
                config: encoded
            }, function(response) {
                if (response.success) {
                    var d = response.data;
                    var info = ' (' + d.items_count + ' items, URL: ' + (d.api_url || 'not set') + ')';
                    if (msg) { msg.style.display='inline'; msg.style.color='#2e7d32'; msg.textContent='✅ Imported' + info + ' — reloading...'; }
                    setTimeout(function() {
                        // Usa ajaxurl per ricavare la base dell'admin, aggiunge cache-buster
                        var adminBase = ajaxurl.replace('admin-ajax.php', 'admin.php');
                        window.location.href = adminBase + '?page=menux&_nc=' + Date.now();
                    }, 1500);
                } else {
                    if (msg) { msg.style.display='inline'; msg.style.color='#d63638'; msg.textContent='❌ ' + response.data; }
                }
            }).fail(function() {
                if (msg) { msg.style.display='inline'; msg.style.color='#d63638'; msg.textContent='❌ Communication error.'; }
            });
            input.value = '';
        };
        reader.readAsText(file);
    }

    function menux_reloadLanguages() {
        var btn = document.getElementById('menux-reload-langs-btn');
        var msg = document.getElementById('menux-reload-langs-msg');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Loading...'; }
        if (msg) { msg.style.display = 'none'; }

        jQuery.post(ajaxurl, {
            action: 'menux_reload_languages',
            nonce: menux_reload_langs_nonce
        }, function(response) {
            if (btn) { btn.disabled = false; btn.textContent = '🔄 Refresh'; }
            if (response.success && response.data) {
                menux_supported_langs = response.data;
                if (msg) {
                    msg.style.display = 'inline';
                    msg.style.color = '#2e7d32';
                    msg.textContent = '✅ Languages updated (' + response.data.length + '). Save to apply.';
                }
                // Aggiorna la visualizzazione delle lingue nella card
                var langsWrap = btn.closest('td');
                if (langsWrap && response.data.length > 0) {
                    var newHtml = '<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">';
                    response.data.forEach(function(lang) {
                        newHtml += '<span style="background:#e8f4f8; border:1px solid #bee3f8; border-radius:3px; padding:3px 8px; font-size:12px; font-family:monospace;"><strong>' + lang.code + '</strong> — ' + lang.label + '</span>';
                    });
                    newHtml += '<button type="button" id="menux-reload-langs-btn" class="button button-small" onclick="menux_reloadLanguages()">🔄 Refresh</button>';
                    newHtml += '</div><span id="menux-reload-langs-msg" style="margin-left:10px; font-size:13px; color:#2e7d32;">✅ Languages updated (' + response.data.length + '). Save to apply.</span>';
                    langsWrap.innerHTML = newHtml;
                }
                // Aggiorna anche i campi lingua nei pannelli Aggiungi
                menux_rebuildAddPanelLangs();
            } else {
                if (msg) {
                    msg.style.display = 'inline';
                    msg.style.color = '#d63638';
                    msg.textContent = '❌ Could not retrieve languages. Check your multilingual plugin is active.';
                }
            }
        }).fail(function() {
            if (btn) { btn.disabled = false; btn.textContent = '🔄 Refresh'; }
            if (msg) {
                msg.style.display = 'inline';
                msg.style.color = '#d63638';
                msg.textContent = '❌ Server communication error.';
            }
        });
    }

    /* Ricostruisce i campi lingua nei pannelli Aggiungi dopo un reload AJAX */
    function menux_rebuildAddPanelLangs() {
        [['bm-page-lang-inputs','page-lang-'], ['bm-custom-lang-inputs','custom-lang-']].forEach(function(pair) {
            var el = document.getElementById(pair[0]);
            if (!el) return;
            el.innerHTML = '';
            menux_supported_langs.forEach(function(lang) {
                var key = 'lang_' + lang.code.replace(/-/g, '_');
                var inp = document.createElement('input');
                inp.type = 'text';
                inp.id = pair[1] + key;
                inp.placeholder = lang.code;
                inp.title = lang.label;
                inp.className = 'bm-add-lang-input';
                el.appendChild(inp);
            });
        });
    }

    function menux_reindex() {
        var list = document.getElementById('menux-sortable');
        if (!list) return;
        var rows = list.querySelectorAll('li.menux-item');
        rows.forEach(function(row, i) {
            row.querySelectorAll('input, select').forEach(function(el) {
                if (el.name) { el.name = el.name.replace(/menu_items\[\d+\]/, 'menu_items[' + i + ']'); }
            });
        });
        menux_index = rows.length;
        var emptyMsg = document.getElementById('menux-empty-msg');
        if (emptyMsg) emptyMsg.style.display = rows.length ? 'none' : 'block';
    }

    function menux_removeItem(btn) {
        btn.closest('li').remove();
        menux_reindex();
        menux_updatePreview();
    }

    function menux_addPage() {
        var sel = document.getElementById('page-select');
        var id = sel.value;
        if (!id) { alert('-- Select a page --'); return; }
        var title = sel.options[sel.selectedIndex].getAttribute('data-title') || '';
        sel.value = '';
        // Raccoglie i valori dei campi lingua dinamici
        var langValues = {};
        if (menux_supported_langs.length > 0) {
            menux_supported_langs.forEach(function(lang) {
                var key = 'lang_' + lang.code.replace(/-/g, '_');
                var inp = document.getElementById('page-lang-' + key);
                langValues[key] = (inp ? inp.value.trim() : '') || title;
                if (inp) inp.value = '';
            });
        }
        menux_buildRow('page', id, langValues, '', title);
    }

    function menux_addCustom() {
        var urlInp  = document.getElementById('custom-url');
        var iconInp = document.getElementById('custom-icon');
        var url  = urlInp.value.trim();
        if (!url) { alert('Please enter a URL'); return; }
        var icon = iconInp.value.trim();
        // Raccoglie i valori dei campi lingua dinamici
        var langValues = {};
        var firstLabel = url;
        if (menux_supported_langs.length > 0) {
            menux_supported_langs.forEach(function(lang, i) {
                var key = 'lang_' + lang.code.replace(/-/g, '_');
                var inp = document.getElementById('custom-lang-' + key);
                langValues[key] = inp ? inp.value.trim() : '';
                if (i === 0 && langValues[key]) firstLabel = langValues[key];
                if (inp) inp.value = '';
            });
        }
        urlInp.value = ''; iconInp.value = '';
        menux_buildRow('custom', url, langValues, icon, firstLabel || url);
    }

    function menux_buildRow(type, valOrId, langValues, icon, titleDisplay, extraData) {
        var idx = menux_index;
        var ul = document.getElementById('menux-sortable');
        var li = document.createElement('li'); li.className = 'menux-item';
        
        var drag = document.createElement('span'); drag.className = 'dashicons dashicons-menu'; 
        li.appendChild(drag);
        
        var container = document.createElement('div'); container.style.cssText = 'flex:1; min-width:0;';
        var titleDiv = document.createElement('div'); titleDiv.style.cssText = 'font-weight:600; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#3c434a;';
        titleDiv.textContent = (type === 'page' ? '📄 ' : '🔗 ') + titleDisplay;
        container.appendChild(titleDiv);

        var inputsDiv1 = document.createElement('div'); inputsDiv1.className = 'bm-inputs-row';

        var makeHidden = function(n, v) { var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'menu_items[' + idx + '][' + n + ']'; inp.value = v; return inp; };
        var makeTxt = function(n, v, ph, cls, w) {
            var inp = document.createElement('input'); inp.type='text'; inp.name='menu_items['+idx+']['+n+']'; inp.value=v||''; inp.placeholder=ph||''; inp.className=cls||'bm-lang-input';
            if(w) inp.style.cssText='flex:none;width:'+w;
            return inp;
        };

        inputsDiv1.appendChild(makeHidden('type', type));
        if (type === 'page') inputsDiv1.appendChild(makeHidden('id', valOrId)); else inputsDiv1.appendChild(makeHidden('url', valOrId));
        // item_key hidden (autogenerato)
        var keyVal = extraData && extraData.item_key ? extraData.item_key : (type==='page'?'page_'+valOrId:'c_'+Math.random().toString(36).substr(2,6));
        inputsDiv1.appendChild(makeHidden('item_key', keyVal));

        // Campi lingua dinamici
        if (menux_supported_langs.length > 0) {
            menux_supported_langs.forEach(function(lang) {
                var key = 'lang_' + lang.code.replace(/-/g, '_');
                var val = (langValues && langValues[key]) ? langValues[key] : '';
                var inp = document.createElement('input');
                inp.type = 'text'; inp.name = 'menu_items[' + idx + '][' + key + ']';
                inp.value = val; inp.placeholder = lang.code; inp.title = lang.label; inp.className = 'bm-lang-input';
                inputsDiv1.appendChild(inp);
            });
        }

        // Icona
        inputsDiv1.appendChild(makeTxt('icon', icon||'', '🔣 Icon', 'bm-lang-input'));

        // Badge text + colori
        var badgeTxt = makeTxt('badge', (extraData&&extraData.badge)||'', '🏷️ Badge', 'bm-lang-input', '64px');
        badgeTxt.title = 'Es: New, Hot, Sale';
        inputsDiv1.appendChild(badgeTxt);
        // Badge bg color
        var badgeBgInp = document.createElement('input'); badgeBgInp.type='color'; badgeBgInp.name='menu_items['+idx+'][badge_bg]'; badgeBgInp.value=(extraData&&extraData.badge_bg)||'#ef4444'; badgeBgInp.title='Badge background color'; badgeBgInp.style.cssText='width:30px;height:26px;padding:1px;border:1px solid #ccc;border-radius:3px;cursor:pointer;flex-none;';
        inputsDiv1.appendChild(badgeBgInp);

        // Visibility (WP role-based)
        var visSel = document.createElement('select'); visSel.name = 'menu_items[' + idx + '][visibility]'; visSel.className = 'menux-visibility';
        visSel.innerHTML = menux_build_vis_options(extraData && extraData.visibility ? extraData.visibility : 'all');
        inputsDiv1.appendChild(visSel);

        // Target _blank
        var targetSel = document.createElement('select'); targetSel.name='menu_items['+idx+'][target]'; targetSel.style.cssText='font-size:11px;height:26px;padding:2px 4px;min-width:75px;';
        targetSel.innerHTML='<option value="">Same tab</option><option value="_blank">New tab</option>';
        if (extraData && extraData.target) targetSel.value = extraData.target;
        inputsDiv1.appendChild(targetSel);

        // ──── FEATURE 2: Notification Dot ────
        var notifLabel = document.createElement('label');
        notifLabel.title = 'Show notification dot';
        notifLabel.style.cssText = 'display:flex;align-items:center;gap:3px;font-size:11px;color:#555;cursor:pointer;white-space:nowrap;';
        var notifCb = document.createElement('input'); notifCb.type='checkbox'; notifCb.name='menu_items['+idx+'][notif_dot]'; notifCb.value='1';
        notifLabel.appendChild(notifCb);
        notifLabel.appendChild(document.createTextNode('🔴'));
        inputsDiv1.appendChild(notifLabel);

        // ──── FEATURE 11: Location ────
        var locSel = document.createElement('select'); locSel.name='menu_items['+idx+'][menu_location]'; locSel.style.cssText='font-size:11px;height:26px;padding:2px 4px;min-width:75px;'; locSel.title='Menu location';
        locSel.innerHTML='<option value="primary">📍 Primary</option><option value="footer">📍 Footer</option><option value="sidebar">📍 Sidebar</option><option value="mobile">📍 Mobile</option>';
        inputsDiv1.appendChild(locSel);

        container.appendChild(inputsDiv1);

        // ──── Riga avanzata (schedule + condizionali) ────
        var details = document.createElement('details');
        details.style.marginTop = '4px';
        var summary = document.createElement('summary');
        summary.style.cssText = 'font-size:10px;color:#6b7280;cursor:pointer;user-select:none;';
        summary.textContent = '⚙️ Advanced options';
        details.appendChild(summary);

        var advRow1 = document.createElement('div');
        advRow1.className = 'bm-inputs-row';
        advRow1.style.marginTop = '6px';
        advRow1.innerHTML = '<label style="font-size:10px;color:#555;">📅 From:</label>'
            + '<input type="datetime-local" name="menu_items['+idx+'][schedule_start]" style="font-size:10px;height:24px;padding:2px 4px;max-width:160px;">'
            + '<label style="font-size:10px;color:#555;">to:</label>'
            + '<input type="datetime-local" name="menu_items['+idx+'][schedule_end]" style="font-size:10px;height:24px;padding:2px 4px;max-width:160px;">';
        details.appendChild(advRow1);

        var advRow2 = document.createElement('div');
        advRow2.className = 'bm-inputs-row';
        advRow2.style.marginTop = '4px';
        advRow2.innerHTML = '<input type="text" name="menu_items['+idx+'][cond_roles]" placeholder="Roles" style="font-size:10px;height:24px;padding:2px 4px;max-width:140px;">'
            + '<select name="menu_items['+idx+'][cond_devices]" style="font-size:10px;height:24px;padding:1px 2px;max-width:100px;" title="Device"><option value="">📱 All devices</option><option value="mobile">📱 Mobile only</option><option value="desktop">🖥️ Desktop only</option></select>'
            + '<input type="text" name="menu_items['+idx+'][cond_time_from]" placeholder="⏰ From HH:MM" style="font-size:10px;height:24px;padding:2px 4px;max-width:70px;">'
            + '<input type="text" name="menu_items['+idx+'][cond_time_to]" placeholder="⏰ To HH:MM" style="font-size:10px;height:24px;padding:2px 4px;max-width:70px;">'
            + '<input type="text" name="menu_items['+idx+'][cond_utm]" placeholder="UTM source" style="font-size:10px;height:24px;padding:2px 4px;max-width:100px;">';
        details.appendChild(advRow2);
        container.appendChild(details);

        // Submenu zone per righe create dinamicamente
        var smZone = document.createElement('div');
        smZone.className = 'bm-submenu-zone';
        smZone.id = 'bm-sm-zone-' + idx;
        smZone.style.cssText = 'margin-top:6px; border-top:1px dashed #e5e7eb; padding-top:6px;';

        var smToggleBtn = document.createElement('button');
        smToggleBtn.type = 'button';
        smToggleBtn.className = 'button button-small';
        smToggleBtn.id = 'bm-sm-toggle-' + idx;
        smToggleBtn.style.cssText = 'font-size:11px; background:#f0f6fc; border-color:#c3d4e8; color:#2271b1;';
        smToggleBtn.textContent = '📂 Add submenu ▾';
        smToggleBtn.setAttribute('onclick', 'menux_toggleSubmenuZone(' + idx + ')');
        smZone.appendChild(smToggleBtn);

        var smBody = document.createElement('div');
        smBody.id = 'bm-sm-body-' + idx;
        smBody.style.display = 'none';
        smBody.style.marginTop = '8px';

        var smList = document.createElement('ul');
        smList.className = 'bm-sm-list';
        smList.id = 'bm-sm-list-' + idx;
        smList.style.cssText = 'list-style:none;padding:0;margin:0 0 6px;border-left:3px solid #c3d4e8;padding-left:10px;';
        smBody.appendChild(smList);

        // Barra aggiungi sub-items
        var smAddBar = document.createElement('div');
        smAddBar.style.cssText = 'display:flex;gap:5px;align-items:center;flex-wrap:wrap;';
        smAddBar.innerHTML = '<button type="button" onclick="menux_addSubBlank(' + idx + ')" class="button button-small" style="font-size:11px;background:#f0f6fc;border-color:#c3d4e8;color:#2271b1;">+ Add sub-item</button>'
            + '<span style="font-size:10px;color:#9ca3af;">Use the type selector to choose Page or Link</span>';
        smBody.appendChild(smAddBar);

        smZone.appendChild(smBody);
        container.appendChild(smZone);

        li.appendChild(container);

        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'button button-small'; btn.title = 'Remove'; btn.innerHTML = '&times;'; btn.style.cssText = 'margin-top:3px; flex-shrink:0;';
        btn.onclick = function() { menux_removeItem(this); }; li.appendChild(btn);
        
        ul.appendChild(li); menux_index = idx + 1;
        menux_updatePreview();
        var emptyMsg = document.getElementById('menux-empty-msg'); if (emptyMsg) emptyMsg.style.display = 'none';
    }

    function menux_updatePreview() {
        var items = [];
        var rows = document.querySelectorAll('#menux-sortable li.menux-item');
        rows.forEach(function(row) {
            var typeEl = row.querySelector('input[name*="[type]"]');
            var urlEl  = row.querySelector('input[name*="[url]"]');
            var iconEl = row.querySelector('input[name*="[icon]"]');
            var visEl  = row.querySelector('select[name*="[visibility]"]');
            var type = typeEl ? typeEl.value : 'custom';
            var url  = (type === 'custom' && urlEl) ? (urlEl.value || '#') : '#';
            var icon = iconEl ? iconEl.value.trim() : '';
            var vis  = visEl ? visEl.value : 'all';
            // Usa il primo campo lingua trovato come label di anteprima
            var firstLangEl = row.querySelector('input[name*="[lang_"]');
            var label = '';
            if (firstLangEl && firstLangEl.value.trim()) {
                label = firstLangEl.value.trim();
            } else if (type === 'custom' && url !== '#') {
                label = url;
            } else {
                label = 'Item';
            }
            items.push({url: url, label: label, icon: icon, vis: vis});
        });

        var wrap = document.getElementById('menux-preview-wrap');
        if (items.length === 0) {
            wrap.innerHTML = '<div style="padding:20px; text-align:center; color:#555; font-style:italic;">Empty menu</div>';
            menux_liveStylePreview();
            return;
        }
        var html = '<nav id="menux-preview-nav" class="menux-container menux-user-guest" style="width:100%; font-family:sans-serif;">' +
            '<div class="menux-hamburger" onclick="this.classList.toggle(\'open\'); this.nextElementSibling.classList.toggle(\'show\');">' +
            '<span></span><span></span><span></span></div>' +
            '<ul class="menux-list" style="padding:10px;">';
        items.forEach(function(item) {
            var liClass = '';
            if (item.vis === 'auth')     liClass = 'bs-show-only-when-auth';
            if (item.vis === 'non-auth') liClass = 'bs-show-only-when-non-auth';
            var iconHtml = item.icon ? '<i class="'+item.icon+'" style="margin-right:8px;"></i>' : '';
            html += '<li class="' + liClass + '"><a href="' + item.url + '" class="menux-link">' + iconHtml + '<span class="menux-label">' + item.label + '</span></a></li>';
        });
        html += '</ul></nav>';
        wrap.innerHTML = html;
        menux_updatePreviewAuth();
        menux_liveStylePreview();
    }

    function menux_updatePreviewAuth() {
        var toggle = document.getElementById('preview-auth-toggle');
        var nav = document.getElementById('menux-preview-nav');
        if (!toggle || !nav) return;
        if (toggle.value === 'logged') { nav.classList.remove('menux-user-guest'); nav.classList.add('menux-user-logged-in'); } 
        else { nav.classList.remove('menux-user-logged-in'); nav.classList.add('menux-user-guest'); }
    }

    function menux_toggleColor(checkbox) {
        var row = checkbox.closest('.menux-color-row');
        var picker = row.querySelector('input[type="color"]');
        picker.style.opacity = checkbox.checked ? '1' : '0.35';
        menux_liveStylePreview();
    }

    function menux_liveStylePreview() {
        function color(field) {
            var cb = document.querySelector('input[name="menux_style_use[' + field + ']"]');
            var cp = document.querySelector('input[name="menux_style[' + field + ']"]');
            return (cb && cb.checked && cp) ? cp.value : '';
        }
        function val(field) {
            var el = document.querySelector('[name="menux_style[' + field + ']"]');
            return el ? el.value.trim() : '';
        }
        function isChecked(field) {
            var el = document.querySelector('[name="menux_style[' + field + ']"]');
            return el ? el.checked : false;
        }

        var css = '';

        // Container
        var cbg = color('container_bg'), cborder = color('container_border');
        if (cbg || cborder) {
            css += '#menux-preview-nav{';
            if (cbg)     css += 'background:' + cbg + ';';
            if (cborder) css += 'border-bottom:1px solid ' + cborder + ';';
            css += '}';
        }

        // Layout Desktop base (strutturale)
        var gap = val('gap') || '20';
        var navJustify = val('nav_justify') || 'flex-start';
        css += '#menux-preview-nav .menux-list{display:flex; list-style:none; margin:0; flex-wrap:wrap; gap:' + gap + 'px; justify-content:' + navJustify + ';}';
        css += '#menux-preview-nav .menux-hamburger{display:none;}';

        // Entrance animation keyframes in preview
        var entAnim = (function(){ var el = document.querySelector('[name="menux_style[entrance_animation]"]:checked'); return el ? el.value : 'none'; })();
        var entDur  = val('entrance_duration') || '0.5';
        var entDel  = val('entrance_delay')    || '0';
        var entStag = parseFloat(val('entrance_stagger') || '0');
        if (entAnim !== 'none') {
            css += '@keyframes bm-entrance-fadeIn{from{opacity:0}to{opacity:1}}';
            css += '@keyframes bm-entrance-slideDown{from{opacity:0;transform:translateY(-24px)}to{opacity:1;transform:translateY(0)}}';
            css += '@keyframes bm-entrance-slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}';
            css += '@keyframes bm-entrance-slideLeft{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:translateX(0)}}';
            css += '@keyframes bm-entrance-slideRight{from{opacity:0;transform:translateX(-24px)}to{opacity:1;transform:translateX(0)}}';
            css += '@keyframes bm-entrance-zoomIn{from{opacity:0;transform:scale(0.85)}to{opacity:1;transform:scale(1)}}';
            css += '@keyframes bm-entrance-flipX{from{opacity:0;transform:perspective(400px) rotateX(-60deg)}to{opacity:1;transform:perspective(400px) rotateX(0)}}';
            if (entStag > 0) {
                document.querySelectorAll('#menux-preview-nav .menux-list > li').forEach(function(li, i) {
                    li.style.animation = 'bm-entrance-' + entAnim + ' ' + entDur + 's ease both ' + (parseFloat(entDel) + i * entStag) + 's';
                });
            } else {
                css += '#menux-preview-nav{animation:bm-entrance-'+entAnim+' '+entDur+'s ease both '+entDel+'s;}';
            }
        }

        // Stile Voci di Menu
        var lc = color('link_color'), fs = val('font_size'), fw = val('font_weight');
        var px = val('padding_x') || '0', py = val('padding_y') || '0';
        var lr = 'text-decoration:none; display:inline-flex; align-items:center; font-weight:bold; color:#333; transition:0.3s;';
        if (lc) lr += 'color:' + lc + ' !important;';
        if (fs) lr += 'font-size:' + fs + 'px;';
        if (fw) lr += 'font-weight:' + fw + ' !important;';
        if (parseInt(px) || parseInt(py)) lr += 'padding:' + py + 'px ' + px + 'px !important;';
        else lr += 'padding:8px 12px;';
        css += '#menux-preview-nav .menux-list li a.menux-link{' + lr + '}';

        // Hover / Active
        var hc = color('link_hover_color'), ac = color('link_active_color'), ab = color('link_active_border');
        if (hc) css += '#menux-preview-nav .menux-list li a.menux-link:hover{color:' + hc + ' !important;}';
        if (ac || ab) {
            css += '#menux-preview-nav .menux-list li a.menux-link.active{';
            if (ac) css += 'color:' + ac + ' !important;';
            if (ab) css += 'border-bottom:2px solid ' + ab + ';';
            css += '}';
        }

        // Hamburger Stile Predefinito
        var hstyle = val('hamburger_style') || 'classic';
        var h_gap = '5px', h_height = '3px', h_radius = '3px', h_width = '30px';
        if(hstyle === 'modern') { h_height = '4px'; h_radius = '10px'; h_gap = '6px'; }
        else if(hstyle === 'minimal') { h_height = '2px'; h_radius = '0px'; h_gap = '7px'; h_width = '35px'; }

        var halign = val('hamburger_align') || 'flex-end';
        var hbg = color('hamburger_bg');
        var h_bg_css = hbg ? 'background:'+hbg+'; border-radius:4px;' : '';
        css += '#menux-preview-nav .menux-hamburger {cursor:pointer; padding:12px; flex-direction:column; gap:'+h_gap+'; width:'+h_width+'; box-sizing:content-box; align-self:'+halign+'; ' + h_bg_css + '}';
        
        var hcolor = color('hamburger_color') || '#333333';
        css += '#menux-preview-nav .menux-hamburger span {height:'+h_height+'; background:'+hcolor+'; width:100%; display:block; transition:all 0.3s ease-in-out; border-radius:'+h_radius+';}';
        
        // Animazione Preview Hamburger
        css += '#menux-preview-nav .menux-hamburger.open span:nth-child(1) { transform: translateY(calc('+h_gap+' + '+h_height+')) rotate(45deg); }';
        css += '#menux-preview-nav .menux-hamburger.open span:nth-child(2) { opacity: 0; }';
        css += '#menux-preview-nav .menux-hamburger.open span:nth-child(3) { transform: translateY(calc(-'+h_gap+' - '+h_height+')) rotate(-45deg); }';

        // Breakpoint per anteprima live
        var bp = val('mobile_breakpoint') || '768';
        var bpMode = (function(){ var el = document.querySelector('[name="menux_style[mobile_breakpoint_mode]"]:checked'); return el ? el.value : 'manual'; })();
        var mob_bg = color('mobile_menu_bg');
        var mob_bg_css = mob_bg ? 'background:'+mob_bg+';' : '';
        var m_pad = val('mobile_menu_pad') || '0';
        var m_shad = isChecked('mobile_menu_shadow') ? 'box-shadow: 0 4px 12px rgba(0,0,0,0.1);' : '';
        var openStyle = (function(){ var el = document.querySelector('[name="menux_style[mobile_open_style]"]:checked'); return el ? el.value : 'dropdown'; })();
        var ovBg = color('mobile_overlay_bg') || '#000000';
        var ovOp = val('mobile_overlay_opacity') || '0.5';
        var fsAlign = val('mobile_fullscreen_align') || 'center';
        var drawerW = val('mobile_drawer_width') || '280';

        // Device-aware preview: force mobile styles when tablet/mobile device is active
        var activeDevice = window._bmPreviewDevice || 'desktop';
        var effectiveBp = (activeDevice === 'mobile') ? 99999
                        : (activeDevice === 'tablet') ? 99999
                        : bp;
        var mediaWrap = '@media(max-width:'+effectiveBp+'px){';

        css += mediaWrap;
        css += '  #menux-preview-nav { display: flex; flex-direction: row; align-items:center; flex-wrap:wrap; }';
        css += '  #menux-preview-nav .menux-hamburger { display:flex; }';

        if (openStyle === 'dropdown') {
            css += '  #menux-preview-nav .menux-list { display:none; flex-direction:column; width:100%; box-sizing:border-box; gap:0; padding:'+m_pad+'px; '+mob_bg_css+' '+m_shad+' }';
            css += '  #menux-preview-nav .menux-list.show { display:flex; animation: bm-fadeIn .3s ease both; }';
            css += '  #menux-preview-nav .menux-list li { width: 100%; text-align: center; border-bottom: 1px solid rgba(0,0,0,0.05); }';
            css += '  #menux-preview-nav .menux-list li:last-child { border-bottom: none; }';
            css += '  #menux-preview-nav .menux-list li a.menux-link { padding: 15px !important; display:flex; justify-content:center; width:100%; box-sizing:border-box; }';
        } else if (openStyle === 'fullscreen') {
            var fsBg = mob_bg_css || 'background:rgba(15,15,15,.97);';
            css += '  #menux-preview-nav .menux-list { display:none; position:fixed; inset:0; z-index:99999; flex-direction:column; align-items:'+fsAlign+'; justify-content:center; padding:60px 20px 20px; box-sizing:border-box; '+fsBg+' overflow-y:auto; }';
            css += '  #menux-preview-nav .menux-list.show { display:flex; animation: bm-fadeIn .3s ease both; }';
            css += '  #menux-preview-nav .menux-list li { width:100%; text-align:'+(fsAlign==='center'?'center':fsAlign==='flex-end'?'right':'left')+'; border-bottom:none; }';
            css += '  #menux-preview-nav .menux-list li a.menux-link { padding:16px 32px !important; font-size:clamp(20px,4vw,32px) !important; font-weight:700 !important; display:flex; justify-content:'+fsAlign+'; }';
        } else if (openStyle === 'drawer-left' || openStyle === 'drawer-right') {
            var isRight = openStyle === 'drawer-right';
            var side = isRight ? 'right:0;' : 'left:0;';
            css += '  #menux-preview-nav .menux-list { display:none; position:fixed; top:0; '+side+' width:min('+drawerW+'px,90vw); height:100vh; z-index:99999; flex-direction:column; align-items:flex-start; justify-content:flex-start; padding:64px 0 24px; box-sizing:border-box; '+mob_bg_css+' box-shadow:'+(isRight?'-4px':' 4px')+' 0 24px rgba(0,0,0,.2); overflow-y:auto; }';
            css += '  #menux-preview-nav .menux-list.show { display:flex; animation:'+(isRight?'bm-slideRight':'bm-slideLeft')+' .3s ease both; }';
            css += '  #menux-preview-nav .menux-list li { width:100%; border-bottom:1px solid rgba(128,128,128,.1); }';
            css += '  #menux-preview-nav .menux-list li a.menux-link { padding:14px 24px !important; display:flex; justify-content:flex-start; font-size:15px !important; }';
        }
        css += '}';

        // Keyframes nell anteprima
        css += '@keyframes bm-fadeIn{from{opacity:0}to{opacity:1}}';
        css += '@keyframes bm-slideLeft{from{opacity:0;transform:translateX(-100%)}to{opacity:1;transform:translateX(0)}}';
        css += '@keyframes bm-slideRight{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}';

        // Pulsante close nel preview
        css += '.menux-close-btn{display:none;position:fixed;top:16px;right:20px;background:none;border:none;font-size:32px;cursor:pointer;color:inherit;opacity:.7;z-index:100000;padding:8px;}';
        css += '.menux-close-btn.visible{display:block;}';
        css += '.menux-close-btn:hover{opacity:1;}';

        var customEl = document.querySelector('[name="menux_style[custom_css]"]');
        if (customEl && customEl.value.trim()) css += customEl.value;

        var tag = document.getElementById('menux-live-style');
        if (!tag) { tag = document.createElement('style'); tag.id = 'menux-live-style'; document.head.appendChild(tag); }
        tag.textContent = css;
    }

    /* ================================================================
       DEVICE SWITCHER
       ================================================================ */
    window._bmPreviewDevice = 'desktop';

    function menux_setDevice(device) {
        window._bmPreviewDevice = device;

        // Update button states
        document.querySelectorAll('.bm-device-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.device === device);
        });

        // Update outer frame class
        var outer = document.getElementById('bm-device-outer');
        if (outer) {
            outer.className = 'bm-device-outer bm-device-' + device;
        }

        // Show/hide device chrome bar
        var chrome = document.getElementById('bm-device-chrome');
        if (chrome) {
            chrome.style.display = (device !== 'desktop') ? 'block' : 'none';
        }

        // Update label
        var label = document.getElementById('bm-preview-device-label');
        if (label) {
            var labels = {
                desktop: '🖥️ Desktop — full width',
                tablet:  '📱 Tablet — 768px',
                mobile:  '📲 Mobile — 375px'
            };
            label.textContent = labels[device] || '';
        }

        // Re-run preview with forced breakpoint
        menux_liveStylePreview();
    }

    /* ================================================================
       MENUX WIZARD
       ================================================================ */
    var menuxWizard = (function() {

        var state = {
            currentStep: 0,
            selectedPages:    [],   // [{id, title}]
            customLinks:      [],   // [{url, label, icon, visibility}]
            apiUrl:           '',
            style: {
                linkColor:        '',
                linkHoverColor:   '',
                linkActiveColor:  '',
                hamburgerColor:   '',
                fontSize:         '',
                fontWeight:       '',
                gap:              '20',
            }
        };

        /* ---- Definizione degli step ---- */
        var steps = [
            { title: '🌐 Configure the API Server',      render: renderStepApi,    validate: validateApi },
            { title: '📄 Choose Pages',             render: renderStepPages,  validate: validatePages },
            { title: '🔗 Custom Links',          render: renderStepLinks,  validate: function(){ return true; } },
            { title: '🎨 Menu Style',               render: renderStepStyle,  validate: function(){ return true; } },
            { title: '✅ Summary & Save',            render: renderStepSummary,validate: function(){ return true; } },
        ];

        /* ---- Helpers UI ---- */
        function label(txt, sub) {
            return '<div style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:'+(sub?'4px':'14px')+';">'+txt+'</div>'
                + (sub ? '<div style="font-size:13px; color:#6b7280; margin-bottom:14px;">'+sub+'</div>' : '');
        }
        function card(inner, extra) {
            return '<div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px; margin-bottom:12px;'+(extra||'')+'">'+inner+'</div>';
        }
        function inputStyle(extra) {
            return 'width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box; outline:none;'+(extra||'');
        }

        /* ---- STEP 1: API URL ---- */
        function renderStepApi() {
            var cur = state.apiUrl || (document.querySelector('[name="menux_api_base_url"]') || {}).value || '';
            return label('Multilingual plugin', 'Enter the Base URL of your server. You can skip this step if you don\'t use the multilingual API.')
                + '<input id="bw-api-url" type="url" value="' + esc(cur) + '" placeholder="https://esempio.terranovasoftware.eu" style="' + inputStyle() + '">'
                + '<p style="font-size:12px; color:#9ca3af; margin:8px 0 0;">Es: <code>https://beeself-lab-01.terranovasoftware.eu</code></p>';
        }
        function validateApi() {
            var v = document.getElementById('bw-api-url');
            state.apiUrl = v ? v.value.trim() : '';
            return true;
        }

        /* ---- STEP 2: Page Selection ---- */
        function renderStepPages() {
            var sel = document.getElementById('page-select');
            if (!sel) return '<p style="color:#d63638;">No pages found on this site.</p>';
            var opts = sel.querySelectorAll('option[value]');
            var html = label('Which pages do you want in the menu?', 'Select the pages to show. You can change the order later.');
            if (!opts.length) return html + '<p style="color:#9ca3af; font-style:italic;">No WordPress pages available.</p>';
            html += '<div style="display:flex; flex-direction:column; gap:8px;">';
            opts.forEach(function(opt) {
                if (!opt.value) return;
                var id = opt.value;
                var title = opt.getAttribute('data-title') || opt.textContent.trim();
                var checked = state.selectedPages.some(function(p){ return p.id == id; });
                html += '<label style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:'+(checked?'#ede9fe':'#f9fafb')+'; border:1px solid '+(checked?'#a78bfa':'#e5e7eb')+'; border-radius:8px; cursor:pointer; transition:all .15s;">'
                    + '<input type="checkbox" data-page-id="'+id+'" data-page-title="'+esc(title)+'" '+(checked?'checked':'')+' style="width:16px; height:16px; cursor:pointer;" onchange="menuxWizard._togglePage(this)">'
                    + '<span style="font-size:14px; font-weight:500; color:#374151;">📄 '+esc(title)+'</span>'
                    + '</label>';
            });
            html += '</div>';
            return html;
        }
        function validatePages() {
            // Rileggere le checkbox al momento della validazione (già aggiornate da _togglePage)
            return true;
        }

        /* ---- STEP 3: Link Personalizzati ---- */
        function renderStepLinks() {
            var html = label('Add custom links?', 'External links, anchors or non-WordPress pages. You can leave this section empty.');
            html += '<div id="bw-custom-list" style="display:flex; flex-direction:column; gap:8px; margin-bottom:12px;">';
            state.customLinks.forEach(function(lk, i) {
                html += renderCustomLinkRow(i, lk);
            });
            html += '</div>';
            html += '<button type="button" onclick="menuxWizard._addLink()" style="display:inline-flex; align-items:center; gap:6px; background:#fff; border:2px dashed #a78bfa; color:#7c3aed; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">+ Add Link</button>';
            return html;
        }
        function renderCustomLinkRow(i, lk) {
            return '<div id="bw-link-'+i+'" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px; display:flex; flex-direction:column; gap:8px;">'
                + '<div style="display:flex; gap:8px; flex-wrap:wrap;">'
                + '<input type="text" placeholder="Label (e.g. Blog)" value="'+esc(lk.label||'')+'" oninput="menuxWizard._updateLink('+i+',\'label\',this.value)" style="'+inputStyle('flex:1; min-width:140px;')+'">'
                + '<input type="url"  placeholder="https://..." value="'+esc(lk.url||'')+'" oninput="menuxWizard._updateLink('+i+',\'url\',this.value)" style="'+inputStyle('flex:2; min-width:200px;')+'">'
                + '</div>'
                + '<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">'
                + '<input type="text" placeholder="Icona (es. fas fa-star)" value="'+esc(lk.icon||'')+'" oninput="menuxWizard._updateLink('+i+',\'icon\',this.value)" style="'+inputStyle('flex:1;')+'">'
                + '<select onchange="menuxWizard._updateLink('+i+',\'visibility\',this.value)" style="padding:9px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; background:#fff; flex:0 0 auto;">'
                + menux_build_vis_options(lk.visibility || 'all')
                + '</select>'
                + '<button type="button" onclick="menuxWizard._removeLink('+i+')" style="background:#fee2e2; border:none; color:#dc2626; width:32px; height:32px; border-radius:6px; cursor:pointer; font-size:15px;">✕</button>'
                + '</div></div>';
        }

        /* ---- STEP 4: Style ---- */
        function renderStepStyle() {
            var s = state.style;
            var html = label('How should the menu look?', 'Set colors and typography. You can change them later in the Style panel.');

            function colorRow(lbl, key, placeholder) {
                return '<div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #f0f0f0;">'
                    + '<span style="width:180px; font-size:13px; color:#374151;">'+lbl+'</span>'
                    + '<input type="color" value="'+(s[key]||'#333333')+'" oninput="menuxWizard._styleVal(\''+key+'\',this.value)" style="width:40px; height:32px; border:none; border-radius:4px; cursor:pointer; padding:0;">'
                    + '<input type="text" placeholder="'+placeholder+'" value="'+(s[key]||'')+'" oninput="menuxWizard._styleVal(\''+key+'\',this.value);this.previousElementSibling.value=this.value" style="'+inputStyle('max-width:120px; margin-left:4px;')+'">'
                    + '</div>';
            }

            html += card(
                colorRow('Link color', 'linkColor', '#333333')
                + colorRow('Link color (hover)', 'linkHoverColor', '#0066cc')
                + colorRow('Link color (active)', 'linkActiveColor', '#0066cc')
                + colorRow('Hamburger color', 'hamburgerColor', '#333333')
            );
            html += card(
                '<div style="display:flex; gap:12px; flex-wrap:wrap;">'
                + '<div style="flex:1; min-width:140px;"><label style="font-size:12px; color:#6b7280; display:block; margin-bottom:4px;">Font size (px)</label>'
                + '<input type="number" min="10" max="36" value="'+(s.fontSize||'')+'" placeholder="14" oninput="menuxWizard._styleVal(\'fontSize\',this.value)" style="'+inputStyle()+'"></div>'
                + '<div style="flex:1; min-width:140px;"><label style="font-size:12px; color:#6b7280; display:block; margin-bottom:4px;">Item spacing (px)</label>'
                + '<input type="number" min="0" max="80" value="'+(s.gap||'20')+'" placeholder="20" oninput="menuxWizard._styleVal(\'gap\',this.value)" style="'+inputStyle()+'"></div>'
                + '<div style="flex:1; min-width:140px;"><label style="font-size:12px; color:#6b7280; display:block; margin-bottom:4px;">Font weight</label>'
                + '<select onchange="menuxWizard._styleVal(\'fontWeight\',this.value)" style="'+inputStyle()+'"><option value="">Normal</option><option value="300" '+(s.fontWeight==='300'?'selected':'')+'>Light</option><option value="500" '+(s.fontWeight==='500'?'selected':'')+'>Medium</option><option value="600" '+(s.fontWeight==='600'?'selected':'')+'>Semibold</option><option value="bold" '+(s.fontWeight==='bold'?'selected':'')+'>Bold</option></select></div>'
                + '</div>'
            );
            return html;
        }

        /* ---- STEP 5: Summary ---- */
        function renderStepSummary() {
            var html = label('All set!', 'Review the summary and click "Apply to Menu" to save your configuration.');
            var total = state.selectedPages.length + state.customLinks.filter(function(l){ return l.url; }).length;
            html += card(
                '<div style="font-size:13px; color:#374151; line-height:1.8;">'
                + (state.apiUrl ? '🌐 <strong>API:</strong> '+esc(state.apiUrl)+'<br>' : '')
                + '📄 <strong>Selected pages:</strong> '+state.selectedPages.length+'<br>'
                + '🔗 <strong>Custom links:</strong> '+state.customLinks.filter(function(l){ return l.url; }).length+'<br>'
                + '📦 <strong>Total menu items:</strong> '+total
                + '</div>'
            );
            if (state.selectedPages.length) {
                html += '<div style="margin-bottom:8px; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Pages</div>';
                state.selectedPages.forEach(function(p) {
                    html += '<div style="display:flex; align-items:center; gap:8px; padding:7px 10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; margin-bottom:6px; font-size:13px; color:#166534;">✅ '+esc(p.title)+'</div>';
                });
            }
            if (state.customLinks.some(function(l){ return l.url; })) {
                html += '<div style="margin:12px 0 8px; font-size:12px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Custom links</div>';
                state.customLinks.filter(function(l){ return l.url; }).forEach(function(l) {
                    html += '<div style="display:flex; align-items:center; gap:8px; padding:7px 10px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; margin-bottom:6px; font-size:13px; color:#1e40af;">🔗 '+(l.label||l.url)+' → '+esc(l.url)+'</div>';
                });
            }
            if (!total) {
                html += '<div style="padding:20px; text-align:center; color:#9ca3af; font-style:italic;">No items configured. Go back to select pages or add links.</div>';
            }
            return html;
        }

        /* ---- Mutatori di stato ---- */
        function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        /* ---- Render UI wizard ---- */
        function renderUI() {
            var s = steps[state.currentStep];
            document.getElementById('bw-step-title').textContent = s.title;
            var pct = ((state.currentStep) / (steps.length - 1)) * 100;
            document.getElementById('bw-progress').style.width = pct + '%';
            document.getElementById('bw-body').innerHTML = s.render();

            // Dots
            var dotsEl = document.getElementById('bw-dots');
            dotsEl.innerHTML = '';
            steps.forEach(function(_, i) {
                var d = document.createElement('div');
                d.style.cssText = 'width:8px; height:8px; border-radius:50%; background:'+(i===state.currentStep?'#7c3aed':(i<state.currentStep?'#a78bfa':'#d1d5db'))+'; transition:background .3s;';
                dotsEl.appendChild(d);
            });

            // Bottoni
            document.getElementById('bw-btn-back').style.visibility = state.currentStep === 0 ? 'hidden' : 'visible';
            var nextBtn = document.getElementById('bw-btn-next');
            if (state.currentStep === steps.length - 1) {
                nextBtn.textContent = '✨ Apply to Menu';
                nextBtn.style.background = 'linear-gradient(135deg,#059669,#047857)';
            } else {
                nextBtn.textContent = 'Next →';
                nextBtn.style.background = 'linear-gradient(135deg,#667eea,#764ba2)';
            }
        }

        /* ---- Public API ---- */
        return {
            open: function() {
                // Pre-popola apiUrl dal campo esistente
    
                state.currentStep = 0;
                document.getElementById('bw-overlay').style.display = 'flex';
                renderUI();
            },
            close: function() {
                document.getElementById('bw-overlay').style.display = 'none';
            },
            next: function() {
                if (!steps[state.currentStep].validate()) return;
                if (state.currentStep === steps.length - 1) {
                    this._applyToForm();
                    this.close();
                    return;
                }
                state.currentStep++;
                renderUI();
            },
            prev: function() {
                if (state.currentStep > 0) { state.currentStep--; renderUI(); }
            },
            _togglePage: function(cb) {
                var id    = cb.getAttribute('data-page-id');
                var title = cb.getAttribute('data-page-title');
                if (cb.checked) {
                    if (!state.selectedPages.some(function(p){ return p.id == id; })) {
                        state.selectedPages.push({ id: id, title: title });
                    }
                    cb.closest('label').style.background = '#ede9fe';
                    cb.closest('label').style.borderColor = '#a78bfa';
                } else {
                    state.selectedPages = state.selectedPages.filter(function(p){ return p.id != id; });
                    cb.closest('label').style.background = '#f9fafb';
                    cb.closest('label').style.borderColor = '#e5e7eb';
                }
            },
            _addLink: function() {
                state.customLinks.push({ url: '', label: '', icon: '', visibility: 'all' });
                var list = document.getElementById('bw-custom-list');
                var i = state.customLinks.length - 1;
                var div = document.createElement('div');
                div.innerHTML = renderCustomLinkRow(i, state.customLinks[i]);
                list.appendChild(div.firstElementChild);
            },
            _removeLink: function(i) {
                state.customLinks.splice(i, 1);
                document.getElementById('bw-body').innerHTML = steps[2].render();
            },
            _updateLink: function(i, key, val) {
                if (state.customLinks[i]) state.customLinks[i][key] = val;
            },
            _styleVal: function(key, val) {
                state.style[key] = val;
            },
            _applyToForm: function() {
                // 1. Empty the current list and rebuild
                var sortable = document.getElementById('menux-sortable');
                if (sortable) sortable.innerHTML = '';
                menux_index = 0;

                // 2a. Pagine
                state.selectedPages.forEach(function(p) {
                    var langValues = {};
                    if (menux_supported_langs.length > 0) {
                        menux_supported_langs.forEach(function(lang) {
                            var key = 'lang_' + lang.code.replace(/-/g,'_');
                            langValues[key] = p.title;
                        });
                    }
                    menux_buildRow('page', p.id, langValues, '', p.title);
                });

                // 2b. Link personalizzati
                state.customLinks.filter(function(l){ return l.url; }).forEach(function(lk) {
                    var langValues = {};
                    if (menux_supported_langs.length > 0) {
                        menux_supported_langs.forEach(function(lang) {
                            var key = 'lang_' + lang.code.replace(/-/g,'_');
                            langValues[key] = lk.label || lk.url;
                        });
                    }
                    menux_buildRow('custom', lk.url, langValues, lk.icon, lk.label || lk.url);
                    // Imposta visibilità sull'ultima riga aggiunta
                    var rows = document.querySelectorAll('#menux-sortable li.menux-item');
                    if (rows.length) {
                        var lastVis = rows[rows.length-1].querySelector('select[name*="[visibility]"]');
                        if (lastVis) lastVis.value = lk.visibility || 'all';
                    }
                });

                // 3. Stile
                var s = state.style;
                function setColor(name, val) {
                    if (!val) return;
                    var cb = document.querySelector('input[name="menux_style_use['+name+']"]');
                    var cp = document.querySelector('input[name="menux_style['+name+']"]');
                    if (cb) { cb.checked = true; menux_toggleColor(cb); }
                    if (cp) cp.value = val;
                }
                function setVal(name, val) {
                    if (val === '' || val === undefined) return;
                    var el = document.querySelector('[name="menux_style['+name+']"]');
                    if (el) el.value = val;
                }
                setColor('link_color',         s.linkColor);
                setColor('link_hover_color',    s.linkHoverColor);
                setColor('link_active_color',   s.linkActiveColor);
                setColor('hamburger_color',     s.hamburgerColor);
                setVal('font_size',    s.fontSize);
                setVal('font_weight',  s.fontWeight);
                setVal('gap',          s.gap);

                menux_liveStylePreview();
                menux_updatePreview();

                // Feedback visivo
                var notice = document.createElement('div');
                notice.className = 'notice notice-success is-dismissible';
                notice.innerHTML = '<p>✨ <strong>Setup Wizard applicata!</strong> Remember to click <strong>Save Menu</strong> to save your changes.</p>';
                var wrap = document.querySelector('.wrap');
                if (wrap) wrap.insertBefore(notice, wrap.querySelector('form'));
                setTimeout(function() { if (notice.parentNode) notice.parentNode.removeChild(notice); }, 5000);
            }
        };
    })();

    // Chiudi overlay cliccando fuori dal modale
    document.addEventListener('click', function(e) {
        var overlay = document.getElementById('bw-overlay');
        if (e.target === overlay) menuxWizard.close();
    });
    </script>
    <?php
}
