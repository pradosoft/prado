# Data.DataGateway Manual

This manual documents the complete Data.DataGateway components for the Prado framework. It provides comprehensive documentation of all classes, features, functionality, events, and integration patterns within the PRADO ecosystem. The Data.DataGateway layer provides a powerful abstraction for database operations including querying, inserting, updating, and deleting records without requiring the use of the Active Record pattern.

## Table of Contents

- [Chapter 1: Introduction](#chapter-1-introduction)
  - [1.1 What is Data.DataGateway?](#11-what-is-datadatagateway)
  - [1.2 Purpose and Scope](#12-purpose-and-scope)
  - [1.3 Key Concepts](#13-key-concepts)
- [Chapter 2: Architecture Overview](#chapter-2-architecture-overview)
  - [2.1 Component Hierarchy](#21-component-hierarchy)
  - [2.2 Class Relationships](#22-class-relationships)
  - [2.3 Integration with Data.Common](#23-integration-with-datacommon)
  - [2.4 Request Flow](#24-request-flow)
- [Chapter 3: TTableGateway Class](#chapter-3-ttablegateway-class)
  - [3.1 Overview and Construction](#31-overview-and-construction)
  - [3.2 Find Methods](#32-find-methods)
  - [3.3 Insert, Update, and Delete Methods](#33-insert-update-and-delete-methods)
  - [3.4 Count and Aggregate Methods](#34-count-and-aggregate-methods)
  - [3.5 Dynamic Finder Methods](#35-dynamic-finder-methods)
  - [3.6 SQL-Based Find Methods](#36-sql-based-find-methods)
- [Chapter 4: TDataGatewayCommand Class](#chapter-4-tdatagatewaycommand-class)
  - [4.1 Overview](#41-overview)
  - [4.2 Command Execution Methods](#42-command-execution-methods)
  - [4.3 Primary Key Operations](#43-primary-key-operations)
  - [4.4 Composite Key Handling](#44-composite-key-handling)
  - [4.5 Index-Based Find Operations](#45-index-based-find-operations)
- [Chapter 5: TSqlCriteria Class](#chapter-5-tsqlcriteria-class)
  - [5.1 Overview](#51-overview)
  - [5.2 Condition and Parameters](#52-condition-and-parameters)
  - [5.3 Ordering and Pagination](#53-ordering-and-pagination)
  - [5.4 Select Field Specification](#54-select-field-specification)
- [Chapter 6: Event System](#chapter-6-event-system)
  - [6.1 OnCreateCommand Event](#61-oncreatecommand-event)
  - [6.2 OnExecuteCommand Event](#62-onexecutecommand-event)
  - [6.3 Event Handler Signatures](#63-event-handler-signatures)
  - [6.4 Practical Event Usage](#64-practical-event-usage)
- [Chapter 7: Integration with PRADO](#chapter-7-integration-with-prado)
  - [7.1 Service Integration](#71-service-integration)
  - [7.2 Module Configuration](#72-module-configuration)
  - [7.3 Connection Management](#73-connection-management)
  - [7.4 Unit of Work Patterns](#74-unit-of-work-patterns)
- [Chapter 8: Complete Usage Examples](#chapter-8-complete-usage-examples)
  - [8.1 Basic CRUD Operations](#81-basic-crud-operations)
  - [8.2 Complex Querying](#82-complex-querying)
  - [8.3 Transaction Management](#83-transaction-management)
  - [8.4 Event-Driven Operations](#84-event-driven-operations)
  - [8.5 Dynamic Finder Patterns](#85-dynamic-finder-patterns)
- [Chapter 9: Best Practices](#chapter-9-best-practices)
  - [9.1 Performance Considerations](#91-performance-considerations)
  - [9.2 Security Guidelines](#92-security-guidelines)
  - [9.3 Error Handling](#93-error-handling)
  - [9.4 Code Organization](#94-code-organization)
- [Chapter 10: Troubleshooting](#chapter-10-troubleshooting)
  - [10.1 Common Issues](#101-common-issues)
  - [10.2 Debugging Techniques](#102-debugging-techniques)
  - [10.3 Error Messages](#103-error-messages)
- [Appendix A: Class Reference Summary](#appendix-a-class-reference-summary)
- [Appendix B: Change Log](#appendix-b-change-log)

---

## Chapter 1: Introduction

### 1.1 What is Data.DataGateway?

Data.DataGateway is a data access layer component within the Prado framework that provides a stateless gateway pattern for interacting with database tables. It allows developers to perform database operations including finding records by various criteria, inserting new records, updating existing records, and deleting records without requiring the overhead and complexity of the Active Record pattern.

The Data.DataGateway layer acts as an intermediary between the application and the database, translating method calls into appropriate SQL statements while handling parameter binding, result mapping, and event notification. Each method within the gateway maps input parameters into a SQL call and executes the SQL against a configured database connection.

Unlike Active Record where each record is an object that knows how to persist itself, the Table Gateway pattern treats the entire table as a single entity. This approach is particularly useful when you need to perform bulk operations, when you want to keep your domain objects separate from persistence logic, or when you prefer a more procedural style of data access.

### 1.2 Purpose and Scope

The primary purposes of Data.DataGateway are:

- **Simplified Data Access**: Provide a straightforward interface for common database operations without requiring the creation of individual record classes
- **Database Portability**: Leverage the underlying Data.Common layer to automatically generate database-specific SQL while maintaining a consistent API
- **Event-Driven Hooks**: Allow application code to intercept and modify database operations at key points during command creation and execution
- **Dynamic Query Building**: Support dynamic finder methods that automatically construct query conditions based on method names
- **Metadata Awareness**: Utilize table metadata to automatically handle primary keys, column types, and quoting conventions

The scope of Data.DataGateway encompasses:

- Single-table CRUD (Create, Read, Update, Delete) operations
- Complex query construction with flexible parameter binding
- Primary key and composite key operations
- Pagination with limit and offset
- Ordering and sorting
- Event notification for command logging and result manipulation
- Integration with the broader Prado Data layer including connections, commands, and metadata

### 1.3 Key Concepts

Understanding the following key concepts is essential for effective use of Data.DataGateway:

**Table Gateway Pattern**: A design pattern where a single object acts as an interface to all records in a database table. The gateway is responsible for all data access operations for that table, including queries, inserts, updates, and deletes.

**Stateless Operation**: The TTableGateway is designed to be stateless with respect to data and data objects. Its role is purely to push data back and forth between the application and the database. No state is maintained between method calls.

**Criteria Objects**: The TSqlCriteria class encapsulates all the parameters needed to construct a query, including conditions, parameters, ordering, limit, offset, and select fields. Criteria objects provide a clean, object-oriented way to specify query parameters.

**Command Builder**: The underlying TDataGatewayCommand uses a TDbCommandBuilder (from Data.Common) to generate actual SQL statements. This builder is selected automatically based on the database driver in use, ensuring database portability.

**Event System**: Two events, OnCreateCommand and OnExecuteCommand, allow application code to observe and modify database operations. These events fire at specific points in the command lifecycle, enabling logging, caching, and result manipulation.

**Dynamic Finders**: Magic methods like findByName() or findAllByAgeAndStatus() automatically construct appropriate WHERE clauses based on method name parsing.

---

## Chapter 2: Architecture Overview

### 2.1 Component Hierarchy

The Data.DataGateway layer consists of five primary classes, each with a distinct responsibility:

**TTableGateway** serves as the primary public interface for application code. It wraps a TDataGatewayCommand and exposes all the public find, insert, update, and delete methods. When a TTableGateway is constructed, it automatically determines the appropriate command builder based on the table name and database connection, then creates an internal TDataGatewayCommand instance.

**TDataGatewayCommand** is the internal command builder and executor. It receives criteria objects from TTableGateway and uses an underlying TDbCommandBuilder to construct and execute database commands. It also manages event raising for command creation and execution.

**TSqlCriteria** encapsulates all parameters needed for a query operation. It stores the SQL condition string, parameter values for binding, ordering specifications, pagination parameters (limit and offset), and select field specifications.

**TDataGatewayEventParameter** is passed to OnCreateCommand event handlers. It contains the TDbCommand that will be executed and the TSqlCriteria that was used to build it.

**TDataGatewayResultEventParameter** is passed to OnExecuteCommand event handlers. It contains the executed TDbCommand and the result returned from the database. Event handlers can modify the result by calling setResult().

### 2.2 Class Relationships

The relationship between these classes forms a clear hierarchy:

```
TTableGateway
    |
    +-- Contains TDataGatewayCommand
    |       |
    |       +-- Uses TDbCommandBuilder (from Data.Common)
    |               |
    |               +-- Uses TDbMetaData (provider-specific)
    |
    +-- Raises OnCreateCommand (TDataGatewayEventParameter)
    +-- Raises OnExecuteCommand (TDataGatewayResultEventParameter)
```

When you call a method on TTableGateway, the following sequence occurs:

1. The method constructs or receives a TSqlCriteria object containing query parameters
2. The method delegates to the internal TDataGatewayCommand instance
3. TDataGatewayCommand uses the TDbCommandBuilder to create a TDbCommand with bound parameters
4. Before execution, TDataGatewayCommand raises the OnCreateCommand event, passing the command and criteria
5. The command is executed, and the result is passed to the OnExecuteCommand event handler
6. The result is returned to the original caller

### 2.3 Integration with Data.Common

Data.DataGateway builds upon the Data.Common infrastructure to achieve database portability and efficient command construction:

**TDbMetaData** provides the entry point for metadata retrieval. When TTableGateway is constructed with a table name string, it calls TDbMetaData::getInstance() to obtain the appropriate provider-specific metadata object. The metadata object is determined by the database driver name (mysql, pgsql, sqlite, mssql, oci).

**TDbCommandBuilder** generates the actual SQL statements. Each provider has its own command builder class (TMysqlCommandBuilder, TPgsqlCommandBuilder, etc.) that extends the base TDbCommandBuilder class. The builder handles:

- SELECT statement construction with column quoting
- WHERE clause generation with parameter placeholders
- ORDER BY clause application
- LIMIT and OFFSET pagination
- INSERT, UPDATE, and DELETE statement generation
- Parameter binding using PDO

**TDbTableInfo and TDbTableColumn** provide schema information. These classes store metadata about table structure including column names, types, primary keys, foreign keys, default values, and nullability. This information is used by the command builder to generate correct SQL.

### 2.4 Request Flow

A complete request flow for a typical find operation:

```
Application Code
    |
    v
TTableGateway::find($criteria, $parameters)
    |
    v
TTableGateway::getCriteria() --> Creates TSqlCriteria from string + parameters
    |
    v
TDataGatewayCommand::find($criteria)
    |
    v
TDataGatewayCommand::getFindCommand($criteria)
    |
    +-- Gets condition, parameters, ordering, limit, offset, select from criteria
    |
    v
TDbCommandBuilder::createFindCommand($where, $params, $ordering, $limit, $offset, $select)
    |
    +-- Uses TDbTableInfo for column quoting and table name
    +-- Uses getSelectFieldList() for column list
    +-- Uses applyOrdering() for ORDER BY clause
    +-- Uses applyLimitOffset() for pagination
    |
    v
Returns TDbCommand with SQL and bound parameters
    |
    v
TDataGatewayCommand::onCreateCommand($command, $criteria)
    |
    +-- Raises OnCreateCommand event
    +-- Event handlers can inspect but not modify the command
    |
    v
$command->query() or $command->execute()
    |
    v
TDataGatewayCommand::onExecuteCommand($command, $result)
    |
    +-- Raises OnExecuteCommand event
    +-- Event handlers can modify the result
    |
    v
Return to Application Code
```

---

## Chapter 3: TTableGateway Class

### 3.1 Overview and Construction

TTableGateway is the primary entry point for using the Data.DataGateway layer. It provides a complete interface for all table-level database operations.

**Basic Construction**:

```php
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

// Create a database connection
$dsn = 'mysql:host=localhost;dbname=testdb';
$conn = new TDbConnection($dsn, 'username', 'password');
$conn->setActive(true);

// Create a table gateway for the 'users' table
$gateway = new TTableGateway('users', $conn);

// Now you can perform operations
$user = $gateway->findByPk(1);
```

**Construction with Table Info Object**:

```php
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;

// Get metadata and table info
$conn = new TDbConnection($dsn, 'username', 'password');
$conn->setActive(true);
$metadata = TDbMetaData::getInstance($conn);
$tableInfo = $metadata->getTableInfo('users');

// Create gateway with table info object
$gateway = new TTableGateway($tableInfo, $conn);
```

**Constructor Details**:

The TTableGateway constructor accepts two parameters:

- **$table**: Either a string containing the table or view name, or a TDbTableInfo object containing pre-loaded table metadata
- **$connection**: A TDbConnection instance for the database

```php
public function __construct($table, $connection)
{
    $this->_connection = $connection;
    if (is_string($table)) {
        $this->setTableName($table);
    } elseif ($table instanceof TDbTableInfo) {
        $this->setTableInfo($table);
    } else {
        throw new TDbException('dbtablegateway_invalid_table_info');
    }
    parent::__construct();
}
```

When constructed with a string table name, TTableGateway automatically:

1. Gets the appropriate TDbMetaData instance for the connection
2. Creates a command builder for the specified table
3. Initializes the internal TDataGatewayCommand

**Properties Available**:

```php
// Get the underlying table info
$tableInfo = $gateway->getTableInfo();

// Get the table name
$tableName = $gateway->getTableName();

// Get the database connection
$conn = $gateway->getDbConnection();
```

### 3.2 Find Methods

TTableGateway provides multiple methods for retrieving data from the database. All methods that return multiple rows return a TDbDataReader iterator, while methods that return a single row return an associative array.

**find() - Single Record**:

The find() method returns a single record matching the specified criteria:

```php
// With named parameters
$user = $gateway->find(
    'username = :name AND status = :status',
    [':name' => 'admin', ':status' => 'active']
);

// With positional parameters
$user = $gateway->find(
    'username = ? AND status = ?',
    ['admin', 'active']
);

// With individual parameters as arguments
$user = $gateway->find('username = ? AND status = ?', 'admin', 'active');

// With a pre-built TSqlCriteria object
$criteria = new TSqlCriteria('username = ? AND status = ?', ['admin', 'active']);
$user = $gateway->find($criteria);
```

**findAll() - Multiple Records**:

Returns a TDbDataReader containing zero or more matching records:

```php
// Find all active users
$reader = $gateway->findAll('status = ?', ['active']);

// Iterate through results
while ($row = $reader->read()) {
    echo $row['username'] . "\n";
}

// Or fetch all at once
$reader = $gateway->findAll();
$allRows = $reader->readAll();
```

**findByPk() - Find by Primary Key**:

Finds a single record using the primary key value:

```php
// Single primary key
$user = $gateway->findByPk(1);

// Multiple arguments for composite keys
$record = $gateway->findByPk($key1, $key2, $key3);

// As an array
$record = $gateway->findByPk([$key1, $key2, $key3]);
```

**findAllByPks() - Find Multiple by Primary Keys**:

Returns a TDbDataReader with records matching the provided primary keys:

```php
// Multiple single-key records
$reader = $gateway->findAllByPks(1, 2, 3, 4);

// As an array of single keys
$reader = $gateway->findAllByPks([1, 2, 3, 4]);

// Composite keys - each sub-array is one record's key
$reader = $gateway->findAllByPks(
    [$key1, $key2],
    [$key3, $key4]
);

// As an array of composite key arrays
$reader = $gateway->findAllByPks([
    [$key1, $key2],
    [$key3, $key4]
]);
```

**findAll() with Pagination and Ordering**:

```php
// Find with ordering
$reader = $gateway->findAll(
    'category = ?',
    ['electronics'],
    ['created_date' => 'DESC', 'name' => 'ASC']
);

// Find with limit
$reader = $gateway->findAll(
    '1=1',
    [],
    ['name' => 'ASC'],
    10  // limit
);

// Find with limit and offset for pagination
$reader = $gateway->findAll(
    '1=1',
    [],
    ['name' => 'ASC'],
    10,  // limit
    20   // offset (skip first 20)
);
```

### 3.3 Insert, Update, and Delete Methods

**insert() - Insert New Record**:

Inserts a new record into the table and returns the last insert ID if available:

```php
// Basic insert
$gateway->insert([
    'username' => 'newuser',
    'email' => 'newuser@example.com',
    'created_at' => date('Y-m-d H:i:s')
]);

// Insert and get the last insert ID
$id = $gateway->insert([
    'username' => 'newuser',
    'email' => 'newuser@example.com',
    'status' => 'active'
]);

// Insert with auto-increment primary key
$userId = $gateway->insert([
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);
echo "New user ID: $userId";
```

The insert() method returns:

- The last insert ID if the table has an auto-increment column or sequence
- true if the insert was successful and no auto-generated ID is available
- false if no rows were affected

**update() - Update Records**:

Updates records matching the specified condition:

```php
// Update with string condition
$affected = $gateway->update(
    ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')],
    'last_login < ?',
    ['2024-01-01']
);

// Update with named parameters
$affected = $gateway->update(
    ['status' => 'active'],
    'user_id = :id',
    [':id' => 42]
);

// Update all records (dangerous!)
$affected = $gateway->update(['status' => 'archived'], '1=1');
```

**updateByPk() - Update by Primary Key**:

Updates a single record identified by its primary key:

```php
// Update single record by primary key
$affected = $gateway->updateByPk(
    ['name' => 'Updated Name', 'status' => 'active'],
    1  // primary key value
);

// Update with composite keys
$affected = $gateway->updateByPk(
    ['name' => 'Updated Name'],
    [$key1, $key2]  // composite key values
);
```

**deleteAll() - Delete Records**:

Deletes records matching the specified condition:

```php
// Delete with string condition
$deleted = $gateway->deleteAll('status = ?', ['deleted']);

// Delete with named parameters
$deleted = $gateway->deleteAll('last_login < ? AND status = ?', ['2023-01-01', 'inactive']);

// Delete all records (dangerous!)
$deleted = $gateway->deleteAll('1=1');
```

**deleteByPk() - Delete by Primary Key**:

Deletes a single record identified by its primary key:

```php
// Delete by primary key
$deleted = $gateway->deleteByPk(1);

// Delete with multiple primary keys (deletes multiple records)
$deleted = $gateway->deleteByPk(1, 2, 3, 4);

// Delete with composite keys
$deleted = $gateway->deleteByPk([$key1, $key2]);

// Delete multiple composite key records
$deleted = $gateway->deleteByPk(
    [$key1, $key2],
    [$key3, $key4]
);
```

**deleteAllByPks() - Alias**:

An alias for deleteByPk() for semantic consistency:

```php
$deleted = $gateway->deleteAllByPks(1, 2, 3);
```

### 3.4 Count and Aggregate Methods

**count() - Count Matching Records**:

Returns the count of records matching the specified criteria:

```php
// Count all records
$total = $gateway->count();

// Count with condition
$activeCount = $gateway->count('status = ?', ['active']);

// Count with complex condition
$count = $gateway->count(
    'category = ? AND created > ?',
    ['electronics', '2024-01-01']
);

// Count with TSqlCriteria
$criteria = new TSqlCriteria();
$criteria->setCondition('status = ? AND level > ?');
$criteria->setParameters(['admin', 5]);
$count = $gateway->count($criteria);
```

### 3.5 Dynamic Finder Methods

TTableGateway supports dynamic finder methods through PHP's __call magic method. These methods automatically construct appropriate query conditions based on method names.

**Basic Dynamic Finders**:

Method names starting with "findBy" return a single record, while methods starting with "findAllBy" return a TDbDataReader:

```php
// findByName($name) becomes "WHERE name = ?"
$user = $gateway->findByName('Alice');

// findAllByStatus($status) becomes "WHERE status = ?"
$reader = $gateway->findAllByStatus('active');

// findByEmailAndStatus($email, $status) becomes "WHERE email = ? AND status = ?"
$user = $gateway->findByEmailAndStatus('alice@example.com', 'active');

// findAllByCategoryAndCreated($category, $date) becomes "WHERE category = ? AND created = ?"
$reader = $gateway->findAllByCategoryAndCreated('electronics', '2024-01-15');
```

**Using Underscores for AND/OR**:

Underscores in method names are parsed to allow explicit AND/OR specification:

```php
// findBy_Name_Or_Status_ allows explicit control
$user = $gateway->findBy_Name_Or_Status_('alice@example.com', 'active');
// Becomes: WHERE name = ? OR status = ?

// findAllBy_Category_And_Status_ explicitly specifies AND
$reader = $gateway->findAllBy_Category_And_Status_('electronics', 'active');
// Becomes: WHERE category = ? AND status = ?
```

**Dynamic Delete Methods**:

Methods starting with "deleteBy" or "deleteAllBy" perform delete operations:

```php
// deleteByStatus($status) - delete all where status matches
$deleted = $gateway->deleteByStatus('inactive');

// deleteAllByCategory($category) - alias for above
$deleted = $gateway->deleteAllByCategory('archived');

// deleteByEmail($email) - delete single record
$deleted = $gateway->deleteByEmail('user@example.com');
```

**Error Handling for Dynamic Methods**:

Dynamic methods throw exceptions when:

- The number of arguments doesn't match the number of conditions in the method name
- A column name in the method doesn't exist in the table schema

```php
try {
    // This will throw if 'nonexistent_column' doesn't exist
    $user = $gateway->findByNonexistentColumn('value');
} catch (TDbException $e) {
    echo "Invalid column: " . $e->getMessage();
}
```

### 3.6 SQL-Based Find Methods

For complex queries that cannot be expressed through the standard finder methods, TTableGateway provides SQL-based finders that accept arbitrary SQL.

**findBySql() - Single Record with Custom SQL**:

```php
// Basic usage with positional parameters
$user = $gateway->findBySql(
    'SELECT * FROM users WHERE username = ? AND password = ?',
    [$username, $password]
);

// With named parameters
$user = $gateway->findBySql(
    'SELECT * FROM users WHERE id = :id AND status = :status',
    [':id' => 1, ':status' => 'active']
);

// Returns null if no matching record
if ($user !== null) {
    echo $user['username'];
}
```

**findAllBySql() - Multiple Records with Custom SQL**:

```php
// Find all users with custom SQL
$reader = $gateway->findAllBySql(
    'SELECT * FROM users WHERE status = ? ORDER BY created DESC LIMIT 10',
    ['active']
);

// Process results
while ($row = $reader->read()) {
    echo $row['username'] . "\n";
}
```

**Important Note on SQL-Based Finders**:

When using SQL-based finders, the query is passed directly to the database without modification. This means:

- Table and column names should use the actual database names
- The query should be compatible with the target database engine
- Parameter binding follows the same rules as other methods
- The SELECT should select all columns needed, as the gateway doesn't add columns automatically

---

## Chapter 4: TDataGatewayCommand Class

### 4.1 Overview

TDataGatewayCommand is the internal command builder and executor that sits between TTableGateway and the underlying TDbCommandBuilder. While TTableGateway provides the public API and handles argument normalization, TDataGatewayCommand handles the actual command construction, parameter binding, and event raising.

**Direct Usage**:

While most users will interact only with TTableGateway, TDataGatewayCommand can be used directly when you need more control:

```php
use Prado\Data\DataGateway\TDataGatewayCommand;
use Prado\Data\Common\TDbMetaData;

// Get builder
$conn = new TDbConnection($dsn, $user, $pass);
$conn->setActive(true);
$metadata = TDbMetaData::getInstance($conn);
$builder = $metadata->createCommandBuilder('users');

// Create command
$cmd = new TDataGatewayCommand($builder);

// Use its methods directly
$cmd->delete(new TSqlCriteria('status = ?', ['deleted']));
```

**Key Properties**:

```php
// Get the underlying table info
$tableInfo = $cmd->getTableInfo();

// Get the database connection
$conn = $cmd->getDbConnection();

// Get the command builder
$builder = $cmd->getBuilder();
```

### 4.2 Command Execution Methods

TDataGatewayCommand provides the following execution methods:

**find() - Find Single Record**:

```php
public function find($criteria)
{
    $command = $this->getFindCommand($criteria);
    return $this->onExecuteCommand($command, $command->queryRow());
}
```

**findAll() - Find Multiple Records**:

```php
public function findAll($criteria)
{
    $command = $this->getFindCommand($criteria);
    return $this->onExecuteCommand($command, $command->query());
}
```

**findBySql() - Find with Custom SQL**:

```php
public function findBySql($criteria)
{
    $command = $this->getSqlCommand($criteria);
    return $this->onExecuteCommand($command, $command->queryRow());
}
```

**findAllBySql() - Find Multiple with Custom SQL**:

```php
public function findAllBySql($criteria)
{
    $command = $this->getSqlCommand($criteria);
    return $this->onExecuteCommand($command, $command->query());
}
```

**insert() - Insert Record**:

```php
public function insert($data)
{
    $command = $this->getBuilder()->createInsertCommand($data);
    $this->onCreateCommand($command, new TSqlCriteria(null, $data));
    $command->prepare();
    if ($this->onExecuteCommand($command, $command->execute()) > 0) {
        $value = $this->getLastInsertId();
        return $value !== null ? $value : true;
    }
    return false;
}
```

**update() - Update Records**:

```php
public function update($data, $criteria)
{
    $where = $criteria->getCondition();
    $parameters = $criteria->getParameters()->toArray();
    $command = $this->getBuilder()->createUpdateCommand($data, $where, $parameters);
    $this->onCreateCommand($command, $criteria);
    $command->prepare();
    return $this->onExecuteCommand($command, $command->execute());
}
```

**delete() - Delete Records**:

```php
public function delete($criteria)
{
    $where = $criteria->getCondition();
    $parameters = $criteria->getParameters()->toArray();
    $command = $this->getBuilder()->createDeleteCommand($where, $parameters);
    $this->onCreateCommand($command, $criteria);
    $command->prepare();
    return $command->execute();
}
```

**count() - Count Records**:

```php
public function count($criteria)
{
    if ($criteria === null) {
        return (int) $this->getBuilder()->createCountCommand()->queryScalar();
    }
    // ... construct count command with criteria
    return $this->onExecuteCommand($command, (int) $command->queryScalar());
}
```

### 4.3 Primary Key Operations

TDataGatewayCommand provides convenience methods for primary key operations:

**findByPk()**:

```php
public function findByPk($keys)
{
    if ($keys === null) {
        return null;
    }
    [$where, $parameters] = $this->getPrimaryKeyCondition((array) $keys);
    $command = $this->getBuilder()->createFindCommand($where, $parameters);
    $this->onCreateCommand($command, new TSqlCriteria($where, $parameters));
    return $this->onExecuteCommand($command, $command->queryRow());
}
```

**updateByPk()**:

```php
public function updateByPk($data, $keys)
{
    [$where, $parameters] = $this->getPrimaryKeyCondition((array) $keys);
    return $this->update($data, new TSqlCriteria($where, $parameters));
}
```

**deleteByPk()**:

```php
public function deleteByPk($keys)
{
    if (count($keys) == 0) {
        return 0;
    }
    $where = $this->getCompositeKeyCondition((array) $keys);
    $command = $this->getBuilder()->createDeleteCommand($where);
    $this->onCreateCommand($command, new TSqlCriteria($where, $keys));
    $command->prepare();
    return $this->onExecuteCommand($command, $command->execute());
}
```

### 4.4 Composite Key Handling

TDataGatewayCommand handles composite (multi-column) primary keys by accepting arrays of values:

**getCompositeKeyCondition()**:

This method constructs an IN clause for composite keys:

```php
protected function getCompositeKeyCondition($values)
{
    $primary = $this->getTableInfo()->getPrimaryKeys();
    $count = count($primary);
    
    if ($count === 0) {
        throw new TDbException('dbtablegateway_no_primary_key_found', ...);
    }
    
    if (!is_array($values) || count($values) === 0) {
        throw new TDbException('dbtablegateway_missing_pk_values', ...);
    }
    
    // Handle single composite key passed as flat array
    if ($count > 1 && (!isset($values[0]) || !is_array($values[0]))) {
        $values = [$values];
    }
    
    // Validate key count
    if ($count > 1 && count($values[0]) !== $count) {
        throw new TDbException('dbtablegateway_pk_value_count_mismatch', ...);
    }
    
    return $this->getIndexKeyCondition($this->getTableInfo(), $primary, $values);
}
```

**Example Composite Key Usage**:

```php
// Table with composite key (table_id, user_id)
// Find by composite key
$record = $gateway->findByPk([$tableId, $userId]);

// Find all by composite keys
$reader = $gateway->findAllByPks(
    [$tableId1, $userId1],
    [$tableId2, $userId2]
);

// Update by composite key
$gateway->updateByPk(
    ['status' => 'completed'],
    [$tableId, $userId]
);

// Delete by composite key
$gateway->deleteByPk([$tableId, $userId]);
```

### 4.5 Index-Based Find Operations

TDataGatewayCommand supports finding records by arbitrary indexes (not just primary keys) through the findAllByIndex() method:

**findAllByIndex()**:

```php
public function findAllByIndex($criteria, $fields, $values)
{
    $index = $this->getIndexKeyCondition($this->getTableInfo(), $fields, $values);
    $where = $criteria->getCondition();
    if ($where !== null && strlen($where) > 0) {
        $criteria->setCondition("({$index}) AND ({$where})");
    } else {
        $criteria->setCondition($index);
    }
    $command = $this->getFindCommand($criteria);
    $this->onCreateCommand($command, $criteria);
    return $this->onExecuteCommand($command, $command->query());
}
```

**Using Index-Based Find**:

```php
// Find all users with specific email and status
$criteria = new TSqlCriteria();
$reader = $gateway->findAllByIndex(
    $criteria,
    ['email', 'status'],           // Fields to match
    ['user@example.com', 'active'] // Values to match
);

// Find with additional condition
$criteria = new TSqlCriteria('level > ?', [5]);
$reader = $gateway->findAllByIndex(
    $criteria,
    ['department'],
    ['sales']
);
```

**getIndexKeyCondition() Helper**:

The internal helper constructs the WHERE clause for index-based operations:

```php
protected function getIndexKeyCondition($table, $fields, $values)
{
    if (!count($values)) {
        return 'FALSE';
    }
    
    $columns = [];
    $tableName = $table->getTableFullName();
    foreach ($fields as $field) {
        $columns[] = $tableName . '.' . $table->getColumn($field)->getColumnName();
    }
    
    return '(' . implode(', ', $columns) . ') IN ' . $this->quoteTuple($values);
}
```

---

## Chapter 5: TSqlCriteria Class

### 5.1 Overview

TSqlCriteria encapsulates all parameters needed to construct a database query. It provides an object-oriented way to specify conditions, parameters, ordering, pagination, and select fields separately from the actual query execution.

**Basic Construction**:

```php
use Prado\Data\DataGateway\TSqlCriteria;

// Empty criteria (selects all)
$criteria = new TSqlCriteria();

// With condition only
$criteria = new TSqlCriteria('status = ?', ['active']);

// With condition and parameters
$criteria = new TSqlCriteria(
    'username = :name AND status = :status',
    [':name' => 'admin', ':status' => 'active']
);

// With multiple parameters as arguments
$criteria = new TSqlCriteria(
    'username = ? AND status = ?',
    'admin',
    'active'
);
```

### 5.2 Condition and Parameters

**getCondition() and setCondition()**:

```php
// Get the WHERE condition string
$condition = $criteria->getCondition();

// Set a condition
$criteria->setCondition('status = ? AND level > ?');
$criteria->setParameters(['admin', 5]);
```

**Auto-Parsing of LIMIT, OFFSET, and ORDER BY**:

The setCondition() method automatically extracts LIMIT, OFFSET, and ORDER BY clauses from a condition string:

```php
// Condition with ORDER BY
$criteria->setCondition('status = ? ORDER BY created DESC');
// Result: condition = 'status = ?', ordering handled separately

// Condition with LIMIT
$criteria->setCondition('status = ? LIMIT 10');
// Result: condition = 'status = ?', limit = 10

// Condition with OFFSET
$criteria->setCondition('status = ? OFFSET 20');
// Result: condition = 'status = ?', offset = 20

// Combined
$criteria->setCondition('status = ? ORDER BY name ASC LIMIT 10 OFFSET 20');
// Result: condition = 'status = ?', ordering = name ASC, limit = 10, offset = 20
```

**getParameters() and setParameters()**:

```php
// Get parameter collection
$params = $criteria->getParameters();
foreach ($params as $name => $value) {
    echo "$name = $value\n";
}

// Set parameters (array or TAttributeCollection)
$criteria->setParameters([':id' => 1, ':status' => 'active']);

// Modify parameters directly
$criteria->getParameters()[':name'] = 'Alice';
```

**getIsNamedParameters()**:

```php
// Check if parameters are named or positional
if ($criteria->getIsNamedParameters()) {
    // Use named parameters
    $command->bindValue(':id', 1);
} else {
    // Use positional parameters
    $command->bindValue(1, 1);
}
```

### 5.3 Ordering and Pagination

**getOrdersBy() and setOrdersBy()**:

```php
// Get ordering collection
$orders = $criteria->getOrdersBy();
foreach ($orders as $column => $direction) {
    echo "$column $direction\n";
}

// Set ordering as array
$criteria->setOrdersBy(['created' => 'DESC', 'name' => 'ASC']);

// Set ordering as string (comma-separated)
$criteria->setOrdersBy('created DESC, name ASC');

// Set ordering as single string
$criteria->setOrdersBy('created DESC');
```

**getLimit() and setLimit()**:

```php
// Get limit
$limit = $criteria->getLimit();

// Set limit
$criteria->setLimit(10);
```

**getOffset() and setOffset()**:

```php
// Get offset
$offset = $criteria->getOffset();

// Set offset
$criteria->setOffset(20);
```

**Pagination Example**:

```php
// First page (items 0-9)
$page1 = new TSqlCriteria();
$page1->setCondition('1=1');
$page1->setOrdersBy(['name' => 'ASC']);
$page1->setLimit(10);
$page1->setOffset(0);

// Second page (items 10-19)
$page2 = new TSqlCriteria();
$page2->setCondition('1=1');
$page2->setOrdersBy(['name' => 'ASC']);
$page2->setLimit(10);
$page2->setOffset(10);
```

### 5.4 Select Field Specification

TSqlCriteria allows you to specify which fields are selected, overriding the default SELECT * behavior:

**getSelect() and setSelect()**:

```php
// Get current select specification
$select = $criteria->getSelect(); // Default is '*'

// Set specific columns
$criteria->setSelect(['id', 'username', 'email']);

// Set with aliases
$criteria->setSelect(['user_id' => 'id', 'user_name' => 'username']);

// Set to select all columns (expands to quoted column list)
$criteria->setSelect('*');

// Set with aggregate functions
$criteria->setSelect(['count' => 'COUNT(*)', 'max_level' => 'MAX(level)']);

// Set NULL for custom expressions
$criteria->setSelect(['custom_value' => 'NULL', 'computed' => 'id + 100']);
```

**Select Specification Rules**:

The setSelect() method has different behavior based on input type:

- **String '*'**: Returns all columns (quoted)
- **Array of column names**: Returns those columns (quoted)
- **Array with 'key => value' where value is column name**: Returns column AS key (aliasing)
- **Array with 'key => expression' where expression contains function**: Returns expression AS key
- **Array with 'NULL'**: Returns NULL AS key

---

## Chapter 6: Event System

The Data.DataGateway event system allows application code to intercept and modify database operations at two key points: when a command is prepared, and when a command has executed. This enables powerful patterns for logging, caching, auditing, and result manipulation.

### 6.1 OnCreateCommand Event

The OnCreateCommand event is raised after a TDbCommand has been created and parameter binding is complete, but before the command is executed. Event handlers receive a TDataGatewayEventParameter object containing the command and criteria.

**When It Fires**:

```php
// Inside TDataGatewayCommand::find()
$command = $this->getFindCommand($criteria);
$this->onCreateCommand($command, $criteria);  // Event fires here
return $this->onExecuteCommand($command, $command->query());
```

**Event Handler Example**:

```php
use Prado\Data\DataGateway\TDataGatewayEventParameter;

// Attach event handler
$gateway->OnCreateCommand[] = function($sender, $param) {
    /** @var TDataGatewayEventParameter $param */
    $command = $param->getCommand();
    $criteria = $param->getCriteria();
    
    // Log the SQL
    echo "Executing: " . $command->getText() . "\n";
    
    // Inspect criteria
    if ($criteria instanceof TSqlCriteria) {
        echo "Condition: " . $criteria->getCondition() . "\n";
    }
};
```

**What Event Handlers Can Do**:

- Inspect the SQL command text
- Inspect bound parameters
- Inspect the criteria object
- Add logging or tracing
- Implement query caching by storing the command

**What Event Handlers Cannot Do**:

- Modify the command text (it is already prepared)
- Modify the criteria (it is already used)
- Bind additional parameters (the command is ready to execute)

### 6.2 OnExecuteCommand Event

The OnExecuteCommand event is raised after a command has been executed and the result is available. Event handlers receive a TDataGatewayResultEventParameter object containing the command and the result. Unlike OnCreateCommand, handlers can modify the result.

**When It Fires**:

```php
public function find($criteria)
{
    $command = $this->getFindCommand($criteria);
    $this->onCreateCommand($command, $criteria);
    return $this->onExecuteCommand($command, $command->queryRow());
    //                       ^^^^^^^^^^^^^^^^^^^ Event fires here with result
}
```

**Event Handler Example - Modifying Results**:

```php
// Transform results before returning
$gateway->OnExecuteCommand[] = function($sender, $param) {
    /** @var TDataGatewayResultEventParameter $param */
    $result = $param->getResult();
    
    if (is_array($result)) {
        // Add computed field
        $result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];
        
        // Remove sensitive data
        unset($result['password_hash']);
        
        // Set modified result
        $param->setResult($result);
    }
    
    return $result;
};
```

**Event Handler Example - Logging Results**:

```php
// Log query results
$gateway->OnExecuteCommand[] = function($sender, $param) {
    $result = $param->getResult();
    $command = $param->getCommand();
    
    if ($result instanceof TDbDataReader) {
        // Count results for logging
        $count = 0;
        while ($row = $result->read()) {
            $count++;
        }
        Prado::trace("Query returned $count rows: " . $command->getText());
    } elseif (is_array($result)) {
        Prado::trace("Query returned 1 row: " . $command->getText());
    } else {
        Prado::trace("Query returned scalar: " . $command->getText());
    }
    
    return $param->getResult();
};
```

### 6.3 Event Handler Signatures

Event handlers must follow the PRADO event handler signature:

```php
function eventHandlerName($sender, $param)
{
    // $sender - the object that raised the event (TTableGateway)
    // $param - the event parameter object
}
```

**For OnCreateCommand**:

```php
use Prado\Data\DataGateway\TDataGatewayEventParameter;

function onCreateCommandHandler($sender, TDataGatewayEventParameter $param)
{
    $command = $param->getCommand();      // TDbCommand
    $criteria = $param->getCriteria();   // TSqlCriteria or mixed
}
```

**For OnExecuteCommand**:

```php
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;

function onExecuteCommandHandler($sender, TDataGatewayResultEventParameter $param)
{
    $command = $param->getCommand();  // TDbCommand
    $result = $param->getResult();   // varies by method
    // Can call $param->setResult($newResult) to modify
}
```

### 6.4 Practical Event Usage

**Query Logging**:

```php
class QueryLogger
{
    private $logFile;
    
    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }
    
    public function logCommand($sender, $param)
    {
        $command = $param->getCommand();
        $sql = $command->getText();
        
        $message = sprintf(
            "[%s] SQL: %s\n",
            date('Y-m-d H:i:s'),
            $sql
        );
        
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
    
    public function logResult($sender, $param)
    {
        $result = $param->getResult();
        if (is_array($result)) {
            $count = count($result);
        } elseif ($result instanceof TDbDataReader) {
            $count = 'reader';
        } else {
            $count = var_export($result, true);
        }
        
        $message = sprintf(
            "[%s] Result: %s\n",
            date('Y-m-d H:i:s'),
            $count
        );
        
        file_put_contents($this->logFile, $message, FILE_APPEND);
    }
}

// Usage
$logger = new QueryLogger('/var/log/queries.log');
$gateway->OnCreateCommand[] = [$logger, 'logCommand'];
$gateway->OnExecuteCommand[] = [$logger, 'logResult'];
```

**Query Auditing**:

```php
class AuditTrail
{
    private $auditGateway;
    
    public function __construct($auditGateway)
    {
        $this->auditGateway = $auditGateway;
    }
    
    public function auditQuery($sender, $param)
    {
        $command = $param->getCommand();
        $userId = Prado::getApplication()->getUser()->getId();
        
        $this->auditGateway->insert([
            'user_id' => $userId,
            'action' => 'SELECT',
            'sql' => $command->getText(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
```

**Result Caching**:

```php
class ResultCache
{
    private $cache = [];
    private $ttl = 300; // 5 minutes
    
    public function getCachedResult($sender, $param)
    {
        $command = $param->getCommand();
        $key = md5($command->getText());
        
        if (isset($this->cache[$key])) {
            $entry = $this->cache[$key];
            if (time() - $entry['time'] < $this->ttl) {
                $param->setResult($entry['result']);
            }
        }
    }
    
    public function cacheResult($sender, $param)
    {
        $result = $param->getResult();
        $command = $param->getCommand();
        $key = md5($command->getText());
        
        $this->cache[$key] = [
            'result' => $result,
            'time' => time()
        ];
    }
}
```

---

## Chapter 7: Integration with PRADO

### 7.1 Service Integration

Data.DataGateway integrates with the PRADO service architecture through the standard service and module patterns.

**Creating a Data Gateway Service**:

```php
// In application configuration (application.xml)
<services>
    <service id="db" class="Prado\Data\TDbConnection" 
             DSN="mysql:host=localhost;dbname=mydb"
             Username="user"
             Password="pass"
             AutoConnect="false" />
</services>
```

**Accessing the Connection**:

```php
// In a page or service
class MyPage extends TPage
{
    protected function getDbConnection()
    {
        return $this->getApplication()->getService('db');
    }
    
    protected function getUserGateway()
    {
        return new TTableGateway('users', $this->getDbConnection());
    }
}
```

### 7.2 Module Configuration

For more complex applications, you can create a dedicated module to manage data gateways:

```php
class DataGatewayModule extends TModule
{
    private $_connections = [];
    private $_gateways = [];
    
    public function init($config)
    {
        // Load connection configurations
        $this->loadConnections();
    }
    
    protected function loadConnections()
    {
        // From module configuration
    }
    
    public function getConnection($name = 'default')
    {
        if (!isset($this->_connections[$name])) {
            // Create connection based on configuration
            $this->_connections[$name] = $this->createConnection($name);
        }
        return $this->_connections[$name];
    }
    
    public function getTableGateway($table, $connectionName = 'default')
    {
        $key = $connectionName . '.' . $table;
        if (!isset($this->_gateways[$key])) {
            $this->_gateways[$key] = new TTableGateway(
                $table,
                $this->getConnection($connectionName)
            );
        }
        return $this->_gateways[$key];
    }
    
    protected function createConnection($name)
    {
        // Load config and create connection
        $conn = new TDbConnection($this->getConnectionString($name));
        $conn->setActive(true);
        return $conn;
    }
}
```

### 7.3 Connection Management

**Connection Pooling**:

While TDbConnection doesn't have built-in pooling, you can implement connection management at the application level:

```php
class ConnectionManager
{
    private static $_connections = [];
    
    public static function getConnection($key, $dsn, $username, $password)
    {
        if (!isset(self::$_connections[$key])) {
            $conn = new TDbConnection($dsn, $username, $password);
            $conn->setActive(true);
            self::$_connections[$key] = $conn;
        }
        return self::$_connections[$key];
    }
    
    public static function closeConnection($key)
    {
        if (isset(self::$_connections[$key])) {
            self::$_connections[$key]->setActive(false);
            unset(self::$_connections[$key]);
        }
    }
}
```

**Lazy Connection Activation**:

```php
$gateway = new TTableGateway('users', $conn);
// Connection is not activated until first command

$user = $gateway->findByPk(1);  // Connection activates here
```

### 7.4 Unit of Work Patterns

While Data.DataGateway doesn't implement the Unit of Work pattern directly, you can use it in combination with transactions for coordinated updates:

```php
class UnitOfWork
{
    private $connection;
    private $inserts = [];
    private $updates = [];
    private $deletes = [];
    
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    
    public function registerNew($table, $data)
    {
        $this->inserts[$table][] = $data;
    }
    
    public function registerDirty($table, $data, $condition, $params)
    {
        $this->updates[$table][] = [$data, $condition, $params];
    }
    
    public function registerDeleted($table, $condition, $params)
    {
        $this->deletes[$table][] = [$condition, $params];
    }
    
    public function commit()
    {
        $this->connection->beginTransaction();
        
        try {
            foreach ($this->inserts as $table => $records) {
                $gateway = new TTableGateway($table, $this->connection);
                foreach ($records as $data) {
                    $gateway->insert($data);
                }
            }
            
            foreach ($this->updates as $table => $operations) {
                $gateway = new TTableGateway($table, $this->connection);
                foreach ($operations as $op) {
                    [$data, $condition, $params] = $op;
                    $gateway->update($data, $condition, $params);
                }
            }
            
            foreach ($this->deletes as $table => $operations) {
                $gateway = new TTableGateway($table, $this->connection);
                foreach ($operations as $op) {
                    [$condition, $params] = $op;
                    $gateway->deleteAll($condition, $params);
                }
            }
            
            $this->connection->commit();
            
            // Clear registered operations
            $this->inserts = [];
            $this->updates = [];
            $this->deletes = [];
            
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
}
```

---

## Chapter 8: Complete Usage Examples

### 8.1 Basic CRUD Operations

**User Management Example**:

```php
class UserService
{
    private $gateway;
    
    public function __construct($connection)
    {
        $this->gateway = new TTableGateway('users', $connection);
    }
    
    public function createUser($username, $email, $password)
    {
        // Hash password (in real application)
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        return $this->gateway->insert([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getUserById($id)
    {
        return $this->gateway->findByPk($id);
    }
    
    public function getUserByUsername($username)
    {
        return $this->gateway->find('username = ?', [$username]);
    }
    
    public function getActiveUsers($limit = 100, $offset = 0)
    {
        return $this->gateway->findAll(
            'status = ?',
            ['active'],
            ['created_at' => 'DESC'],
            $limit,
            $offset
        );
    }
    
    public function updateUserStatus($userId, $status)
    {
        return $this->gateway->updateByPk(
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            $userId
        );
    }
    
    public function updatePassword($userId, $newPasswordHash)
    {
        return $this->gateway->updateByPk(
            ['password_hash' => $newPasswordHash, 'updated_at' => date('Y-m-d H:i:s')],
            $userId
        );
    }
    
    public function deleteUser($userId)
    {
        return $this->gateway->deleteByPk($userId);
    }
    
    public function countActiveUsers()
    {
        return $this->gateway->count('status = ?', ['active']);
    }
}
```

### 8.2 Complex Querying

**Blog Post Searching**:

```php
class BlogService
{
    private $gateway;
    
    public function __construct($connection)
    {
        $this->gateway = new TTableGateway('posts', $connection);
    }
    
    public function searchPosts($criteria)
    {
        $sqlCriteria = new TSqlCriteria();
        $params = [];
        $conditions = [];
        
        if (!empty($criteria['keyword'])) {
            $conditions[] = '(title LIKE ? OR content LIKE ?)';
            $keyword = '%' . $criteria['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if (!empty($criteria['category'])) {
            $conditions[] = 'category_id = ?';
            $params[] = $criteria['category'];
        }
        
        if (!empty($criteria['author_id'])) {
            $conditions[] = 'author_id = ?';
            $params[] = $criteria['author_id'];
        }
        
        if (!empty($criteria['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $criteria['status'];
        }
        
        if (!empty($criteria['date_from'])) {
            $conditions[] = 'created_at >= ?';
            $params[] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $conditions[] = 'created_at <= ?';
            $params[] = $criteria['date_to'];
        }
        
        // Build condition
        $where = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';
        
        // Set criteria properties
        $sqlCriteria->setCondition($where);
        $sqlCriteria->setParameters($params);
        
        // Ordering
        $orderBy = $criteria['order_by'] ?? 'created_at';
        $orderDir = $criteria['order_dir'] ?? 'DESC';
        $sqlCriteria->setOrdersBy([$orderBy => $orderDir]);
        
        // Pagination
        if (isset($criteria['limit'])) {
            $sqlCriteria->setLimit((int) $criteria['limit']);
        }
        if (isset($criteria['offset'])) {
            $sqlCriteria->setOffset((int) $criteria['offset']);
        }
        
        return $this->gateway->findAll($sqlCriteria);
    }
    
    public function getRelatedPosts($postId, $limit = 5)
    {
        // Get the original post to find category
        $post = $this->gateway->findByPk($postId);
        if (!$post) {
            return [];
        }
        
        // Find posts in same category, excluding current
        return $this->gateway->findAll(
            'category_id = ? AND id != ? AND status = ?',
            [$post['category_id'], $postId, 'published'],
            ['view_count' => 'DESC'],
            $limit
        );
    }
}
```

### 8.3 Transaction Management

**Financial Transaction Example**:

```php
class AccountService
{
    private $connection;
    private $transactionLog;
    
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->transactionLog = new TTableGateway('transaction_log', $connection);
    }
    
    public function transfer($fromAccountId, $toAccountId, $amount, $description)
    {
        $this->connection->beginTransaction();
        
        try {
            // Get current balances with row locking
            $accounts = new TTableGateway('accounts', $this->connection);
            
            $fromAccount = $accounts->findBySql(
                'SELECT * FROM accounts WHERE id = ? FOR UPDATE',
                [$fromAccountId]
            );
            
            $toAccount = $accounts->findBySql(
                'SELECT * FROM accounts WHERE id = ? FOR UPDATE',
                [$toAccountId]
            );
            
            // Validate sufficient funds
            if ($fromAccount['balance'] < $amount) {
                throw new Exception('Insufficient funds');
            }
            
            // Debit from account
            $accounts->updateByPk(
                ['balance' => $fromAccount['balance'] - $amount],
                $fromAccountId
            );
            
            // Credit to account
            $accounts->updateByPk(
                ['balance' => $toAccount['balance'] + $amount],
                $toAccountId
            );
            
            // Log the transaction
            $this->transactionLog->insert([
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'amount' => $amount,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->connection->commit();
            
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
}
```

### 8.4 Event-Driven Operations

**Audit Logging with Events**:

```php
class AuditedTableService
{
    private $gateway;
    private $auditGateway;
    private $userId;
    
    public function __construct($tableName, $connection, $userId = null)
    {
        $this->gateway = new TTableGateway($tableName, $connection);
        $this->auditGateway = new TTableGateway('audit_log', $connection);
        $this->userId = $userId ?? $this->getCurrentUserId();
        
        // Attach event handlers
        $this->gateway->OnCreateCommand[] = [$this, 'onCreateCommand'];
        $this->gateway->OnExecuteCommand[] = [$this, 'onExecuteCommand'];
    }
    
    public function onCreateCommand($sender, $param)
    {
        $command = $param->getCommand();
        $criteria = $param->getCriteria();
        
        // Log to audit trail
        $this->auditGateway->insert([
            'user_id' => $this->userId,
            'table_name' => $this->gateway->getTableName(),
            'action' => $this->detectAction($command->getText()),
            'sql_text' => $command->getText(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function onExecuteCommand($sender, $param)
    {
        // Can modify results if needed
        return $param->getResult();
    }
    
    protected function detectAction($sql)
    {
        $sql = strtoupper(trim($sql));
        if (strpos($sql, 'INSERT') === 0) {
            return 'INSERT';
        }
        if (strpos($sql, 'UPDATE') === 0) {
            return 'UPDATE';
        }
        if (strpos($sql, 'DELETE') === 0) {
            return 'DELETE';
        }
        if (strpos($sql, 'SELECT') === 0) {
            return 'SELECT';
        }
        return 'UNKNOWN';
    }
    
    protected function getCurrentUserId()
    {
        if (Prado::getApplication()->hasUser()) {
            return Prado::getApplication()->getUser()->getId();
        }
        return null;
    }
    
    // Delegate all other methods to the underlying gateway
    public function __call($method, $args)
    {
        return call_user_func_array([$this->gateway, $method], $args);
    }
}
```

### 8.5 Dynamic Finder Patterns

**Dynamic Finder Service**:

```php
class DynamicFinderService
{
    private $gateway;
    
    public function __construct($tableName, $connection)
    {
        $this->gateway = new TTableGateway($tableName, $connection);
    }
    
    // All find/findAll/delete methods are automatically available
    // through the __call magic method
    
    // Example: $service->findByEmailAndStatus($email, $status)
    // becomes: find('email = ? AND status = ?', [$email, $status])
    
    // Example: $service->findAllByCategory($category)
    // becomes: findAll('category = ?', [$category])
    
    // Example: $service->deleteByStatus($status)
    // becomes: deleteAll('status = ?', [$status])
    
    // The TTableGateway magic __call handles all of these automatically
}
```

**Usage Examples**:

```php
$service = new DynamicFinderService('products', $connection);

// These all work automatically via dynamic finders
$product = $service->findBySku('ABC123');
$products = $service->findAllByCategory('electronics');
$cheapProducts = $service->findAllByPriceLessThan(100);
$products = $service->findAllByCategoryAndStatus('electronics', 'active');
$service->deleteByDiscontinued(true);
```

---

## Chapter 9: Best Practices

### 9.1 Performance Considerations

**Use Pagination for Large Datasets**:

```php
// Bad: Loading all records
$allUsers = $gateway->findAll();  // May timeout with large tables

// Good: Use pagination
$page = $gateway->findAll('1=1', [], ['name' => 'ASC'], 100, $offset);
```

**Index Your Queries**:

Ensure your database queries can use indexes effectively. The Data.DataGateway layer uses your table metadata to construct queries, but you must ensure appropriate indexes exist on your database columns.

**Use Appropriate Select Fields**:

```php
// Bad: Select all when you only need a few fields
$users = $gateway->findAllBySql('SELECT * FROM users');

// Good: Select only needed fields
$users = $gateway->findAllBySql(
    'SELECT id, username, email FROM users WHERE status = ?',
    ['active']
);
```

**Connection Management**:

```php
// Reuse connections where possible
$connection = $this->getConnection();
$gateway1 = new TTableGateway('table1', $connection);
$gateway2 = new TTableGateway('table2', $connection);

// Both gateways share the same connection
```

### 9.2 Security Guidelines

**Always Use Parameter Binding**:

```php
// Bad: SQL injection vulnerable
$user = $gateway->find("username = '$username'");

// Good: Parameterized query
$user = $gateway->find('username = ?', [$username]);
```

**Validate Input at Application Layer**:

```php
public function getUserByEmail($email)
{
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email format');
    }
    
    return $this->gateway->find('email = ?', [$email]);
}
```

**Limit Query Scope**:

```php
// Be specific with your conditions
$user = $gateway->find('id = ? AND status = ?', [$id, 'active']);

// Avoid selecting everything when you need one record
$user = $gateway->findByPk($id);  // Uses primary key - most efficient
```

### 9.3 Error Handling

**Try-Catch for Database Operations**:

```php
try {
    $gateway->insert([
        'username' => $username,
        'email' => $email
    ]);
} catch (TDbException $e) {
    // Handle duplicate key, constraint violation, etc.
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        throw new Exception('Username already exists');
    }
    throw $e;
}
```

**Transaction Error Handling**:

```php
$conn->beginTransaction();
try {
    $gateway->update(['status' => 'active'], 'id = ?', [$id]);
    $logGateway->insert(['user_id' => $id, 'action' => 'activate']);
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    // Log or re-throw
    throw $e;
}
```

### 9.4 Code Organization

**Service Layer Pattern**:

```php
// Data Access Layer - TTableGateway usage
class UserGateway
{
    private $gateway;
    
    public function __construct($connection)
    {
        $this->gateway = new TTableGateway('users', $connection);
    }
    
    public function findById($id) { /* ... */ }
    public function findByEmail($email) { /* ... */ }
    public function insert($data) { /* ... */ }
    public function update($id, $data) { /* ... */ }
    public function delete($id) { /* ... */ }
}

// Business Logic Layer
class UserService
{
    private $gateway;
    
    public function __construct($connection)
    {
        $this->gateway = new UserGateway($connection);
    }
    
    public function registerUser($username, $email, $password)
    {
        // Business logic
        if ($this->gateway->findByEmail($email)) {
            throw new Exception('Email already registered');
        }
        
        return $this->gateway->insert([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password)
        ]);
    }
}
```

---

## Chapter 10: Troubleshooting

### 10.1 Common Issues

**"No primary key found" Error**:

This occurs when using primary key methods (findByPk, updateByPk, deleteByPk) on a table that has no defined primary key.

Solution: Either define a primary key in your table schema, or use alternative methods:

```php
// Instead of
$gateway->findByPk(1);

// Use
$gateway->find('id = ?', [1]);

// Or for updates
$gateway->update(['field' => 'value'], 'id = ?', [1]);
```

**"Mismatch args exception" Error**:

This occurs with dynamic finder methods when the number of conditions in the method name doesn't match the number of arguments provided.

```php
// Method name has 2 conditions (Email and Status)
// But only 1 argument provided
$gateway->findByEmailAndStatus($email);  // Throws exception

// Correct
$gateway->findByEmailAndStatus($email, $status);
```

**"Mismatch column name" Error**:

This occurs when a dynamic finder method references a column that doesn't exist in the table.

```php
// 'nonexistent' is not a column in the 'users' table
$gateway->findByNonexistentColumn('value');  // Throws exception
```

**Connection Already Active/Inactive**:

```php
// Sometimes happens with shared connections
try {
    $conn->setActive(true);
} catch (TDbException $e) {
    // Connection might already be active
}

// Check first
if (!$conn->getActive()) {
    $conn->setActive(true);
}
```

### 10.2 Debugging Techniques

**Inspect Generated SQL**:

```php
// Using OnCreateCommand event
$gateway->OnCreateCommand[] = function($sender, $param) {
    $command = $param->getCommand();
    Prado::trace('SQL: ' . $command->getText());
};
```

**Inspect Bound Parameters**:

```php
$gateway->OnCreateCommand[] = function($sender, $param) {
    $command = $param->getCommand();
    $criteria = $param->getCriteria();
    
    if ($criteria instanceof TSqlCriteria) {
        foreach ($criteria->getParameters() as $name => $value) {
            Prado::trace("$name = " . var_export($value, true));
        }
    }
};
```

**Check Criteria State**:

```php
$criteria = new TSqlCriteria('status = ?', ['active']);
$criteria->setOrdersBy(['name' => 'ASC']);
$criteria->setLimit(10);

echo $criteria;  // Uses __toString() for debugging
```

### 10.3 Error Messages

**Common TDbException Messages**:

| Error Code | Description | Solution |
|------------|-------------|----------|
| dbtablegateway_invalid_table_info | Invalid table parameter passed to constructor | Pass string table name or TDbTableInfo object |
| dbtablegateway_invalid_criteria | Invalid criteria type | Use string or TSqlCriteria |
| dbtablegateway_no_primary_key_found | Table has no primary key | Use alternative find/update/delete methods |
| dbtablegateway_missing_pk_values | Missing primary key values | Provide all key values for composite keys |
| dbtablegateway_pk_value_count_mismatch | Wrong number of key values | Match composite key column count |
| dbtablegateway_mismatch_args_exception | Dynamic finder arg mismatch | Match number of method arguments to conditions |
| dbtablegateway_mismatch_column_name | Invalid column in dynamic finder | Use actual column names from table schema |

---

## Appendix A: Class Reference Summary

### TTableGateway

**Constructor**:

```php
public function __construct($table, $connection)
```

**Properties**:

```php
public function getTableInfo()  // TDbTableInfo
public function getTableName()  // string
public function getDbConnection()  // TDbConnection
```

**Find Methods**:

```php
public function find($criteria, $parameters = [])
public function findAll($criteria = null, $parameters = [])
public function findByPk($keys)
public function findAllByPks($keys)
public function findBySql($sql, $parameters = [])
public function findAllBySql($sql, $parameters = [])
```

**Update Methods**:

```php
public function insert($data)
public function update($data, $criteria, $parameters = [])
public function updateByPk($data, $keys)
```

**Delete Methods**:

```php
public function deleteAll($criteria, $parameters = [])
public function deleteByPk($keys)
public function deleteAllByPks($keys)
```

**Aggregate Methods**:

```php
public function count($criteria = null, $parameters = [])
public function getLastInsertId()
```

**Dynamic Finders**: Via `__call()` magic method

### TDataGatewayCommand

**Constructor**:

```php
public function __construct($builder)  // TDbCommandBuilder
```

**Properties**:

```php
public function getTableInfo()  // TDbTableInfo
public function getDbConnection()  // TDbConnection
public function getBuilder()  // TDbCommandBuilder
```

**Execution Methods**:

```php
public function find($criteria)
public function findAll($criteria)
public function findByPk($keys)
public function findAllByPk($keys)
public function findBySql($criteria)
public function findAllBySql($criteria)
public function insert($data)
public function update($data, $criteria)
public function updateByPk($data, $keys)
public function delete($criteria)
public function deleteByPk($keys)
public function count($criteria)
```

**Events**:

```php
public function onCreateCommand($command, $criteria)  // Raises OnCreateCommand
public function onExecuteCommand($command, $result)  // Raises OnExecuteCommand
```

### TSqlCriteria

**Constructor**:

```php
public function __construct($condition = null, $parameters = [])
```

**Properties**:

```php
public function getCondition()
public function setCondition($value)

public function getParameters()  // TAttributeCollection
public function setParameters($value)

public function getOrdersBy()  // TAttributeCollection
public function setOrdersBy($value)

public function getLimit()
public function setLimit($value)

public function getOffset()
public function setOffset($value)

public function getSelect()
public function setSelect($value)

public function getIsNamedParameters()  // bool
```

### TDataGatewayEventParameter

**Constructor**:

```php
public function __construct($command, $criteria)
```

**Properties**:

```php
public function getCommand()  // TDbCommand
public function getCriteria()  // mixed
```

### TDataGatewayResultEventParameter

**Constructor**:

```php
public function __construct($command, $result)
```

**Properties**:

```php
public function getCommand()  // TDbCommand
public function getResult()  // mixed
public function setResult($value)  // modify before returning
```

---

## Appendix B: Change Log

**Version 4.3.3**:
- Added comprehensive Data.DataGateway manual
- Expanded event system documentation
- Added complete usage examples

**Version 4.3.2**:
- Minor documentation updates and clarifications

**Version 4.3.1**:
- Initial Data.DataGateway documentation release

---

Notes:

- This manual assumes knowledge of PHP and Prado framework conventions
- Examples are illustrative; adapt code to your project conventions
- Always use parameterized queries to prevent SQL injection
- Connection management (opening/closing) is the developer's responsibility