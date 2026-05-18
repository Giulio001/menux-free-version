<?php
/**
 * MenuX — Advanced Logo System
 *
 * Handles multi-context logo rendering (desktop, dark mode, sticky, mobile)
 * with SVG inline support, responsive variants and backward-compat with the
 * legacy menux_style[logo_url] field.
 *
 * @package GiuliomaxMenuBuilder
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Menux_Logo {

	/** Cached settings array (cleared on save). */
	private static $settings = null;

	// ─────────────────────────────────────────────────────────────────
	// Schema / defaults
	// ─────────────────────────────────────────────────────────────────

	public static function defaults() {
		return array(
			// Attachment IDs — preferred, enables wp_get_attachment_image + srcset.
			'desktop_id'       => 0,
			'desktop_url'      => '',   // External URL fallback
			'desktop_dark_id'  => 0,
			'desktop_dark_url' => '',
			'sticky_id'        => 0,
			'sticky_url'       => '',
			'mobile_id'        => 0,
			'mobile_url'       => '',
			// Sizing
			'width'            => '120',
			'width_unit'       => 'px',  // px | % | em | rem | vw
			'max_width'        => '',
			'max_width_unit'   => 'px',
			'height'           => '',    // empty = auto
			'height_unit'      => 'px',
			// Spacing
			'margin'           => '0 16px 0 0',
			'padding'          => '4px 0',
			// Alignment
			'vertical_align'   => 'center',  // flex-start | center | flex-end
			// Link
			'link_enabled'     => '1',
			'link_url'         => '',    // empty = home_url('/')
			'link_target'      => '',    // '' | '_blank'
			// Accessibility
			'alt'              => '',
			// Position inside nav container
			'position'         => 'left',  // left | right | center | center-split
			// Responsive
			'hide_on_mobile'   => '0',
		);
	}

	public static function get_settings() {
		if ( null === self::$settings ) {
			$saved          = get_option( 'menux_logo', array() );
			self::$settings = wp_parse_args( (array) $saved, self::defaults() );
		}
		return self::$settings;
	}

	// ─────────────────────────────────────────────────────────────────
	// Rendering — public entry point
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Returns the logo HTML for the given context.
	 *
	 * @param bool $is_mobile  True when building the mobile-only variant.
	 * @param bool $is_sticky  True when building the sticky-context variant.
	 */
	public static function render( $is_mobile = false, $is_sticky = false ) {
		$logo = self::get_settings();

		// Resolve which source to use for this context.
		$attachment_id = 0;
		$logo_url      = '';

		if ( $is_sticky && (int) $logo['sticky_id'] > 0 ) {
			$attachment_id = (int) $logo['sticky_id'];
		} elseif ( $is_sticky && ! empty( $logo['sticky_url'] ) ) {
			$logo_url = $logo['sticky_url'];
		} elseif ( $is_mobile && (int) $logo['mobile_id'] > 0 ) {
			$attachment_id = (int) $logo['mobile_id'];
		} elseif ( $is_mobile && ! empty( $logo['mobile_url'] ) ) {
			$logo_url = $logo['mobile_url'];
		} elseif ( (int) $logo['desktop_id'] > 0 ) {
			$attachment_id = (int) $logo['desktop_id'];
		} elseif ( ! empty( $logo['desktop_url'] ) ) {
			$logo_url = $logo['desktop_url'];
		} else {
			// Backward compat: check legacy menux_style[logo_url].
			$style    = get_option( 'menux_style', array() );
			$logo_url = $style['logo_url'] ?? '';
		}

		if ( ! $attachment_id && ! $logo_url ) {
			return '';
		}

		$alt = ! empty( $logo['alt'] ) ? $logo['alt'] : (string) get_bloginfo( 'name' );

		// Build image markup.
		if ( $attachment_id ) {
			$mime     = (string) get_post_mime_type( $attachment_id );
			$img_html = ( $mime === 'image/svg+xml' )
				? self::render_svg_inline( $attachment_id, $alt )
				: self::render_attachment( $attachment_id, $alt, $logo, $is_sticky, $is_mobile );
		} else {
			$img_html = self::render_url( $logo_url, $alt );
		}

		// CSS classes on the wrapper.
		$css_classes = array( 'menux-logo' );
		$css_classes[] = $is_mobile ? 'menux-logo-mobile' : 'menux-logo-desktop';

		// Wrap in an anchor or a span.
		$link_enabled = ( $logo['link_enabled'] ?? '1' ) === '1';
		if ( $link_enabled ) {
			$href   = ! empty( $logo['link_url'] ) ? esc_url( $logo['link_url'] ) : esc_url( home_url( '/' ) );
			$target = ( ! empty( $logo['link_target'] ) && $logo['link_target'] === '_blank' )
				? ' target="_blank" rel="noopener noreferrer"' : '';
			return '<a href="' . $href . '" class="' . esc_attr( implode( ' ', $css_classes ) ) . '"'
				. ' aria-label="' . esc_attr( $alt ) . '"' . $target . '>'
				. $img_html . '</a>';
		}

		return '<span class="' . esc_attr( implode( ' ', $css_classes ) ) . '"'
			. ' aria-label="' . esc_attr( $alt ) . '">'
			. $img_html . '</span>';
	}

	// ─────────────────────────────────────────────────────────────────
	// Private rendering helpers
	// ─────────────────────────────────────────────────────────────────

	/** Renders a WP attachment with srcset and optional dark-mode variant. */
	private static function render_attachment( $id, $alt, $logo, $is_sticky, $is_mobile ) {
		$img_attrs = array(
			'class'    => 'menux-logo-img',
			'alt'      => $alt,
			'loading'  => 'eager',   // Logo is above the fold — no lazy loading.
			'decoding' => 'async',
		);
		$light_img = wp_get_attachment_image( $id, 'full', false, $img_attrs );

		// Dark variant only for default desktop logo.
		$dark_id = (int) ( $logo['desktop_dark_id'] ?? 0 );
		if ( $dark_id > 0 && ! $is_sticky && ! $is_mobile ) {
			$dark_attrs          = $img_attrs;
			$dark_attrs['class'] = 'menux-logo-img menux-logo-dark-img';
			$dark_img            = wp_get_attachment_image( $dark_id, 'full', false, $dark_attrs );
			if ( $dark_img ) {
				return '<span class="menux-logo-light-wrap">' . $light_img . '</span>'
					. '<span class="menux-logo-dark-wrap">'  . $dark_img  . '</span>';
			}
		}

		return $light_img;
	}

	/** Renders an external URL as a plain <img> tag. */
	private static function render_url( $url, $alt ) {
		return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt )
			. '" class="menux-logo-img" loading="eager" decoding="async">';
	}

	/**
	 * Reads the SVG file from disk and injects accessibility attributes inline.
	 * Falls back to an <img> tag if the file cannot be read or inline is not requested.
	 *
	 * @param int    $id      Attachment ID.
	 * @param string $alt     Alt text.
	 * @param bool   $inline  True to return inline SVG; false to return <img> pointing to the URL.
	 */
	private static function render_svg_inline( $id, $alt, $inline = true ) {
		$url = (string) wp_get_attachment_url( $id );

		if ( ! $inline ) {
			return self::render_url( $url, $alt );
		}

		$path = get_attached_file( $id );
		if ( ! $path || ! file_exists( $path ) ) {
			return self::render_url( $url, $alt );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$svg = file_get_contents( $path );
		if ( ! $svg ) {
			return self::render_url( $url, $alt );
		}
		$svg = preg_replace( '/<\?xml[^>]*\?>/i', '', $svg );
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/i',  '', $svg );
		$svg = trim( $svg );
		// Inject role + aria-label + focusable="false" on the <svg> root.
		$svg = preg_replace(
			'/<svg(\s)/i',
			'<svg role="img" aria-label="' . esc_attr( $alt ) . '" focusable="false"$1',
			$svg,
			1
		);
		// Insert <title> as first child element (screen readers prefer it over aria-label alone).
		$title_tag = '<title>' . esc_html( $alt ) . '</title>';
		$svg = preg_replace( '/(<svg[^>]*>)/i', '$1' . $title_tag, $svg, 1 );

		return '<span class="menux-logo-svg">' . $svg . '</span>';
	}

	/**
	 * Returns allowed HTML tags for wp_kses when outputting logo HTML.
	 * Needed because SVG elements are not in wp_kses_post's allowlist.
	 */
	public static function kses_allowed_tags() {
		$post_tags = wp_kses_allowed_html( 'post' );
		$svg_tags  = array(
			'svg'    => array( 'xmlns' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'viewBox' => true, 'fill' => true, 'role' => true, 'aria-label' => true, 'focusable' => true, 'class' => true, 'style' => true ),
			'title'  => array(),
			'path'   => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-rule' => true, 'clip-rule' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
			'rect'   => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ),
			'g'      => array( 'fill' => true, 'transform' => true ),
			'use'    => array( 'href' => true, 'xlink:href' => true ),
			'defs'   => array(),
			'symbol' => array( 'id' => true, 'viewbox' => true, 'viewBox' => true ),
			'span'   => array( 'class' => true, 'style' => true, 'aria-label' => true, 'aria-hidden' => true ),
			'a'      => array( 'href' => true, 'class' => true, 'aria-label' => true, 'target' => true, 'rel' => true ),
			'img'    => array( 'src' => true, 'alt' => true, 'class' => true, 'loading' => true, 'decoding' => true, 'width' => true, 'height' => true, 'srcset' => true, 'sizes' => true ),
		);
		return array_merge( $post_tags, $svg_tags );
	}

	// ─────────────────────────────────────────────────────────────────
	// Save
	// ─────────────────────────────────────────────────────────────────

	public static function save( $raw ) {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$allowed_units = array( 'px', '%', 'em', 'rem', 'vw', 'vh' );

		update_option( 'menux_logo', array(
			'desktop_id'       => absint( $raw['desktop_id']        ?? 0 ),
			'desktop_url'      => esc_url_raw( $raw['desktop_url']      ?? '' ),
			'desktop_dark_id'  => absint( $raw['desktop_dark_id']   ?? 0 ),
			'desktop_dark_url' => esc_url_raw( $raw['desktop_dark_url'] ?? '' ),
			'sticky_id'        => absint( $raw['sticky_id']         ?? 0 ),
			'sticky_url'       => esc_url_raw( $raw['sticky_url']       ?? '' ),
			'mobile_id'        => absint( $raw['mobile_id']         ?? 0 ),
			'mobile_url'       => esc_url_raw( $raw['mobile_url']       ?? '' ),
			'width'            => is_numeric( $raw['width']     ?? '' ) ? (string) $raw['width']     : '120',
			'width_unit'       => in_array( $raw['width_unit']    ?? 'px', $allowed_units, true ) ? $raw['width_unit']    : 'px',
			'max_width'        => is_numeric( $raw['max_width']  ?? '' ) ? (string) $raw['max_width']  : '',
			'max_width_unit'   => in_array( $raw['max_width_unit'] ?? 'px', $allowed_units, true ) ? $raw['max_width_unit'] : 'px',
			'height'           => is_numeric( $raw['height']    ?? '' ) ? (string) $raw['height']    : '',
			'height_unit'      => in_array( $raw['height_unit']   ?? 'px', $allowed_units, true ) ? $raw['height_unit']   : 'px',
			'margin'           => self::sanitize_spacing( $raw['margin']  ?? '0 16px 0 0' ),
			'padding'          => self::sanitize_spacing( $raw['padding'] ?? '4px 0' ),
			'vertical_align'   => in_array( $raw['vertical_align'] ?? 'center', array( 'flex-start', 'center', 'flex-end' ), true )
				? $raw['vertical_align'] : 'center',
			'link_enabled'     => ! empty( $raw['link_enabled'] ) ? '1' : '0',
			'link_url'         => esc_url_raw( $raw['link_url'] ?? '' ),
			'link_target'      => in_array( $raw['link_target'] ?? '', array( '', '_blank' ), true ) ? ( $raw['link_target'] ?? '' ) : '',
			'alt'              => sanitize_text_field( $raw['alt'] ?? '' ),
			'position'         => in_array( $raw['position'] ?? 'left', array( 'left', 'right', 'center', 'center-split' ), true )
				? $raw['position'] : 'left',
			'hide_on_mobile'   => ! empty( $raw['hide_on_mobile'] ) ? '1' : '0',
		) );

		self::$settings = null;
	}

	/** Strips anything that is not digits, letters, spaces, dots, or %. */
	private static function sanitize_spacing( $value ) {
		return preg_replace( '/[^0-9a-zA-Z .%]/', '', (string) $value );
	}

	// ─────────────────────────────────────────────────────────────────
	// CSS generation — called from css-generator.php
	// ─────────────────────────────────────────────────────────────────

	public static function generate_css() {
		$logo = self::get_settings();
		$css  = '';

		$wu    = in_array( $logo['width_unit']     ?? 'px', array( 'px', '%', 'em', 'rem', 'vw' ), true ) ? $logo['width_unit'] : 'px';
		$w     = is_numeric( $logo['width'] ?? '' ) ? $logo['width'] . $wu : '120px';

		$hu    = in_array( $logo['height_unit']    ?? 'px', array( 'px', 'em', 'rem', 'vh' ), true ) ? $logo['height_unit'] : 'px';
		$h     = is_numeric( $logo['height'] ?? '' ) ? $logo['height'] . $hu : '';
		$h_css = $h ? 'height:' . $h . ';' : 'height:auto;';

		$mwu   = in_array( $logo['max_width_unit'] ?? 'px', array( 'px', '%', 'em', 'rem' ), true ) ? $logo['max_width_unit'] : 'px';
		$mw    = is_numeric( $logo['max_width'] ?? '' ) ? 'max-width:' . $logo['max_width'] . $mwu . ';' : '';

		$valign  = in_array( $logo['vertical_align'] ?? 'center', array( 'flex-start', 'center', 'flex-end' ), true ) ? $logo['vertical_align'] : 'center';
		$margin  = self::sanitize_spacing( $logo['margin']  ?? '0 16px 0 0' );
		$padding = self::sanitize_spacing( $logo['padding'] ?? '4px 0' );

		$css .= '.menux-logo{display:inline-flex;align-items:' . $valign . ';flex-shrink:0;'
			. 'text-decoration:none;margin:' . $margin . ';padding:' . $padding . ';}';

		$css .= '.menux-logo .menux-logo-img'
			. '{width:' . $w . ';' . $h_css . $mw . 'display:block;}';

		$css .= '.menux-logo-svg{display:inline-flex;width:' . $w . ';' . $h_css . '}'
			. '.menux-logo-svg svg{width:100%;height:auto;display:block;}';

		// Dark-mode logo switching.
		$has_dark = ! empty( $logo['desktop_dark_id'] ) || ! empty( $logo['desktop_dark_url'] );
		if ( $has_dark ) {
			$css .= '.menux-logo .menux-logo-dark-wrap{display:none;}';
			// Explicit dark via data attribute.
			$css .= '[data-bm-theme="dark"] .menux-logo .menux-logo-light-wrap,'
				. '.menux-container[data-bs-theme="dark"] .menux-logo .menux-logo-light-wrap{display:none;}';
			$css .= '[data-bm-theme="dark"] .menux-logo .menux-logo-dark-wrap,'
				. '.menux-container[data-bs-theme="dark"] .menux-logo .menux-logo-dark-wrap{display:block;}';
			// OS-level preference.
			$css .= '@media(prefers-color-scheme:dark){'
				. '.menux-logo .menux-logo-light-wrap{display:none;}'
				. '.menux-logo .menux-logo-dark-wrap{display:block;}'
				. '}';
		}

		// Hide on mobile.
		if ( ! empty( $logo['hide_on_mobile'] ) && $logo['hide_on_mobile'] === '1' ) {
			$css .= '@media(max-width:768px){.menux-logo{display:none;}}';
		}

		// Separate mobile logo: hide desktop variant on small screens.
		$has_mobile = ! empty( $logo['mobile_id'] ) || ! empty( $logo['mobile_url'] );
		if ( $has_mobile ) {
			$css .= '.menux-logo-mobile{display:none;}';
			$css .= '@media(max-width:768px){'
				. '.menux-logo-desktop{display:none;}'
				. '.menux-logo-mobile{display:inline-flex;}'
				. '}';
		}

		return $css;
	}

	// ─────────────────────────────────────────────────────────────────
	// Admin helper — returns preview HTML for a slot (used in logo-panel.php)
	// ─────────────────────────────────────────────────────────────────

	public static function get_preview_img( $attachment_id, $url ) {
		if ( $attachment_id ) {
			return wp_get_attachment_image( (int) $attachment_id, array( 80, 40 ), false, array( 'style' => 'max-width:80px;max-height:40px;object-fit:contain;border-radius:4px;' ) );
		}
		if ( $url ) {
			return '<img src="' . esc_url( $url ) . '" style="max-width:80px;max-height:40px;object-fit:contain;border-radius:4px;" alt="" loading="lazy">';
		}
		return '<span style="color:#9ca3af;font-size:11px;">No image</span>';
	}
}
