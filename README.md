# CMXR — Canvas Motion Backgrounds for WordPress

A WordPress plugin for creating and managing animated canvas motion backgrounds — moving shapes, orbs, and blobs rendered on an HTML5 canvas. Attach to any element by CSS ID — works with Elementor, Gutenberg, Divi, or any theme.

**Current version:** 1.3.0

## Features

- **Visual Configurator** — 3-panel editor (shape list / live preview / settings) with real-time canvas rendering
- **Drag-to-Reorder Layers** — drag shapes in the sidebar to control render order; top of list = visually on top
- **Layer Badges** — each shape shows its layer number so stacking is always visible
- **6 Animation Types** — Drift (compound harmonic), Orbit, Pulse, Wave, Fixed, Figure-8 (Lissajous)
- **Interactivity** — Parallax, Repel, Attract, or Off, with global strength/radius plus per-layer depth and normal/reverse direction
- **Anchor Point** — per-animation 3×3 anchor (top-left … bottom-right) that sets which point of the container shape positions are measured from; defaults to top-left so existing animations are unchanged
- **12 Shapes** — Circle, Double, Triple, Blob, Outline, Ring, Box, Box Outline, Capsule, Capsule Outline, Line, and Wave Line
- **Advanced Color Controls** — solid, dual-color, and gradients with up to 5 colors plus six color-animation modes
- **Flexible Layout Controls** — independent width/height, rotation, blur, opacity, position, and `%`, `px`, `vw`, or `vh` units that auto-convert when switched
- **6 Blend Modes** — Screen, Normal, Multiply, Overlay, Lighten, Hard-Light
- **Responsive Preview** — Fill, Mobile, Tablet, Desktop, and custom canvas sizes with Elementor breakpoint detection
- **Dashboard Tools** — live preview modal, copy target ID, duplicate, activate/deactivate, edit, and delete actions
- **Import and Export** — back up or migrate all animations through validated JSON files
- **REST API** — full CRUD + duplicate/toggle endpoints
- **Diagnostics and Settings** — configurable DPR cap, visibility threshold, defaults, cache clearing, debug logging, and a diagnostics page when `WP_DEBUG` is enabled
- **Consistent Admin UI** — shared header, footer, card components, and CSS variables across all pages
- **Performance** — cached active configs, near-viewport engine lazy loading, off-screen pause, DPR cap, bounded color cache, and `prefers-reduced-motion` support
- **Dynamic Page Support** — automatically initializes and disposes animations after Elementor, AJAX, or SPA DOM changes; `CMXR.refresh()` is available for manual rescans
- **Publishing Ready** — translatable strings and POT template, GPL-2.0-or-later license, object-level REST capability checks, and sanitized configuration data
- **WordPress Ready** — GPL-2.0-or-later, requires WP 6.0+ and PHP 7.4+

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor (optional — any CSS ID works)

## Installation

1. Clone or download this repository
2. Copy the `cmxr-canvas-motion-backgrounds/` folder into `wp-content/plugins/`
3. Activate **CMXR — Canvas Motion Backgrounds** in the WordPress admin
4. Go to **CMXR > New Animation** to create your first animation

## Quick Start

1. Create an animation at **CMXR > New Animation**
2. Add up to 20 shapes, set colors, choose animation types, and configure interaction
3. Note the **Animation ID** (e.g., `hero-bg`)
4. Add that as a CSS ID on any element in Elementor (Advanced → CSS ID) or in code: `<div id="hero-bg">`
5. The canvas animation renders automatically behind your content

## Project Structure

```
cmxr-canvas-motion-backgrounds/         ← WordPress plugin root
├── cmxr-canvas-motion-backgrounds.php  ← entry point (constants, activation hooks)
├── uninstall.php                       ← cleanup on uninstall
├── readme.txt                          ← WordPress Plugin Directory readme
├── admin/
│   ├── class-cmxr-admin.php            ← menu + asset enqueuing
│   ├── class-cmxr-dashboard.php        ← animation list + render_header() helper
│   ├── class-cmxr-configurator.php     ← editor page controller
│   ├── class-cmxr-settings.php         ← WP Settings API
│   ├── class-cmxr-debug.php            ← debug/diagnostics page (only when WP_DEBUG)
│   ├── class-cmxr-explorexr.php        ← ExploreXR promo page
│   ├── css/
│   │   ├── admin.css                   ← shared admin styles + CSS variables
│   │   └── configurator.css            ← editor-specific styles
│   └── js/
│       ├── admin.js                    ← dashboard interactions + preview modal
│       └── configurator.js             ← editor logic, sortable layers, live preview
├── includes/
│   ├── class-cmxr-loader.php           ← bootstraps all hooks
│   ├── class-cmxr-activator.php        ← activation handler
│   ├── class-cmxr-deactivator.php      ← deactivation handler
│   ├── class-cmxr-schema.php           ← single source of truth for allowed enum values
│   ├── class-cmxr-cpt.php              ← CPT registration + sanitize_config()
│   ├── class-cmxr-public.php           ← config JSON injection + detect script
│   └── class-cmxr-rest.php             ← REST API endpoints
├── public/
│   ├── css/cmxr.css                    ← canvas container styles
│   └── js/
│       ├── cmxr-detect.js              ← scans DOM, injects core + renderers + engine when animations found
│       ├── cmxr-core.js                ← shared orb math + canvas draw (frontend + preview)
│       ├── cmxr-renderers.js           ← renderer registry + shared host lifecycle (rAF, observers, DPR)
│       └── cmxr-engine.js              ← frontend bootstrap + dynamic DOM lifecycle
├── templates/admin/
│   ├── dashboard.php
│   ├── configurator.php
│   ├── settings.php
│   ├── debug.php
│   ├── explorexr.php
│   └── partials/header.php
└── languages/                          ← i18n (.pot files)
```

