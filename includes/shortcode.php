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
    $current_utm = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';
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

        $out  = '<li class="'.esc_attr(implode(' ', $li_classes)).'" role="none">';
        $out .= '<a href="'.esc_url($url).'" class="menux-link"'.$target
               .' role="menuitem"'
               .($has_children ? ' aria-haspopup="true" aria-expanded="false"' : '')
               .' data-item-key="'.esc_attr($item_key).'"'
               .' data-item-label="'.esc_attr($first_label).'"'
               .' data-item-url="'.esc_attr($url).'">';
        $out .= $icon;
        $out .= '<span class="menux-label"'.$lang_attrs.' data-default="'.esc_attr($first_label).'"></span>';
        $out .= $badge_html;
        $out .= $notif_dot_html;
        $out .= '</a>';

        // Submenu (2° e 3° livello)
        if ($has_children) {
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

    // Logo HTML
    $logo_html = '';
    if (!empty($s['logo_url'])) {
        $lw       = !empty($s['logo_width'])  ? intval($s['logo_width'])  : 120;
        $lh_style = !empty($s['logo_height']) ? 'height:'.intval($s['logo_height']).'px;' : '';
        $alt      = !empty($s['logo_alt'])    ? esc_attr($s['logo_alt'])  : 'Logo';
        $logo_img = '<img src="'.esc_url($s['logo_url']).'" width="'.$lw.'" style="'.$lh_style.'display:block;" alt="'.$alt.'">';
        $logo_href = !empty($s['logo_link'])  ? esc_url($s['logo_link'])  : '';
        $logo_html = $logo_href
            ? '<a href="'.$logo_href.'" class="menux-logo" aria-label="'.$alt.'">'.$logo_img.'</a>'
            : '<span class="menux-logo">'.$logo_img.'</span>';
    }

    // Sticky spacer height viene calcolato via JS
    $sticky_attrs = $is_sticky ? ' data-menux-sticky="1"' : '';
    $aria_label   = !empty($s['a11y_aria_label']) ? esc_attr($s['a11y_aria_label']) : 'Main menu';
    $skip_target  = !empty($s['a11y_skip_target']) ? esc_attr($s['a11y_skip_target']) : 'main';

    // ──── Variabili necessarie nell'HTML del nav ────
    $open_style = $s['mobile_open_style']      ?? 'dropdown';
    $bp_mode    = $s['mobile_breakpoint_mode'] ?? 'manual';
    $bp         = ($s['mobile_breakpoint'] !== '') ? intval($s['mobile_breakpoint']) : 768;

    ob_start();
    ?>
    <?php if ($s['a11y_skip_link'] === '1'): ?>
    <a class="menux-skip-link" href="#<?php echo $skip_target; ?>"><?php echo esc_html__('Skip to main content', 'menux'); ?></a>
    <?php endif; ?>
    <?php if ($is_sticky): ?><div class="menux-sticky-spacer" id="menux-spacer" style="display:none;"></div><?php endif; ?>

    <?php /* ──── Overlay backdrop per fullscreen / drawer ──── */ ?>
    <?php if (in_array($open_style, array('fullscreen','drawer-left','drawer-right'))): ?>
    <div class="menux-overlay" id="menux-overlay" aria-hidden="true"></div>
    <?php endif; ?>

    <?php /* ──── Pulsante close (X) — fuori dal nav, position:fixed, hidden di default ──── */ ?>
    <?php if (in_array($open_style, array('fullscreen','drawer-left','drawer-right'))): ?>
    <button type="button" class="menux-close-btn" id="menux-close-btn" aria-label="Close menu">×</button>
    <?php endif; ?>

    <nav class="menux-container <?php echo esc_attr($nav_class); ?>"<?php echo $sticky_attrs; ?><?php echo $dm_attr; ?> id="menux-nav-main"
         role="navigation" aria-label="<?php echo $aria_label; ?>"
         data-mobile-open-style="<?php echo esc_attr($open_style); ?>"
         data-mobile-bp-mode="<?php echo esc_attr($bp_mode); ?>"
         data-mobile-bp="<?php echo intval($bp); ?>">
        <?php if (!empty($s['progress_bar_enabled']) && $s['progress_bar_enabled'] === '1'): ?>
        <div class="menux-progress-bar" id="menux-progress-bar" aria-hidden="true"></div>
        <?php endif; ?>
        <?php if (!empty($logo_html) && in_array($s['logo_position'] ?? 'left', array('left','center-split'))): ?>
            <?php echo $logo_html; ?>
        <?php endif; ?>
        <div class="menux-hamburger" aria-expanded="false" aria-controls="menux-list-main" aria-label="<?php esc_attr_e('Open/close menu', 'menux'); ?>">
            <span></span><span></span><span></span>
        </div>
        <ul class="menux-list" id="menux-list-main" role="menubar">
            <?php foreach ($menu_items as $raw): echo $render_item_html($raw, 0); endforeach; ?>
        </ul>
        <?php if (!empty($logo_html) && ($s['logo_position'] ?? 'left') === 'right'): ?>
            <?php echo $logo_html; ?>
        <?php endif; ?>
        <?php echo $search_html; ?>
    </nav>

    <style>
        <?php echo menux_generate_css($menux_style); ?>
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var supportedCodes = <?php echo wp_json_encode($lang_codes); ?>;
        var defaultCode    = <?php echo wp_json_encode($default_lang); ?>;
        var currentUrl     = window.location.href.split(/[?#]/)[0].replace(/\/$/, "");
        var ajaxUrl        = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var trackNonce     = '<?php echo esc_js(wp_create_nonce('menux_track_nonce')); ?>';
        var isLoggedIn     = <?php echo $is_logged_in ? '1' : '0'; ?>;
        var isSticky       = <?php echo $is_sticky ? '1' : '0'; ?>;

        function findBestCode(langAttr, codes) {
            var lang = (langAttr || defaultCode).toLowerCase().replace('_', '-');
            for (var i = 0; i < codes.length; i++) { if (codes[i].toLowerCase() === lang) return codes[i]; }
            var prefix = lang.substring(0, 2);
            for (var i = 0; i < codes.length; i++) { if (codes[i].toLowerCase().startsWith(prefix)) return codes[i]; }
            return codes[0] || defaultCode;
        }

        function applyLanguage() {
            var langAttr = document.documentElement.getAttribute("lang") || defaultCode;
            var bestCode = findBestCode(langAttr, supportedCodes);
            var dataAttr = 'data-lang-' + bestCode.toLowerCase().replace('-', '_');
            document.querySelectorAll(".menux-container").forEach(function(nav) {
                nav.querySelectorAll(".menux-label").forEach(function(span) {
                    span.textContent = span.getAttribute(dataAttr) || span.getAttribute('data-default') || '';
                });
            });
        }
        applyLanguage();

        // Active link
        document.querySelectorAll(".menux-container").forEach(function(nav) {
            nav.querySelectorAll("a.menux-link").forEach(function(a) {
                var href = (a.getAttribute("href") || "").replace(/\/$/, "");
                if (currentUrl === href) a.classList.add("active");
            });

            var hamburger  = nav.querySelector(".menux-hamburger");
            var ul         = nav.querySelector(".menux-list");
            var overlay    = document.getElementById('menux-overlay');
            var closeBtn   = document.getElementById('menux-close-btn');
            var openStyle  = nav.getAttribute('data-mobile-open-style') || 'dropdown';
            var bpMode     = nav.getAttribute('data-mobile-bp-mode') || 'manual';
            var bpPx       = parseInt(nav.getAttribute('data-mobile-bp') || '768', 10);

            // ──── Funzione open/close ────
            function bmOpenMenu() {
                if (!ul) return;
                ul.classList.add('show');
                hamburger && hamburger.classList.add('open');
                hamburger && hamburger.setAttribute('aria-expanded', 'true');
                if (overlay && openStyle !== 'dropdown') {
                    overlay.style.display = 'block';
                    overlay.getBoundingClientRect();
                    overlay.classList.add('visible');
                }
                // Mostra il close btn
                if (closeBtn && openStyle !== 'dropdown') {
                    closeBtn.classList.add('visible');
                }
                if (openStyle !== 'dropdown') {
                    document.body.style.overflow = 'hidden';
                }
            }

            function bmCloseMenu() {
                if (!ul) return;
                ul.classList.remove('show');
                hamburger && hamburger.classList.remove('open');
                hamburger && hamburger.setAttribute('aria-expanded', 'false');
                ul.querySelectorAll('.menux-submenu.mobile-open').forEach(function(sm) {
                    sm.classList.remove('mobile-open');
                });
                if (overlay) {
                    overlay.classList.remove('visible');
                    setTimeout(function(){ if (!overlay.classList.contains('visible')) overlay.style.display = 'none'; }, 320);
                }
                // Nascondi il close btn
                if (closeBtn) {
                    closeBtn.classList.remove('visible');
                }
                document.body.style.overflow = '';
            }

            function bmToggleMenu() {
                ul && ul.classList.contains('show') ? bmCloseMenu() : bmOpenMenu();
            }

            if (hamburger && ul) {
                hamburger.addEventListener('click', function(e) {
                    e.preventDefault();
                    bmToggleMenu();
                });
            }

            // Overlay click → close
            if (overlay) {
                overlay.addEventListener('click', bmCloseMenu);
            }

            // Pulsante X → close
            if (closeBtn) {
                closeBtn.addEventListener('click', bmCloseMenu);
            }

            // ESC → close
            document.addEventListener('keydown', function(e) {
                if ((e.key === 'Escape' || e.keyCode === 27) && ul && ul.classList.contains('show')) {
                    bmCloseMenu();
                    hamburger && hamburger.focus();
                }
            });

            // Submenu mobile: tap per openre (accordion)
            nav.querySelectorAll(".menux-has-children > a.menux-link").forEach(function(a) {
                a.addEventListener("click", function(e) {
                    var isMobile = bpMode === 'auto'
                        ? nav.classList.contains('menux-is-mobile')
                        : window.innerWidth <= bpPx;
                    if (isMobile) {
                        e.preventDefault();
                        var subMenu = a.parentElement.querySelector('.menux-submenu');
                        if (subMenu) {
                            subMenu.classList.toggle('mobile-open');
                            a.classList.toggle('mobile-sm-open');
                        }
                    }
                });
            });

            // ──── FEATURE: Breakpoint automatico (ResizeObserver) ────
            if (bpMode === 'auto') {
                function bmCheckFit() {
                    // Misura se le voci entrano nella riga del container
                    var list = nav.querySelector('.menux-list');
                    if (!list) return;
                    // In auto mode togliamo momentaneamente la classe mobile per misurare il naturale
                    var wasMobile = nav.classList.contains('menux-is-mobile');
                    nav.classList.remove('menux-is-mobile');
                    nav.classList.add('menux-is-desktop');
                    list.style.visibility = 'hidden';
                    list.style.display = 'flex';
                    list.style.flexDirection = 'row';

                    var containerW = nav.getBoundingClientRect().width;
                    var listScrollW = list.scrollWidth;
                    var logoW = 0;
                    var logo = nav.querySelector('.menux-logo');
                    if (logo) logoW = logo.getBoundingClientRect().width + 16;

                    var fits = (listScrollW + logoW) <= containerW;

                    // Ripristina
                    list.style.visibility = '';
                    list.style.display = '';
                    list.style.flexDirection = '';

                    if (fits) {
                        nav.classList.remove('menux-is-mobile');
                        nav.classList.add('menux-is-desktop');
                        // Se il menu era aperto, closeamolo
                        if (wasMobile && ul && ul.classList.contains('show')) bmCloseMenu();
                    } else {
                        nav.classList.remove('menux-is-desktop');
                        nav.classList.add('menux-is-mobile');
                    }
                }

                if (window.ResizeObserver) {
                    new ResizeObserver(function() { bmCheckFit(); }).observe(nav);
                } else {
                    // Fallback per browser vecchi
                    window.addEventListener('resize', bmCheckFit, { passive: true });
                }
                bmCheckFit(); // check immediato

            } else {
                // Modalità manuale: usa media query CSS, ma dobbiamo resettare se ridimensionato
                window.addEventListener('resize', function() {
                    if (window.innerWidth > bpPx && ul && ul.classList.contains('show')) {
                        bmCloseMenu();
                    }
                }, { passive: true });
            }
        });

        // Click tracking — ──── FEATURE 12+13: aggiungi country ────
        var bmCountryCode = '';
        // Geolocalizzazione leggera via API gratuita (non bloccante)
        try {
            fetch('https://ipapi.co/json/', {mode:'cors'}).then(function(r){return r.json()}).then(function(d){
                if (d && d.country_code) bmCountryCode = d.country_code;
            }).catch(function(){});
        } catch(e){}

        document.querySelectorAll("a.menux-link[data-item-key]").forEach(function(a) {
            a.addEventListener("click", function() {
                var key   = a.getAttribute('data-item-key')   || '';
                var label = a.getAttribute('data-item-label') || '';
                var url   = a.getAttribute('data-item-url')   || a.getAttribute('href') || '';
                var lang  = document.documentElement.getAttribute('lang') || defaultCode;
                var params = { action:'menux_track_click', nonce:trackNonce, item_key:key, item_label:label, item_url:url, user_lang:lang, is_logged_in:isLoggedIn, country:bmCountryCode };
                navigator.sendBeacon
                    ? navigator.sendBeacon(ajaxUrl, new URLSearchParams(params))
                    : fetch(ajaxUrl, { method:'POST', body: new URLSearchParams(params) });
            });
        });

        // Sticky
        if (isSticky) {
            var nav = document.getElementById('menux-nav-main');
            var spacer = document.getElementById('menux-spacer');
            if (nav) {
                var navTop = nav.getBoundingClientRect().top + window.scrollY;
                var navH   = nav.offsetHeight;

                // ──── FEATURE 5: Auto-hide on scroll ────
                var lastScrollY = window.scrollY;
                var scrollThreshold = 5;
                nav.classList.add('menux-autohide');

                function checkSticky() {
                    var currentY = window.scrollY;
                    if (currentY >= navTop) {
                        nav.classList.add('menux-sticky-fixed');
                        if (spacer) { spacer.style.display = 'block'; spacer.style.height = navH + 'px'; }

                        // Auto-hide: nascondi scrollando giù, mostra scrollando su
                        if (currentY > lastScrollY && (currentY - lastScrollY) > scrollThreshold) {
                            nav.classList.add('menux-hidden');
                        } else if (currentY < lastScrollY && (lastScrollY - currentY) > scrollThreshold) {
                            nav.classList.remove('menux-hidden');
                        }
                    } else {
                        nav.classList.remove('menux-sticky-fixed');
                        nav.classList.remove('menux-hidden');
                        if (spacer) spacer.style.display = 'none';
                    }
                    lastScrollY = currentY;
                }
                window.addEventListener('scroll', checkSticky, { passive: true });
                checkSticky();
            }
        }

        // ──── Scroll progress bar ────
        var progressBar = document.getElementById('menux-progress-bar');
        if (progressBar) {
            function menuxUpdateProgress() {
                var docH = document.documentElement.scrollHeight - window.innerHeight;
                var pct  = docH > 0 ? (window.scrollY / docH) * 100 : 0;
                progressBar.style.width = Math.min(100, pct).toFixed(1) + '%';
            }
            window.addEventListener('scroll', menuxUpdateProgress, { passive: true });
            menuxUpdateProgress();
        }

        // Osserva lang change
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) { if (m.attributeName === 'lang') applyLanguage(); });
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

        // === RICERCA MODALE ===
        var searchEnabled = <?php echo (!empty($s['search_enabled']) && $s['search_enabled'] === '1') ? 'true' : 'false'; ?>;
        if (searchEnabled) {
            var _bmSM = {
                modal: document.getElementById('menux-search-modal'),
                input: document.getElementById('menux-search-input-modal'),
                results: document.getElementById('menux-search-results-modal'),
                countEl: document.getElementById('menux-search-count'),
                openBtn: document.getElementById('menux-search-open'),
                closeBtn: document.getElementById('menux-search-close'),
                backdrop: document.getElementById('menux-search-backdrop'),
                navBar: document.getElementById('bm-search-nav-bar'),
                navLabel: document.getElementById('bm-snb-label'),
                navPrev: document.getElementById('bm-snb-prev'),
                navNext: document.getElementById('bm-snb-next'),
                navClear: document.getElementById('bm-snb-clear'),
                currentTab: 'menu',
                focusIdx: -1,
                pageMatches: [],
                pageMatchIdx: -1,
                debounceTimer: null
            };

            // ---- Apertura / chiusura ----
            function bmSmOpen() {
                if (!_bmSM.modal) return;
                _bmSM.modal.classList.add('bm-sm-open');
                document.body.style.overflow = 'hidden';
                setTimeout(function() { if (_bmSM.input) _bmSM.input.focus(); }, 50);
                bmSmRenderEmpty();
            }
            function bmSmClose() {
                if (!_bmSM.modal) return;
                _bmSM.modal.classList.remove('bm-sm-open');
                document.body.style.overflow = '';
                if (_bmSM.input) _bmSM.input.value = '';
                _bmSM.focusIdx = -1;
                bmSmRenderEmpty();
            }

            if (_bmSM.openBtn) _bmSM.openBtn.addEventListener('click', bmSmOpen);
            if (_bmSM.closeBtn) _bmSM.closeBtn.addEventListener('click', bmSmClose);
            if (_bmSM.backdrop) _bmSM.backdrop.addEventListener('click', bmSmClose);

            // ---- Tab switch ----
            document.querySelectorAll('#menux-search-box .bm-sm-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('#menux-search-box .bm-sm-tab').forEach(function(t){ t.classList.remove('active'); });
                    tab.classList.add('active');
                    _bmSM.currentTab = tab.getAttribute('data-tab');
                    _bmSM.focusIdx = -1;
                    var q = _bmSM.input ? _bmSM.input.value.trim() : '';
                    if (q.length >= 2) bmSmSearch(q);
                    else bmSmRenderEmpty();
                });
            });

            // ---- Highlight helper ----
            function bmSmHL(text, q) {
                if (!q) return text;
                var safe = q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
                return text.replace(new RegExp('(' + safe + ')', 'gi'), '<mark>$1</mark>');
            }

            // ---- Render states ----
            function bmSmRenderEmpty() {
                if (!_bmSM.results) return;
                _bmSM.results.innerHTML = '<div class="bm-sm-empty">'
                    + '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                    + '<div style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">Start typing to search</div>'
                    + '<div style="font-size:12px;">Tab <strong>Menu</strong>: search menu links · Tab <strong>In page</strong>: find text on the current page</div>'
                    + '</div>';
                if (_bmSM.countEl) _bmSM.countEl.textContent = '';
            }

            function bmSmRenderNoResults(q) {
                if (!_bmSM.results) return;
                _bmSM.results.innerHTML = '<div class="bm-sm-empty">'
                    + '<svg viewBox="0 0 24 24"><path d="M10 10l4 4M14 10l-4 4"/><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                    + '<div style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">No results for "<em style=\'color:#6366f1;font-style:normal;\'>' + q + '</em>"</div>'
                    + '</div>';
                if (_bmSM.countEl) _bmSM.countEl.textContent = '0 results';
            }

            // ---- Search dispatcher ----
            function bmSmSearch(q) {
                if (_bmSM.currentTab === 'menu') bmSmSearchMenu(q);
                else bmSmSearchPage(q);
            }

            // ---- MENU search ----
            function bmSmSearchMenu(q) {
                var items = [];
                document.querySelectorAll('#menux-nav-main a.menux-link').forEach(function(a) {
                    var labelEl = a.querySelector('.menux-label');
                    var label = labelEl ? labelEl.textContent.trim() : a.textContent.trim();
                    var href  = a.getAttribute('href') || '';
                    var iconEl = a.querySelector('i[class]');
                    var iconClass = iconEl ? iconEl.className : '';
                    if (label && href && href !== '#') items.push({ label: label, href: href, icon: iconClass });
                });
                var matches = items.filter(function(it) { return it.label.toLowerCase().indexOf(q.toLowerCase()) !== -1; });
                if (!matches.length) { bmSmRenderNoResults(q); return; }

                var html = '';
                matches.forEach(function(it, i) {
                    var iconHtml = it.icon
                        ? '<div class="bm-sm-result-icon"><i class="' + it.icon + '" aria-hidden="true"></i></div>'
                        : '<div class="bm-sm-result-icon">🔗</div>';
                    html += '<a href="' + it.href + '" class="bm-sm-result" data-idx="' + i + '" onclick="bmSmClose()">'
                        + iconHtml
                        + '<div><div class="bm-sm-result-title">' + bmSmHL(it.label, q) + '</div>'
                        + '<div class="bm-sm-result-sub">' + it.href + '</div></div>'
                        + '</a>';
                });
                if (_bmSM.results) _bmSM.results.innerHTML = html;
                if (_bmSM.countEl) _bmSM.countEl.textContent = matches.length + ' result' + (matches.length===1?'':'s');
                _bmSM.focusIdx = -1;
            }

            // ---- PAGE search (cerca testo nel DOM) ----
            function bmSmSearchPage(q) {
                // Prima rimuovi highlights precedenti
                bmSmClearPageHighlights();

                var bodyEl = document.body;
                var walker = document.createTreeWalker(bodyEl, NodeFilter.SHOW_TEXT, {
                    acceptNode: function(node) {
                        var p = node.parentNode;
                        // Skip script, style, menux, modal
                        while (p) {
                            var tn = (p.tagName || '').toLowerCase();
                            if (tn === 'script' || tn === 'style' || tn === 'noscript') return NodeFilter.FILTER_REJECT;
                            if (p.id === 'menux-search-modal' || p.id === 'bm-search-nav-bar') return NodeFilter.FILTER_REJECT;
                            if (p.classList && (p.classList.contains('menux-container') || p.classList.contains('menux-sticky-spacer'))) return NodeFilter.FILTER_REJECT;
                            p = p.parentNode;
                        }
                        return NodeFilter.FILTER_ACCEPT;
                    }
                }, false);

                var re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'gi');
                var hits = [];
                var node;
                while ((node = walker.nextNode())) {
                    var text = node.nodeValue;
                    var m;
                    var lastIdx = 0;
                    var frag = null;
                    re.lastIndex = 0;
                    while ((m = re.exec(text)) !== null) {
                        if (!frag) frag = document.createDocumentFragment();
                        if (m.index > lastIdx) frag.appendChild(document.createTextNode(text.slice(lastIdx, m.index)));
                        var mark = document.createElement('mark');
                        mark.className = 'menux-highlight';
                        mark.textContent = m[0];
                        frag.appendChild(mark);
                        hits.push(mark);
                        lastIdx = m.index + m[0].length;
                    }
                    if (frag) {
                        if (lastIdx < text.length) frag.appendChild(document.createTextNode(text.slice(lastIdx)));
                        node.parentNode.replaceChild(frag, node);
                    }
                }

                _bmSM.pageMatches = hits;
                _bmSM.pageMatchIdx = -1;

                if (!hits.length) { bmSmRenderNoResults(q); return; }

                // Render lista riassuntiva dei match
                var html = '';
                hits.forEach(function(mark, i) {
                    // Estrai contesto
                    var ctx = '';
                    var par = mark.parentNode;
                    while (par && !ctx.trim()) {
                        ctx = par.textContent || '';
                        par = par.parentNode;
                    }
                    ctx = ctx.replace(/\s+/g,' ').trim().slice(0, 120);
                    html += '<div class="bm-sm-page-hit" data-hit-idx="' + i + '">'
                        + '<div class="bm-sm-result-title">' + bmSmHL(mark.textContent, q) + '</div>'
                        + '<div class="bm-sm-ph-ctx">' + bmSmHL(ctx, q) + '</div>'
                        + '<div class="bm-sm-ph-pos">Match ' + (i+1) + ' di ' + hits.length + '</div>'
                        + '</div>';
                });
                if (_bmSM.results) _bmSM.results.innerHTML = html;
                if (_bmSM.countEl) _bmSM.countEl.textContent = hits.length + ' occurrence' + (hits.length===1?'a':'e');

                // Click su hit → vai a quel punto, close modale, mostra nav bar
                _bmSM.results.querySelectorAll('.bm-sm-page-hit').forEach(function(el) {
                    el.addEventListener('click', function() {
                        var idx = parseInt(el.getAttribute('data-hit-idx'));
                        bmSmClose();
                        bmSmGoToHit(idx);
                        bmSmShowNavBar(q);
                    });
                });

                if (hits.length > 0) bmSmShowNavBar(q);
            }

            function bmSmGoToHit(idx) {
                if (!_bmSM.pageMatches.length) return;
                idx = ((idx % _bmSM.pageMatches.length) + _bmSM.pageMatches.length) % _bmSM.pageMatches.length;
                _bmSM.pageMatchIdx = idx;
                // Reset corrente
                _bmSM.pageMatches.forEach(function(m) { m.className = 'menux-highlight'; });
                var cur = _bmSM.pageMatches[idx];
                cur.className = 'menux-highlight menux-highlight-current';
                cur.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (_bmSM.navLabel) _bmSM.navLabel.textContent = (idx+1) + ' / ' + _bmSM.pageMatches.length;
            }

            function bmSmShowNavBar(q) {
                if (!_bmSM.navBar || !_bmSM.pageMatches.length) return;
                _bmSM.navBar.classList.add('show');
                if (_bmSM.navLabel) _bmSM.navLabel.textContent = _bmSM.pageMatchIdx >= 0 ? (_bmSM.pageMatchIdx+1) + ' / ' + _bmSM.pageMatches.length : _bmSM.pageMatches.length + ' found';
                bmSmGoToHit(0);
            }

            function bmSmClearPageHighlights() {
                document.querySelectorAll('mark.menux-highlight,mark.menux-highlight-current').forEach(function(mark) {
                    var txt = document.createTextNode(mark.textContent);
                    mark.parentNode.replaceChild(txt, mark);
                });
                _bmSM.pageMatches = [];
                _bmSM.pageMatchIdx = -1;
                if (_bmSM.navBar) _bmSM.navBar.classList.remove('show');
            }

            if (_bmSM.navPrev) _bmSM.navPrev.addEventListener('click', function() { bmSmGoToHit(_bmSM.pageMatchIdx - 1); });
            if (_bmSM.navNext) _bmSM.navNext.addEventListener('click', function() { bmSmGoToHit(_bmSM.pageMatchIdx + 1); });
            if (_bmSM.navClear) _bmSM.navClear.addEventListener('click', function() { bmSmClearPageHighlights(); });

            // ---- Keyboard navigation ----
            if (_bmSM.input) {
                _bmSM.input.addEventListener('input', function() {
                    clearTimeout(_bmSM.debounceTimer);
                    var q = _bmSM.input.value.trim();
                    if (q.length < 2) { bmSmRenderEmpty(); return; }
                    _bmSM.debounceTimer = setTimeout(function() { bmSmSearch(q); }, 220);
                });

                _bmSM.input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') { bmSmClose(); return; }
                    var items = _bmSM.results ? _bmSM.results.querySelectorAll('.bm-sm-result,.bm-sm-page-hit') : [];
                    if (!items.length) return;
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        _bmSM.focusIdx = (_bmSM.focusIdx + 1) % items.length;
                        items.forEach(function(el,i){ el.classList.toggle('bm-sm-focused', i === _bmSM.focusIdx); });
                        items[_bmSM.focusIdx].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        _bmSM.focusIdx = (_bmSM.focusIdx - 1 + items.length) % items.length;
                        items.forEach(function(el,i){ el.classList.toggle('bm-sm-focused', i === _bmSM.focusIdx); });
                        items[_bmSM.focusIdx].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'Enter' && _bmSM.focusIdx >= 0) {
                        e.preventDefault();
                        items[_bmSM.focusIdx].click();
                    }
                });
            }

            // Chiudi con Esc globale
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && _bmSM.modal && _bmSM.modal.classList.contains('bm-sm-open')) { bmSmClose(); }
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
