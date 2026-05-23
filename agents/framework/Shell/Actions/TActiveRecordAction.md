# Shell/Actions/TActiveRecordAction

### Directories
[framework](../../INDEX.md) / [Shell](../INDEX.md) / [Actions](./INDEX.md) / **`TActiveRecordAction`**

## Class Info
**Location:** `framework/Shell/Actions/TActiveRecordAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview
Generates `TActiveRecord` class skeletons from database tables.

## Availability

This action is only registered by `TShellApplication::installShellActions()` when a `TActiveRecordConfig` module is present (`hasActiveRecordConfig()` returns true).

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

# With soap and overwrite flags, and prefix/suffix
php prado-cli.php activerecord/generate-all App/Records soap overwrite MyPrefix MySuffix

# Suffix only (soap and overwrite are positional — use empty or omit)
php prado-cli.php activerecord/generate-all App/Records soap false "" MySuffix
```

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'activerecord'` |
| `methods` | `['generate', 'generate-all']` |
| `parameters` | `generate`: `[table, output]`; `generate-all`: `[output]` |
| `optional` | `generate`: `[soap, overwrite]`; `generate-all`: `[soap, overwrite, prefix, suffix]` |

## Subcommands

### `activerecord/generate <table> <output> [soap] [overwrite]`

Generates a single record class for `<table>`. `<output>` is a Prado namespace path (e.g., `App.Models.User`). The output file must resolve to a path inside the application base directory. Skips generation if the file already exists and `overwrite` is not set.

### `activerecord/generate-all <output> [soap] [overwrite] [prefix] [suffix]`

Queries the database for all tables and calls `actionGenerate()` for each. `<output>` is the base namespace directory. Generated class names are `ucfirst(table_name)` with optional prefix/suffix prepended/appended. All arguments are positional (not flags).

## Output File Constraint

`getOutputFile()` enforces that the resolved output path is within the application `BasePath`. Files outside the app directory are rejected with an error.

## Supported Databases

MySQL, MySQLi, SQLite, PostgreSQL, MSSQL, SQLSRV, DBLIB, Oracle, IBM DB2, Firebird/Interbase

## See Also

- [TShellAction](../TShellAction.md) - Base class
- [TActiveRecord](../../Data/TActiveRecord.md) - Active Record base class
- [TShellApplication](../TShellApplication.md) - `hasActiveRecordConfig()` gate