# SphereXR — Claude Code Context

## Project Overview

WordPress plugin for canvas-based orb background animations. Animations attach to any element by CSS ID. Built for use with Elementor but works with any theme.

**Plugin folder:** `spherexr/` (install this into `wp-content/plugins/`)

**Agent communication:** Caveman skill is available in `.claude/skills/caveman/`. Use terse exact technical communication when active, but keep security warnings, irreversible-action confirmations, and release instructions fully clear.

## Architecture

### Data Flow

1. Admin creates animation → stored as CPT `spherexr_animation` with JSON in `_spherexr_config` post meta
2. Frontend: `class-spherexr-public.php` outputs active configs as `<script type="application/json">` in footer
3. `spherexr-detect.js` reads config, scans DOM for matching element IDs, injects engine
4. `spherexr-engine.js` renders canvas animations via requestAnimationFrame

### Class Map

| Class | File | Role |
|-------|------|------|
| `SphereXR_Loader` | `includes/class-spherexr-loader.php` | Bootstraps all hooks |
| `SphereXR_Admin` | `admin/class-spherexr-admin.php` | Menu registration + asset enqueuing |
| `SphereXR_Dashboard` | `admin/class-spherexr-dashboard.php` | Animation list page. Also contains `static render_header()` / `static render_footer()` shared by all admin pages |
| `SphereXR_Configurator` | `admin/class-spherexr-configurator.php` | Editor page controller |
| `SphereXR_Settings` | `admin/class-spherexr-settings.php` | WP Settings API + `admin_post_*` handlers for export, import, cache clear. Uses static `$hooked` guard — instantiated twice (early in loader + inside `add_menu_pages()`), guard prevents double-registration |
| `SphereXR_Debug` | `admin/class-spherexr-debug.php` | Debug/diagnostic page |
| `SphereXR_ExploreXR` | `admin/class-spherexr-explorexr.php` | ExploreXR (Free and Premium) promo page |
| `SphereXR_CPT` | `includes/class-spherexr-cpt.php` | CPT registration + `sanitize_config()` |
| `SphereXR_REST` | `includes/class-spherexr-rest.php` | REST endpoints at `spherexr/v1` |
| `SphereXR_Public` | `includes/class-spherexr-public.php` | Frontend config injection + detect script |

### Admin UI Conventions

- All admin pages use `.spherexr-wrap` root wrapper
- All pages except the configurator use `SphereXR_Dashboard::render_header( $title, $actions_html )` — branded ExpoXR gradient banner + quick-actions bar (template: `templates/admin/partials/header.php`). The partial ends with `<hr class="wp-header-end">` so WP core places admin notices below the header — keep this marker
- Configurator keeps its slim editor toolbar; it has a `screen-reader-text` h1 + `wp-header-end` above it for notice placement
- All pages end with `SphereXR_Dashboard::render_footer()` — ExpoXR family branding footer
- CSS variables defined in `admin/css/admin.css` `:root` block — use them for all new styles
- Card component: `.sxr-page-card` (white surface, border, shadow)
- Section headings: `.sxr-section-title`

### Orb Data Model

Stored as JSON in `_spherexr_config` post meta. Key structure:

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
- Both `spherexr-engine.js` and `configurator.js` iterate the array **in reverse** for drawing

### Drag-to-Reorder

jQuery UI Sortable on `#sxr-orb-list` in the configurator. Each `<li>` has `data-orb-id`. After sort, `initSortable()` callback rebuilds `config.orbs` array from DOM order.

## REST API

Namespace: `spherexr/v1`. All endpoints require `edit_posts` capability + `X-WP-Nonce` header.

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

Settings page (`admin.php?page=spherexr-settings`) includes three tool cards below the settings form:

- **Cache** — POSTs to `admin-post.php?action=spherexr_clear_cache`. Deletes `_transient_spherexr_*` rows from `wp_options` and clears object cache. Redirects with `?sxr_notice=cache_cleared`.
- **Export** — POSTs to `admin-post.php?action=spherexr_export`. Streams a `spherexr-export-YYYY-MM-DD.json` download. Format: `{ plugin, version, exported_at, animations: [{title, status, config}] }`.
- **Import** — multipart POST to `admin-post.php?action=spherexr_import`. Accepts the export bundle format or a bare array. Each animation is sanitized via `SphereXR_CPT::sanitize_config()`. Creates new posts (never overwrites). Redirects with `?sxr_notice=imported&sxr_import_count=N&sxr_fail_count=M`.

All three handlers are in `SphereXR_Settings` and registered via `admin_post_{action}` hooks. The early `new SphereXR_Settings()` in the loader ensures these hooks fire before `admin_menu`. The `$hooked` static guard prevents double-registration when the class is instantiated a second time inside `add_menu_pages()`.

## Key Constraints

- Max 20 orbs per animation (hard cap in `SphereXR_CPT::sanitize_config()`)
- All config values are sanitized through `sanitize_config()` before saving — never bypass it
- Position max of 100 when unit is `percent` — this is a known limitation for non-percent units (existing behavior)
- Canvas uses `z-index: -1` and parent uses `isolation: isolate` — do not change this or orbs appear above content
- Engine pauses when container is off-screen (IntersectionObserver) and respects `prefers-reduced-motion`
- `SphereXR_Settings` uses a static `$hooked` flag to prevent double hook registration (instantiated twice per request) — do not add `admin_post_*` or `admin_init` hooks in `__construct()` without this guard

## CSS Variable Reference

Defined in `admin/css/admin.css`:

```css
--sxr-accent:       #2271b1   /* primary blue */
--sxr-accent-h:     #135e96   /* hover state */
--sxr-accent-muted: rgba(34, 113, 177, 0.10)
--sxr-surface:      #ffffff
--sxr-surface-2:    #f6f7f7
--sxr-border:       #dcdcde
--sxr-text:         #1d2327
--sxr-text-muted:   #646970
--sxr-active:       #00a32a
--sxr-inactive:     #8c8f94
--sxr-danger:       #d63638
```

## Testing Changes

No automated test suite. Manual verification steps:

1. **Admin UI consistency** — load Dashboard → Settings → Debug → Configurator. All should share same header style.
2. **Layer ordering** — create 2 orbs with distinct colors. Top of sidebar list should be visually on top in both configurator preview and frontend.
3. **Drag reorder** — drag orb rows in configurator sidebar. Preview updates immediately. Save, reload — order persists.
4. **Frontend render** — add `id="hero-bg"` to any container, set animation active, verify canvas appears behind content.
5. **Settings save** — change DPR cap, save, reload settings page, verify value persists.
6. **REST API** — use `wp-json/spherexr/v1/animations` to verify endpoints respond correctly.



## Do Not

- Do not remove `--sxr-accent-muted` from `:root` — it's used by layer badges in configurator.css
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

