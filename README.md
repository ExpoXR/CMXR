# CMXR — Canvas Motion Backgrounds for WordPress

A WordPress plugin for creating and managing animated canvas motion backgrounds — moving shapes, orbs, and blobs rendered on an HTML5 canvas. Attach to any element by CSS ID — works with Elementor, Gutenberg, Divi, or any theme.

## Features

- **Visual Configurator** — 3-panel editor (shape list / live preview / settings) with real-time canvas rendering
- **Drag-to-Reorder Layers** — drag shapes in the sidebar to control render order; top of list = visually on top
- **Layer Badges** — each shape shows its layer number so stacking is always visible
- **6 Animation Types** — Drift (compound harmonic), Orbit, Pulse, Wave, Fixed, Figure-8 (Lissajous)
- **Interactivity** — Parallax, Repel, Attract, Follow cursor
- **4 Shapes** — Circle, Double, Triple, Blob (with organic deformation)
- **6 Blend Modes** — Screen, Normal, Multiply, Overlay, Lighten, Hard-Light
- **REST API** — full CRUD + duplicate/toggle endpoints
- **Consistent Admin UI** — shared header, card components, and CSS variables across all pages
- **Performance** — IntersectionObserver pause when off-screen, DPR cap for HiDPI, `prefers-reduced-motion` support
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
2. Add shapes, set colors, choose animation type
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
│   ├── class-cmxr-debug.php            ← debug/diagnostics page
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
│   ├── class-cmxr-i18n.php             ← text domain loading
│   ├── class-cmxr-cpt.php              ← CPT registration + sanitize_config()
│   ├── class-cmxr-public.php           ← config JSON injection + detect script
│   └── class-cmxr-rest.php             ← REST API endpoints
├── public/
│   ├── css/cmxr.css                    ← canvas container styles
│   └── js/
│       ├── cmxr-detect.js              ← scans DOM, injects engine when animations found
│       └── cmxr-engine.js              ← requestAnimationFrame canvas renderer
├── templates/admin/
│   ├── dashboard.php
│   ├── configurator.php
│   ├── settings.php
│   └── debug.php
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

## For Contributors

See [CLAUDE.md](CLAUDE.md) for Claude Code context and [AGENTS.md](AGENTS.md) for AI agent context including architecture decisions and constraint documentation.

**WordPress coding standards apply.** All PHP sanitization goes through `CMXR_CPT::sanitize_config()`. New shape properties must be added there before adding them anywhere else.

**Three render engines must stay in sync:**
- `public/js/cmxr-engine.js` (frontend)
- `admin/js/configurator.js` (editor preview)
- `admin/js/admin.js` (dashboard modal preview)

## Changelog

### 1.0.0

- Initial release
- Canvas motion backgrounds with 6 animation types and 4 shapes
- 3-panel visual configurator with live preview
- Drag-to-reorder layers with layer number badges
- Cursor interactivity (Parallax, Repel, Attract, Follow)
- REST API for full programmatic control
- Consistent admin UI across Dashboard, Settings, Debug, and Configurator
- Performance optimizations: off-screen pause, DPR cap, reduced-motion support
- WordPress 6.0+ / PHP 7.4+ compatibility headers

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

**Ayal Othman** — [expoxr.com](https://expoxr.com)
