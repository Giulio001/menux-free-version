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
        // Submenu
        $saved_style['submenu_shadow']          = isset($raw_style['submenu_shadow'])     ? '1' : '0';
        $saved_style['submenu_animation']       = in_array($raw_style['submenu_animation'] ?? 'fade', array('fade','slide','none'), true) ? $raw_style['submenu_animation'] : 'fade';
        
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
