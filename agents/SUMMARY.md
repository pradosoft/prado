# Prado Framework — Minimum Viable Context

Namespace `Prado\` · version `4.3.2` (next/`@since`: `4.3.3`) · PHP 8.1+ · 749 classes in `framework/` · every new class must be added to `framework/classes.php`.

**`TComponent`** is the base of nearly everything. It has three systems:

**Properties** — state via `getXxx()`/`setXxx()` pairs; no public fields. Magic `__get`/`__set` dispatches to them. `$obj->Name` calls `getName()`/`setName()`.

**Events** — three prefixes, all dispatched at runtime:
- `on*` — per-instance listener lists; raised with `$this->raiseEvent('OnFoo', $this, $param)`; handlers attached via `attachEventHandler()`; run in priority order.
- `dy*` — dispatched via `__call` through a `TCallChain` to all attached, enabled behaviors that implement the method; first argument is filtered and returned through the chain; every `dy*` call a class makes must have a `@method` tag in that class's docblock.
- `fx*` — application-wide static listeners; classes with `getAutoGlobalListen() = true` auto-register their public `fxXxx()` methods.

**Behaviors** — mixins attached at runtime. `$obj->attachBehavior('name', $b)` / `detachBehavior()`. `$obj->isa('MyClass')` returns true if the object IS that class OR has an attached behavior of that class (unlike `instanceof`). `TBehavior` = per-instance stateful; `TClassBehavior` = stateless, applied to all instances of a class (owner injected as first param).

**`TApplication`** is the service container. Modules and services register via `application.xml`. Access anywhere via `Prado::getApplication()`. Key getters: `getModule($id)`, `getRequest()`, `getResponse()`, `getSession()`, `getUser()`, `getCache()`. `TApplicationComponent` (base of all modules, services, controls) provides these as `$this->getXxx()` shortcuts.

**`TModule`** — pluggable unit; implement `init($config)` (always call `parent::init($config)`). Init phases in dependency order: instantiate → `dyPreInit` → `init` → `dyPostInit`. Declare deps via `IModuleDependency::getModuleDependencies(): array` returning module ID strings.

**`TService`** — handles one request type (page, JSON, RPC, SOAP, feed); only one runs per request. `TPageService` runs the page lifecycle.

**`TControl`** — base UI control; lives in a parent/child tree rooted at `TPage`. Lifecycle events: `onPreInit` → `onInit` → `onLoad` → `onPreRender` → `render` → `onUnload`. State managed via view state. Active controls (AJAX) extend this with callback support.
