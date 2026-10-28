# Web/UI/WebControls/TSvgDocument

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSvgDocument`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSvgDocument.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TSvgDocument parses SVG markup into a DOM so it can be written inline in an HTML page. [TSvg](./TSvg.md) uses it for `SvgFile` and `Svg` content.

## Parsing

| Input | Result |
|---|---|
| Well-formed XML with a root `<svg>` | Parsed with `LIBXML_NONET` (no network) |
| DOCTYPE without entities (as SVG editors write) | Dropped |
| DOCTYPE declaring entities | `svgdocument_entity_invalid` (no entity amplification) |
| Not well-formed | `svgdocument_invalid` |
| Root other than `<svg>` | `svgdocument_root_invalid` |
| `fromFile($path)` unreadable | `svgdocument_file_invalid` |

## Methods

| Method | Description |
|---|---|
| `getRootAttributes()` | Root attributes such as `viewBox`, `fill`; namespace declarations excluded |
| `getInnerMarkup()` | XML serialization of the root's children |
| `getTitle()` / `ensureTitleId($id)` / `removeTitles()` | The root `<title>` (the SVG's own text alternative); give it an ID; remove root `<title>`/`<desc>` |
| `sanitize()` | Removes `UNSAFE_ELEMENTS` (script, foreignObject, iframe, embed, object, handler, listener), `on*` attributes, `javascript:`/`vbscript:`/non-image `data:` in `href`/`src`, non-fragment references on `<use>`/`<image>`/`<feImage>`, `<animate>`/`<set>` targeting `href` or `on*`, and `@import` and external `url()` in CSS; `<a>` links to other pages remain |
| `prefixIds($prefix)` | `id` → `<prefix>_id`; rewrites `#id` hrefs, `url(#id)` in attributes and `<style>`, `#id` selectors, `IDREF_ATTRIBUTES` (`aria-labelledby` …), and `id.event` in `begin`/`end`; returns the map |
| `scopeStyles($scope)` / `scopeCss($css, $scope)` | Prefixes each selector with `$scope` (a leading `svg` or `:root` becomes `$scope`); recurses into `@media`/`@supports`/`@container`/`@layer`; leaves `@keyframes`/`@font-face`; strips comments |
| `extractStyles()` | Removes `<style>` elements, returns their non-empty CSS |

## Tests

- `tests/unit/Web/UI/WebControls/TSvgDocumentTest.php`

## See Also

- [TSvg](./TSvg.md)

**@since 4.4.0**
