# TActiveRecordBelongsTo

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Relations](./INDEX.md) > [TActiveRecordBelongsTo](./TActiveRecordBelongsTo.md)

`Prado\Data\ActiveRecord\Relations\TActiveRecordBelongsTo`

Implements the BELONGS_TO relationship between Active Records.

Inherits from [`TActiveRecordRelation`](./TActiveRecordRelation.md).

## Description

`TActiveRecordBelongsTo` implements the `TActiveRecord::BELONGS_TO` relationship between the source objects and the related foreign object. Use this when the foreign key resides in the source table.

### Example Entity Relationship

```
+--------+            +--------+
|  Team  | 1 <----- * | Player |
+--------+            +--------+
```

Where each player belongs to only one team.

## Usage Example

```php
class PlayerRecord extends TActiveRecord
{
    const TABLE = 'player';
    public $player_id;
    public $team_name; // foreign key player.team_name <-> team.name
    public $team; // foreign object TeamRecord

    public static $RELATIONS = [
        'team' => [self::BELONGS_TO, 'TeamRecord']
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}

// Fetch players with their teams
$players = PlayerRecord::finder()->with_team()->findAll();
```

## Key Methods

### `collectForeignObjects(&$results)`

Gets the foreign key index values from the results and finds the corresponding foreign objects.

### `getRelationForeignKeys()`

Returns foreign key field names as key and object properties as value.

### `updateAssociatedRecords()`

Updates the source object first, then saves the foreign object.

## See Also

- [TActiveRecordRelation](./TActiveRecordRelation.md)
- [TActiveRecordHasMany](./TActiveRecordHasMany.md)
- [TActiveRecordHasOne](./TActiveRecordHasOne.md)
- [TActiveRecordHasManyAssociation](./TActiveRecordHasManyAssociation.md)

## Category

ActiveRecord Relations
