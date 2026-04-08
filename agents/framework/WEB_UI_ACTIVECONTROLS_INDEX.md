# Web/UI/ActiveControls/INDEX.md - WEB_UI_ACTIVECONTROLS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

AJAX-enabled controls for the Prado framework. ActiveControls trigger server-side processing via XMLHttpRequest callbacks instead of full-page form postbacks. The browser DOM is updated in place from the server's response.

## How Callbacks Work

1. User interacts with an ActiveControl (click, change, timer, etc.).
2. JavaScript (`ajax3.js`) sends an `XMLHttpRequest` to the server with `X-PRADO-CALLBACK` headers identifying the target control and parameters.
3. `TActivePageAdapter` intercepts the request: runs the normal page lifecycle but routes to `raiseCallbackEvent()` instead of `raisePostBackEvent()`.
4. The control's `raiseCallbackEvent()` handler executes server logic and optionally calls `TCallbackClientScript` methods to push DOM updates.
5. Response is sent via `X-PRADO-*` headers (not HTML body):
   - `X-PRADO-ACTIONS` — JSON array of client-side DOM commands
   - `X-PRADO-DATA` — arbitrary return data
   - `X-PRADO-PAGESTATE` — updated page state token
   - `X-PRADO-SCRIPTLIST` / `X-PRADO-STYLESHEET` — dynamic asset loading
6. JavaScript processes the response and applies updates without a page reload.

## Core Classes

- **`IActiveControl`** — Interface: `getActiveControl()` returns a `TBaseActiveControl` or `TBaseActiveCallbackControl`.

- **`ICallbackEventHandler`** — Extends `IActiveControl`. Interface: `raiseCallbackEvent(TCallbackEventParameter $param)`. Implement this on any control that should respond to AJAX callbacks.

- **`TActiveControlAdapter`** — Wraps any control to add active/callback support. Instantiates `TBaseActiveControl` (or `TBaseActiveCallbackControl` for `ICallbackEventHandler` controls). Publishes the `ajax` script package. Tracks viewstate changes via `TCallbackPageStateTracker` to send only changed properties.

- **`TBaseActiveControl`** — Options holder (`TMap`). `setOption($name, $value)` / `getOption($name)`. `canUpdateClientSide()` returns `true` when: control initialized, not loading post data, is a callback request, `EnableUpdate=true`, and control is visible.

- **`TBaseActiveCallbackControl`** — Extends `TBaseActiveControl`. Adds `TCallbackClientSide` options and `CausesValidation`, `ValidationGroup` properties.

- **`TActivePageAdapter`** — `TPage` adapter. Intercepts callback requests. Handles `X-PRADO-*` response headers. Manages deferred control rendering. Traps errors into the callback error handler.

- **`TCallbackClientScript`** — Server-side API for client DOM updates. Key methods:
  - `update($controlID, $html)` — Replace innerHTML
  - `replace($controlID, $html)` — Replace element
  - `insertContent($position, $controlID, $html)` — Insert HTML (before/after/top/bottom)
  - `setAttribute($controlID, $attr, $value)` — Set attribute
  - `show($controlID)` / `hide($controlID)` — Toggle visibility
  - `focus($controlID)` — Set focus
  - `redirect($url)` — Client-side redirect
  - `appendFunction($js)` — Execute arbitrary JavaScript

- **`TCallbackResponseAdapter`** — Overrides `THttpResponse` to send callback-specific headers instead of normal HTML.

- **`TCallbackClientSide`** — Stores client-side options: `OnSuccess`, `OnFailure`, `OnLoading`, `OnComplete` JavaScript handlers; `PostState`, `EnablePageStateUpdate`.

## Active Controls

| Class | Base Control | Notes |
|---|---|---|
| `TActiveButton` | TButton | Callback on click |
| `TActiveLinkButton` | TLinkButton | Callback on click |
| `TActiveImageButton` | TImageButton | Callback with x,y coordinates |
| `TActiveCheckBox` | TCheckBox | Callback on check/uncheck |
| `TActiveRadioButton` | TRadioButton | Callback on selection |
| `TActiveCheckBoxList` | TCheckBoxList | Callback on any item check change |
| `TActiveRadioButtonList` | TRadioButtonList | Callback on selection change |
| `TActiveTextBox` | TTextBox | Callback on text change (with `AutoPostBack`) |
| `TActiveDropDownList` | TDropDownList | Callback on selection change |
| `TActiveListBox` | TListBox | Callback on selection change |
| `TActivePanel` | TPanel | Container with `refresh()` method for server-side HTML update |
| `TActiveDataGrid` | TDataGrid | Data grid with callback paging/sorting/editing |
| `TActiveDataList` | TDataList | Data list with callback editing |
| `TActiveRepeater` | TRepeater | Repeater with callback support |
| `TActiveFileUpload` | TFileUpload | Async upload via hidden iframe; `OnFileUpload` event |
| `TActiveDatePicker` | TDatePicker | Date picker with `OnDateChanged` callback |
| `TInPlaceTextBox` | (custom) | Click-to-edit label; `LoadTextOnEdit` option |
| `TActiveCustomValidator` | TCustomValidator | Server-side validation via callback |
| `TTimeTriggeredCallback` | (no UI) | Timer-based callback; `Interval`, `StartTimerOnLoad` |
| `TValueTriggeredCallback` | (no UI) | Polls a JS expression; fires callback when value changes |
| `TEventTriggeredCallback` | (no UI) | Fires callback on arbitrary DOM event |

## Conventions

- **Implement `ICallbackEventHandler`** on any new active control. The `raiseCallbackEvent()` method is the server-side callback handler.
- **Use `TActiveControlAdapter`** (not subclassing) to add active behaviour to existing controls. The adapter pattern keeps controls decoupled from the callback mechanism.
- **`canUpdateClientSide()`** — always check this before calling `TCallbackClientScript` methods to avoid updating the DOM during non-callback requests.
- **`TCallbackPageStateTracker`** — tracks which viewstate properties changed during a callback. Only changed properties are sent to the client, minimising payload.
- **Client-side class** — the JavaScript counterpart lives in `framework/Web/Javascripts/source/prado/activecontrols/activecontrols3.js` under `Prado.WebUI.<ClassName>`.

## Gotchas

- Callbacks run the **full page lifecycle** (init, load, etc.) — ensure event handlers are idempotent.
- `TActivePageAdapter` suppresses normal HTML rendering; only `X-PRADO-*` headers are sent. Do not `echo` from callback handlers.
- `TTimeTriggeredCallback` keeps firing while the page is open — always implement server-side guards against runaway callbacks.
- `TActiveFileUpload` uses a hidden iframe (not XHR) for upload; progress tracking is limited.
