# Data.SqlMap Manual

This manual documents the complete Data.SqlMap component for the Prado framework. It provides comprehensive documentation of all classes, configuration options, statement types, result mapping, parameter mapping, caching, and integration patterns within the PRADO ecosystem. SqlMap implements the DataMapper pattern to separate database operations from object-oriented business logic, providing a powerful abstraction for managing SQL statements and their results.

## Table of Contents

- [Chapter 1: Introduction](#chapter-1-introduction)
  - [1.1 What is SqlMap?](#11-what-is-sqlmap)
  - [1.2 Purpose and Scope](#12-purpose-and-scope)
  - [1.3 Key Concepts](#13-key-concepts)
- [Chapter 2: Architecture Overview](#chapter-2-architecture-overview)
  - [2.1 Component Hierarchy](#21-component-hierarchy)
  - [2.2 Configuration Files](#22-configuration-files)
  - [2.3 Request Flow](#23-request-flow)
- [Chapter 3: TSqlMapManager Class](#chapter-3-tsqlmapmanager-class)
  - [3.1 Overview](#31-overview)
  - [3.2 Connection Management](#32-connection-management)
  - [3.3 Statement Management](#33-statement-management)
  - [3.4 Result Map Management](#34-result-map-management)
  - [3.5 Type Handler Registry](#35-type-handler-registry)
  - [3.6 Cache Management](#36-cache-management)
- [Chapter 4: TSqlMapGateway Class](#chapter-4-tsqlmapgateway-class)
  - [4.1 Overview](#41-overview)
  - [4.2 Query Methods](#42-query-methods)
  - [4.3 Update Methods](#43-update-methods)
  - [4.4 Transaction Support](#44-transaction-support)
  - [4.5 Cache Management](#45-cache-management)
- [Chapter 5: TSqlMapConfig Class](#chapter-5-tsqlmapconfig-class)
  - [5.1 Overview](#51-overview)
  - [5.2 Configuration File Loading](#52-configuration-file-loading)
  - [5.3 Cache Configuration](#53-cache-configuration)
- [Chapter 6: XML Configuration](#chapter-6-xml-configuration)
  - [6.1 SqlMap Configuration File Structure](#61-sqlmap-configuration-file-structure)
  - [6.2 Statement Types](#62-statement-types)
  - [6.3 Result Maps](#63-result-maps)
  - [6.4 Parameter Maps](#64-parameter-maps)
  - [6.5 Cache Models](#65-cache-models)
  - [6.6 Type Handlers](#66-type-handlers)
- [Chapter 7: Statement Classes](#chapter-7-statement-classes)
  - [7.1 TMappedStatement](#71-tmappedstatement)
  - [7.2 TSelectMappedStatement](#72-tselectmappedstatement)
  - [7.3 TInsertMappedStatement](#73-tinsertmappedstatement)
  - [7.4 TUpdateMappedStatement](#74-tupdatemappedstatement)
  - [7.5 TDeleteMappedStatement](#75-tdeletemappedstatement)
  - [7.6 TStaticSql](#76-tstaticsql)
  - [7.7 TPreparedStatement](#77-tpreparedstatement)
- [Chapter 8: Result Mapping](#chapter-8-result-mapping)
  - [8.1 Basic Result Mapping](#81-basic-result-mapping)
  - [8.2 Column Aliasing](#82-column-aliasing)
  - [8.3 Nested Result Maps](#83-nested-result-maps)
  - [8.4 Discriminators](#84-discriminators)
- [Chapter 9: Parameter Mapping](#chapter-9-parameter-mapping)
  - [9.1 Basic Parameter Mapping](#91-basic-parameter-mapping)
  - [9.2 Inline Parameters](#92-inline-parameters)
  - [9.3 Property Access](#93-property-access)
- [Chapter 10: Caching](#chapter-10-caching)
  - [10.1 Cache Models](#101-cache-models)
  - [10.2 Cache Configuration](#102-cache-configuration)
  - [10.3 Cache Implementation](#103-cache-implementation)
- [Chapter 11: Type Handlers](#chapter-11-type-handlers)
  - [11.1 Overview](#111-overview)
  - [11.2 Custom Type Handlers](#112-custom-type-handlers)
- [Chapter 12: Integration with PRADO](#chapter-12-integration-with-prado)
  - [12.1 Module Configuration](#121-module-configuration)
  - [12.2 Service Integration](#122-service-integration)
- [Chapter 13: Complete Usage Examples](#chapter-13-complete-usage-examples)
  - [13.1 Basic CRUD Operations](#131-basic-crud-operations)
  - [13.2 Complex Result Mapping](#132-complex-result-mapping)
  - [13.3 Transaction Management](#133-transaction-management)
  - [13.4 Caching Strategies](#134-caching-strategies)
- [Chapter 14: Best Practices](#chapter-14-best-practices)
  - [14.1 Performance Considerations](#141-performance-considerations)
  - [14.2 Security Guidelines](#142-security-guidelines)
  - [14.3 Code Organization](#143-code-organization)
- [Chapter 15: Troubleshooting](#chapter-15-troubleshooting)
  - [15.1 Common Issues](#151-common-issues)
  - [15.2 Debugging Techniques](#152-debugging-techniques)
- [Appendix A: Class Reference Summary](#appendix-a-class-reference-summary)
- [Appendix B: XML Schema Reference](#appendix-b-xml-schema-reference)
- [Appendix C: Change Log](#appendix-c-change-log)

---

## Chapter 1: Introduction

### 1.1 What is SqlMap?

Data.SqlMap implements the DataMapper pattern where a layer of Mappers moves data between domain objects and the database. Unlike ActiveRecord where objects carry persistence logic, SqlMap separates SQL statement management into external XML configuration files, keeping your domain objects clean and focused on business logic.

SqlMap provides:

- **Externalized SQL**: SQL statements are stored in XML configuration files, not in PHP code
- **Result Mapping**: Automatic mapping of database results to objects, collections, and primitive types
- **Parameter Mapping**: Automatic mapping of object properties to SQL statement parameters
- **Caching**: Built-in support for caching query results to improve performance
- **Type Handling**: Custom type conversion between PHP and database types

### 1.2 Purpose and Scope

The primary purposes of Data.SqlMap are:

- **Separation of Concerns**: Keep SQL out of domain objects
- **SQL Reuse**: Define SQL once and reuse across multiple calls
- **Portability**: Database-specific SQL can be managed centrally
- **Automated Mapping**: Reduce boilerplate code for result population
- **Performance Optimization**: Built-in caching and statement preparation

The scope encompasses:

- XML-based SQL statement configuration
- Multiple statement types (select, insert, update, delete)
- Complex result mapping with nested objects and collections
- Parameter mapping with various binding strategies
- Multiple caching strategies
- Transaction management

### 1.3 Key Concepts

**Mapped Statement**: A named SQL statement with associated processing logic, defined in XML configuration.

**Result Map**: A configuration that describes how to map database result columns to object properties, including nested results and discriminators.

**Parameter Map**: A configuration that describes how to map object properties to SQL statement parameters.

**Cache Model**: A configuration that defines caching behavior for specific statements.

**Type Handler**: Custom conversion logic between PHP types and database types.

---

## Chapter 2: Architecture Overview

### 2.1 Component Hierarchy

The Data.SqlMap layer consists of these primary components:

**TSqlMapManager** serves as the central configuration and management component:

- Holds all mapped statements, result maps, parameter maps, and cache models
- Provides access to the TSqlMapGateway for executing statements
- Manages type handler registry
- Handles XML configuration loading

**TSqlMapGateway** provides the client interface:

- Methods for executing queries (queryForObject, queryForList, queryForMap)
- Methods for executing updates (insert, update, delete)
- Cache flushing operations
- Type handler registration

**Statement Classes** handle statement execution:

- TMappedStatement: Base statement class
- TSelectMappedStatement: SELECT statement execution
- TInsertMappedStatement: INSERT statement execution
- TUpdateMappedStatement: UPDATE statement execution
- TDeleteMappedStatement: DELETE statement execution

**Configuration Classes** parse and store configuration:

- TSqlMapXmlConfiguration: Main configuration parser
- TSqlMapXmlMappingConfiguration: Mapping file parser
- TResultMap: Result mapping configuration
- TParameterMap: Parameter mapping configuration
- TSqlMapCacheModel: Cache configuration

### 2.2 Configuration Files

SqlMap uses XML configuration files to define all statements and mappings:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sqlMap>
    <!-- Global properties -->
    <property name="Database" value="MySQL"/>
    
    <!-- Type handlers -->
    <typeHandler class="MyCustomHandler"/>
    
    <!-- Result maps -->
    <resultMap id="UserResult">
        <result property="id" column="user_id"/>
        <result property="username" column="username"/>
    </resultMap>
    
    <!-- Statements -->
    <select id="getUser" resultMap="UserResult">
        SELECT * FROM users WHERE user_id = #id#
    </select>
</sqlMap>
```

### 2.3 Request Flow

**Query Execution Flow**:

```
$sqlmap->queryForObject('getUser', $id)
    |
    v
TSqlMapGateway::queryForObject()
    |
    v
TSqlMapManager::getMappedStatement('getUser')
    |
    v
TSelectMappedStatement::executeQueryForObject()
    |
    v
Create TDbCommand with SQL from statement
    |
    v
Bind parameters from input object
    |
    v
Execute query
    |
    v
TResultMap::populateObject() maps results to object
    |
    v
Return mapped object
```

**Update Execution Flow**:

```
$sqlmap->insert('insertUser', $userObject)
    |
    v
TSqlMapGateway::insert()
    |
    v
TInsertMappedStatement::executeInsert()
    |
    v
Create TDbCommand with SQL
    |
    v
TParameterMap::setParameter() binds values from object
    |
    v
Execute insert
    |
    v
Return last insert ID
```

---

## Chapter 3: TSqlMapManager Class

### 3.1 Overview

TSqlMapManager is the central component that holds all SqlMap configuration and provides access to the gateway for statement execution.

```php
// Basic instantiation
$conn = new TDbConnection($dsn, $user, $pass);
$manager = new TSqlMapManager($conn);
$manager->configureXml('/path/to/sqlmap.xml');
$sqlmap = $manager->getSqlmapGateway();
```

### 3.2 Connection Management

```php
public function setDbConnection($conn)
public function getDbConnection()
```

```php
$dsn = 'mysql:host=localhost;dbname=testdb';
$conn = new TDbConnection($dsn, 'username', 'password');

$manager = new TSqlMapManager($conn);
// Or set later
$manager->setDbConnection($conn);
```

### 3.3 Statement Management

**Get a Statement**:

```php
$statement = $manager->getMappedStatement('getUserById');
```

**Add a Statement Programmatically**:

```php
$statement = new TSelectMappedStatement();
$statement->setID('getUserById');
$statement->setResultMap('UserResult');
$statement->setSql('SELECT * FROM users WHERE id = ?');

$manager->addMappedStatement($statement);
```

**Check if Statement Exists**:

```php
if ($manager->getMappedStatements()->contains('getUser')) {
    // Statement exists
}
```

### 3.4 Result Map Management

**Get a Result Map**:

```php
$resultMap = $manager->getResultMap('UserResult');
```

**Add a Result Map Programmatically**:

```php
$resultMap = new TResultMap();
$resultMap->setID('UserResult');
// Configure columns...

$manager->getResultMaps()->add('UserResult', $resultMap);
```

### 3.5 Type Handler Registry

```php
$registry = $manager->getTypeHandlers();

// Register custom handler
$handler = new MyCustomTypeHandler();
$registry->registerTypeHandler($handler);

// Check if type is handled
if ($registry->hasTypeHandler('datetime')) {
    // Handler exists
}
```

### 3.6 Cache Management

**Flush All Caches**:

```php
$manager->flushCacheModels();
```

**Get Cache Dependencies**:

```php
$dependencies = $manager->getCacheDependencies();
```

---

## Chapter 4: TSqlMapGateway Class

### 4.1 Overview

TSqlMapGateway is the client interface for executing SqlMap statements. It provides methods for queries and updates.

```php
$sqlmap = $manager->getSqlmapGateway();

// Query for single object
$user = $sqlmap->queryForObject('getUser', 1);

// Query for list
$users = $sqlmap->queryForList('getAllUsers');

// Insert
$newId = $sqlmap->insert('insertUser', $userData);

// Update
$affected = $sqlmap->update('updateUser', $userData);

// Delete
$affected = $sqlmap->delete('deleteUser', 1);
```

### 4.2 Query Methods

**queryForObject() - Single Result**:

```php
public function queryForObject($statementName, $parameter = null, $result = null)
```

```php
// With parameter
$user = $sqlmap->queryForObject('getUser', 1);

// With parameter object
$criteria = new stdClass();
$criteria->id = 1;
$criteria->active = true;
$user = $sqlmap->queryForObject('getUser', $criteria);

// With existing result object
$existingUser = new User();
$user = $sqlmap->queryForObject('getUser', 1, $existingUser);
```

**queryForList() - Multiple Results**:

```php
public function queryForList($statementName, $parameter = null, $result = null, $skip = -1, $max = -1)
```

```php
// Get all users
$users = $sqlmap->queryForList('getAllUsers');

// With parameter
$users = $sqlmap->queryForList('getUsersByStatus', 'active');

// With pagination
$users = $sqlmap->queryForList('getAllUsers', null, null, 0, 10); // skip, max

// With parameter and pagination
$users = $sqlmap->queryForList(
    'getUsersByStatus',
    'active',
    null,  // result list
    0,     // skip
    10     // max
);
```

**queryWithRowDelegate() - Custom Row Processing**:

```php
public function queryWithRowDelegate($statementName, $delegate, $parameter = null, $result = null, $skip = -1, $max = -1)
```

```php
$users = [];
$sqlmap->queryWithRowDelegate(
    'getAllUsers',
    function($row, $index, $list) use (&$users) {
        $user = new User();
        $user->id = $row['id'];
        $user->username = $row['username'];
        $users[] = $user;
    },
    null,  // parameter
    null,  // result list
    0,     // skip
    100    // max
);
```

**queryForPagedList() - Paginated Results**:

```php
public function queryForPagedList($statementName, $parameter = null, $pageSize = 10, $page = 0)
```

```php
// Get paginated results
$pagedList = $sqlmap->queryForPagedList('getAllUsers', null, 10, 0);

// Access page data
$currentPage = $pagedList->getPageIndex();
$totalPages = $pagedList->getPageCount();
$users = $pagedList->getArray();
```

**queryForMap() - Key-Value Results**:

```php
public function queryForMap($statementName, $parameter = null, $keyProperty = null, $valueProperty = null, $skip = -1, $max = -1)
```

```php
// Key by id, value by username
$userMap = $sqlmap->queryForMap(
    'getAllUsers',
    null,
    'id',       // key property
    'username'  // value property
);

// Result: [1 => 'alice', 2 => 'bob', 3 => 'charlie']

// Key by id, full object as value
$userMap = $sqlmap->queryForMap(
    'getAllUsers',
    null,
    'id',       // key property
    null        // value property (full object)
);
```

### 4.3 Update Methods

**insert() - Insert Record**:

```php
public function insert($statementName, $parameter = null)
```

```php
// Insert with parameter object
$userData = new stdClass();
$userData->username = 'alice';
$userData->email = 'alice@example.com';
$newId = $sqlmap->insert('insertUser', $userData);

// Insert with array
$newId = $sqlmap->insert('insertUser', [
    'username' => 'alice',
    'email' => 'alice@example.com'
]);
```

**update() - Update Records**:

```php
public function update($statementName, $parameter = null)
```

```php
// Update with parameter object
$userData = new stdClass();
$userData->id = 1;
$userData->email = 'newemail@example.com';
$affected = $sqlmap->update('updateUser', $userData);

// Update with array
$affected = $sqlmap->update('updateUser', [
    'id' => 1,
    'email' => 'newemail@example.com'
]);
```

**delete() - Delete Records**:

```php
public function delete($statementName, $parameter = null)
```

```php
// Delete by ID
$affected = $sqlmap->delete('deleteUser', 1);

// Delete with complex parameter
$criteria = new stdClass();
$criteria->status = 'inactive';
$criteria->deleted_before = '2024-01-01';
$affected = $sqlmap->delete('deleteInactiveUsers', $criteria);
```

### 4.4 Transaction Support

SqlMap leverages the underlying TDbConnection for transaction management:

```php
$conn = $sqlmap->getDbConnection();

try {
    $conn->beginTransaction();
    
    // Perform multiple operations
    $sqlmap->update('debitAccount', $debitData);
    $sqlmap->update('creditAccount', $creditData);
    $sqlmap->insert('logTransaction', $logData);
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}
```

### 4.5 Cache Management

**flushCaches() - Clear All Caches**:

```php
$sqlmap->flushCaches();
```

This clears all cached results for all cache models.

---

## Chapter 5: TSqlMapConfig Class

### 5.1 Overview

TSqlMapConfig extends TDataSourceConfig to provide module-level configuration for SqlMap. It integrates with the PRADO module system and supports caching of the SqlMap manager instance.

```php
class MySqlMapModule extends TSqlMapConfig
{
    // Uses parent configuration
}

// In application.xml
<modules>
    <module id="sqlmap" class="MySqlMapModule"
            ConfigFile="application.sqlmap.xml"
            EnableCache="true" />
</modules>
```

### 5.2 Configuration File Loading

```php
// Set via property
$config->setConfigFile('application.sqlmap.xml');

// Get the manager
$manager = $config->getSqlMapManager();
$sqlmap = $manager->getSqlmapGateway();
```

### 5.3 Cache Configuration

Enable caching of the SqlMap manager instance:

```php
$config->setEnableCache(true);

// Cache is cleared on configuration change
$config->clearCache();
```

---

## Chapter 6: XML Configuration

### 6.1 SqlMap Configuration File Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sqlMap xmlns="http://pradosoft.com/dtd/sqlmap.dtd">
    
    <!-- Global properties -->
    <property name="Database" value="MySQL"/>
    
    <!-- Type handlers -->
    <typeHandler class="MyDateTimeHandler"/>
    
    <!-- Result maps -->
    <resultMap id="UserResult" class="User">
        <result property="id" column="user_id"/>
        <result property="username" column="username"/>
        <result property="email" column="email"/>
    </resultMap>
    
    <!-- Parameter maps -->
    <parameterMap id="UserParameter">
        <parameter property="username"/>
        <parameter property="email"/>
    </parameterMap>
    
    <!-- Cache models -->
    <cacheModel id="userCache" type="LRU">
        <flushInterval minutes="30"/>
        <flushOnExecute statement="insertUser"/>
        <property name="cacheSize" value="1000"/>
    </cacheModel>
    
    <!-- Statements -->
    <select id="getUser" parameterClass="int" resultMap="UserResult">
        SELECT * FROM users WHERE user_id = #value#
    </select>
    
    <insert id="insertUser" parameterMap="UserParameter">
        INSERT INTO users (username, email) VALUES (?, ?)
    </insert>
    
</sqlMap>
```

### 6.2 Statement Types

**select Statement**:

```xml
<select id="getUser" resultMap="UserResult">
    SELECT * FROM users WHERE user_id = #id#
</select>

<select id="getUsersByStatus" parameterClass="string" resultMap="UserResult">
    SELECT * FROM users WHERE status = #value#
</select>
```

**insert Statement**:

```xml
<insert id="insertUser" parameterMap="UserParameter">
    INSERT INTO users (username, email) VALUES (?, ?)
</insert>

<insert id="insertUserAutoKey" resultClass="int">
    INSERT INTO users (username) VALUES (#username#);
    SELECT LAST_INSERT_ID();
</insert>
```

**update Statement**:

```xml
<update id="updateUser" parameterClass="User">
    UPDATE users SET username = #username#, email = #email# WHERE user_id = #id#
</update>
```

**delete Statement**:

```xml
<delete id="deleteUser" parameterClass="int">
    DELETE FROM users WHERE user_id = #value#
</delete>
```

### 6.3 Result Maps

**Basic Result Map**:

```xml
<resultMap id="UserResult" class="User">
    <result property="id" column="user_id"/>
    <result property="username" column="username"/>
    <result property="email" column="email"/>
    <result property="createdAt" column="created_at"/>
</resultMap>
```

**Result with Type Conversion**:

```xml
<resultMap id="UserResult" class="User">
    <result property="id" column="user_id"/>
    <result property="createdAt" column="created_at" type="datetime"/>
</resultMap>
```

**Nested Result Map**:

```xml
<resultMap id="OrderResult" class="Order">
    <result property="id" column="order_id"/>
    <result property="customer" resultMap="CustomerResult"/>
</resultMap>
```

### 6.4 Parameter Maps

**Basic Parameter Map**:

```xml
<parameterMap id="UserParameter">
    <parameter property="username"/>
    <parameter property="email"/>
</parameterMap>
```

**With Type Specification**:

```xml
<parameterMap id="UserParameter">
    <parameter property="username" type="string"/>
    <parameter property="createdAt" type="timestamp"/>
</parameterMap>
```

**Inline Parameters**:

```xml
<update id="updateUser">
    UPDATE users SET username = #username#, email = #email# WHERE id = #id#
</update>
```

### 6.5 Cache Models

**LRU (Least Recently Used) Cache**:

```xml
<cacheModel id="userCache" type="LRU">
    <flushInterval hours="1"/>
    <flushOnExecute statement="insertUser"/>
    <flushOnExecute statement="updateUser"/>
    <flushOnExecute statement="deleteUser"/>
    <property name="cacheSize" value="1000"/>
</cacheModel>
```

**FIFO (First In, First Out) Cache**:

```xml
<cacheModel id="userCache" type="FIFO">
    <flushInterval minutes="30"/>
    <property name="cacheSize" value="100"/>
</cacheModel>
```

### 6.6 Type Handlers

**Custom Type Handler**:

```xml
<typeHandler class="MyDateTimeHandler" type="datetime">
    <property name="format" value="Y-m-d H:i:s"/>
</typeHandler>
```

---

## Chapter 7: Statement Classes

### 7.1 TMappedStatement

Base statement class providing common functionality.

```php
// Methods
public function executeQueryForObject($conn, $parameter, $result)
public function executeQueryForList($conn, $parameter, $result, $skip, $max, $delegate = null)
public function executeUpdate($conn, $parameter)
public function executeInsert($conn, $parameter)
```

### 7.2 TSelectMappedStatement

Handles SELECT statements that return results.

```php
public function executeQueryForObject($conn, $parameter, $result = null)
{
    // Build command, bind parameters, execute, map results
}

public function executeQueryForList($conn, $parameter, $result = null, $skip = -1, $max = -1, $delegate = null)
{
    // Build command, bind parameters, execute, return list
}

public function executeQueryForMap($conn, $parameter, $keyProperty, $valueProperty, $skip, $max, $delegate = null)
{
    // Build command, bind parameters, execute, return map
}
```

### 7.3 TInsertMappedStatement

Handles INSERT statements.

```php
public function executeInsert($conn, $parameter)
{
    // Build command, bind parameters, execute insert
    // Return last insert ID if applicable
}
```

**With Auto-Generated Key**:

```xml
<insert id="insertUser" resultClass="int">
    INSERT INTO users (username) VALUES (#username#)
    SELECT LAST_INSERT_ID();
</insert>
```

### 7.4 TUpdateMappedStatement

Handles UPDATE statements.

```php
public function executeUpdate($conn, $parameter)
{
    // Build command, bind parameters, execute
    // Return affected row count
}
```

### 7.5 TDeleteMappedStatement

Handles DELETE statements.

```php
public function executeUpdate($conn, $parameter)  // Note: uses same method as Update
{
    // Builds DELETE command, binds parameters, executes
    // Return affected row count
}
```

### 7.6 TStaticSql

Represents static SQL statements defined in XML.

```php
class TStaticSql extends TMappedStatement
{
    // SQL text is static, no dynamic generation
}
```

### 7.7 TPreparedStatement

Represents prepared statements that can be cached and reused.

```php
class TPreparedStatement extends TMappedStatement
{
    // Uses PDO prepared statements for performance
}
```

---

## Chapter 8: Result Mapping

### 8.1 Basic Result Mapping

**Property to Column Mapping**:

```xml
<resultMap id="UserResult" class="User">
    <result property="id" column="user_id"/>
    <result property="username" column="username"/>
    <result property="email" column="email_address"/>
</resultMap>
```

The result mapper automatically populates object properties from database columns.

### 8.2 Column Aliasing

**Using SQL Aliases**:

```xml
<resultMap id="UserResult" class="User">
    <result property="id" column="user_id"/>
    <result property="username" column="user_name"/>  <!-- SQL: SELECT user_name ... -->
</resultMap>
```

### 8.3 Nested Result Maps

**Nested Objects**:

```xml
<resultMap id="OrderResult" class="Order">
    <result property="id" column="order_id"/>
    <result property="customerName" column="customer_name"/>
    <result property="customer" resultMap="CustomerResult"/>
</resultMap>

<resultMap id="CustomerResult" class="Customer">
    <result property="id" column="customer_id"/>
    <result property="name" column="customer_name"/>
</resultMap>
```

### 8.4 Discriminators

**Conditional Mapping**:

```xml
<resultMap id="VehicleResult" class="Vehicle">
    <result property="id" column="vehicle_id"/>
    <result property="type" column="vehicle_type"/>
    <discriminator column="vehicle_type">
        <resultMap resultMap="CarResult"/>
        <resultMap resultMap="TruckResult"/>
    </discriminator>
</resultMap>
```

---

## Chapter 9: Parameter Mapping

### 9.1 Basic Parameter Mapping

**Using Parameter Map**:

```xml
<parameterMap id="UserInsert">
    <parameter property="username"/>
    <parameter property="email"/>
</parameterMap>

<insert id="insertUser" parameterMap="UserInsert">
    INSERT INTO users (username, email) VALUES (?, ?)
</insert>
```

### 9.2 Inline Parameters

**Hash Notation (#)**:

```xml
<select id="getUser" resultMap="UserResult">
    SELECT * FROM users WHERE id = #id#
</select>

<update id="updateUser">
    UPDATE users SET username = #username# WHERE id = #id#
</update>
```

**Question Mark (?) for Positional**:

```xml
<insert id="insertUser">
    INSERT INTO users (username, email) VALUES (?, ?)
</insert>
```

### 9.3 Property Access

**Simple Properties**:

```xml
<parameter property="username"/>
```

**Nested Properties**:

```xml
<parameter property="address.city"/>
```

**Indexed Properties**:

```xml
<parameter property="items[0]"/>
```

---

## Chapter 10: Caching

### 10.1 Cache Models

SqlMap supports multiple cache model types:

**LRU (Least Recently Used)**:

```xml
<cacheModel id="userCache" type="LRU">
    <flushInterval hours="1"/>
    <property name="cacheSize" value="1000"/>
</cacheModel>
```

**FIFO (First In, First Out)**:

```xml
<cacheModel id="userCache" type="FIFO">
    <flushInterval minutes="30"/>
    <property name="cacheSize" value="500"/>
</cacheModel>
```

### 10.2 Cache Configuration

**Flush on Execute**:

```xml
<cacheModel id="userCache" type="LRU">
    <flushInterval hours="1"/>
    <flushOnExecute statement="insertUser"/>
    <flushOnExecute statement="updateUser"/>
    <flushOnExecute statement="deleteUser"/>
</cacheModel>
```

**Timed Flush**:

```xml
<cacheModel id="userCache" type="LRU">
    <flushInterval minutes="30"/>  <!-- Flush every 30 minutes -->
</cacheModel>
```

### 10.3 Cache Implementation

**Enabling Cache for Statement**:

```xml
<select id="getUser" resultMap="UserResult" cacheModel="userCache">
    SELECT * FROM users WHERE id = #value#
</select>
```

**Manual Cache Flush**:

```php
$sqlmap->flushCaches();  // Flush all caches
```

---

## Chapter 11: Type Handlers

### 11.1 Overview

Type handlers provide custom conversion between PHP types and database types.

**Built-in Type Handlers**:

- String handling
- Integer handling
- Boolean handling
- DateTime handling
- NULL handling
- Blob handling

### 11.2 Custom Type Handlers

```php
use Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandler;

class MyDateTimeHandler extends TSqlMapTypeHandler
{
    public function getParameter($value)
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        return $value;
    }
    
    public function getResult($value)
    {
        if ($value !== null) {
            return new DateTime($value);
        }
        return null;
    }
}
```

**Registering Custom Handler**:

```php
$handler = new MyDateTimeHandler();
$sqlmap->registerTypeHandler($handler);
```

**Using in XML**:

```xml
<typeHandler class="MyDateTimeHandler" type="datetime"/>
```

---

## Chapter 12: Integration with PRADO

### 12.1 Module Configuration

**Create SqlMap Module**:

```php
class SqlMapModule extends TSqlMapConfig
{
    protected function getConfigFile()
    {
        return $this->getApplication()->getBasePath() . '/protected/sqlmap.xml';
    }
}
```

**Configure in Application.xml**:

```xml
<modules>
    <module id="sqlmap" class="SqlMapModule" EnableCache="true"/>
</modules>
```

### 12.2 Service Integration

**SqlMap Service Class**:

```php
class SqlMapService
{
    private $sqlmap;
    
    public function __construct()
    {
        $config = $this->getApplication()->getModule('sqlmap');
        $this->sqlmap = $config->getSqlMapManager()->getSqlmapGateway();
    }
    
    public function getUserById($id)
    {
        return $this->sqlmap->queryForObject('getUser', $id);
    }
    
    public function getAllUsers()
    {
        return $this->sqlmap->queryForList('getAllUsers');
    }
    
    public function insertUser($data)
    {
        return $this->sqlmap->insert('insertUser', $data);
    }
}
```

---

## Chapter 13: Complete Usage Examples

### 13.1 Basic CRUD Operations

**SqlMap.xml Configuration**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sqlMap>
    <resultMap id="UserResult" class="User">
        <result property="id" column="user_id"/>
        <result property="username" column="username"/>
        <result property="email" column="email"/>
        <result property="createdAt" column="created_at"/>
    </resultMap>
    
    <select id="getUser" parameterClass="int" resultMap="UserResult">
        SELECT * FROM users WHERE user_id = #value#
    </select>
    
    <select id="getAllUsers" resultMap="UserResult">
        SELECT * FROM users ORDER BY username
    </select>
    
    <insert id="insertUser" parameterClass="User">
        INSERT INTO users (username, email, created_at)
        VALUES (#username#, #email#, #createdAt#)
    </insert>
    
    <update id="updateUser" parameterClass="User">
        UPDATE users SET
            username = #username#,
            email = #email#
        WHERE user_id = #id#
    </update>
    
    <delete id="deleteUser" parameterClass="int">
        DELETE FROM users WHERE user_id = #value#
    </delete>
</sqlMap>
```

**User Class**:

```php
class User
{
    public $id;
    public $username;
    public $email;
    public $createdAt;
}
```

**Service Usage**:

```php
class UserService
{
    private $sqlmap;
    
    public function __construct()
    {
        $config = $this->getApplication()->getModule('sqlmap');
        $this->sqlmap = $config->getSqlMapManager()->getSqlmapGateway();
    }
    
    public function getById($id)
    {
        return $this->sqlmap->queryForObject('getUser', $id);
    }
    
    public function getAll()
    {
        return $this->sqlmap->queryForList('getAllUsers');
    }
    
    public function create($data)
    {
        $user = new User();
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->createdAt = date('Y-m-d H:i:s');
        
        return $this->sqlmap->insert('insertUser', $user);
    }
    
    public function update($user)
    {
        return $this->sqlmap->update('updateUser', $user);
    }
    
    public function delete($id)
    {
        return $this->sqlmap->delete('deleteUser', $id);
    }
}
```

### 13.2 Complex Result Mapping

**Nested Objects with Relationships**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sqlMap>
    <resultMap id="OrderResult" class="Order">
        <result property="id" column="order_id"/>
        <result property="orderDate" column="order_date"/>
        <result property="customer" resultMap="CustomerResult"/>
        <result property="items" resultMap="OrderItemResult"/>
    </resultMap>
    
    <resultMap id="CustomerResult" class="Customer">
        <result property="id" column="customer_id"/>
        <result property="name" column="customer_name"/>
    </resultMap>
    
    <resultMap id="OrderItemResult" class="OrderItem">
        <result property="id" column="item_id"/>
        <result property="productName" column="product_name"/>
        <result property="quantity" column="quantity"/>
        <result property="price" column="price"/>
    </resultMap>
    
    <select id="getOrderWithDetails" parameterClass="int" resultMap="OrderResult">
        SELECT o.order_id, o.order_date,
               c.customer_id, c.customer_name,
               i.item_id, i.product_name, i.quantity, i.price
        FROM orders o
        INNER JOIN customers c ON o.customer_id = c.customer_id
        INNER JOIN order_items i ON o.order_id = i.order_id
        WHERE o.order_id = #value#
    </select>
</sqlMap>
```

### 13.3 Transaction Management

**Bank Transfer Example**:

```php
class AccountService
{
    private $sqlmap;
    private $conn;
    
    public function __construct()
    {
        $config = $this->getApplication()->getModule('sqlmap');
        $this->sqlmap = $config->getSqlMapManager()->getSqlmapGateway();
        $this->conn = $this->sqlmap->getDbConnection();
    }
    
    public function transfer($fromId, $toId, $amount)
    {
        $this->conn->beginTransaction();
        
        try {
            // Debit source account
            $debitData = new stdClass();
            $debitData->id = $fromId;
            $debitData->amount = $amount;
            $this->sqlmap->update('debitAccount', $debitData);
            
            // Credit destination account
            $creditData = new stdClass();
            $creditData->id = $toId;
            $creditData->amount = $amount;
            $this->sqlmap->update('creditAccount', $creditData);
            
            // Log transaction
            $logData = new stdClass();
            $logData->from_id = $fromId;
            $logData->to_id = $toId;
            $logData->amount = $amount;
            $logData->created_at = date('Y-m-d H:i:s');
            $this->sqlmap->insert('insertTransactionLog', $logData);
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
```

### 13.4 Caching Strategies

**Read-Heavy Caching**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<sqlMap>
    <!-- LRU cache for reference data -->
    <cacheModel id="countryCache" type="LRU">
        <flushInterval hours="12"/>
        <property name="cacheSize" value="200"/>
    </cacheModel>
    
    <!-- FIFO cache for lookup tables -->
    <cacheModel id="statusCache" type="FIFO">
        <flushInterval minutes="60"/>
        <property name="cacheSize" value="50"/>
    </cacheModel>
    
    <select id="getAllCountries" resultClass="array" cacheModel="countryCache">
        SELECT * FROM countries
    </select>
    
    <select id="getStatuses" resultClass="array" cacheModel="statusCache">
        SELECT * FROM statuses
    </select>
    
    <!-- Flush cache on updates -->
    <insert id="insertCountry" parameterClass="array">
        INSERT INTO countries (name) VALUES (#name#)
    </insert>
    
    <cacheModel id="countryCache" type="LRU">
        <flushInterval hours="12"/>
        <flushOnExecute statement="insertCountry"/>
        <flushOnExecute statement="updateCountry"/>
        <flushOnExecute statement="deleteCountry"/>
    </cacheModel>
</sqlMap>
```

---

## Chapter 14: Best Practices

### 14.1 Performance Considerations

**Use Prepared Statements**:

```xml
<!-- Good: Uses statement reuse -->
<select id="getUser" resultMap="UserResult">
    SELECT * FROM users WHERE id = #value#
</select>

<!-- Avoid: Complex dynamic SQL -->
<select id="search" resultMap="UserResult">
    SELECT * FROM users WHERE 1=1
    <isNotNull property="username">AND username LIKE '%$username$%'</isNotNull>
</select>
```

**Enable Caching for Read Operations**:

```xml
<select id="getReferenceData" resultClass="array" cacheModel="refCache">
    SELECT * FROM reference_table
</select>
```

**Use Appropriate Fetch Sizes**:

```php
// Pagination
$users = $sqlmap->queryForList('getAllUsers', null, null, 0, 100);
```

### 14.2 Security Guidelines

**Always Use Parameter Binding**:

```xml
<!-- Good: Parameter binding -->
<select id="getUser" resultMap="UserResult">
    SELECT * FROM users WHERE id = #value# AND status = #status#
</select>

<!-- Bad: String concatenation (SQL injection risk) -->
<select id="getUser" resultMap="UserResult">
    SELECT * FROM users WHERE id = $value$
</select>
```

**Validate Input at Application Layer**:

```php
public function getUserById($id)
{
    if (!is_numeric($id)) {
        throw new InvalidArgumentException('ID must be numeric');
    }
    return $this->sqlmap->queryForObject('getUser', (int) $id);
}
```

### 14.3 Code Organization

**Separate Configuration Files**:

```
protected/
  config/
    sqlmap/
      UserMap.xml
      OrderMap.xml
      ProductMap.xml
    application.sqlmap.xml
```

**Organize by Domain**:

```xml
<!-- UserMap.xml -->
<sqlMap>
    <resultMap id="UserResult" class="User">...</resultMap>
    <select id="getUser">...</select>
    <insert id="insertUser">...</insert>
</sqlMap>
```

---

## Chapter 15: Troubleshooting

### 15.1 Common Issues

**Statement Not Found**:

```
TSqlMapUndefinedException: SqlMap contains no statement 'getUser'
```

Solution: Ensure the statement is defined in your XML configuration and the configuration file is properly loaded.

**Result Map Error**:

```
TSqlMapConfigurationException: Unable to find result mapping 'UserResult'
```

Solution: Ensure the resultMap ID matches exactly and is defined before the statement that references it.

**Parameter Binding Error**:

```
PDOException: SQLSTATE[HY000]: General error: Invalid parameter index
```

Solution: Ensure parameter count matches in your parameterMap and SQL statement.

### 15.2 Debugging Techniques

**Enable SQL Logging**:

```php
// In your record or service
$sqlmap->getSqlMapManager()->attachEventHandler('OnExecuteCommand', function($sender, $param) {
    $command = $param->getCommand();
    Prado::trace('SQL: ' . $command->getText());
});
```

**Check Statement Configuration**:

```php
$statement = $manager->getMappedStatement('getUser');
echo $statement->getSql();
```

---

## Appendix A: Class Reference Summary

### TSqlMapManager

**Constructor**:

```php
public function __construct($connection = null)
```

**Methods**:

```php
public function setDbConnection($conn)
public function getDbConnection()
public function getSqlmapGateway()
public function configureXml($file)
public function getMappedStatements()
public function getMappedStatement($name)
public function addMappedStatement(IMappedStatement $statement)
public function getResultMaps()
public function getResultMap($name)
public function getParameterMaps()
public function getTypeHandlers()
public function flushCacheModels()
public function getCacheDependencies()
```

### TSqlMapGateway

**Methods**:

```php
public function getSqlMapManager()
public function getDbConnection()
public function queryForObject($statementName, $parameter = null, $result = null)
public function queryForList($statementName, $parameter = null, $result = null, $skip = -1, $max = -1)
public function queryWithRowDelegate($statementName, $delegate, $parameter = null, $result = null, $skip = -1, $max = -1)
public function queryForPagedList($statementName, $parameter = null, $pageSize = 10, $page = 0)
public function queryForPagedListWithRowDelegate($statementName, $delegate, $parameter = null, $pageSize = 10, $page = 0)
public function queryForMap($statementName, $parameter = null, $keyProperty = null, $valueProperty = null, $skip = -1, $max = -1)
public function queryForMapWithRowDelegate($statementName, $delegate, $parameter = null, $keyProperty = null, $valueProperty = null, $skip = -1, $max = -1)
public function insert($statementName, $parameter = null)
public function update($statementName, $parameter = null)
public function delete($statementName, $parameter = null)
public function flushCaches()
public function registerTypeHandler($typeHandler)
```

### TSqlMapConfig

**Properties**:

```php
public function getConfigFile()
public function setConfigFile($value)
public function setEnableCache($value)
public function getEnableCache()
public function clearCache()
public function getSqlMapManager()
```

### Statement Classes

**IMappedStatement**:

```php
public function executeQueryForObject($conn, $parameter, $result)
public function executeQueryForList($conn, $parameter, $result, $skip, $max, $delegate = null)
public function executeUpdate($conn, $parameter)
public function executeInsert($conn, $parameter)
```

**TMappedStatement**: Base implementation

**TSelectMappedStatement**: SELECT statement execution

**TInsertMappedStatement**: INSERT statement execution

**TUpdateMappedStatement**: UPDATE statement execution

**TDeleteMappedStatement**: DELETE statement execution

---

## Appendix B: XML Schema Reference

### Main Elements

| Element | Description |
|----------|-------------|
| `<sqlMap>` | Root element for all SqlMap configuration |
| `<property>` | Global property for variable substitution |
| `<typeHandler>` | Custom type handler registration |
| `<resultMap>` | Result mapping configuration |
| `<parameterMap>` | Parameter mapping configuration |
| `<cacheModel>` | Cache configuration |
| `<select>` | SELECT statement definition |
| `<insert>` | INSERT statement definition |
| `<update>` | UPDATE statement definition |
| `<delete>` | DELETE statement definition |

### Result Map Elements

| Element | Description |
|---------|-------------|
| `<result>` | Property to column mapping |
| `<discriminator>` | Conditional result mapping |
| `<resultMap>` | Nested result map reference |

### Cache Model Attributes

| Attribute | Description |
|----------|-------------|
| `id` | Unique identifier for the cache |
| `type` | Cache type: LRU, FIFO |
| `<flushInterval>` | Time between cache flushes |
| `<flushOnExecute>` | Statements that trigger cache flush |
| `<property>` | Cache-specific properties |

---

## Appendix C: Change Log

Notes:

- This manual assumes knowledge of PHP and Prado framework conventions
- Examples are illustrative; adapt code to your project conventions
- Always use parameter binding to prevent SQL injection
- Cache configuration should be tuned based on application-specific patterns