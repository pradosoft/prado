# Data/ActiveRecord/Relations/TActiveRecordHasOne

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [ActiveRecord](../INDEX.md) / [Relations](./INDEX.md) / **`TActiveRecordHasOne`**

## Class Info
**Location:** `framework/Data/ActiveRecord/Relations/TActiveRecordHasOne.php`
**Namespace:** `Prado\Data\ActiveRecord\Relations`

## Overview
`Prado\Data\ActiveRecord\Relations\TActiveRecordHasOne`

Implements the HAS_ONE relationship between Active Records.

Inherits from [`TActiveRecordRelation`](./TActiveRecordRelation.md).

## Description

`TActiveRecordHasOne` models the object relationship where a record's property is an instance of a foreign record object having a foreign key related to the source object. The HAS_ONE relation is similar to HAS_MANY, but returns a single record instead of a collection.

### Example Entity Relationship

```
+-----+            +--------+
| Car | 1 <----- 1 | Engine |
+-----+            +--------+
```

Where each engine belongs to only one car.

## Usage Example

```php
class CarRecord extends TActiveRecord
{
    const TABLE = 'car';
    public $car_id;
    public $colour;
    public $engine; // engine foreign object

    public static $RELATIONS = [
        'engine' => [self::HAS_ONE, 'EngineRecord']
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}

class EngineRecord extends TActiveRecord
{
    const TABLE = 'engine';
    public $engine_id;
    public $capacity;
    public $car_id; // foreign key to cars
}

// Fetch cars with their engines
$cars = CarRecord::finder()->with_engine()->findAll();
```

## Key Methods

### `collectForeignObjects(&$results)`

Gets the foreign key index values from the results and finds the corresponding foreign object.

### `getRelationForeignKeys()`

Returns foreign key field names as key and object properties as value.

### `updateAssociatedRecords()`

Updates the associated foreign object by setting the foreign key value and saving.

## Differences from HAS_MANY

| HAS_ONE | HAS_MANY |
|---------|----------|
| Returns single record | Returns array of records |
| Property is a single object | Property is an array |
| Similar FK arrangement | Similar FK arrangement |

## See Also

- [TActiveRecordRelation](./TActiveRecordRelation.md)
- [TActiveRecordBelongsTo](./TActiveRecordBelongsTo.md)
- [TActiveRecordHasMany](./TActiveRecordHasMany.md)
- [TActiveRecordHasManyAssociation](./TActiveRecordHasManyAssociation.md)

## Category

ActiveRecord Relations
