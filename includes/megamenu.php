<?php
/**
 * MenuX — Mega Menu System
 *
 * Renders mega menu column panels for first-level nav items.
 * Column data is stored per-item inside menux_menu_items.
 *
 * Supported column item types:
 *   link      — clickable link with icon + optional description
 *   heading   — non-clickable section label
 *   divider   — horizontal rule separator
 *   image     — image block (WP attachment or external URL)
 *   shortcode — arbitrary shortcode / Gutenberg reusable block
 *
 * @package GiuliomaxMenuBuilder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Menux_MegaMenu {

	// ─────────────────────────────────────────────────────────────────
	// Frontend rendering
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Renders the full mega menu panel HTML for one first-level item.
	 *
	 * @param array $item  The raw menu item array (must have mega_columns key).
	 * @return string
	 */
	public static function render_panel( $item ) {
		$cols       = is_array( $item['mega_columns'] ?? null ) ? $item['mega_columns'] : array();
		$full_width = ( $item['mega_full_width'] ?? '1' ) === '1';

		if ( empty( $cols ) ) {
			return '';
		}

		$panel_class = 'menux-mega-panel';
		if ( $full_width ) {
			$panel_class .= ' menux-mega-full';
		}

		$out  = '<div class="' . esc_attr( $panel_class ) . '" role="menu" aria-label="' . esc_attr__( 'Submenu', 'giuliomax-menu-builder' ) . '">';
		$out .= '<div class="menux-mega-inner">';

		foreach ( $cols as $col ) {
			$col_items = is_array( $col['items'] ?? null ) ? $col['items'] : array();

			// Column width: explicit % or flex:1 for auto.
			$col_style = '';
			if ( ! empty( $col['width_pct'] ) && is_numeric( $col['width_pct'] ) ) {
				$pct       = min( 100, max( 5, (int) $col['width_pct'] ) );
				$col_style = 'flex:0 0 ' . $pct . '%;max-width:' . $pct . '%;';
			}

			$out .= '<div class="menux-mega-col"' . ( $col_style ? ' style="' . esc_attr( $col_style ) . '"' : '' ) . '>';
			foreach ( $col_items as $mega_item ) {
				$out .= self::render_col_item( $mega_item );
			}
			$out .= '</div>';
		}

		$out .= '</div></div>';
		return $out;
	}

	/** Renders a single column item based on its type. */
	private static function render_col_item( $mega_item ) {
		$type = $mega_item['type'] ?? 'link';

		switch ( $type ) {

			case 'heading':
				if ( empty( $mega_item['label'] ) ) return '';
				$icon_h = ! empty( $mega_item['icon'] )
					? '<i class="' . esc_attr( $mega_item['icon'] ) . '" aria-hidden="true" style="margin-right:5px;"></i>' : '';
				return '<h3 class="menux-mega-heading">' . $icon_h . esc_html( $mega_item['label'] ) . '</h3>';

			case 'divider':
				return '<hr class="menux-mega-divider" aria-hidden="true">';

			case 'image':
				return self::render_col_image( $mega_item );

			case 'shortcode':
				if ( empty( $mega_item['content'] ) ) return '';
				// do_shortcode is intentionally not wrapped — trusted admin-entered content.
				return '<div class="menux-mega-widget">' . do_shortcode( $mega_item['content'] ) . '</div>';

			default: // 'link'
				return self::render_col_link( $mega_item );
		}
	}

	private static function render_col_link( $item ) {
		$url    = ! empty( $item['url'] ) ? esc_url( $item['url'] ) : '#';
		$label  = esc_html( $item['label'] ?? '' );
		$target = ( ! empty( $item['target'] ) && $item['target'] === '_blank' )
			? ' target="_blank" rel="noopener noreferrer"' : '';
		$icon_html = ! empty( $item['icon'] )
			? '<i class="' . esc_attr( $item['icon'] ) . '" aria-hidden="true"></i>' : '';
		$desc_html = ! empty( $item['desc'] )
			? '<small class="menux-mega-link-desc">' . esc_html( $item['desc'] ) . '</small>' : '';

		return '<a href="' . $url . '" class="menux-mega-link" role="menuitem"' . $target . '>'
			. $icon_html
			. '<span class="menux-mega-link-inner">'
			.   '<span class="menux-mega-link-label">' . $label . '</span>'
			.   $desc_html
			. '</span>'
			. '</a>';
	}

	private static function render_col_image( $item ) {
		if ( ! empty( $item['image_id'] ) && (int) $item['image_id'] > 0 ) {
			$img = wp_get_attachment_image( (int) $item['image_id'], 'medium', false, array( 'loading' => 'lazy' ) );
		} elseif ( ! empty( $item['image_url'] ) ) {
			$img = '<img src="' . esc_url( $item['image_url'] ) . '"'
				. ' alt="' . esc_attr( $item['label'] ?? '' ) . '" loading="lazy">';
		} else {
			return '';
		}

		if ( ! empty( $item['url'] ) ) {
			$target = ( ! empty( $item['target'] ) && $item['target'] === '_blank' )
				? ' target="_blank" rel="noopener noreferrer"' : '';
			return '<div class="menux-mega-image"><a href="' . esc_url( $item['url'] ) . '"' . $target . '>' . $img . '</a></div>';
		}

		return '<div class="menux-mega-image">' . $img . '</div>';
	}

	// ─────────────────────────────────────────────────────────────────
	// Save helper — sanitises raw POST column data
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Sanitises and returns a clean mega_columns array from raw JSON input.
	 *
	 * @param string $json_raw  Raw JSON string from a hidden form input.
	 * @return array
	 */
	public static function sanitize_columns( $json_raw ) {
		if ( empty( $json_raw ) ) return array();

		$decoded = json_decode( stripslashes( (string) $json_raw ), true );
		if ( ! is_array( $decoded ) ) return array();

		$allowed_types = array( 'link', 'heading', 'divider', 'image', 'shortcode' );
		$clean_cols    = array();

		foreach ( $decoded as $col ) {
			if ( ! is_array( $col ) ) continue;

			$clean_col = array(
				'width_pct' => is_numeric( $col['width_pct'] ?? '' ) ? (int) $col['width_pct'] : '',
				'items'     => array(),
			);

			foreach ( (array) ( $col['items'] ?? array() ) as $mega_item ) {
				if ( ! is_array( $mega_item ) ) continue;
				$item_type = in_array( $mega_item['type'] ?? 'link', $allowed_types, true )
					? $mega_item['type'] : 'link';

				$clean_col['items'][] = array(
					'type'      => $item_type,
					'label'     => sanitize_text_field( $mega_item['label']     ?? '' ),
					'url'       => ( 'link' === $item_type || 'image' === $item_type )
									? esc_url_raw( $mega_item['url'] ?? '' ) : '',
					'icon'      => sanitize_text_field( $mega_item['icon']      ?? '' ),
					'desc'      => sanitize_text_field( $mega_item['desc']      ?? '' ),
					'image_id'  => absint( $mega_item['image_id']               ?? 0 ),
					'image_url' => esc_url_raw( $mega_item['image_url']         ?? '' ),
					// kses strips unsafe HTML in shortcode content.
					'content'   => wp_kses_post( $mega_item['content']          ?? '' ),
					'target'    => in_array( $mega_item['target'] ?? '', array( '', '_blank' ), true )
								   ? ( $mega_item['target'] ?? '' ) : '',
				);
			}

			$clean_cols[] = $clean_col;
		}

		return $clean_cols;
	}

	// ─────────────────────────────────────────────────────────────────
	// CSS generation — called from css-generator.php
	// ─────────────────────────────────────────────────────────────────

	public static function generate_css( $s = array() ) {
		$bg         = ! empty( $s['mega_bg'] )            ? sanitize_hex_color( $s['mega_bg'] ) : '#fff';
		$pad_y      = isset( $s['mega_padding_y'] )        && is_numeric( $s['mega_padding_y'] )    ? (int) $s['mega_padding_y']    : 24;
		$pad_x      = isset( $s['mega_padding_x'] )        && is_numeric( $s['mega_padding_x'] )    ? (int) $s['mega_padding_x']    : 32;
		$col_gap    = isset( $s['mega_col_gap'] )          && is_numeric( $s['mega_col_gap'] )       ? (int) $s['mega_col_gap']      : 16;
		$max_w      = isset( $s['mega_max_width'] )        && is_numeric( $s['mega_max_width'] )     ? (int) $s['mega_max_width']    : 0;
		$radius     = isset( $s['mega_border_radius'] )    && is_numeric( $s['mega_border_radius'] ) ? (int) $s['mega_border_radius'] : 14;
		$font_size  = isset( $s['mega_font_size'] )        && is_numeric( $s['mega_font_size'] )     && (int) $s['mega_font_size'] > 0 ? (int) $s['mega_font_size'] : 0;
		$link_color = ! empty( $s['mega_link_color'] )     ? sanitize_hex_color( $s['mega_link_color'] )    : '#374151';
		$head_color = ! empty( $s['mega_heading_color'] )  ? sanitize_hex_color( $s['mega_heading_color'] ) : '#9ca3af';
		$accent     = ! empty( $s['mega_accent_color'] )   ? sanitize_hex_color( $s['mega_accent_color'] )  : '#667eea';
		$mob_off    = ! empty( $s['mega_mobile_disable'] ) && $s['mega_mobile_disable'] === '1';
		$breakpoint = isset( $s['mobile_breakpoint'] )     && is_numeric( $s['mobile_breakpoint'] ) ? (int) $s['mobile_breakpoint'] : 768;

		$max_w_rule    = $max_w > 0 ? 'max-width:' . $max_w . 'px;margin-left:auto;margin-right:auto;' : '';
		$font_size_rule = $font_size > 0 ? 'font-size:' . $font_size . 'px;' : '';

		// Derive hover colors from accent
		$hover_bg    = 'rgba(102,126,234,.08)';
		$hover_color = $accent;

		$mobile_css = $mob_off
			? '@media(max-width:' . $breakpoint . 'px){.menux-mega-panel{display:none!important;}}'
			: '@media(max-width:' . $breakpoint . 'px){
  .menux-list > li.menux-has-mega > .menux-mega-panel{
    position:static;opacity:1;visibility:visible;pointer-events:auto;
    transform:none;box-shadow:none;border:none;border-radius:0;
    background:transparent;max-width:none;
  }
  .menux-mega-inner{flex-direction:column;padding:8px 0 8px 16px;gap:0;}
  .menux-mega-col{min-width:0;padding:0 0 8px 0;}
  .menux-mega-col+.menux-mega-col{border-top:1px solid #f3f4f6;border-left:none;padding-top:8px;}
  .menux-mega-heading{font-size:10px;margin-bottom:4px;}
  .menux-mega-link{padding:5px 0;}
  .menux-mega-image{display:none;}
  .menux-mega-widget{display:none;}
}';

		return '
/* ── Mega Menu ─────────────────────────────────────── */
/* Higher specificity than .menux-list li{position:relative} so the panel
   resolves left:0/right:0 against .menux-container, not the <li>. */
.menux-list > li.menux-has-mega{position:static;}
.menux-mega-panel{
  position:absolute;left:0;right:0;top:100%;
  background:' . esc_attr( $bg ) . ';
  ' . $font_size_rule . '
  box-shadow:0 16px 48px -8px rgba(0,0,0,.18),0 2px 8px rgba(0,0,0,.06);
  border-top:2px solid rgba(0,0,0,.05);
  border-radius:0 0 ' . $radius . 'px ' . $radius . 'px;
  opacity:0;visibility:hidden;pointer-events:none;
  transform:translateY(-10px);
  transition:opacity .2s ease,visibility .2s ease,transform .2s ease;
  z-index:9990;
}
.menux-list > li.menux-has-mega.menux-open > .menux-mega-panel{
  opacity:1;visibility:visible;pointer-events:auto;transform:none;
}
.menux-mega-inner{
  display:flex;gap:' . $col_gap . 'px;
  padding:' . $pad_y . 'px ' . $pad_x . 'px;
  ' . $max_w_rule . '
}
.menux-mega-col{flex:1;min-width:0;}
.menux-mega-col+.menux-mega-col{padding-left:' . $col_gap . 'px;}
.menux-mega-heading{
  font-size:10px;font-weight:700;color:' . esc_attr( $head_color ) . ';
  text-transform:uppercase;letter-spacing:.7px;
  margin:0 0 10px;padding:0 0 8px;
  border-bottom:1px solid rgba(0,0,0,.07);
}
.menux-mega-link{
  display:flex;align-items:flex-start;gap:10px;
  padding:7px 10px;margin:0 -10px;
  text-decoration:none;color:' . esc_attr( $link_color ) . ';
  border-radius:8px;transition:background .15s,color .15s;
}
.menux-mega-link:hover{background:' . $hover_bg . ';color:' . esc_attr( $hover_color ) . ';}
.menux-mega-link:hover i{color:' . esc_attr( $hover_color ) . ';}
.menux-mega-link i{font-size:15px;flex-shrink:0;margin-top:2px;color:' . esc_attr( $accent ) . ';transition:color .15s;}
.menux-mega-link-inner{display:flex;flex-direction:column;}
.menux-mega-link-label{font-size:13px;font-weight:500;line-height:1.3;}
.menux-mega-link-desc{font-size:11px;color:#9ca3af;margin-top:2px;line-height:1.4;}
.menux-mega-divider{border:none;border-top:1px solid rgba(0,0,0,.07);margin:8px 0;}
.menux-mega-image img{width:100%;height:auto;border-radius:8px;display:block;}
.menux-mega-widget{font-size:13px;line-height:1.5;}
' . $mobile_css . '
';
	}
}
