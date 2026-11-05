# Web/UI/WebControls/TArticle

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TArticle`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TArticle.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TArticle represents the HTML5 `<article>` element — a self-contained composition in a document, page, application, or site (e.g., a blog post, news article, or forum post).

The default tag is `article`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)), for example from a theme or template.

## Template Usage

```xml
<com:TArticle ID="post" CssClass="blog-post">
    <!-- article content here -->
</com:TArticle>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'article'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'article'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [TSection](./TSection.md)
- [TWebControlDecorator](./TWebControlDecorator.md)

**@since 4.3.3**
