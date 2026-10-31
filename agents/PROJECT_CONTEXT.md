# Project Context — Prado Framework

Full architecture reference for the Prado codebase. See [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) for the quick-access cheatsheet.

---

## Version

- **Current release:** `4.3.2`
- **Next release (use for `@since` tags):** `4.3.3`
- **PHP minimum:** 8.1 (CI tests 8.1, 8.2, 8.3)
- **Namespace root:** `Prado\`

---

## Core Abstractions

| Class | File | Role |
|---|---|---|
| `TComponent` | `framework/TComponent.php` | Base class for all objects; property, event, behavior systems |
| `TApplication` | `framework/TApplication.php` | Top-level service container; entry point for every request |
| `TApplicationComponent` | `framework/TApplicationComponent.php` | Base for all app-aware components; shortcut accessors |
| `TApplicationConfiguration` | `framework/TApplicationConfiguration.php` | Parses `application.xml` or `application.php` |
| `TModule` | `framework/TModule.php` | Base for pluggable modules registered in config |
| `TService` | `framework/TService.php` | Base for application services (page, JSON, RPC, SOAP, feed) |
| `Prado` | `framework/Prado.php` | Static framework utility; autoloader, logger, aliases |
| `TEventHandler` | `framework/TEventHandler.php` | Invokable wrapper for event handlers with data injection |
| `TEventSubscription` | `framework/TEventSubscription.php` | RAII-style temporary event handler subscription |
| `TPropertyValue` | `framework/TPropertyValue.php` | Static type-coercion utilities for property setters |
| `TComponentReflection` | `framework/TComponentReflection.php` | Introspection for TComponent property/event metadata |

---

## Layer Breakdown

```
TApplication
 └─ Web Layer      framework/Web/
      THttpRequest, THttpResponse, THttpSession
      TUrlManager / TUrlMapping (routing)
      TAssetManager
      Services/      (TPageService, TJsonService, TFeedService, TRpcService, TSoapService)
      UI/            (TControl, TPage, TTemplateControl, WebControls/, ActiveControls/, JuiControls/)
 └─ Data Layer     framework/Data/
      TDbConnection  (PDO wrapper)
      DataGateway/   (stateless table gateway)
      Active Record ORM: pradosoft/prado-activerecord; SqlMap: pradosoft/prado-sqlmap (separate packages)
 └─ Caching        framework/Caching/    (TAPCCache, TMemCache, TRedisCache, TDbCache, TEtcdCache)
 └─ Security       framework/Security/   (TAuthManager, TSecurityManager, TUserManager, TPermissionsManager)
 └─ I18N           framework/I18N/       (TGlobalization, MessageSource, CultureInfo, TDateFormat, TNumberFormat)
 └─ Xml            framework/Xml/        (TXmlDocument/TXmlElement with XPath, DOM compat, ArrayAccess)
 └─ Util           framework/Util/       (logging, behaviors, cron, RPC clients, signals, TVarDumper, TCallChain)
 └─ PHPStan        framework/PHPStan/    (custom static-analysis extensions for dynamic Prado properties)
 └─ Collections    framework/Collections/ (TList, TMap, TPriorityList, TPriorityMap, TQueue, TStack, TWeakList)
 └─ Exceptions     framework/Exceptions/  (exception hierarchy, TErrorHandler, multilingual messages)
 └─ Shell          framework/Shell/       (TShellApplication, TShellAction, TShellWriter — CLI support)
```

---

## Lifecycles

### Application Lifecycle

```
initApplication()
  → onConfiguration             ← modules initialized, services registered
  → onInitComplete              ← modules initialized, service instanced

run() → processRequest()
  → onBeginRequest              ← request parsing complete
  → onLoadState                 ← load page/session state
  → onLoadStateComplete
  → onAuthentication            ← TAuthManager sets current user
  → onAuthenticationComplete
  → onAuthorization             ← authorization rules checked
  → onAuthorizationComplete
  → onPreRunService
  → runService()                ← TPageService / TJsonService / etc. runs
  → onSaveState                 ← persist session/page state
  → onSaveStateComplete
  → onPreFlushOutput
  → flushOutput()               ← send HTTP response
  → onEndRequest                ← always runs (cleanup, logging flush)
