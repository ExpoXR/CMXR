# CMXR — Claude Code Context

## Project Overview

WordPress plugin for canvas-based motion backgrounds (animated shapes, orbs, and blobs). Animations attach to any element by CSS ID. Built for use with Elementor but works with any theme.

**Plugin folder:** `cmxr-canvas-motion-backgrounds/` (install this into `wp-content/plugins/`)

**Agent communication:** Caveman skill is available in `.claude/skills/caveman/`. Use terse exact technical communication when active, but keep security warnings, irreversible-action confirmations, and release instructions fully clear.

## Architecture

### Data Flow

1. Admin creates animation → stored as CPT `cmxr_animation` with JSON in `_cmxr_config` post meta
2. Frontend: `class-cmxr-public.php` outputs active configs as `<script type="application/json">` in footer
3. `cmxr-detect.js` reads config, scans DOM for matching element IDs, injects engine
4. `cmxr-engine.js` renders canvas animations via requestAnimationFrame

### Class Map

| Class | File | Role |
|-------|------|------|
| `CMXR_Loader` | `includes/class-cmxr-loader.php` | Bootstraps all hooks |
| `CMXR_Admin` | `admin/class-cmxr-admin.php` | Menu registration + asset enqueuing |
| `CMXR_Dashboard` | `admin/class-cmxr-dashboard.php` | Animation list page. Also contains `static render_header()` / `static render_footer()` shared by all admin pages |
| `CMXR_Configurator` | `admin/class-cmxr-configurator.php` | Editor page controller |
| `CMXR_Settings` | `admin/class-cmxr-settings.php` | WP Settings API + `admin_post_*` handlers for export, import, cache clear. Uses static `$hooked` guard — instantiated twice (early in loader + inside `add_menu_pages()`), guard prevents double-registration |
| `CMXR_Debug` | `admin/class-cmxr-debug.php` | Debug/diagnostic page |
| `CMXR_ExploreXR` | `admin/class-cmxr-explorexr.php` | ExploreXR (Free and Premium) promo page |
| `CMXR_CPT` | `includes/class-cmxr-cpt.php` | CPT registration + `sanitize_config()` |
| `CMXR_REST` | `includes/class-cmxr-rest.php` | REST endpoints at `cmxr/v1` |
| `CMXR_Public` | `includes/class-cmxr-public.php` | Frontend config injection + detect script |

### Admin UI Conventions

- All admin pages use `.cmxr-wrap` root wrapper
- All pages except the configurator use `CMXR_Dashboard::render_header( $title, $actions_html )` — branded ExpoXR gradient banner + quick-actions bar (template: `templates/admin/partials/header.php`). The partial ends with `<hr class="wp-header-end">` so WP core places admin notices below the header — keep this marker
- Configurator keeps its slim editor toolbar; it has a `screen-reader-text` h1 + `wp-header-end` above it for notice placement
- All pages end with `CMXR_Dashboard::render_footer()` — ExpoXR family branding footer
- CSS variables defined in `admin/css/admin.css` `:root` block — use them for all new styles
- Card component: `.cmxr-page-card` (white surface, border, shadow)
- Section headings: `.cmxr-section-title`

### Orb Data Model

Stored as JSON in `_cmxr_config` post meta. Key structure:

```json
{
  "animation_id": "hero-bg",
  "active": true,
  "global": {
    "speed": 1.0,
    "safe_margin": 5,
    "blend_mode": "screen",
    "preview_bg": "transparent",
    "preview_w": null,
    "preview_h": null,
    "interactivity": { "enabled": true, "mode": "parallax", "strength": 0.5, "radius": 30 }
  },
  "orbs": [
    {
      "id": "o1234567890",
      "shape": "circle",
      "color": "#38a3d7",
      "color_mode": "solid",
      "color_b": "",
      "size": { "w": 40, "h": 40, "unit": "percent" },
      "position": { "x": 50, "y": 50, "unit": "percent" },
      "blur": 72,
      "opacity": 0.8,
      "animation": { "type": "drift", "amplitude_x": 5, "amplitude_y": 5, "frequency_x": 0.4, "frequency_y": 0.5, "phase": 0 },
      "parallax": 0.5
    }
  ]
}
```

**Orb array order = render order.** Index 0 renders last (on top). The UI list reflects this: top of list = visually on top in canvas.

### Layer / Z-Index System

No explicit z_index property. Array position controls stacking:
- `orbs[0]` = drawn last = visually on top
- `orbs[last]` = drawn first = visually below all others
- Both `cmxr-engine.js` and `configurator.js` iterate the array **in reverse** for drawing

