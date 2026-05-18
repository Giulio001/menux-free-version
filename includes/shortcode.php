<?php
/**
 * MenuX Free — Frontend Shortcode
 * Renders the [menux] shortcode on the frontend.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('menux', 'menux_render_shortcode');
function menux_render_shortcode($atts = array()) {
    $atts = shortcode_atts(array(
        'location' => 'primary',   // ──── FEATURE 11: multi-menu location ────
    ), $atts, 'menux');

    $all_menu_items  = get_option('menux_menu_items', array());
    $menux_style   = get_option('menux_style', array());
    $s               = wp_parse_args($menux_style, menux_style_defaults());
    $supported_langs = menux_get_supported_languages();

    // ──── FEATURE 11: filtra per location ────
    $requested_location = sanitize_key($atts['location']);
    $menu_items = array();
    foreach ($all_menu_items as $item) {
        $item_loc = !empty($item['menu_location']) ? $item['menu_location'] : 'primary';
        if ($item_loc === $requested_location) {
            $menu_items[] = $item;
        }
    }
    // Fallback: se nessun item ha location settato, mostra tutti (retrocompatibilità)
    if (empty($menu_items) && $requested_location === 'primary') {
        $menu_items = $all_menu_items;
    }
    if (empty($menu_items)) return '';

    $is_logged_in = is_user_logged_in();
    $nav_class    = $is_logged_in ? 'menux-user-logged-in' : 'menux-user-guest';
    $is_sticky    = ($s['sticky'] === '1');

    // ──── FEATURE 10: pre-calcola condizioni globali ────
    $current_user_roles = array();
    if ($is_logged_in) {
        $cur_user = wp_get_current_user();
        $current_user_roles = $cur_user->roles;
    }
    $current_page_id = get_queried_object_id();
    $current_hour_min = current_time('H:i');
    $current_utm = isset($_GET['utm_source']) ? sanitize_text_field( wp_unslash( $_GET['utm_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $is_mobile_device = wp_is_mobile();

    // ──── FEATURE 4+10: Funzione di filtraggio item ────
    $should_show_item = function($raw) use ($is_logged_in, $current_user_roles, $current_page_id, $current_hour_min, $current_utm, $is_mobile_device) {
        // Role-based visibility
        $vis = $raw['visibility'] ?? 'all';
        if ( $vis === 'all' ) {
            // visible to everyone
        } elseif ( $vis === 'logged_in' ) {
            if ( ! $is_logged_in ) return false;
        } elseif ( $vis === 'logged_out' ) {
            if ( $is_logged_in ) return false;
        } elseif ( $vis === 'auth' ) {
            // legacy value — treat as logged_in
            if ( ! $is_logged_in ) return false;
        } elseif ( $vis === 'non-auth' ) {
            // legacy value — treat as logged_out
            if ( $is_logged_in ) return false;
        } else {
            // Specific WP role: must be logged in AND have that exact role
            if ( ! $is_logged_in ) return false;
            if ( ! in_array( $vis, (array) $current_user_roles, true ) ) return false;
        }

        // ──── FEATURE 4: Scheduled items ────
        $now = current_time('Y-m-d\TH:i');
        if (!empty($raw['schedule_start']) && $now < $raw['schedule_start']) return false;
        if (!empty($raw['schedule_end'])   && $now > $raw['schedule_end'])   return false;

        // ──── FEATURE 10: Condizionali avanzati ────
        // Ruoli utente
        if (!empty($raw['cond_roles'])) {
            $allowed_roles = array_map('trim', explode(',', $raw['cond_roles']));
            if (empty(array_intersect($current_user_roles, $allowed_roles))) return false;
        }
        // Device
        if (!empty($raw['cond_devices'])) {
            $allowed_devices = array_map('trim', explode(',', $raw['cond_devices']));
            $current_device = $is_mobile_device ? 'mobile' : 'desktop';
            if (!in_array($current_device, $allowed_devices)) return false;
        }
        // Pagina corrente
        if (!empty($raw['cond_pages'])) {
            $allowed_pages = array_map('intval', explode(',', $raw['cond_pages']));
            if (!in_array($current_page_id, $allowed_pages)) return false;
        }
        // Fascia oraria
        if (!empty($raw['cond_time_from']) && !empty($raw['cond_time_to'])) {
            if ($current_hour_min < $raw['cond_time_from'] || $current_hour_min > $raw['cond_time_to']) return false;
        }
        // UTM source
        if (!empty($raw['cond_utm'])) {
            if (strtolower($current_utm) !== strtolower($raw['cond_utm'])) return false;
        }

        return true;
    };

    // Costruisce label da un raw item, con supporto multilingua
    $get_label = function($raw) use ($supported_langs) {
        $is_page = ($raw['type'] === 'page');
        if (!empty($supported_langs)) {
            foreach ($supported_langs as $lang) {
                $key = menux_code_to_key($lang['code']);
                if (!empty($raw[$key])) return $raw[$key];
            }
        }
        if (!empty($raw['it'])) return $raw['it'];
        return $is_page ? get_the_title($raw['id'] ?? 0) : ($raw['url'] ?? '');
    };

    // Costruisce HTML <li> con supporto sottomenu ricorsivo
    $render_item_html = null;
    $render_item_html = function($raw, $depth = 0) use (&$render_item_html, $supported_langs, $get_label, $is_logged_in, $should_show_item) {
        // ──── FEATURE 4+10: check visibilità ────
        if (!$should_show_item($raw)) return '';

        $is_page  = ($raw['type'] === 'page');
        $url      = $is_page ? get_permalink($raw['id'] ?? 0) : ($raw['url'] ?? '#');
        $icon     = !empty($raw['icon']) ? '<i class="'.esc_attr($raw['icon']).'" aria-hidden="true" style="margin-right:6px;"></i>' : '';
        $target   = !empty($raw['target']) ? ' target="'.esc_attr($raw['target']).'" rel="noopener"' : '';
        if (!empty($raw['target']) && $raw['target'] === '_blank') {
            $target .= ' aria-label="'.esc_attr($get_label($raw)).' (opens in new window)"';
        }
        $vis      = $raw['visibility'] ?? 'all';
        $item_key = $raw['item_key'] ?? ($is_page ? 'page_'.($raw['id']??0) : 'c_'.md5($url));
        $has_children = !empty($raw['children']) && is_array($raw['children']) && count($raw['children']) > 0;

        $li_classes = array();
        if ($has_children) $li_classes[] = 'menux-has-children';

        // ──── FEATURE 2: Notification Dot ────
        $notif_dot_html = '';
        if (!empty($raw['notif_dot']) && $raw['notif_dot'] === '1') {
            $notif_dot_html = '<span class="menux-notif-dot"></span>';
            $li_classes[] = 'menux-has-notif';
        }

        // Badge
        $badge_html = '';
        if (!empty($raw['badge'])) {
            $bc  = !empty($raw['badge_color']) ? $raw['badge_color'] : '#ffffff';
            $bbg = !empty($raw['badge_bg'])    ? $raw['badge_bg']    : '#ef4444';
            $badge_html = '<span class="menux-badge" style="color:'.esc_attr($bc).';background:'.esc_attr($bbg).';">'.esc_html($raw['badge']).'</span>';
        }

        // Labels multilingua
        $labels = array();
        if (!empty($supported_langs)) {
            foreach ($supported_langs as $lang) {
                $key   = menux_code_to_key($lang['code']);
                $short = strtolower(substr($lang['code'], 0, 2));
                $labels[$lang['code']] = !empty($raw[$key]) ? $raw[$key] : (!empty($raw[$short]) ? $raw[$short] : ($is_page ? get_the_title($raw['id']??0) : $url));
            }
        } else {
            $labels['it-IT'] = !empty($raw['it']) ? $raw['it'] : ($is_page ? get_the_title($raw['id']??0) : $url);
            $labels['en-US'] = $raw['en'] ?? $labels['it-IT'];
        }

        $lang_attrs = '';
        foreach ($labels as $code => $lbl) {
            $attr = 'data-lang-' . strtolower(str_replace('-','_',$code));
            $lang_attrs .= ' '.esc_attr($attr).'="'.esc_attr($lbl).'"';
        }
        $first_label = reset($labels);

        // Mega menu flag (first level only)
        $is_mega = ( $depth === 0 && ! empty( $raw['mega_menu'] ) && $raw['mega_menu'] === '1'
                    && ! empty( $raw['mega_columns'] ) && is_array( $raw['mega_columns'] ) );
        if ( $is_mega ) {
            $li_classes[] = 'menux-has-mega';
        }

        $out  = '<li class="'.esc_attr(implode(' ', $li_classes)).'" role="none">';
        $out .= '<a href="'.esc_url($url).'" class="menux-link"'.$target
               .' role="menuitem"'
               .(($has_children || $is_mega) ? ' aria-haspopup="true" aria-expanded="false"' : '')
               .' data-item-key="'.esc_attr($item_key).'"'
               .' data-item-label="'.esc_attr($first_label).'"'
               .' data-item-url="'.esc_attr($url).'">';
        $out .= $icon;
        $out .= '<span class="menux-label"'.$lang_attrs.' data-default="'.esc_attr($first_label).'"></span>';
        $out .= $badge_html;
        $out .= $notif_dot_html;
        $out .= '</a>';

        if ( $is_mega ) {
            // Render mega menu panel instead of standard submenu.
            $out .= Menux_MegaMenu::render_panel( $raw );
        } elseif ($has_children) {
            // Standard submenu (2° e 3° livello).
            $out .= '<ul class="menux-submenu" role="menu">';
            foreach ($raw['children'] as $child) {
                $out .= $render_item_html($child, $depth + 1);
            }
            $out .= '</ul>';
        }
        $out .= '</li>';
        return $out;
    };

    $lang_codes   = empty($supported_langs) ? array('it-IT','en-US') : array_column($supported_langs, 'code');
    $default_lang = !empty($lang_codes) ? $lang_codes[0] : 'it-IT';

    // Dark mode
    $dark_mode     = $s['dark_mode'] ?? 'light';
    $dm_attr       = '';
    if ($dark_mode === 'dark')  $dm_attr = ' data-bs-theme="dark" data-bm-theme="dark"';
    elseif ($dark_mode === 'light') $dm_attr = ' data-bs-theme="light"';
    // auto: no attribute, CSS media query handles it

    // Search modal HTML
    $search_html = '';
    if (!empty($s['search_enabled']) && $s['search_enabled'] === '1') {
        $ph = esc_attr($s['search_placeholder'] ?? 'Search in page...');
        $search_html = '<div class="menux-search-wrap">'
            . '<button type="button" class="menux-search-btn" aria-label="Open search" id="menux-search-open">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
            . '</button>'
            . '</div>'
            // Modal (appended once outside nav)
            . '<div id="menux-search-modal" role="dialog" aria-modal="true" aria-label="Search">'
            . '<div id="menux-search-backdrop"></div>'
            . '<div id="menux-search-box">'
            . '  <div class="bm-sm-header">'
            . '    <span class="bm-sm-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>'
            . '    <input type="search" id="menux-search-input-modal" placeholder="'.$ph.'" autocomplete="off" spellcheck="false">'
            . '    <button id="menux-search-close" title="Close (Esc)" aria-label="Close search">✕</button>'
            . '  </div>'
            . '  <div class="bm-sm-tabs">'
            . '    <button class="bm-sm-tab active" data-tab="menu" type="button">🔗 Menu</button>'
            . '    <button class="bm-sm-tab" data-tab="page" type="button">📄 In page</button>'
            . '  </div>'
            . '  <div class="bm-sm-body" id="menux-search-results-modal"></div>'
            . '  <div class="bm-sm-footer">'
            . '    <span class="bm-sm-kbd"><kbd>↑</kbd><kbd>↓</kbd> navigate</span>'
            . '    <span class="bm-sm-kbd"><kbd>↵</kbd> open</span>'
            . '    <span class="bm-sm-kbd"><kbd>Esc</kbd> close</span>'
            . '    <span class="bm-sm-count" id="menux-search-count"></span>'
            . '  </div>'
            . '</div>'
            . '</div>'
            // Nav bar for page highlights
            . '<div id="bm-search-nav-bar">'
            . '  <span id="bm-snb-label">0 / 0</span>'
            . '  <button type="button" id="bm-snb-prev">↑ Previous</button>'
            . '  <button type="button" id="bm-snb-next">↓ Next</button>'
            . '  <button type="button" class="bm-snb-close" id="bm-snb-clear" aria-label="Clear highlights">✕ Close</button>'
            . '</div>';
    }

    // Logo HTML — uses Menux_Logo for advanced multi-context rendering.
    // Falls back to legacy menux_style[logo_url] if no new logo is configured.
    $logo_settings = class_exists( 'Menux_Logo' ) ? Menux_Logo::get_settings() : array();
    $logo_position = $logo_settings['position'] ?? ( $s['logo_position'] ?? 'left' );
    $logo_html     = class_exists( 'Menux_Logo' ) ? Menux_Logo::render() : '';

    // Build separate mobile logo if configured.
    $logo_mobile_html = '';
    if ( class_exists( 'Menux_Logo' ) ) {
        $has_mobile_logo = ! empty( $logo_settings['mobile_id'] ) || ! empty( $logo_settings['mobile_url'] );
        if ( $has_mobile_logo ) {
            $logo_mobile_html = Menux_Logo::render( true );
        }
    }

    // Sticky spacer height viene calcolato via JS
    $aria_label  = 'Main menu';
    $skip_target = 'main';

    // ──── Variabili necessarie nell'HTML del nav ────
    $open_style = $s['mobile_open_style']      ?? 'dropdown';
    $bp_mode    = $s['mobile_breakpoint_mode'] ?? 'manual';
    $bp         = ($s['mobile_breakpoint'] !== '') ? intval($s['mobile_breakpoint']) : 768;

    ob_start();
    ?>
    <?php if ($is_sticky): ?><div class="menux-sticky-spacer" id="menux-spacer" style="display:none;"></div><?php endif; ?>

    <?php /* ──── Overlay backdrop per fullscreen / drawer ──── */ ?>
    <?php if (in_array($open_style, array('fullscreen','drawer-left','drawer-right'), true)): ?>
    <div class="menux-overlay" id="menux-overlay" aria-hidden="true"></div>
    <?php endif; ?>

    <?php /* ──── Pulsante close (X) — fuori dal nav, position:fixed, hidden di default ──── */ ?>
    <?php if (in_array($open_style, array('fullscreen','drawer-left','drawer-right'), true)): ?>
    <button type="button" class="menux-close-btn" id="menux-close-btn" aria-label="Close menu">×</button>
    <?php endif; ?>

    <nav class="menux-container <?php echo esc_attr($nav_class); ?>"<?php if ($is_sticky) echo ' data-menux-sticky="1"'; ?><?php if ($dark_mode === 'dark') echo ' data-bs-theme="dark" data-bm-theme="dark"'; elseif ($dark_mode === 'light') echo ' data-bs-theme="light"'; ?> id="menux-nav-main"
         role="navigation" aria-label="<?php echo esc_attr($aria_label); ?>"
         data-mobile-open-style="<?php echo esc_attr($open_style); ?>"
         data-mobile-bp-mode="<?php echo esc_attr($bp_mode); ?>"
         data-mobile-bp="<?php echo intval($bp); ?>">
        <?php if (!empty($s['progress_bar_enabled']) && $s['progress_bar_enabled'] === '1'): ?>
        <div class="menux-progress-bar" id="menux-progress-bar" aria-hidden="true"></div>
        <?php endif; ?>
        <?php
        // Logo kses uses an expanded tag list to allow inline SVG elements.
        $logo_kses = class_exists( 'Menux_Logo' ) ? Menux_Logo::kses_allowed_tags() : wp_kses_allowed_html( 'post' );
        ?>
        <?php if (!empty($logo_html) && in_array($logo_position, array('left','center-split'), true)): ?>
            <?php echo wp_kses( $logo_html, $logo_kses ); ?>
            <?php echo wp_kses( $logo_mobile_html, $logo_kses ); ?>
        <?php endif; ?>
        <div class="menux-hamburger" aria-expanded="false" aria-controls="menux-list-main" aria-label="<?php esc_attr_e('Open/close menu', 'giuliomax-menu-builder'); ?>">
            <span></span><span></span><span></span>
        </div>
        <?php if (!empty($logo_html) && $logo_position === 'center'): ?>
            <div class="menux-logo-center-wrap" style="flex:1;display:flex;justify-content:center;">
                <?php echo wp_kses( $logo_html, $logo_kses ); ?>
                <?php echo wp_kses( $logo_mobile_html, $logo_kses ); ?>
            </div>
        <?php endif; ?>
        <ul class="menux-list" id="menux-list-main" role="menubar">
            <?php foreach ($menu_items as $raw): echo wp_kses_post( $render_item_html($raw, 0) ); endforeach; ?>
        </ul>
        <?php if (!empty($logo_html) && $logo_position === 'right'): ?>
            <?php echo wp_kses( $logo_html, $logo_kses ); ?>
            <?php echo wp_kses( $logo_mobile_html, $logo_kses ); ?>
        <?php endif; ?>
        <?php echo wp_kses_post( $search_html ); ?>
    </nav>


    <?php
    /* Enqueue frontend script and inject dynamic config via WP APIs (no inline <script>/<style>) */
    static $menux_script_done = false;

    if ( ! $menux_script_done ) {
        wp_enqueue_script( 'menux-frontend' );
        $frontend_data = wp_json_encode( array(
            'supportedCodes' => $lang_codes,
            'defaultCode'    => $default_lang,
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'trackNonce'     => wp_create_nonce( 'menux_track_nonce' ),
            'isLoggedIn'     => $is_logged_in ? 1 : 0,
            'isSticky'       => $is_sticky    ? 1 : 0,
            'searchEnabled'  => ( ! empty( $s['search_enabled'] ) && $s['search_enabled'] === '1' ),
        ) );
        wp_add_inline_script( 'menux-frontend', 'window.menuxFrontendData = ' . $frontend_data . ';', 'before' );
        $menux_script_done = true;
    }
    ?>
    <?php
    return ob_get_clean();
}