```

`onError` fires whenever an unhandled exception escapes any stage.

### Module Lifecycle

Modules are initialized in four ordered phases, sorted by dependency (Kahn's topological sort):

1. **Instantiate & configure** — module created, properties set via `setXxx()`. No behaviors active yet.
2. **`dyPreInit($config)`** — raised on each module (in dependency order). Behaviors may intercept before `init()`.
3. **`init($config)`** — primary initialization point. Always call `parent::init($config)` to fire `dyInit`.
4. **`dyPostInit($config)`** — raised after `init()`. Cross-module wiring that requires all dependencies initialized.

### Module Dependency Ordering

Dependencies are declared via **`IModuleDependency`** interface — the sole mechanism. Two sources, both fully re-evaluated before every phase:

- **Source 1:** Module implements `IModuleDependency` and returns dependency IDs from `getModuleDependencies(): ?array`.
- **Source 2:** Any behavior attached to the module that also implements `IModuleDependency`.

Both sources are merged, deduplicated, and sorted with Kahn's algorithm. Behaviors are re-scanned on every call because they may be attached/detached between phases.

```php
// Module declaring its own dependencies:
class TMyModule extends TModule implements IModuleDependency {
    public function getModuleDependencies(): array {
        return ['db', 'cache'];
    }
}

// Behavior contributing dependencies:
class TMyBehavior extends TBehavior implements IModuleDependency {
    public function getModuleDependencies(): ?array { return ['db']; }
}
```

### Page Lifecycle (via TPageService)

```
onPreInit → initRecursive → onInitComplete
  → loadPageState (POST/Callback)
  → processPostData (POST/Callback)
  → onPreLoad → loadRecursive
  → processPostData (POST/Callback)
  → raiseChangedEvents (POST/Callback)
  → raisePostBackEvent (POST-only)
  → processCallbackEvent (Callback-only)
  → onLoadComplete → preRenderRecursive → onPreRenderComplete
  → savePageState → onSaveStateComplete
  → renderControl (GET/POST) / renderCallbackResponse (Callback-only)
  → unloadRecursive
```

---

## Event / Property / Behavior System

### Property System

Properties defined via `getXxx()`/`setXxx()` pairs; no public fields. Magic `__get`/`__set`/`__isset`/`__unset` dispatch to these.

- Read-only = getter only. Write-only = setter only.
- JS-friendly variants: `getJsXxx()`/`setJsXxx()` accept `TJavaScriptLiteral` values.
- Type coercion in setters uses `TPropertyValue::ensureBoolean/Integer/Float/String/Array/Enum()`.
- Dot-path access: `$obj->Parent->Page->Title` evaluated recursively via `__get`.

### Event Prefixes

| Prefix | Type | Mechanism | Storage |
|---|---|---|---|
| `on` | Object events | `raiseEvent()` / `attachEventHandler()` | Per-instance `$_e` |
| `dy` | Dynamic events (behaviors) | `__call()` dispatch via `TCallChain` | N/A — behavior methods |
| `fx` | Global events | `listen()` / `unlisten()` | Static `$_ue` per event |

**`on` events:** `$this->raiseEvent('OnEventName', $this, $param)` — all registered handlers called in priority order. Use `RAISE_EVENT_BROADCAST` flag to fire even with no handlers; `RAISE_EVENT_GLOBAL` to also fire as `fx*`.

**`dy` events:** Called on the owner; dispatched through `TCallChain` to all attached, enabled behaviors that implement the method. The first parameter is always filtered and returned through the chain. Every `dy*` method on a class must be documented with `@method` in the class docblock.

**`fx` events:** Auto-registered on construction when `getAutoGlobalListen()` is true. Any instance with a matching public `fxXxx()` method receives the event.

### Behavior System

Instance behaviors — attached to one component:
```php
$component->attachBehavior('name', new MyBehavior());
$component->detachBehavior('name');
$component->asa('MyBehavior');       // by class name
$component->isa('MyBehavior');       // true if IS or HAS behavior of class
$component->getBehaviors(?string $class);  // filter by class/interface
```

Class behaviors — applied to all instances of a class:
```php
TComponent::attachClassBehavior('name', new MyClassBehavior(), MyClass::class);
TComponent::detachClassBehavior('name', MyClass::class);
```

- `IBehavior` / `TBehavior` — per-instance, stateful; owner stored as `WeakReference`.
- `IClassBehavior` / `TClassBehavior` — class-wide, stateless; owner injected as first parameter on every call.
- `IDynamicMethods` — behavior receives all `dy*` calls regardless of whether it defines the exact method.

### Serialization & Cloning

- `__sleep()` / `__wakeup()` — `_getZappableSleepProps()` returns mangled private property names to exclude from serialization. Always call `parent::_getZappableSleepProps($exprops)`.
- `__clone()` — re-attaches behaviors; fires `dyClone`. Always call `parent::__clone()`.
- `__destruct()` — removes all `fx*` listeners; detaches behaviors.

---

## TApplication Key API

```php
$app = Prado::getApplication();

