# Web/Services/TPageService

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TPageService`**

## Class Info
**Location:** `framework/Web/Services/TPageService.php`
**Namespace:** `Prado\Web\Services`

## Overview
Application services handle different types of HTTP responses. Registered in `application.xml`; `TApplication` activates the appropriate service based on the incoming request. `TPageService` is the default and most commonly used.

---

## TPageService

The primary service for template-based web pages.

### Configuration

**application.xml:**
```xml
<services>
  <service id="page" class="Prado\Web\Services\TPageService"
           BasePath="pages" DefaultPage="Home" />
</services>
```

**PHP equivalent:**
```php
return [
    'services' => [
        'page' => [
            'class' => 'Prado\Web\Services\TPageService',
            'properties' => ['BasePath' => 'pages', 'DefaultPage' => 'Home'],
        ],
    ],
];
```

### Activation

Activated when the URL contains no special service parameter, or when `ServiceID` matches the page service. Default `ServiceID` is `'page'`.

### Key Properties

| Property | Description |
|----------|-------------|
| `BasePath` | Root directory for page files (default: `pages/` under app root) |
| `DefaultPage` | Page to serve when no page specified (default: `'Home'`) |
| `ServiceParameter` | URL parameter identifying the page path |

### Page Resolution

URL `?page=Blog.Posts` maps to file `pages/Blog/Posts.php` (class) + `pages/Blog/Posts.page` (template).

### Directory Config

Each page directory can contain a `config.xml`:
```xml
<config>
    <authorization>
        <deny users="?" />
        <allow roles="member" />
    </authorization>
    <modules>
        <module id="..." class="..." />
    </modules>
</config>
```

Rules apply to all pages in that directory and subdirectories. `TPageConfiguration` parses these.

### Event

`onPreRunPage` — raised before the page lifecycle starts. Modules can inspect/modify the `TPage` object.

---

## TJsonService

Returns JSON responses from named handler classes.

```xml
<modules>
    <module id="json" class="Prado\Web\Services\TJsonService">
        <json id="api" class="MyJsonHandler" />
    </module>
</modules>
```

Handler class must implement a method returning the response data; `TJsonService` serializes it.

---

## TRpcService

Generic RPC framework supporting JSON-RPC and XML-RPC:

```xml
<modules>
    <module id="rpc" class="Prado\Web\Services\TRpcService" />
</modules>
```

Architecture:
- `TRpcProtocol` — marshals request/response format
- `TRpcServer` — request router
- `TRpcApiProvider` — implement RPC methods here (all public methods exposed)

---

## TSoapService

SOAP service with auto-generated WSDL.

```xml
<modules>
    <module id="soap" class="Prado\Web\Services\TSoapService">
        <soap id="api" class="MySoapProvider" />
    </module>
</modules>
```

Provider class uses `@soapmethod` PHPDoc annotation with typed `@param` and `@return` for WSDL generation. Requires PHP `soap` extension.

---

## TFeedService

RSS/Atom feed generation:

```xml
<modules>
    <module id="feed" class="Prado\Web\Services\TFeedService">
        <feed id="blog" class="MyFeedProvider" />
    </module>
</modules>
```

---

## Patterns & Gotchas

- **`TPageService` is the default** — omit it from `application.xml` to use defaults, or configure only what differs.
- **Service routing** — `THttpRequest` determines the active service. The `ServiceID` URL parameter selects the service; page name is the `ServiceParameter`.
- **SOAP WSDL** — Method parameters must have strict `@param Type $name` annotations. Missing type annotations produce invalid WSDL.
- **`TRpcApiProvider` auto-exposure** — all public methods are exposed; prefix internal helpers with `_` to signal intent (though they'll still be callable unless explicitly excluded).
