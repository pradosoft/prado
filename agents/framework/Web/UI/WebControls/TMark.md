# Web/UI/WebControls/TMark

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMark`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMark.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TMark represents the HTML5 `<mark>` element — a run of text marked or highlighted for reference purposes (e.g., search result highlighting).

The default tag is `mark`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
Search results for <com:TMark>keyword</com:TMark> found.
```

## Key Properties

Inherits all properties from [TWebControl](./TWebControl.md) and [THtmlElement](./THtmlElement.md):

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'mark'`. Override via theme or template. |
| `IsMutated` | `true` if `TagName` differs from the default `'mark'` |
| `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` | Standard [TWebControl](./TWebControl.md) properties |

## See Also

- [THtmlElement](./THtmlElement.md)
- [TWebControl](./TWebControl.md)

**@since 4.3.3**
