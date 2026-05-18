=== Giuliomax Menu Builder ===
Contributors: giuliomax
Tags: menu, navigation, hamburger menu, mega menu, responsive menu, shortcode
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful and easy-to-use menu plugin. Build any menu — including full Mega Menus — via the [menux] shortcode with icons, roles, submenus, mobile modes and multilingual support.

== Description ==

**Giuliomax Menu Builder** lets you create fully customized navigation menus and embed them anywhere using the `[menux]` shortcode. Build your menu visually from the WordPress admin, style it with 20 preset themes or full manual controls, and it works perfectly on desktop, tablet and mobile.

Starting from **v2.3**, it ships a complete **Mega Menu system** — a feature typically locked behind premium plugins — for free.

= ✨ Main Features =

* **Visual menu builder** — drag-and-drop interface to add pages, custom links, icons, badges, notification dots and submenus up to 3 levels
* **Page + custom link support** — use existing WP pages or external URLs, with target controls and role visibility
* **Advanced item targeting** — show/hide items by user role, login state, device, current page, schedule, time range or UTM source
* **Menu location assignment** — tag items for Primary, Footer, Sidebar or Mobile and render with `[menux location="..."]`
* **20 preset themes** — professionally designed themes with one-click apply and live preview: Ghost, Void Dark, Indigo Pulse, Aurora Night, Navy Command, Tropical Bloom, Evergreen, Cyber Matrix, Rose Silk, Sky Fresh, Newspaper Ink, Dev Dark, Ocean Electric, Violet Cloud, Swiss Pro, Cosmic Dusk, Warm Honey, Cloud Glass, Carbon Pro, Aegean Teal
* **Responsive mobile menu** — automatic fit-based breakpoint or manual pixel threshold
* **4 mobile opening modes** — Dropdown, Fullscreen overlay, Drawer left, Drawer right
* **Hamburger style controls** — Classic, Modern or Minimal icon appearance, alignment and colors
* **Link shape** — configurable border-radius for pill-style or sharp links
* **Sticky header** — fixed menu on scroll with background, shadow, alignment, shrink and auto-hide behavior
* **Scroll progress bar** — page reading indicator that works on sticky or normal menus
* **Dark mode support** — Light, Dark or Auto (follows OS preference)
* **Typography & layout** — Google Fonts, fallback font, size, weight, letter spacing, text transform and items alignment
* **Link animations** — hover effects like Lift, Scale, Pulse, Bounce, Shake, Glow and Underline
* **Entrance animations** — Fade, Slide, Zoom, Flip with duration, delay and stagger controls
* **Submenu styling** — background, border, link color, shadow and animation controls
* **Logo support** — add and position a logo image directly inside the menu bar
* **WP Menu Integration** — intercept classic theme `wp_nav_menu()` calls and replace them with MenuX output
* **Import / Export** — save and restore menu configurations as JSON
* **Full reset** — one-click button to delete all saved items, styles and settings
* **Multilingual ready** — WPML, Polylang and TranslatePress support with language-specific item labels
* **Live preview** — real-time desktop/tablet/mobile preview while building the menu

= ⚡ Mega Menu (free) =

Build rich, full-width dropdown panels for any first-level navigation item — the kind of feature most plugins charge for.

* **Up to 4 columns** per panel, each independently sized (% width or auto)
* **5 column item types**: clickable links, section headings, dividers, images, and arbitrary shortcodes / Gutenberg blocks
* **Icon + description** on link items — give users context at a glance (e.g. "Dashboard — Analytics & reports")
* **Icon on headings** — visually group sections with a Font Awesome icon
* **Click-to-pick icon picker** — browse and search Font Awesome 6 Free icons directly from the editor; no class names to remember
* **Drag & drop reordering** of items within each column
* **Independent per-item toggle** — enable Mega Menu on one or multiple nav items independently
* **Full-width panel** that anchors to the navigation container, not to the individual link
* **Gradient backgrounds** — 12 ready-made gradient presets (Dark, Purple, Indigo, Sky, Teal, Emerald, Sunset, Rose, Gold, Charcoal…) plus a custom gradient builder with direction, two color pickers and live apply
* **Panel appearance controls** — padding, column gap, border radius, font size, link color, heading color, accent color
* **Smooth hover** — 200 ms grace timer ensures the panel never disappears when moving the mouse from the trigger link to the panel content
* **Mobile-aware** — collapses inline on small screens, or can be disabled on mobile entirely
* **6 demo templates** — one-click starting points to get up and running in seconds:
  * SaaS Product (4 cols, dark gradient — Features, Docs, Company, CTA)
  * E-Commerce (3 cols, white — Categories, Deals, Account)
  * Creative Agency (3 cols, purple gradient — Services, Portfolio, Studio)
  * News & Blog (3 cols, light — Topics, Sections, Connect)
  * Corporate (3 cols, sky gradient — Solutions, Industries, Company)
  * Restaurant (3 cols, warm gradient — Menu, Locations, Reserve)
