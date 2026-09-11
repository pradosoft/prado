# Web/UI/WebControls/TSlider

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSlider`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSlider.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TSlider displays a slider for numeric input. It consists of a track defining the range and a handle that slides to select a value. Supports horizontal and vertical directions, step sizes, and custom values.

## Key Properties/Methods

- `getDirection()` / `setDirection($value)` - Horizontal or Vertical
- `getMinValue()` / `setMinValue($value)` - Minimum value (default 0.0)
- `getMaxValue()` / `setMaxValue($value)` - Maximum value (default 100.0)
- `getStepSize()` / `setStepSize($value)` - Step between values
- `getValue()` / `setValue($value)` - Current slider value
- `getValues()` / `setValues($value)` - Array of allowed values
- `getProgressIndicator()` / `setProgressIndicator($value)` - Whether to show progress bar
- `getCssUrl()` / `setCssUrl($value)` - Custom CSS file URL
- `getAutoPostBack()` / `setAutoPostBack($value)` - Auto postback on change
- `getClientSide()` - Gets TSliderClientScript for event handlers
- `onValueChanged($param)` - Event raised when value changes

## See Also

- [TSliderClientScript](./TSliderClientScript.md)
- [TSliderDirection](./TSliderDirection.md)
- [IDataRenderer](../../../IDataRenderer.md)

## Color Scheme

The slider body and track backgrounds adapt through `light-dark()`, as do the body border, the track border and the handle. The progress fill stays red in both schemes. The handle is drawn in CSS rather than loaded as a bitmap, because `light-dark()` resolves colors and not images, so a PNG handle could not follow a declared scheme; `TSliderHandleHorizontal.png` and `TSliderHandleVertical.png` are no longer referenced. The shipped stylesheet declares no `color-scheme` of its own, so an
application that declares none keeps the light rendering it already has. See
[assets/INDEX.md](./assets/INDEX.md) for the contract.
