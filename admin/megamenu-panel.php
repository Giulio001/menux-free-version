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
	?>
	<div class="bm-card">
		<div class="bm-card-header">
			<span class="bm-card-icon bm-card-icon-indigo">⚡</span>
			<div class="bm-card-titles">
				<h2 class="bm-card-title"><?php esc_html_e( 'Mega Menu', 'giuliomax-menu-builder' ); ?></h2>
				<p class="bm-card-subtitle"><?php esc_html_e( 'Enable a full-width column layout on any first-level item. Each column supports links, headings, dividers, images and shortcodes.', 'giuliomax-menu-builder' ); ?></p>
			</div>
		</div>
		<div class="bm-card-body">

			<?php if ( empty( $top_level ) ) : ?>
				<p style="color:#6b7280;font-size:13px;"><?php esc_html_e( 'Add menu items in the Menu Structure panel first.', 'giuliomax-menu-builder' ); ?></p>
			<?php else : ?>

			<p style="font-size:12px;color:#6b7280;margin:0 0 16px;"><?php esc_html_e( 'Toggle Mega Menu on an item and click "Edit Columns" to configure the column layout. Save the form to persist changes.', 'giuliomax-menu-builder' ); ?></p>

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
	<div id="menux-mega-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999999;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;" onclick="if(event.target===this)menuxMegaClose()">
		<div id="menux-mega-dialog" style="background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.3);width:min(1100px,98vw);font-family:-apple-system,sans-serif;overflow:hidden;">

			<!-- Header -->
			<div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;">
				<div>
					<div style="color:rgba(255,255,255,.7);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Mega Menu Editor</div>
					<div id="menux-mega-dialog-title" style="color:#fff;font-size:17px;font-weight:700;">Edit Columns</div>
				</div>
				<div style="display:flex;gap:10px;align-items:center;">
					<button type="button" onclick="menuxMegaSave()" style="background:#fff;color:#4f46e5;border:none;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">💾 Save</button>
					<button type="button" onclick="menuxMegaClose()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;">&times;</button>
				</div>
			</div>

			<!-- Toolbar -->
			<div style="padding:14px 24px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
				<div style="display:flex;align-items:center;gap:8px;">
					<span style="font-size:12px;font-weight:600;color:#374151;">Columns:</span>
					<?php foreach ( array( 1, 2, 3, 4, 5, 6 ) as $n ) : ?>
					<button type="button"
						class="bm-mega-col-count-btn"
						data-cols="<?php echo esc_attr( $n ); ?>"
						onclick="menuxMegaSetCols(<?php echo esc_attr( $n ); ?>)"
						style="width:30px;height:30px;border:2px solid #e5e7eb;border-radius:6px;background:#fff;font-size:13px;font-weight:700;cursor:pointer;color:#374151;">
						<?php echo esc_html( $n ); ?>
					</button>
					<?php endforeach; ?>
				</div>
				<button type="button" onclick="menuxMegaAddCol()" style="background:#ede9fe;color:#5b21b6;border:1px dashed #a78bfa;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">+ Add Column</button>
			</div>

			<!-- Columns area -->
			<div id="menux-mega-cols-wrap" style="display:flex;gap:0;overflow-x:auto;min-height:400px;align-items:stretch;">
				<!-- Columns rendered by JS -->
			</div>

			<!-- Footer -->
			<div style="padding:14px 24px;border-top:1px solid #f0f0f0;background:#fafafa;display:flex;justify-content:flex-end;gap:10px;border-radius:0 0 16px 16px;">
				<button type="button" onclick="menuxMegaClose()" style="background:#fff;border:1px solid #d1d5db;color:#374151;padding:8px 20px;border-radius:8px;font-size:13px;cursor:pointer;"><?php esc_html_e( 'Cancel', 'giuliomax-menu-builder' ); ?></button>
				<button type="button" onclick="menuxMegaSave()" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;color:#fff;padding:8px 22px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">💾 <?php esc_html_e( 'Save columns', 'giuliomax-menu-builder' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
