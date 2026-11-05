# Web/Services/Rest/TRestPagination

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestPagination`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestPagination.php`
**Namespace:** `Prado\Web\Services\Rest`
**Extends:** `TApplicationComponent`
**Since:** 4.4.0

## Overview
Pagination of a list handler. `fromRequest($request, $defaultPerPage, $maxPerPage)` reads `page` and `per_page` through `THttpRequest::itemAt()`; `Page` is clamped to at least 1 and `PerPage` to `[1, MaxPerPage]`. `getOffset()` / `getLimit()` feed a query; `toMeta($total)` gives `total`, `per_page`, `current_page`, `last_page`, `from`, `to`; `paginate($data, $total)` wraps a page as `{data, meta}`.

```php
#[TRestRoute('GET')]
public function index(): array
{
    $pagination = TRestPagination::fromRequest();
    return $pagination->paginate(User::findAll($pagination->getOffset(), $pagination->getLimit()), User::count());
}
```

## Gotchas
- `getOffset()` returns `PHP_INT_MAX` instead of overflowing when `(page - 1) * perPage` exceeds it.
- `from` and `to` are `null` for an empty total.
