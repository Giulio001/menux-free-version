<?php
/**
 * MenuX Free — Style Panel
 * Color field helper and full style configuration panel.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_color_field($field, $value) {
    $has     = ($value !== '' && $value !== null);
    $hex     = $has ? $value : '#333333';
    $opacity = $has ? '1' : '0.35';
    echo '<div class="menux-color-row" style="display:flex;align-items:center;gap:8px;">';
    echo '<input type="checkbox" name="menux_style_use[' . esc_attr($field) . ']" value="1"'
        . ($has ? ' checked' : '') . ' onchange="menux_toggleColor(this)">';
    echo '<input type="color" name="menux_style[' . esc_attr($field) . ']" value="' . esc_attr($hex) . '"'
        . ' style="cursor:pointer;opacity:' . esc_attr($opacity) . ';width:42px;height:30px;padding:2px;border:1px solid #c3c4c7;border-radius:3px;"'
        . ' onchange="menux_liveStylePreview()">';
    echo '<span style="color:#999;font-size:12px;">' . ($has ? esc_html($value) : 'not set') . '</span>';
    echo '</div>';
}

function menux_render_style_panel($style) {
    $s  = wp_parse_args((array) $style, menux_style_defaults());
    $td = esc_attr( 'padding:7px 12px 7px 0; font-size:13px; font-weight:500; width:200px; vertical-align:top;' );
    $tv = esc_attr( 'padding:7px 0;' );
    $tabs = array(
        'colors'    => '🎨 Colors',
        'typo'      => '🔤 Typography',
        'layout'    => '📐 Layout',
        'mobile'    => '📱 Mobile',
        'darkmode'  => '🌙 Dark Mode',
        'css'       => '⚙️ Advanced',
    );
    ?>
    <div class="bm-card menux-style-panel">
        <div class="bm-card-header">
            <span class="bm-card-icon bm-card-icon-amber">🎨</span>
            <div class="bm-card-titles">
                <h2 class="bm-card-title">Menu Style</h2>
                <p class="bm-card-subtitle">Customize colors, typography, layout and behavior</p>
            </div>
        </div>

        <!-- TAB NAV -->
        <div id="bm-style-tabs" class="bm-tabs-nav">
            <?php foreach ($tabs as $key => $label): ?>
            <button type="button" class="bm-tab-btn" data-tab="bm-tab-<?php echo esc_attr( $key ); ?>"
                onclick="menux_switchTab('bm-tab-<?php echo esc_js( $key ); ?>')">
                <?php echo esc_html( $label ); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="bm-card-body bm-tabs-body">

        <!-- ===== TAB: COLORI ===== -->
        <div id="bm-tab-colors" class="bm-tab-pane">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; flex-wrap:wrap;">
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Container</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <?php foreach (array('container_bg'=>'Background','container_border'=>'Bottom border') as $f=>$l): ?>
            <tr><td style="<?php echo esc_attr( $td );?>"><?php echo esc_html( $l );?></td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field($f,$s[$f]);?></td></tr>
            <?php endforeach; ?>
            </tbody></table>

            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">Normal links</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr><td style="<?php echo esc_attr( $td );?>">Text color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_color',$s['link_color']);?></td></tr>
            </tbody></table>

            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">Links on hover</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr><td style="<?php echo esc_attr( $td );?>">Text color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_hover_color',$s['link_hover_color']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Background</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_hover_bg',$s['link_hover_bg']);?></td></tr>
            </tbody></table>
        </div>
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Active link (current page)</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr><td style="<?php echo esc_attr( $td );?>">Text color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_active_color',$s['link_active_color']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Background</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_active_bg',$s['link_active_bg']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Bottom border</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('link_active_border',$s['link_active_border']);?></td></tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Active font weight</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[link_active_font_weight]" onchange="menux_liveStylePreview()">
                        <option value="" <?php selected($s['link_active_font_weight'],'');?>>Inherit</option>
                        <option value="400" <?php selected($s['link_active_font_weight'],'400');?>>Regular (400)</option>
                        <option value="500" <?php selected($s['link_active_font_weight'],'500');?>>Medium (500)</option>
                        <option value="600" <?php selected($s['link_active_font_weight'],'600');?>>Semibold (600)</option>
                        <option value="700" <?php selected($s['link_active_font_weight'],'700');?>>Bold (700)</option>
                    </select>
                </td>
            </tr>
            </tbody></table>

            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">Last menu item</div>
            <p style="font-size:11px;color:#6b7280;margin:0 0 8px;">Useful for a "Logout" button or final CTA.</p>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Push right</td>
                <td style="<?php echo esc_attr( $tv );?>"><label><input type="checkbox" name="menux_style[push_last_item]" value="1" <?php checked($s['push_last_item'],'1');?> onchange="menux_liveStylePreview()"> <code>margin-left: auto</code></label></td>
            </tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Text color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('last_item_color',$s['last_item_color']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Hover color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('last_item_hover_color',$s['last_item_hover_color']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Background</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('last_item_bg',$s['last_item_bg']);?></td></tr>
            </tbody></table>
        </div>
        </div>

        <!-- Submenu colors -->
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb;">
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Submenu</div>
            <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <?php foreach (array('submenu_bg'=>'Background','submenu_border'=>'Border','submenu_link_color'=>'Link color') as $f=>$l): ?>
            <div style="display:flex;align-items:center;gap:8px;"><span style="font-size:12px;color:#374151;min-width:90px;"><?php echo esc_html( $l );?></span><?php menux_color_field($f,$s[$f]);?></div>
            <?php endforeach; ?>
            </div>
        </div>

        </div><!-- end tab colors -->

        <!-- ===== TAB: TIPOGRAFIA ===== -->
        <div id="bm-tab-typo" class="bm-tab-pane" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Font</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Google Font</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="text" name="menux_style[google_font]" value="<?php echo esc_attr($s['google_font']);?>" placeholder="es. Inter, Nunito, Playfair Display…" style="width:100%;max-width:260px;" oninput="menux_liveStylePreview()">
                    <p class="description" style="font-size:11px;margin:3px 0 0;">Trova il nome su <a href="https://fonts.google.com" target="_blank">fonts.google.com</a></p>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Font fallback</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="text" name="menux_style[font_family]" value="<?php echo esc_attr($s['font_family']);?>" placeholder="es. Arial, sans-serif" style="width:100%;max-width:260px;" oninput="menux_liveStylePreview()">
                    <p class="description" style="font-size:11px;margin:3px 0 0;">Used if Google Font is empty.</p>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Size (px)</td>
                <td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[font_size]" value="<?php echo esc_attr($s['font_size']);?>" min="8" max="48" style="width:80px;" onchange="menux_liveStylePreview()"></td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Weight (font-weight)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[font_weight]" onchange="menux_liveStylePreview()">
                        <option value="" <?php selected($s['font_weight'],'');?>>Normal</option>
                        <option value="300" <?php selected($s['font_weight'],'300');?>>Light (300)</option>
                        <option value="400" <?php selected($s['font_weight'],'400');?>>Regular (400)</option>
                        <option value="500" <?php selected($s['font_weight'],'500');?>>Medium (500)</option>
                        <option value="600" <?php selected($s['font_weight'],'600');?>>Semibold (600)</option>
                        <option value="700" <?php selected($s['font_weight'],'700');?>>Bold (700)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Letter spacing (px)</td>
                <td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[letter_spacing]" value="<?php echo esc_attr($s['letter_spacing']);?>" step="0.5" min="-2" max="10" style="width:80px;" oninput="menux_liveStylePreview()"></td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Text transform</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[text_transform]" onchange="menux_liveStylePreview()">
                        <option value="none"       <?php selected($s['text_transform'],'none');?>>None</option>
                        <option value="uppercase"  <?php selected($s['text_transform'],'uppercase');?>>UPPERCASE</option>
                        <option value="lowercase"  <?php selected($s['text_transform'],'lowercase');?>>lowercase</option>
                        <option value="capitalize" <?php selected($s['text_transform'],'capitalize');?>>Capitalize</option>
                    </select>
                </td>
            </tr>
            </tbody></table>
        </div>
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Animations &amp; Transitions</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Hover effect</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[link_animation]" onchange="menux_liveStylePreview()">
                        <option value="none"      <?php selected($s['link_animation'],'none');?>>None</option>
                        <option value="lift"      <?php selected($s['link_animation'],'lift');?>>🚀 Lift</option>
                        <option value="scale"     <?php selected($s['link_animation'],'scale');?>>🔍 Scale</option>
                        <option value="pulse"     <?php selected($s['link_animation'],'pulse');?>>💓 Pulse</option>
                        <option value="bounce"    <?php selected($s['link_animation'],'bounce');?>>🏀 Bounce</option>
                        <option value="shake"     <?php selected($s['link_animation'],'shake');?>>📳 Shake</option>
                        <option value="glow"      <?php selected($s['link_animation'],'glow');?>>✨ Glow</option>
                        <option value="underline" <?php selected($s['link_animation'],'underline');?>>〰️ Animated underline</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Transition duration (s)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[link_transition]" value="<?php echo esc_attr($s['link_transition'] ?? '0.3');?>" step="0.1" min="0" max="2" style="width:80px;" oninput="menux_liveStylePreview()">
                    <p class="description" style="font-size:11px;margin:3px 0 0;">Hover animation speed (e.g. 0.3 = 300ms)</p>
                </td>
            </tr>
            </tbody></table>

            <div style="margin-top:20px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;">
                <div style="font-size:12px;font-weight:700;color:#0369a1;margin-bottom:8px;">⚡ Quick fonts</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    <?php foreach (array('Inter','Roboto','Nunito','Montserrat','Lato','Poppins','Open Sans','Raleway','Playfair Display','Source Sans Pro') as $gf): ?>
                    <button type="button" class="button button-small" style="font-size:11px;" onclick="document.querySelector('[name=\"menux_style[google_font]\"]').value='<?php echo esc_js( $gf );?>';menux_liveStylePreview();"><?php echo esc_html( $gf );?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        </div>
        </div><!-- end tab typo -->

        <!-- ===== TAB: LAYOUT ===== -->
        <div id="bm-tab-layout" class="bm-tab-pane" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Spacing</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Items alignment</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[nav_justify]" onchange="menux_liveStylePreview()">
                        <option value="flex-start"   <?php selected($s['nav_justify'] ?? 'flex-start','flex-start');?>>⬅️ Left</option>
                        <option value="center"       <?php selected($s['nav_justify'] ?? 'flex-start','center');?>>↔️ Center</option>
                        <option value="flex-end"     <?php selected($s['nav_justify'] ?? 'flex-start','flex-end');?>>➡️ Right</option>
                        <option value="space-between"<?php selected($s['nav_justify'] ?? 'flex-start','space-between');?>>⟺ Space between</option>
                        <option value="space-evenly" <?php selected($s['nav_justify'] ?? 'flex-start','space-evenly');?>>⠿ Space evenly</option>
                    </select>
                </td>
            </tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Item gap (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[gap]" value="<?php echo esc_attr($s['gap']);?>" min="0" style="width:80px;" onchange="menux_liveStylePreview()"></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Link horizontal padding (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[padding_x]" value="<?php echo esc_attr($s['padding_x']);?>" min="0" style="width:80px;" onchange="menux_liveStylePreview()"></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Link vertical padding (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[padding_y]" value="<?php echo esc_attr($s['padding_y']);?>" min="0" style="width:80px;" onchange="menux_liveStylePreview()"></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Link border radius (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[link_border_radius]" value="<?php echo esc_attr($s['link_border_radius'] ?? '');?>" min="0" max="50" placeholder="0" style="width:80px;" onchange="menux_liveStylePreview()"><p class="description" style="font-size:11px;margin:3px 0 0;">E.g. 20 for pill, 8 for rounded tab</p></td></tr>
            </tbody></table>

            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">Submenu</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr><td style="<?php echo esc_attr( $td );?>">Shadow</td><td style="<?php echo esc_attr( $tv );?>"><label><input type="checkbox" name="menux_style[submenu_shadow]" value="1" <?php checked($s['submenu_shadow'],'1');?> onchange="menux_liveStylePreview()"> Enable</label></td></tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Open animation</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[submenu_animation]" onchange="menux_liveStylePreview()">
                        <option value="fade"  <?php selected($s['submenu_animation'],'fade');?>>Fade + Slide</option>
                        <option value="slide" <?php selected($s['submenu_animation'],'slide');?>>Slide accordion</option>
                        <option value="none"  <?php selected($s['submenu_animation'],'none');?>>None</option>
                    </select>
                </td>
            </tr>
            </tbody></table>
        </div>
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Sticky Menu</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr><td style="<?php echo esc_attr( $td );?>">Enable sticky</td><td style="<?php echo esc_attr( $tv );?>"><label><input type="checkbox" name="menux_style[sticky]" value="1" <?php checked($s['sticky'],'1');?> onchange="menux_liveStylePreview()"> Keep menu fixed at top on scroll</label></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Sticky background</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('sticky_bg',$s['sticky_bg']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Shadow sticky</td><td style="<?php echo esc_attr( $tv );?>"><label><input type="checkbox" name="menux_style[sticky_shadow]" value="1" <?php checked($s['sticky_shadow'],'1');?> onchange="menux_liveStylePreview()"> Enable</label></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Z-Index</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[sticky_z_index]" value="<?php echo esc_attr($s['sticky_z_index']);?>" min="100" max="99999" style="width:90px;" oninput="menux_liveStylePreview()"></td></tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Horizontal alignment</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[sticky_justify]" onchange="menux_liveStylePreview()">
                        <option value="flex-start"    <?php selected($s['sticky_justify'] ?? 'flex-start','flex-start');?>>⬅️ Left</option>
                        <option value="center"        <?php selected($s['sticky_justify'] ?? 'flex-start','center');?>>↔️ Center</option>
                        <option value="flex-end"      <?php selected($s['sticky_justify'] ?? 'flex-start','flex-end');?>>➡️ Right</option>
                        <option value="space-between" <?php selected($s['sticky_justify'] ?? 'flex-start','space-between');?>>↔️ Space-between</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Vertical alignment</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[sticky_align_items]" onchange="menux_liveStylePreview()">
                        <option value="flex-start" <?php selected($s['sticky_align_items'] ?? 'center','flex-start');?>>⬆️ Top</option>
                        <option value="center"     <?php selected($s['sticky_align_items'] ?? 'center','center');?>>⏺️ Center</option>
                        <option value="flex-end"   <?php selected($s['sticky_align_items'] ?? 'center','flex-end');?>>⬇️ Bottom</option>
                    </select>
                </td>
            </tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Horizontal padding (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[sticky_padding_x]" value="<?php echo esc_attr($s['sticky_padding_x'] ?? '');?>" min="0" max="200" style="width:80px;" placeholder="auto" oninput="menux_liveStylePreview()"></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Vertical padding (px)</td><td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[sticky_padding_y]" value="<?php echo esc_attr($s['sticky_padding_y'] ?? '');?>" min="0" max="100" style="width:80px;" placeholder="auto" oninput="menux_liveStylePreview()"></td></tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Transition duration (s)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[sticky_transition]" value="<?php echo esc_attr($s['sticky_transition'] ?? '0.3');?>" min="0" max="2" step="0.05" style="width:80px;" oninput="menux_liveStylePreview()">
                    <span style="font-size:11px;color:#6b7280;margin-left:6px;">Smooth background + shadow fade when menu becomes sticky</span>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Shrink on sticky</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <label style="display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="menux_style[sticky_shrink]" value="1" <?php checked($s['sticky_shrink'] ?? '0','1');?> onchange="menux_liveStylePreview()">
                        Reduce height, font size and logo when sticky
                    </label>
                </td>
            </tr>
            </tbody></table>

            <!-- Progress bar -->
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 10px;">📊 Scroll Progress Bar</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Enable</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <label style="display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="menux_style[progress_bar_enabled]" value="1" <?php checked($s['progress_bar_enabled'] ?? '0','1');?> onchange="menux_liveStylePreview()">
                        Show page reading progress bar on the menu
                    </label>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Color</td>
                <td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('progress_bar_color', $s['progress_bar_color'] ?? '#667eea');?></td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Height (px)</td>
                <td style="<?php echo esc_attr( $tv );?>"><input type="number" name="menux_style[progress_bar_height]" value="<?php echo esc_attr($s['progress_bar_height'] ?? '3');?>" min="1" max="10" style="width:70px;" oninput="menux_liveStylePreview()"></td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Position</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[progress_bar_position]" onchange="menux_liveStylePreview()">
                        <option value="bottom" <?php selected($s['progress_bar_position'] ?? 'bottom','bottom');?>>Bottom of menu</option>
                        <option value="top"    <?php selected($s['progress_bar_position'] ?? 'bottom','top');?>>Top of menu</option>
                    </select>
                </td>
            </tr>
            </tbody></table>
        </div>
        </div>

        <!-- ── Entrance Animation ── -->
        <div style="margin-top:24px;border-top:1px solid #f1f3f5;padding-top:20px;">
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">✨ Entrance Animation</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;margin-bottom:14px;" id="bm-entrance-grid">
            <?php
            $entrance_opts = array(
                'none'       => array('label'=>'None',        'icon'=>'⛔'),
                'fadeIn'     => array('label'=>'Fade in',     'icon'=>'🌅'),
                'slideDown'  => array('label'=>'Slide down',  'icon'=>'⬇️'),
                'slideUp'    => array('label'=>'Slide up',    'icon'=>'⬆️'),
                'slideLeft'  => array('label'=>'Slide left',  'icon'=>'⬅️'),
                'slideRight' => array('label'=>'Slide right', 'icon'=>'➡️'),
                'zoomIn'     => array('label'=>'Zoom in',     'icon'=>'🔍'),
                'flipX'      => array('label'=>'Flip X',      'icon'=>'🔄'),
            );
            $cur_entrance = $s['entrance_animation'] ?? 'none';
            foreach ($entrance_opts as $val => $opt): ?>
                <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:10px 8px;border:2px solid <?php echo esc_attr( $cur_entrance===$val ? '#667eea' : '#e5e7eb' ); ?>;border-radius:10px;cursor:pointer;background:<?php echo esc_attr( $cur_entrance===$val ? '#eef2ff' : '#fff' ); ?>;transition:all .15s;font-size:12px;text-align:center;" class="bm-entrance-card">
                    <input type="radio" name="menux_style[entrance_animation]" value="<?php echo esc_attr($val); ?>" <?php checked($cur_entrance, $val); ?> style="display:none;" onchange="menux_updateEntranceCard(this); menux_liveStylePreview();">
                    <span style="font-size:20px;"><?php echo esc_html( $opt['icon'] ); ?></span>
                    <span style="font-weight:500;color:#374151;"><?php echo esc_html($opt['label']); ?></span>
                </label>
            <?php endforeach; ?>
            </div>
            <table style="border-collapse:collapse;width:100%;max-width:420px;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Duration (s)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[entrance_duration]" value="<?php echo esc_attr($s['entrance_duration'] ?? '0.5'); ?>" min="0.1" max="3" step="0.1" style="width:80px;" oninput="menux_liveStylePreview()">
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Delay (s)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[entrance_delay]" value="<?php echo esc_attr($s['entrance_delay'] ?? '0'); ?>" min="0" max="5" step="0.1" style="width:80px;" oninput="menux_liveStylePreview()">
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Stagger items (s)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[entrance_stagger]" value="<?php echo esc_attr($s['entrance_stagger'] ?? '0'); ?>" min="0" max="1" step="0.05" style="width:80px;" oninput="menux_liveStylePreview()">
                    <span style="font-size:11px;color:#6b7280;margin-left:6px;">Each item animates with this extra delay</span>
                </td>
            </tr>
            </tbody></table>

            <button type="button" onclick="menux_previewEntrance()" style="margin-top:10px;padding:6px 16px;font-size:12px;background:#eef2ff;border:1px solid #c7d2fe;color:#4f46e5;border-radius:6px;cursor:pointer;font-weight:500;">
                ▶ Preview animation
            </button>
        </div>

        </div><!-- end tab layout -->

        <!-- ===== TAB: MOBILE ===== -->
        <div id="bm-tab-mobile" class="bm-tab-pane" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

        <!-- Colonna sinistra: Hamburger -->
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">🍔 Hamburger</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Icon style</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[hamburger_style]" onchange="menux_liveStylePreview()">
                        <option value="classic" <?php selected($s['hamburger_style'],'classic');?>>Classic (3 lines)</option>
                        <option value="modern"  <?php selected($s['hamburger_style'],'modern');?>>Modern (rounded)</option>
                        <option value="minimal" <?php selected($s['hamburger_style'],'minimal');?>>Minimal (thin)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Alignment</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <select name="menux_style[hamburger_align]" onchange="menux_liveStylePreview()">
                        <option value="flex-start" <?php selected($s['hamburger_align'],'flex-start');?>>Left</option>
                        <option value="center"     <?php selected($s['hamburger_align'],'center');?>>Center</option>
                        <option value="flex-end"   <?php selected($s['hamburger_align'],'flex-end');?>>Right</option>
                    </select>
                </td>
            </tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Icon color</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('hamburger_color',$s['hamburger_color']);?></td></tr>
            <tr><td style="<?php echo esc_attr( $td );?>">Button background</td><td style="<?php echo esc_attr( $tv );?>"><?php menux_color_field('hamburger_bg',$s['hamburger_bg']);?></td></tr>
            </tbody></table>

            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin:18px 0 10px;">📐 When it appears</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Breakpoint mode</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:2px;">
                        <label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:2px solid <?php echo esc_attr( ($s['mobile_breakpoint_mode']??'manual')==='auto'?'#667eea':'#e5e7eb' ); ?>;border-radius:8px;cursor:pointer;background:<?php echo esc_attr( ($s['mobile_breakpoint_mode']??'manual')==='auto'?'#f0f4ff':'#fff' ); ?>;">
                            <input type="radio" name="menux_style[mobile_breakpoint_mode]" value="auto" <?php checked($s['mobile_breakpoint_mode']??'manual','auto'); ?> onchange="menux_liveStylePreview();bmToggleBreakpointMode(this.value)" style="margin-top:3px;flex-shrink:0;">
                            <div>
                                <strong style="font-size:13px;color:#111827;">✨ Automatic</strong>
                                <p style="font-size:11px;color:#6b7280;margin:2px 0 0;">The hamburger appears when items no longer fit in the row. No px to choose.</p>
                            </div>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:2px solid <?php echo esc_attr( ($s['mobile_breakpoint_mode']??'manual')==='manual'?'#667eea':'#e5e7eb' ); ?>;border-radius:8px;cursor:pointer;background:<?php echo esc_attr( ($s['mobile_breakpoint_mode']??'manual')==='manual'?'#f0f4ff':'#fff' ); ?>;">
                            <input type="radio" name="menux_style[mobile_breakpoint_mode]" value="manual" <?php checked($s['mobile_breakpoint_mode']??'manual','manual'); ?> onchange="menux_liveStylePreview();bmToggleBreakpointMode(this.value)" style="margin-top:3px;flex-shrink:0;">
                            <div>
                                <strong style="font-size:13px;color:#111827;">🎚️ Manual</strong>
                                <p style="font-size:11px;color:#6b7280;margin:2px 0 0;">Specify the width (px) below which the hamburger appears.</p>
                            </div>
                        </label>
                    </div>
                </td>
            </tr>
            <tr id="bm-bp-manual-row" style="<?php echo esc_attr( ($s['mobile_breakpoint_mode']??'manual')==='auto'?'display:none':'' ); ?>">
                <td style="<?php echo esc_attr( $td );?>">Breakpoint (px)</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <input type="number" name="menux_style[mobile_breakpoint]" value="<?php echo esc_attr($s['mobile_breakpoint']);?>" min="320" max="2560" style="width:90px;" onchange="menux_liveStylePreview()">
                    <p class="description" style="font-size:11px;margin:3px 0 0;">Below this px → hamburger. Typical: 768 or 1024.</p>
                </td>
            </tr>
            </tbody></table>
        </div>

        <!-- Colonna destra: Opening mode -->
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">📱 Opening mode</div>

            <?php
            $open_styles = array(
                'dropdown'     => array('icon'=>'⬇️',  'label'=>'Dropdown',      'desc'=>'Classic dropdown below the menu'),
                'fullscreen'   => array('icon'=>'⛶',   'label'=>'Fullscreen',     'desc'=>'100% screen overlay with large centered items'),
                'drawer-left'  => array('icon'=>'◀️',  'label'=>'Drawer Left','desc'=>'Side panel that slides from the left'),
                'drawer-right' => array('icon'=>'▶️',  'label'=>'Drawer Right',  'desc'=>'Side panel that slides from the right'),
            );
            $cur_open = $s['mobile_open_style'] ?? 'dropdown';
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
            <?php foreach ($open_styles as $val => $info): ?>
                <label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:2px solid <?php echo esc_attr( $cur_open===$val?'#667eea':'#e5e7eb' ); ?>;border-radius:8px;cursor:pointer;background:<?php echo esc_attr( $cur_open===$val?'#f0f4ff':'#fff' ); ?>;" onclick="this.querySelector('input').click()">
                    <input type="radio" name="menux_style[mobile_open_style]" value="<?php echo esc_attr( $val ); ?>" <?php checked($cur_open,$val); ?> onchange="menux_liveStylePreview();bmToggleOpenStyle(this.value)" style="margin-top:3px;flex-shrink:0;">
                    <div>
                        <strong style="font-size:13px;color:#111827;"><?php echo esc_html( $info['icon'] ) . ' ' . esc_html( $info['label'] ); ?></strong>
                        <p style="font-size:11px;color:#6b7280;margin:2px 0 0;"><?php echo esc_html( $info['desc'] ); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
            </div>

            <!-- Mode-specific options -->
            <div style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;background:#fafbfc;">

                <!-- Comuni a fullscreen + drawer -->
                <div id="bm-mob-overlay-opts" style="<?php echo esc_attr( $cur_open==='dropdown'?'display:none':'' ); ?>">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Background overlay</div>
                    <table style="border-collapse:collapse;width:100%;margin-bottom:10px;"><tbody>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;width:130px;">Color</td><td style="padding:4px 0;"><?php menux_color_field('mobile_overlay_bg',$s['mobile_overlay_bg']??'#000000');?></td></tr>
                    <tr>
                        <td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Opacity</td>
                        <td style="padding:4px 0;">
                            <input type="range" name="menux_style[mobile_overlay_opacity]" min="0" max="1" step="0.05"
                                value="<?php echo esc_attr($s['mobile_overlay_opacity']??'0.5'); ?>"
                                oninput="this.nextElementSibling.textContent=Math.round(this.value*100)+'%';menux_liveStylePreview()"
                                style="width:120px;vertical-align:middle;">
                            <span style="font-size:12px;color:#6b7280;margin-left:6px;"><?php echo absint( round( ($s['mobile_overlay_opacity']??0.5)*100 ) ); ?>%</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Background blur (px)</td>
                        <td style="padding:4px 0;"><input type="number" name="menux_style[mobile_overlay_blur]" value="<?php echo esc_attr($s['mobile_overlay_blur']??'0'); ?>" min="0" max="20" style="width:60px;" oninput="menux_liveStylePreview()"><span style="font-size:11px;color:#9ca3af;margin-left:6px;">0 = no blur</span></td>
                    </tr>
                    </tbody></table>
                </div>

                <!-- Solo fullscreen -->
                <div id="bm-mob-fullscreen-opts" style="<?php echo esc_attr( $cur_open!=='fullscreen'?'display:none':'' ); ?>">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Fullscreen</div>
                    <table style="border-collapse:collapse;width:100%;"><tbody>
                    <tr>
                        <td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;width:130px;">Items alignment</td>
                        <td style="padding:4px 0;">
                            <select name="menux_style[mobile_fullscreen_align]" onchange="menux_liveStylePreview()">
                                <option value="center"     <?php selected($s['mobile_fullscreen_align']??'center','center'); ?>>⏺️ Center</option>
                                <option value="flex-start" <?php selected($s['mobile_fullscreen_align']??'center','flex-start'); ?>>⬅️ Left</option>
                                <option value="flex-end"   <?php selected($s['mobile_fullscreen_align']??'center','flex-end'); ?>>➡️ Right</option>
                            </select>
                        </td>
                    </tr>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Dropdown background</td><td style="padding:4px 0;"><?php menux_color_field('mobile_menu_bg',$s['mobile_menu_bg']);?></td></tr>
                    </tbody></table>
                </div>

                <!-- Solo drawer -->
                <div id="bm-mob-drawer-opts" style="<?php echo esc_attr( !in_array($cur_open,array('drawer-left','drawer-right'),true)?'display:none':'' ); ?>">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Drawer</div>
                    <table style="border-collapse:collapse;width:100%;"><tbody>
                    <tr>
                        <td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;width:130px;">Width (px)</td>
                        <td style="padding:4px 0;"><input type="number" name="menux_style[mobile_drawer_width]" value="<?php echo esc_attr($s['mobile_drawer_width']??'280'); ?>" min="200" max="600" style="width:80px;" oninput="menux_liveStylePreview()"></td>
                    </tr>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Drawer background</td><td style="padding:4px 0;"><?php menux_color_field('mobile_menu_bg',$s['mobile_menu_bg']);?></td></tr>
                    </tbody></table>
                </div>

                <!-- Dropdown opzioni -->
                <div id="bm-mob-dropdown-opts" style="<?php echo esc_attr( $cur_open!=='dropdown'?'display:none':'' ); ?>">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Dropdown</div>
                    <table style="border-collapse:collapse;width:100%;"><tbody>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;width:130px;">Dropdown background</td><td style="padding:4px 0;"><?php menux_color_field('mobile_menu_bg',$s['mobile_menu_bg']);?></td></tr>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Padding (px)</td><td style="padding:4px 0;"><input type="number" name="menux_style[mobile_menu_pad]" value="<?php echo esc_attr($s['mobile_menu_pad']);?>" min="0" style="width:80px;" onchange="menux_liveStylePreview()"></td></tr>
                    <tr><td style="font-size:12px;color:#374151;padding:4px 12px 4px 0;">Shadow</td><td style="padding:4px 0;"><label><input type="checkbox" name="menux_style[mobile_menu_shadow]" value="1" <?php checked($s['mobile_menu_shadow'],'1');?> onchange="menux_liveStylePreview()"> Enable</label></td></tr>
                    </tbody></table>
                </div>

                <!-- Open animation (all modes) -->
                <div style="border-top:1px solid #f1f5f9;margin-top:12px;padding-top:12px;">
                    <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Open animation</div>
                    <select name="menux_style[mobile_open_animation]" onchange="menux_liveStylePreview()" style="font-size:12px;">
                        <option value="fade"  <?php selected($s['mobile_open_animation']??'fade','fade'); ?>>✨ Fade</option>
                        <option value="slide" <?php selected($s['mobile_open_animation']??'fade','slide'); ?>>⬇️ Slide</option>
                        <option value="scale" <?php selected($s['mobile_open_animation']??'fade','scale'); ?>>🔍 Scale</option>
                    </select>
                </div>
            </div>
        </div>
        </div>

        <script>
        function bmToggleBreakpointMode(val) {
            var row = document.getElementById('bm-bp-manual-row');
            if (row) row.style.display = val === 'manual' ? '' : 'none';
            // Update radio card styles
            document.querySelectorAll('[name="menux_style[mobile_breakpoint_mode]"]').forEach(function(r) {
                var lbl = r.closest('label');
                if (!lbl) return;
                lbl.style.borderColor = r.checked ? '#667eea' : '#e5e7eb';
                lbl.style.background  = r.checked ? '#f0f4ff' : '#fff';
            });
        }
        function bmToggleOpenStyle(val) {
            document.getElementById('bm-mob-overlay-opts').style.display   = val !== 'dropdown' ? '' : 'none';
            document.getElementById('bm-mob-fullscreen-opts').style.display = val === 'fullscreen' ? '' : 'none';
            document.getElementById('bm-mob-drawer-opts').style.display     = (val === 'drawer-left' || val === 'drawer-right') ? '' : 'none';
            document.getElementById('bm-mob-dropdown-opts').style.display   = val === 'dropdown' ? '' : 'none';
            // Update radio card styles
            document.querySelectorAll('[name="menux_style[mobile_open_style]"]').forEach(function(r) {
                var lbl = r.closest('label');
                if (!lbl) return;
                lbl.style.borderColor = r.checked ? '#667eea' : '#e5e7eb';
                lbl.style.background  = r.checked ? '#f0f4ff' : '#fff';
            });
        }
        </script>

        </div><!-- end tab mobile -->

        <!-- ===== TAB: LOGO ===== -->
        <!-- ===== TAB: AVANZATO (CSS custom) ===== -->
        <div id="bm-tab-css" class="bm-tab-pane" style="display:none;">
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <div style="flex:1;min-width:300px;display:flex;flex-direction:column;">
            <label style="font-weight:600;font-size:13px;margin-bottom:6px;">
                Custom CSS
                <span style="font-weight:normal;color:#666;display:block;font-size:11px;margin-top:2px;">Add your custom CSS rules here.</span>
            </label>
            <textarea name="menux_style[custom_css]" rows="20" style="width:100%;font-family:'Consolas','Monaco',monospace;font-size:12px;line-height:1.5;resize:vertical;background:#1e1e1e;color:#d4d4d4;border:1px solid #333;border-radius:4px;padding:10px;" oninput="menux_liveStylePreview()"><?php echo esc_textarea(wp_unslash($s['custom_css']));?></textarea>
        </div>
        <div style="flex:0 0 260px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:8px;">⚡ Quick snippets</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php
                    $snippets = array(
                        'Centered items'        => '.menux-list{justify-content:center;}',
                        'Logo auto-push'        => '.menux-logo{margin-right:auto;}',
                        'Push last right'  => '.menux-list>li:last-child{margin-left:auto;}',
                        'Red logout'          => ".menux-list>li:last-child>a{color:#dc2626!important;}\n.menux-list>li:last-child>a:hover{color:#fca5a5!important;}",
                        'Vertical dividers'    => '.menux-list li{border-right:1px solid rgba(0,0,0,.1);}',
                        'Links with border-radius'=> '.menux-list li a.menux-link{border-radius:6px;}',
                        'Active bold'      => '.menux-list li a.menux-link.active{font-weight:600!important;}',
                    );
                    foreach ($snippets as $label => $code):
                    ?>
                    <button type="button" class="button button-small" style="font-size:11px;text-align:left;"
                        onclick="menux_appendCSS(<?php echo json_encode($code);?>)"><?php echo esc_html($label);?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px;">
                <div style="font-size:12px;font-weight:700;color:#856404;margin-bottom:6px;">📋 Your current CSS</div>
                <p style="font-size:11px;color:#6b7280;margin:0;">Paste your existing CSS here to maintain and manage it from this panel without modifying the theme.</p>
            </div>
        </div>
        </div>
        </div><!-- end tab css -->

        <!-- ===== TAB: DARK MODE ===== -->
        <div id="bm-tab-darkmode" class="bm-tab-pane" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Color Mode</div>
            <table style="border-collapse:collapse;width:100%;"><tbody>
            <tr>
                <td style="<?php echo esc_attr( $td );?>">Color theme</td>
                <td style="<?php echo esc_attr( $tv );?>">
                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
                        <?php
                        $dm_options = array(
                            'light' => array('icon'=>'☀️', 'label'=>'Light',  'desc'=>'Sets <code>data-bs-theme="light"</code> on the nav. Bootstrap and compatible CSS use the light scheme.'),
                            'dark'  => array('icon'=>'🌙', 'label'=>'Dark',    'desc'=>'Sets <code>data-bs-theme="dark"</code> on the nav. Useful for menus on dark backgrounds or night-mode layouts.'),
                            'auto'  => array('icon'=>'⚙️', 'label'=>'Auto (system)',  'desc'=>'No attribute: the menu adapts the color scheme based on the user\'s OS preference (<code>prefers-color-scheme</code>).'),
                        );
                        $curr_dm = $s['dark_mode'] ?? 'light';
                        foreach ($dm_options as $val => $opt):
                        ?>
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:2px solid <?php echo esc_attr( $curr_dm === $val ? '#2271b1' : '#e5e7eb' ); ?>;border-radius:8px;cursor:pointer;background:<?php echo esc_attr( $curr_dm === $val ? '#f0f6fc' : '#fff' ); ?>;">
                            <input type="radio" name="menux_style[dark_mode]" value="<?php echo esc_attr( $val );?>" <?php checked($curr_dm,$val);?> style="margin-top:2px;" onchange="this.closest('.bm-tab-pane').querySelectorAll('label').forEach(function(l){l.style.borderColor='#e5e7eb';l.style.background='#fff';}); this.closest('label').style.borderColor='#2271b1'; this.closest('label').style.background='#f0f6fc';">
                            <span>
                                <span style="font-size:16px;"><?php echo esc_html( $opt['icon'] );?></span>
                                <strong style="font-size:13px;margin-left:4px;"><?php echo esc_html( $opt['label'] );?></strong>
                                <p style="margin:4px 0 0;font-size:11px;color:#6b7280;"><?php echo wp_kses_post( $opt['desc'] );?></p>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            </tbody></table>
        </div>
        <div>
            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:16px;margin-bottom:16px;">
                <div style="font-size:13px;font-weight:700;color:#92400e;margin-bottom:8px;">💡 How it integrates with your theme</div>
                <p style="font-size:12px;color:#78350f;margin:0 0 8px;">This option adds the <code>data-bs-theme</code> attribute directly to the menu's <code>&lt;nav&gt;</code> tag.</p>
                <p style="font-size:12px;color:#78350f;margin:0 0 8px;"><strong>Bootstrap 5.3+:</strong> riconosce automaticamente l'attributo e applica variabili CSS per tema chiaro/scuro.</p>
                <p style="font-size:12px;color:#78350f;margin:0;">For a full dark theme, make sure you also configure the background and text colors in the <em>Colors</em> tab.</p>
            </div>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;">
                <div style="font-size:13px;font-weight:700;color:#166534;margin-bottom:8px;">✅ Attributo HTML generato</div>
                <?php $curr_dm = $s['dark_mode'] ?? 'light'; ?>
                <code style="display:block;padding:10px;background:#1e1e1e;color:#9cdcfe;border-radius:6px;font-size:12px;line-height:1.5;">
                    &lt;nav <?php
                    if ($curr_dm === 'dark') echo esc_html( 'data-bs-theme="dark"' );
                    elseif ($curr_dm === 'light') echo esc_html( 'data-bs-theme="light"' );
                    else echo '<em style="color:#ce9178;">// no attribute (auto)</em>';
                    ?>&gt;
                </code>
            </div>
        </div>
        </div>
        </div><!-- end tab darkmode -->

        </div><!-- end bm-card-body bm-tabs-body -->
    </div><!-- end bm-card menux-style-panel -->

    <script>
    function menux_switchTab(activeId) {
        document.querySelectorAll('.bm-tab-pane').forEach(function(p){ p.style.display='none'; });
        document.querySelectorAll('.bm-tab-btn').forEach(function(b){
            var isActive = b.getAttribute('data-tab') === activeId;
            // Pulisce eventuali stili inline residui e usa la classe .active (vedi CSS)
            b.style.color         = '';
            b.style.borderColor   = '';
            b.style.background    = '';
            if (isActive) b.classList.add('active');
            else b.classList.remove('active');
        });
        var pane = document.getElementById(activeId);
        if (pane) pane.style.display = 'block';
    }
    // Inizializza la prima tab
    document.addEventListener('DOMContentLoaded', function() { menux_switchTab('bm-tab-colors'); });

    function menux_appendCSS(snippet) {
        var ta = document.querySelector('[name="menux_style[custom_css]"]');
        if (ta) { ta.value = (ta.value.trim() ? ta.value.trim() + '\n' : '') + snippet; menux_liveStylePreview(); }
    }
    function menux_openLogoUploader() {
        if (typeof wp === 'undefined' || !wp.media) { alert('Media uploader non disponibile.'); return; }
        var frame = wp.media({ title:'Choose a Logo', button:{text:'Use this image'}, multiple:false });
        frame.on('select', function() {
            var att = frame.state().get('selection').first().toJSON();
            var inp = document.getElementById('bm-logo-url-input');
            if (inp) { inp.value = att.url; menux_liveStylePreview(); }
        });
        frame.open();
    }

    // ================================================================
    // ICON PICKER
    // ================================================================
    var mxIconPicker = (function() {
        var currentInput = null;
        var searchTimeout = null;

        // Lista icone Font Awesome 6 Free — raggruppate per categoria
        var iconGroups = {
            '🏠 Navigazione': ['fa-solid fa-house','fa-solid fa-house-chimney','fa-solid fa-bars','fa-solid fa-xmark','fa-solid fa-arrow-left','fa-solid fa-arrow-right','fa-solid fa-arrow-up','fa-solid fa-arrow-down','fa-solid fa-chevron-left','fa-solid fa-chevron-right','fa-solid fa-chevron-up','fa-solid fa-chevron-down','fa-solid fa-angles-left','fa-solid fa-angles-right','fa-solid fa-circle-arrow-left','fa-solid fa-circle-arrow-right','fa-solid fa-link','fa-solid fa-external-link','fa-solid fa-up-right-from-square','fa-solid fa-sitemap','fa-solid fa-map'],
            '👤 Utente': ['fa-solid fa-user','fa-solid fa-user-tie','fa-regular fa-user','fa-solid fa-users','fa-solid fa-user-group','fa-solid fa-user-check','fa-solid fa-user-plus','fa-solid fa-user-minus','fa-solid fa-user-xmark','fa-solid fa-address-card','fa-regular fa-address-card','fa-solid fa-id-card','fa-solid fa-person','fa-solid fa-right-to-bracket','fa-solid fa-right-from-bracket','fa-solid fa-lock','fa-solid fa-unlock','fa-solid fa-key','fa-solid fa-shield-halved','fa-solid fa-circle-user'],
            '📞 Comunicazione': ['fa-solid fa-phone','fa-solid fa-envelope','fa-regular fa-envelope','fa-solid fa-comments','fa-regular fa-comments','fa-solid fa-comment','fa-regular fa-comment','fa-solid fa-bell','fa-regular fa-bell','fa-solid fa-at','fa-solid fa-paper-plane','fa-regular fa-paper-plane','fa-solid fa-inbox','fa-solid fa-share-nodes','fa-solid fa-rss','fa-solid fa-headset','fa-solid fa-mobile-screen','fa-solid fa-mobile'],
            '💼 Business': ['fa-solid fa-briefcase','fa-solid fa-building','fa-regular fa-building','fa-solid fa-chart-bar','fa-regular fa-chart-bar','fa-solid fa-chart-line','fa-solid fa-chart-pie','fa-solid fa-handshake','fa-regular fa-handshake','fa-solid fa-suitcase','fa-solid fa-file-contract','fa-solid fa-clipboard-list','fa-solid fa-calendar-days','fa-regular fa-calendar-days','fa-solid fa-clock','fa-regular fa-clock','fa-solid fa-dollar-sign','fa-solid fa-euro-sign','fa-solid fa-receipt','fa-solid fa-cash-register'],
            '🛒 E-commerce': ['fa-solid fa-cart-shopping','fa-solid fa-cart-plus','fa-solid fa-bag-shopping','fa-solid fa-store','fa-solid fa-shop','fa-solid fa-tag','fa-solid fa-tags','fa-solid fa-gift','fa-solid fa-percent','fa-solid fa-ticket','fa-solid fa-credit-card','fa-regular fa-credit-card','fa-solid fa-wallet','fa-solid fa-basket-shopping','fa-solid fa-box','fa-solid fa-boxes-stacked','fa-solid fa-truck','fa-solid fa-truck-fast','fa-solid fa-heart','fa-regular fa-heart'],
            '⚙️ Settings': ['fa-solid fa-gear','fa-solid fa-gears','fa-solid fa-wrench','fa-solid fa-screwdriver-wrench','fa-solid fa-sliders','fa-solid fa-toggle-on','fa-solid fa-toggle-off','fa-solid fa-power-off','fa-solid fa-circle-info','fa-solid fa-question','fa-solid fa-circle-question','fa-regular fa-circle-question','fa-solid fa-triangle-exclamation','fa-solid fa-circle-exclamation','fa-solid fa-ban','fa-solid fa-trash','fa-regular fa-trash-can','fa-solid fa-pen','fa-regular fa-pen-to-square','fa-solid fa-magnifying-glass'],
            '📄 Contenuto': ['fa-solid fa-file','fa-regular fa-file','fa-solid fa-file-lines','fa-regular fa-file-lines','fa-solid fa-book','fa-solid fa-book-open','fa-solid fa-newspaper','fa-regular fa-newspaper','fa-solid fa-image','fa-regular fa-image','fa-solid fa-images','fa-solid fa-camera','fa-solid fa-video','fa-solid fa-film','fa-solid fa-music','fa-solid fa-podcast','fa-solid fa-microphone','fa-solid fa-star','fa-regular fa-star','fa-solid fa-bookmark'],
            '🌐 Social': ['fa-brands fa-facebook','fa-brands fa-facebook-f','fa-brands fa-twitter','fa-brands fa-x-twitter','fa-brands fa-instagram','fa-brands fa-linkedin','fa-brands fa-linkedin-in','fa-brands fa-youtube','fa-brands fa-tiktok','fa-brands fa-whatsapp','fa-brands fa-telegram','fa-brands fa-github','fa-brands fa-gitlab','fa-brands fa-google','fa-brands fa-apple','fa-brands fa-android','fa-brands fa-wordpress','fa-brands fa-slack','fa-brands fa-discord','fa-brands fa-spotify'],
            '🎓 Informazione': ['fa-solid fa-graduation-cap','fa-solid fa-school','fa-solid fa-chalkboard-user','fa-solid fa-lightbulb','fa-regular fa-lightbulb','fa-solid fa-brain','fa-solid fa-flask','fa-solid fa-microscope','fa-solid fa-atom','fa-solid fa-globe','fa-solid fa-earth-europe','fa-solid fa-language','fa-solid fa-list','fa-solid fa-list-check','fa-solid fa-table-list','fa-solid fa-table','fa-solid fa-database','fa-solid fa-server','fa-solid fa-code','fa-solid fa-terminal'],
        };

        function buildModal() {
            var existing = document.getElementById('bm-icon-picker-modal');
            if (existing) return;

            var overlay = document.createElement('div');
            overlay.id = 'bm-icon-picker-modal';
            overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999998;align-items:center;justify-content:center;padding:16px;';

            var html = '<div style="background:#fff;border-radius:14px;box-shadow:0 24px 60px rgba(0,0,0,.3);width:min(760px,96vw);max-height:85vh;display:flex;flex-direction:column;overflow:hidden;font-family:-apple-system,sans-serif;">'
                // Header
                + '<div style="background:linear-gradient(135deg,#0f172a,#1e293b);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">'
                + '<div><div style="color:rgba(255,255,255,.6);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Font Awesome 6 Free</div>'
                + '<div style="color:#fff;font-size:16px;font-weight:700;">🎨 Select Icon</div></div>'
                + '<button onclick="mxIconPicker.close()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">&times;</button>'
                + '</div>'
                // Search bar
                + '<div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;flex-shrink:0;display:flex;gap:8px;align-items:center;">'
                + '<div style="flex:1;position:relative;"><span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;">🔍</span>'
                + '<input type="text" id="bm-icon-search" placeholder="Search icon… (e.g. home, user, cart)" oninput="mxIconPicker.search(this.value)" style="width:100%;padding:8px 10px 8px 32px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;box-sizing:border-box;">'
                + '</div>'
                + '<button type="button" onclick="mxIconPicker.clear()" style="background:#fff;border:1px solid #d1d5db;color:#555;padding:7px 14px;border-radius:6px;font-size:12px;cursor:pointer;white-space:nowrap;">Remove icon</button>'
                + '</div>'
                // Body
                + '<div id="bm-icon-body" style="overflow-y:auto;flex:1;padding:16px;"></div>'
                // Footer
                + '<div style="padding:10px 16px;border-top:1px solid #f0f0f0;background:#fafafa;display:flex;align-items:center;gap:12px;flex-shrink:0;border-radius:0 0 14px 14px;">'
                + '<div id="bm-icon-preview" style="display:flex;align-items:center;gap:8px;font-size:13px;color:#6b7280;">None icona selezionata</div>'
                + '<div style="margin-left:auto;display:flex;gap:8px;">'
                + '<button onclick="mxIconPicker.close()" style="background:#fff;border:1px solid #d1d5db;color:#374151;padding:7px 18px;border-radius:7px;font-size:13px;cursor:pointer;">Cancel</button>'
                + '<button id="bm-icon-apply-btn" onclick="mxIconPicker.apply()" disabled style="background:linear-gradient(135deg,#0f172a,#334155);border:none;color:#fff;padding:7px 20px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;opacity:.4;">✅ Use this icon</button>'
                + '</div></div>'
                + '</div>';

            overlay.innerHTML = html;
            document.body.appendChild(overlay);
            overlay.addEventListener('click', function(e){ if(e.target===overlay) mxIconPicker.close(); });

            mxIconPicker.renderAll();
        }

        function renderGroup(title, icons, container) {
            var div = document.createElement('div');
            div.style.cssText = 'margin-bottom:18px;';
            div.innerHTML = '<div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid #e5e7eb;">'+title+'</div>';
            var grid = document.createElement('div');
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(52px,1fr));gap:4px;';
            icons.forEach(function(ic) {
                var parts = ic.split(' ');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.title = ic;
                btn.setAttribute('data-icon', ic);
                btn.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 4px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:3px;transition:all .1s;min-height:52px;';
                btn.innerHTML = '<i class="'+ic+'" style="font-size:18px;color:#374151;"></i><span style="font-size:8px;color:#9ca3af;text-align:center;word-break:break-all;line-height:1.2;">'+parts[parts.length-1].replace('fa-','')+'</span>';
                btn.onmouseenter = function(){ this.style.background='#e0e7ff'; this.style.borderColor='#667eea'; };
                btn.onmouseleave = function(){ if(mxIconPicker._selected!==ic){ this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'; } };
                btn.onclick = function(){ mxIconPicker.select(ic); };
                grid.appendChild(btn);
            });
            div.appendChild(grid);
            container.appendChild(div);
        }

        return {
            _selected: null,
            _target: null,

            open: function(inputEl) {
                buildModal();
                this._target = inputEl;
                this._selected = inputEl ? inputEl.value.trim() : null;
                var overlay = document.getElementById('bm-icon-picker-modal');
                overlay.style.display = 'flex';
                // Pre-seleziona se c'è già un'icona
                if (this._selected) {
                    this.updatePreview(this._selected);
                    var btn = document.getElementById('bm-icon-apply-btn');
                    if (btn) { btn.disabled=false; btn.style.opacity='1'; }
                    // Evidenzia icona corrente
                    setTimeout(function(){
                        var cur = document.querySelector('[data-icon="'+mxIconPicker._selected+'"]');
                        if (cur) { cur.style.background='#e0e7ff'; cur.style.borderColor='#667eea'; cur.scrollIntoView({block:'center'}); }
                    }, 100);
                }
                var searchEl = document.getElementById('bm-icon-search');
                if (searchEl) { searchEl.value=''; searchEl.focus(); }
            },

            close: function() {
                var o = document.getElementById('bm-icon-picker-modal');
                if (o) o.style.display='none';
                this._selected = null;
            },

            apply: function() {
                if (!this._selected || !this._target) { this.close(); return; }
                this._target.value = this._selected;
                // Aggiorna preview icona accanto all'input
                var previewEl = this._target.nextElementSibling;
                if (previewEl && previewEl.classList.contains('bm-icon-preview-el')) {
                    previewEl.className = 'bm-icon-preview-el '+this._selected;
                } else {
                    var ic = document.createElement('i');
                    ic.className = 'bm-icon-preview-el '+this._selected;
                    ic.style.cssText = 'font-size:16px;color:#374151;margin-left:6px;';
                    this._target.parentNode.insertBefore(ic, this._target.nextSibling);
                }
                // Trigger change event
                var ev = new Event('input', {bubbles:true});
                this._target.dispatchEvent(ev);
                this.close();
                menux_updatePreview();
            },

            clear: function() {
                if (this._target) {
                    this._target.value = '';
                    var previewEl = this._target.nextElementSibling;
                    if (previewEl && previewEl.classList.contains('bm-icon-preview-el')) previewEl.remove();
                    var ev = new Event('input', {bubbles:true});
                    this._target.dispatchEvent(ev);
                }
                this.close();
                menux_updatePreview();
            },

            select: function(ic) {
                // Deseleziona precedente
                if (this._selected) {
                    var prev = document.querySelector('[data-icon="'+this._selected+'"]');
                    if (prev) { prev.style.background='#f9fafb'; prev.style.borderColor='#e5e7eb'; }
                }
                this._selected = ic;
                var cur = document.querySelector('[data-icon="'+ic+'"]');
                if (cur) { cur.style.background='#e0e7ff'; cur.style.borderColor='#667eea'; }
                this.updatePreview(ic);
                var btn = document.getElementById('bm-icon-apply-btn');
                if (btn) { btn.disabled=false; btn.style.opacity='1'; }
            },

            updatePreview: function(ic) {
                var pv = document.getElementById('bm-icon-preview');
                if (pv) pv.innerHTML = '<i class="'+ic+'" style="font-size:22px;color:#334155;"></i> <span style="font-size:12px;color:#374151;font-weight:600;">'+ic+'</span>';
            },

            renderAll: function() {
                var body = document.getElementById('bm-icon-body');
                if (!body) return;
                body.innerHTML = '';
                Object.keys(iconGroups).forEach(function(g){ renderGroup(g, iconGroups[g], body); });
            },

            search: function(q) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function(){
                    var body = document.getElementById('bm-icon-body');
                    if (!body) return;
                    if (!q.trim()) { mxIconPicker.renderAll(); return; }
                    body.innerHTML = '';
                    var all = [];
                    Object.values(iconGroups).forEach(function(arr){ all = all.concat(arr); });
                    var filtered = all.filter(function(ic){ return ic.indexOf(q.toLowerCase()) !== -1; });
                    if (!filtered.length) { body.innerHTML = '<p style="color:#9ca3af;text-align:center;padding:40px;font-size:13px;">None icona trovata per "'+q+'"</p>'; return; }
                    renderGroup('Results ('+filtered.length+')', filtered, body);
                }, 200);
            }
        };
    })();

    // Inizializza icone preview esistenti al caricamento
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input.bm-icon-input').forEach(function(inp) {
            if (inp.value.trim()) {
                var ic = document.createElement('i');
                ic.className = 'bm-icon-preview-el '+inp.value.trim();
                ic.style.cssText = 'font-size:16px;color:#374151;margin-left:6px;';
                inp.parentNode.insertBefore(ic, inp.nextSibling);
            }
        });
    });
    // ---- FINE ICON PICKER ----

    // ---- ENTRANCE ANIMATION CARD HIGHLIGHT ----
    function menux_updateEntranceCard(radio) {
        document.querySelectorAll('.bm-entrance-card').forEach(function(card) {
            var inp = card.querySelector('input[type="radio"]');
            var active = inp && inp.checked;
            card.style.border = active ? '2px solid #667eea' : '2px solid #e5e7eb';
            card.style.background = active ? '#eef2ff' : '#fff';
        });
    }

    function menux_previewEntrance() {
        var nav = document.getElementById('menux-preview-wrap');
        if (!nav) return;
        var anim = (document.querySelector('[name="menux_style[entrance_animation]"]:checked') || {}).value || 'none';
        var dur  = (document.querySelector('[name="menux_style[entrance_duration]"]') || {}).value || '0.5';
        var delay= (document.querySelector('[name="menux_style[entrance_delay]"]') || {}).value || '0';
        if (anim === 'none') return;
        nav.style.animation = 'none';
        nav.offsetHeight; // reflow
        nav.style.animation = 'bm-entrance-' + anim + ' ' + dur + 's ease both ' + delay + 's';
        // Stagger items
        var stagger = parseFloat((document.querySelector('[name="menux_style[entrance_stagger]"]') || {}).value || '0');
        if (stagger > 0) {
            nav.querySelectorAll('.menux-list > li').forEach(function(li, i) {
                li.style.animation = 'none';
                li.offsetHeight;
                li.style.animation = 'bm-entrance-' + anim + ' ' + dur + 's ease both ' + (parseFloat(delay) + i * stagger) + 's';
            });
        }
    }
    // ---- END ENTRANCE ANIMATION ----
    </script>
    <?php
}
