# Web/UI/IValidatable

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / **`IValidatable`**

**Location:** `framework/Web/UI/IValidatable.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface for controls that can be validated by validators.

## Key Methods

| Method | Description |
|--------|-------------|
| `getValidationPropertyValue(): mixed` | Returns value to be validated |
| `getIsValid(): bool` | Whether validation passed |
| `setIsValid(bool $value)` | Sets validation status |

## See Also

- [TControl](./TControl.md) - Base control class
- [IValidator](./IValidator.md) - Validator interface
