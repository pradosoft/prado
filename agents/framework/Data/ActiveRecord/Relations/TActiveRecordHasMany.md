# Data/ActiveRecord/Relations/TActiveRecordHasMany

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Relations](./INDEX.md) / **`TActiveRecordHasMany`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Relations/TActiveRecordHasMany.php`
**Namespace:** `Prado\Data\ActiveRecord\Relations`

## Overview
`Prado\Data\ActiveRecord\Relations\TActiveRecordHasMany`

Implements the HAS_MANY relationship between Active Records.

Inherits from [`TActiveRecordRelation`](./TActiveRecordRelation.md).

## Description

`TActiveRecordHasMany` implements the `TActiveRecord::HAS_MANY` relationship between the source object having zero or more foreign objects. Use this when the foreign key resides in the related table.

### Example Entity Relationship

```
+--------+            +--------+
|  Team  | 1 <----- * | Player |
+--------+            +--------+
```

Where one team may have zero or more players.

## Usage Example

```php
class TeamRecord extends TActiveRecord
{
    const TABLE = 'team';
    public $name;
    public $location;
    public $players = [];

    public static $RELATIONS = [
        'players' => [self::HAS_MANY, 'PlayerRecord']
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}

// Fetch teams with their players
$teams = TeamRecord::finder()->with_players()->findAll();

// With additional criteria
$teams = TeamRecord::finder()->with_players('age > ?', 25)->findAll();
```

## Key Methods

### `collectForeignObjects(&$results)`

Gets the foreign key index values from the results and finds the corresponding foreign objects.

### `getRelationForeignKeys()`

Returns foreign key field names as key and object properties as value.

### `updateAssociatedRecords()`

Updates all associated foreign objects by setting the foreign key values and saving each.

## See Also

- [TActiveRecordRelation](./TActiveRecordRelation.md)
- [TActiveRecordBelongsTo](./TActiveRecordBelongsTo.md)
- [TActiveRecordHasOne](./TActiveRecordHasOne.md)
- [TActiveRecordHasManyAssociation](./TActiveRecordHasManyAssociation.md)

## Category

ActiveRecord Relations
