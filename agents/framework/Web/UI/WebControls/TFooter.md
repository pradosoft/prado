# Web/UI/WebControls/TFooter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFooter`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFooter.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TFooter represents the HTML5 `<footer>` element — a footer for its nearest ancestor sectioning content or sectioning root element. Typically contains authorship information, copyright notices, or navigation links.

The default tag is `footer`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TFooter CssClass="page-footer">
    <p>&copy; 2026 My Site</p>
</com:TFooter>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'footer'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'footer'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [THeader](./THeader.md)
- [TSection](./TSection.md)

**@since 4.3.3**
