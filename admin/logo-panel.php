<?php
/**
 * MenuX — Logo Admin Panel
 *
 * Renders the logo configuration section in the admin UI.
 * Each logo slot supports a WP Media Library attachment ID or an external URL.
 *
 * @package GiuliomaxMenuBuilder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders one logo slot row (upload button + preview + clear button + optional URL input).
 *
 * @param string $slot_key  Short identifier: desktop | desktop_dark | sticky | mobile
 * @param string $label     Human-readable slot label
 * @param array  $logo      Current logo settings
 * @param string $desc      Optional tooltip description
 */
function menux_logo_slot( $slot_key, $label, $logo, $desc = '' ) {
	$id_field  = $slot_key . '_id';
	$url_field = $slot_key . '_url';
	$att_id    = (int) ( $logo[ $id_field ]  ?? 0 );
	$ext_url   = $logo[ $url_field ] ?? '';

	$preview_html = Menux_Logo::get_preview_img( $att_id, $ext_url );
	?>
	<div class="bm-logo-slot" id="bm-logo-slot-<?php echo esc_attr( $slot_key ); ?>">
		<div class="bm-logo-slot-label">
			<?php echo esc_html( $label ); ?>
			<?php if ( $desc ) : ?>
				<span class="bm-logo-slot-desc"><?php echo esc_html( $desc ); ?></span>
			<?php endif; ?>
		</div>
		<div class="bm-logo-slot-body">
			<!-- Hidden fields -->
			<input type="hidden"
				name="menux_logo[<?php echo esc_attr( $id_field ); ?>]"
				id="bm-logo-att-<?php echo esc_attr( $slot_key ); ?>"
				value="<?php echo esc_attr( $att_id ); ?>">

			<!-- Preview -->
			<div class="bm-logo-preview" id="bm-logo-preview-<?php echo esc_attr( $slot_key ); ?>">
				<?php echo wp_kses_post( $preview_html ); ?>
			</div>

			<!-- Actions -->
			<button type="button"
				class="bm-btn bm-btn-secondary bm-btn-sm"
				onclick="menuxLogoUpload('<?php echo esc_js( $slot_key ); ?>')">
				<?php echo $att_id || $ext_url ? esc_html__( 'Change', 'giuliomax-menu-builder' ) : esc_html__( 'Upload', 'giuliomax-menu-builder' ); ?>
			</button>

			<?php if ( $att_id || $ext_url ) : ?>
			<button type="button"
				class="bm-btn bm-btn-sm bm-btn-danger-ghost"
				onclick="menuxLogoClear('<?php echo esc_js( $slot_key ); ?>')">
				<?php esc_html_e( 'Clear', 'giuliomax-menu-builder' ); ?>
			</button>
			<?php endif; ?>

			<!-- External URL fallback -->
			<div style="margin-top:8px;">
				<input type="url"
					class="bm-input"
					name="menux_logo[<?php echo esc_attr( $url_field ); ?>]"
					id="bm-logo-url-<?php echo esc_attr( $slot_key ); ?>"
					value="<?php echo esc_url( $ext_url ); ?>"
					placeholder="<?php esc_attr_e( 'Or paste external URL (SVG, PNG, WebP…)', 'giuliomax-menu-builder' ); ?>"
					style="width:100%;max-width:380px;font-size:12px;"
					oninput="menuxLogoUrlChange('<?php echo esc_js( $slot_key ); ?>', this.value)">
			</div>
		</div>
	</div>
	<?php
}

/**
 * Main logo panel renderer — called from admin-page.php.
 */
