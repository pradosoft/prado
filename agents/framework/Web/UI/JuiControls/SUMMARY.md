# Web/UI/JuiControls/SUMMARY.md

jQuery UI widget wrappers for the Prado framework; each JuiControl configures and initializes a jQuery UI widget with optional AJAX callback support.

## Classes

- **`IJuiOptions`** — Interface: `getOptions()`, `getValidOptions()`, `getValidEvents()`, `getWidget()`, `getWidgetID()`.

- **`TJuiControlAdapter`** — Extends `TActiveControlAdapter`; publishes jQuery UI CSS/JS assets; sets up `TJuiCallbackPageStateTracker`.

- **`TJuiControlOptions`** — Stores jQuery widget options in viewstate; `setOption($name, $value)` / `getOption($name)`.

- **`TJuiCallbackPageStateTracker`** — Tracks changes to `JuiOptions` during callbacks.

- **`TJuiDatePicker`** — Extends `TActiveTextBox`; culture-aware date format; `OnDateChanged` callback.

- **`TJuiDialog`** — Modal/non-modal popup; `OnClose` callback; content set via child controls.

- **`TJuiSlider`** — Range or single-value slider; `OnSlide`, `OnStop` callbacks.

- **`TJuiAutoComplete`** — Text input with server-provided suggestions; `OnSuggest` callback returns items.

- **`TJuiDraggable`** — Makes child controls draggable; `OnStop` callback with position.

- **`TJuiDroppable`** — Drop target; `OnDrop` callback with dragged control info.

- **`TJuiSortable`** — Reorderable list; `OnUpdate` callback with new order.

- **`TJuiSelectable`** — Multi-select lasso; `OnStop` callback with selected items.

- **`TJuiResizable`** — Resizable container; `OnStop` callback with new dimensions.

- **`TJuiProgressbar`** — Progress display; `Value` and `Max` properties; `OnComplete` callback.
