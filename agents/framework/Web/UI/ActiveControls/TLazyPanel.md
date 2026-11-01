# Web/UI/ActiveControls/TLazyPanel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TLazyPanel`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TLazyPanel.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Extends:** [TActivePanel](./TActivePanel.md)
**Implements:** [ICallbackEventHandler](./ICallbackEventHandler.md), `INamingContainer`

## Overview
Renders a placeholder, then loads its content by callback when the page loads, when the panel becomes visible, or on a client call (Livewire `lazy`, htmx `revealed`). The first request does none of the content's work. Issue #1408.

```xml
<com:TLazyPanel ID="Stats" Trigger="Visible" RootMargin="200px" OnLoadContent="loadStats">
	<prop:PlaceholderTemplate><p>Loading statistics…</p></prop:PlaceholderTemplate>
	<prop:ContentTemplate><com:TRepeater ID="StatRows">...</com:TRepeater></prop:ContentTemplate>
</com:TLazyPanel>
```

## Properties, Methods, Events

| Member | Description |
|---|---|
| `ContentTemplate` | Instantiated only when the content loads |
| `PlaceholderTemplate` | Instantiated when the panel renders unloaded; its controls render and raise no events |
| `Trigger` | [TLazyPanelTrigger](./TLazyPanelTrigger.md): `Load`, `Visible` (default), `Manual` |
| `RootMargin` | `rootMargin` of the `IntersectionObserver` for `Visible`; default `'0px'` |
| `IsContentLoaded` | Read-only; kept in control state |
| `loadContent($param = null)` | Loads from the server in any request; in a callback the panel renders and replaces the placeholder |
| `OnLoadContent` | Raised after the ContentTemplate is instantiated, once, in the request that loads |
| `ActiveControl.ClientSide` | Options of the load callback; `CausesValidation` defaults to false |

## Lifecycle

| Request | Result |
|---|---|
| Unloaded render | `aria-busy="true"`, body content, the placeholder; registers `Prado.WebUI.TLazyPanel` |
| Load callback (`raiseCallbackEvent`) | `loadContent()`: control state flag, ContentTemplate, OnLoadContent, render → `Prado.Element.replace` |
| Load callback, already loaded | Renders again, so a repeated request still replaces the placeholder |
| Later postback or callback | `loadState()` instantiates the ContentTemplate; its controls take their state from the page state |
| Loaded render | No `aria-busy`, no client script |

- Templates are instantiated with the panel's template control as context (`TTemplate::instantiateIn($tplControl, $panel)`, as `TConditional`), so `$this->StatRows`, `<%= %>` expressions, and `OnClick="handler"` resolve on the declaring page or template control.
- The panel is a naming container: automatic IDs in the content are numbered within the panel, so they match across requests that create the content at different lifecycle points.
- The placeholder is created at render, after the page state is saved, so it adds no state and never shifts content IDs.

## Client (`Prado.WebUI.TLazyPanel`, activecontrols3.js, `ajax` package)

| Condition | Result |
|---|---|
| `Load`, or `Visible` without `IntersectionObserver` | Dispatches in `onInit` |
| `Visible` | `IntersectionObserver` with `rootMargin`; dispatches on the first intersection, then disconnects |
| `Manual` | `Prado.WebUI.TLazyPanel.load(id)` dispatches |
| A request in flight | `load()` does nothing |
| Failure (`onFailure`) | Placeholder kept, `aria-busy` removed, `data-prado-lazy="failed"`; `ClientSide.OnFailure` still runs; `load(id)` retries |

## Accessibility

- `aria-busy="true"` marks the unloaded region; the loaded render has none, so the replacement ends it. A Morph `UpdateMode` keeps the element and removes the attribute.
- The panel is no live region, so arriving content is not announced. For a short result to announce, add `Attributes.aria-live="polite"`; aria-busy holds the announcement until the content is complete.
- The load moves no focus. A placeholder should say what is loading.
- `Visible` content loads when the panel scrolls into view. Screen readers that move the virtual cursor without scrolling may reach the placeholder first; use `Load` for content needed early.

## Gotchas

- Without JavaScript, and for crawlers that do not run it, the placeholder stays. Do not lazy-load content that must be indexed.
- The load callback posts the page inputs (`PostInputs` true) and updates the page state; `EnablePageStateUpdate` false loses the loaded flag on the next request.
- Each panel sends its own callback; the callback queue sends them one at a time.
- Content needs `dataBind()` in `OnLoadContent` for `<%# %>` expressions.
- Not implemented: unloading back to the placeholder, an error template, batching several panels in one request.

## Tests

- `tests/unit/Web/UI/ActiveControls/TLazyPanelTest.php`
- `tests/js/activecontrols/lazypanel.test.js`
- `tests/playwright/active-controls/LazyPanelTestCase.spec.js` with `tests/harness/active-controls/protected/pages/LazyPanelTest.page`

## See Also

- [TActivePanel](./TActivePanel.md), [TActiveUpdateMode](./TActiveUpdateMode.md), [TCallbackClientScript](./TCallbackClientScript.md)

**@since 4.4.0**
