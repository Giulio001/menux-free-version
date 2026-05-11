<?php
/**
 * MenuX Free — Menu Builder
 * Drag-and-drop menu item builder for the admin panel.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_render_builder($items, $all_pages, $supported_langs = array(), $menux_wp_roles_for_select = array()) {
    $idx = 0;
    ?>
    <div class="menux-panel">
        <div class="bm-add-grid">

            <!-- Add Page -->
            <div class="bm-card bm-card-add">
                <div class="bm-card-header">
                    <span class="bm-card-icon bm-card-icon-blue">📄</span>
                    <div class="bm-card-titles">
                        <h2 class="bm-card-title">Add Page</h2>
                        <p class="bm-card-subtitle">Add an existing WordPress page</p>
                    </div>
                </div>
                <div class="bm-card-body">
                    <select id="page-select" class="bm-input bm-select" style="margin-bottom:10px;">
                        <option value="">-- -- Select a page -- --</option>
                        <?php foreach ($all_pages as $page) : ?>
                            <option value="<?php echo absint( $page->ID ); ?>" data-title="<?php echo esc_attr($page->post_title); ?>">
                                <?php echo esc_html($page->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="bm-add-lang-inputs" id="bm-page-lang-inputs">
                        <?php if (!empty($supported_langs)) : ?>
                            <?php foreach ($supported_langs as $lang) :
                                $key = menux_code_to_key($lang['code']); ?>
                            <input type="text"
                                   id="page-lang-<?php echo esc_attr($key); ?>"
                                   placeholder="<?php echo esc_attr($lang['code']); ?>"
                                   title="<?php echo esc_attr($lang['label']); ?>"
                                   class="bm-add-lang-input">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($supported_langs)) : ?>
                        <p class="bm-form-help" style="margin:0 0 10px;">Labels per language (uses page title if empty)</p>
                    <?php endif; ?>
                    <button type="button" class="bm-btn bm-btn-primary bm-btn-block" onclick="menux_addPage()">
                        <span style="font-size:14px;">+</span> Add to Menu
                    </button>
                </div>
            </div>

            <!-- Add Custom Link -->
            <div class="bm-card bm-card-add">
                <div class="bm-card-header">
                    <span class="bm-card-icon bm-card-icon-pink">🔗</span>
                    <div class="bm-card-titles">
                        <h2 class="bm-card-title">Add Custom Link</h2>
                        <p class="bm-card-subtitle">Custom link to external or internal URLs</p>
                    </div>
                </div>
                <div class="bm-card-body">
                    <input type="text" id="custom-url" class="bm-input" placeholder="URL (https://...)" style="margin-bottom:8px;">
                    <div class="bm-add-lang-inputs" id="bm-custom-lang-inputs">
                        <?php if (!empty($supported_langs)) : ?>
                            <?php foreach ($supported_langs as $lang) :
                                $key = menux_code_to_key($lang['code']); ?>
                            <input type="text"
                                   id="custom-lang-<?php echo esc_attr($key); ?>"
                                   placeholder="<?php echo esc_attr($lang['code']); ?>"
                                   title="<?php echo esc_attr($lang['label']); ?>"
                                   class="bm-add-lang-input">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="text" id="custom-icon" class="bm-input" placeholder="Icon (e.g. fas fa-home)" style="margin-bottom:8px;">
                    <?php if (!empty($supported_langs)) : ?>
                        <p class="bm-form-help" style="margin:0 0 10px;">Labels per language (placeholder = code)</p>
                    <?php endif; ?>
                    <button type="button" class="bm-btn bm-btn-primary bm-btn-block" onclick="menux_addCustom()">
                        <span style="font-size:14px;">+</span> Add Link
                    </button>
                </div>
            </div>
        </div>

        <div class="bm-card bm-card-structure">
            <div class="bm-card-header">
                <span class="bm-card-icon bm-card-icon-purple">🧩</span>
                <div class="bm-card-titles">
                    <h2 class="bm-card-title">Menu Structure</h2>
                    <p class="bm-card-subtitle">
                        <span class="bm-tip-inline">↕️ Drag to reorder</span>
                        <?php if (!empty($supported_langs)) : ?>
                            <span class="bm-tip-divider">·</span>
                            Languages:
                            <?php foreach ($supported_langs as $i => $l) : ?>
                                <code class="bm-lang-mini"><?php echo esc_html($l['code']); ?></code><?php if ($i < count($supported_langs)-1) echo ' '; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="bm-items-count"><?php echo count($items); ?> items</span>
            </div>
            <div class="bm-card-body">
            <ul id="menux-sortable" style="list-style:none; padding:0; margin:0; min-height:50px;">
                <?php foreach ($items as $item) :
                    $is_page   = ($item['type'] === 'page');
                    $title     = $is_page ? get_the_title($item['id']) : $item['url'];
                    $icon_val  = $item['icon'] ?? '';
                    $vis       = $item['visibility'] ?? 'all';
                    $badge_val = $item['badge'] ?? '';
                    $badge_bg  = $item['badge_bg'] ?? '#ef4444';
                    $target    = $item['target'] ?? '';
                    $item_key  = $item['item_key'] ?? ($is_page ? 'page_'.($item['id']??0) : 'c_'.md5($item['url']??''));
                    $has_subs  = !empty($item['children']);
                ?>
                <li class="menux-item<?php echo $has_subs ? esc_attr(' menux-item-has-subs') : ''; ?>">
                    <span class="dashicons dashicons-menu"></span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#3c434a;">
                            <?php echo $is_page ? '📄 ' . esc_html($title) : '🔗 ' . esc_url($title); ?>
                            <?php if ($has_subs): ?><span style="font-size:10px; background:#e0f2fe; color:#0369a1; padding:1px 5px; border-radius:8px; margin-left:4px;"><?php echo absint( count($item['children']) ); ?> children</span><?php endif; ?>
                        </div>
                        <div class="bm-inputs-row">
                            <input type="hidden" name="menu_items[<?php echo absint( $idx ); ?>][type]" value="<?php echo esc_attr($item['type']); ?>">
                            <input type="hidden" name="menu_items[<?php echo absint( $idx ); ?>][item_key]" value="<?php echo esc_attr( $item_key ); ?>">
                            <?php if ($is_page) : ?>
                                <input type="hidden" name="menu_items[<?php echo absint( $idx ); ?>][id]" value="<?php echo intval($item['id']); ?>">
                            <?php else : ?>
                                <input type="hidden" name="menu_items[<?php echo absint( $idx ); ?>][url]" value="<?php echo esc_url($item['url']); ?>">
                            <?php endif; ?>
                            <?php if (!empty($supported_langs)) : ?>
                                <?php foreach ($supported_langs as $lang) :
                                    $key   = menux_code_to_key($lang['code']);
                                    $short = strtolower(substr($lang['code'], 0, 2));
                                    $lv    = $item[$key] ?? ($item[$short] ?? ($is_page && $short === 'it' ? $title : ''));
                                ?>
                                <input type="text"
                                       name="menu_items[<?php echo absint( $idx ); ?>][<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr($lv); ?>"
                                       placeholder="<?php echo esc_attr($lang['code']); ?>"
                                       title="<?php echo esc_attr($lang['label']); ?>"
                                       class="bm-lang-input">
                                <?php endforeach; ?>
                            <?php else : ?>
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][lang_it_IT]"
                                       value="<?php echo esc_attr($item['lang_it_IT'] ?? $item['it'] ?? ($is_page ? $title : '')); ?>"
                                       placeholder="it-IT" class="bm-lang-input" title="Italiano">
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][lang_en_US]"
                                       value="<?php echo esc_attr($item['lang_en_US'] ?? $item['en'] ?? ''); ?>"
                                       placeholder="en-US" class="bm-lang-input" title="English">
                            <?php endif; ?>
                            <div style="display:inline-flex;align-items:center;gap:3px;">
                                <?php if (!empty($icon_val)): ?><i class="bm-icon-preview-el <?php echo esc_attr( $icon_val ); ?>" style="font-size:16px;color:#374151;"></i><?php endif; ?>
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][icon]" value="<?php echo esc_attr( $icon_val ); ?>" placeholder="icon" title="Icon class" class="bm-lang-input bm-icon-input" style="max-width:120px!important;">
                                <button type="button" title="Pick icon" onclick="mxIconPicker.open(this.previousElementSibling)" style="padding:2px 6px;font-size:13px;height:26px;cursor:pointer;background:#f9fafb;border:1px solid #d1d5db;border-radius:4px;" tabindex="-1">🎨</button>
                            </div>
                            <!-- Badge -->
                            <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][badge]" value="<?php echo esc_attr( $badge_val ); ?>" placeholder="🏷️ Badge" title="Es: New, Hot, Sale" class="bm-lang-input" style="max-width:64px!important;">
                            <input type="color" name="menu_items[<?php echo absint( $idx ); ?>][badge_bg]" value="<?php echo esc_attr( $badge_bg ); ?>" title="Badge background color" style="width:30px;height:26px;padding:1px;border:1px solid #ccc;border-radius:3px;cursor:pointer;">
                            <!-- Visibility (WP roles) -->
                            <select name="menu_items[<?php echo absint( $idx ); ?>][visibility]" class="menux-visibility">
                                <?php foreach ($menux_wp_roles_for_select as $role_key => $role_label) : ?>
                                <option value="<?php echo esc_attr($role_key); ?>" <?php selected($vis, $role_key); ?>>
                                    <?php echo esc_html($role_label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Target -->
                            <select name="menu_items[<?php echo absint( $idx ); ?>][target]" style="font-size:11px;height:26px;padding:2px 4px;min-width:75px;">
                                <option value=""       <?php selected($target, ''); ?>>Same tab</option>
                                <option value="_blank" <?php selected($target, '_blank'); ?>>New tab</option>
                            </select>
                            <!-- ──── FEATURE 2: Notification Dot ──── -->
                            <label title="Show notification dot" style="display:flex;align-items:center;gap:3px;font-size:11px;color:#555;cursor:pointer;white-space:nowrap;">
                                <input type="checkbox" name="menu_items[<?php echo absint( $idx ); ?>][notif_dot]" value="1" <?php checked($item['notif_dot'] ?? '0', '1'); ?>>
                                🔴
                            </label>
                            <!-- ──── FEATURE 11: Location ──── -->
                            <select name="menu_items[<?php echo absint( $idx ); ?>][menu_location]" style="font-size:11px;height:26px;padding:2px 4px;min-width:75px;" title="Menu location">
                                <?php $cur_loc = $item['menu_location'] ?? 'primary'; ?>
                                <option value="primary" <?php selected($cur_loc, 'primary'); ?>>📍 Primary</option>
                                <option value="footer" <?php selected($cur_loc, 'footer'); ?>>📍 Footer</option>
                                <option value="sidebar" <?php selected($cur_loc, 'sidebar'); ?>>📍 Sidebar</option>
                                <option value="mobile" <?php selected($cur_loc, 'mobile'); ?>>📍 Mobile</option>
                            </select>
                        </div>

                        <!-- ──── RIGA 2: Schedule + Condizionali avanzati ──── -->
                        <?php
                        $has_advanced = !empty($item['schedule_start']) || !empty($item['schedule_end']) || !empty($item['cond_roles']) || !empty($item['cond_devices']) || !empty($item['cond_pages']) || !empty($item['cond_time_from']);
                        ?>
                        <details style="margin-top:4px;<?php echo $has_advanced ? '' : ''; ?>" <?php echo $has_advanced ? 'open' : ''; ?>>
                            <summary style="font-size:10px; color:#6b7280; cursor:pointer; user-select:none;">
                                ⚙️ Advanced options
                                <?php if ($has_advanced): ?><span style="color:#4f46e5;font-weight:600;">●</span><?php endif; ?>
                            </summary>
                            <div class="bm-inputs-row" style="margin-top:6px;">
                                <!-- FEATURE 4: Schedule -->
                                <label style="font-size:10px;color:#555;">📅 From:</label>
                                <input type="datetime-local" name="menu_items[<?php echo absint( $idx ); ?>][schedule_start]" value="<?php echo esc_attr($item['schedule_start'] ?? ''); ?>" style="font-size:10px;height:24px;padding:2px 4px;max-width:160px;">
                                <label style="font-size:10px;color:#555;">to:</label>
                                <input type="datetime-local" name="menu_items[<?php echo absint( $idx ); ?>][schedule_end]" value="<?php echo esc_attr($item['schedule_end'] ?? ''); ?>" style="font-size:10px;height:24px;padding:2px 4px;max-width:160px;">
                            </div>
                            <div class="bm-inputs-row" style="margin-top:4px;">
                                <!-- FEATURE 10: Condizionali -->
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][cond_roles]" value="<?php echo esc_attr($item['cond_roles'] ?? ''); ?>" placeholder="Roles (e.g.: editor,author)" title="Show only to these WP roles" style="font-size:10px;height:24px;padding:2px 4px;max-width:140px;">
                                <select name="menu_items[<?php echo absint( $idx ); ?>][cond_devices]" style="font-size:10px;height:24px;padding:1px 2px;max-width:100px;" title="Device">
                                    <option value="" <?php selected($item['cond_devices'] ?? '', ''); ?>>📱 All devices</option>
                                    <option value="mobile" <?php selected($item['cond_devices'] ?? '', 'mobile'); ?>>📱 Mobile only</option>
                                    <option value="desktop" <?php selected($item['cond_devices'] ?? '', 'desktop'); ?>>🖥️ Desktop only</option>
                                </select>
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][cond_time_from]" value="<?php echo esc_attr($item['cond_time_from'] ?? ''); ?>" placeholder="⏰ From HH:MM" title="Show from" style="font-size:10px;height:24px;padding:2px 4px;max-width:70px;">
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][cond_time_to]" value="<?php echo esc_attr($item['cond_time_to'] ?? ''); ?>" placeholder="⏰ To HH:MM" title="Show until" style="font-size:10px;height:24px;padding:2px 4px;max-width:70px;">
                                <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][cond_utm]" value="<?php echo esc_attr($item['cond_utm'] ?? ''); ?>" placeholder="UTM source" title="Show only with this utm_source" style="font-size:10px;height:24px;padding:2px 4px;max-width:100px;">
                            </div>
                        </details>
                        <div class="bm-submenu-zone" id="bm-sm-zone-<?php echo absint( $idx ); ?>" style="margin-top:6px; border-top:1px dashed #e5e7eb; padding-top:6px;">
                            <button type="button" class="button button-small"
                                onclick="menux_toggleSubmenuZone(<?php echo absint( $idx ); ?>)"
                                id="bm-sm-toggle-<?php echo absint( $idx ); ?>"
                                style="font-size:11px; background:#f0f6fc; border-color:#c3d4e8; color:#2271b1;">
                                📂 <?php echo $has_subs ? absint( count($item['children']) ) . ' sub-items' : 'Add submenu'; ?> ▾
                            </button>
                            <div id="bm-sm-body-<?php echo absint( $idx ); ?>" style="display:<?php echo $has_subs ? 'block' : 'none'; ?>; margin-top:8px;">
                                <ul class="bm-sm-list" id="bm-sm-list-<?php echo absint( $idx ); ?>" style="list-style:none;padding:0;margin:0 0 6px;border-left:3px solid #c3d4e8;padding-left:10px;">
                                <?php if ($has_subs): foreach ($item['children'] as $ci => $child):
                                    $child_is_page = ($child['type'] === 'page');
                                    $child_label   = '';
                                    if (!empty($supported_langs)) {
                                        foreach ($supported_langs as $cl) { $ck = menux_code_to_key($cl['code']); if (!empty($child[$ck])) { $child_label = $child[$ck]; break; } }
                                    }
                                    if (empty($child_label)) $child_label = $child_is_page ? get_the_title($child['id']??0) : ($child['url']??'');
                                    $child_url = $child_is_page ? '' : ($child['url']??'');
                                    // Figli di terzo livello
                                    $grand_children = $child['children'] ?? [];
                                ?>
                                <li class="bm-sm-item" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:5px 8px;margin-bottom:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                    <span style="font-size:16px;color:#9ca3af;cursor:move;" title="Drag">⠿</span>
                                    <!-- Tipo: toggle tra pagina e link -->
                                    <select name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][type]"
                                            class="bm-child-type-sel"
                                            data-parent="<?php echo absint( $idx ); ?>" data-child="<?php echo absint( $ci ); ?>"
                                            onchange="menux_toggleChildType(this)"
                                            style="font-size:10px;height:24px;padding:1px 4px;border-radius:4px;border:1px solid #c3d4e8;color:#1d4ed8;background:#dbeafe;font-weight:600;max-width:90px;">
                                        <option value="page"   <?php selected($child['type'],'page');?>>📄 Page</option>
                                        <option value="custom" <?php selected($child['type'],'custom');?>>🔗 Link</option>
                                    </select>
                                    <!-- Campo pagina (visibile se type=page) -->
                                    <div class="bm-child-page-wrap" style="display:<?php echo $child_is_page ? 'flex' : 'none'; ?>;align-items:center;gap:4px;">
                                        <select name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][id]"
                                                style="font-size:11px;height:24px;padding:1px 4px;max-width:160px;">
                                            <option value="">-- Page --</option>
                                            <?php foreach (get_pages(['sort_column'=>'post_title']) as $pg): ?>
                                            <option value="<?php echo absint( $pg->ID ); ?>" <?php selected(intval($child['id']??0), $pg->ID); ?>><?php echo esc_html($pg->post_title); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Campo URL (visibile se type=custom) -->
                                    <div class="bm-child-url-wrap" style="display:<?php echo !$child_is_page ? 'flex' : 'none'; ?>;align-items:center;gap:4px;">
                                        <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][url]" value="<?php echo esc_attr($child_url); ?>" placeholder="https://..." style="font-size:11px;width:130px;height:24px;padding:2px 5px;">
                                    </div>
                                    <?php if (!empty($supported_langs)): foreach ($supported_langs as $cl): $ck = menux_code_to_key($cl['code']); ?>
                                    <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][<?php echo esc_attr($ck); ?>]" value="<?php echo esc_attr($child[$ck]??''); ?>" placeholder="<?php echo esc_attr($cl['code']); ?>" title="<?php echo esc_attr($cl['label']); ?>" style="font-size:11px;width:80px;height:24px;padding:2px 5px;">
                                    <?php endforeach; else: ?>
                                    <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][lang_it_IT]" value="<?php echo esc_attr($child['lang_it_IT']??$child_label); ?>" placeholder="Label" style="font-size:11px;width:110px;height:24px;padding:2px 5px;">
                                    <?php endif; ?>
                                    <!-- Icona con picker -->
                                    <div style="display:inline-flex;align-items:center;gap:2px;">
                                        <?php if (!empty($child['icon'])): ?><i class="bm-icon-preview-el <?php echo esc_attr($child['icon']);?>" style="font-size:14px;color:#374151;"></i><?php endif; ?>
                                        <input type="text" name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][icon]" value="<?php echo esc_attr($child['icon']??''); ?>" placeholder="icon" class="bm-icon-input" style="font-size:11px;width:90px;height:24px;padding:2px 5px;">
                                        <button type="button" onclick="mxIconPicker.open(this.previousElementSibling)" style="padding:1px 5px;font-size:11px;height:24px;cursor:pointer;background:#f9fafb;border:1px solid #d1d5db;border-radius:3px;" tabindex="-1">🎨</button>
                                    </div>
                                    <select name="menu_items[<?php echo absint( $idx ); ?>][children][<?php echo absint( $ci ); ?>][target]" style="font-size:11px;height:24px;padding:1px 3px;">
                                        <option value="" <?php selected($child['target']??'',''); ?>>Same tab</option>
                                        <option value="_blank" <?php selected($child['target']??'','_blank'); ?>>↗ New tab</option>
                                    </select>
                                    <?php if (!empty($grand_children)): ?>
                                    <span style="font-size:10px;background:#dbeafe;color:#1d4ed8;padding:1px 5px;border-radius:6px;"><?php echo absint( count($grand_children) ); ?> children</span>
                                    <?php foreach ($grand_children as $gi => $gc):
                                        $gc_is_page = ($gc['type']==='page');
                                        foreach ($gc as $gk => $gv) {
                                            if ($gk==='children') continue;
                                            echo '<input type="hidden" name="menu_items[' . absint($idx) . '][children][' . absint($ci) . '][children][' . absint($gi) . '][' . esc_attr($gk) . ']" value="' . esc_attr($gv) . '">';
                                        }
                                    endforeach; ?>
                                    <?php endif; ?>
                                    <button type="button" onclick="this.closest('li').remove(); menux_updateSubCount(<?php echo absint( $idx ); ?>);" style="background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;border-radius:3px;padding:1px 7px;font-size:12px;cursor:pointer;margin-left:auto;">&times;</button>
                                </li>
                                <?php endforeach; endif; ?>
                                </ul>
                                <!-- Aggiungi voce figlia -->
                                <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;">
                                    <button type="button" onclick="menux_addSubBlank(<?php echo absint( $idx ); ?>)" class="button button-small" style="font-size:11px;background:#f0f6fc;border-color:#c3d4e8;color:#2271b1;">+ Add sub-item</button>
                                    <span style="font-size:10px;color:#9ca3af;">Use the type selector to choose Page or Link</span>
                                </div>
                            </div>
                        </div><!-- fine bm-submenu-zone -->
                    </div><!-- fine container flex:1 -->
                    <button type="button" class="button button-small" onclick="menux_removeItem(this)" title="Remove" style="margin-top:3px; flex-shrink:0;">&times;</button>
                </li>
                <?php $idx++; endforeach; ?>
            </ul>
            <p id="menux-empty-msg" class="bm-empty-msg" style="<?php echo !empty($items) ? 'display:none;' : ''; ?>">
                <span class="bm-empty-msg-icon">📋</span>
                <span>No items yet. Add a page or a link above. Add a page or a link above!</span>
            </p>
            </div><!-- fine bm-card-body Menu Structure -->
        </div><!-- fine bm-card Menu Structure -->
    </div><!-- fine menux-panel -->
    <?php
}

function menux_get_preview_markup($items, $supported_langs = array()) {
    if (empty($items)) return '<div style="padding:20px; text-align:center; color:#555; font-style:italic;">Empty menu</div>';
    $out  = '<nav id="menux-preview-nav" class="menux-container menux-user-guest" style="width:100%; font-family:sans-serif;">';
    $out .= '<div class="menux-hamburger" onclick="this.classList.toggle(\'open\'); this.nextElementSibling.classList.toggle(\'show\');"><span></span><span></span><span></span></div>';
    $out .= '<ul class="menux-list" style="padding:10px;">';
    foreach ($items as $item) {
        $is_page = ($item['type'] === 'page');
        // Usa la prima lingua disponibile come label, con fallback ai vecchi campi
        $label = '';
        if (!empty($supported_langs)) {
            foreach ($supported_langs as $lang) {
                $key = menux_code_to_key($lang['code']);
                if (!empty($item[$key])) { $label = $item[$key]; break; }
            }
        }
        if (empty($label)) {
            $label = !empty($item['it']) ? $item['it'] : (!empty($item['en']) ? $item['en'] : ($is_page ? get_the_title($item['id']) : $item['url']));
        }
        $url      = $is_page ? get_permalink($item['id']) : $item['url'];
        $icon     = !empty($item['icon']) ? '<i class="'.esc_attr($item['icon']).'" style="margin-right:8px;"></i>' : '';
        $vis      = $item['visibility'] ?? 'all';
        $li_class = '';
        if ($vis === 'auth')     $li_class = 'bs-show-only-when-auth';
        if ($vis === 'non-auth') $li_class = 'bs-show-only-when-non-auth';
        $out .= '<li class="' . esc_attr($li_class) . '"><a href="' . esc_url($url) . '" class="menux-link">'.$icon.'<span class="menux-label">' . esc_html($label) . '</span></a></li>';
    }
    $out .= '</ul></nav>';
    return $out;
}
