# Web/Javascripts/TStyleSheetHelper

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`TStyleSheetHelper`**

## Class Info
**Location:** `framework/Web/Javascripts/TStyleSheetHelper.php`
**Namespace:** `Prado\Web\Javascripts`
**Since:** 4.4.0

## Overview
Static utility that renders `<style>` blocks and `<link rel="stylesheet">` tags with the CSP nonce and Subresource Integrity. It is the stylesheet counterpart of [TJavaScript](TJavaScript.md) and follows its pattern: per-request static state, render helpers, and a separate required-integrity flag.

## State

| State | Methods | Notes |
|---|---|---|
| CSP nonce | `getStyleNonce()` | Returns `TJavaScript::getScriptNonce()`: one nonce per response, which `THttpHeaderCsp` substitutes for `NONCE` in any directive |
| SRI registry | `hasStyleIntegrity()`, `getStyleIntegrity()`, `setStyleIntegrity($url, ?$hash, $method = 'sha384')` | URL-keyed, normalized by `THttpUtility::normalizeIntegrityUrl()`; separate from the script registry; `TIntegrityManager` fills both |
| Required integrity | `getRequireStyleIntegrity()`, `setRequireStyleIntegrity(bool)` | Default false; separate from `RequireScriptIntegrity` because font service CSS generated per browser has no fixed hash |

## Render Helpers

| Method | Output |
|---|---|
| `renderStyleHeader(array $attributes = [])` | `<style type="text/css" nonce="…">` + CDATA prologue; a caller `nonce` key wins (`false` suppresses) |
| `renderStyleFooter()` | CDATA epilogue + `</style>` |
| `renderStyleBlock($css)` / `renderStyleBlocks(array)` | One `<style>`; `''` for an empty list |
| `renderStyleSheetFile($url, $media = '')` | `<link rel="stylesheet" type="text/css" [media] href nonce [integrity crossorigin="anonymous"] />` |
| `renderStyleSheetFiles(array)` | Each entry a URL or `[url, media]`, as `TClientScriptManager` stores them |

- `integrity` and `crossorigin` render only for a remote URL (`THttpUtility::isLocalUrl()` false) with a registered hash.
- `RequireStyleIntegrity` on + remote URL without a hash → `TConfigurationException` `stylesheet_integrity_required`.

## Producers

| Producer | Path |
|---|---|
| [TStyleSheet](../UI/WebControls/TStyleSheet.md) child CSS | `renderStyleHeader()` / `renderStyleFooter()` |
| `TClientScriptManager::renderStyleSheets()` (`registerStyleSheet()`) | `renderStyleBlocks()` |
| `TClientScriptManager::renderStyleSheetFiles()` (`registerStyleSheetFile()`, PRADO and theme CSS) | `renderStyleSheetFiles()` |
| Callback stylesheet sections | `ajax3.js` `Prado.StyleSheetManager`: `createStyleSheetCode()` and `createAssetElement()` set the page's nonce from `prado.js` `Prado.Element.getPageNonce()` |

## Callbacks
A callback response is a new request with a new nonce, while the page keeps the policy of its first load. The client therefore uses the page's nonce from `Prado.Element.getPageNonce()`, which reads the `nonce` property of the first element that has one; the browser hides the attribute after parsing, but the property keeps it. prado.js caches the nonce on load, before callback markup with a callback nonce reaches the page. Callback scripts use the same nonce: `Prado.ScriptManager` script files, `Prado.Element.appendScriptBlock()`, and the scripts `Prado.Element.executeScripts()` re-creates after `replace()`. CSS that changes in a callback is registered with `registerStyleSheet()`; a TStyleSheet rendered again in a callback carries the callback's nonce and is blocked.

## Policy
A nonce does not cover `style="…"` attributes, which Prado controls render. A nonce policy for styles splits the directives:

```
style-src-elem 'self' NONCE; style-src-attr 'unsafe-inline'
```

## Tests

- `tests/unit/Web/Javascripts/TStyleSheetHelperTest.php`
- `tests/unit/Web/UI/WebControls/TStyleSheetTest.php`, `tests/unit/Web/UI/TClientScriptManagerTest.php`, `tests/unit/Web/TIntegrityManagerTest.php`
- `tests/js/activecontrols/stylesheetmanager.test.js`
- `tests/playwright/web/HttpHeaders/TStyleSheetHelperTestCase.spec.js` with `tests/harness/HttpHeaders/protected/pages/StyleCspPage` and `StyleSriPage`

## See Also

- [TJavaScript](TJavaScript.md)
- [TClientScriptManager](../UI/TClientScriptManager.md)
