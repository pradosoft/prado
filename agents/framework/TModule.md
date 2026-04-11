# TModule

### Directories
[framework](./INDEX.md) / **`TModule`**

## Class Info
**Location:** `framework/TModule.php`
**Namespace:** `Prado`
**Extends:** `TApplicationComponent`

## Overview
Base class for all application modules. Modules are persistent services registered in `application.xml` and initialized once per request.

### Interface (IModule)

```php
interface IModule {
    public function init($config);   // called once during application init
    public function getID(): string;
    public function setID(string $id): void;
}
```

### Lifecycle

1. `TApplication` reads `application.xml`, creates each module instance.
2. Sets module `ID` from the `id` attribute.
3. Calls `init($configElement)` passing the XML config element.
4. Module becomes available via `$app->getModule($id)`.

### Creating a Module

```php
use Prado\TModule;

class MyModule extends TModule
{
    private string $_apiKey = '';

    public function init($config)
    {
        // $config is TXmlElement or null
        parent::init($config);  // raises dyInit — always call parent
        // Connect to external service, set up DB tables, etc.
    }

    public function getApiKey(): string { return $this->_apiKey; }
    public function setApiKey(string $v): void { $this->_apiKey = $v; }
}
```

```xml
<!-- application.xml -->
<module id="mymodule" class="MyModule" ApiKey="abc123" />
```

Access at runtime:
```php
$module = Prado::getApplication()->getModule('mymodule');
```

### Dynamic Events

- `dyPreInit($config, $chain)` — fired after module is loaded but before `init()`. Behaviors can intercept.
- `dyInit($config, $chain)` — fired inside `init()` after `parent::init()`.

### IDbModule

Marker interface for modules that own a database connection. Consumed by `TDbParameterModule` and `TPermissionsManager` when locating a DB-capable module.

### IPluginModule / TDbPluginModule

- `IPluginModule` — marker for Composer-installable extension modules.
- `TDbPluginModule` — extends `TModule` with a `TDbConnection` via `ConnectionID` property.

---

## TService

Base for application services. Services handle the actual response generation; only one service runs per request.

### Interface (IService)

```php
interface IService {
    public function init($config);
    public function run();              // generates response
    public function getID(): string;
    public function setID(string $id): void;
    public function getEnabled(): bool;
    public function setEnabled(bool $v): void;
}
```

### Built-in Services

| Class | ID | Purpose |
|-------|----|---------|
| `TPageService` | `page` | Template-based web pages |
| `TJsonService` | `json` | JSON responses |
| `TRpcService` | `rpc` | JSON-RPC / XML-RPC |
| `TSoapService` | `soap` | SOAP / WSDL |
| `TFeedService` | `feed` | RSS / Atom feeds |

### Service Activation

`THttpRequest` examines the URL/query-string to determine which service should run. The service `ID` appears as a URL parameter (e.g., `?page=Home`). Services not matched are not initialized.

---

## TApplicationComponent

Both `TModule` and `TService` extend `TApplicationComponent`. It provides:

```php
$this->getApplication();    // TApplication
$this->getService();        // current IService
$this->getRequest();        // THttpRequest
$this->getResponse();       // THttpResponse
$this->getSession();        // THttpSession
$this->getUser();           // IUser
```

Also provides `publishAssets($path)` for publishing framework assets and global event listening via `listen()`/`unlisten()`.
