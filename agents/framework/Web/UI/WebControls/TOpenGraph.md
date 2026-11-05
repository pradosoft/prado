# Web/UI/WebControls/TOpenGraph

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TOpenGraph`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TOpenGraph.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TControl`

## Overview
TOpenGraph renders the Open Graph `og:*` and X (Twitter) Card `twitter:*` meta tags that social platforms read to preview a shared page. Each non-empty property renders one [TMetaTag](./TMetaTag.md). It renders where it is placed, usually in [THead](./THead.md) or a content placeholder of the head, so each page can supply its own tags.

## Template Usage

```xml
<com:THead>
  <com:TOpenGraph Title="Blue Widget" Description="A widget, in blue."
    Url="https://example.com/widgets/blue" Image="https://example.com/img/blue.png"
    ImageAlt="A blue widget" SiteName="Acme" TwitterCard="summary_large_image">
    <com:TMetaTag Property="product:price:amount" Content="9.95" />
  </com:TOpenGraph>
</com:THead>
```

## Properties

Tags render in this order; all properties are viewstate.

| Property | Meta tag | Notes |
|---|---|---|
| `Title` | `og:title` | |
| `Type` | `og:type` | Default `website` (`DEFAULT_TYPE`); empty renders none |
| `Url` | `og:url` | Absolute canonical URL |
| `Description` | `og:description` | |
| `SiteName` | `og:site_name` | |
| `Locale` | `og:locale` | Such as `en_US` |
| `Image` | `og:image` | Absolute URL |
| `ImageAlt` | `og:image:alt` | |
| `ImageWidth`, `ImageHeight` | `og:image:width`, `og:image:height` | Integers; 0 renders none |
| `TwitterCard` | `twitter:card` | `summary`, `summary_large_image`, `app`, `player` |
| `TwitterSite` | `twitter:site` | Site `@handle` |
| `TwitterCreator` | `twitter:creator` | Author `@handle` |
| `MetaTags` | | `TMetaTagCollection`; a TMetaTag in the body joins it and renders after the property tags |

`og:*` tags use the `property` attribute (`TMetaTag::Property`); `twitter:*` tags use `name`. `createPropertyMetaTags()` returns the property tags.

## Gotchas

- An X Card without its own `twitter:title`, `twitter:description`, or `twitter:image` uses the `og:*` ones, so TOpenGraph renders no twitter duplicates.
- `Url` and `Image` render as given. The control does not derive an absolute URL from the request, whose Host header a client controls.
- `Title` does not fall back to the page title.
- Child controls render after the meta tags.

## Tests

- `tests/unit/Web/UI/WebControls/TOpenGraphTest.php`

## See Also

- [THead](./THead.md)
- [TMetaTag](./TMetaTag.md)
- [TJsonLd](./TJsonLd.md)

**@since 4.4.0**
