# Web/UI/WebControls/TAside

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAside`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAside.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TAside represents the HTML5 `<aside>` element — a section of a page consisting of content that is tangentially related to the surrounding content, such as a sidebar, pull quote, or advertisement.

The default tag is `aside`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TAside CssClass="sidebar">
    <!-- sidebar content here -->
</com:TAside>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'aside'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'aside'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [TNav](./TNav.md)
- [TWebControlDecorator](./TWebControlDecorator.md)

**@since 4.3.3**