// Module access:
$app->getModule('cache');       // ICache or any registered module
$app->getModule('auth');        // TAuthManager
$app->setModule($id, $module);

// Core shortcuts:
$app->getRequest();             // THttpRequest
$app->getResponse();            // THttpResponse
$app->getSession();             // THttpSession
$app->getUser();                // IUser
$app->getCache();               // ICache (primary)
$app->getSecurityManager();     // TSecurityManager
$app->getService();             // current IService
$app->getGlobalization();       // TGlobalization or null

// Global state (persisted between requests, in RuntimePath):
$app->getGlobalState($key, $default);
$app->setGlobalState($key, $value, $default);
$app->clearGlobalState($key);
```

### Default Modules (Always Present)

Even with an empty `application.xml`:
- `THttpRequest` (id: `request`)
- `THttpResponse` (id: `response`)
- `TApplicationStatePersister` (page state)
- `THttpSession` (id: `session`, lazy)
- `TSecurityManager` (id: `security`)
- `TAssetManager` (id: `asset`)
- `TPageService` (id: `page`)

### Application Modes (TApplicationMode)

| Mode | Behavior |
|---|---|
| `Off` | Refuses all requests |
| `Debug` | Cache always updated; verbose errors |
| `Normal` | Standard production mode |
| `Performance` | Like Normal but skips cache freshness checks; templates not recompiled |

---

## Bootstrapping

```php
// index.php:
require 'vendor/autoload.php';
$app = new Prado\TApplication('/path/to/protected');
$app->run();
```

### application.xml Structure

```xml
<application id="MyApp" Mode="Debug">
    <paths>
        <using namespace="Application.Pages" />
    </paths>
    <modules>
        <module id="db" class="Prado\Data\TDataSourceConfig"
                ConnectionString="mysql:host=localhost;dbname=myapp"
                Username="root" Password="secret" />
        <module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" />
        <module id="auth" class="Prado\Security\TAuthManager"
                UserManager="users" LoginPage="Login" />
    </modules>
    <services>
        <service id="page" class="Prado\Web\Services\TPageService" />
    </services>
    <parameters>
        <parameter id="AppVersion" value="1.0.0" />
    </parameters>
