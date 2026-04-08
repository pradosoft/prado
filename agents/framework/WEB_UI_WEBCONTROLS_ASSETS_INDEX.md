# Web/UI/WebControls/assets/INDEX.md - WEB_UI_WEBCONTROLS_ASSETS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Static assets (CSS, fonts, images) and the server-side CAPTCHA image generator used by WebControl components. These files are published to the web-accessible asset directory by `TAssetManager` at runtime.

## Files

### CSS

- **`accordion.css`** — Default styles for the `TAccordion` control (expand/collapse panels).
- **`tabpanel.css`** — Default styles for the `TTabPanel` control (tabbed interface).
- **`keyboard.css`** — Default styles for the `TKeyboard` control (virtual on-screen keyboard).
- **`TSlider/TSlider.css`** — Default styles for the `TSlider` control (range slider).

### Images

- **`TSlider/TSliderHandleHorizontal.png`** — Horizontal slider handle graphic.
- **`TSlider/TSliderHandleVertical.png`** — Vertical slider handle graphic.

### PHP (Server-Side Generation)

- **`captcha.php`** — CAPTCHA image generator. Called directly via a published URL with Base64-encoded, private-key-validated options. Supports multiple visual themes:
  - `opaque_bubble` — opaque background with bubble noise
  - `noisy` — random dot noise
  - `grid` — grid-line background
  - `scribble` — scribbled line background
  - `morph` — morphed/warped text
  - `shadowed` — shadowed text
  Requires `verase.ttf` for font rendering. Outputs `image/png` directly (no buffering).

### Fonts

- **`verase.ttf`** — TrueType font used exclusively by `captcha.php` for CAPTCHA text rendering.

## Conventions

- CSS files here are **defaults** — override by supplying a custom stylesheet path to the control's `CssUrl` property.
- `captcha.php` validates a private key before rendering; the key is set on the `TCaptcha` control and passed as part of the encoded options. Do not expose the private key in client-side code.
- These assets are published by `TAssetManager`; the published URL (not the source path) is what gets embedded in page output.
