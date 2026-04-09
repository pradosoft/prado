# Data.ActiveRecord Manual

This manual documents the complete Data.ActiveRecord component for the Prado framework. It provides comprehensive documentation of all classes, features, functionality, relationships, events, and integration patterns within the PRADO ecosystem. The ActiveRecord pattern provides an object-oriented approach to database operations where each record is represented as an object that encapsulates both data and behavior.

## Table of Contents

- [Chapter 1: Introduction](#chapter-1-introduction)
  - [1.1 What is ActiveRecord?](#11-what-is-activerecord)
  - [1.2 Purpose and Scope](#12-purpose-and-scope)
  - [1.3 Key Concepts](#13-key-concepts)
- [Chapter 2: Architecture Overview](#chapter-2-architecture-overview)
  - [2.1 Component Hierarchy](#21-component-hierarchy)
  - [2.2 Class Relationships](#22-class-relationships)
  - [2.3 Request Flow](#23-request-flow)
- [Chapter 3: TActiveRecord Class](#chapter-3-tactiverecord-class)
  - [3.1 Record Definition](#31-record-definition)
  - [3.2 Table Name Configuration](#32-table-name-configuration)
  - [3.3 Column Mapping](#33-column-mapping)
  - [3.4 Record State Management](#34-record-state-management)
  - [3.5 Save Operations](#35-save-operations)
  - [3.6 Delete Operations](#36-delete-operations)
- [Chapter 4: Finder Methods](#chapter-4-finder-methods)
  - [4.1 Basic Find Operations](#41-basic-find-operations)
  - [4.2 Find by Primary Key](#42-find-by-primary-key)
  - [4.3 Find All Operations](#43-find-all-operations)
  - [4.4 Find with SQL](#44-find-with-sql)
  - [4.5 Count Operations](#45-count-operations)
  - [4.6 Dynamic Finder Methods](#46-dynamic-finder-methods)
  - [4.7 Index-Based Find Operations](#47-index-based-find-operations)
- [Chapter 5: Relationships](#chapter-5-relationships)
  - [5.1 Relationship Types](#51-relationship-types)
  - [5.2 BELONGS_TO Relationship](#52-belongs_to-relationship)
  - [5.3 HAS_ONE Relationship](#53-has_one-relationship)
  - [5.4 HAS_MANY Relationship](#54-has_many-relationship)
  - [5.5 MANY_TO_MANY Relationship](#55-many_to_many-relationship)
  - [5.6 Lazy Loading](#56-lazy-loading)
  - [5.7 Eager Loading with "with"](#57-eager-loading-with-with)
- [Chapter 6: Events and Life Cycle](#chapter-6-events-and-life-cycle)
  - [6.1 OnInsert Event](#61-oninsert-event)
  - [6.2 OnUpdate Event](#62-onupdate-event)
  - [6.3 OnDelete Event](#63-ondelete-event)
  - [6.4 OnCreateCommand Event](#64-oncreatecommand-event)
  - [6.5 OnExecuteCommand Event](#65-onexecutecommand-event)
- [Chapter 7: TActiveRecordCriteria Class](#chapter-7-tactiverecordcriteria-class)
  - [7.1 Overview](#71-overview)
  - [7.2 Condition and Parameters](#72-condition-and-parameters)
  - [7.3 Ordering and Pagination](#73-ordering-and-pagination)
- [Chapter 8: TActiveRecordManager Class](#chapter-8-tactiverecordmanager-class)
  - [8.1 Overview](#81-overview)
  - [8.2 Connection Management](#82-connection-management)
  - [8.3 Gateway Configuration](#83-gateway-configuration)
  - [8.4 Cache Configuration](#84-cache-configuration)
  - [8.5 Invalid Finder Result Handling](#85-invalid-finder-result-handling)
- [Chapter 9: TActiveRecordGateway Class](#chapter-9-tactiverecordgateway-class)
  - [9.1 Overview](#91-overview)
  - [9.2 Command Generation](#92-command-generation)
  - [9.3 Insert Operations](#93-insert-operations)
  - [9.4 Update Operations](#94-update-operations)
  - [9.5 Delete Operations](#95-delete-operations)
- [Chapter 10: Integration with PRADO](#chapter-10-integration-with-prado)
  - [10.1 Module Configuration](#101-module-configuration)
  - [10.2 Connection Management](#102-connection-management)
  - [10.3 Service Integration](#103-service-integration)
- [Chapter 11: Complete Usage Examples](#chapter-11-complete-usage-examples)
  - [11.1 Basic CRUD Operations](#111-basic-crud-operations)
  - [11.2 Complex Relationships](#112-complex-relationships)
  - [11.3 Transaction Management](#113-transaction-management)
  - [11.4 Event-Driven Logic](#114-event-driven-logic)
  - [11.5 Dynamic Finders](#115-dynamic-finders)
- [Chapter 12: Best Practices](#chapter-12-best-practices)
  - [12.1 Performance Considerations](#121-performance-considerations)
  - [12.2 Security Guidelines](#122-security-guidelines)
  - [12.3 Error Handling](#123-error-handling)
  - [12.4 Code Organization](#124-code-organization)
- [Chapter 13: Troubleshooting](#chapter-13-troubleshooting)
  - [13.1 Common Issues](#131-common-issues)
  - [13.2 Debugging Techniques](#132-debugging-techniques)
- [Appendix A: Class Reference Summary](#appendix-a-class-reference-summary)
- [Appendix B: Change Log](#appendix-b-change-log)

---

## Chapter 1: Introduction

### 1.1 What is ActiveRecord?

Data.ActiveRecord implements the Active Record pattern where each database record is wrapped in an object. This differs from the Table Gateway pattern (used by Data.DataGateway) in that ActiveRecord objects maintain state and encapsulate both data and behavior related to that data.

An ActiveRecord object encapsulates:

- Data values corresponding to database columns
- Business logic and validation
- Persistence operations (save, delete, update)
- Relationship navigation to related records

The ActiveRecord pattern is particularly useful when:

- Domain objects closely mirror database schema
- You need rich domain logic attached to records
- Objects need to maintain state across operations
- You want an object-oriented interface to database operations

### 1.2 Purpose and Scope

The primary purposes of Data.ActiveRecord are:

- **Object-Relational Mapping**: Map database tables to PHP classes
- **Encapsulated Persistence**: Each object knows how to save itself
- **Relationship Navigation**: Access related objects through properties
- **Domain Logic Integration**: Add business rules directly to record classes
- **Event-Driven Hooks**: Intercept save/update/delete operations

The scope encompasses:

- Single-record CRUD operations
- Complex relationship handling (belongs_to, has_one, has_many, many_to_many)
- Dynamic query methods based on method names
- Life cycle events for validation and business logic
- Integration with the broader Prado Data layer

### 1.3 Key Concepts

**Active Record Pattern**: A design pattern where each record is an object that wraps a database row, encapsulating data access and domain logic.

**Record State**: Active records maintain internal state (NEW, LOADED, DELETED) that determines behavior during save and delete operations.

**Finder Objects**: Static instances obtained via the `finder()` method that return new record objects when querying.

**Column Mapping**: A mechanism to map physical database column names to logical property names in the active record class.

**Relationship Definitions**: Static declarations that establish relationships between different active record classes.

**Lazy Loading**: Related objects are loaded on-demand when accessed, not when the parent object is loaded.

---

## Chapter 2: Architecture Overview

### 2.1 Component Hierarchy

The Data.ActiveRecord layer consists of these primary classes:

**TActiveRecord** serves as the base class for all active record classes. It provides:

- Object property access to database columns
- Save, delete, and update operations
- Dynamic finder methods via `__call`
- Relationship property access via `__get` and `__set`
- Life cycle event methods

**TActiveRecordManager** provides:

- Default database connection management
- Singleton instance access
- Gateway and cache configuration
- Invalid finder result handling

**TActiveRecordGateway** handles:

- Command generation for CRUD operations
- Criteria to SQL translation
- Event raising for command lifecycle
- Post-insert ID updates

**TActiveRecordCriteria** encapsulates:

- SQL WHERE conditions
- Parameter bindings
- Ordering specifications
- Pagination (limit and offset)

### 2.2 Class Relationships

```
TActiveRecord (base class for user records)
    |
    +-- finder() --> returns static finder instance
    +-- getRecordGateway() --> TActiveRecordGateway
    |       |
    |       +-- Uses TDbCommandBuilder (from Data.Common)
    |
    +-- TActiveRecordManager (singleton)
            |
            +-- setDbConnection() / getDbConnection()
            +-- getRecordGateway()
            +-- setCache()
```

**Relationship Classes**:

```
TActiveRecordRelation (abstract base)
    |
    +-- TActiveRecordBelongsTo
    +-- TActiveRecordHasOne
    +-- TActiveRecordHasMany
    +-- TActiveRecordHasManyAssociation

TActiveRecordRelationContext
    |
    +-- Manages relationship fetching and object population
```

### 2.3 Request Flow

**Save Operation Flow**:

```
$record->save()
    |
    v
TActiveRecord::save()
    |
    v
Check record state (NEW or LOADED)
    |
    v
Raise OnInsert or OnUpdate event
    |
    v
TActiveRecordGateway::insert() or update()
    |
    v
Create TDbCommand via TDbCommandBuilder
    |
    v
Bind parameters using column values
    |
    v
Execute command
    |
    v
Raise OnCreateCommand / OnExecuteCommand events
    |
    v
For insert: update post-insert ID
    |
    v
Update record state
```

**Find Operation Flow**:

```
UserRecord::finder()->findByPk($pk)
    |
    v
TActiveRecord::finder() returns static finder
    |
    v
findByPk() calls getRecordGateway()
    |
    v
TActiveRecordGateway::findRecordByPK()
    |
    v
Creates TDbCommand with primary key condition
    |
    v
Executes query
    |
    v
Returns array data
    |
    v
TActiveRecord::populateObject() creates instance
    |
    v
Sets record state to LOADED
```

---

## Chapter 3: TActiveRecord Class

### 3.1 Record Definition

To define an active record class, extend `TActiveRecord` and declare public properties for each column:

```php
use Prado\Data\ActiveRecord\TActiveRecord;

class UserRecord extends TActiveRecord
{
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $created_at;
    public $status;
}
```

Each public property corresponds to a column in the database table. The property name should match the column name exactly, or you can use column mapping for different names.

### 3.2 Table Name Configuration

By default, the table name is derived from the class name. You can override this in three ways:

**Option 1: TABLE Constant**:

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';
    
    public $id;
    public $username;
    public $email;
}
```

**Option 2: table() Method**:

```php
class UserRecord extends TActiveRecord
{
    public function table()
    {
        return 'users';
    }
    
    public $id;
    public $username;
    public $email;
}
```

**Option 3: Default (class name)**:

If neither constant nor method is provided, the class name is used as the table name, converted to lowercase.

### 3.3 Column Mapping

Column mapping allows you to use different names in your PHP class than in the database:

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';
    
    // Physical column => Logical name
    public static $COLUMN_MAPPING = [
        'user_id' => 'id',
        'email_address' => 'email',
        'password_hash' => 'password',
    ];
    
    // Use these logical names as properties
    public $id;
    public $email;
    public $password;
}
```

With this mapping:

- The `id` property reads/writes the `user_id` column
- The `email` property reads/writes the `email_address` column
- The `password` property reads/writes the `password_hash` column

### 3.4 Record State Management

Active records maintain internal state:

```php
TActiveRecord::STATE_NEW      // 0 - Created but not saved
TActiveRecord::STATE_LOADED  // 1 - Loaded from DB or successfully saved
TActiveRecord::STATE_DELETED // 2 - Deleted from DB, cannot save again
```

**State Transitions**:

```
new UserRecord() --> STATE_NEW
    |
    v (save())
STATE_NEW --> STATE_LOADED (after successful insert)

finder()->findByPk() --> STATE_LOADED
    |
    v (delete())
STATE_LOADED --> STATE_DELETED (after successful delete)
```

**Checking State**:

```php
if ($record->_recordState === TActiveRecord::STATE_NEW) {
    // New record, will be inserted
} elseif ($record->_recordState === TActiveRecord::STATE_LOADED) {
    // Existing record, will be updated
}
```

### 3.5 Save Operations

The `save()` method intelligently determines whether to insert or update:

```php
public function save()
{
    $gateway = $this->getRecordGateway();
    $param = new TActiveRecordChangeEventParameter();
    
    if ($this->_recordState === self::STATE_NEW) {
        $this->onInsert($param);
        if ($param->getIsValid() && $gateway->insert($this)) {
            $this->_recordState = self::STATE_LOADED;
            return true;
        }
    } elseif ($this->_recordState === self::STATE_LOADED) {
        $this->onUpdate($param);
        if ($param->getIsValid() && $gateway->update($this)) {
            return true;
        }
    } else {
        throw new TActiveRecordException('ar_save_invalid');
    }
    
    return false;
}
```

**Save Examples**:

```php
// Create and save a new record
$user = new UserRecord();
$user->username = 'alice';
$user->email = 'alice@example.com';
$user->password_hash = password_hash('secret');
$user->created_at = date('Y-m-d H:i:s');
$user->status = 'active';
$user->save();

// Load, modify, and save
$user = UserRecord::finder()->findByPk(1);
$user->email = 'newemail@example.com';
$user->save();
```

### 3.6 Delete Operations

**Instance Delete**:

```php
$user = UserRecord::finder()->findByPk(1);
$user->delete();
// Now $user is in STATE_DELETED
```

**Static Delete by Primary Key**:

```php
// Delete single record
UserRecord::finder()->deleteByPk(1);

// Delete multiple records
UserRecord::finder()->deleteByPk(1, 2, 3, 4);

// With composite keys
UserRecord::finder()->deleteByPk([$key1, $key2]);
```

**Delete with Criteria**:

```php
// Delete all matching records
$deleted = UserRecord::finder()->deleteAll('status = ?', ['inactive']);

// With named parameters
$deleted = UserRecord::finder()->deleteAll('last_login < ?', ['2024-01-01']);
```

---

## Chapter 4: Finder Methods

### 4.1 Basic Find Operations

**find() - Single Record**:

```php
// With positional parameters
$user = UserRecord::finder()->find('username = ? AND status = ?', ['admin', 'active']);

// With named parameters
$user = UserRecord::finder()->find('username = :name AND status = :status', [':name' => 'admin', ':status' => 'active']);

// With individual arguments
$user = UserRecord::finder()->find('username = ? AND status = ?', 'admin', 'active');

// With TActiveRecordCriteria
$criteria = new TActiveRecordCriteria('username = ? AND status = ?', ['admin', 'active']);
$user = UserRecord::finder()->find($criteria);
```

### 4.2 Find by Primary Key

```php
// Single primary key
$user = UserRecord::finder()->findByPk(1);

// Multiple arguments for composite keys
$record = UserRecord::finder()->findByPk($key1, $key2, $key3);

// As an array
$record = UserRecord::finder()->findByPk([$key1, $key2]);
```

### 4.3 Find All Operations

**findAll()**:

```php
// Find all records
$users = UserRecord::finder()->findAll();

// Find with condition
$activeUsers = UserRecord::finder()->findAll('status = ?', ['active']);

// Find with ordering
$sortedUsers = UserRecord::finder()->findAll(
    '1=1',
    [],  // no parameters
    ['created_at' => 'DESC']
);

// Find with pagination
$page = UserRecord::finder()->findAll(
    'status = ?',
    ['active'],
    ['name' => 'ASC'],
    10,  // limit
    20   // offset
);
```

**findAllByPks()**:

```php
// Multiple single-key records
$users = UserRecord::finder()->findAllByPks(1, 2, 3, 4);

// As array of keys
$users = UserRecord::finder()->findAllByPks([1, 2, 3, 4]);

// Composite keys
$users = UserRecord::finder()->findAllByPks(
    [$key1, $key2],
    [$key3, $key4]
);
```

### 4.4 Find with SQL

**findBySql() - Single Record**:

```php
$user = UserRecord::finder()->findBySql(
    'SELECT * FROM users WHERE username = ? AND password = ?',
    [$username, $password]
);
```

**findAllBySql() - Multiple Records**:

```php
$users = UserRecord::finder()->findAllBySql(
    'SELECT * FROM users WHERE status = ? ORDER BY created_at DESC LIMIT 10',
    ['active']
);
```

### 4.5 Count Operations

```php
// Count all
$total = UserRecord::finder()->count();

// Count with condition
$activeCount = UserRecord::finder()->count('status = ?', ['active']);

// Count with TActiveRecordCriteria
$criteria = new TActiveRecordCriteria();
$criteria->setCondition('level > ?');
$criteria->setParameters([5]);
$count = UserRecord::finder()->count($criteria);
```

### 4.6 Dynamic Finder Methods

Dynamic finders automatically construct query conditions from method names.

**Basic Dynamic Finders**:

```php
// findByColumnName($value) - WHERE column = ?
$user = UserRecord::finder()->findByUsername('admin');

// findAllByColumnName($value) - returns array
$users = UserRecord::finder()->findAllByStatus('active');

// Multiple conditions
$user = UserRecord::finder()->findByEmailAndStatus('admin@example.com', 'active');
// Becomes: WHERE email = ? AND status = ?
```

**Underscore Syntax for AND/OR**:

```php
// Use underscores for explicit control
$user = UserRecord::finder()->findBy_Username_Or_Status_('admin', 'active');
// Becomes: WHERE username = ? OR status = ?

// AND is default between conditions
$user = UserRecord::finder()->findByEmailAndStatusAndLevel('admin@example.com', 'active', 5);
// Becomes: WHERE email = ? AND status = ? AND level = ?
```

**Dynamic Delete Methods**:

```php
// deleteByColumnName($value)
UserRecord::finder()->deleteByStatus('inactive');

// deleteAllByColumnName($value) - alias
UserRecord::finder()->deleteAllByStatus('deleted');
```

**Invalid Finder Result Handling**:

By default, invalid finder method names return null. You can configure different behavior:

```php
// In your ActiveRecord class
public function setInvalidFinderResult($value)
{
    // Options: TActiveRecordInvalidFinderResult::Null (default)
    //          TActiveRecordInvalidFinderResult::Exception
    $this->_invalidFinderResult = $value;
}
```

### 4.47 Index-Based Find Operations

**findAllByIndex()**:

```php
$criteria = new TActiveRecordCriteria();
$records = UserRecord::finder()->findAllByIndex(
    $criteria,
    ['department_id', 'status'],  // fields to match
    [$deptId, 'active']           // values to match
);
```

---

## Chapter 5: Relationships

### 5.1 Relationship Types

ActiveRecord supports four relationship types defined in the static `$RELATIONS` array:

```php
public static $RELATIONS = [
    'relationName' => [relationship_type, 'RelatedRecordClass', 'foreign_key_column'],
];
```

- `self::BELONGS_TO` - This record has a foreign key referencing another table
- `self::HAS_ONE` - Another record has a foreign key referencing this record (one-to-one)
- `self::HAS_MANY` - Another record has a foreign key referencing this record (one-to-many)
- `self::MANY_TO_MANY` - Uses a junction table for many-to-many relationships

### 5.2 BELONGS_TO Relationship

A record belongs to another record when it has a foreign key pointing to that record.

```php
class PostRecord extends TActiveRecord
{
    const TABLE = 'posts';
    
    public $id;
    public $title;
    public $content;
    public $author_id;  // foreign key
    public $created_at;
    
    public static $RELATIONS = [
        'author' => [self::BELONGS_TO, 'UserRecord', 'author_id'],
    ];
    
    // After defining relations, access related object via property
    public $author;  // This will be lazy-loaded
}
```

**Usage**:

```php
$post = PostRecord::finder()->findByPk(1);
echo $post->title;
echo $post->author->username;  // Lazy loads the UserRecord
```

### 5.3 HAS_ONE Relationship

A record has one related record that references it.

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';
    
    public $id;
    public $username;
    public $email;
    
    public static $RELATIONS = [
        'profile' => [self::HAS_ONE, 'ProfileRecord', 'user_id'],
    ];
    
    public $profile;  // Lazy loaded
}
```

**Usage**:

```php
$user = UserRecord::finder()->findByPk(1);
echo $user->username;
echo $user->profile->bio;  // Lazy loads the ProfileRecord
```

### 5.4 HAS_MANY Relationship

A record has many related records that reference it.

```php
class UserRecord extends TActiveRecord
{
    const TABLE = 'users';
    
    public $id;
    public $username;
    
    public static $RELATIONS = [
        'posts' => [self::HAS_MANY, 'PostRecord', 'author_id'],
    ];
    
    public $posts;  // Array of PostRecord objects
}
```

**Usage**:

```php
$user = UserRecord::finder()->findByPk(1);
foreach ($user->posts as $post) {
    echo $post->title;
}
```

### 5.5 MANY_TO_MANY Relationship

Many-to-many relationships use a junction table.

```php
class PostRecord extends TActiveRecord
{
    const TABLE = 'posts';
    
    public $id;
    public $title;
    
    public static $RELATIONS = [
        'tags' => [self::MANY_TO_MANY, 'TagRecord', 'post_tags', 'post_id', 'tag_id'],
    ];
}
```

### 5.6 Lazy Loading

By default, related objects are loaded lazily when accessed:

```php
$post = PostRecord::finder()->findByPk(1);
// At this point, the 'author' is NOT loaded

echo $post->author->username;  
// Now the author is loaded from the database
```

This behavior is automatic when you access the relationship property.

### 5.7 Eager Loading with "with"

Use the `with` prefix to eagerly load relationships:

```php
// withPropertyName(args)
$post = PostRecord::finder()->withAuthor()->findByPk(1);
// Author is loaded in the same query or with minimal additional queries

// Chain multiple relationships
$post = PostRecord::finder()->withAuthor()->withComments()->findByPk(1);
```

**Implementation**:

```php
// Given a TeamRecord with players relationship
class TeamRecord extends TActiveRecord
{
    public static $RELATIONS = [
        'players' => [self::HAS_MANY, 'PlayerRecord', 'team_id'],
    ];
}

// Use withPlayers() to eager load
$team = TeamRecord::finder()->withPlayers()->findByPk(1);
foreach ($team->players as $player) {
    echo $player->name;
}
```

---

## Chapter 6: Events and Life Cycle

ActiveRecord provides events that fire at key points during record operations. These events allow you to implement validation, auto-population, and business logic.

### 6.1 OnInsert Event

Fires before a new record is inserted. Can set `IsValid` to false to prevent the insert.

```php
class UserRecord extends TActiveRecord
{
    public $id;
    public $username;
    public $email;
    public $password;
    public $created_at;
    public $nounce;
    
    protected function onInsert($param)
    {
        // Call parent to ensure event is raised properly
        parent::onInsert($param);
        
        // Hash password with nonce
        $this->nounce = md5(time());
        $this->password = md5($this->password . $this->nounce);
        
        // Set creation timestamp
        $this->created_at = date('Y-m-d H:i:s');
    }
}
```

**Preventing Insert**:

```php
protected function onInsert($param)
{
    parent::onInsert($param);
    
    if ($this->username === 'admin') {
        $param->setIsValid(false);
        throw new Exception('Cannot create admin user this way');
    }
}
```

### 6.2 OnUpdate Event

Fires before an existing record is updated.

```php
class UserRecord extends TActiveRecord
{
    protected function onUpdate($param)
    {
        parent::onUpdate($param);
        
        // Track modification
        $this->updated_at = date('Y-m-d H:i:s');
    }
}
```

### 6.3 OnDelete Event

Fires before a record is deleted.

```php
class UserRecord extends TActiveRecord
{
    protected function onDelete($param)
    {
        parent::onDelete($param);
        
        // Prevent deletion of admin users
        if ($this->username === 'admin') {
            $param->setIsValid(false);
            throw new Exception('Cannot delete admin user');
        }
    }
}
```

### 6.4 OnCreateCommand Event

Fires after a command is prepared with parameters bound, but before execution. Useful for logging.

```php
class UserRecord extends TActiveRecord
{
    protected function onCreateCommand($param)
    {
        $command = $param->getCommand();
        $criteria = $param->getCriteria();
        
        // Log the SQL
        Prado::trace('SQL: ' . $command->getText());
    }
}
```

### 6.5 OnExecuteCommand Event

Fires after a command is executed and results are available. Can modify results.

```php
class UserRecord extends TActiveRecord
{
    protected function onExecuteCommand($param)
    {
        $result = $param->getResult();
        
        if (is_array($result) && isset($result['password'])) {
            unset($result['password']);  // Remove sensitive data
            $param->setResult($result);
        }
    }
}
```

---

## Chapter 7: TActiveRecordCriteria Class

### 7.1 Overview

`TActiveRecordCriteria` extends `TSqlCriteria` and is used to encapsulate query parameters for ActiveRecord finder methods.

```php
$criteria = new TActiveRecordCriteria();
$criteria->setCondition('status = ? AND level > ?');
$criteria->setParameters(['active', 5]);
$criteria->setOrdersBy(['name' => 'ASC', 'created' => 'DESC']);
$criteria->setLimit(10);
$criteria->setOffset(20);
```

### 7.2 Condition and Parameters

```php
$criteria = new TActiveRecordCriteria();

// String condition
$criteria->setCondition('username = ?');

// With parameters
$criteria->setCondition('status = ? AND created > ?');
$criteria->setParameters(['active', '2024-01-01']);

// Or pass in constructor
$criteria = new TActiveRecordCriteria(
    'status = ? AND level > ?',
    ['active', 5]
);
```

### 7.3 Ordering and Pagination

```php
// Set ordering
$criteria->setOrdersBy(['name' => 'ASC', 'created' => 'DESC']);

// Set pagination
$criteria->setLimit(10);   // 10 records
$criteria->setOffset(20);  // Skip first 20

// Get values
$limit = $criteria->getLimit();
$offset = $criteria->getOffset();
$orders = $criteria->getOrdersBy();
```

---

## Chapter 8: TActiveRecordManager Class

### 8.1 Overview

`TActiveRecordManager` is a singleton component that provides:

- Default database connection for all active records
- Gateway instance management
- Cache configuration for metadata
- Invalid finder result handling

### 8.2 Connection Management

**Setting the Default Connection**:

```php
// Create connection
$dsn = 'mysql:host=localhost;dbname=testdb';
$conn = new TDbConnection($dsn, 'username', 'password');

// Set as default
TActiveRecordManager::getInstance()->setDbConnection($conn);
```

All active records will use this connection unless they override it.

### 8.3 Gateway Configuration

**Custom Gateway Class**:

```php
TActiveRecordManager::getInstance()->setGatewayClass('MyCustomGateway');

// Then get records use the custom gateway
$gateway = TActiveRecordManager::getInstance()->getRecordGateway();
```

### 8.4 Cache Configuration

Enable caching to improve performance by caching table metadata:

```php
// Set an ICache implementation
$cache = new TFileCache('/tmp/cache');
TActiveRecordManager::getInstance()->setCache($cache);
```

### 8.5 Invalid Finder Result Handling

Configure what happens when an invalid dynamic finder method is called:

```php
// Option 1: Return null (default)
TActiveRecordManager::getInstance()->setInvalidFinderResult(
    TActiveRecordInvalidFinderResult::Null
);

// Option 2: Throw exception
TActiveRecordManager::getInstance()->setInvalidFinderResult(
    TActiveRecordInvalidFinderResult::Exception
);
```

You can also set this per-record class:

```php
class UserRecord extends TActiveRecord
{
    public function setInvalidFinderResult($value)
    {
        $this->_invalidFinderResult = TActiveRecordInvalidFinderResult::Exception;
    }
}
```

---

## Chapter 9: TActiveRecordGateway Class

### 9.1 Overview

`TActiveRecordGateway` handles the actual command building and execution for ActiveRecord operations. It wraps `TDataGatewayCommand` and provides ActiveRecord-specific functionality.

### 9.2 Command Generation

The gateway creates commands using the table's `TDbCommandBuilder`:

```php
public function getCommand(TActiveRecord $record)
{
    $conn = $record->getDbConnection();
    $tableInfo = $this->getRecordTableInfo($record);
    
    // Create command builder for table
    $builder = $tableInfo->createCommandBuilder($conn);
    
    // Return configured TDataGatewayCommand
    $command = new TDataGatewayCommand($builder);
    
    return $command;
}
```

### 9.3 Insert Operations

The gateway handles insert operations:

```php
public function insert(TActiveRecord $record)
{
    // Get insert values from record
    $values = $this->getInsertValues($record);
    
    // Create and execute insert command
    $result = $command->insert($values);
    
    // Update post-insert ID if applicable
    if ($result) {
        $this->updatePostInsert($record);
    }
    
    return $result;
}

protected function updatePostInsert($record)
{
    $command = $this->getCommand($record);
    $tableInfo = $command->getTableInfo();
    
    foreach ($tableInfo->getColumns() as $name => $column) {
        if ($column->hasSequence()) {
            $record->setColumnValue($name, $command->getLastInsertID());
        }
    }
}
```

### 9.4 Update Operations

```php
public function update(TActiveRecord $record)
{
    // Get values and primary keys
    [$values, $keys] = $this->getUpdateValues($record);
    
    // Execute update by primary key
    return $command->updateByPk($values, $keys);
}
```

### 9.5 Delete Operations

```php
public function delete(TActiveRecord $record)
{
    // Get primary key values
    $keys = $this->getPrimaryKeyValues($record);
    
    // Delete by primary key
    return $command->deleteByPk($keys);
}
```

---

## Chapter 10: Integration with PRADO

### 10.1 Module Configuration

Create a module class for ActiveRecord management:

```php
class ActiveRecordModule extends TModule
{
    private $_manager;
    
    public function init($config)
    {
        // Create manager
        $this->_manager = new TActiveRecordManager();
        
        // Set connection
        $conn = new TDbConnection(
            'mysql:host=localhost;dbname=testdb',
            'username',
            'password'
        );
        $this->_manager->setDbConnection($conn);
        
        // Set cache if available
        if ($this->getApplication()->hasCache()) {
            $this->_manager->setCache($this->getApplication()->getCache());
        }
    }
    
    public function getManager()
    {
        return $this->_manager;
    }
}
```

**Application Configuration**:

```xml
<modules>
    <module id="ar" class="ActiveRecordModule" />
</modules>
```

### 10.2 Connection Management

**Multiple Connections**:

```php
class MultiDbModule extends TModule
{
    private $_managers = [];
    
    public function init($config)
    {
        // Default connection
        $this->_managers['default'] = $this->createManager('default');
        
        // Secondary connection
        $this->_managers['analytics'] = $this->createManager('analytics');
    }
    
    protected function createManager($name)
    {
        $manager = new TActiveRecordManager();
        $config = $this->getConfig()->getSubitem($name);
        
        $dsn = $config->getAttribute('dsn');
        $username = $config->getAttribute('username');
        $password = $config->getAttribute('password');
        
        $conn = new TDbConnection($dsn, $username, $password);
        $manager->setDbConnection($conn);
        
        return $manager;
    }
    
    public function getManager($name = 'default')
    {
        return $this->_managers[$name] ?? null;
    }
}
```

### 10.3 Service Integration

**Service Class Pattern**:

```php
class UserService
{
    private $recordClass;
    
    public function __construct($recordClass = UserRecord::class)
    {
        $this->recordClass = $recordClass;
    }
    
    public function findById($id)
    {
        return $this->recordClass::finder()->findByPk($id);
    }
    
    public function create($data)
    {
        $record = new $this->recordClass();
        $record->copyFrom($data);
        $record->save();
        return $record;
    }
    
    public function update($id, $data)
    {
        $record = $this->findById($id);
        if ($record === null) {
            return false;
        }
        
        foreach ($data as $key => $value) {
            $record->$key = $value;
        }
        
        return $record->save();
    }
}
```

---

## Chapter 11: Complete Usage Examples

### 11.1 Basic CRUD Operations

**User Management Service**:

```php
class UserService
{
    protected $recordClass = UserRecord::class;
    
    public function createUser($username, $email, $password)
    {
        // Check if username exists
        if ($this->findByUsername($username) !== null) {
            throw new Exception('Username already exists');
        }
        
        $user = new $this->recordClass();
        $user->username = $username;
        $user->email = $email;
        $user->password = password_hash($password);
        $user->status = 'active';
        $user->created_at = date('Y-m-d H:i:s');
        
        if ($user->save()) {
            return $user;
        }
        
        return false;
    }
    
    public function findByUsername($username)
    {
        return $this->recordClass::finder()->find('username = ?', [$username]);
    }
    
    public function findByEmail($email)
    {
        return $this->recordClass::finder()->find('email = ?', [$email]);
    }
    
    public function getActiveUsers($limit = 100, $offset = 0)
    {
        return $this->recordClass::finder()->findAll(
            'status = ?',
            ['active'],
            ['created_at' => 'DESC'],
            $limit,
            $offset
        );
    }
    
    public function deactivateUser($id)
    {
        $user = $this->findById($id);
        if ($user === null) {
            return false;
        }
        
        $user->status = 'inactive';
        return $user->save();
    }
    
    public function deleteUser($id)
    {
        $user = $this->findById($id);
        if ($user === null) {
            return false;
        }
        
        return $user->delete();
    }
    
    public function countActiveUsers()
    {
        return $this->recordClass::finder()->count('status = ?', ['active']);
    }
}
```

### 11.2 Complex Relationships

**Blog with Authors and Comments**:

```php
class PostRecord extends TActiveRecord
{
    const TABLE = 'posts';
    
    public $id;
    public $title;
    public $content;
    public $author_id;
    public $created_at;
    public $status;
    
    public static $RELATIONS = [
        'author' => [self::BELONGS_TO, 'AuthorRecord', 'author_id'],
        'comments' => [self::HAS_MANY, 'CommentRecord', 'post_id'],
    ];
}

class AuthorRecord extends TActiveRecord
{
    const TABLE = 'authors';
    
    public $id;
    public $name;
    public $email;
    public $bio;
    
    public static $RELATIONS = [
        'posts' => [self::HAS_MANY, 'PostRecord', 'author_id'],
    ];
}

class CommentRecord extends TActiveRecord
{
    const TABLE = 'comments';
    
    public $id;
    public $post_id;
    public $author_name;
    public $content;
    public $created_at;
    
    public static $RELATIONS = [
        'post' => [self::BELONGS_TO, 'PostRecord', 'post_id'],
    ];
}
```

**Usage with Relationships**:

```php
// Get a post with author and comments
$post = PostRecord::finder()->withAuthor()->findByPk(1);

echo $post->title;
echo $post->author->name;  // Loaded via eager loading

foreach ($post->comments as $comment) {
    echo $comment->content;
}

// Get all posts by an author
$author = AuthorRecord::finder()->findByPk(1);
foreach ($author->posts as $post) {
    echo $post->title;
}
```

### 11.3 Transaction Management

```php
class AccountService
{
    public function transfer($fromId, $toId, $amount)
    {
        $conn = TActiveRecord::getActiveDbConnection();
        $conn->beginTransaction();
        
        try {
            // Get accounts
            $from = AccountRecord::finder()->findByPk($fromId);
            $to = AccountRecord::finder()->findByPk($toId);
            
            // Validate balance
            if ($from->balance < $amount) {
                throw new Exception('Insufficient funds');
            }
            
            // Perform transfer
            $from->balance -= $amount;
            $to->balance += $amount;
            
            // Save both
            $from->save();
            $to->save();
            
            // Log transaction
            $log = new TransactionLogRecord();
            $log->from_account_id = $fromId;
            $log->to_account_id = $toId;
            $log->amount = $amount;
            $log->created_at = date('Y-m-d H:i:s');
            $log->save();
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
```

### 11.4 Event-Driven Logic

**Timestamp Tracking**:

```php
class TimestampRecord extends TActiveRecord
{
    public $id;
    public $name;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    
    protected function onInsert($param)
    {
        parent::onInsert($param);
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        
        if (Prado::getApplication()->hasUser()) {
            $this->created_by = Prado::getApplication()->getUser()->getId();
            $this->updated_by = $this->created_by;
        }
    }
    
    protected function onUpdate($param)
    {
        parent::onUpdate($param);
        $this->updated_at = date('Y-m-d H:i:s');
        
        if (Prado::getApplication()->hasUser()) {
            $this->updated_by = Prado::getApplication()->getUser()->getId();
        }
    }
}
```

**Soft Delete Pattern**:

```php
class SoftDeleteRecord extends TActiveRecord
{
    public $id;
    public $name;
    public $deleted_at;
    public $deleted_by;
    
    protected function onDelete($param)
    {
        // Instead of actual delete, mark as deleted
        $param->setIsValid(false);
        
        $this->deleted_at = date('Y-m-d H:i:s');
        if (Prado::getApplication()->hasUser()) {
            $this->deleted_by = Prado::getApplication()->getUser()->getId();
        }
        
        // Save as update
        $this->_recordState = self::STATE_LOADED;
        $this->save();
    }
}
```

### 11.5 Dynamic Finders

**Dynamic Finder Service**:

```php
// All these work automatically via __call magic method

// Find by single column
$user = UserRecord::finder()->findByUsername('alice');
$users = UserRecord::finder()->findAllByStatus('active');

// Find by multiple columns
$user = UserRecord::finder()->findByEmailAndStatus('alice@example.com', 'active');

// Find with OR
$user = UserRecord::finder()->findBy_Email_Or_Username_('alice@example.com', 'alice');

// Count
$count = UserRecord::finder()->countByStatus('active');

// Delete
UserRecord::finder()->deleteByStatus('inactive');

// Dynamic finders return null for no match
$user = UserRecord::finder()->findByUsername('nonexistent');
// $user is null
```

---

## Chapter 12: Best Practices

### 12.1 Performance Considerations

**Use Eager Loading for Known Relationships**:

```php
// Bad: N+1 queries
$posts = PostRecord::finder()->findAll();
foreach ($posts as $post) {
    echo $post->author->name;  // Query for each author
}

// Good: Single query with JOIN
$posts = PostRecord::finder()->withAuthor()->findAll();
foreach ($posts as $post) {
    echo $post->author->name;  // No additional queries
}
```

**Use Pagination for Large Datasets**:

```php
// Bad: Load all records
$allUsers = UserRecord::finder()->findAll();

// Good: Paginate
$page1 = UserRecord::finder()->findAll('1=1', [], ['name' => 'ASC'], 100, 0);
$page2 = UserRecord::finder()->findAll('1=1', [], ['name' => 'ASC'], 100, 100);
```

**Cache Metadata**:

```php
// Enable caching for metadata
$cache = new TFileCache('/tmp/cache');
TActiveRecordManager::getInstance()->setCache($cache);
```

### 12.2 Security Guidelines

**Always Use Parameter Binding**:

```php
// Bad: SQL injection vulnerable
$user = UserRecord::finder()->find("username = '$username'");

// Good: Parameterized query
$user = UserRecord::finder()->find('username = ?', [$username]);
```

**Protect Sensitive Data in Events**:

```php
protected function onExecuteCommand($param)
{
    $result = $param->getResult();
    
    if (is_array($result) && isset($result['password_hash'])) {
        unset($result['password_hash']);
        $param->setResult($result);
    }
}
```

### 12.3 Error Handling

```php
try {
    $user = new UserRecord();
    $user->username = 'alice';
    $user->email = 'alice@example.com';
    
    if (!$user->save()) {
        throw new Exception('Failed to save user');
    }
} catch (TActiveRecordException $e) {
    // Handle ActiveRecord-specific errors
    echo 'Record error: ' . $e->getMessage();
} catch (Exception $e) {
    // Handle other errors
    echo 'Error: ' . $e->getMessage();
}
```

### 12.4 Code Organization

**Record Class Best Practices**:

```php
// UserRecord.php

namespace App\Models;

use Prado\Data\ActiveRecord\TActiveRecord;

class UserRecord extends TActiveRecord
{
    // Table configuration
    const TABLE = 'users';
    
    // Column mapping (if needed)
    public static $COLUMN_MAPPING = [
        'user_id' => 'id',
    ];
    
    // Relationships
    public static $RELATIONS = [
        'profile' => [self::HAS_ONE, 'ProfileRecord', 'user_id'],
        'posts' => [self::HAS_MANY, 'PostRecord', 'author_id'],
    ];
    
    // Properties (public for AR)
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $created_at;
    
    // Finder (required for dynamic methods)
    public static function finder($className = __CLASS__)
    {
        return parent::finder($className);
    }
    
    // Event handlers
    protected function onInsert($param)
    {
        parent::onInsert($param);
        // Custom logic
    }
}
```

---

## Chapter 13: Troubleshooting

### 13.1 Common Issues

**"No primary key found" Error**:

This occurs when using primary key methods on a table without a defined primary key.

Solution: Use alternative find methods:

```php
// Instead of
$user->deleteByPk(1);

// Use
$user->deleteAll('id = ?', [1]);
```

**State Error on Save**:

```php
// Error: Cannot save a deleted record
if ($record->_recordState === TActiveRecord::STATE_DELETED) {
    throw new Exception('Cannot save deleted record');
}
```

**Relationship Not Loading**:

Ensure the relationship is properly defined and the foreign key column exists:

```php
public static $RELATIONS = [
    'author' => [self::BELONGS_TO, 'AuthorRecord', 'author_id'],
];
```

### 13.2 Debugging Techniques

**Enable SQL Logging**:

```php
// In your record class
protected function onCreateCommand($param)
{
    $command = $param->getCommand();
    Prado::trace('SQL: ' . $command->getText());
}

// Check record state
echo $record->_recordState;

// Check loaded relationships
print_r($record->_relationsObjs);
```

---

## Appendix A: Class Reference Summary

### TActiveRecord

**Constants**:

```php
const BELONGS_TO = 'BELONGS_TO';
const HAS_ONE = 'HAS_ONE';
const HAS_MANY = 'HAS_MANY';
const MANY_TO_MANY = 'MANY_TO_MANY';

const STATE_NEW = 0;
const STATE_LOADED = 1;
const STATE_DELETED = 2;
```

**Static Methods**:

```php
public static function finder($className = __CLASS__)
public static function getRecordManager()
public static function createRecord($type, $data)
```

**Instance Methods**:

```php
public function save()
public function delete()
public function deleteByPk($keys)
public function deleteAll($criteria = null, $parameters = [])

public function find($criteria, $parameters = [])
public function findAll($criteria = null, $parameters = [])
public function findByPk($keys)
public function findAllByPks($keys)
public function findBySql($sql, $parameters = [])
public function findAllBySql($sql, $parameters = [])
public function findAllByIndex($criteria, $fields, $values)
public function count($criteria = null, $parameters = [])

public function getDbConnection()
public function setDbConnection($connection)
public function getRecordTableInfo()
public function getColumnValue($columnName)
public function setColumnValue($columnName, $value)
public function toArray()
public function toJSON()
public function copyFrom($data)
public function equals(TActiveRecord $record, $strict = false)
```

**Event Methods**:

```php
protected function onInsert($param)
protected function onUpdate($param)
protected function onDelete($param)
public function onCreateCommand($param)
public function onExecuteCommand($param)
```

### TActiveRecordManager

**Static Methods**:

```php
public static function getInstance($self = null)
```

**Instance Methods**:

```php
public function setDbConnection($conn)
public function getDbConnection()
public function getRecordGateway()
public function setCache($cache)
public function getCache()
public function setGatewayClass($value)
public function getGatewayClass()
public function setInvalidFinderResult($value)
public function getInvalidFinderResult()
```

### TActiveRecordGateway

**Instance Methods**:

```php
public function getCommand(TActiveRecord $record)
public function findRecordByPK(TActiveRecord $record, $keys)
public function findRecordsByPks(TActiveRecord $record, $keys)
public function findRecordsByCriteria(TActiveRecord $record, $criteria, $iterator = false)
public function findRecordBySql(TActiveRecord $record, $criteria)
public function findRecordsBySql(TActiveRecord $record, $criteria)
public function insert(TActiveRecord $record)
public function update(TActiveRecord $record)
public function delete(TActiveRecord $record)
public function countRecords(TActiveRecord $record, $criteria)
```

### TActiveRecordCriteria

**Extends**: `TSqlCriteria`

**Constructor**:

```php
public function __construct($condition = null, $parameters = [])
```

**Properties** (from parent):

```php
public function getCondition()
public function setCondition($value)
public function getParameters()
public function setParameters($value)
public function getOrdersBy()
public function setOrdersBy($value)
public function getLimit()
public function setLimit($value)
public function getOffset()
public function setOffset($value)
```

### Relationship Classes

**TActiveRecordRelationContext**: Manages relationship fetching

**TActiveRecordBelongsTo**: Handles belongs-to relationships

**TActiveRecordHasOne**: Handles has-one relationships

**TActiveRecordHasMany**: Handles has-many relationships

**TActiveRecordHasManyAssociation**: Handles many-to-many relationships

---

## Appendix B: Change Log

**Version 4.3.3**:

- Added comprehensive Data.ActiveRecord manual
- Expanded relationship documentation
- Added complete usage examples

**Version 4.3.2**:

- Minor documentation updates and clarifications

**Version 4.3.1**:

- Initial Data.ActiveRecord documentation release

---

Notes:

- This manual assumes knowledge of PHP and Prado framework conventions
- Examples are illustrative; adapt code to your project conventions
- Always use parameterized queries to prevent SQL injection
- Connection management is the developer's responsibility