</application>
```

---

## Collections (`framework/Collections/`)

| Class | Purpose |
|---|---|
| `TList` | Integer-indexed. Override `insertAt()`/`removeAt()` in subclasses |
| `TMap` | Key-value (`string`/`int` keys). `dyAddItem()`/`dyRemoveItem()` hooks |
| `TPriorityList` | `TList` with numeric priority (default `10`; lower = higher). Flattened cache `$_fd` |
| `TPriorityMap` | `TMap` with priority ordering; iterated in priority order |
| `TQueue` | FIFO — `enqueue()` / `dequeue()` |
| `TStack` | LIFO — `push()` / `pop()` |
| `TWeakList` | `TList` backed by weak references; dead entries auto-removed |
| `TAttributeCollection` | Case-insensitive name=value pairs for HTML/XML attributes |
| `TArraySubscription` | RAII subscription to collection change events |
| `TNull` | Null object for collections |

All collections implement `ArrayAccess`, `Countable`, `IteratorAggregate`. Support `__sleep()`/`__wakeup()`.

---

## Caching (`framework/Caching/`)

`ICache` interface: `get()`, `set()`, `add()`, `delete()`, `flush()`. All backends extend `TCache` (abstract base).

| Backend | Class | Requires |
|---|---|---|
| APCu | `TAPCCache` | `apcu` PHP extension |
| Memcached | `TMemCache` | `\Memcached` extension |
| Redis | `TRedisCache` | `\Redis` extension (port 6379) |
| Database | `TDbCache` | PDO; auto-creates `pradocache` table |
| etcd | `TEtcdCache` | etcd HTTP v2 API via cURL |

Cache dependencies invalidate entries: `TFileCacheDependency`, `TDirectoryCacheDependency`, `TGlobalStateCacheDependency`, `TApplicationStateCacheDependency`, `TChainedCacheDependency` (OR-logic composite).

Key prefixed with app unique ID hash to prevent collisions when multiple apps share one backend.

---

## Data Layer (`framework/Data/`)

Three complementary patterns:

**1. `TDbConnection` (PDO wrapper)**
```php
$conn = new TDbConnection('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$conn->open();
$cmd = $conn->createCommand('SELECT * FROM users WHERE id = :id');
$cmd->bindValue(':id', $id);
$rows = $cmd->queryAll();
```

**2. `TActiveRecord` (stateful ORM)**
```php
class UserRecord extends TActiveRecord {
    const TABLENAME = 'users';
    public $id, $name, $email;
    public static function finder($class = __CLASS__) {
        return parent::finder($class);
    }
}
$user = UserRecord::finder()->findByPk(1);
$user->name = 'New Name';
$user->save();
```

**3. `TTableGateway` (stateless gateway)**
```php
$gw = new TTableGateway('users', $conn);
$rows = $gw->findAll(new TSqlCriteria('active = :a', [':a' => 1]));
```

SqlMap (`TSqlMapConfig`, `TSqlMapGateway`, XML SQL mapping) left the framework in 4.4.0 (#1168, #1356). It is the `pradosoft/prado-sqlmap` package; `composer require pradosoft/prado-sqlmap` keeps the `Prado\Data\SqlMap` namespace, short names, and API.

`TDbMetaData::getInstance($conn)` auto-selects the correct driver (MySQL, PostgreSQL, SQLite, MSSQL, Oracle, IBM DB2, Firebird). Always use parameter binding — never interpolate user input.

---

## Security (`framework/Security/`)

- **`TAuthManager`** — Module; coordinates authentication/authorization. Properties: `UserManager`, `LoginPage`, `AuthExpire`, `AllowAutoLogin`. Must be declared before `TPermissionsManager`.
- **`TUser`** / **`TDbUser`** — User objects. Properties: `Name`, `Roles`, `IsGuest`. Session-persisted.
- **`TUserManager`** / **`TDbUserManager`** — User repositories implementing `IUserManager`.
- **`TSecurityManager`** — Hashing, encryption/decryption, HMAC. Used by `TPage` for state signing.
- **`TAuthorizationRule`** — Single ACL rule. Wildcards: `*` = all users, `?` = authenticated only.
- **`TPermissionsManager`** — RBAC with role hierarchy, named permissions (`blog.post.create`), wildcard matching (`blog.*`). Loads dynamic roles from `TDbParameterModule`. `SuperRoles` bypass all checks.
- **`IPermissions`** — Interface for classes declaring their own permissions; attach `TPermissionsBehavior` automatically.

Access current user: `Prado::getApplication()->getUser()` or `$this->getUser()` from any `TApplicationComponent`.

---

## I18N (`framework/I18N/`)

- **`TGlobalization`** — Module; configure in `application.xml`. Properties: `Culture`, `Charset`, `DefaultCulture`. Access via `Prado::getGlobalization()`.
- **`TGlobalizationAutoDetect`** — Auto-sets culture from `Accept-Language` header.
- **`MessageSource::factory($type, $source)`** — Creates backend: `'XLIFF'`, `'PHP'`, `'gettext'`, `'Database'`. Always use the factory.
- **`TDateFormat`** / **`TNumberFormat`** — ICU-style locale-aware formatting.
- **`TChoiceFormat`** — Plural/interval selection: `"0#none|1#one item|1<{0} items"`.
- **`<com:TTranslate>`** — Template control for translated text.
- **Culture format:** RFC 4646 tags — `en_US`, `zh_CN`, `pt_BR`.
- `TGlobalization` must init before any translation is attempted (module order matters).

---

## Xml (`framework/Xml/`)

Used throughout for configuration, template processing, and application XML handling.

- **`TXmlDocument`** — Wraps `DOMDocument`. Load: `loadFromFile($path)` / `loadFromString($xml)`. Save: `saveToString()`. Root element = document (`TagName` = root tag).
- **`TXmlElement`** — Single XML element. Properties: `TagName`, `Value` (text content), `Attributes` (TMap), `Elements` (TXmlElementList), `Parent`. XPath: `$elem->xpath('//item[@id="1"]')`. Implements `ArrayAccess`, `Countable`, `IteratorAggregate`.
- **`TXmlElementList`** — `TList` of child `TXmlElement` objects maintaining `Parent` references.

---

## Util Layer (`framework/Util/`)

### Logging
- `TLogger` — core logger. Access: `Prado::getLogger()`. Methods: `log($msg, $level, $category)`. Levels: `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`. Profiling via `PROFILE_BEGIN`/`PROFILE_END` pairs.
- `TLogRouter` — module routing logs to multiple `TLogRoute` targets (file, DB, email, browser, stdout, syslog).
- Shorthand: `Prado::log()`, `Prado::trace()`, `Prado::warning()`, `Prado::error()`, `Prado::fatal()`.

### Behaviors
- `TBehavior` — per-instance (one owner; `WeakReference`). Override `attach($component)` and `detach($component)`.
- `TClassBehavior` — class-wide (stateless; owner injected as first param).
- `TCallChain` — AOP method chain for `dy*` events. Behaviors call `$chain->call(...)` to continue or return early to break.
- `TBehaviorsModule` — loads behavior configuration from `application.xml`.
- **Rule:** Behaviors must unregister event handlers in `detach()` that were registered in `attach()`.

### Parameter Modules
- `TParameterModule` — named config parameters; access via `$app->getParameters()`.
- `TDbParameterModule` — DB-backed; supports WordPress `option_name`/`option_value` schema, auto-capture of changes, serialize via PHP/JSON/callable.

### Cron
- `TCronModule` — file-based scheduled tasks.
- `TDbCronModule` — DB-backed cron with logging and shell commands.
- `TTimeScheduler` — time-based trigger using `SIGALRM`.

### Signals
- `TSignalsDispatcher` — POSIX signal dispatcher; translates OS signals to `fx*` global events.
- Install via `TApplicationSignals` behavior (preferred) or `TSignalsDispatcher::singleton()->attach()`.

### Other
- `TVarDumper` — human-readable `TComponent` variable dump; use instead of `var_dump()`.
- `TDataFieldAccessor` — dot-notation property path accessor (`"User.Profile.Name"`).
- `TJsonRpcClient` / `TXmlRpcClient` — RPC clients.

---

## Exceptions (`framework/Exceptions/`)

Base: `TException` — supports error codes from `framework/Exceptions/messages/messages.txt`.

```php
throw new TInvalidDataValueException('error_code', $param1, $param2);
// or with HTTP code:
throw new THttpException(404, 'page_not_found');
```

| Exception | Use |
|---|---|
| `TApplicationException` | General application-level errors |
| `TConfigurationException` | Bad configuration values |
| `THttpException` | HTTP status errors (404, 500, …) |
| `TInvalidDataTypeException` | Wrong data type |
| `TInvalidDataValueException` | Bad value for valid type |
| `TInvalidOperationException` | Operation not allowed in current state |
| `TNotSupportedException` | Feature not implemented |
| `TUnknownMethodException` | Undefined method called |
| `TDbException` / `TDbConnectionException` | Database errors |
| `TTemplateException` | Template parse/render errors |

Error message files: `framework/Exceptions/messages/messages.txt` (English master). Language variants: `messages-<lang>.txt`. Format: `error_code=Message with {0} placeholders`. Add to `messages.txt` first, then translate.

---

## Custom PHPStan Extensions (`framework/PHPStan/`)

Extensions teach PHPStan about Prado's dynamic systems. Configured in `phpstan.neon.dist`.

- **`DynamicMethodsClassReflectionExtension`** — Any `dy*` or `fx*` method (case-insensitive prefix) on a `TComponent` subclass is valid; no false "undefined method" errors.
- **`TComponentPropertiesReflectionExtension`** — Maps `getXxx()`/`setXxx()` pairs to virtual properties; prevents "undefined property" errors.
- **`TComponentIsaTypeSpecifyingExtension`** — Narrows type when `isa()` returns `true`, similar to `instanceof`.
- **`TComponentHasMethodTypeSpecifyingExtension`** — Narrows type when `hasMethod('foo')` is true.
- **`PradoMethodVisibleStaticMethodTypeSpecifyingExtension`** — Narrows type when `Prado::method_visible($obj, 'foo')` is true.

Update extensions when adding new dynamic accessor prefixes or type-narrowing helpers.

---

## Web Layer (`framework/Web/`)

### HTTP Core
- **`THttpRequest`** — Incoming HTTP request. Implements `ArrayAccess` (unified GET+POST). Properties: `RequestType`, `Url`, `IsSecureConnection`, `PathInfo`. Manages service routing.
- **`THttpResponse`** — HTTP response. Status codes, headers, cookies, output buffering, `writeFile()`, redirects.
- **`THttpSession`** — PHP session wrapper (`ArrayAccess`). Lazy start; `AutoStart=true` to start eagerly.
- **`TAssetManager`** — Publishes private assets to `assets/`. Timestamp-based caching. Access: `$app->getAssetManager()`.
- **`TUrlManager`** / **`TUrlMapping`** — URL construction and SEF routing. Lazy-loaded on first URL construction.

### Services
| Class | ID | Purpose |
|---|---|---|
| `TPageService` | `page` | Template-based web pages |
| `TJsonService` | `json` | JSON responses |
| `TRpcService` | `rpc` | JSON-RPC / XML-RPC |
| `TSoapService` | `soap` | SOAP / WSDL |
| `TFeedService` | `feed` | RSS / Atom feeds |

### UI Hierarchy
```
TControl (base)
 ├── TTemplateControl → TPage (web page root)
 │    └── TTemplateControlInheritable
 ├── TWebControl (all HTML-rendering controls)
 │    └── WebControls/ (TButton, TCheckBox, TTextBox, TPanel, validators, lists, TDataGrid, etc.)
 └── ActiveControls/ (TActiveButton, TActiveTextBox, etc. — callback-enabled)
     JuiControls/   (TJuiDialog, TJuiAutoComplete, etc. — jQuery UI)
```

### JavaScript Registration

Always register JS/CSS via `TClientScriptManager::registerPackage()` — never reference files directly.

- `TJavaScriptLiteral` — raw JS expression (not quoted).
- `TJavaScript::quoteJsLiteral($val)` — safe PHP→JS encoding.
- Package manifests: `packages.php` (JS) and `css-packages.php` (CSS) in `Javascripts/`.

---

## ActiveControls JavaScript

Client-side JS for ActiveControls lives at `framework/Web/Javascripts/source/prado/`. PHP controls register JS via `registerClientScriptFile()` or the adapter pattern. JS class names mirror PHP names under `Prado.WebUI.*`.

### File-to-Control Mapping

| JS File | PHP Control(s) | Purpose |
|---|---|---|
| `prado/prado.js` | (all) | Core namespace, jQuery extensions, `Prado.Registry`, `Prado.PostBack`, `Prado.RequestManager` |
| `prado/logger/logger.js` | (utility) | Client-side debug console |
| `prado/controls/controls.js` | TButton, TLinkButton, TImageButton, TTextBox | Base control classes |
| `prado/controls/tabpanel.js` | TTabPanel | Tab switching |
| `prado/controls/accordion.js` | TAccordion | Expand/collapse |
| `prado/controls/slider.js` | TSlider | Drag-and-drop range slider |
| `prado/controls/keyboard.js` | TKeyboard | Virtual on-screen keyboard |
| `prado/controls/htmlarea.js` | THtmlArea | TinyMCE v3/v4 wrapper |
| `prado/controls/htmlarea5.js` | THtmlArea5 | TinyMCE v5+ wrapper |
| `prado/datepicker/datepicker.js` | TDatePicker | Popup calendar |
| `prado/colorpicker/colorpicker.js` | TColorPicker | HSB color picker |
| `prado/ratings/ratings.js` | TRatingList, TActiveRatingList | Star rating widget |
| `prado/validator/validation3.js` | TBaseValidator subclasses, TValidationSummary | Client-side validation |
| `prado/activecontrols/ajax3.js` | (all active controls) | AJAX callback engine |
| `prado/activecontrols/activecontrols3.js` | TActiveButton, TActiveTextBox, TActiveDropDownList, etc. | Active control wrappers |
| `prado/activecontrols/inlineeditor.js` | TInPlaceTextBox | Click-to-edit |
| `prado/activecontrols/activedatepicker.js` | TActiveDatePicker | Date picker with callback |
| `prado/activefileupload/activefileupload.js` | TActiveFileUpload | Async file upload |

### JS Dependency Chain

```
prado.js
 ├── logger/logger.js
 ├── controls/controls.js  →  tabpanel, accordion, slider, keyboard, htmlarea, htmlarea5
 ├── datepicker/datepicker.js  →  activecontrols/activedatepicker.js
 ├── colorpicker/colorpicker.js
 ├── ratings/ratings.js
 ├── validator/validation3.js
 └── activecontrols/ajax3.js  →  activecontrols3.js, inlineeditor.js, activefileupload.js
```

### Key AJAX Response Headers (set by PHP, read by `ajax3.js`)

| Header | Purpose |
|---|---|
| `X-PRADO-REDIRECT` | Redirect client to URL |
| `X-PRADO-DATA` | Arbitrary return data |
| `X-PRADO-ACTIONS` | JSON array of DOM update commands |
| `X-PRADO-ERROR` | Error message |
| `X-PRADO-PAGESTATE` | Updated page state token |
| `X-PRADO-SCRIPTLIST` / `X-PRADO-STYLESHEET` | Dynamic asset loading |

All JS controls self-register in `Prado.Registry[controlId]` on construction and clean up via `deinitialize()`.

`Prado.WebUI.Control.observe()` attaches native listeners and records each observed element in `Prado.Observed`. jQuery `.trigger()` does not reach native listeners, so the `jQuery.fn.trigger` bridge in `prado.js` dispatches a native event to an element registered under its own id or recorded in `Prado.Observed`. `TCallbackClientScript::raiseClientEvent()` goes through this bridge.

---

## Shell (`framework/Shell/`)

CLI support for the `prado-cli` executable (`bin/prado-cli`).

- `TShellApplication` — CLI application; mirrors `TApplication` lifecycle for command-line use.
- `TShellAction` — Base for shell actions (commands).
- `TShellWriter` — ANSI-colored console output.
- Built-in actions: `TWebServerAction`, `THelpAction`, `TFlushCachesAction`, `TDbParameterAction`, `TActiveRecordAction`, `TPhpShellAction`.
- Cron actions: `TShellCronAction`, `TShellDbCronAction`.

---

## Test Bootstrap

Tests require a running `TApplication`. Bootstrap: `tests/test_tools/phpunit_bootstrap.php` instantiates one from `tests/test_tools/Security/app/`.

- Database tests need MySQL/PostgreSQL pre-initialized from `tests/initdb_mysql.sql` / `tests/initdb_pgsql.sql`.
- JavaScript tests: Vitest in `tests/js/`.
- Functional tests: Playwright in `tests/playwright/`, Selenium in `tests/FunctionalTests/`.

---

## Directory Structure

```
./
├── agents/                     # Agent Working Knowledge
│   ├── PROJECT_CONTEXT.md      # This file — complete project architecture reference
│   ├── PROJECT_SUMMARY.md      # Quick-access cheatsheet for common agent needs
│   └── framework/              # Mirrors framework/ hierarchy
│       ├── CLASSES.md          # Full class listing (749 classes)
│       ├── INDEX.md            # Root framework knowledge
│       ├── SUMMARY.md          # Root framework class summaries
│       ├── <Class>.md          # Per-class knowledge files
│       └── <path>/INDEX.md     # Directory knowledge file
│       └── <path>/SUMMARY.md   # Class summary for directory
├── framework/                  # Framework source (PSR-4 Prado\)
│   ├── Caching/                # TAPCCache, TMemCache, TRedisCache, TDbCache, TEtcdCache
│   ├── Collections/            # TList, TMap, TPriorityList, TPriorityMap, TQueue, TStack, TWeakList
│   ├── Data/                   # TDbConnection (PDO), DataGateway; ActiveRecord and SqlMap are separate packages
│   ├── Exceptions/             # Exception hierarchy, TErrorHandler, multilingual error messages
│   ├── I18N/                   # TGlobalization, MessageSource, CultureInfo, date/number formatting
│   ├── IO/                     # Text writers, TTarFileExtractor, stream notifications
│   ├── PHPStan/                # PHPStan extensions for dynamic events and TComponent::isa()
│   ├── Security/               # TAuthManager, TSecurityManager, TUserManager, TPermissionsManager
│   ├── Shell/                  # TShellApplication, TShellAction, TShellWriter — CLI support
│   ├── Util/                   # Logging, behaviors, cron, helpers, TCallChain, TVarDumper
│   │   ├── Behaviors/          # Installable system behaviors
│   │   └── Cron/               # TCronModule, TDbCronModule, TTimeScheduler
│   ├── Web/                    # HTTP layer, routing, asset management, UI, services, JS
│   │   ├── Javascripts/        # TJavaScript, source/prado/ (browser JS for WebControls)
│   │   ├── Services/           # TPageService, TJsonService, TFeedService, TRpcService, TSoapService
│   │   └── UI/                 # TControl, TPage, TTemplateControl, TTheme
│   │       ├── ActiveControls/ # Callback-enabled active controls
│   │       ├── JuiControls/    # jQuery UI controls
│   │       └── WebControls/    # TButton, TCheckBox, TTextBox, TPanel, validation, etc.
│   ├── Xml/                    # TXmlDocument, TXmlElement — DOM-compatible with XPath
│   ├── classes.php             # Registry of all 749 Prado classes → namespace (must be kept current)
│   ├── TComponent.php          # Base class for all components
│   ├── TApplication.php        # Main application / service container
│   ├── TApplicationComponent.php
│   ├── TApplicationConfiguration.php
│   ├── TModule.php             # Base class for modules
│   ├── TService.php            # Base class for services
│   └── Prado.php               # Framework static utility class
├── bin/                        # prado-cli executable
├── tests/
│   ├── FunctionalTests/        # Selenium functional tests
│   ├── playwright/             # Playwright functional tests
│   ├── js/                     # Vitest unit tests for JavaScript
│   ├── test_tools/             # PHPUnit bootstrap, Selenium bootstrap
│   ├── unit/                   # PHPUnit tests mirroring framework/
│   └── initdb_*.sql            # Database initialization
├── AGENTS.md                   # Agent rules and guidelines
├── CLAUDE.md                   # Agent rules and guidelines (Claude-specific)
├── composer.json
├── HISTORY.md
└── README.md
```
