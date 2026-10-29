# Web/Services/Rest/TRestResource

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestResource`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestResource.php`
**Namespace:** `Prado\Web\Services\Rest`
**Extends:** `TApplicationComponent`, implements `IRestResource`
**Since:** 4.4.0

## Overview
Base class of the resources `TRestService` serves. A handler is any public method carrying `#[TRestRoute(verbs, pattern)]`; a class-level `#[TRestRoute(pattern: 'users')]` is the prefix of every route of the resource.

```php
#[TRestRoute(pattern: 'users')]
class UsersResource extends TRestResource
{
    #[TRestRoute('GET', '{id:\d+}')]
    public function show(int $id): array { return UserDao::find($id) ?? $this->notFound(); }

    #[TRestRoute('POST')]
    public function store(): array
    {
        $data = $this->validateBody(['name' => 'required|string|max:255', 'email' => 'required|email']);
        return $this->created(UserDao::create($data));
    }
}
```

## Routes
- `getRoutes(): array<string, TRestRoute[]>` — for each public method, the `TRestRoute` attributes of the method, looked up through `TComponentReflection::getMethodAttributesByType()` from the class up its parents, so an override without the attribute keeps the inherited route. Override to declare routes as a table: `return parent::getRoutes() + ['export' => [new TRestRoute('GET', 'export')]];`.
- `getRoutePrefix()` — the `pattern` of the nearest class-level `TRestRoute` (`TComponentReflection::getClassAttributesByType()` with inheritance), `''` for none. A `prefix` attribute in the configuration replaces it.
- `setRoute($route, $parameters)`, `getRoute()`, `getPathParameters()`, `getPathParameter($name, $default)` — set by the service before dispatch.
- `authorize(TRestRoutePattern $route)` — no-op; override and throw (`unauthorized()`, `forbidden()`) to refuse.
- `getService()` — the running `TRestService`, `null` outside one (for `constructUrl()`).

## Request helpers
| Method | Reads |
|---|---|
| `getBody()` | `THttpRequest::getParsedBody()` (JSON, `+json`, form on any verb, multipart; malformed JSON is `400`) |
| `input($key, $default)` | body field, then `THttpRequest::getQueryParameters()` |
| `query($key, $default)` | query string only |
| `hasInput($key)`, `only($keys)`, `except($keys)` | body (and query for `hasInput`) |

## Response helpers
- `created($data)` (201), `accepted($data)` (202), `noContent()` (204); `getStatusCode()`.
- `header($name, $value)` — adds a header, `TInvalidDataValueException('restresource_invalid_header')` for a bad name or a CR/LF value; `getResponseHeaders()`.
- `abort($status, $detail, $errors)`, `notFound()`, `unauthorized()`, `forbidden()`, `conflict()`, `unprocessable($errors, $detail)` — throw `TRestException`.

## Validation
- `validate($data, $rules)` / `validateBody($rules)` — `TInputValidator` rules (`'age' => 'required|integer|min:0'`); return the declared, coerced fields; throw `TRestException::unprocessable()` (422) with the field errors and the localized detail `The given data was invalid.`.
- `getValidator()` / `setValidator()` — the validator, created on first use; set one with `addRule()` for custom rules.

## Patterns & Gotchas
- A resource is created once per request by the service; keep handler state in local variables.
- `getRoutes()` returns attribute instances, not compiled routes; the service joins the prefixes and builds `TRestRoutePattern`.
- The old `doIndex`/`doShow` naming convention and `__call` are gone; every handler is declared.
