<?php
/**
 * MenuX Free — CSS Generator
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_generate_css($style) {
    $s = wp_parse_args((array) $style, menux_style_defaults());

    // Google Font import
    $css = '';
    if (!empty($s['google_font'])) {
        $gf_slug = str_replace(' ', '+', sanitize_text_field($s['google_font']));
        $css .= "@import url('https://fonts.googleapis.com/css2?family={$gf_slug}:wght@300;400;500;600;700&display=swap');\n";
    }

    // Dark mode attribute
    $dark_mode = $s['dark_mode'] ?? 'light';

    // Container
    $cr = '';
    if (!empty($s['container_bg_gradient'])) $cr .= 'background:' . wp_strip_all_tags($s['container_bg_gradient']) . ';';
    elseif ($s['container_bg'] !== '')       $cr .= 'background:' . sanitize_hex_color($s['container_bg']) . ';';
    if ($s['container_border'] !== '') $cr .= 'border-bottom:1px solid ' . sanitize_hex_color($s['container_border']) . ';';
    if (!empty($s['google_font']))     $cr .= "font-family:'" . sanitize_text_field($s['google_font']) . "', sans-serif;";
    elseif (!empty($s['font_family'])) $cr .= 'font-family:' . sanitize_text_field($s['font_family']) . ';';
    $strans = isset($s['sticky_transition']) && is_numeric($s['sticky_transition']) ? floatval($s['sticky_transition']) : 0.3;
    $css .= '.menux-container{position:relative;display:flex;flex-direction:row;align-items:center;flex-wrap:wrap;transition:background '.$strans.'s ease,box-shadow '.$strans.'s ease,padding '.$strans.'s ease;' . $cr . '}';

    // Dark mode CSS variables
    if ($dark_mode === 'dark') {
        $css .= '[data-bs-theme="dark"] .menux-container,.menux-container[data-bm-theme="dark"]{color-scheme:dark;}';
    } elseif ($dark_mode === 'auto') {
        $css .= '@media(prefers-color-scheme:dark){.menux-container{color-scheme:dark;}}';
    }

    // Sticky
    if ($s['sticky'] === '1') {
        $sbg = !empty($s['sticky_bg']) ? 'background:' . sanitize_hex_color($s['sticky_bg']) . ';' : '';
        $sshad  = $s['sticky_shadow'] !== '0'  ? 'box-shadow:0 2px 12px rgba(0,0,0,.12);' : '';
        $sz     = !empty($s['sticky_z_index']) ? intval($s['sticky_z_index']) : 9999;
        $sjust  = !empty($s['sticky_justify'])      ? esc_attr($s['sticky_justify'])      : 'flex-start';
        $salign = !empty($s['sticky_align_items'])  ? esc_attr($s['sticky_align_items'])  : 'center';
        $spx    = !empty($s['sticky_padding_x'])    ? 'padding-left:'.intval($s['sticky_padding_x']).'px;padding-right:'.intval($s['sticky_padding_x']).'px;' : '';
        $spy    = !empty($s['sticky_padding_y'])    ? 'padding-top:'.intval($s['sticky_padding_y']).'px;padding-bottom:'.intval($s['sticky_padding_y']).'px;' : '';
        $css   .= '.menux-container.menux-sticky-fixed{position:fixed;top:0;left:0;width:100%;z-index:'.$sz.';justify-content:'.$sjust.';align-items:'.$salign.';'.$sbg.$sshad.$spx.$spy.'}';
        $css   .= '.menux-sticky-spacer{display:block;}';
        // Shrink on sticky
        if (!empty($s['sticky_shrink']) && $s['sticky_shrink'] === '1') {
            $css .= '.menux-container.menux-sticky-fixed{padding-top:6px!important;padding-bottom:6px!important;}';
            $css .= '.menux-container.menux-sticky-fixed .menux-link{font-size:13px;}';
            $css .= '.menux-container.menux-sticky-fixed .menux-logo img{max-height:32px!important;}';
        }
        // Progress bar
        if (!empty($s['progress_bar_enabled']) && $s['progress_bar_enabled'] === '1') {
            $pb_color  = !empty($s['progress_bar_color'])  ? sanitize_hex_color($s['progress_bar_color'])  : '#667eea';
            $pb_height = !empty($s['progress_bar_height']) ? intval($s['progress_bar_height'])              : 3;
            $pb_pos    = (!empty($s['progress_bar_position']) && $s['progress_bar_position'] === 'top') ? 'top:0' : 'bottom:0';
            $css .= '.menux-progress-bar{position:absolute;'.$pb_pos.';left:0;height:'.$pb_height.'px;background:'.$pb_color.';width:0%;transition:width .1s linear;pointer-events:none;z-index:10;}';
        }
    }
    // Progress bar when sticky is off (standalone reading bar)
    if (empty($s['sticky']) || $s['sticky'] !== '1') {
        if (!empty($s['progress_bar_enabled']) && $s['progress_bar_enabled'] === '1') {
            $pb_color  = !empty($s['progress_bar_color'])  ? sanitize_hex_color($s['progress_bar_color'])  : '#667eea';
            $pb_height = !empty($s['progress_bar_height']) ? intval($s['progress_bar_height'])              : 3;
            $pb_pos    = (!empty($s['progress_bar_position']) && $s['progress_bar_position'] === 'top') ? 'top:0' : 'bottom:0';
            $css .= '.menux-progress-bar{position:absolute;'.$pb_pos.';left:0;height:'.$pb_height.'px;background:'.$pb_color.';width:0%;transition:width .1s linear;pointer-events:none;z-index:10;}';
        }
    }

    // List layout
    $gap = ($s['gap'] !== '') ? max(0, intval($s['gap'])) : 20;
    $nav_justify = !empty($s['nav_justify']) ? esc_attr($s['nav_justify']) : 'flex-start';
    $css .= '.menux-list{list-style:none;display:flex;margin:0;padding:0;flex-wrap:wrap;gap:' . $gap . 'px;align-items:center;justify-content:' . $nav_justify . ';}';

    // ── Entrance animation ──
    $entrance     = $s['entrance_animation'] ?? 'none';
    $ent_dur      = isset($s['entrance_duration']) && is_numeric($s['entrance_duration']) ? floatval($s['entrance_duration']) : 0.5;
    $ent_delay    = isset($s['entrance_delay'])    && is_numeric($s['entrance_delay'])    ? floatval($s['entrance_delay'])    : 0;
    $ent_stagger  = isset($s['entrance_stagger'])  && is_numeric($s['entrance_stagger'])  ? floatval($s['entrance_stagger'])  : 0;

    if ($entrance !== 'none') {
        $css .= '@keyframes bm-entrance-fadeIn{from{opacity:0}to{opacity:1}}';
        $css .= '@keyframes bm-entrance-slideDown{from{opacity:0;transform:translateY(-24px)}to{opacity:1;transform:translateY(0)}}';
        $css .= '@keyframes bm-entrance-slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}';
        $css .= '@keyframes bm-entrance-slideLeft{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:translateX(0)}}';
        $css .= '@keyframes bm-entrance-slideRight{from{opacity:0;transform:translateX(-24px)}to{opacity:1;transform:translateX(0)}}';
        $css .= '@keyframes bm-entrance-zoomIn{from{opacity:0;transform:scale(0.85)}to{opacity:1;transform:scale(1)}}';
        $css .= '@keyframes bm-entrance-flipX{from{opacity:0;transform:perspective(400px) rotateX(-60deg)}to{opacity:1;transform:perspective(400px) rotateX(0)}}';

        $ent_anim_val = esc_attr($entrance);
        if ($ent_stagger > 0) {
            // Stagger: animate each li individually
            $css .= '.menux-list > li{opacity:0;}';
            for ($i = 0; $i < 20; $i++) {
                $d = round($ent_delay + $i * $ent_stagger, 3);
                $css .= '.menux-list > li:nth-child(' . ($i + 1) . '){animation:bm-entrance-' . $ent_anim_val . ' ' . $ent_dur . 's ease forwards ' . $d . 's;}';
            }
        } else {
            // Whole container animates
            $css .= '.menux-container{animation:bm-entrance-' . $ent_anim_val . ' ' . $ent_dur . 's ease both ' . $ent_delay . 's;}';
        }
    }

    // Link animation keyframes
    $anim = $s['link_animation'] ?? 'none';
    $css .= '@keyframes bm-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}';
    $css .= '@keyframes bm-shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}';
    $css .= '@keyframes bm-bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}';
    $css .= '@keyframes bm-glow{0%,100%{text-shadow:none}50%{text-shadow:0 0 8px currentColor}}';
    $css .= '@keyframes bm-underline-grow{from{width:0}to{width:100%}}';

    // Link base
    $trans_dur = !empty($s['link_transition']) ? floatval($s['link_transition']) : 0.3;
    $lr = 'text-decoration:none;display:inline-flex;align-items:center;transition:all '.$trans_dur.'s ease;position:relative;';
    if ($s['link_color'] !== '')       $lr .= 'color:' . sanitize_hex_color($s['link_color']) . ';';
    if ($s['font_size'] !== '')        $lr .= 'font-size:' . intval($s['font_size']) . 'px;';
    if ($s['font_weight'] !== '')      $lr .= 'font-weight:' . esc_attr($s['font_weight']) . ';';
    if (!empty($s['letter_spacing']))  $lr .= 'letter-spacing:' . floatval($s['letter_spacing']) . 'px;';
    if (!empty($s['text_transform']))  $lr .= 'text-transform:' . sanitize_text_field($s['text_transform']) . ';';
    $px = ($s['padding_x'] !== '') ? intval($s['padding_x']) : 0;
    $py = ($s['padding_y'] !== '') ? intval($s['padding_y']) : 0;
    if ($px || $py) $lr .= 'padding:' . $py . 'px ' . $px . 'px;';
    if ($s['link_border_radius'] !== '') $lr .= 'border-radius:' . intval($s['link_border_radius']) . 'px;';
    $css .= '.menux-list > li > a.menux-link{' . $lr . '}';

    // Link animation on hover
    $anim_css = '';
    if ($anim === 'pulse')         $anim_css = 'animation:bm-pulse .4s ease;';
    elseif ($anim === 'shake')     $anim_css = 'animation:bm-shake .4s ease;';
    elseif ($anim === 'bounce')    $anim_css = 'animation:bm-bounce .4s ease;';
    elseif ($anim === 'glow')      $anim_css = 'animation:bm-glow .6s ease infinite;';
    elseif ($anim === 'lift')      $anim_css = 'transform:translateY(-3px);';
    elseif ($anim === 'scale')     $anim_css = 'transform:scale(1.1);';
    elseif ($anim === 'underline') {
        $css .= '.menux-list > li > a.menux-link::after{content:"";position:absolute;bottom:0;left:0;width:0;height:2px;background:currentColor;transition:width .3s ease;}';
        $anim_css = '&::after{width:100%}';
    }

    // Hover
    $hr = '';
    if ($s['link_hover_color'] !== '') $hr .= 'color:' . sanitize_hex_color($s['link_hover_color']) . ';';
    if (!empty($s['link_hover_bg_gradient']))  $hr .= 'background:' . wp_strip_all_tags($s['link_hover_bg_gradient']) . ';';
    elseif (!empty($s['link_hover_bg']))       $hr .= 'background:' . sanitize_hex_color($s['link_hover_bg']) . ';';
    if ($anim_css && $anim !== 'underline') $hr .= $anim_css;
    if ($hr) $css .= '.menux-list > li > a.menux-link:hover{' . $hr . '}';

    // Active
    $ar = '';
    if ($s['link_active_color'] !== '')       $ar .= 'color:' . sanitize_hex_color($s['link_active_color']) . ';';
    if ($s['link_active_border'] !== '')      $ar .= 'border-bottom:2px solid ' . sanitize_hex_color($s['link_active_border']) . ';';
    if (!empty($s['link_active_font_weight'])) $ar .= 'font-weight:' . esc_attr($s['link_active_font_weight']) . '!important;';
    if (!empty($s['link_active_bg_gradient']))  $ar .= 'background:' . wp_strip_all_tags($s['link_active_bg_gradient']) . ';';
    elseif (!empty($s['link_active_bg']))       $ar .= 'background:' . sanitize_hex_color($s['link_active_bg']) . ';';
    if ($ar) $css .= '.menux-list > li > a.menux-link.active{' . $ar . '}';

    // Push last item to the right
    if ($s['push_last_item'] === '1') {
        $css .= '.menux-list > li:last-child{margin-left:auto;}';
    }

    // Last item custom color
    $licr = '';
    if (!empty($s['last_item_color'])) $licr .= 'color:' . sanitize_hex_color($s['last_item_color']) . '!important;';
    if (!empty($s['last_item_bg']))    $licr .= 'background:' . sanitize_hex_color($s['last_item_bg']) . ';';
    if ($licr) $css .= '.menux-list > li:last-child > a.menux-link{' . $licr . '}';
    if (!empty($s['last_item_hover_color'])) {
        $css .= '.menux-list > li:last-child > a.menux-link:hover{color:' . sanitize_hex_color($s['last_item_hover_color']) . '!important;}';
    }

    // Badge etichette
    $css .= '.menux-badge{display:inline-block;font-size:9px;font-weight:700;padding:1px 5px;border-radius:10px;margin-left:5px;vertical-align:middle;text-transform:uppercase;letter-spacing:.5px;line-height:1.6;}';

    // ──── FEATURE 2: Notification Dot animato ────
    $css .= '.menux-notif-dot{display:inline-block;width:8px;height:8px;background:#ef4444;border-radius:50%;margin-left:6px;vertical-align:top;position:relative;animation:menux-pulse 2s infinite;box-shadow:0 0 0 0 rgba(239,68,68,.5);}';
    $css .= '@keyframes menux-pulse{0%{box-shadow:0 0 0 0 rgba(239,68,68,.5)}70%{box-shadow:0 0 0 8px rgba(239,68,68,0)}100%{box-shadow:0 0 0 0 rgba(239,68,68,0)}}';

    // ──── FEATURE 5: Auto-hide on scroll ────
    $css .= '.menux-sticky-fixed.menux-autohide{transition:transform .3s cubic-bezier(.4,0,.2,1);}';
    $css .= '.menux-sticky-fixed.menux-autohide.menux-hidden{transform:translateY(-100%);pointer-events:none;}';

    // Submenu
    $smbc  = !empty($s['submenu_bg'])         ? sanitize_hex_color($s['submenu_bg'])         : '#ffffff';
    $smbd  = !empty($s['submenu_border'])      ? sanitize_hex_color($s['submenu_border'])      : '#e5e7eb';
    $smlc  = !empty($s['submenu_link_color'])  ? sanitize_hex_color($s['submenu_link_color'])  : '#374151';
    $smsh  = ($s['submenu_shadow']  ?? '1') === '1' ? 'box-shadow:0 8px 24px rgba(0,0,0,.12);' : '';
    $smanim = $s['submenu_animation'] ?? 'fade';
    $sm_anim_init = '';
    $sm_anim_show = '';
    if ($smanim === 'fade') {
        $sm_anim_init = 'opacity:0;pointer-events:none;transform:translateY(6px);';
        $sm_anim_show = 'opacity:1;pointer-events:auto;transform:translateY(0);';
    } elseif ($smanim === 'slide') {
        $sm_anim_init = 'opacity:0;pointer-events:none;transform:translateY(-10px);max-height:0;overflow:hidden;';
        $sm_anim_show = 'opacity:1;pointer-events:auto;transform:translateY(0);max-height:600px;';
    } else {
        $sm_anim_init = 'display:none;opacity:0;pointer-events:none;';
        $sm_anim_show = 'display:block;opacity:1;pointer-events:auto;';
    }

    $css .= '.menux-list li{position:relative;}';
    $css .= '.menux-submenu{list-style:none;margin:0;padding:6px 0;position:absolute;top:100%;left:0;min-width:200px;background:'.$smbc.';border:1px solid '.$smbd.';border-radius:8px;z-index:10001;'.$smsh.'transition:all .25s ease;'.$sm_anim_init.'}';
    $css .= '.menux-list li:hover > .menux-submenu{' . $sm_anim_show . '}';
    $css .= '.menux-submenu li{position:relative;}';
    $css .= '.menux-submenu a.menux-link{display:flex!important;align-items:center;padding:8px 16px!important;white-space:nowrap;color:'.$smlc.'!important;font-size:13px!important;font-weight:400!important;width:100%;box-sizing:border-box;}';
    $css .= '.menux-submenu a.menux-link:hover{background:rgba(0,0,0,.04);}';
    // 3° livello: apre a destra
    $css .= '.menux-submenu .menux-submenu{top:0;left:100%;margin-top:-6px;}';
    // Indicatore freccia per voci con figli
    $css .= '.menux-has-children > a.menux-link::after{content:"▾";margin-left:5px;font-size:10px;transition:transform .2s;}';
    $css .= '.menux-has-children > .menux-submenu li .menux-has-children > a.menux-link::after{content:"▸";}';

    // Hamburger
    $hbg = ($s['hamburger_bg'] !== '') ? 'background:'.sanitize_hex_color($s['hamburger_bg']).'; border-radius: 4px;' : '';
    $h_gap = '5px'; $h_height = '3px'; $h_radius = '3px'; $h_width = '30px';
    if ($s['hamburger_style'] === 'modern')  { $h_height = '4px'; $h_radius = '10px'; $h_gap = '6px'; }
    elseif ($s['hamburger_style'] === 'minimal') { $h_height = '2px'; $h_radius = '0px'; $h_gap = '7px'; $h_width = '35px'; }
    $css .= '.menux-hamburger{display:none;cursor:pointer;padding:12px;flex-direction:column;gap:'.$h_gap.';width:'.$h_width.'; box-sizing:content-box;' . $hbg . '}';
    $hc = ($s['hamburger_color'] !== '') ? sanitize_hex_color($s['hamburger_color']) : '#333333';
    $css .= '.menux-hamburger span{height:'.$h_height.';background:' . $hc . ';width:100%;display:block;transition:all 0.3s ease-in-out; border-radius:'.$h_radius.';}';

    // ════════════════════════════════════════════════════
    // MOBILE RESPONSIVE — breakpoint + modalità apertura
    // ════════════════════════════════════════════════════
    $bp              = ($s['mobile_breakpoint'] !== '') ? intval($s['mobile_breakpoint']) : 768;
    $bp_mode         = $s['mobile_breakpoint_mode'] ?? 'manual';
    $mobile_bg       = ($s['mobile_menu_bg'] !== '') ? 'background:'.sanitize_hex_color($s['mobile_menu_bg']).';' : '';
    $mobile_pad      = ($s['mobile_menu_pad'] !== '') ? intval($s['mobile_menu_pad']) : 0;
    $mobile_shadow   = ($s['mobile_menu_shadow'] == '1') ? 'box-shadow: 0 4px 12px rgba(0,0,0,0.1);' : '';
    $hamburger_align = esc_attr($s['hamburger_align']);
    $open_style      = $s['mobile_open_style'] ?? 'dropdown';
    $ov_bg           = !empty($s['mobile_overlay_bg']) ? sanitize_hex_color($s['mobile_overlay_bg']) : '#000000';
    $ov_opacity      = isset($s['mobile_overlay_opacity']) ? floatval($s['mobile_overlay_opacity']) : 0.5;
    $ov_blur         = isset($s['mobile_overlay_blur']) ? intval($s['mobile_overlay_blur']) : 0;
    $fs_align        = esc_attr($s['mobile_fullscreen_align'] ?? 'center');
    $drawer_w        = isset($s['mobile_drawer_width']) ? intval($s['mobile_drawer_width']) : 280;
    $open_anim       = $s['mobile_open_animation'] ?? 'fade';

    // ──── Selettore CSS mobile: .menux-is-mobile (auto) oppure media query (manual) ────
    $mob_sel_open  = $bp_mode === 'auto' ? '.menux-is-mobile' : "@media(max-width:{$bp}px)";
    $desk_sel_open = $bp_mode === 'auto' ? '.menux-is-desktop' : "@media(min-width:".($bp+1)."px)";

    // Desktop: fila orizzontale
    if ($bp_mode === 'manual') {
        $css .= "@media(min-width:".($bp+1)."px){.menux-container{display:flex;flex-direction:row;align-items:center;flex-wrap:nowrap;}.menux-hamburger{display:none!important;}.menux-list{display:flex!important;flex-direction:row;align-items:center;flex:1;min-width:0;justify-content:".$nav_justify.";}}";
    } else {
        // In auto mode il JS aggiunge le classi — usiamo classi statiche
        $css .= '.menux-is-desktop .menux-hamburger{display:none!important;}';
        $css .= '.menux-is-desktop .menux-list{display:flex!important;flex-direction:row;align-items:center;flex:1;min-width:0;justify-content:'.$nav_justify.';}';
        $css .= '.menux-is-desktop .menux-container{flex-direction:row;align-items:center;flex-wrap:nowrap;}';
    }

    // ──── Overlay backdrop (usato da fullscreen e drawer) ────
    $css .= '.menux-overlay{position:fixed;inset:0;background:'.esc_attr($ov_bg).';opacity:0;z-index:99998;transition:opacity .3s ease;cursor:pointer;pointer-events:none;display:none;}';
    $css .= '.menux-overlay.visible{display:block;opacity:'.esc_attr($ov_opacity).';pointer-events:auto;}';
    if ($ov_blur > 0) {
        $css .= '.menux-overlay.visible{backdrop-filter:blur('.intval($ov_blur).'px);-webkit-backdrop-filter:blur('.intval($ov_blur).'px);}';
    }

    // ──── Animazioni keyframes ────
    $css .= '@keyframes bm-fadeIn{from{opacity:0}to{opacity:1}}';
    $css .= '@keyframes bm-slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}';
    $css .= '@keyframes bm-slideLeft{from{opacity:0;transform:translateX(-100%)}to{opacity:1;transform:translateX(0)}}';
    $css .= '@keyframes bm-slideRight{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}';
    $css .= '@keyframes bm-scaleIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}';
    $css .= '@keyframes fadeInMenu{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}';

    // Animazione per stile corrente
    $anim_map = array(
        'fade'  => 'bm-fadeIn .3s ease both',
        'slide' => 'bm-slideDown .3s ease both',
        'scale' => 'bm-scaleIn .3s ease both',
    );
    $anim_css = $anim_map[$open_anim] ?? 'bm-fadeIn .3s ease both';
    $anim_drawer_l = 'bm-slideLeft .3s ease both';
    $anim_drawer_r = 'bm-slideRight .3s ease both';

    // Chiudi pulsante (X) usato in fullscreen e drawer
    $css .= '.menux-close-btn{display:none;position:fixed;top:16px;right:20px;background:none;border:none;font-size:32px;line-height:1;cursor:pointer;color:inherit;opacity:.7;z-index:100000;padding:8px;transition:opacity .2s;}';
    $css .= '.menux-close-btn:hover{opacity:1;}';
    // Mostra il close-btn solo quando il menu è aperto E la modalità è fullscreen o drawer
    $css .= '.menux-list.show ~ .menux-close-btn{display:block;}';
    // Alternativa robusta: classe esplicita aggiunta via JS
    $css .= '.menux-close-btn.visible{display:block;}';

    // ════ BLOCCO MOBILE ════
    if ($bp_mode === 'manual') {
        $media_open  = "@media(max-width:{$bp}px){";
        $media_close = "}";
    } else {
        $media_open  = ".menux-is-mobile{";
        $media_close = "}";
    }

    // Hamburger visibile su mobile
    $css .= $media_open
        . '.menux-container{display:flex;flex-direction:row;align-items:center;flex-wrap:wrap;}'
        . '.menux-hamburger{display:flex;' . ( $hamburger_align === 'flex-start' ? 'margin-right:auto;' : ( $hamburger_align === 'center' ? 'margin-left:auto;margin-right:auto;' : 'margin-left:auto;' ) ) . '}'
        . $media_close;

    // ════ STILI SPECIFICI PER MODALITÀ ════
    if ($open_style === 'dropdown') {
        // ── Dropdown classico ──
        $css .= $media_open
            . '.menux-list{display:none;flex-direction:column;width:100%;gap:0;padding:'.$mobile_pad.'px;box-sizing:border-box;'.$mobile_bg.$mobile_shadow.'}'
            . '.menux-list>li{width:100%;text-align:center;border-bottom:1px solid rgba(0,0,0,0.05);}'
            . '.menux-list>li:last-child{border-bottom:none;}'
            . '.menux-list>li>a.menux-link{padding:15px;display:flex;justify-content:center;}'
            . '.menux-list.show{display:flex;animation:'.$anim_css.';position:relative;z-index:9999;}'
            . '.menux-logo{flex-shrink:0;display:inline-flex;align-items:center;}'
            . '.menux-search-wrap{margin-left:auto;}'
            . $media_close;

    } elseif ($open_style === 'fullscreen') {
        // ── Fullscreen overlay ──
        $fs_bg = $mobile_bg ?: 'background:rgba(15,15,15,.97);';
        $css .= $media_open
            . '.menux-list{'
            .   'display:none;position:fixed;inset:0;z-index:99999;'
            .   'flex-direction:column;align-items:'.$fs_align.';justify-content:center;'
            .   'padding:60px '.$mobile_pad.'px '.$mobile_pad.'px;box-sizing:border-box;'
            .   $fs_bg
            .   'overflow-y:auto;'
            . '}'
            . '.menux-list.show{display:flex;animation:'.$anim_css.';}'
            . '.menux-list>li{width:100%;text-align:'.($fs_align==='center'?'center':($fs_align==='flex-end'?'right':'left')).';border-bottom:none;}'
            . '.menux-list>li>a.menux-link{'
            .   'padding:16px 32px;font-size:clamp(20px,4vw,32px);font-weight:700;'
            .   'display:flex;justify-content:'.$fs_align.';'
            .   'letter-spacing:.02em;transition:opacity .2s,transform .2s;'
            . '}'
            . '.menux-list>li>a.menux-link:hover{opacity:.6;transform:scale(1.04);}'
            // Stagger animazione voci
            . '.menux-list.show>li{animation:bm-slideDown .4s ease both;}'
            . '.menux-list.show>li:nth-child(1){animation-delay:.05s}'
            . '.menux-list.show>li:nth-child(2){animation-delay:.1s}'
            . '.menux-list.show>li:nth-child(3){animation-delay:.15s}'
            . '.menux-list.show>li:nth-child(4){animation-delay:.2s}'
            . '.menux-list.show>li:nth-child(5){animation-delay:.25s}'
            . '.menux-list.show>li:nth-child(6){animation-delay:.3s}'
            . '.menux-list.show>li:nth-child(n+7){animation-delay:.35s}'
            . $media_close;

    } elseif ($open_style === 'drawer-left' || $open_style === 'drawer-right') {
        // ── Drawer laterale ──
        $is_right = ($open_style === 'drawer-right');
        $drawer_side = $is_right ? 'right:0;' : 'left:0;';
        $drawer_anim = $is_right ? $anim_drawer_r : $anim_drawer_l;
        $css .= $media_open
            . '.menux-list{'
            .   'display:none;position:fixed;top:0;'.$drawer_side
            .   'width:min('.$drawer_w.'px,90vw);height:100vh;z-index:99999;'
            .   'flex-direction:column;align-items:flex-start;justify-content:flex-start;'
            .   'padding:64px 0 24px;box-sizing:border-box;'
            .   $mobile_bg
            .   ($mobile_shadow ?: 'box-shadow:'.($is_right?'-4px':' 4px').' 0 24px rgba(0,0,0,.2);')
            .   'overflow-y:auto;'
            . '}'
            . '.menux-list.show{display:flex;animation:'.$drawer_anim.';}'
            . '.menux-list>li{width:100%;border-bottom:1px solid rgba(128,128,128,.1);}'
            . '.menux-list>li:last-child{border-bottom:none;}'
            . '.menux-list>li>a.menux-link{padding:14px 24px;display:flex;justify-content:flex-start;font-size:15px;}'
            . $media_close;
    }

    // ── Submenu mobile (accordion) — uguale per tutti le modalità ──
    $css .= $media_open
        . '.menux-submenu{position:static!important;box-shadow:none!important;border:none!important;border-left:3px solid rgba(0,0,0,.1)!important;border-radius:0!important;margin-left:15px;background:rgba(0,0,0,.02)!important;opacity:1!important;transform:none!important;pointer-events:auto!important;display:none!important;max-height:none!important;}'
        . '.menux-submenu.mobile-open{display:block!important;}'
        . '.menux-has-children>a.menux-link{justify-content:center;}'
        . '.menux-search-field.open{width:140px!important;}'
        . '.menux-search-results{right:auto;left:0;min-width:240px;}'
        . $media_close;

    // ── Hamburger X animation ──
    $css .= $media_open
        . '.menux-hamburger.open span:nth-child(1){transform:translateY(calc('.$h_gap.' + '.$h_height.')) rotate(45deg);}'
        . '.menux-hamburger.open span:nth-child(2){opacity:0;}'
        . '.menux-hamburger.open span:nth-child(3){transform:translateY(calc(-'.$h_gap.' - '.$h_height.')) rotate(-45deg);}'
        . $media_close;

    // ── Logo (advanced system) ──
    if ( class_exists( 'Menux_Logo' ) ) {
        $css .= Menux_Logo::generate_css();
    }

    // ── Mega Menu ──
    if ( class_exists( 'Menux_MegaMenu' ) ) {
        $css .= Menux_MegaMenu::generate_css();
    }

    return $css;
}
