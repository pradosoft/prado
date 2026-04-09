# Shell / Actions / TActiveRecordAction

### Directories
[./](../INDEX.md) > [Shell](../INDEX.md) > [Actions](./INDEX.md) > [TActiveRecordAction](./TActiveRecordAction.md)

**Location:** `framework/Shell/Actions/TActiveRecordAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview

Generates `TActiveRecord` class skeletons from database tables.

## Usage

```bash
# Generate for single table
php prado-cli.php activerecord/generate table_name App\Models\User

# Generate with SOAP properties
php prado-cli.php activerecord/generate table_name App\Models\User soap

# Overwrite existing files
php prado-cli.php activerecord/generate table_name App\Models\User soap overwrite

# Generate for ALL tables
php prado-cli.php activerecord/generate-all App/Records

# With prefix/suffix
php prado-cli.php activerecord/generate-all App/Records null MyPrefix MySuffix
```

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'activerecord'` |
| `methods` | `['generate', 'generate-all']` |

## Subcommands

### `activerecord/generate <table> <output> [soap] [overwrite]`

Generates a single record class.

### `activerecord/generate-all <output> [soap] [overwrite] [prefix] [suffix]`

Generates record classes for all tables.

## Supported Databases

MySQL, MySQLi, SQLite, PostgreSQL, MSSQL, SQLSRV, DBLIB, Oracle, IBM DB2, Firebird/Interbase

## See Also

- [TShellAction](../TShellAction.md) - Base class
- [TActiveRecord](../../Data/TActiveRecord.md) - Active Record base class