# Web/UI/WebControls/THeader

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THeader`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THeader.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
THeader represents the HTML5 `<header>` element — a container for introductory content or a set of navigational links. Typically used for a page masthead, section heading, or banner.

The default tag is `header`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:THeader CssClass="site-header">
    <com:TNav><!-- navigation --></com:TNav>
</com:THeader>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'header'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'header'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [TFooter](./TFooter.md)
- [TNav](./TNav.md)

**@since 4.3.3**
