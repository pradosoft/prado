# Web/UI/WebControls/TValidatorClientSide

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TValidatorClientSide`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TValidatorClientSide.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TValidatorClientSide provides client-side event options for validators. It extends TClientSideOptions and allows custom JavaScript handlers for validation events.

## Key Properties/Methods

- `getOnValidate()` / `setOnValidate(string)` - JavaScript before validation
- `getOnValidationSuccess()` / `setOnValidationSuccess(string)` - JavaScript on success
- `getOnValidationError()` / `setOnValidationError(string)` - JavaScript on error
- `getObserveChanges()` / `setObserveChanges(bool)` - Revalidate on input changes

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TClientSideOptions](./TClientSideOptions.md)
