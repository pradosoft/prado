# Data/ActiveRecord/Relations/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md)] | ActiveRecord Directory |

## Purpose

Relationship implementations for the Active Record ORM — HasOne, HasMany, BelongsTo, and many-to-many association loading.

## Classes

- **`TActiveRecordRelation`** — Abstract base class for all relation types. Holds a `TActiveRecordRelationContext` and a criteria object. Subclasses implement `getRelationForeignObject()` / `updateAssociationTable()`.

- **`TActiveRecordRelationContext`** — Carries metadata about a relation: the source record, the property being populated, and the relation definition from `$RELATIONS`. Used by all relation classes to know what to load and where to put it.

- **`TActiveRecordHasOne`** — Loads a single associated record where the foreign key lives in the related table (one-to-one, FK on the other side).

- **`TActiveRecordHasMany`** — Loads a collection of related records where the FK is on the related table's side (one-to-many).

- **`TActiveRecordHasManyAssociation`** — Loads a many-to-many collection via an intermediate join/association table. Handles `insert`/`delete` on the join table.

- **`TActiveRecordBelongsTo`** — Loads the "parent" record where the FK lives on the source table (many-to-one or one-to-one, FK on this side).

## Defining Relations

In an AR class declare a `$RELATIONS` static array:

```php
public static $RELATIONS = [
    'author'   => [self::BELONGS_TO,  'UserRecord'],
    'comments' => [self::HAS_MANY,    'CommentRecord'],
    'tags'     => [self::MANY_TO_MANY,'TagRecord', 'post_tag'],
];
```

Constants: `TActiveRecord::HAS_ONE`, `HAS_MANY`, `BELONGS_TO`, `MANY_TO_MANY`.

## Patterns & Gotchas

- Relations are **lazy-loaded** on first property access via `__get` in `TActiveRecord`.
- Pass a `TActiveRecordCriteria` as the third element of a relation definition to add ordering/limiting.
- For `MANY_TO_MANY`, the third element is the join-table name; the fourth (optional) is a criteria.
- Circular relations across two AR classes require careful ordering to avoid infinite recursion during eager loading.
