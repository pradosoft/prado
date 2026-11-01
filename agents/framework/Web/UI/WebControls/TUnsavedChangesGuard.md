# Web/UI/WebControls/TUnsavedChangesGuard

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TUnsavedChangesGuard`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TUnsavedChangesGuard.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TUnsavedChangesGuard marks the fields a user changed and warns through `beforeunload` while changes are unsaved (Livewire `wire:dirty`). It is a `TWebControl` rendering an empty `<span id>`; the client class `Prado.WebUI.TUnsavedChangesGuard` (`controls/unsavedchanges.js`, package `unsavedchanges`) does the work. [TFormGuard](./TFormGuard.md) guards submission against bots; this control guards the user's unsaved input.

```php
<com:TUnsavedChangesGuard ID="Guard" ControlToWatch="EditPanel"
    StatusText="You have unsaved changes." DirtyCssClass="changed" />
<com:TTextBox ID="Search" Dataset.prado-dirty-ignore="true" />
```

**Scope:** the form holding the guard, or the element of `ControlToWatch` (resolved by `findControl()`, must render its ClientID, such as a TPanel). An unknown ID throws `unsavedchangesguard_controltowatch_invalid`.

## Dirty Detection (client)

The baseline is the rendered default, so typing and undoing clears the mark, and a browser-restored value (back/reload) counts as changed.

| Field | Changed when |
|---|---|
| text-like `<input>` | `value` differs from `defaultValue` sanitized by a detached clone (range midpoint, lowercase color) |
| `<textarea>` | `value` differs from `defaultValue`, line breaks normalized |
| checkbox, radio | `checked` differs from `defaultChecked` |
| select-one | `selectedIndex` differs from the last `defaultSelected` option, else the first enabled option (display size 1), else -1 |
| select-multiple | any option's `selected` differs from `defaultSelected` |
| file | `files` differ from the baseline (none, or the files at `markClean()`) |

Not watched: `hidden`, `button`, `submit`, `reset`, `image` inputs, and a field inside `[data-prado-dirty-ignore]`, which a template sets with `Dataset.prado-dirty-ignore="true"` (the `TWebControl` data-* collection adds the prefix). `TClientScriptManager` renders the Prado state fields (`PRADO_PAGESTATE`, `PRADO_CSRF_TOKEN`, registered hidden fields) as `type="text"` inputs (#642) in a container with that attribute. The scope is re-checked on `input`/`change` (capturing), `prado:callbackUpdated`, form `reset`, and `pageshow`.

## Client Markup

| State | Markup |
|---|---|
| field changed | `data-prado-dirty` and `DirtyCssClass` on the field; a radio only while checked |
| scope dirty | `data-prado-unsaved` on the scope element |
| scope state changes | `prado:dirtyChange` on the scope, `detail.dirty` |
| `StatusText` set | guard span is `role="status"` (unless `Role` is set); holds the text while dirty, empty while clean |

## Leave Warning

`WarnOnLeave` (default true) installs the `beforeunload` listener only while dirty (an installed listener costs the back-forward cache in some browsers).

| Navigation | Warns |
|---|---|
| link, address bar, reload, close, a submit of another form | yes, while dirty |
| submit of the guard's form in this window (native button, `Prado.PostBack`, TLinkButton, auto-postback, `form.submit()`) | no: `formdata` on the form sets `leaving` |
| submit with `target`/`formtarget` naming another window | yes (the page stays) |
| cancelled submit (validation) | no `formdata`, so yes |
| callback | no navigation |
| callback redirect | no: `prado:callbackRedirect` sets `leaving` |

`leaving` clears on the `beforeunload` it lets pass, on `pageshow`, and on an edit after the task of the submission. A TCheckBox posts back on `click`, before its own `input`/`change`, so edits in the same task keep it.

## Baseline Reset

| Event | Result |
|---|---|
| postback | posted values render as defaults: clean |
| postback with a failed validator | `getRendersDirty()` true: options `Dirty: true`, status text rendered |
| callback re-rendering fields (Replace or Morph) | new `value`/`checked`/`selected` attributes: clean after `prado:callbackUpdated` |
| server `markClean()` in a callback | `callClientFunction('Prado.WebUI.TUnsavedChangesGuard.markClean', [guard])` |
| client `markClean()` | writes `defaultValue`/`defaultChecked`/`defaultSelected` from the current state, so a form reset returns to it |
| `markDirty()` (server or client) | scope dirty without a changed field, until `markClean()` |

Outside a callback, `markClean()`/`markDirty()` set the state the guard renders (`_dirty`, per request, not in viewstate).

A callback re-registers the guard's end script, so a new wrapper replaces the old one. `replace($super, ...)` carries over `forced` (markDirty) and the file baselines; the first `setDirty()` of a wrapper syncs `data-prado-unsaved`, the status text, and the `beforeunload` listener from the markup the old wrapper left, and dispatches `prado:dirtyChange` only when the state differs from it.

## Key Properties/Methods

- `ControlToWatch` - ID path of the watched container; '' is the form
- `WarnOnLeave` - default true
- `DirtyCssClass` - class(es) on a changed field; default ''
- `StatusText` - live-region text while dirty; default ''
- `markClean()` / `markDirty()` - set the state; in a callback, sent to the client
- `getRendersDirty()` - the rendered state: the marked state, else a postback with a failed validator

Client: `isDirty()`, `dirtyFields()`, `update()`, `markClean()`, `markDirty()`, and the static `Prado.WebUI.TUnsavedChangesGuard.markClean(id)` / `markDirty(id)`.

## Omissions

- The server does not learn which fields changed.
- contenteditable and THtmlArea/THtmlArea5 editors are not watched; TinyMCE writes its textarea on save.
- A field outside a container scope that uses the `form` attribute is not watched; a form scope uses `form.elements` and includes it.
- The browser's own `beforeunload` text is shown; browsers ignore custom text.

## Accessibility

Audited in 4.4.0. The field mark is a data attribute and class for the app's styles; the app gives it a non-color cue (an outline style, an icon, or text). `StatusText` gives a text indicator: a `role="status"` live region, announced once per clean-to-dirty change, empty while clean. The guard adds no focusable content. The leave warning is the browser's native dialog. `tests/playwright/web/TUnsavedChangesGuardTestCase.spec.js` checks the status region text and an outline mark.

## See Also

- [TFormGuard](./TFormGuard.md)
- [TActiveUpdateMode](../ActiveControls/TActiveUpdateMode.md) - morphed callbacks keep fields; their defaults update
- [packages](../../Javascripts/packages.md)
