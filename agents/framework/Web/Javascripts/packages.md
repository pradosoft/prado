# Web/Javascripts/packages

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](INDEX.md) / **`packages`**

**Location:** `framework/Web/Javascripts/packages.php`

## Overview
This file defines the JavaScript package registry for PRADO. It declares all JS packages, their source files, folder mappings, and dependencies. Consumed by `TClientScriptManager` to resolve and publish packages in dependency order.

## Package Folders

| Alias | Path |
|-------|------|
| `prado` | `Prado\Web\Javascripts\source\prado` |
| `jquery` | `Vendor\bower-asset\jquery\dist` |
| `jquery-ui` | `Vendor\bower-asset\jquery-ui` |
| `tinymce` | `Vendor\bower-asset\tinymce` |
| `highlightjs` | `Vendor\bower-asset\highlightjs` |
| `clipboard` | `Vendor\bower-asset\clipboard\dist` |
| `teleport` | `prado/controls/teleport.js`: `Prado.WebUI.TTeleport`, the page form association of [TTeleport](../UI/WebControls/TTeleport.md) content |
| `idiomorph` | `Vendor\npm-asset\idiomorph\dist` |

## Packages

| Package | Files |
|---------|-------|
| `prado` | `prado/prado.js`, `prado/controls/controls.js`, `prado/controls/browser.js` |
| `logger` | `prado/logger/logger.js` |
| `validator` | `prado/validator/validation3.js` |
| `ajax` | `prado/activecontrols/ajax3.js`, `prado/activecontrols/activecontrols3.js` |
| `datepicker` | `prado/datepicker/datepicker.js` |
| `colorpicker` | `prado/colorpicker/colorpicker.js` |
| `slider` | `prado/controls/slider.js` |
| `keyboard` | `prado/controls/keyboard.js` |
| `keyshortcuts` | `prado/keyshortcuts/keyshortcuts.js`: the `Prado.KeyShortcuts` key-binding registry; [TWebControl](../UI/WebControls/TWebControl.md) `KeyShortcuts` registers it |
| `rowselect` | `prado/controls/rowselect.js` |
| `unsavedchanges` | `prado/controls/unsavedchanges.js` |
| `tabpanel` | `prado/controls/tabpanel.js` |
| `accordion` | `prado/controls/accordion.js` |
| `htmlarea` | `prado/controls/htmlarea.js` |
| `htmlarea5` | `prado/controls/htmlarea5.js` |
| `ratings` | `prado/ratings/ratings.js` |
| `inlineeditor` | `prado/activecontrols/inlineeditor.js` |
| `activefileupload` | `prado/activefileupload/activefileupload.js` |
| `activedatepicker` | `prado/activecontrols/activedatepicker.js` |
| `proofofwork` | `prado/controls/proofofwork-solver.js`, `prado/controls/proofofwork.js` |
| `idiomorph` | `idiomorph/idiomorph.js`: DOM morphing for callback updates; `npm-asset/idiomorph`, pinned exactly in composer.json and package.json |
| `serviceworker` | `prado/serviceworker/serviceworker.js`: `Prado.ServiceWorker`, registered by [TServiceWorker](../UI/WebControls/TServiceWorker.md). The `worker*.js` files in the folder are worker scripts, not packages |
| `sharedconnection` | `prado/sharedconnection/sharedconnection.js`: `Prado.SharedConnection`, one connection per key shared by the tabs of the origin |

## Dependencies

Key dependencies include: `prado` requires `jquery`, `ajax` requires `jquery` and `prado`, `activedatepicker` requires `jquery`, `prado`, `datepicker`, and `ajax`. `morph` (no files of its own) requires `jquery`, `prado`, `ajax`, and `idiomorph`; [TActiveControlAdapter](../UI/ActiveControls/TActiveControlAdapter.md) registers it for a control whose callback updates morph.

## Prado.SharedConnection

`subscribe({key, open(resumeId, emit), onMessage(message, {leader}), heartbeat, shared})` → `{close(), isLeader()}`. Tabs requesting the Web Lock `prado-sharedconnection:<key>` elect one holder, which opens the transport and posts each `{id, event, data}` and a heartbeat on the `BroadcastChannel` of the same name. The holder releases the lock when its last subscriber closes, on `pagehide`, and on `freeze` (requesting it again on `pageshow` and `resume`); the next tab opens from the last ID it received. A tab that hears no heartbeat for two intervals steals the lock. Without `navigator.locks` or `BroadcastChannel`, or with `shared: false`, each tab holds its own. `eventSource(url, {resumeParameter, events, withCredentials})` is the server-sent event transport; the key should include the user's token. `Prado.SharedConnection.Connection` takes an environment (`locks`, `BroadcastChannel`, `target`, `now`) for tests. Tests: `tests/js/sharedconnection/`.

## See Also

- [css-packages.php](./css-packages.md) - CSS package registry
- [TClientScriptManager](../UI/TClientScriptManager.md) — Consumes this registry