* **Toggleable live preview** — click "👁 Preview" in the editor toolbar to see an approximation of the panel below the editor, without leaving the page

= 🎨 Style Options =

* Colors: background (solid or gradient), links, hover state (solid or gradient), active state (solid or gradient), submenus, hamburger
* Typography: Google Fonts, font family, size, weight, letter spacing, text transform
* Layout: gap, horizontal padding, vertical padding, items alignment, link border-radius
* Mobile: breakpoint, opening mode, overlay color/opacity/blur, drawer width, open animation
* Sticky: background, shadow, padding, alignment, transition, shrink effect, auto-hide on scroll
* Mega Menu: background (solid or gradient), padding, gap, radius, font size, link color, heading color, accent color, mobile behavior

= 🔌 Usage =

1. Go to **Giuliomax Menu Builder** in the WordPress admin sidebar
2. Build your menu items using the visual drag-and-drop builder
3. Apply a preset theme or configure colors and style manually
4. To add a Mega Menu: go to the **Mega Menu** tab, enable it on a nav item, click **Edit Columns**, add columns and items
5. Place `[menux]` in any page, post, widget or template

= 🚀 Pro Version =

The **Pro** version adds:

* 📊 **Click statistics** — track which items users click, by device, role and date
* 🔍 **Search bar** — instant full-text search modal with keyboard navigation
* ♿ **Accessibility panel** — WCAG 2.1 tools (focus outline, skip link, reduced motion, high contrast, ARIA labels)
* 🎨 **Custom CSS** — write your own CSS rules directly from the admin panel
* 📄 **Multiple menus** — create and manage independent menu configurations for different pages or locations

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Go to **MenuX** in the admin menu
4. Build your menu and copy the shortcode `[menux]`
5. Paste `[menux]` wherever you want the menu to appear

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher

== Frequently Asked Questions ==

= How do I display the menu? =

Use the shortcode `[menux]` in any page, post, widget or block. For multiple locations on the same site use `[menux location="footer"]` etc. and assign items to that location in the builder.

= Can I have different menus for different pages? =

Not in the free version. Use role-based visibility or per-item device/page conditionals to adapt the menu per context. Full multiple-menu support is available in the Pro version.

= Does it work with page builders? =

Yes. You can insert `[menux]` as a shortcode block in Gutenberg, Elementor, Divi, WPBakery and most other builders.

= Does it work with caching plugins? =

Yes. The menu HTML is generated server-side and compatible with WP Rocket, LiteSpeed Cache, W3 Total Cache and similar plugins.

= How do I add Font Awesome icons? =

In the menu builder, each item has an icon field. Type the FA class name, e.g. `fa-solid fa-house`, or use the **🎨 Pick** button to browse and search icons visually. Font Awesome 6 Free is loaded automatically.

= Is it compatible with WPML / Polylang / TranslatePress? =

Yes. This plugin automatically detects installed multilingual plugins and lets you set a label per language for each menu item.

= Where is the mobile breakpoint? =

Go to **MenuX → Style → Mobile** and set your preferred pixel breakpoint, or choose Auto to detect based on available space. Default is 768px.

= How do I use the preset themes? =

In the admin panel click **Choose Theme** (top bar). Browse the 20 preset themes, hover to preview and click **Apply** to try one live. Click **Apply & Close** to keep it, or **Cancel** to revert.

= How does the Mega Menu work? =

Go to the **Mega Menu** tab, enable the toggle next to a first-level nav item, then click **Edit Columns**. Add up to 4 columns, fill them with headings, links (with icon and description), dividers, images or shortcodes, then click **Save columns**. Not sure where to start? Hit **✨ Demo** to load one of 6 ready-made templates.

= Does the WP Menu Integration work with all themes? =

It works with classic PHP themes that call `wp_nav_menu()` directly (e.g. GeneratePress, OceanWP, Neve, Kadence). Themes that use a custom header builder (e.g. Astra Header Builder) bypass `wp_nav_menu()` internally, so the integration cannot intercept them — use the shortcode approach instead.

= Can I reset everything and start fresh? =

Yes. Go to **MenuX → Tools → Reset Everything** and confirm. This permanently deletes all menu items, styles and integration settings.

= Can I use custom CSS? =

Custom CSS editing is available in the Pro version. In the free version you can target the `.menux-container` wrapper and its child elements from your theme's stylesheet.

== Screenshots ==

