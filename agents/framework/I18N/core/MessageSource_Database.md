# I18N / core / MessageSource_Database

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [MessageSource_Database](./MessageSource_Database.md)

**Location:** `framework/I18N/core/MessageSource_Database.php`
**Namespace:** `Prado\I18N\core`

## Overview

Database-backed message source. Stores translations in two tables: `catalogue` (metadata) and `trans_unit` (translation entries).

## Database Schema

```sql
CREATE TABLE catalogue (
    cat_id INTEGER PRIMARY KEY,
    name VARCHAR(255),      -- e.g., 'messages.en_US'
    date_modified INTEGER
);

CREATE TABLE trans_unit (
    id INTEGER,
    cat_id INTEGER,
    source VARCHAR(255),     -- original text
    target VARCHAR(255),    -- translated text
    comments TEXT,
    date_added INTEGER,
    date_modified INTEGER
);
```

## Usage

```php
// application.xml
<module id="db1" class="Prado\Data\TDbConnection" ... />

// Get messages
$source = MessageSource::factory('Database', 'db1');
$source->setCulture('en_US');
$source->setCache(new MessageCache('/tmp'));

$formatter = new MessageFormat($source);
echo $formatter->format('Hello');
```

## Key Methods

### `getDbConnection(): TDbConnection`

Get the database connection.

### `loadData($variant): array`

Load translations for a catalogue+variant.

### `save($catalogue = 'messages'): bool`

Save untranslated messages to database.

### `update($text, $target, $comments, $catalogue = 'messages'): bool`

Update a single translation.

### `delete($message, $catalogue = 'messages'): bool`

Delete a translation.

## See Also

- [MessageSource](./MessageSource.md) - Abstract base class
- [MessageSource](./MessageSource.md)::factory() - Factory method