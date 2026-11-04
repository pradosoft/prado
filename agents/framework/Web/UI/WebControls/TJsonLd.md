# Web/UI/WebControls/TJsonLd

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TJsonLd`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TJsonLd.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TControl`

## Overview
TJsonLd renders structured data, such as a schema.org Organization, Product, or BreadcrumbList, as a `<script type="application/ld+json">` data block. It renders where it is placed: in [THead](./THead.md), a content placeholder of the head, or the body. It renders nothing when `Data` is empty.

## Template Usage

```xml
<com:THead>
  <com:TJsonLd ID="Organization">
    <prop:Data>{"@type": "Organization", "name": "Acme", "url": "https://example.com"}</prop:Data>
  </com:TJsonLd>
</com:THead>
```

```php
$this->Organization->setData(['@type' => 'Organization', 'name' => 'Acme']);
```

## Properties

| Property | Notes |
|---|---|
| `Data` | Array, `JsonSerializable` object, JSON text, or null; JSON text is decoded into an array, else `jsonld_data_invalid`. Viewstate. |
| `Context` | Default `https://schema.org` (`DEFAULT_CONTEXT`); prepended as `@context` to an associative `Data` without one. Empty adds none. |
| `Json` (read-only) | The encoded document; empty when `Data` is null or `[]` |

## Encoding

- `TJavaScript::jsonEncode()` with `JSON_HEX_TAG | JSON_UNESCAPED_SLASHES`: `<` and `>` become `<`/`>`, so a value cannot close the script or open a comment.
- Non-ASCII characters are `\u` escaped, so the block is correct in any page charset; `jsonEncode()` transcodes a non-UTF-8 globalization charset first.
- No CDATA wrapper (`TJavaScript::renderScriptBlock()` adds one), since it would make the JSON invalid.
- The request CSP nonce (`TJavaScript::getScriptNonce()`) renders when active. CSP does not govern data blocks; the nonce keeps every inline `<script>` of the page tagged.

## Tests

- `tests/unit/Web/UI/WebControls/TJsonLdTest.php`

## See Also

- [THead](./THead.md)
- [TOpenGraph](./TOpenGraph.md)
- `TMediaType::JSON_LD`

**@since 4.4.0**
