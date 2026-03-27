# Hospital Alerts Bar — WordPress Plugin

**Version:** 1.0.0  
**Requires WordPress:** 5.8+  
**Requires PHP:** 7.4+  

---

## Overview

Hospital Alerts Bar is a full-featured WordPress plugin that lets hospital admins create and manage announcement bars displayed on the front-end of the website. Alerts are shown only during their configured date window and rotate automatically when multiple are active.

---

## Installation

### Option A — Manual Upload
1. Copy the entire `hospital-alerts-bar/` folder to `/wp-content/plugins/`.
2. Log into your WordPress admin panel.
3. Go to **Plugins → Installed Plugins**.
4. Find **Hospital Alerts Bar** and click **Activate**.

### Option B — ZIP Upload
1. Compress the `hospital-alerts-bar/` folder into `hospital-alerts-bar.zip`.
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP, then click **Activate**.

---

## File Structure

```
hospital-alerts-bar/
├── hospital-alerts-bar.php          ← Main plugin bootstrap
├── README.md                        ← This file
├── includes/
│   ├── class-hab-post-type.php      ← Custom Post Type registration
│   ├── class-hab-meta-boxes.php     ← Alert field meta boxes
│   ├── class-hab-settings.php       ← Admin settings page
│   ├── class-hab-frontend.php       ← Front-end HTML output
│   └── class-hab-shortcode.php      ← [hospital_alerts] shortcode
└── assets/
    ├── css/
    │   ├── hab-frontend.css         ← Front-end alert bar styles
    │   └── hab-admin.css            ← Admin meta box / settings styles
    └── js/
        ├── hab-frontend.js          ← Slider, dismiss, swipe logic
        └── hab-admin.js             ← Color picker & live preview
```

---

## Creating Alerts

1. In the WordPress admin sidebar click **Hospital Alerts → Add New Alert**.
2. Fill in the **Alert Title** (used as the bold heading in the bar).
3. Complete the **Alert Details** meta box:

   | Field              | Description                                                    |
   |--------------------|----------------------------------------------------------------|
   | Alert Message      | The descriptive text shown after the title                     |
   | Background Color   | WP color-picker – choose any hex color                         |
   | Text Color         | WP color-picker – ensure sufficient contrast                   |
   | Start Date         | Alert only shows on or after this date                         |
   | End Date           | Alert stops showing after this date (inclusive)                |
   | Display Position   | **Top** or **Bottom** of the viewport                         |

4. A **Live Preview** section shows how the bar will look as you edit.
5. Click **Publish** to save.

---

## Global Settings

Go to **Hospital Alerts → Settings** to configure:

| Setting         | Description                                      | Default |
|-----------------|--------------------------------------------------|---------|
| Enable Alert Bar | Master toggle — hides bar from all visitors     | On      |
| Global Font Size | Applied to all alert text (10 – 40 px)          | 15 px   |
| Global Padding   | Top/bottom padding of each alert (4 – 60 px)   | 14 px   |

---

## Displaying Alerts

### Automatic (recommended)
The alert bar is automatically injected site-wide just before `</body>` — no theme edits required.

### Shortcode
Place `[hospital_alerts]` in any page, post, or widget area. When used via shortcode the bar renders inline (not fixed-position) so it integrates naturally within content.

### PHP Template Tag
Add the following to any theme template file:
```php
<?php hab_render_alerts(); ?>
```

---

## How Date Visibility Works

- If **no Start Date** is set the alert is shown immediately from publication.
- If **no End Date** is set the alert runs indefinitely.
- The comparison uses `current_time('timestamp')` which respects your WordPress timezone setting (**Settings → General → Timezone**).

---

## Slider Behaviour

- If only **one** active alert exists it is displayed statically.
- If **multiple** active alerts exist they rotate every **5 seconds** with a fade animation.
- Navigation dots appear below the text for manual control.
- Hovering/focusing the bar **pauses** auto-rotation.
- Swipe left/right on touch devices to navigate.
- The dismiss (×) button closes the bar for the rest of the browser session (`sessionStorage`).

---

## Customising Styles

All visual properties use CSS custom properties defined in `hab-frontend.css`. You can override them in your theme's `style.css` without editing plugin files:

```css
:root {
    --hab-font-size:  16px;   /* alert text size   */
    --hab-padding:    18px;   /* top/bottom padding */
    --hab-z-index:    99999;  /* stacking order     */
}
```

Per-alert colours (`--slide-bg`, `--slide-color`) are set inline on each `.hab-slide` element and can be targeted in CSS:

```css
.hab-slide { border-bottom: 3px solid rgba(255,255,255,0.3); }
```

---

## Frequently Asked Questions

**Q: The alert bar overlaps my sticky header.**  
A: Lower `--hab-z-index` in your theme CSS, or add `margin-top` to your header element equal to the bar height.

**Q: How do I offset the top bar when the WP admin bar is visible?**  
A: This is handled automatically via the `.admin-bar .hab-bar--top` CSS rule included in `hab-frontend.css`.

**Q: Can I have some alerts on top and others on bottom?**  
A: Yes. Set the **Display Position** field per-alert. Top and bottom bars are rendered independently.

**Q: The shortcode bar is fixed-position over my content.**  
A: When output via `[hospital_alerts]` the bar is wrapped in `.hab-shortcode-wrap` which overrides `position:fixed` to `position:relative`.

---

## Changelog

### 1.0.0
- Initial release.
- Custom post type `hospital_alert` with full meta fields.
- Date-range visibility logic.
- Multi-alert slider with dots, swipe, keyboard, and hover-pause.
- Global settings page (enable/disable, font size, padding).
- `[hospital_alerts]` shortcode and `hab_render_alerts()` template tag.
- Fully separate CSS & JS assets (no inline styles).
- Admin bar offset, forced-color (high-contrast) support.
- Responsive at all breakpoints.
