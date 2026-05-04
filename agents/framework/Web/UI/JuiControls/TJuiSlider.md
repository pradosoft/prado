# Web/UI/JuiControls/TJuiSlider

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiSlider`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiSlider.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Slider widget based on jQuery UI Slider. Extends [TActivePanel](../ActiveControls/TActivePanel.md). Can be used for single or range values.

## Key Properties/Methods

- `getOptions()` - Slider options (min, max, step, value, orientation, etc.)
- `getValidOptions()` - Valid options: animate, classes, disabled, max, min, orientation, range, step, value, values
- `getValidEvents()` - Events: change, create, slide, start, stop
- `onChange($params)` - Raises OnChange event
- `onSlide($params)` - Raises OnSlide event
- `onStart($params)` - Raises OnStart event
- `onStop($params)` - Raises OnStop event

## See Also

- [TJuiEventParameter](TJuiEventParameter.md)
