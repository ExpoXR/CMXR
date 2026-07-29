=== CMXR — Canvas Motion Backgrounds ===
Contributors: expoxr
Tags: animation, canvas, background, shapes, elementor
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create animated canvas backgrounds with responsive orbs, shapes, and interactive effects for WordPress, Elementor, and Gutenberg.

== Description ==

Canvas Motion Background Made Easy. CMXR lets you build and manage animated canvas motion backgrounds directly from the WordPress admin. Compose moving shapes — orbs, blobs, and multi-ring forms — that render on an HTML5 canvas, sit behind your page content, and are driven by a tiny vanilla-JS engine with zero frontend dependencies.

**Key features:**

* **Visual configurator** — three-panel editor with live canvas preview, shape/color/animation controls, and real-time feedback
* **Drag-to-reorder layers** — drag shapes in the sidebar to control which one renders on top
* **Layer badges** — each shape shows its layer number (1 = topmost)
* **6 animation types** — Drift, Orbit, Pulse, Wave, Fixed, Figure-8 (Lissajous)
* **Interactivity modes** — Parallax, Repel, Attract, or Off
* **Anchor point** — a per-animation 3×3 anchor (top-left … bottom-right) that sets which point of the container shape positions are measured from
* **Flexible units** — size and position in %, px, vw, or vh, with values auto-converting when you switch units
* **12 shapes** — Soft Orbs (Circle, Double, Triple, Blob, Outline, Ring), Geometry (Box, Box Outline, Capsule, Capsule Outline), and Lines (Line, Wave Line)
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

1. Extract the `cmxr-canvas-motion-backgrounds` folder
2. Upload it to `/wp-content/plugins/`
3. Go to **Plugins** and activate CMXR

= Usage =

1. Go to **CMXR > New Animation** in the WordPress admin
2. Add shapes, configure colors, animation types, and sizes
3. Note the **Animation ID** (e.g., `hero-bg`)
4. Add that ID as a CSS ID on any element in your page
5. The animation renders automatically as the element's background

== Frequently Asked Questions ==

= Does this require Elementor? =

No. CMXR works with any theme or page builder. Add the CSS ID to any HTML element or Elementor section.

= How do I control which shape appears on top? =

Drag shapes in the configurator's left sidebar. The shape at the top of the list renders above all others. The layer badge (number) shows each shape's stacking order.

= How do I attach an animation to an Elementor section? =

In Elementor, open the section settings → **Advanced** tab → **CSS ID** field. Enter the Animation ID shown in the configurator or dashboard.

= Why is the animation not showing? =

1. Verify the Animation ID is set in the configurator
2. Confirm the animation is set to **Active** on the dashboard
3. Confirm the CSS ID on your element exactly matches the Animation ID (case-sensitive)
4. Check the **Debug** page (CMXR > Debug) for registered animations and asset URLs

= Can I have multiple animations on one page? =

Yes. Each animation targets a unique CSS ID. Add as many as you need.

= Does it affect page performance? =

The engine pauses automatically when the animated element is scrolled off-screen. It also respects the browser's `prefers-reduced-motion` setting and caps the device pixel ratio (configurable in Settings) to control canvas resolution on HiDPI displays.

= Is there a limit on shapes per animation? =

Up to 20 shapes per animation.

== Screenshots ==

1. CMXR Dashboard
2. New Animation Dashboard
3. Settings Page
4. New Animation — different shapes
5. Different color styling for shapes
6. Different color animation styling for shapes
7. Different shapes with easy blur, opacity, size, and position controls
8. Different shape animation styles

== Changelog ==

= 1.3.0 =
* Added a per-animation Anchor point (3x3 grid) so shape positions can be measured from any corner or edge of the container; defaults to top-left so existing animations are unchanged.
* Added an "Off" option to the Interaction mode selector.
* Fixed px position being clamped to 100 on save; px positions now persist up to the configured range.
* Fixed vw/vh units resolving against the browser window in the editor; preview now uses the device frame and the frontend reflows on window resize.
* Fixed the live preview drifting from saved values after save, which previously required a page reload.
* Unit switches now auto-convert the value so shapes keep their visual size and position.
* Removed duplicated dead preview render loops; the shared renderer is now the single rendering path.

= 1.2.1 =
Minor fixes and improvements.

= 1.2.0 =
* Added ExpoXR Orb Flow and Orb Flow Interactive templates.
* Added versioned template configs, template REST routes, and shared renderer lifecycle.
* Added template library, procedural editor, deterministic seeds, responsive counts, and reduced-motion previews.

= 1.1.0 =
* Fixed orb size in px units being silently clamped to 200px on save, causing frontend shape size to ignore the configured px width/height.

= 1.0.1 =
* Fixed WordPress admin footer overlap on CMXR admin screens and configurator columns.
* Removed obsolete pre-release interactivity migration code.
* Added GPL-2.0 license file and translation template for publishing readiness.

= 1.0.0 =
* Initial release
* Canvas-based motion backgrounds with 6 animation types
* Visual 3-panel configurator with live preview
* Drag-to-reorder shape layers with layer number badges
* Layer ordering: top of list = visually on top on canvas
* Interactivity: Parallax, Repel, Attract
* 12 shapes: Circle, Double, Triple, Blob, Outline, Ring, Box, Box Outline, Capsule, Capsule Outline, Line, Wave Line
* 6 blend modes
* REST API for full programmatic control
* Duplicate, toggle-active, preview modal on dashboard
* Consistent admin UI across all pages (Settings, Debug, Configurator)
* Debug page with system info and config inspection
* Performance: IntersectionObserver pause, DPR cap, reduced-motion support
* WordPress 6.0+ and PHP 7.4+ compatible

== Upgrade Notice ==

= 1.3.0 =
Adds a per-animation anchor point and an Interaction "Off" option, and fixes px/vw/vh sizing and positioning. Existing animations are unchanged (they use the top-left anchor). No manual upgrade steps required.

= 1.2.0 =
Adds reusable Orb templates while preserving existing layered-shape animations.

= 1.1.0 =
Fixes px-unit orb sizing on the frontend. No manual upgrade steps required.

= 1.0.1 =
Fixes admin footer layout overlap and removes obsolete pre-release migration code. No manual upgrade steps required.

= 1.0.0 =
Initial release — no upgrade steps required.
