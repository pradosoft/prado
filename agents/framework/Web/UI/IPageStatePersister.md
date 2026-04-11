# Web/UI/IPageStatePersister

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / **`IPageStatePersister`**

**Location:** `framework/Web/UI/IPageStatePersister.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface for page state persistence implementations.

## Key Methods

| Method | Description |
|--------|-------------|
| `getPage(): TPage` | Returns the page this persister works for |
| `setPage(TPage $page)` | Sets the page |
| `save($state)` | Saves state to persistent storage |
| `load(): mixed` | Loads state from persistent storage |

## See Also

- [TPage](./TPage.md) - Page class
- [TSessionPageStatePersister](./TSessionPageStatePersister.md) - Session-based implementation
- [TCachePageStatePersister](./TCachePageStatePersister.md) - Cache-based implementation
