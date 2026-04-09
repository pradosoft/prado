# TActiveCustomValidatorClientSide

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveCustomValidatorClientSide](./TActiveCustomValidatorClientSide.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveCustomValidatorClientSide.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Client-side options for [TActiveCustomValidator](./TActiveCustomValidator.md). Provides OnValidate, OnValidationSuccess, and OnValidationError events plus ObserveChanges option to revalidate when control value changes.

## Key Properties/Methods

- `setOnValidate($javascript)` / `getOnValidate()` - Before validation functions called
- `setOnValidationSuccess($javascript)` / `getOnValidationSuccess()` - After successful validation
- `setOnValidationError($javascript)` / `getOnValidationError()` - After validation failure
- `setObserveChanges($value)` / `getObserveChanges()` - Revalidate when control changes

## See Also

- [TCallbackClientSide](./TCallbackClientSide.md), [TActiveCustomValidator](./TActiveCustomValidator.md)
