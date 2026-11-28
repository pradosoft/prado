# Web/UI/WebControls/TServiceWorker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TServiceWorker`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TServiceWorker.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TControl`

## Overview
Registers the page's service worker of [TServiceWorkerModule](../../Services/ServiceWorker/TServiceWorkerModule.md) with `Prado.ServiceWorker.register(url, {scope, worker})` (package `serviceworker`). The module's `AutoRegister` adds one to every page without one, so a template places it only to set `Worker` or `ModuleID`.

```xml
<com:THead>
  <com:TServiceWorker Worker="admin" />
</com:THead>
```

## Properties

| Property | Default | Use |
|---|---|---|
| `ModuleID` | `Auto` | The module; `Auto` is the first `TServiceWorkerModule`; another class throws `serviceworker_module_invalid` |
| `Worker` | empty | Worker name; empty selects the page's worker (`TServiceWorkerModule::getPageWorkerName()`) |

## Rendering

- `onPreRender()` registers when the control is enabled, the request is not a callback, the module and worker are enabled, and the worker's scope controls the request path (`getPageWorker()`).
- With a page form: end script under the key `prado:serviceworker` (`SCRIPT_KEY`); a second TServiceWorker replaces the first.
- Without a form (no end scripts render): `render()` writes the pending script files and the registration block in place, in the head when the module added it.
- `findModule($app, $id)` (static) is shared with [TWebManifest](TWebManifest.md).

## Accessibility

Audited for #1478: the control renders only `<script>` elements, no visible, focusable, or announced content. Update prompts are the application's, through `prado:serviceworkerupdate`; they need the usual live-region and focus handling.

## Client (`Prado.ServiceWorker`)

`register(url, options)` once per URL (`updateViaCache: 'none'`); `postMessage(type, data)` → promise of the reply (`timeout` ms); `activate(registration)`; document events `prado:serviceworkerready`, `prado:serviceworkerupdate` (`detail.activate()`), `prado:serviceworkermessage`. Unsupported browsers: `register()` resolves null.

## Tests
- `tests/unit/Web/UI/WebControls/TServiceWorkerTest.php`
- `tests/js/serviceworker/serviceworker.test.js`

**@since 4.4.0**
