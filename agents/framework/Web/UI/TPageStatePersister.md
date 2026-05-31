# Web/UI/TPageStatePersister

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](INDEX.md) / **`TPageStatePersister`**

## Class Info
**Location:** `framework/Web/UI/TPageStatePersister.php`
**Namespace:** `Prado\Web\UI`
**Extends:** [`TComponent`](../../TComponent.md)
**Implements:** `IPageStatePersister`
**Since:** 3.0

## Overview
`TPageStatePersister` is the default page-state persister. It serialises the view-state into a hidden form field (`__VIEWSTATE`) on save and deserialises it from the same field on load. Because the state travels with the form submission, it requires no server-side storage but does increase the page payload size.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Page` | [`TPage`](./TPage.md) | The page instance this persister works for. |

## Key Methods

| Method | Description |
|--------|-------------|
| `save(mixed $state): void` | Serialises and encodes `$state`, then writes it into the page's hidden `__VIEWSTATE` field. |
| `load(): mixed` | Reads the `__VIEWSTATE` field, decodes and deserialises it, and returns the state. Throws `THttpException` if the state is corrupted. |

## See Also

- [`TSessionPageStatePersister`](./TSessionPageStatePersister.md) — alternative persister that stores state in the PHP session
- `IPageStatePersister` — interface defining `save()` and `load()`
- [`TPage`](./TPage.md) — owner of the persister
