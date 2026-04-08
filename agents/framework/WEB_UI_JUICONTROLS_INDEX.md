# Web/UI/JuiControls/INDEX.md - WEB_UI_JUICONTROLS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

jQuery UI widget wrappers for the Prado framework. Each JuiControl is a thin PHP class that configures and initialises a jQuery UI widget, with optional AJAX callback support via the `ActiveControls` mechanism.

## Core Infrastructure

- **`IJuiOptions`** — Interface: `getOptions()`, `getValidOptions()`, `getValidEvents()`, `getWidget()`, `getWidgetID()`.

- **`TJuiControlAdapter`** — Extends `TActiveControlAdapter`. Publishes jQuery UI CSS/JS assets. Sets up `TJuiCallbackPageStateTracker` to watch `JuiOptions` changes during callbacks.

- **`TJuiControlOptions`** — Stores jQuery widget options in viewstate. `setOption($name, $value)` / `getOption($name)`. Validates option names against `getValidOptions()`. Options are serialised to JSON and passed to the widget's JavaScript initialiser.

- **`TJuiCallbackPageStateTracker`** — Tracks changes to `JuiOptions` during callbacks so only changed widget options are sent to the client.

## Controls

| Class | jQuery UI Widget | Notes |
|---|---|---|
| `TJuiDatePicker` | `$.datepicker` | Extends `TActiveTextBox`; culture-aware date format; `OnDateChanged` callback |
| `TJuiDialog` | `$.dialog` | Modal/non-modal popup; `OnClose` callback; content set via child controls |
| `TJuiSlider` | `$.slider` | Range or single-value slider; `OnSlide`, `OnStop` callbacks |
| `TJuiAutoComplete` | `$.autocomplete` | Text input with server-provided suggestions; `OnSuggest` callback returns items |
| `TJuiDraggable` | `$.draggable` | Makes child controls draggable; `OnStop` callback with position |
| `TJuiDroppable` | `$.droppable` | Drop target; `OnDrop` callback with dragged control info |
| `TJuiSortable` | `$.sortable` | Reorderable list; `OnUpdate` callback with new order |
| `TJuiSelectable` | `$.selectable` | Multi-select lasso; `OnStop` callback with selected items |
| `TJuiResizable` | `$.resizable` | Resizable container; `OnStop` callback with new dimensions |
| `TJuiProgressbar` | `$.progressbar` | Progress display; `Value` and `Max` properties; `OnComplete` callback |

## Conventions

- **`ValidOptions`** — Each control defines `getValidOptions()` returning the set of jQuery UI options it accepts. Adding an unknown option throws an exception. When wrapping a new jQuery UI widget, populate this list from the jQuery UI docs.
- **`ValidEvents`** — Each control defines `getValidEvents()` for jQuery UI events that trigger AJAX callbacks (e.g., `slide`, `stop`, `select`).
- **Option passing** — PHP options are serialised to JSON and injected into the jQuery widget initialisation call. Use `setOption($key, $value)` in PHP or set `Options.Key` in `.page`/`.tpl` templates.
- **Client-side class** — The JavaScript side is handled by jQuery UI directly; `ajax3.js` wraps callbacks via `Prado.JuiCallback(id, eventType, event, ui, target)`.

## Gotchas

- JuiControls require jQuery UI CSS to be published — `TJuiControlAdapter` does this automatically, but the theme must be configured.
- `TJuiDatePicker` uses a different date format syntax from `TDatePicker` — jQuery UI uses `yy-mm-dd` style tokens, not ICU patterns.
- `TJuiAutoComplete`'s `OnSuggest` callback must return an array of label/value pairs serialised as JSON.
- `TJuiDialog` content is rendered server-side; use `TActivePanel` inside the dialog for dynamic content updates.
