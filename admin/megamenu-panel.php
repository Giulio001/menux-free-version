<?php
/**
 * MenuX — Mega Menu Admin Panel
 *
 * Renders the mega menu configuration panel. Each first-level item can have
 * the Mega Menu toggle enabled, which replaces the standard dropdown with a
 * full-width column layout. Column data is managed via a JS editor modal and
 * serialised to a JSON hidden input on save.
 *
 * @package GiuliomaxMenuBuilder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the mega menu overview panel.
 *
 * @param array $menu_items  Saved menu items array from menux_menu_items option.
 */
function menux_render_megamenu_panel( $menu_items ) {
	// Only first-level items can have mega menu.
	$top_level = array();
	foreach ( (array) $menu_items as $item ) {
		if ( ! empty( $item['type'] ) ) {
			$top_level[] = $item;
		}
	}

	$s               = get_option( 'menux_style', array() );
	$mega_bg         = ! empty( $s['mega_bg'] )           ? $s['mega_bg']                  : '#ffffff';
	$mega_pad_y      = isset( $s['mega_padding_y'] )      ? (int) $s['mega_padding_y']     : 24;
	$mega_pad_x      = isset( $s['mega_padding_x'] )      ? (int) $s['mega_padding_x']     : 32;
	$mega_max_w      = isset( $s['mega_max_width'] )      ? (int) $s['mega_max_width']     : 0;
	$mega_gap        = isset( $s['mega_col_gap'] )        ? (int) $s['mega_col_gap']       : 16;
	$mega_mob        = ( $s['mega_mobile_disable'] ?? '0' ) === '1';
	$mega_radius     = isset( $s['mega_border_radius'] )  ? (int) $s['mega_border_radius'] : 14;
	$mega_font_size  = isset( $s['mega_font_size'] )      ? (int) $s['mega_font_size']      : 0;
	$mega_link_color = ! empty( $s['mega_link_color'] )   ? $s['mega_link_color']          : '#374151';
	$mega_head_color = ! empty( $s['mega_heading_color'] )? $s['mega_heading_color']       : '#9ca3af';
	$mega_accent     = ! empty( $s['mega_accent_color'] ) ? $s['mega_accent_color']        : '#667eea';
	?>
	<div class="bm-card">
		<div class="bm-card-header">
			<span class="bm-card-icon bm-card-icon-indigo">⚡</span>
			<div class="bm-card-titles">
				<h2 class="bm-card-title"><?php esc_html_e( 'Mega Menu', 'giuliomax-menu-builder' ); ?></h2>
				<p class="bm-card-subtitle"><?php esc_html_e( 'Full-width column layout with links, headings, images and shortcodes. Max 4 columns.', 'giuliomax-menu-builder' ); ?></p>
			</div>
		</div>
		<div class="bm-card-body">

		<!-- ── Panel Appearance Settings ── -->
		<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
			<div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px;">Panel Appearance</div>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px 20px;align-items:end;">

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Background</label>
					<div style="display:flex;align-items:center;gap:6px;">
						<input type="checkbox" name="menux_style_use[mega_bg]" value="1" id="bm-mega-bg-use" <?php checked( ! empty( $s['mega_bg'] ) ); ?>>
						<input type="color" id="bm-mega-setting-bg" name="menux_style[mega_bg]" value="<?php echo esc_attr( $mega_bg ); ?>" style="width:40px;height:28px;padding:1px;border:1px solid #d1d5db;border-radius:5px;cursor:pointer;">
					</div>
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Max Width (px)</label>
					<input type="number" id="bm-mega-setting-max-w" name="menux_style[mega_max_width]" value="<?php echo esc_attr( $mega_max_w ?: '' ); ?>" placeholder="Full width" min="400" max="2400" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Padding Top/Bottom (px)</label>
					<input type="number" id="bm-mega-setting-pad-y" name="menux_style[mega_padding_y]" value="<?php echo esc_attr( $mega_pad_y ); ?>" min="0" max="80" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Padding Left/Right (px)</label>
					<input type="number" id="bm-mega-setting-pad-x" name="menux_style[mega_padding_x]" value="<?php echo esc_attr( $mega_pad_x ); ?>" min="0" max="120" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Column Gap (px)</label>
					<input type="number" id="bm-mega-setting-gap" name="menux_style[mega_col_gap]" value="<?php echo esc_attr( $mega_gap ); ?>" min="0" max="80" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Border Radius (px)</label>
					<input type="number" name="menux_style[mega_border_radius]" value="<?php echo esc_attr( $mega_radius ); ?>" min="0" max="40" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Font Size (px)</label>
					<input type="number" name="menux_style[mega_font_size]" value="<?php echo esc_attr( $mega_font_size ?: '' ); ?>" placeholder="Default" min="10" max="24" class="bm-input" style="width:100%;">
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Link Color</label>
					<div style="display:flex;align-items:center;gap:6px;">
						<input type="checkbox" name="menux_style_use[mega_link_color]" value="1" id="bm-mega-link-color-use" <?php checked( ! empty( $s['mega_link_color'] ) ); ?>>
						<input type="color" name="menux_style[mega_link_color]" value="<?php echo esc_attr( $mega_link_color ); ?>" style="width:40px;height:28px;padding:1px;border:1px solid #d1d5db;border-radius:5px;cursor:pointer;">
					</div>
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Heading Color</label>
					<div style="display:flex;align-items:center;gap:6px;">
						<input type="checkbox" name="menux_style_use[mega_heading_color]" value="1" id="bm-mega-head-color-use" <?php checked( ! empty( $s['mega_heading_color'] ) ); ?>>
						<input type="color" name="menux_style[mega_heading_color]" value="<?php echo esc_attr( $mega_head_color ); ?>" style="width:40px;height:28px;padding:1px;border:1px solid #d1d5db;border-radius:5px;cursor:pointer;">
					</div>
				</div>

				<div>
					<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Accent / Icon Color</label>
					<div style="display:flex;align-items:center;gap:6px;">
						<input type="checkbox" name="menux_style_use[mega_accent_color]" value="1" id="bm-mega-accent-color-use" <?php checked( ! empty( $s['mega_accent_color'] ) ); ?>>
						<input type="color" name="menux_style[mega_accent_color]" value="<?php echo esc_attr( $mega_accent ); ?>" style="width:40px;height:28px;padding:1px;border:1px solid #d1d5db;border-radius:5px;cursor:pointer;">
					</div>
				</div>

				<div style="display:flex;align-items:flex-end;padding-bottom:4px;">
					<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#374151;">
						<input type="checkbox" name="menux_style[mega_mobile_disable]" value="1" <?php checked( $mega_mob ); ?>>
						Disable on mobile
					</label>
				</div>

			</div>
		</div>

			<?php if ( empty( $top_level ) ) : ?>
				<p style="color:#6b7280;font-size:13px;"><?php esc_html_e( 'Add menu items in the Menu Structure panel first.', 'giuliomax-menu-builder' ); ?></p>
			<?php else : ?>

			<p style="font-size:12px;color:#6b7280;margin:0 0 16px;"><?php esc_html_e( 'Enable Mega Menu on an item, then click "Edit Columns". Save the form to persist.', 'giuliomax-menu-builder' ); ?></p>

			<div class="bm-mega-item-list">
				<?php foreach ( $top_level as $idx => $item ) :
					$item_key  = $item['item_key'] ?? ( 'item_' . $idx );
					$is_page   = ( $item['type'] ?? '' ) === 'page';
					$label     = ! empty( $item['it'] ) ? $item['it'] : ( $is_page ? get_the_title( $item['id'] ?? 0 ) : ( $item['url'] ?? '—' ) );
					$mega_on   = ( $item['mega_menu'] ?? '0' ) === '1';
					$full_w    = ( $item['mega_full_width'] ?? '1' ) === '1';
					$cols_json = ! empty( $item['mega_columns'] ) ? wp_json_encode( $item['mega_columns'] ) : '[]';
					?>
					<div class="bm-mega-item" id="bm-mega-item-<?php echo esc_attr( $item_key ); ?>">
						<div class="bm-mega-item-header">
							<span class="bm-mega-item-label"><?php echo esc_html( $label ); ?></span>

							<label class="bm-toggle" title="<?php esc_attr_e( 'Enable Mega Menu for this item', 'giuliomax-menu-builder' ); ?>">
								<input type="checkbox"
									class="bm-mega-toggle-cb"
									data-item-key="<?php echo esc_attr( $item_key ); ?>"
									name="menu_items[<?php echo esc_attr( $idx ); ?>][mega_menu]"
									value="1"
									<?php checked( $mega_on ); ?>
									onchange="menuxMegaToggle('<?php echo esc_js( $item_key ); ?>', this.checked)">
								<span class="bm-toggle-slider"></span>
								<span class="bm-toggle-label"><?php esc_html_e( 'Mega Menu', 'giuliomax-menu-builder' ); ?></span>
							</label>

							<label class="bm-mega-full-label" id="bm-mega-full-<?php echo esc_attr( $item_key ); ?>" style="<?php echo $mega_on ? '' : 'display:none;'; ?>">
								<input type="checkbox"
									name="menu_items[<?php echo esc_attr( $idx ); ?>][mega_full_width]"
									value="1"
									<?php checked( $full_w ); ?>>
								<span style="font-size:12px;color:#374151;"><?php esc_html_e( 'Full width', 'giuliomax-menu-builder' ); ?></span>
							</label>

							<button type="button"
								class="bm-btn bm-btn-primary bm-btn-sm"
								id="bm-mega-edit-<?php echo esc_attr( $item_key ); ?>"
								style="<?php echo $mega_on ? '' : 'display:none;'; ?>"
								onclick="menuxMegaOpen('<?php echo esc_js( $item_key ); ?>')">
								<?php esc_html_e( 'Edit Columns', 'giuliomax-menu-builder' ); ?> ▶
							</button>

							<!-- Hidden JSON field — updated by the JS editor -->
							<input type="hidden"
								id="mega-json-<?php echo esc_attr( $item_key ); ?>"
								name="menu_items[<?php echo esc_attr( $idx ); ?>][mega_columns_json]"
								value="<?php echo esc_attr( $cols_json ); ?>">
						</div>

						<div class="bm-mega-item-summary" id="bm-mega-summary-<?php echo esc_attr( $item_key ); ?>" style="<?php echo $mega_on ? '' : 'display:none;'; ?>">
							<?php
							$num_cols = count( $item['mega_columns'] ?? array() );
							if ( $num_cols > 0 ) {
								echo '<span class="bm-mega-info">' . sprintf(
									// translators: %d = number of columns
									esc_html__( '%d column(s) configured', 'giuliomax-menu-builder' ),
									$num_cols
								) . '</span>';
							} else {
								echo '<span class="bm-mega-info bm-mega-info-empty">' . esc_html__( 'No columns yet — click Edit Columns.', 'giuliomax-menu-builder' ) . '</span>';
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php endif; ?>
		</div>
	</div>

	<!-- ──── MEGA MENU EDITOR MODAL ──── -->
	<div id="menux-mega-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:999999;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;" onclick="if(event.target===this)menuxMegaClose()">
		<div id="menux-mega-dialog" style="background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.3);width:min(1200px,98vw);font-family:-apple-system,sans-serif;overflow:hidden;">

			<!-- Header -->
			<div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;">
				<div>
					<div style="color:rgba(255,255,255,.65);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Mega Menu Editor</div>
					<div id="menux-mega-dialog-title" style="color:#fff;font-size:16px;font-weight:700;">Edit Columns</div>
				</div>
				<div style="display:flex;gap:8px;align-items:center;">
					<button type="button" onclick="menuxMegaSave()" style="background:#fff;color:#4f46e5;border:none;padding:7px 18px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Save</button>
					<button type="button" onclick="menuxMegaClose()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;">&times;</button>
				</div>
			</div>

			<!-- Toolbar -->
			<div style="padding:10px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
				<div style="display:flex;align-items:center;gap:6px;">
					<span style="font-size:11px;font-weight:600;color:#6b7280;">Quick set:</span>
					<?php foreach ( array( 1, 2, 3, 4 ) as $n ) : ?>
					<button type="button"
						class="bm-mega-col-count-btn"
						data-cols="<?php echo esc_attr( $n ); ?>"
						onclick="menuxMegaSetCols(<?php echo esc_attr( $n ); ?>)"
						style="width:28px;height:28px;border:2px solid #e5e7eb;border-radius:6px;background:#fff;font-size:12px;font-weight:700;cursor:pointer;color:#374151;">
						<?php echo esc_html( $n ); ?>
					</button>
					<?php endforeach; ?>
				</div>
				<button type="button" onclick="menuxMegaAddCol()" style="background:#ede9fe;color:#5b21b6;border:1px dashed #a78bfa;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">+ Add Column</button>
				<span style="margin-left:auto;font-size:11px;color:#9ca3af;">Max 4 columns</span>
			</div>

			<!-- Body: columns editor + live preview side by side -->
			<div style="display:flex;min-height:420px;overflow:hidden;">

				<!-- Left: Columns editor -->
				<div id="menux-mega-cols-wrap" style="flex:1;display:flex;overflow-x:auto;align-items:stretch;border-right:1px solid #e5e7eb;min-width:0;">
					<!-- Columns rendered by JS -->
				</div>

				<!-- Right: Live preview -->
				<div style="width:300px;min-width:260px;flex-shrink:0;background:#f1f5f9;display:flex;flex-direction:column;overflow:hidden;">
					<div style="padding:12px 16px 8px;border-bottom:1px solid #e2e8f0;">
						<span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#64748b;">Live Preview</span>
					</div>
					<!-- Nav trigger simulation -->
					<div style="background:#1e293b;padding:8px 16px;display:flex;align-items:center;gap:16px;">
						<span style="color:#94a3b8;font-size:11px;">Home</span>
						<span style="color:#fff;font-size:11px;font-weight:600;border-bottom:2px solid #818cf8;padding-bottom:2px;" id="menux-mega-preview-trigger">Menu item ▾</span>
						<span style="color:#94a3b8;font-size:11px;">About</span>
					</div>
					<!-- Preview panel -->
					<div style="flex:1;overflow-y:auto;padding:0;">
						<div id="menux-mega-preview" style="background:#fff;border-bottom:1px solid #e2e8f0;padding:16px;min-height:120px;">
							<div style="color:#9ca3af;font-size:11px;text-align:center;padding:20px 0;">Add columns to see the preview</div>
						</div>
					</div>
					<div style="padding:8px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;">
						<span style="font-size:10px;color:#94a3b8;">Approximate preview — actual look depends on your theme.</span>
					</div>
				</div>

			</div>

			<!-- Footer -->
			<div style="padding:12px 20px;border-top:1px solid #f0f0f0;background:#fafafa;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 16px 16px;">
				<button type="button" onclick="menuxMegaClose()" style="background:#fff;border:1px solid #d1d5db;color:#374151;padding:7px 18px;border-radius:8px;font-size:13px;cursor:pointer;"><?php esc_html_e( 'Cancel', 'giuliomax-menu-builder' ); ?></button>
				<button type="button" onclick="menuxMegaSave()" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;color:#fff;padding:7px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">Save columns</button>
			</div>
		</div>
	</div>
	<?php
}
