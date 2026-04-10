# Data/ActiveRecord/Relations/TActiveRecordRelation

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Relations](./INDEX.md) / **`TActiveRecordRelation`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Relations/TActiveRecordRelation.php`
**Namespace:** `Prado\Data\ActiveRecord\Relations`

## Overview
`Prado\Data\ActiveRecord\Relations\TActiveRecordRelation`

Abstract base class for Active Record relationships.

## Description

`TActiveRecordRelation` is the abstract base class for all Active Record relationship types. It provides common functionality for loading and managing related records.

Each subclass handles a specific relationship type:
- `TActiveRecordBelongsTo` - Many-to-one or one-to-one with FK on source table
- `TActiveRecordHasMany` - One-to-many relationship
- `TActiveRecordHasOne` - One-to-one relationship
- `TActiveRecordHasManyAssociation` - Many-to-many via association table

## Key Methods

### `collectForeignObjects(&$results)`

Fetches the foreign objects for the given source results and populates the relationship properties.

### `getRelationForeignKeys()`

Returns foreign key field names as key and object properties as value.

### `updateAssociatedRecords()`

Updates all associated foreign objects. Must be implemented by subclasses.

### `__call($method, $args)`

Dispatches method calls to the source record finder. When results are returned, corresponding foreign objects are also fetched and assigned.

### `fetchResultsInto($obj)`

Fetches results for the current relationship.

## Properties

### Protected Properties

| Property | Type | Description |
|----------|------|-------------|
| `Context` | [`TActiveRecordRelationContext`](./TActiveRecordRelationContext.md) | The relation context |
| `Criteria` | [`TActiveRecordCriteria`](../TActiveRecordCriteria.md) | Search criteria for the relation |
| `SourceRecord` | [`TActiveRecord`](../TActiveRecord.md) | The source record being queried |

## See Also

- [TActiveRecordBelongsTo](./TActiveRecordBelongsTo.md)
- [TActiveRecordHasMany](./TActiveRecordHasMany.md)
- [TActiveRecordHasOne](./TActiveRecordHasOne.md)
- [TActiveRecordHasManyAssociation](./TActiveRecordHasManyAssociation.md)
- [TActiveRecordRelationContext](./TActiveRecordRelationContext.md)

## Category

ActiveRecord Relations
