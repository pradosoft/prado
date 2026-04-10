# Web/UI/TSkinTemplate

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TSkinTemplate`**

## Class Info
**Location:** `framework/Web/UI/TSkinTemplate.php`
**Namespace:** `Prado\Web\UI`

## Overview
Extends [TTemplate](./TTemplate.md) specifically for parsing `.skin` files used by the theming system. The single behavioral difference from `TTemplate` is that **attribute validation is disabled** before parsing begins.

This allows skin files to reference control classes or properties that may not be available in all installations, making themes more portable. Parse-time errors that would normally abort template parsing are deferred: errors surface only when the skin is actually applied to a control and a property assignment fails.

## Key Constants

None beyond those inherited from [TTemplate](./TTemplate.md).

## Key Properties

None beyond those inherited from [TTemplate](./TTemplate.md).

## Key Methods

- `__construct($template, $contextPath, $tplFile = null, $startingLine = 0, $sourceTemplate = true)` — Calls `$this->setAttributeValidation(false)` **before** calling `parent::__construct()`. This ensures the parser runs with validation off for the entire skin file parse pass.

## Patterns & Gotchas

- **Attribute validation is off** — unknown attributes and non-existent control classes in `.skin` files do not throw exceptions during parsing. This is intentional for portability; errors are deferred to property-set time.
- **Errors appear at skin application time** — if a skin references a class that does not exist or a property that the control doesn't have, the error is raised when [TTheme](./TTheme.md) applies the skin to a control, not at parse time.
- **Identical API to `TTemplate`** — callers use this exactly like `TTemplate`; the only difference is internal validation behavior.
- **Used by `TTheme`** — [TTheme](./TTheme.md) uses `TSkinTemplate` (not `TTemplate`) when parsing `.skin` files so that themes remain loadable even in environments where some control classes are absent.
- **`$sourceTemplate = true` default** — skin files are always treated as source templates (loaded from external storage), consistent with `TTemplate`'s usage conventions.
- **`@since 4.2.0`** — introduced in 4.2.0 as a separation of skin-parsing behavior from regular template parsing.

(End of file - total 31 lines)
