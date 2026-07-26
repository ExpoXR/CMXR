# CMXR — AI Agent Context

## What This Codebase Does

WordPress plugin that renders animated canvas motion backgrounds (shapes, orbs, and blobs). Admins design animations in a 3-panel visual editor. Animations store as JSON in WordPress CPT post meta and render via a small vanilla-JS engine injected into the frontend page.

## Repository Layout

```
cmxr-canvas-motion-backgrounds/               ← install this as WordPress plugin
  cmxr-canvas-motion-backgrounds.php          ← plugin entry point (constants, hooks)
  uninstall.php         ← cleanup on uninstall
  admin/                ← all WP admin UI
  includes/             ← core logic (CPT, REST, public, loader)
  public/               ← frontend assets (engine, detect, css)
  templates/admin/      ← PHP view templates for each admin page
README.md               ← GitHub readme
readme.txt              ← WordPress Plugin Directory readme
CLAUDE.md               ← Claude Code guidance
AGENTS.md               ← this file
```

## Key Files for Common Tasks

| Task | Files to read |
|------|--------------|
| Add new orb property | `class-cmxr-cpt.php` (sanitize_config), `configurator.php` (template field), `configurator.js` (bind field + preview), `cmxr-engine.js` (draw logic) |
| Add new admin page | `class-cmxr-admin.php` (menu), `class-cmxr-dashboard.php` (render_header), new template in `templates/admin/` |
| Change render behavior | `cmxr-engine.js` (frontend), `configurator.js` (admin preview), `admin.js` (dashboard modal preview) — all three must stay in sync |
| Change REST API | `class-cmxr-rest.php` |
| Change settings | `class-cmxr-settings.php` (register + sanitize), `templates/admin/settings.php` (template) |
| Style changes | `admin/css/admin.css` (shared), `admin/css/configurator.css` (editor-specific) |

## Architecture Decisions

**Why CPT + post meta JSON (not a custom table)?**
Uses WordPress core APIs for storage. Posts give free revision history, REST API support, and capability integration. JSON meta avoids ALTER TABLE on activation.

**Why vanilla JS engine?**
Zero dependencies. The engine is injected into public pages where jQuery/React may not be available or desirable.

**Why reversed draw order?**
Canvas has no z-index. Last drawn = visually on top. Orb list index 0 = top of UI list = should be on top visually → draw loop iterates array in reverse.

**Why jQuery for sortable only?**
jQuery UI Sortable is already bundled with WordPress admin. Using it avoids adding a dependency. The rest of the configurator uses vanilla JS.

## Data Mutations

All orb config mutations go through:
1. `admin/js/configurator.js` → in-memory `config` object
2. `PUT /wp-json/cmxr/v1/animations/{id}` on save
3. `CMXR_REST::update_animation()` → `CMXR_CPT::sanitize_config()` → `update_post_meta()`

`sanitize_config()` is the single source of truth for valid values. If adding a new property, add it here first.

## Constraints Agents Must Respect

- **Orb cap**: max 20 orbs per animation (enforced in `sanitize_config`)
- **Blend mode allowlist**: `screen | normal | multiply | overlay | lighten | hard-light`
- **Shapes allowlist**: `circle | double | triple | blob`
- **Animation types allowlist**: `drift | orbit | pulse | wave | fixed | figure8`
- **Units allowlist**: `percent | px | vw | vh`
- **Canvas z-index**: public canvas sits at `z-index: -1` behind content — do not raise it
- **Three render engines must stay in sync**: `cmxr-engine.js`, preview in `configurator.js`, modal preview in `admin.js`

## CSS Conventions

- All CSS variables prefixed `--cmxr-` defined in `admin/css/admin.css` `:root`
- Admin component classes: `.cmxr-` prefix (e.g., `.cmxr-page-card`, `.cmxr-layer-badge`)
- Legacy classes: `.cmxr-` prefix (e.g., `.cmxr-header`, `.cmxr-table`)
- New components should use `.cmxr-` prefix
- Configurator-specific styles go in `configurator.css`, shared admin styles in `admin.css`

## REST API Quick Reference

Base: `/wp-json/cmxr/v1`  
Auth: cookie + `X-WP-Nonce` header (`wp_create_nonce('wp_rest')`)  
Capability required: `edit_posts`

```
GET    /animations           → array of { id, title, status, config }
POST   /animations           → { title } → { id, ... }
GET    /animations/:id       → { id, title, status, config }
PUT    /animations/:id       → { title, config } → { id, ... }
DELETE /animations/:id       → { deleted: true }
POST   /animations/:id/duplicate → { id, ... }
POST   /animations/:id/toggle    → { id, active: bool }
```

## Frontend Injection Flow

```
wp_footer (priority 5)
  └── CMXR_Public::output_config_json()
        └── <script id="cmxr-config" type="application/json">
              [{ animation_id, active, global, orbs }, ...]

wp_enqueue_scripts (priority 20)
  └── cmxr-detect.js
        ├── reads #cmxr-config
        ├── filters active animations
        ├── finds matching DOM elements by ID
        └── injects cmxr-engine.js + cmxr.css
              └── cmxr-engine.js
                    └── initAnimation(el, cfg) per matched element
                          ├── creates <canvas> as first child
                          ├── ResizeObserver → resize canvas
                          ├── IntersectionObserver → pause/resume RAF
                          └── requestAnimationFrame render loop
```

## Agent Communication

Caveman skill is installed and available in `.claude/skills/caveman/`. Use terse exact technical communication when active. Do not let terse wording weaken security warnings, irreversible-action confirmations, or release instructions.