function menux_render_logo_panel() {
	$logo = Menux_Logo::get_settings();
	?>
	<div class="bm-card">
		<div class="bm-card-header">
			<span class="bm-card-icon bm-card-icon-purple">🖼️</span>
			<div class="bm-card-titles">
				<h2 class="bm-card-title"><?php esc_html_e( 'Logo', 'giuliomax-menu-builder' ); ?></h2>
				<p class="bm-card-subtitle"><?php esc_html_e( 'Configure logo for desktop, dark mode, sticky state and mobile. SVG preferred — PNG, WebP, JPG also supported.', 'giuliomax-menu-builder' ); ?></p>
			</div>
		</div>
		<div class="bm-card-body">

			<!-- ── LOGO SLOTS ── -->
			<div class="bm-logo-slots">
				<?php
				menux_logo_slot( 'desktop',      __( 'Desktop logo',         'giuliomax-menu-builder' ), $logo, __( 'Main logo shown on desktop.', 'giuliomax-menu-builder' ) );
				menux_logo_slot( 'desktop_dark', __( 'Desktop logo (dark)',  'giuliomax-menu-builder' ), $logo, __( 'Shown when dark mode is active. Leave empty to reuse the main logo.', 'giuliomax-menu-builder' ) );
				menux_logo_slot( 'sticky',       __( 'Sticky logo',          'giuliomax-menu-builder' ), $logo, __( 'Replaces desktop logo when the menu is in sticky mode. Optional.', 'giuliomax-menu-builder' ) );
				menux_logo_slot( 'mobile',       __( 'Mobile logo',          'giuliomax-menu-builder' ), $logo, __( 'Shown on small screens instead of the desktop logo. Optional.', 'giuliomax-menu-builder' ) );
				?>
			</div>

			<hr style="margin:24px 0;border:none;border-top:1px solid #f0f0f0;">

			<!-- ── SIZING ── -->
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
			<div>
				<div class="bm-logo-section-title"><?php esc_html_e( 'Sizing', 'giuliomax-menu-builder' ); ?></div>
				<table class="bm-logo-table">
					<tr>
						<td><?php esc_html_e( 'Width', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<div style="display:flex;gap:6px;align-items:center;">
								<input type="number" name="menux_logo[width]" value="<?php echo esc_attr( $logo['width'] ); ?>" min="1" max="1200" style="width:70px;" class="bm-input">
								<select name="menux_logo[width_unit]" class="bm-select" style="width:70px;">
									<?php foreach ( array( 'px', '%', 'em', 'rem', 'vw' ) as $u ) : ?>
									<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $logo['width_unit'], $u ); ?>><?php echo esc_html( $u ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Height', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<div style="display:flex;gap:6px;align-items:center;">
								<input type="number" name="menux_logo[height]" value="<?php echo esc_attr( $logo['height'] ); ?>" min="1" max="600" placeholder="auto" style="width:70px;" class="bm-input">
								<select name="menux_logo[height_unit]" class="bm-select" style="width:70px;">
									<?php foreach ( array( 'px', 'em', 'rem', 'vh' ) as $u ) : ?>
									<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $logo['height_unit'], $u ); ?>><?php echo esc_html( $u ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<p style="font-size:11px;color:#9ca3af;margin:3px 0 0;"><?php esc_html_e( 'Leave empty for auto', 'giuliomax-menu-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Max-width', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<div style="display:flex;gap:6px;align-items:center;">
								<input type="number" name="menux_logo[max_width]" value="<?php echo esc_attr( $logo['max_width'] ); ?>" min="1" max="1200" placeholder="—" style="width:70px;" class="bm-input">
								<select name="menux_logo[max_width_unit]" class="bm-select" style="width:70px;">
									<?php foreach ( array( 'px', '%', 'em', 'rem' ) as $u ) : ?>
									<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $logo['max_width_unit'], $u ); ?>><?php echo esc_html( $u ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Vertical align', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<select name="menux_logo[vertical_align]" class="bm-select">
								<option value="flex-start" <?php selected( $logo['vertical_align'], 'flex-start' ); ?>><?php esc_html_e( 'Top', 'giuliomax-menu-builder' ); ?></option>
								<option value="center"     <?php selected( $logo['vertical_align'], 'center' ); ?>><?php esc_html_e( 'Center', 'giuliomax-menu-builder' ); ?></option>
								<option value="flex-end"   <?php selected( $logo['vertical_align'], 'flex-end' ); ?>><?php esc_html_e( 'Bottom', 'giuliomax-menu-builder' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<div class="bm-logo-section-title"><?php esc_html_e( 'Spacing', 'giuliomax-menu-builder' ); ?></div>
				<table class="bm-logo-table">
					<tr>
						<td><?php esc_html_e( 'Margin', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<input type="text" name="menux_logo[margin]" value="<?php echo esc_attr( $logo['margin'] ); ?>"
								placeholder="0 16px 0 0" class="bm-input" style="width:180px;">
							<p style="font-size:11px;color:#9ca3af;margin:3px 0 0;"><?php esc_html_e( 'CSS shorthand: top right bottom left', 'giuliomax-menu-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Padding', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<input type="text" name="menux_logo[padding]" value="<?php echo esc_attr( $logo['padding'] ); ?>"
								placeholder="4px 0" class="bm-input" style="width:180px;">
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Alt text', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<input type="text" name="menux_logo[alt]" value="<?php echo esc_attr( $logo['alt'] ); ?>"
								placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
								class="bm-input" style="width:180px;"
								aria-describedby="bm-logo-alt-hint">
							<p id="bm-logo-alt-hint" style="font-size:11px;color:#9ca3af;margin:3px 0 0;"><?php esc_html_e( 'Defaults to site name if empty.', 'giuliomax-menu-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Hide on mobile', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
								<input type="checkbox" name="menux_logo[hide_on_mobile]" value="1" <?php checked( $logo['hide_on_mobile'], '1' ); ?>>
								<span style="font-size:13px;"><?php esc_html_e( 'Yes — hide below 768px', 'giuliomax-menu-builder' ); ?></span>
							</label>
						</td>
					</tr>
				</table>
			</div>
			</div>

			<hr style="margin:24px 0;border:none;border-top:1px solid #f0f0f0;">

			<!-- ── LINK OPTIONS ── -->
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
			<div>
				<div class="bm-logo-section-title"><?php esc_html_e( 'Link', 'giuliomax-menu-builder' ); ?></div>
				<table class="bm-logo-table">
					<tr>
						<td><?php esc_html_e( 'Link enabled', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
								<input type="checkbox" name="menux_logo[link_enabled]" value="1" <?php checked( $logo['link_enabled'], '1' ); ?>>
								<span style="font-size:13px;"><?php esc_html_e( 'Yes (link to homepage by default)', 'giuliomax-menu-builder' ); ?></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Custom URL', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<input type="url" name="menux_logo[link_url]" value="<?php echo esc_url( $logo['link_url'] ); ?>"
								placeholder="<?php echo esc_url( home_url( '/' ) ); ?>"
								class="bm-input" style="width:220px;">
							<p style="font-size:11px;color:#9ca3af;margin:3px 0 0;"><?php esc_html_e( 'Leave empty to link to the homepage.', 'giuliomax-menu-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Open in new tab', 'giuliomax-menu-builder' ); ?></td>
						<td>
							<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
								<input type="checkbox" name="menux_logo[link_target]" value="_blank" <?php checked( $logo['link_target'], '_blank' ); ?>>
								<span style="font-size:13px;"><?php esc_html_e( 'Yes', 'giuliomax-menu-builder' ); ?></span>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<!-- ── POSITION ── -->
			<div>
				<div class="bm-logo-section-title"><?php esc_html_e( 'Position in menu bar', 'giuliomax-menu-builder' ); ?></div>
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px;">
					<?php
					$positions = array(
						'left'          => array( 'label' => __( 'Left', 'giuliomax-menu-builder' ),         'icon' => '⬅️', 'desc' => __( 'Before the nav links', 'giuliomax-menu-builder' ) ),
						'right'         => array( 'label' => __( 'Right', 'giuliomax-menu-builder' ),        'icon' => '➡️', 'desc' => __( 'After the nav links', 'giuliomax-menu-builder' ) ),
						'center'        => array( 'label' => __( 'Center', 'giuliomax-menu-builder' ),       'icon' => '⏺️', 'desc' => __( 'Centered between items', 'giuliomax-menu-builder' ) ),
						'center-split'  => array( 'label' => __( 'Center split', 'giuliomax-menu-builder' ), 'icon' => '⬛', 'desc' => __( 'Logo in the middle, links on both sides', 'giuliomax-menu-builder' ) ),
					);
					foreach ( $positions as $val => $pos ) :
						$is_active = ( $logo['position'] === $val );
					?>
					<label style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border:2px solid <?php echo esc_attr( $is_active ? '#667eea' : '#e5e7eb' ); ?>;border-radius:8px;cursor:pointer;background:<?php echo esc_attr( $is_active ? '#f0f4ff' : '#fff' ); ?>;">
						<input type="radio" name="menux_logo[position]" value="<?php echo esc_attr( $val ); ?>" <?php checked( $logo['position'], $val ); ?> style="margin-top:3px;flex-shrink:0;" onchange="this.closest('.bm-card-body').querySelectorAll('[name=\'menux_logo[position]\']').forEach(function(r){var l=r.closest('label');l.style.borderColor=r.checked?'#667eea':'#e5e7eb';l.style.background=r.checked?'#f0f4ff':'#fff';})">
						<div>
							<strong style="font-size:13px;color:#111827;"><?php echo esc_html( $pos['icon'] . ' ' . $pos['label'] ); ?></strong>
							<p style="font-size:11px;color:#6b7280;margin:2px 0 0;"><?php echo esc_html( $pos['desc'] ); ?></p>
						</div>
					</label>
					<?php endforeach; ?>
				</div>
			</div>
			</div>

		</div><!-- /.bm-card-body -->
	</div><!-- /.bm-card -->
	<?php
}
