# Web/UI/WebControls/assets/INDEX.md

### Directories
[framework](../../../../INDEX.md) / [Web](../../../INDEX.md) / [UI](../../INDEX.md) / [WebControls](../INDEX.md) / **`assets`**

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

## Color Scheme

The shipped stylesheets follow the `color-scheme` the application declares. No
Prado property selects the scheme, and no shipped stylesheet declares
`color-scheme` of its own.

| Application declares | Controls render |
|---|---|
| nothing | light, whatever the operating system prefers |
| `color-scheme: light` | light |
| `color-scheme: dark` | dark |
| `color-scheme: light dark` | the scheme the operating system prefers, updating live |
| a scheme on a container | that scheme inside the container only |

Two mechanisms carry the colors.

- `light-dark(light, dark)` supplies designed colors. The plain light value
  precedes each call, so a browser without `light-dark()` support keeps the light
  rendering. The function resolves against the used `color-scheme`, which
  inherits, so a container darkens its own subtree without affecting the page.
- `Canvas` and `CanvasText` supply chrome that tracks the page, such as the
  active tab face, the date picker panel, and the color picker panel. These
  system colors resolve per scheme on their own and also answer forced-colors
  mode.

An application whose dark theme comes only from its own `prefers-color-scheme`
media query leaves these controls light. Declaring `color-scheme` on the root
connects it.

Raster assets carry fixed colors and do not adapt: the slider handle PNGs, the
rating star GIFs, and the color picker hue and target images.

Functional coverage lives in `tests/playwright/web/ColorSchemeTestCase.spec.js`
against the `ColorSchemeTest` harness page. Those tests assert the contract, not
the palette: that declaring nothing matches declaring light, that a dark
operating system alone changes nothing, that every adapted color resolves
differently under a dark declaration, that every adapting background darkens,
and that each foreground clears its WCAG contrast minimum against its own
background in both schemes. Changing a design value therefore does not break
them, while a low-contrast or non-adapting value does.

The two schemes are held to the same minimums and reach them with different
ratios. Text and borders carry no direction rule: the active accordion bar is
dark in both schemes, so its text is lighter in the light scheme, where the bar
behind it is lighter. Only backgrounds are required to darken.

## Conventions

- CSS files here are **defaults** — override by supplying a custom stylesheet path to the control's `CssUrl` property.
- `captcha.php` validates a private key before rendering; the key is set on the `TCaptcha` control and passed as part of the encoded options. Do not expose the private key in client-side code.
- These assets are published by `TAssetManager`; the published URL (not the source path) is what gets embedded in page output.
