# TActiveRecord / Active Record ORM

### Directories

[./](../INDEX.md) > [Data](../INDEX.md) > [ActiveRecord](./INDEX.md) > [TActiveRecord](./TActiveRecord.md)

**Location:** `framework/Data/ActiveRecord/`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview

Active Record ORM. Each subclass maps to one database table; instances represent individual rows. Provides static finders, lazy-loaded relations, lifecycle events, and automatic SQL generation.

## Defining a Record Class

```php
use Prado\Data\ActiveRecord\TActiveRecord;

class PostRecord extends TActiveRecord
{
    // Required:
    const TABLENAME = 'posts';

    // Public properties map to columns:
    public $id;
    public $title;
    public $body;
    public $author_id;
    public $created_at;

    // Optional: map DB column names → PHP property names
    public static $COLUMN_MAPPING = [
        'created_at' => 'createdAt',
    ];

    // Optional: lazy-loaded relations
    public static $RELATIONS = [
        // 'propName' => [type, RelatedClass, optional_fk_or_junction]
        'author'   => [self::BELONGS_TO,   'UserRecord', 'author_id'],
        'comments' => [self::HAS_MANY,     'CommentRecord'],
        'tags'     => [self::MANY_TO_MANY, 'TagRecord', 'post_tag'],
        'summary'  => [self::HAS_ONE,      'PostSummaryRecord'],
    ];

    // Required boilerplate:
    public static function finder($class = __CLASS__)
    {
        return parent::finder($class);
    }
}
```

## Finder Methods (Static via `finder()`)

```php
$finder = PostRecord::finder();

// By primary key:
$post = $finder->findByPk(1);
$posts = $finder->findAllByPks([1, 2, 3]);

// Generic find:
$post = $finder->find('title = ?', 'Hello');           // first match
$posts = $finder->findAll('active = 1');               // all matches
$posts = $finder->findAll(new TActiveRecordCriteria('active=1', [], 'created_at DESC', 10));

// SQL:
$post = $finder->findBySql('SELECT * FROM posts WHERE id = ?', [1]);
$posts = $finder->findAllBySql('SELECT * FROM posts WHERE active = 1');

// Count:
$n = $finder->count('active = 1');
```

## Persistence Methods (Instance)

```php
$post = new PostRecord();
$post->title = 'Hello World';
$post->save();      // INSERT (PK is null/new) or UPDATE (PK exists)

$post->insert();    // explicit INSERT
$post->update();    // explicit UPDATE
$post->delete();    // DELETE this record

// Load by PK into existing instance:
$post->findByPk(5);
```

## Relations (Lazy-loaded)

```php
$post = PostRecord::finder()->findByPk(1);
echo $post->author->Name;   // lazy loads UserRecord on first access
$comments = $post->comments; // returns array of CommentRecord
```

Relation types:
| Constant | Meaning |
|----------|---------|
| `TActiveRecord::HAS_ONE` | One child record (FK in child table) |
| `TActiveRecord::HAS_MANY` | Many child records (FK in child table) |
| `TActiveRecord::BELONGS_TO` | Parent record (FK in this table) |
| `TActiveRecord::MANY_TO_MANY` | Junction table (`[type, class, junction_table]`) |

## Lifecycle Events

| Event | Trigger |
|-------|---------|
| `onBeforeSave` | Before any save (insert or update) |
| `onAfterSave` | After any save |
| `onBeforeDelete` | Before delete |
| `onAfterDelete` | After delete |

```php
public function onBeforeSave($param)
{
    $this->created_at = date('Y-m-d H:i:s');
    parent::onBeforeSave($param);
}
```

## TActiveRecordCriteria

[`TActiveRecordCriteria`](./TActiveRecordCriteria.md) class:
$criteria = new TActiveRecordCriteria();
$criteria->Condition = 'active = :active AND category_id = :cat';
$criteria->Parameters = [':active' => 1, ':cat' => 5];
$criteria->OrdersBy = ['created_at' => 'DESC'];
$criteria->Limit = 20;
$criteria->Offset = 0;

// Shorthand constructor:
$criteria = new TActiveRecordCriteria('active = 1', [], 'title ASC', 10, 0);
```

## TActiveRecordManager

Singleton registry. Configure via [`TActiveRecordConfig`](./TActiveRecordConfig.md) in `application.xml`:
```xml
<module id="ar" class="Prado\Data\ActiveRecord\TActiveRecordConfig"
        ConnectionID="db" EnableCache="true" />
```

Or programmatically:
```php
TActiveRecordManager::getInstance()->setDbConnection($conn);
```

## Patterns & Gotchas

- **`finder()` boilerplate is required** — `parent::finder(__CLASS__)` ensures the correct class is returned.
- **`COLUMN_MAPPING`** maps physical DB column names → PHP property names. Without it, property names must exactly match column names.
- **`save()` insert-or-update** — detects insert vs update by whether the PK property is `null`. For composite PKs or explicit control, use `insert()`/`update()`.
- **Composite PKs** — `findByPk(['col1' => $a, 'col2' => $b])`.
- **Lazy relations** — loaded on first `__get` access; no explicit load call needed.
- **Connection scope** — `TActiveRecordManager` uses one shared connection; for multi-database, configure separate manager instances.
- **`TActiveRecordGateway`** handles internal SQL generation via [`TDbCommandBuilder`](../Common/TDbCommandBuilder.md) — don't use it directly.
