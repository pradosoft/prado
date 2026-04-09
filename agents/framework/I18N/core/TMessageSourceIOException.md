# I18N / core / TMessageSourceIOException

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [TMessageSourceIOException](./TMessageSourceIOException.md)

**Location:** `framework/I18N/core/TMessageSourceIOException.php`
**Namespace:** `Prado\I18N\core`

## Overview

Exception thrown when unable to read or write message source data (file system errors, permission issues).

## Example

```php
try {
    $source->save('messages');
} catch (TMessageSourceIOException $e) {
    // Unable to write to translation file
    echo "Error: " . $e->getMessage();
}
```

## See Also

- `TIOException` - Base I/O exception
- [MessageSource](./MessageSource.md) - Sources that may throw this