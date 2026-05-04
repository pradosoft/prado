# Exceptions/TNotSupportedException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TNotSupportedException`**

## Class Info
**Location:** `framework/Exceptions/TNotSupportedException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Represents an exception caused by using an unsupported PRADO feature. Extends `TSystemException`.

## Hierarchy

```
TNotSupportedException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TNotSupportedException('feature_not_supported', 'SOAP extension');
```

## Common Causes

- Missing PHP extension
- Feature disabled in configuration
- Platform/environment limitation

## See Also

- `[TSystemException](./TSystemException.md)` - System-level exceptions
