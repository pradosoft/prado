# Web/UI/IPostBackDataHandler

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / **`IPostBackDataHandler`**

**Location:** `framework/Web/UI/IPostBackDataHandler.php`
**Namespace:** `Prado\Web\UI`

## Overview
Interface for controls that load postback data.

## Key Methods

| Method | Description |
|--------|-------------|
| `loadPostData(string $key, array $values): bool` | Loads user input data from postback |
| `raisePostDataChangedEvent()` | Raises event when data changes |
| `getDataChanged(): bool` | Whether postback caused data change |

## See Also

- [TControl](./TControl.md) - Base control class