## REST API

Base URL: `/wp-json/cmxr/v1`  
Authentication: WordPress cookie auth + `X-WP-Nonce` header  
Required capability: `edit_posts`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/animations` | List all animations |
| POST | `/animations` | Create animation |
| GET | `/animations/{id}` | Get single animation |
| PUT | `/animations/{id}` | Update animation |
| DELETE | `/animations/{id}` | Delete animation |
| POST | `/animations/{id}/duplicate` | Clone animation |
| POST | `/animations/{id}/toggle` | Toggle active state |

## Layer Ordering

Shapes are rendered on a single HTML5 canvas. Render order determines stacking:

- **Top of list** → drawn last → visually on top
- **Bottom of list** → drawn first → visually behind all others
- Drag shapes in the configurator sidebar to reorder
- Layer badge (number) on each row shows current stacking order

## Shape and Color Controls

- **Soft Orbs:** Circle, Double, Triple, Blob, Outline, Ring
- **Geometry:** Box, Box Outline, Capsule, Capsule Outline
- **Lines:** Line, Wave Line
- **Color modes:** Solid, Dual Color, Gradient (up to 5 colors)
- **Color animation:** None, Left to Right, Right to Left, Top to Bottom, Bottom to Top, Both Axes
- **Sizing units:** Percent, pixels, viewport width, viewport height (values auto-convert when the unit is switched)
- **Anchor point:** per-animation 3×3 grid (top-left … bottom-right) that positions are measured from; motion still follows the viewport
- **Interactivity:** Parallax, Repel, Attract, or Off
- **Layer controls:** width, height, rotation, X/Y position, blur, opacity, animation timing, and interaction direction

## Settings and Tools

- Configure device-pixel-ratio cap and IntersectionObserver visibility threshold
- Set default speed, safe margin, and blend mode for new animations
- Enable debug logging; inspect system/config data through the diagnostics page when `WP_DEBUG` is active
- Clear CMXR transient caches
- Export all animations to JSON or import validated JSON without overwriting existing animations

## Frontend Loading

CMXR outputs active animation configuration as JSON, then loads its small detector only when active animations exist. Canvas CSS loads after a matching target is found; the shared rendering core and engine are deferred until that target nears the viewport. The engine pauses off-screen animations, responds to container resize, and tracks dynamic DOM additions/removals.

## For Contributors

See [CLAUDE.md](CLAUDE.md) for Claude Code context and [AGENTS.md](AGENTS.md) for AI agent context including architecture decisions and constraint documentation.

**WordPress coding standards apply.** All PHP sanitization goes through `CMXR_CPT::sanitize_config()`. New shape properties must be added there before adding them anywhere else.

Rendering math and drawing live in `public/js/cmxr-core.js` and are shared by the frontend engine, configurator preview, and dashboard preview.

## Changelog

### 1.3.0

- Added a per-animation **Anchor point** (3×3 grid) so shape positions can be measured from any corner or edge of the container; defaults to top-left, so existing animations are unchanged
- Added an **Off** option to the Interaction mode selector
- Fixed `px` position being clamped to 100 on save; px positions now persist up to the configured range
- Fixed `vw`/`vh` units resolving against the browser window in the editor; the preview now uses the device frame and the frontend reflows on window resize
- Fixed the live preview drifting from saved values after save (previously required a page reload)
- Unit switches now auto-convert the value so shapes keep their visual size and position
- Removed duplicated dead preview render loops; the shared renderer is now the single rendering path

### 1.2.1

- Minor fixes and improvements

### 1.2.0

- Added ExpoXR Orb Flow and Orb Flow Interactive templates
- Added versioned template configs, template REST routes, and shared renderer lifecycle
- Added template library, procedural editor, deterministic seeds, responsive counts, and reduced-motion previews

### 1.1.0

- Fixed orb size in px units being silently clamped to 200px on save, which made the frontend ignore the configured px width/height

### 1.0.1

- Fixed WordPress admin footer overlap and configurator column spacing
- Removed obsolete pre-release interactivity migration code
- Added GPL-2.0 license file and translation template for publishing readiness
- No manual upgrade steps required

### 1.0.0

- Initial release
- Canvas motion backgrounds with 6 animation types and 12 shapes
- 3-panel visual configurator with live preview
- Drag-to-reorder layers with layer number badges
- Cursor interactivity (Parallax, Repel, Attract)
- Solid, dual-color, and animated multi-stop gradient controls
- Responsive/custom previews, layer rotation, and per-layer interaction direction
- REST API for full programmatic control
- Dashboard preview, duplicate, active toggle, copy ID, edit, and delete actions
- Settings, diagnostics, JSON import/export, and cache tools
- Consistent admin UI across Dashboard, Settings, Debug, and Configurator
- Performance optimizations: off-screen pause, DPR cap, reduced-motion support
- WordPress 6.0+ / PHP 7.4+ compatibility headers

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

**Ayal Othman** — [expoxr.com](https://expoxr.com)
