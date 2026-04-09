# IPostBackDataHandler

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [IPostBackDataHandler](./IPostBackDataHandler.md)

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

(End of file - total 20 lines)
