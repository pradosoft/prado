# Data/TDbNullConversionMode

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbNullConversionMode`**

## Class Info
**Location:** `framework/Data/TDbNullConversionMode.php`
**Namespace:** `Prado\Data`

## Overview
`TDbNullConversionMode` is an enumerable class that specifies how NULL and empty values should be converted when reading from the database.

## Constants

- `Preserved` - No conversion is performed for null and empty values
- `NullToEmptyString` - NULL values are converted to empty strings
- `EmptyStringToNull` - Empty strings are converted to NULL

## Usage

```php
TDbNullConversionMode::Preserved;           // Keep original values
TDbNullConversionMode::NullToEmptyString;    // NULL becomes ''
TDbNullConversionMode::EmptyStringToNull;   // '' becomes NULL
```

## See Also

- [TDbConnection](./TDbConnection.md) - Uses this for null/empty value handling