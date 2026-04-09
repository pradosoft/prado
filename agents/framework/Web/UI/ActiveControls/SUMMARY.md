# SUMMARY.md

AJAX-enabled controls triggering server-side processing via XMLHttpRequest callbacks instead of full-page form postbacks.

## Classes

- **`IActiveControl`** — Interface: `getActiveControl()` returns `TBaseActiveControl` or `TBaseActiveCallbackControl`.

- **`ICallbackEventHandler`** — Extends `IActiveControl`; interface: `raiseCallbackEvent(TCallbackEventParameter $param)`.

- **`TActiveControlAdapter`** — Wraps any control to add active/callback support; publishes `ajax` script package.

- **`TBaseActiveControl`** — Options holder (`TMap`); `setOption($name, $value)` / `getOption($name)`; `canUpdateClientSide()`.

- **`TBaseActiveCallbackControl`** — Extends `TBaseActiveControl`; adds `TCallbackClientSide` options and `CausesValidation`, `ValidationGroup` properties.

- **`TActivePageAdapter`** — `TPage` adapter; intercepts callback requests, handles `X-PRADO-*` response headers.

- **`TCallbackClientScript`** — Server-side API for client DOM updates; methods: `update()`, `replace()`, `insertContent()`, `setAttribute()`, `show()`, `hide()`, `focus()`, `redirect()`, `appendFunction()`.

- **`TCallbackResponseAdapter`** — Overrides `THttpResponse` to send callback-specific headers instead of normal HTML.

- **`TCallbackClientSide`** — Stores client-side options: `OnSuccess`, `OnFailure`, `OnLoading`, `OnComplete` JavaScript handlers.

- **`TActiveButton`** / **`TActiveLinkButton`** / **`TActiveImageButton`** — Callback on click.

- **`TActiveCheckBox`** / **`TActiveRadioButton`** — Callback on check/uncheck or selection.

- **`TActiveCheckBoxList`** / **`TActiveRadioButtonList`** — Callback on any item check change.

- **`TActiveTextBox`** / **`TActiveDropDownList`** / **`TActiveListBox`** — Callback on text change or selection change.

- **`TActivePanel`** — Container with `refresh()` method for server-side HTML update.

- **`TActiveDataGrid`** / **`TActiveDataList`** / **`TActiveRepeater`** — Data controls with callback paging/sorting/editing.

- **`TActiveFileUpload`** — Async upload via hidden iframe; `OnFileUpload` event.

- **`TActiveDatePicker`** — Date picker with `OnDateChanged` callback.

- **`TInPlaceTextBox`** — Click-to-edit label; `LoadTextOnEdit` option.

- **`TActiveCustomValidator`** — Server-side validation via callback.

- **`TTimeTriggeredCallback`** / **`TValueTriggeredCallback`** / **`TEventTriggeredCallback`** — Timer/poll/event-based callbacks.
