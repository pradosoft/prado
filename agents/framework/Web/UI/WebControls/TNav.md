# Web/UI/WebControls/TNav

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TNav`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TNav.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TNav represents the HTML5 `<nav>` element — a section of a page that links to other pages or to parts within the page (a navigation directory).

The default tag is `nav`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TNav CssClass="main-nav">
    <com:THyperLink NavigateUrl="/home" Text="Home" />
    <com:THyperLink NavigateUrl="/about" Text="About" />
</com:TNav>
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'nav'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'nav'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [THeader](./THeader.md)
- [TAside](./TAside.md)

**@since 4.3.3**
