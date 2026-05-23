# Web/UI/WebControls/TMain

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMain`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMain.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TMain represents the HTML5 `<main>` element — the dominant content area of the `<body>`. The main content consists of content directly related to or expanding upon the central topic of the document. There should be only one `<main>` element per page.

The default tag is `main`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TMain CssClass="content">
    <!-- primary page content here -->
</com:TMain>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'main'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'main'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [THeader](./THeader.md)
- [TFooter](./TFooter.md)

**@since 4.3.3**
