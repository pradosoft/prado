# Web/UI/TCompositeLiteral

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TCompositeLiteral`**

## Class Info
**Location:** `framework/Web/UI/TCompositeLiteral.php`
**Namespace:** `Prado\Web\UI`

## Overview
TCompositeLiteral is used internally by TTemplate to represent consecutive static strings, expressions, and statements. It maintains separate collections for expressions, statements, and databindings, evaluating them in the context of a container component during rendering.

## Key Properties/Methods

- `Container` - The evaluation context for expressions and statements
- `TYPE_EXPRESSION`, `TYPE_STATEMENTS`, `TYPE_DATABINDING` - Item type constants
- `evaluateDynamicContent()` - Evaluates expressions and statements
- `dataBind()` - Performs databindings
- `render($writer)` - Renders the concatenated literal content

## See Also

- [TTemplate](./TTemplate.md)
- [IBindable](./IBindable.md)
- [IRenderable](./IRenderable.md)

(End of file - total 22 lines)