### Drag-to-Reorder

jQuery UI Sortable on `#cmxr-orb-list` in the configurator. Each `<li>` has `data-orb-id`. After sort, `initSortable()` callback rebuilds `config.orbs` array from DOM order.

## REST API

Namespace: `cmxr/v1`. All endpoints require `edit_posts` capability + `X-WP-Nonce` header.

```
GET    /animations
POST   /animations
GET    /animations/{id}
PUT    /animations/{id}
DELETE /animations/{id}
POST   /animations/{id}/duplicate
POST   /animations/{id}/toggle
```

## Settings Page — Tools

Settings page (`admin.php?page=cmxr-settings`) includes three tool cards below the settings form:

- **Cache** — POSTs to `admin-post.php?action=cmxr_clear_cache`. Deletes `_transient_cmxr_*` rows from `wp_options` and clears object cache. Redirects with `?cmxr_notice=cache_cleared`.
- **Export** — POSTs to `admin-post.php?action=cmxr_export`. Streams a `cmxr-export-YYYY-MM-DD.json` download. Format: `{ plugin, version, exported_at, animations: [{title, status, config}] }`.
- **Import** — multipart POST to `admin-post.php?action=cmxr_import`. Accepts the export bundle format or a bare array. Each animation is sanitized via `CMXR_CPT::sanitize_config()`. Creates new posts (never overwrites). Redirects with `?cmxr_notice=imported&cmxr_import_count=N&cmxr_fail_count=M`.

All three handlers are in `CMXR_Settings` and registered via `admin_post_{action}` hooks. The early `new CMXR_Settings()` in the loader ensures these hooks fire before `admin_menu`. The `$hooked` static guard prevents double-registration when the class is instantiated a second time inside `add_menu_pages()`.

## Key Constraints

- Max 20 orbs per animation (hard cap in `CMXR_CPT::sanitize_config()`)
- All config values are sanitized through `sanitize_config()` before saving — never bypass it
- Position max of 100 when unit is `percent` — this is a known limitation for non-percent units (existing behavior)
- Canvas uses `z-index: -1` and parent uses `isolation: isolate` — do not change this or orbs appear above content
- Engine pauses when container is off-screen (IntersectionObserver) and respects `prefers-reduced-motion`
- `CMXR_Settings` uses a static `$hooked` flag to prevent double hook registration (instantiated twice per request) — do not add `admin_post_*` or `admin_init` hooks in `__construct()` without this guard

## CSS Variable Reference

Defined in `admin/css/admin.css`:

```css
--cmxr-accent:       #2271b1   /* primary blue */
--cmxr-accent-h:     #135e96   /* hover state */
--cmxr-accent-muted: rgba(34, 113, 177, 0.10)
--cmxr-surface:      #ffffff
--cmxr-surface-2:    #f6f7f7
--cmxr-border:       #dcdcde
--cmxr-text:         #1d2327
--cmxr-text-muted:   #646970
--cmxr-active:       #00a32a
--cmxr-inactive:     #8c8f94
--cmxr-danger:       #d63638
```

## Testing Changes

No automated test suite. Manual verification steps:

1. **Admin UI consistency** — load Dashboard → Settings → Debug → Configurator. All should share same header style.
2. **Layer ordering** — create 2 orbs with distinct colors. Top of sidebar list should be visually on top in both configurator preview and frontend.
3. **Drag reorder** — drag orb rows in configurator sidebar. Preview updates immediately. Save, reload — order persists.
4. **Frontend render** — add `id="hero-bg"` to any container, set animation active, verify canvas appears behind content.
5. **Settings save** — change DPR cap, save, reload settings page, verify value persists.
6. **REST API** — use `wp-json/cmxr/v1/animations` to verify endpoints respond correctly.



## Do Not

- Do not remove `--cmxr-accent-muted` from `:root` — it's used by layer badges in configurator.css
- Do not change draw loop direction in engine.js — reversal is intentional for layer ordering
- Do not use `$raw['orbs']` directly in sanitize_config without the `array_slice` cap
- Do not add inline styles to admin templates — use CSS classes and existing variables

---

## Skills Reference

Caveman skill is available for terse AI communication:

```
/caveman        — activate full caveman mode
/caveman lite   — no filler, keep full sentences
/caveman ultra  — maximum abbreviation
stop caveman    — return to normal mode
```

Location: `.claude/skills/caveman/`

Caveman mode stays active across responses. Disable with "stop caveman" or "normal mode".

