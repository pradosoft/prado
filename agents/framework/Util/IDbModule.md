# IDbModule

### Directories

[Util](../) > IDbModule

**Location:** `framework/Util/IDbModule.php`
**Namespace:** `Prado\Util`

## Overview

Interface for database modules. Extends `[IModule](../TModule.md)`.

## Key Methods

| Method | Description |
|--------|-------------|
| `getDbConnection(): [TDbConnection](../Data/TDbConnection.md)` | Returns the database connection instance |
| `getConnectionID(): string` | Returns the ID of the `[TDataSourceConfig](../Data/TDataSourceConfig.md)` module |
| `setConnectionID(string $value)` | Sets the datasource module ID |

## See Also

- `[TDbParameterModule](TDbParameterModule.md)` - Implements this interface
- `TDbPluginModule` - Implements this interface
