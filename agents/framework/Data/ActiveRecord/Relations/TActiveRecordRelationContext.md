# Data/ActiveRecord/Relations/TActiveRecordRelationContext

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Relations](./INDEX.md) / **`TActiveRecordRelationContext`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Relations/TActiveRecordRelationContext.php`
**Namespace:** `Prado\Data\ActiveRecord\Relations`

## Overview
`Prado\Data\ActiveRecord\Relations\TActiveRecordRelationContext`

Holds metadata about Active Record relationships.

## Description

`TActiveRecordRelationContext` holds information regarding record relationships such as the record relation property name, query criteria, and foreign object record class names. This class is used internally by passing a context to the `TActiveRecordRelation` constructor.

## Key Methods

### `getProperty()`

Returns the name of the record property that the relationship results will be assigned to.

### `getSourceRecord()`

Returns the active record instance that queried for its related records.

### `getForeignRecordClass()`

Returns the foreign record class name.

### `getForeignRecordFinder()`

Returns the corresponding relationship foreign object finder instance.

### `getRelationHandler($criteria = null)`

Creates and returns the `TActiveRecordRelation` handler for specific relationships. Returns an instance of `TActiveRecordHasOne`, `TActiveRecordBelongsTo`, `TActiveRecordHasMany`, or `TActiveRecordHasManyAssociation`.

### `getRelationType()`

Returns the relation type: `HAS_MANY`, `HAS_ONE`, `BELONGS_TO`, or `MANY_TO_MANY`.

### `getAssociationTable()`

Returns the M-N relationship association table name.

### `updateAssociatedRecords($updateBelongsTo = false)`

Updates associated records for all relations.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Property` | `string` | The property name for relation results |
| `SourceRecord` | [`TActiveRecord`](../TActiveRecord.md) | The source record instance |
| `Relation` | `array` | The relation definition from `$RELATIONS` |

## See Also

- [TActiveRecordRelation](./TActiveRecordRelation.md)
- [TActiveRecord](./TActiveRecord.md)

## Category

ActiveRecord Relations
