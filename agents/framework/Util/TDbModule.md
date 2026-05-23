# Util/TDbModule

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TDbModule`**

## Class Info
**Location:** `framework/Util/TDbModule.php`
**Namespace:** `Prado\Util`
**Extends:** [`TModule`](../TModule.md)
**Implements:** [`IDbModule`](IDbModule.md)
**Since:** 4.3.3

## Overview
`TDbModule` is the standard base class for database-backed modules. It combines `TModule` with the `TDbPropertiesTrait` connection helpers and the `IDbModule` interface, so subclasses get a `ConnectionID` property and a `getDbConnection()` method out of the box.

`TDbModule` guards `setConnectionID()` — if the subclass implements `assertUninitialized()`, the property cannot be changed after `init()` completes.

## Relationship to TDbPropertiesTrait / IDbModule

| What | Where it comes from |
|------|---------------------|
| `getConnectionID()` / `setConnectionID()` | `TDbPropertiesTrait` (aliased, guarded) |
| `getDbConnection()` | `TDbPropertiesTrait` |
| `IDbModule` contract | implemented here |

## Configuration

```xml
<modules>
  <module id="db" class="Prado\Data\TDataSourceConfig">
    <database ConnectionString="mysql:host=localhost;dbname=mydb"
      Username="dbuser" Password="dbpass" />
  </module>
  <module id="mydbmodule" class="MyApp\MyDbModule" ConnectionID="db" />
</modules>
```

PHP style:

```php
return [
    'modules' => [
        'db' => ['class' => 'Prado\Data\TDataSourceConfig', ...],
        'mydbmodule' => [
            'class' => 'MyApp\MyDbModule',
            'properties' => ['ConnectionID' => 'db'],
        ],
    ],
];
```

## Key Methods

```php
$module->setConnectionID(string $id): void   // must be set before init()
$module->getDbConnection(): TDbConnection    // from TDbPropertiesTrait
```

## Subclasses

- [`TDbParameterModule`](TDbParameterModule.md) — DB-backed parameter store
- [`TDbCronManager`](Cron/TDbCronManager.md) — DB-backed cron scheduler

## Patterns & Gotchas

- **Call `setConnectionID()` before `init()`** — the guard throws `TInvalidOperationException` if the module has already initialized.
- **Default SQLite** — `TDbPropertiesTrait::getDbConnection()` falls back to a local SQLite file in the runtime path when no `ConnectionID` is set. Override `getSqliteDatabaseName()` in subclasses to customise the filename.
- **Post-init guard is automatic** — if a subclass implements `assertUninitialized()`, `setConnectionID()` calls it automatically, so the guard works without any extra wiring.
