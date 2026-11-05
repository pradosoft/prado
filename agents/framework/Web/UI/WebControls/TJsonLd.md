# Web/UI/WebControls/TJsonLd

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TJsonLd`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TJsonLd.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TControl`

## Overview
TJsonLd renders structured data, such as a schema.org Organization, Product, or BreadcrumbList, as a `<script type="application/ld+json">` data block. Its body is the JSON document; page code sets `StructuredData` instead. It renders where it is placed: in [THead](./THead.md), a content placeholder of the head, or the body. It renders nothing when it has no document.

## Template Usage

```xml
<com:THead>
  <com:TJsonLd>
    {"@type": "Organization", "name": "Acme", "url": "https://example.com"}
  </com:TJsonLd>
  <com:TJsonLd ID="ProductLd" />
</com:THead>
```

```php
$this->ProductLd->setStructuredData(['@type' => 'Product', 'name' => $product->Name]);
```

## Document Sources

| Source | Use | Invalid JSON |
|---|---|---|
| `StructuredData` | Document built in code: array, `JsonSerializable` object, or JSON text (decoded into an array). Viewstate. Takes precedence over the body. | `jsonld_json_invalid` on set |
| Body | Fixed document written in the template; rendered, then decoded | `jsonld_json_invalid` on render |

`Context` (default `https://schema.org`, `DEFAULT_CONTEXT`) is prepended as `@context` to an associative document without one; empty adds none.

## Gotchas

- An expression (`<%= %>`, `<%# %>`) in the body inserts its text unescaped; a `"` or `\` in the value makes the body invalid JSON. Dynamic values belong in `StructuredData`, which encodes every value.
- The body renders through `renderBody()`, which swaps the writer's output for a `TTextWriter` and restores it, as TTeleport does, so the render filter context is kept.
- `Data` is the `IDataRenderer` property of data-bound controls (TButton, THyperLink, repeater items); TJsonLd uses `StructuredData` to avoid that meaning.

## Encoding

- `TJavaScript::jsonEncode()` with `JSON_HEX_TAG | JSON_UNESCAPED_SLASHES`: `<` and `>` become `\u003C`/`\u003E`, so a value cannot close the script or open a comment.
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
