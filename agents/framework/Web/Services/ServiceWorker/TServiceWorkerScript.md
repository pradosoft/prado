# Web/Services/ServiceWorker/TServiceWorkerScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`TServiceWorkerScript`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/TServiceWorkerScript.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`
**Extends:** `TComponent`

## Overview
Builds one worker's script. [TServiceWorkerModule](TServiceWorkerModule.md) creates it per script request and passes it to each admitted [IServiceWorkerContributor](IServiceWorkerContributor.md).

## API

| Method | Use |
|---|---|
| `getWorker()` | The [TServiceWorkerDefinition](TServiceWorkerDefinition.md) being built |
| `addFragment($name, $code, $priority = 10, $config = [])` | Fragment body; a same name replaces; name `[A-Za-z0-9_.-]+` |
| `addFragmentFile($name, $file, …)` | Body from a file; `serviceworkerscript_file_invalid` |
| `hasFragment()`, `removeFragment()`, `getFragments()` | Fragments in run order: ascending priority, then order added |
| `addPrecache($urls)`, `getPrecache()` | URLs stored on install; deduplicated |
| `addExclude($prefixes)`, `getExclude()` | URL path+query prefixes the fetch handlers skip |
| `getOptions()` | `{worker, scope, precache, exclude, fragments}` |
| `getVersion()` | 16-hex sha256 of the options JSON and the body |
| `render()` | The script |
| `renderUnregister($worker)` (static) | Options `{worker, scope}` + `worker-unregister.js` |
| `getSourcePath($file)` (static) | `framework/Web/Javascripts/source/prado/serviceworker/<file>` |

## Rendered script

```javascript
const PRADO_SERVICE_WORKER = {"version":"…","worker":"default","scope":"/","precache":[…],"exclude":[…],"fragments":{"name":{…}}};
/* worker.js: defines prado */
prado.addFragment("name", function (prado, config) {
/* fragment code */
}, PRADO_SERVICE_WORKER.fragments["name"]);
prado.start();
```

A fragment's `config` is its JSON-encoded array (`{}` when empty). The version changes with any byte of the options, runtime, or fragments, so the browser installs an update and the caches are renamed.

## Tests
- `tests/unit/Web/Services/ServiceWorker/TServiceWorkerScriptTest.php`
- `tests/js/serviceworker/worker.test.js`, `worker-strategies.test.js`

**@since 4.4.0**
