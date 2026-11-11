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
<modules>
    <!-- application.xml -->
    <module id="mymodule" class="MyModule" ApiKey="abc123" />
</modules>
```

Access at runtime:
```php
$module = Prado::getApplication()->getModule('mymodule');
```

### Child Elements (4.4.0)

A module creates a component that only it uses from a child element of its configuration, with no module ID:

| Method | Result |
|---|---|
| `getConfigElements($config, $tag, $multiple = false)` | XML: the direct child elements named `$tag` (the first only, unless `$multiple`). PHP: the `$tag` key as one element (`['class' => ..., 'properties' => [...]]`, or a class name), or with `$multiple` also a list of them. A PHP element that is neither throws `module_config_class_required`. |
| `createElementComponent($element, $tag, $type, $error = null)` | Creates the `class` of one element, applies the other attributes (or `properties`) with `setSubProperties()`, and calls `init($element)` when it is an `IModule`. A component given properties must be a `TComponent`; one without may be any class. |
| `createConfigComponent($config, $tag, $type, $error = null)` | The component of the first element with the tag, or null when there is none. |

| Condition | Exception |
|---|---|
| Element has no `class` | `module_config_class_required` |
| PHP `properties` is not an array | `module_config_properties_invalid` |
| Component is not `$type` | `$error` with the class, or `module_config_component_invalid` |
| Component is given properties and is not a `TComponent` | `module_config_properties_unsupported` |

```xml
<module id="mymodule" class="MyModule">
    <mutex class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
</module>
```

```php
'mymodule' => ['class' => MyModule::class, 'mutex' => ['class' => TDbMutex::class, 'properties' => ['ConnectionID' => 'db']]],
// in MyModule::init($config):
$mutex = $this->createConfigComponent($config, 'mutex', IMutex::class);
```

`TCronModule` creates its `<wake>`, `<mutex>`, and `<dependency>` components this way. `TLogRouter` creates each `<route>` with `getConfigElements()` and `createElementComponent()`; its PHP key is `routes`. Modules that configure an existing object from an element (`TDataSourceConfig` `<database>`, `TCompressionConfigTrait` `<compression>`) parse their elements themselves.

### Dynamic Events

| Event | Signature | When |
|-------|-----------|------|
| `dyPreInit` | `dyPreInit(mixed $config)` | Fired by `TApplication` after loading the module but **before** calling `init()`. Behaviors can intercept to modify `$config` or perform setup. |
| `dyInit` | `dyInit(mixed $config)` | Fired inside `TModule::init()`. Behaviors hook here to participate in module initialization. Always call `parent::init($config)` to trigger this. |

Both events are declared in the class doc-block PHPDoc so IDEs and static analysis see them.

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
