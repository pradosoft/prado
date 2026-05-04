# Web/UI/IButtonControl

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / **`IButtonControl`**

**Location:** `framework/Web/UI/IButtonControl.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface for button controls. Defines common properties and events for buttons.

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `Text` | `string` | Button caption |
| `CausesValidation` | `bool` | Whether button triggers validation |
| `CommandName` | `string` | Command name for OnCommand event |
| `CommandParameter` | `string` | Parameter for OnCommand event |
| `ValidationGroup` | `string` | Group of validators |
| `IsDefaultButton` | `bool` | Whether button is default button |

## Key Events

| Event | Description |
|-------|-------------|
| `onClick(TEventParameter $param)` | Raised when button is clicked |
| `onCommand(TCommandEventParameter $param)` | Raised when command is fired |

## See Also

- [TButton](./TButton.md) - Button implementation
- [TLinkButton](./TLinkButton.md) - Link button implementation
- [TEventParameter](./TEventParameter.md)
- [TCommandEventParameter](./TCommandEventParameter.md)
