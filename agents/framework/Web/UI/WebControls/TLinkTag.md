# Web/UI/WebControls/TLinkTag

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLinkTag`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLinkTag.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TComponent` (like [TMetaTag](./TMetaTag.md), not a control)

## Overview
TLinkTag represents a `<link>` element in the page head. [THead](./THead.md) collects it into `LinkTags` and renders the collection after the meta tags and before the stylesheets and head scripts, so a preload or preconnect starts early.

## Template Usage

```xml
<com:THead>
  <com:TLinkTag Rel="canonical" Href="https://example.com/products" />
  <com:TLinkTag Rel="alternate" HrefLang="fr" Href="https://example.com/fr/products" />
  <com:TLinkTag Rel="preconnect" Href="https://cdn.example.com" CrossOrigin="anonymous" />
  <com:TLinkTag Rel="preload" Href="fonts/body.woff2" As="font" Type="font/woff2" CrossOrigin="anonymous" />
  <com:TLinkTag Rel="icon" Href="icon-192.png" Sizes="192x192" Type="image/png" />
  <com:TLinkTag Rel="manifest" Href="app.webmanifest" />
</com:THead>
```

## Properties

Attributes render in this order; an empty value renders nothing.

| Property | Attribute | Notes |
|---|---|---|
| `ID` | `id` | Found by `TLinkTagCollection::findLinkTagByID()` |
| `Rel` | `rel` | Space-separated link types; whitespace collapsed |
| `Href` | `href` | |
| `HrefLang` | `hreflang` | `x-default` marks the fallback alternate |
| `Type` | `type` | MIME type |
| `Media` | `media` | Media query |
| `Sizes` | `sizes` | Icon sizes; whitespace collapsed |
| `As` | `as` | Preload destination; lowercased |
| `ImageSrcset`, `ImageSizes` | `imagesrcset`, `imagesizes` | Responsive image preload |
| `CrossOrigin` | `crossorigin` | `anonymous` or `use-credentials`; else `media_crossorigin_invalid` |
| `Integrity` | `integrity` | Subresource Integrity hash |
| `Title` | `title` | Names an alternate stylesheet or feed |
| `FetchPriority` | `fetchpriority` | [TFetchPriority](./TFetchPriority.md) |
| `ReferrerPolicy` | `referrerpolicy` | [TReferrerPolicy](./TReferrerPolicy.md) |
| (automatic) | `nonce` | The request CSP nonce (`TJavaScript::getScriptNonce()`) on a `stylesheet`, `preload`, or `modulepreload` link |

## Gotchas

- A font preload needs `CrossOrigin`, even from the same origin; without it the browser fetches the font twice.
- Page and theme stylesheets register through `TClientScriptManager::registerStyleSheetFile()`, which orders them with the theme and reaches a callback. A `stylesheet` TLinkTag renders only on a full page.
- The collection is stored in THead viewstate, as `MetaTags` is.
- THead.ShortcutIcon still renders its own `<link rel="shortcut icon">`.

## Tests

- `tests/unit/Web/UI/WebControls/TLinkTagTest.php`

## See Also

- [THead](./THead.md)
- [TLinkTagCollection](./TLinkTagCollection.md)
- [TMetaTag](./TMetaTag.md)

**@since 4.4.0**
