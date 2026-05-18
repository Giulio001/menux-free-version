<?php
/**
 * MenuX Free — Style Defaults
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_style_defaults() {
    return array(
        'container_bg'               => '',
        'container_bg_gradient'      => '',
        'container_border'           => '',
        'link_color'                 => '',
        'link_hover_color'           => '',
        'link_hover_bg'              => '',
        'link_hover_bg_gradient'     => '',
        'link_active_color'          => '',
        'link_active_border'         => '',
        'link_active_font_weight'    => '',
        'link_active_bg'             => '',
        'link_active_bg_gradient'    => '',
        'font_size'             => '',
        'font_weight'           => '',
        'gap'                   => '20',
        'padding_x'             => '0',
        'padding_y'             => '0',
        'mobile_breakpoint'     => '768',
        'mobile_breakpoint_mode'=> 'manual',
        'hamburger_color'       => '',
        'hamburger_bg'          => '',
        'mobile_menu_bg'        => '',
        'hamburger_style'       => 'classic',
        'hamburger_align'       => 'flex-end',
        'mobile_menu_pad'       => '0',
        'mobile_menu_shadow'    => '0',
        'mobile_open_style'     => 'dropdown',
        'mobile_overlay_bg'     => '#000000',
        'mobile_overlay_opacity'=> '0.5',
        'mobile_overlay_blur'   => '0',
        'mobile_fullscreen_align'=> 'center',
        'mobile_drawer_width'   => '280',
        'mobile_open_animation' => 'fade',
        // Typography
        'google_font'           => '',
        'font_family'           => '',
        'letter_spacing'        => '',
        'text_transform'        => '',
        // Animations
        'link_animation'        => 'none',
        'link_transition'       => '0.3',
        // Layout
        'nav_justify'           => 'flex-end',
        'push_last_item'        => '0',
        'last_item_color'       => '',
        'last_item_hover_color' => '',
        'last_item_bg'          => '',
        // Entrance animation
        'entrance_animation'    => 'none',
        'entrance_duration'     => '0.5',
        'entrance_delay'        => '0',
        'entrance_stagger'      => '0',
        // Dark mode
        'dark_mode'             => 'light',
        // Sticky
        'sticky'                => '0',
        'sticky_bg'             => '',
        'sticky_shadow'         => '1',
        'sticky_z_index'        => '9999',
        'sticky_padding_x'      => '',
        'sticky_padding_y'      => '',
        'sticky_justify'        => 'flex-start',
        'sticky_align_items'    => 'center',
        'sticky_transition'     => '0.3',
        'sticky_shrink'         => '0',
        'auto_hide_scroll'      => '1',
        // Submenu
        'submenu_bg'            => '#ffffff',
        'submenu_border'        => '#e5e7eb',
        'submenu_link_color'    => '#374151',
        'submenu_shadow'        => '1',
        'submenu_animation'     => 'fade',
        // Link shape
        'link_border_radius'    => '',
        // Scroll progress bar
        'progress_bar_enabled'  => '0',
        'progress_bar_color'    => '#667eea',
        'progress_bar_height'   => '3',
        'progress_bar_position' => 'bottom',
    );
}
