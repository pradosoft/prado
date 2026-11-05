# Web/Services/Rest/TRestRoute

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestRoute`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestRoute.php`
**Namespace:** `Prado\Web\Services\Rest`
**Kind:** final PHP attribute, `TARGET_CLASS | TARGET_METHOD | IS_REPEATABLE`
**Since:** 4.4.0

## Overview
Declares what a resource method handles. Readonly constructor properties: `verbs` (`?string`, `Verbs` syntax, `null` for every verb), `pattern` (relative to the class prefix, `''` for the prefix itself), `parameters` (placeholder constraints), `name` (route name; default `ClassShortName.method`), `query` (GET variable constraints).

```php
#[TRestRoute(pattern: 'users')]            // class: prefix
class UsersResource extends TRestResource
{
    #[TRestRoute('GET', '{id:\d+}')]        // method: verbs, pattern
    #[TRestRoute('GET', 'by-id/{id}', name: 'users.byId')]
    public function show(int $id): array {}
}
```

## Gotchas
- Not a `TComponent`; `TRestService` turns each instance into a `TRestRoutePattern`.
- Inheritance is handled by `TRestResource::getRoutes()` and `getRoutePrefix()`, not by PHP; an override without the attribute keeps the parent's declaration.
