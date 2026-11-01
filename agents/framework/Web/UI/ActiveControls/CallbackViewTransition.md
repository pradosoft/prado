# Web/UI/ActiveControls/CallbackViewTransition

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`CallbackViewTransition`**

## Overview
A callback response can apply its client actions inside a view transition (`document.startViewTransition`), so the browser animates from the old page to the updated one. The default is off.

| Setting | Scope |
|---|---|
| `ActiveControl.ViewTransition` ([TBaseActiveControl](./TBaseActiveControl.md)) | A response in which this control renders again |
| `TCallbackClientScript::setViewTransition(true)` ([TCallbackClientScript](./TCallbackClientScript.md)) | The current response, from a callback handler |
| `TPage.CallbackViewTransition` ([TPage](../TPage.md)) | Every callback response with client actions; set per page or on `<pages>` |

## Usage

```xml
<com:TActivePanel ID="Results" ActiveControl.ViewTransition="true" Style="view-transition-name: results">
```

```xml
<%@ CallbackViewTransition="true" %>
```

```php
$this->getPage()->getCallbackClient()->setViewTransition(true);
```

## Pipeline

1. The render-in-callback active controls (TActivePanel, TActiveDataGrid, TActiveDataList, TActiveRepeater, TActiveMultiView, TActivePager, TActiveTableRow, TActiveTableCell, TActiveSvg) send their markup through `TBaseActiveControl::replaceClientContent()`, which turns on the response's `ViewTransition` for a `ViewTransition` control and passes `getIsMorphUpdate()`.
2. `TActivePageAdapter::renderResponse()` adds the `X-PRADO-VIEWTRANSITION` part (`1`) when the response has client actions and `getIsViewTransition()` is true: `TPage.CallbackViewTransition` or the response's `ViewTransition`.
3. `Prado.CallbackRequest.successHandler()` reads the part through `getIsViewTransition()`, which also requires `Prado.Element.canViewTransition()`. After the assets load, `applyInViewTransition()` runs `dispatchActions()` through `Prado.Element.viewTransition()`.

## Client Rules

| Condition | Result |
|---|---|
| No `document.startViewTransition`, a hidden page, or `prefers-reduced-motion: reduce` | The actions run at once, as without the part |
| A response | One transition runs all its actions, in order |
| onSuccess | Runs before the actions, on the old page |
| onComplete | Waits until the actions ran, so it sees the updated page |
| The next callback request | A send gate holds it until the actions ran, so it posts the updated inputs |
| A transition started by `Prado.Element.viewTransition()` is running | The new one skips it: it jumps to its end state |
| An action throws | `__run()` reports it through `exceptionHandler()` (onException); the other actions run |
| `update` of `viewTransition()` throws | Its promise rejects; `applyInViewTransition()` reports it through `exceptionHandler()` and releases the holds |
| The browser skips the animation (a duplicate `view-transition-name`) | The actions still run |

Inline scripts in the new markup run once, inside the update, as without a transition. The focus snapshot of a morphed update ([TActiveUpdateMode](./TActiveUpdateMode.md)) is the one taken when the request was sent. Focus stays where it was.

## Naming

The framework ships no view transition CSS; the browser default cross-fades the page. An element animates on its own with a `view-transition-name`, unique on the page:

```xml
<com:TActivePanel ID="Results" ActiveControl.ViewTransition="true" Style="view-transition-name: results">
```

```css
::view-transition-group(results) { animation-duration: 200ms; }
```

## Gotchas

- The update runs in a later frame, after the browser captures the old page. Code that reads the DOM right after the response, outside onComplete, sees the old page.
- The `::view-transition` overlay covers the page while it animates (250 ms by default).
- A response that loads new scripts (`X-PRADO-SCRIPTLIST`) starts the transition, and its holds, after they load. Until then the next request is not held, as without a transition.

## Tests

- `tests/unit/Web/UI/ActiveControls/TCallbackViewTransitionTest.php`
- `tests/js/prado/viewtransition.test.js`, `tests/js/activecontrols/viewtransition.test.js`
- `tests/playwright/web/TViewTransitionTestCase.spec.js` with `tests/harness/web/protected/pages/ViewTransitionTest.page` and `ViewTransitionPageTest.page` (Chromium, Firefox, WebKit): one transition per response, onSuccess and onComplete timing, the next request's inputs, reduced motion, no support, an action error, focus kept, and the page setting

## See Also

- [TBaseActiveControl](./TBaseActiveControl.md), [TCallbackClientScript](./TCallbackClientScript.md), [TActivePageAdapter](./TActivePageAdapter.md), [TActiveUpdateMode](./TActiveUpdateMode.md)

**@since 4.4.0**
