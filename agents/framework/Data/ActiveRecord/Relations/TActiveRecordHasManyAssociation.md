# TActiveRecordHasManyAssociation

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Relations](./INDEX.md) > [TActiveRecordHasManyAssociation](./TActiveRecordHasManyAssociation.md)

`Prado\Data\ActiveRecord\Relations\TActiveRecordHasManyAssociation`

Implements the M-N (many-to-many) relationship via an association table.

Inherits from [`TActiveRecordRelation`](./TActiveRecordRelation.md).

## Description

`TActiveRecordHasManyAssociation` implements the M-N (many-to-many) relationship via an association table. This is used when records can have multiple related records on both sides.

### Example Entity Relationship

```
+---------+            +------------------+            +----------+
| Article | * -----> * | Article_Category | * <----- * | Category |
+---------+            +------------------+            +----------+
```

## Usage Example

```php
class ArticleRecord extends TActiveRecord
{
    const TABLE = 'Article';
    public $article_id;
    public $Categories = [];

    public static $RELATIONS = [
        'Categories' => [self::MANY_TO_MANY, 'CategoryRecord', 'Article_Category']
    ];

    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
}

class CategoryRecord extends TActiveRecord
{
    const TABLE = 'Category';
    public $category_id;
    public $Articles = [];

    public static $RELATIONS = [
        'Articles' => [self::MANY_TO_MANY, 'ArticleRecord', 'Article_Category']
    ];
}

// Fetch articles with categories
$articles = ArticleRecord::finder()->withCategories()->findAll();
```

## Key Methods

### `collectForeignObjects(&$results)`

Gets the foreign key index values and fetches related objects via the association table.

### `getRelationForeignKeys()`

Returns two arrays: source keys and foreign keys from the association table.

### `getAssociationTable()`

Returns the [`TDbTableInfo`](../Common/TDbTableInfo.md) for the association/junction table.

### `updateAssociatedRecords()`

Updates associated foreign objects and maintains the association table entries.

## Association Table Structure

The association table (e.g., `Article_Category`) typically contains:
- Foreign key to source table (e.g., `article_id`)
- Foreign key to target table (e.g., `category_id`)

## See Also

- [TActiveRecordRelation](./TActiveRecordRelation.md)
- [TActiveRecordBelongsTo](./TActiveRecordBelongsTo.md)
- [TActiveRecordHasMany](./TActiveRecordHasMany.md)
- [TActiveRecordHasOne](./TActiveRecordHasOne.md)

## Category

ActiveRecord Relations
