# Exceptions/TConfigurationException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TConfigurationException`**

## Class Info
**Location:** `framework/Exceptions/TConfigurationException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Represents an exception caused by invalid configurations, such as errors in application configuration files or control template files.

## Hierarchy

```
TConfigurationException
└── [TSystemException](./TSystemException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TConfigurationException('config_error_code', $param1);
```

## Common Error Codes

- Invalid application.xml structure
- Missing required module properties
- Invalid template syntax

## See Also

- `[TException](./TException.md)` - Base exception class
- `[TSystemException](./TSystemException.md)` - System-level exceptions