1. Main admin panel with visual menu builder
2. Preset themes modal with live preview
3. Style panel — Colors tab
4. Style panel — Layout and spacing options
5. Mega Menu editor — column builder with live preview
6. Mega Menu demo templates picker
7. Live preview with desktop/tablet/mobile switcher
8. Frontend result — desktop view with mega menu open
9. Frontend result — mobile fullscreen menu open

== External Services ==

This plugin may connect to the following external service:

**Google Fonts** (optional)

If a Google Font name is entered in the Style panel (Typography tab), the plugin loads that font's stylesheet from Google's servers at `https://fonts.googleapis.com`. This request is made only when a Google Font is explicitly configured by the site administrator. The font name and the visitor's IP address are sent to Google as part of the standard HTTP request.

* Terms of service: https://developers.google.com/terms
* Privacy policy: https://policies.google.com/privacy

Font Awesome icons are bundled locally within the plugin and do **not** load from any external server.

== Changelog ==

= 2.3.6 =
* Mega Menu editor: preview panel moved below the column editor and hidden by default — toggle via the new "👁 Preview" button in the toolbar, giving columns full horizontal space

= 2.3.5 =
* Added **6 demo templates** for the Mega Menu editor (SaaS, E-Commerce, Agency, Blog, Corporate, Restaurant) — each loads a full column layout with headings, icons and descriptions in one click via the new "✨ Demo" toolbar button

= 2.3.4 =
* Fixed icon picker appearing behind the mega menu item edit form (z-index corrected)

= 2.3.3 =
* Fixed multi-item mega menu: enabling mega on more than one nav item now works reliably (POST structure changed from positional index to item_key)
* Fixed mega panel disappearing when moving the mouse from the trigger link to the panel — replaced CSS `:hover` with a JS 200 ms grace timer

= 2.3.2 =
* Mega Menu: added gradient background picker with 12 presets and custom gradient builder
* Mega Menu: added font size, link color, heading color and accent/icon color controls
* Mega Menu: default mobile behavior changed to "disabled on mobile"
* Mega Menu: icon picker (click to browse Font Awesome 6 Free) added to all item types that support icons
* Mega Menu: drag & drop reordering of items within columns

= 2.3.1 =
* Mega Menu: added icon support on heading items
* Mega Menu: removed column separator lines for a cleaner layout
* Mega Menu: added font size setting for the mega panel

= 2.3.0 =
* Introduced **Mega Menu** — full-width column panels for first-level nav items (free)
* Up to 4 columns per panel with 5 item types: link, heading, divider, image, shortcode
* Link items support icon (Font Awesome) + description text
* Panel appearance: background color, padding, column gap, border radius
* Full-width panel anchored to the nav container (CSS specificity fix)
* Mobile: mega panel collapses inline below the trigger link

= 2.2.0 =
* Added 20 professionally designed preset themes with one-click apply and live preview
* Added link border-radius (pill/rounded shape) style control
* Added submenu background, border, link color, shadow and animation controls
* Added WP Menu Integration panel (replaces classic theme wp_nav_menu() calls)
* Added full Reset button (deletes all items, styles and settings)
* Fixed hamburger alignment — now correctly uses margin-based horizontal positioning
* Fixed live preview not applying border-radius, hover/active backgrounds
* Fixed preset theme apply not correctly setting radio button fields (mobile mode, entrance animation, dark mode)
* Fixed form validation errors on hidden number inputs (novalidate)
* Fixed gradient fields not saving or rendering on frontend
* Default items alignment changed to Right (flex-end)
* Default mobile opening mode changed to Fullscreen on all preset themes

= 2.1.2 =
* Removed Custom CSS textarea from the Style panel to comply with WordPress.org plugin guidelines
* Removed ipapi.co external call (click-tracking country detection is a Pro-only feature)
* Updated External Services documentation in readme

= 2.1.1 =
* Initial release of Giuliomax Menu Builder
* Includes all core features: builder, 4 mobile modes, sticky, scroll progress bar, entrance animations, import/export, multilingual

== Upgrade Notice ==

= 2.3.6 =
Mega Menu improvements: preview now toggled via button below the editor for a cleaner workspace.

= 2.3.5 =
Added 6 mega menu demo templates — load a full column layout in one click.

= 2.3.0 =
Major update: full Mega Menu system added for free. Supports up to 4 columns, 5 item types, gradient backgrounds, mobile handling and more.

= 2.2.0 =
Major update: 20 preset themes, submenu controls, WP integration, reset button and multiple bug fixes. Recommended for all users.

= 2.1.2 =
Compliance update: removes the Custom CSS textarea and an undisclosed external service call.

= 2.1.1 =
First public release.
