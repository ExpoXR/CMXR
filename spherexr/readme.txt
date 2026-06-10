=== SphereXR — Canvas Orb Animations ===
Contributors: ayalothman
Tags: animation, canvas, background, orbs, elementor
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.1.0
Requires PHP: 8.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Canvas-based orb background animations for WordPress. Attach to any element by CSS ID — no coding required.

== Description ==

SphereXR lets you build and manage animated orb backgrounds directly from the WordPress admin. Animations render on an HTML5 canvas, sit behind your page content, and are driven by a tiny vanilla-JS engine with zero frontend dependencies.

**Key features:**

* **Visual configurator** — three-panel editor with live canvas preview, shape/color/animation controls, and real-time feedback
* **Drag-to-reorder layers** — drag orbs in the sidebar to control which orb renders on top
* **Layer badges** — each orb shows its layer number (1 = topmost)
* **6 animation types** — Drift, Orbit, Pulse, Wave, Fixed, Figure-8 (Lissajous)
* **Interactivity modes** — Parallax, Repel, Attract, Follow cursor
* **4 shapes** — Circle, Double, Triple, Blob (with organic deformation)
* **Blend modes** — Screen, Normal, Multiply, Overlay, Lighten, Hard-Light
* **REST API** — full programmatic control over animations
* **Performance** — pauses off-screen (IntersectionObserver), respects `prefers-reduced-motion`, DPR cap to limit canvas size on HiDPI screens
* **Any theme, any builder** — works with Elementor, Divi, Gutenberg, or hand-coded HTML; just add a CSS ID

== Installation ==

= From ZIP =

1. Download the plugin ZIP
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. Activate the plugin

= Manual =

1. Extract the `spherexr` folder
2. Upload it to `/wp-content/plugins/`
3. Go to **Plugins** and activate SphereXR

= Usage =

1. Go to **SphereXR > New Animation** in the WordPress admin
2. Add orbs, configure colors, animation types, and sizes
3. Note the **Animation ID** (e.g., `hero-bg`)
4. Add that ID as a CSS ID on any element in your page
5. The animation renders automatically as the element's background

== Frequently Asked Questions ==

= Does this require Elementor? =

No. SphereXR works with any theme or page builder. Add the CSS ID to any HTML element or Elementor section.

= How do I control which orb appears on top? =

Drag orbs in the configurator's left sidebar. The orb at the top of the list renders above all others. The layer badge (number) shows each orb's stacking order.

= How do I attach an animation to an Elementor section? =

In Elementor, open the section settings → **Advanced** tab → **CSS ID** field. Enter the Animation ID shown in the configurator or dashboard.

= Why is the animation not showing? =

1. Verify the Animation ID is set in the configurator
2. Confirm the animation is set to **Active** on the dashboard
3. Confirm the CSS ID on your element exactly matches the Animation ID (case-sensitive)
4. Check the **Debug** page (SphereXR > Debug) for registered animations and asset URLs

= Can I have multiple animations on one page? =

Yes. Each animation targets a unique CSS ID. Add as many as you need.

= Does it affect page performance? =

The engine pauses automatically when the animated element is scrolled off-screen. It also respects the browser's `prefers-reduced-motion` setting and caps the device pixel ratio (configurable in Settings) to control canvas resolution on HiDPI displays.

= Is there a limit on orbs per animation? =

Up to 20 orbs per animation.

== Screenshots ==

1. Dashboard — list of animations with status, orb count, and action buttons
2. Configurator — three-panel editor with live canvas preview
3. Orb list — drag handles and layer badges for layer ordering
4. Settings — Performance and default animation value configuration
5. Debug — system info, asset URLs, and registered animation configs

== Changelog ==

= 1.1.0 =
* New: branded ExpoXR admin header with quick actions bar on all plugin pages
* New: ExpoXR family footer on all plugin pages
* New: ExploreXR (Free and Premium) page introducing the ExploreXR plugin
* Fixed: WordPress admin notices no longer overlap the plugin page header
* Fixed: preview background and preview size are now preserved when saving an animation
* Fixed: duplicating an animation multiple times now produces unique animation IDs
* Fixed: PHP 8.1+ deprecation notice from the hidden edit-animation admin page
* Fixed: config sanitization returned null instead of the default for blend mode, shape, color mode, units and animation type when the field was missing

= 1.0.0 =
* Initial release
* Canvas-based orb animations with 6 animation types
* Visual 3-panel configurator with live preview
* Drag-to-reorder orb layers with layer number badges
* Layer ordering: top of list = visually on top on canvas
* Interactivity: Parallax, Repel, Attract, Follow cursor
* 4 orb shapes: Circle, Double, Triple, Blob
* 6 blend modes
* REST API for full programmatic control
* Duplicate, toggle-active, preview modal on dashboard
* Consistent admin UI across all pages (Settings, Debug, Configurator)
* Debug page with system info and config inspection
* Performance: IntersectionObserver pause, DPR cap, reduced-motion support
* WordPress 6.0+ and PHP 7.4+ compatible

== Upgrade Notice ==

= 1.1.0 =
Refreshed admin UI, admin notice fix, and preview settings persistence fix. No data migration required.

= 1.0.0 =
Initial release — no upgrade steps required.
