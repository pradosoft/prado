# Web/UI/WebControls/TPicture

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPicture`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPicture.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TImage`

## Overview
TPicture represents the HTML `<picture>` element: an image with alternative resources by format, density, or layout. It renders `<picture>`, its child [TMediaSource](./TMediaSource.md) controls, then the `<img>` of its [TImage](./TImage.md) properties. The control's attributes (`ID`, `CssClass`, `Style`) render on the `<img>`; the `<picture>` has none.

## Template Usage

```xml
<com:TPicture ImageUrl="chart.png" AlternateText="Sales rose 20% in 2026">
	<com:TMediaSource Srcset="chart.avif" Type="image/avif" />
	<com:TMediaSource Srcset="chart.webp" Type="image/webp" />
</com:TPicture>
```

## Key Properties

| Property | Description |
|---|---|
| `ImageUrl` | Fallback image URL, from TImage |
| `AlternateText` | Text alternative of every source; `alt` always renders |

## Accessibility

- `<picture>` has no role; the `<img>` is the image, named by `AlternateText`.
- Every source shows the same content, so one text alternative describes them all.
- A decorative image keeps AlternateText empty, which renders `alt=""`.

## Tests

- `tests/unit/Web/UI/WebControls/TPictureTest.php`

## See Also

- [TImage](./TImage.md)
- [TMediaSource](./TMediaSource.md)

**@since 4.4.0**
