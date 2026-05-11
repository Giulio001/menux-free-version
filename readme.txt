=== MenuX Free ===
Contributors: giuliomax
Tags: menu, navigation, hamburger menu, responsive menu, shortcode
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful and easy-to-use menu plugin. Build any menu via the [menux] shortcode with icons, roles, submenus, mobile modes and multilingual support.

== Description ==

**MenuX Free** lets you create fully customized navigation menus and embed them anywhere using the `[menux]` shortcode. Build your menu visually from the WordPress admin, style it with colors and typography, and it works perfectly on desktop, tablet and mobile.

= ✨ Main Features =

* **Visual menu builder** — drag-and-drop interface to add pages, custom links and submenus (up to 3 levels deep)
* **Hamburger mobile menu** — automatic hamburger button below the breakpoint you choose
* **4 mobile opening modes** — Dropdown, Fullscreen overlay, Drawer left, Drawer right
* **Font Awesome icons** — add any FA icon to each menu item
* **Role-based visibility** — show/hide items for guests, logged-in users or specific WordPress roles
* **Scheduled items** — set a start and end date/time for each menu item
* **Badges** — add colored badge labels to items (e.g. "New", "Hot")
* **Notification dot** — animated red dot indicator on any item
* **Submenus** — nested menus up to 3 levels with smooth animations
* **Dark mode support** — Light, Dark or Auto (follows OS preference)
* **Sticky menu** — keep the menu fixed at the top on scroll, with optional shrink effect
* **Scroll progress bar** — reading progress indicator on the sticky menu
* **Entrance animations** — Fade, Slide, Zoom, Flip and more
* **Link animations** — Pulse, Shake, Bounce, Glow, Lift, Scale, Underline on hover
* **Custom CSS** — write your own CSS rules directly from the panel
* **Import / Export** — backup and restore your menu configuration as JSON
* **Multilingual** — native support for WPML, Polylang and TranslatePress
* **Live preview** — real-time desktop/tablet/mobile preview in the admin

= 🎨 Style Options =

* Colors: background, links, hover, active state, submenus, last item
* Typography: Google Fonts, font family, size, weight, letter spacing, text transform
* Layout: gap, padding, item alignment, push-last-right
* Mobile: breakpoint, overlay color and opacity, blur, drawer width, open animation

= 🔌 Usage =

1. Go to **MenuX** in the WordPress admin sidebar
2. Build your menu items
3. Configure colors and style
4. Place `[menux]` in any page, post, widget or template

= 🚀 Pro Version =

The **MenuX Pro** version adds:

* 📊 **Click statistics** — track which items users click, by device, role and date
* 🖼️ **Logo** — add and position a logo image inside the menu
* 🔍 **Search bar** — instant full-text search modal with keyboard navigation
* ♿ **Accessibility panel** — WCAG 2.1 tools (focus outline, skip link, reduced motion, high contrast, ARIA labels)
* 🌈 **Gradients** — CSS gradient backgrounds for container, hover and active states

== Installation ==

1. Upload the `menux-free` folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Go to **MenuX** in the admin menu
4. Build your menu and copy the shortcode `[menux]`
5. Paste `[menux]` wherever you want the menu to appear

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher

== Frequently Asked Questions ==

= How do I display the menu? =

Use the shortcode `[menux]` in any page, post, widget or block. The same menu is shown everywhere; for multiple menus in the same site, use the Import/Export feature to manage separate configurations.

= Can I have different menus for different pages? =

Not in the free version. Use the Custom CSS panel or role-based visibility to adapt the menu per context.

= Does it work with page builders? =

Yes. You can insert `[menux]` as a shortcode block in Gutenberg, Elementor, Divi, WPBakery and most other builders.

= Does it work with caching plugins? =

Yes. The menu HTML is generated server-side and compatible with WP Rocket, LiteSpeed Cache, W3 Total Cache and similar plugins.

= How do I add Font Awesome icons? =

In the menu builder, each item has an icon field. Type the FA class name, e.g. `fa-solid fa-house`. The Font Awesome 6 library is loaded automatically.

= Is it compatible with WPML / Polylang / TranslatePress? =

Yes. MenuX Free automatically detects installed multilingual plugins and lets you set a label per language for each menu item.

= Where is the mobile breakpoint? =

Go to **MenuX → Style → Layout → Sticky & Advanced** and set your preferred pixel breakpoint. Default is 768px.

= Can I use custom CSS? =

Yes, use the **Style → Advanced** tab for custom CSS rules. Quick-snippets are provided as shortcuts for the most common tweaks.

== Screenshots ==

1. Main admin panel with visual menu builder
2. Style panel — Colors tab
3. Style panel — Layout and sticky options
4. Live preview with desktop/tablet/mobile switcher
5. Frontend result — desktop view
6. Frontend result — mobile hamburger menu open

== External Services ==

This plugin may connect to the following external service:

**Google Fonts** (optional)
If you configure a Google Font in the Style panel (Typography tab), the plugin loads the font stylesheet from Google's servers:
`https://fonts.googleapis.com`

This request is made only when a Google Font is explicitly selected by the site administrator. No data is sent by default. By using this feature you agree to Google's privacy policy: https://policies.google.com/privacy

Font Awesome icons are bundled locally within the plugin and do **not** load from any external server.

== Changelog ==

= 2.1.1 =
* Initial release of MenuX Free
* Includes all core features: builder, 4 mobile modes, sticky, scroll progress bar, entrance animations, import/export, multilingual

== Upgrade Notice ==

= 2.1.1 =
First public release. No upgrade needed.
