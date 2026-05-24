# Web/UI/WebControls/TSection

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSection`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSection.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TSection represents the HTML5 `<section>` element — a generic section of a document such as a chapter or a topic. Use `<section>` when the content is a thematic grouping; use `<div>` (or `TPanel`) for purely presentational grouping.

The default tag is `section`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TSection CssClass="intro-section">
    <h2>Introduction</h2>
    <!-- section content -->
</com:TSection>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'section'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'section'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [TArticle](./TArticle.md)
- [TMain](./TMain.md)

**@since 4.3.3**
