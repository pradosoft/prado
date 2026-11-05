# Web/UI/TTemplateControlInheritable

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TTemplateControlInheritable`**

## Class Info
**Location:** `framework/Web/UI/TTemplateControlInheritable.php`
**Namespace:** `Prado\Web\UI`

## Overview
TTemplateControlInheritable extends [TTemplateControl](./TTemplateControl.md) to support template inheritance. By default, control templates are expected in files with the same name as the class but with `.tpl` extension in the same directory. When a control inherits another, it uses the base class template unless it defines its own template file.

## Key Properties/Methods

- `createChildControls()` — Loads and instantiates the control template. Uses the control's own `.tpl` file if available; otherwise walks the class hierarchy via `doCreateChildControlsFor`. After instantiating templates, replicates the `TCompositeControl` parent behaviour by calling `$this->dyCreateChildControls()`. **Note:** this replication avoids calling `parent::createChildControls()` directly, which previously caused a bug where the parent chain was skipped.
- `doCreateChildControlsFor($parentClass)` — Recursively creates child controls for the given class and its ancestors up to (but not including) `TTemplateControl`.
- `doTemplateForClass($parentClass)` — Loads and instantiates the template for a specific class via `getTemplateManager()->getTemplateByClassName($parentClass)`.
- `IsSourceTemplateControl` — Returns whether the control loads its template from external storage (file, DB, etc.).

## Patterns & Gotchas

- **Parent chain replication** — `createChildControls` explicitly calls `$this->dyCreateChildControls()` to match `TCompositeControl`'s behaviour rather than calling `parent::createChildControls()`, which would invoke `TTemplateControl::createChildControls` and double-instantiate templates. This was a bug fix introduced in 4.3.3.
- **Template search order** — first checks for an own template (via `getTemplate()`); if none, walks ancestors from root to leaf via `doCreateChildControlsFor`.

## See Also

- [TTemplateControl](./TTemplateControl.md)
- [TTemplate](./TTemplate.md)

(End of file - total 20 lines)
