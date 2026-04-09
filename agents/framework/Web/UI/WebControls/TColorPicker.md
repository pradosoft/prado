# TColorPicker

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TColorPicker](./TColorPicker.md)

**Location:** `framework/Web/UI/WebControls/TColorPicker.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Color picker control. Extends `TTextBox`, displaying a color swatch button that opens a popup HSB color picker widget. The selected color is stored as a hex string (e.g., `#ff3300`) in the text box.

JavaScript: `colorpicker/colorpicker.js` (published via `TAssetManager`).

Extends `[TTextBox](./TTextBox.md)`.

## Constants

```php
TColorPicker::SCRIPT_PATH = 'colorpicker'
```

## TColorPickerMode Enum

```php
TColorPickerMode::Simple  // Simple color button only (no popup, just a swatch)
TColorPickerMode::Basic   // Basic popup picker (default)
TColorPickerMode::Full    // Full HSB picker with hex/RGB inputs
```

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Mode` | TColorPickerMode | Basic | Picker style |
| `ShowColorPicker` | bool | true | Whether to show the popup picker button |
| `ColorPickerStyle` | string | `default` | CSS theme for the picker widget |
| `OKButtonText` | string | `OK` | OK button label |
| `CancelButtonText` | string | `Cancel` | Cancel button label |

## Value Storage

The control stores the selected color as a hex string in the text box `Text` property:

```php
$color = $picker->getText();   // e.g., '#ff3300'
$picker->setText('#00aaff');   // set programmatically
```

## Template Usage

```xml
<com:TColorPicker ID="bgColor" Mode="Full" />
```

```xml
<com:TColorPicker ID="textColor"
                  Mode="Basic"
                  Text="#336699"
                  OKButtonText="Choose"
                  CancelButtonText="Close" />
```

## Rendering

`TColorPicker` renders a text input (from `TTextBox`) plus an adjacent `<div class="TColorPicker_button">` swatch. The JS binds to the swatch to open the picker popup.

`renderEndTag()` outputs the swatch `<div>` immediately after the `</input>`.

## Key Methods (inherited from TTextBox + additions)

```php
$picker->getMode(): TColorPickerMode
$picker->setMode($v): void
$picker->getShowColorPicker(): bool
$picker->setShowColorPicker(bool $v): void
$picker->getColorPickerStyle(): string
$picker->setColorPickerStyle(string $v): void
$picker->getText(): string    // hex color value, e.g. '#ffffff'
$picker->setText(string $v): void
```

## Patterns & Gotchas

- **Hex format** — the value is stored as `#rrggbb`. Validate format server-side with `TPropertyValue::ensureHexColor()` if needed.
- **`Mode=Simple`** — shows only a colored swatch button; clicking it does not open a picker. Useful for displaying a color without allowing user editing.
- **`ShowColorPicker=false`** — hides the swatch button entirely, leaving only the text input. The user must type hex values manually.
- **No AutoPostBack** — color picker changes do not auto-submit. Use a submit button or call JavaScript to trigger form submission.
- **`TWebColors`** — provides a mapping of CSS named colors to hex values. Use `TWebColors::colorValues()` to populate a predefined color list for users.
