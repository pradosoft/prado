# Data/ActiveRecord/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md)] | Data Directory |
| [`Exceptions/`](Exceptions/INDEX.md)] | `TActiveRecordException`, `TActiveRecordConfigurationException` |
| [`Relations/`](Relations/INDEX.md) | `TActiveRecordHasOne`, `HasMany`, `BelongsTo`, `HasManyAssociation` + context class |
| [`Scaffold/`](Scaffolding/INDEX.md) | Auto-generated CRUD UI controls (`TScaffoldListView`, `TScaffoldEditView`, etc.) |
| [`Scaffold/InputBuilder/`](Scaffolding/InputBuilder/INDEX.md) | Driver-specific column-to-control mapping for scaffold edit forms |

## Purpose

Active Record ORM for the Prado framework. Each AR class maps to one database table; instances represent individual rows and encapsulate both data and persistence logic.

## Core Classes

- **`TActiveRecord`** — Base class for all Active Record domain objects. Key conventions:
  - Declare `const TABLENAME = 'my_table'` (optional; defaults to lowercased class name).
  - Declare `public static $COLUMN_MAPPING = ['db_col' => 'phpProp']` for name translation.
  - Declare `public static $RELATIONS = [...]` for lazy-loaded relationships.
  - Public properties correspond to table columns (or COLUMN_MAPPING entries).
  - **Static finder via `finder()`:** `UserRecord::finder()->findByPk(1)`, `findAll()`, `findBySql()`, `findAllBySql()`, `count()`.
  - **Persistence:** `save()` (insert or update), `insert()`, `update()`, `delete()`.
  - **Lifecycle events:** `onBeforeSave`, `onAfterSave`, `onBeforeDelete`, `onAfterDelete` — attach handlers or override.
  - **Criteria:** `findAll(new TActiveRecordCriteria('active=1'))`.

- **`TActiveRecordManager`** — Singleton registry. Provides the shared `TDbConnection` and `TDbMetaData` cache. Configure via `TActiveRecordConfig` in `application.xml` or call `TActiveRecordManager::getInstance()->setDbConnection($conn)` directly.

- **`TActiveRecordConfig`** — `TModule` subclass registered in `application.xml`. Properties: `ConnectionID`, `EnableCache`. Initialises the `TActiveRecordManager` during application startup.

- **`TActiveRecordGateway`** — Internal bridge between `TActiveRecord` and `TDbCommandBuilder`. Generates INSERT/UPDATE/DELETE/SELECT commands from the AR metadata. Not used directly by application code.

- **`TActiveRecordCriteria`** — Extends `TSqlCriteria` with AR-specific options. Properties: `Condition`, `Parameters`, `OrdersBy`, `Limit`, `Offset`. Also supports `with()` for eager-loading named relations.

- **`TActiveRecordChangeEventParameter`** — Event parameter passed to `onBeforeSave`, `onAfterSave`, etc. Contains the record and the change type (`INSERT`, `UPDATE`, `DELETE`).

- **`TActiveRecordInvalidFinderResult`** — Enum-like: controls what `findByPk()` returns for missing records (`null` vs exception). Set via `TActiveRecordManager::setInvalidFinderResult()`.

## Defining a Record Class

```php
class PostRecord extends TActiveRecord
{
    const TABLENAME = 'posts';

    public $id;
    public $title;
    public $author_id;

    public static $RELATIONS = [
        'author'   => [self::BELONGS_TO, 'UserRecord', 'author_id'],
        'comments' => [self::HAS_MANY,   'CommentRecord'],
        'tags'     => [self::MANY_TO_MANY, 'TagRecord', 'post_tag'],
    ];

    public static function finder($class = __CLASS__)
    {
        return parent::finder($class);
    }
}
```

## Patterns & Gotchas

- **`finder()` is a static factory** — always call it on the concrete subclass to get a properly typed instance. `parent::finder(__CLASS__)` is the standard boilerplate.
- **Lazy relations** — relation properties are populated on first `__get` access; no explicit `load()` call needed.
- **`COLUMN_MAPPING`** — maps physical DB column names to PHP property names. If omitted, column names must match property names exactly.
- **`save()` vs `insert()`/`update()`** — `save()` performs an INSERT if the PK is null/empty, otherwise UPDATE. For upsert semantics, use explicit `insert()`/`update()`.
- **Composite PKs** — `findByPk(['id1' => $a, 'id2' => $b])` — pass an associative array.
- **Connection scope** — `TActiveRecordManager` uses one connection; for multi-database scenarios, configure separate manager instances.
