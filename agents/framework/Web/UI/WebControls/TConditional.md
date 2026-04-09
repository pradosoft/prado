# TConditional

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TConditional](./TConditional.md)

**Location:** `framework/Web/UI/WebControls/TConditional.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TConditional displays content based on evaluation of a PHP expression. If true, instantiates TrueTemplate; otherwise, FalseTemplate. Evaluated at early stage (before onInit).

## Key Properties/Methods

- `Condition` - PHP expression for determining which template to use
- `TrueTemplate` - Template applied when condition is true
- `FalseTemplate` - Template applied when condition is false
- `createChildControls()` - Evaluates condition and instantiates appropriate template

## See Also

- [TControl](./TControl.md)
