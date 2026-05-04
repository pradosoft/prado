# Web/UI/WebControls/TLiteral

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLiteral`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLiteral.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TLiteral displays static text on a Web page without any style properties. Unlike TLabel, it does not have style properties like BackColor or Font. Text can be HTML-encoded if needed.

## Key Properties/Methods

- `getText()` / `setText()` - Gets or sets the static text
- `getData()` / `setData()` - IDataRenderer implementation (same as Text)
- `getEncode()` / `setEncode()` - Gets or sets whether text should be HTML-encoded
- `render()` - Renders the literal text

## See Also

- [TControl](./TControl.md)
- [TLabel](./TLabel.md)
