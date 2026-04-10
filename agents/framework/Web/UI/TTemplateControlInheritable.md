# Web/UI/TTemplateControlInheritable

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TTemplateControlInheritable`**

## Class Info
**Location:** `framework/Web/UI/TTemplateControlInheritable.php`
**Namespace:** `Prado\Web\UI`

## Overview
TTemplateControlInheritable extends [TTemplateControl](./TTemplateControl.md) to support template inheritance. By default, control templates are expected in files with the same name as the class but with `.tpl` extension in the same directory. When a control inherits another, it uses the base class template unless it defines its own template file.

## Key Properties/Methods

- `createChildControls()` - Loads and instantiates control template, using inherited template if no own template exists
- `doCreateChildControlsFor($parentClass)` - Recursively creates child controls for parent classes
- `doTemplateForClass($parentClass)` - Creates and instantiates template for a specific class
- `IsSourceTemplateControl` - Returns whether the control loads its template from external storage

## See Also

- [TTemplateControl](./TTemplateControl.md)
- [TTemplate](./TTemplate.md)

(End of file - total 20 lines)
