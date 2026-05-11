<?php
/**
 * MenuX Pro — Style Defaults
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_style_defaults() {
    return array(
        'container_bg'          => '',
        'container_border'      => '',
        'link_color'            => '',
        'link_hover_color'      => '',
        'link_hover_bg'         => '',   // NUOVO: link background on hover
        'link_active_color'     => '',
        'link_active_border'    => '',
        'link_active_font_weight'=> '',  // NUOVO: font-weight del link attivo
        'link_active_bg'        => '',   // NUOVO: active link background
        'font_size'             => '',
        'font_weight'           => '',
        'gap'                   => '20',
        'padding_x'             => '0',
        'padding_y'             => '0',
        'mobile_breakpoint'     => '768',
        'mobile_breakpoint_mode'=> 'manual',   // 'manual' | 'auto'
        'hamburger_color'       => '',
        'hamburger_bg'          => '',
        'mobile_menu_bg'        => '',
        'hamburger_style'       => 'classic',
        'hamburger_align'       => 'flex-end',
        'mobile_menu_pad'       => '0',
        'mobile_menu_shadow'    => '0',
        // ──── Modalità apertura menu mobile ────
        'mobile_open_style'     => 'dropdown',  // 'dropdown' | 'fullscreen' | 'drawer-left' | 'drawer-right'
        'mobile_overlay_bg'     => '#000000',   // backdrop overlay color (fullscreen/drawer)
        'mobile_overlay_opacity'=> '0.5',       // overlay opacity (0-1)
        'mobile_overlay_blur'   => '0',         // background blur in px (fullscreen)
        'mobile_fullscreen_align'=> 'center',   // allineamento voci in fullscreen: center | flex-start | flex-end
        'mobile_drawer_width'   => '280',       // larghezza drawer in px
        'mobile_open_animation' => 'fade',      // animazione apertura: fade | slide | scale
        'custom_css'            => '',
        // Typography avanzata
        'google_font'           => '',
        'font_family'           => '',
        'letter_spacing'        => '',
        'text_transform'        => '',
        // Animazioni
        'link_animation'        => 'none',
        'link_transition'       => '0.3', // NUOVO: durata transizione in secondi
        // Layout avanzato
        'nav_justify'           => 'flex-start', // desktop menu items alignment: flex-start | center | flex-end | space-between | space-evenly
        'push_last_item'        => '0',   // NUOVO: spinge l'ultimo elemento a destra
        'last_item_color'       => '',    // NUOVO: last item text color
        'last_item_hover_color' => '',    // NUOVO: last item hover color
        'last_item_bg'          => '',    // NUOVO: last item background
        // Entrance animation
        'entrance_animation'    => 'none',   // none | fadeIn | slideDown | slideUp | slideLeft | slideRight | zoomIn | flipX
        'entrance_duration'     => '0.5',    // seconds
        'entrance_delay'        => '0',      // seconds (delay before animation starts)
        'entrance_stagger'      => '0',      // seconds (stagger delay per menu item)
        // Dark mode
        'dark_mode'             => 'light',  // 'light', 'dark', 'auto'
        // Search
        'search_enabled'        => '0',
        'search_placeholder'    => 'Search...',
        'search_color'          => '',
        'search_bg'             => '',
        // Sticky
        'sticky'                => '0',
        'sticky_bg'             => '',
        'sticky_shadow'         => '1',
        'sticky_z_index'        => '9999',
        'sticky_padding_x'      => '',
        'sticky_padding_y'      => '',
        'sticky_justify'        => 'flex-start',  // allineamento contenuto sticky
        'sticky_align_items'    => 'center',
        'auto_hide_scroll'      => '1',  // ──── FEATURE 5: auto-hide on scroll ────
        // Logo
        'logo_url'              => '',
        'logo_width'            => '120',
        'logo_height'           => '',
        'logo_alt'              => '',
        'logo_link'             => '',
        'logo_position'         => 'left',
        // Submenu
        'submenu_bg'            => '#ffffff',
        'submenu_border'        => '#e5e7eb',
        'submenu_link_color'    => '#374151',
        'submenu_shadow'        => '1',
        'submenu_animation'     => 'fade',
        // Accessibility
        'a11y_focus_visible'    => '1',    // mostra outline focus
        'a11y_focus_color'      => '#2271b1', // focus outline color
        'a11y_focus_offset'     => '2',    // offset outline (px)
        'a11y_focus_width'      => '2',    // spessore outline (px)
        'a11y_skip_link'        => '0',    // mostra "salta al contenuto"
        'a11y_skip_target'      => 'main', // id target del link skip
        'a11y_reduced_motion'   => '0',    // rispetta prefers-reduced-motion
        'a11y_aria_label'       => 'Main menu', // aria-label del <nav>
        'a11y_min_touch_target' => '0',    // dimensione minima 44x44px su mobile
        'a11y_link_underline'   => '0',    // forza sottolineatura link
        'a11y_high_contrast'    => '0',    // high contrast mode
        // Link shape
        'link_border_radius'    => '',
        // Gradients
        'container_bg_gradient'    => '',
        'link_hover_bg_gradient'   => '',
        'link_active_bg_gradient'  => '',
        // Sticky dynamic
        'sticky_bg_gradient'       => '',
        'sticky_transition'        => '0.3',
        'sticky_shrink'            => '0',
        // Scroll progress bar
        'progress_bar_enabled'     => '0',
        'progress_bar_color'       => '#667eea',
        'progress_bar_height'      => '3',
        'progress_bar_position'    => 'bottom',
    );
}
