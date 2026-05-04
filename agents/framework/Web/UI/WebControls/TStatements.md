# Web/UI/WebControls/TStatements

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TStatements`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TStatements.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TStatements executes PHP statements during the rendering stage and displays the output. The statements are set via the Statements property and execute in the context of the TStatements object itself. Use with caution as it allows arbitrary PHP execution.

## Key Properties/Methods

- `getStatements()` / `setStatements(string)` - PHP statements to execute
- `render($writer)` - Executes statements and writes output

## See Also

- [TControl](./TControl.md)
