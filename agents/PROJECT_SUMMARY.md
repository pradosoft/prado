# Project Summary — Prado Framework

Quick-access cheatsheet for common agent needs. For full detail see [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md).

---

## Version

| Item | Value |
|---|---|
| Current release | `4.3.2` |
| **Next release (`@since` tag)** | **`4.3.3`** |
| PHP minimum | 8.1 |
| Namespace root | `Prado\` |
| Total classes | 749 (see `agents/framework/CLASSES.md`) |

---

## Key Rules (Always Apply)

- `framework/classes.php` **must** be updated for every new class.
- `if` statements **always** use a block `{}` — never a single-line body.
- All changes must be **backward compatible** between point versions.
- `@since` tag uses next release (`4.3.3`); omit on methods when class `@since` matches.
- Documentation: **Present Perfect tense**, **American English**.
- `dy*` dynamic events on a class **must** be documented with `@method` in the class docblock.
- Error codes go in `framework/Exceptions/messages/messages.txt` first.
- Property setters use `TPropertyValue::ensureXxx()` for type coercion.
- Behaviors **must** unregister event handlers in `detach()` that were registered in `attach()`.

---

## Event Prefix Quick Reference

| Prefix | What it is | How to call | How to handle |
|---|---|---|---|
| `on` | Object event | `$this->raiseEvent('OnFoo', $this, $param)` | `$obj->attachEventHandler('OnFoo', $handler)` |
| `dy` | Dynamic / behavior event | `$this->dyFoo($arg)` via `__call` | Implement `dyFoo($arg, TCallChain $chain)` in behavior |
| `fx` | Global static event | Auto-dispatched to all listeners | Implement `fxFoo(...)` on listening class; `getAutoGlobalListen()` |

**`dy*` chain rule:** First parameter is filtered and returned. Always accept `TCallChain` as last parameter. Call `$chain->dyFoo(...)` to continue or return early to break.

---

## Property System Pattern

```php
// Define:
private string $_name = '';
public function getName(): string { return $this->_name; }
public function setName(string $v): void { $this->_name = $v; }

// Setters that accept string input from config/templates:
public function setMaxRetries(int|string $v): void {
    $this->_maxRetries = TPropertyValue::ensureInteger($v);
}

// Usage via magic:
$obj->Name = 'foo';       // calls setName()
echo $obj->Name;          // calls getName()
isset($obj->Name);        // calls getName() !== null
unset($obj->Name);        // calls setName(null) or clears event handlers
```

---

## Module Pattern

```php
class TMyModule extends TModule implements IModuleDependency
{
    private string $_apiKey = '';

    public function getModuleDependencies(): array
    {
        return ['db', 'cache'];  // module IDs this depends on
    }

    public function init($config): void
    {
        parent::init($config);  // raises dyInit — always call parent
        // Setup code here
    }

    public function getApiKey(): string { return $this->_apiKey; }
    public function setApiKey(string $v): void { $this->_apiKey = $v; }
}
```

```xml
<!-- application.xml -->
<module id="mymodule" class="MyApp\TMyModule" ApiKey="abc123" />
```

```php
// Access:
$module = Prado::getApplication()->getModule('mymodule');
```

---

## TApplication Common Accessors

```php
$app = Prado::getApplication();
$app->getModule('id');          // any registered module
$app->getRequest();             // THttpRequest
$app->getResponse();            // THttpResponse
$app->getSession();             // THttpSession
$app->getUser();                // IUser (guest if unauthenticated)
$app->getCache();               // ICache (primary cache module)
$app->getSecurityManager();     // TSecurityManager
$app->getService();             // current IService
$app->getGlobalization();       // TGlobalization or null
$app->getParameters();          // TAttributeCollection of config params
$app->getGlobalState($k, $d);  // persisted cross-request state
$app->setGlobalState($k, $v, $d);
```

From any `TApplicationComponent` (controls, modules, services):
```php
$this->getApplication();   $this->getRequest();
$this->getResponse();      $this->getSession();
$this->getUser();          $this->getService();
```

---

## TComponent Behavior API

```php
// Instance:
$obj->attachBehavior('name', new MyBehavior());
$obj->detachBehavior('name');
$obj->getBehavior('name');
$obj->getBehaviors();                      // TPriorityMap
$obj->getBehaviors(IModuleDependency::class);  // filtered by interface
$obj->asa('MyBehavior');                   // by class name
$obj->isa('MyBehavior');                   // true if IS or HAS that class

