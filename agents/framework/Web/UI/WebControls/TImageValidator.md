# Web/UI/WebControls/TImageValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TImageValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TImageValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TImageValidator extends [TFileValidator](./TFileValidator.md): every file restriction applies, and each file must additionally be a readable image whose pixel dimensions satisfy the bounds. Server side uses `getimagesize()`; a file that cannot be read as an image (or has no local temp file) fails closed.

## Inheritance

`TImageValidator` → `TFileValidator` → `TBaseValidator` → `TLabel` → …

Client-side class: `Prado.WebUI.TImageValidator` (validation3.js), extends `Prado.WebUI.TFileValidator` via `$super` injection.

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `MinImageWidth` / `MaxImageWidth` | int | `0` | Pixel width bounds. `0` disables either check. |
| `MinImageHeight` / `MaxImageHeight` | int | `0` | Pixel height bounds. `0` disables either check. |

## Client-side Asynchronous Decode

Prado client validation is synchronous, so the JS class decodes dimensions out of band:

- `onInit()` observes the input's `change` event; each selection change drops the cache (`_imageInfo`, keyed by `name|size|lastModified`) and decodes every file via `URL.createObjectURL` + `Image`.
- `evaluateIsValid()` → `isValidFile()`: an undecoded (pending or uncached) file **passes** client-side; when a decode completes, `revalidate()` re-runs `validate()` + `updateSummary()` if results are already displayed (`this.visible`).
- A submit that outruns the decode therefore posts back and the authoritative server-side validation catches the file. The Playwright tests accept either path.
- Browsers without `URL.createObjectURL` skip the client dimension checks entirely (`canReadImages()` guard); jsdom tests seed `_imageInfo` directly.

## Patterns & Gotchas

- **Fails closed server-side** — a missing/unreadable temp file or a `getimagesize()` failure is invalid, unlike TFileValidator checks that pass when unverifiable.
- **notImage detection** — client: `Image.onerror`; server: `getimagesize() === false`. Both fail the file.
- Dimension property names carry the `Image` infix (`MinImageWidth`, not `MinWidth`) to avoid confusion with the validator control's own `Width`/`Height` style properties.

## Tests

- Unit: `tests/unit/Web/UI/WebControls/TImageValidatorTest.php` (crafts minimal GIF headers — `'GIF89a' . pack('v',$w) . pack('v',$h) . "\x00\x00\x00"` — which `getimagesize()` reads)
- JS (vitest): `tests/js/validator/imagevalidator.test.js` (seeds the dimension cache; jsdom cannot decode)
- Functional (Playwright): `tests/playwright/validators/ImageValidatorTestCase.spec.js` + `ImageValidator.page`, real PNGs built by `tests/playwright/validators/png.js`
