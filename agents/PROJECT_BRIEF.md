# Prado Framework — Project Brief

The fast path to productive Prado coding. Quick-access API in [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) · Full detail in [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

---

## What Prado Is

Component-based, event-driven PHP web framework.
**Version:** `4.3.2` (next/`@since`: `4.3.3`). 
**Namespace:** `Prado\`.
**PHP** 8.1+.
**749 classes** across `framework/` (PSR-4). Every class must also be registered in `framework/classes.php`.

---

## The Core: TComponent

Everything meaningful extends `TComponent`. It provides three orthogonal systems.

### 1 — Property System

State via `getXxx()`/`setXxx()` pairs — **no public fields**.
Magic `__get`/`__set`/`__isset`/`__unset` dispatches to them.
Read-only = getter only. Write-only = setter only.

```php
private string $_name = '';
public function getName(): string          { return $this->_name; }
public function setName(string $v): void   { $this->_name = $v; }

// Usage (via magic):
$obj->Name = 'foo';   // → setName('foo')
echo $obj->Name;      // → getName()
```

**Config/template setters** that receive string input use `TPropertyValue` for coercion:
```php
public function setRetries(int|string $v): void {
    $this->_retries = TPropertyValue::ensureInteger($v);
}
// ensureBoolean / ensureInteger / ensureFloat / ensureString / ensureArray / ensureEnum
```

JS-friendly property variant: `getJsXxx()` / `setJsXxx()` — accepts `TJavaScriptLiteral`.

### 2 — Event System (Three Prefixes)

#### `on*` — Object Events (broadcast-listener)
```php
// Raise (inside the component):
$this->raiseEvent('OnClick', $this, $param);
// Attach from outside:
$button->attachEventHandler('OnClick', [$this, 'handleClick']);
$button->detachEventHandler('OnClick', [$this, 'handleClick']);
```
All handlers run in **priority order** (lower number = higher priority). Flags: `RAISE_EVENT_BROADCAST` (fire even without handlers), `RAISE_EVENT_GLOBAL` (also raise as `fx*`).

#### `dy*` — Dynamic Events (behavior dispatch)
```php
// In component:
$result = $this->dyValidate($value);   // dispatched via __call through TCallChain

// In behavior:
public function dyValidate(mixed $value, TCallChain $chain): mixed {
    // inspect/modify $value…
    return $chain->dyValidate($newValue);  // continue chain; or return early to break
}
```
**Rules:** First parameter is filtered and returned through the chain. Always accept `TCallChain` as the last parameter. Every `dy*` call a class makes **must** be documented with `@method` in that class's docblock.

#### `fx*` — Global Events (application-wide)
```php
// Any class with getAutoGlobalListen() = true auto-registers its public fxXxx() methods:
public function fxAttachClassBehavior(object $sender, TClassBehaviorEventParameter $param): void { … }
// Manual: $this->listen() / $this->unlisten()
```

### 3 — Behavior System (mixins)

```php
// Instance behavior — one owner, stateful:
$obj->attachBehavior('cache', new TCacheBehavior());
$obj->detachBehavior('cache');
$obj->getBehavior('cache');
$obj->getBehaviors();                          // TPriorityMap of all behaviors
$obj->getBehaviors(IModuleDependency::class);  // filter by interface/class
$obj->asa('TCacheBehavior');   // get behavior instance by class name
$obj->isa('TCacheBehavior');   // true if IS the class OR has a behavior of that class
                                // NOT the same as instanceof — includes attached behaviors

// Class behavior — stateless, applies to all instances of a class:
TComponent::attachClassBehavior('logging', new TLoggingBehavior(), MyClass::class);
TComponent::detachClassBehavior('logging', MyClass::class);
// IClassBehavior methods receive $owner as first parameter on every call
```

**TBehavior** (per-instance): extend this. Override `attach($owner)` and `detach($owner)`. Any event handlers registered in `attach()` **must** be unregistered in `detach()`.

**TClassBehavior** (class-wide): stateless. Owner injected as first method parameter.

**IDynamicMethods**: behavior receives ALL `dy*` calls, even methods it doesn't explicitly define.

### Serialization / Cloning

```php
// Exclude private props from serialization:
public function _getZappableSleepProps(array &$exprops): void {
    parent::_getZappableSleepProps($exprops);              // always call parent
    $exprops[] = "\0" . self::class . "\0_myPrivateProp"; // mangled format
}
// __clone must call parent::__clone() — re-attaches behaviors, fires dyClone
// __destruct — auto-removes fx* listeners and detaches behaviors
```

---

## TApplicationComponent

Base for everything in the application layer (modules, services, controls). Provides:

```php
$this->getApplication();   $this->getService();
$this->getRequest();       $this->getResponse();
$this->getSession();       $this->getUser();
$this->publishAsset($path);  // → public URL for a private asset
```

---

## TApplication — Service Container

Entry point: `$app = Prado::getApplication()`. Bootstrapped via `new TApplication('/path/to/protected'); $app->run();`.

```php
$app->getModule('id');           // any registered module
$app->getRequest();              // THttpRequest
$app->getResponse();             // THttpResponse
$app->getSession();              // THttpSession
$app->getUser();                 // IUser (always present; guest if unauthenticated)
$app->getCache();                // ICache (primary cache)
$app->getSecurityManager();      // TSecurityManager
$app->getParameters();           // TAttributeCollection — config parameters
$app->getGlobalState($k, $d);   // cross-request persisted state
$app->setGlobalState($k, $v);
```

**Default modules** (always present): `request`, `response`, `session`, `security`, `asset`, `page`.

**Modes:** `Debug` (verbose, no cache), `Normal` (production), `Performance` (skip freshness checks), `Off`.

---

## TModule — Pluggable Modules

```php
class TMyModule extends TModule implements IModuleDependency
{
    private string $_key = '';

    public function getModuleDependencies(): array
    {
        return ['db', 'cache'];   // module IDs this must init after
    }

    public function init($config): void
    {
        parent::init($config);   // always — fires dyInit
        // one-time setup here
    }

    public function getKey(): string      { return $this->_key; }
    public function setKey(string $v): void { $this->_key = $v; }
}
```

```xml
<!-- application.xml -->
<module id="mymodule" class="MyApp\TMyModule" Key="abc123" />
```

**Four init phases** (all in dependency-sorted order):
1. Instantiate + set properties
2. `dyPreInit($config)` — behaviors intercept before init
3. `init($config)` — primary init; call `parent::init($config)` to fire `dyInit`
4. `dyPostInit($config)` — cross-module wiring after all deps initialized

Module dependency sources (both re-evaluated before every phase):
- Module itself implements `IModuleDependency`
- Attached behaviors that implement `IModuleDependency`

---

## Application Lifecycle

```
initApplication() → onConfiguration → onInitComplete
run() →
  onBeginRequest → onLoadState → onLoadStateComplete →
  onAuthentication → onAuthenticationComplete →
  onAuthorization → onAuthorizationComplete →
  onPreRunService → runService() →
  onSaveState → onSaveStateComplete →
  onPreFlushOutput → flushOutput() → onEndRequest
```
`onError` — fires on any unhandled exception. `onEndRequest` — always runs.

---

## Page Lifecycle (TPageService)

```
onPreInit → initRecursive → onInitComplete →
[POST/CB] loadPageState → processPostData → onPreLoad →
loadRecursive → processPostData → raiseChangedEvents →
[POST] raisePostBackEvent | [CB] processCallbackEvent →
onLoadComplete → preRenderRecursive → onPreRenderComplete →
savePageState → onSaveStateComplete →
[GET/POST] renderControl | [CB] renderCallbackResponse →
unloadRecursive
```

---

## Exceptions

```php
throw new TInvalidDataValueException('error_code', $param);   // bad value
throw new TInvalidOperationException('error_code');            // wrong state
throw new TConfigurationException('error_code', $param);      // bad config
throw new THttpException(404, 'page_not_found');
throw new TNotSupportedException('error_code');
throw new TDbException('error_code');
```

Error codes defined in `framework/Exceptions/messages/messages.txt` (English master). Format: `error_code=Message with {0} placeholders`. Add here first; then translate into `messages-<lang>.txt`.

---

## Key Rules — Always Apply

| Rule | Detail |
|---|---|
| `framework/classes.php` | Update for **every** new class added |
| `if` blocks | Always use `{}` — never single-line |
| Backward compat | All changes compatible within point versions |
| `@since` | Next release `4.3.3`; omit on methods when class `@since` matches |
| `dy*` docblock | Every `dy*` call raised by a class → `@method` tag in that class |
| Error codes | Add to `messages.txt` before throwing |
| Self-encapsulate | Access own state through `getXxx()`/`setXxx()`, not raw fields |
| Indentation | Tabs, not spaces. Unix line endings |
| PHPStan | Update `framework/PHPStan/` extensions when adding new dynamic accessors |

---

## Logging

```php
Prado::debug($msg, $category, $ctl);    // Debug mode only
Prado::trace($msg, $category, $ctl);    // DEBUG in Debug, INFO otherwise, silent in Performance
Prado::info($msg, $category, $ctl);
Prado::notice($msg, $category, $ctl);
Prado::warning($msg, $category, $ctl);
Prado::error($msg, $category, $ctl);
Prado::alert($msg, $category, $ctl);
Prado::fatal($msg, $category, $ctl);
Prado::log($msg, TLogger::WARNING, 'My.Category', $ctl);  // explicit level
Prado::profileBegin($token, $category); // start profiling block
Prado::profileEnd($token, $category);   // end; returns elapsed float seconds
Prado::varDump($var);                   // use instead of var_dump() for TComponent objects
```

Category convention: dot-separated — `'Prado.Web.THttpRequest'`, `'MyApp.Auth'`.

---

## Full Documentation

| File | Contents |
|---|---|
| `agents/PROJECT_CONTEXT.md` | Complete architecture reference — all layers, APIs, patterns |
| `agents/PROJECT_SUMMARY.md` | Quick-access API cheatsheet — common calls and patterns |
| `agents/framework/INDEX.md` | Framework root knowledge |
| `agents/framework/CLASSES.md` | All 749 classes by namespace |
| `agents/framework/<Dir>/INDEX.md` | Per-subsystem knowledge |
| `agents/framework/<Class>.md` | Per-class knowledge (TComponent, TApplication, etc.) |