// Class-wide:
TComponent::attachClassBehavior('name', new MyClassBehavior(), MyClass::class);
TComponent::detachClassBehavior('name', MyClass::class);
```

**`isa()` is not `instanceof`** — `isa()` returns true if the object IS the class OR has an attached behavior of that class.

---

## Serialization Pattern

```php
public function _getZappableSleepProps(array &$exprops): void
{
    parent::_getZappableSleepProps($exprops);
    $exprops[] = "\0" . self::class . "\0_myPrivateProp";
}
```

---

## Common Exception Types

```php
throw new TInvalidDataValueException('error_code', $param);
throw new TInvalidOperationException('error_code');
throw new TConfigurationException('error_code', $param);
throw new THttpException(404, 'page_not_found');
throw new TNotSupportedException('error_code');
```

Error codes defined in `framework/Exceptions/messages/messages.txt`. Format: `error_code=Message with {0} placeholder`.

---

## TPropertyValue Conversions

```php
TPropertyValue::ensureBoolean($v);   // '1', 'true', 'yes', 'on' → true
TPropertyValue::ensureInteger($v);   // '42' → 42
TPropertyValue::ensureFloat($v);     // '3.14' → 3.14
TPropertyValue::ensureString($v);    // any → string
TPropertyValue::ensureArray($v);     // CSV string or array → array
TPropertyValue::ensureEnum($v, TMyEnum::class); // validates enum value
```

---

## Application Lifecycle (Ordered)

```
initApplication() → onInitComplete
run() →
  onBeginRequest → onLoadState → onLoadStateComplete →
  onAuthentication → onAuthenticationComplete →
  onAuthorization → onAuthorizationComplete →
  onPreRunService → runService() →
  onSaveState → onSaveStateComplete →
  onPreFlushOutput → flushOutput() → onEndRequest
```

`onError` fires on any unhandled exception. `onEndRequest` always runs.

---

## Module Initialization Phases (ordered by dependency)

1. Instantiate + set properties
2. `dyPreInit($config)` — behaviors intercept before init
3. `init($config)` — primary init; call `parent::init($config)`
4. `dyPostInit($config)` — cross-module wiring after all deps initialized

Module dependencies declared via `IModuleDependency::getModuleDependencies(): ?array` — returns array of module ID strings. Both the module and its attached behaviors may implement `IModuleDependency`. Both sources fully re-evaluated before every phase.

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

## Database Quick Reference

```php
// ── 1. TDbConnection (PDO wrapper) ──────────────────────────────────────────
$conn = new TDbConnection('mysql:host=localhost;dbname=mydb', 'user', 'pass');
$conn->open();
$cmd = $conn->createCommand('SELECT * FROM t WHERE id = :id AND active = :a');
$cmd->bindValue(':id', $id);
$cmd->bindValue(':a', 1);
$row  = $cmd->queryRow();       // first row as array
$rows = $cmd->queryAll();       // all rows
$val  = $cmd->queryScalar();    // single value
$col  = $cmd->queryColumn();    // first column as array
$cmd->execute();                // INSERT / UPDATE / DELETE (returns row count)

// Transaction:
$tx = $conn->beginTransaction();
try { /* ... */ $tx->commit(); } catch (\Exception $e) { $tx->rollBack(); throw $e; }

// ── 2. TActiveRecord (stateful ORM) ─────────────────────────────────────────
class UserRecord extends TActiveRecord {
    const TABLENAME = 'users';
    public $id, $name, $email;
    public static function finder($c = __CLASS__) { return parent::finder($c); }
}
$user  = UserRecord::finder()->findByPk(1);
$users = UserRecord::finder()->findAll();           // TDbDataReader
$users = UserRecord::finder()->findAllByAttributes(['active' => 1]);
$user  = UserRecord::finder()->findBySql('SELECT * FROM users WHERE id = :id', [':id' => 1]);
$count = UserRecord::finder()->count();
$user->name = 'New'; $user->save();                // INSERT or UPDATE
$user->delete();
$new = new UserRecord(['name' => 'Alice']); $new->insert();

// ── 3. TTableGateway (stateless gateway) ─────────────────────────────────────
$gw = new TTableGateway('users', $conn);
$row  = $gw->findByPk(1);
$rows = $gw->findAll(new TSqlCriteria('active = :a', [':a' => 1]));
$rows = $gw->findBySql('SELECT * FROM users WHERE name LIKE :n', [':n' => 'A%']);
$n    = $gw->findCount(new TSqlCriteria('active = 1'));
$gw->insert(['name' => 'Alice', 'email' => 'a@example.com']);
$gw->updateByPk(['name' => 'Bob'], 1);
$gw->update(['active' => 0], new TSqlCriteria('created_at < :d', [':d' => $date]));
$gw->deleteByPk(1);
$gw->delete(new TSqlCriteria('active = 0'));

// TSqlCriteria — used by TTableGateway and TActiveRecord finders:
$c = new TSqlCriteria('active = :a AND role = :r', [':a' => 1, ':r' => 'admin']);
$c->OrdersBy = ['name' => 'asc', 'created_at' => 'desc'];
$c->Limit  = 20;
$c->Offset = 40;
$c->Select = 'id, name, email';   // columns to select (default *)
```

Always use parameter binding — never interpolate user input into SQL.

---

## Caching Quick Reference

```php
$cache = $app->getCache();           // primary cache (ICache)
$val = $cache->get('mykey');
$cache->set('mykey', $val, 3600);    // TTL in seconds (0 = forever)
$cache->add('mykey', $val);          // no-op if key exists
$cache->delete('mykey');
$cache['mykey'] = $val;              // ArrayAccess — set with no TTL
```

Cache backends: `TAPCCache`, `TMemCache`, `TRedisCache`, `TDbCache`, `TEtcdCache`. Configure in `application.xml`.

---

## Security Quick Reference

```php
$user = $this->getUser();       // IUser (always present; guest if not logged in)
$user->getName();               // '' if guest
$user->getIsGuest();            // true if not authenticated
$user->getRoles();              // array of role strings

