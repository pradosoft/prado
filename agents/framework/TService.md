# TService

### Directories
[framework](./INDEX.md) / **`TService`**

## Class Info
**Location:** `framework/TService.php`
**Namespace:** `Prado`
**Extends:** `TApplicationComponent`
**Implements:** `IService`

## Overview
Abstract base class for all application services. Services handle actual response generation; only one service runs per request. `TApplication` resolves which service to use from the request, then calls `init($config)` followed by `run()`.

## Core Properties

| Property | Description |
|----------|-------------|
| `ID` | Unique service identifier (set from `id` attribute in `application.xml`) |
| `Enabled` | Whether the service may handle requests; default `true` |

## Core Methods

| Method | Description |
|--------|-------------|
| `init($config)` | Initializes the service; raises `dyInit($config)` for behaviors. Always call `parent::init($config)` in subclasses. |
| `run()` | Handles the request. Override in subclasses to generate the response. Default implementation is empty. |
| `getID()` / `setID()` | Service identifier |
| `getEnabled()` / `setEnabled()` | Enable/disable the service |
| `getInstance(?TApplication $app = null): ?static` | Returns the currently active service if it is an instance of the called class, or `null` otherwise. Accepts an optional `TApplication` argument; defaults to `Prado::getApplication()`. @since 4.3.3 |

## Static Factory

`TService::getInstance()` provides a type-safe way to access the active service from anywhere in the application. Because the return type is `?static`, calling `TPageService::getInstance()` returns `?TPageService` — no cast required.

```php
// Available from onInitComplete onwards:
$app->onInitComplete[] = function () {
    TPageService::getInstance()?->onPreRunPage[] = function (TPageService $sender, TPage $page) {
        $page->onLoad[] = [$this, 'pageHandlerInModule'];
    };
};

// Or from within a module's init():
$service = TPageService::getInstance();
if ($service !== null) {
    // Only runs when the page service is active
}
```

## Dynamic Events

| Event | Signature | Description |
|-------|-----------|-------------|
| `dyInit` | `dyInit(mixed $config)` | Raised inside `init()`, allowing attached behaviors to hook into service initialization. @since 4.3.3 |

## Creating a Custom Service

```php
use Prado\TService;

class MyApiService extends TService
{
	public function run()
	{
		$response = $this->getResponse();
		$response->setContentType('application/json');
		$response->write(json_encode(['ok' => true]));
	}
}
```

```xml
<!-- application.xml -->
<services>
    <service id="api" class="MyApiService" />
</services>
```

## Built-in Services

| Class | Default ID | Purpose |
|-------|-----------|---------|
| `TPageService` | `page` | Template-based web pages |
| `TJsonService` | `json` | JSON responses |
| `TRpcService` | `rpc` | JSON-RPC / XML-RPC |
| `TSoapService` | `soap` | SOAP / WSDL |
| `TFeedService` | `feed` | RSS / Atom feeds |

## See Also
- [`TModule`](./TModule.md) — sibling base class for modules
- [`TApplication`](./TApplication.md) — manages service lifecycle