// RBAC:
$pm = $app->getModule('permissions');  // TPermissionsManager
// Permission names: 'blog.post.create', 'admin.users.*'
// Wildcards: '*' = all users, '?' = authenticated only
```

---

## Logging Quick Reference

All `Prado::*` log methods accept `($msg, $category = null, $ctl = null)` unless noted.
`$category` defaults to the calling class name. `$ctl` is an optional `TControl` or string context.

```php
// Level-specific shorthand methods:
Prado::debug($msg, $category, $ctl);    // TLogger::DEBUG  — only fires in Debug mode
Prado::trace($msg, $category, $ctl);    // DEBUG in Debug mode, INFO otherwise; silent in Performance
Prado::info($msg, $category, $ctl);     // TLogger::INFO
Prado::notice($msg, $category, $ctl);   // TLogger::NOTICE
Prado::warning($msg, $category, $ctl);  // TLogger::WARNING
Prado::error($msg, $category, $ctl);    // TLogger::ERROR
Prado::alert($msg, $category, $ctl);    // TLogger::ALERT
Prado::fatal($msg, $category, $ctl);    // TLogger::FATAL

// Generic — pass any TLogger level constant:
Prado::log($msg, TLogger::WARNING, 'My.Category', $ctl);  // returns ?float

// Profiling — PROFILE_BEGIN / PROFILE_END matched by $token + category:
Prado::profileBegin($token, $category, $ctl);           // void
$elapsed = Prado::profileEnd($token, $category, $ctl);  // returns float seconds

// Direct logger access:
$logger = Prado::getLogger();                   // TLogger instance
$logger->getLogs($levels, $categories);         // filter; $levels = bitmask
$logger->deleteLogs($levels, $categories);
$logger->flush($final = false);                 // route to TLogRoute targets + clear
$logger->getLogCount();

// Variable dump (use instead of var_dump for TComponent objects):
$str = Prado::varDump($var, $depth = 10, $highlight = false);
```

**TLogger level constants:**
```php
TLogger::DEBUG    = 0x01    TLogger::INFO     = 0x02
TLogger::NOTICE   = 0x04    TLogger::WARNING  = 0x08
TLogger::ERROR    = 0x10    TLogger::ALERT    = 0x20
TLogger::FATAL    = 0x40    TLogger::PROFILE  = 0x40000
TLogger::PROFILE_BEGIN = 0x80000    TLogger::PROFILE_END = 0x100000
```

Category convention: dot-separated, e.g., `'Prado.Web.THttpRequest'`, `'MyApp.Auth'`.

---

## XML Quick Reference

```php
$doc = new TXmlDocument('1.0', 'utf-8');
$doc->loadFromFile('config.xml');
// or:
$doc->loadFromString('<root><item id="1">Hello</item></root>');

foreach ($doc->Elements as $child) {
    echo $child->TagName . ': ' . $child->Value;
    echo $child->Attributes['id'];
}

$results = $doc->xpath('//item[@id="1"]');
```

---

## application.xml Skeleton

```xml
<application id="MyApp" Mode="Debug">
    <modules>
        <module id="db" class="Prado\Data\TDataSourceConfig"
                ConnectionString="mysql:host=localhost;dbname=myapp"
                Username="root" Password="secret" />
        <module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" />
        <module id="log" class="Prado\Util\TLogRouter">
            <module class="Prado\Util\TFileLogRoute" Levels="error,warning" />
        </module>
        <module id="auth" class="Prado\Security\TAuthManager"
                UserManager="users" LoginPage="Login" />
        <module id="users" class="Prado\Security\TUserManager">
            <user name="admin" password="secret" roles="admin" />
        </module>
    </modules>
    <services>
        <service id="page" class="Prado\Web\Services\TPageService"
                 BasePath="Application.Pages" DefaultPage="Home" />
    </services>
    <parameters>
        <parameter id="AppVersion" value="1.0.0" />
    </parameters>
</application>
```

---

## Working Knowledge Files

Scan: `find agents/ -type f -name '*.md' | sort`

| File | Purpose |
|---|---|
| `agents/PROJECT_CONTEXT.md` | Full architecture reference |
| `agents/PROJECT_SUMMARY.md` | This file — quick-access cheatsheet |
| `agents/framework/CLASSES.md` | All 749 classes listed by namespace |
| `agents/framework/INDEX.md` | Root framework directory knowledge |
| `agents/framework/SUMMARY.md` | Root class one-liners |
| `agents/framework/<Class>.md` | Per-class knowledge (TComponent, TApplication, etc.) |
| `agents/framework/<Dir>/INDEX.md` | Directory knowledge for each subsystem |
| `agents/framework/<Dir>/SUMMARY.md` | Class summaries for each directory |

**Working knowledge files are read-only** — do not update them unless explicitly instructed.
