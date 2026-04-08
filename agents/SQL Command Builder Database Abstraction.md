# Data.Common Manual for Database Command Building

This manual documents the complete Data.Common components used for building and executing database commands within the Prado framework. It provides comprehensive architecture context, detailed functionality descriptions, and practical examples for all components including the core classes, provider-specific implementations, and database selection mechanisms.

## Table of Contents
- [Chapter 1: Introduction](#chapter-1-introduction)
- [Chapter 2: Architecture Overview](#chapter-2-architecture-overview)
- [Chapter 3: Core Interfaces and Classes](#chapter-3-core-interfaces-and-classes)
- [Chapter 4: Building Commands](#chapter-4-building-commands)
- [Chapter 5: Parameter Handling](#chapter-5-parameter-handling)
- [Chapter 6: Command Builders](#chapter-6-command-builders)
- [Chapter 7: Data Adapters and Execution](#chapter-7-data-adapters-and-execution)
- [Chapter 8: Transactions and Batching](#chapter-8-transactions-and-batching)
- [Chapter 9: Database Selection and Provider Routing](#chapter-9-database-selection-and-provider-routing)
- [Chapter 10: Testing, Debugging, and Quality](#chapter-10-testing-debugging-and-quality)
- [Chapter 11: Practical Examples](#chapter-11-practical-examples)
- [Chapter 12: Extending Data.Common](#chapter-12-extending-datacommon)
- [Chapter 13: Best Practices](#chapter-13-best-practices)
- [Chapter 14: Troubleshooting](#chapter-14-troubleshooting)
- [Appendix A: Glossary](#appendix-a-glossary)
- [Appendix B: Change Log](#appendix-b-change-log)
- [Appendix C: Provider Overview](#appendix-c-provider-overview)

---

## Chapter 1: Introduction

This chapter provides purpose, scope, and intended audience for Data.Common command building components.

### Purpose
The Data.Common library supplies a consistent, testable path to **create database commands that are portable across supported providers** (MySQL, PostgreSQL, SQLite, MSSQL, Oracle).

### Scope
The scope focuses on command text generation, parameter binding, and execution orchestration, independent of UI or business logic and database providers.

### Audience
This manual targets framework developers implementing new providers or consumers building on top of Data.Common.

### Key Concepts
- Command: a structured representation of a database operation (text, parameters, type).
- Parameter: a value passed to a command to customize behavior without string concatenation.
- Provider: a database-specific implementation (MySQL, MSSQL, PostgreSQL, SQLite, Oracle).
- Metadata: information about database schema and structure that allows for provider-specific command building.

---

## Chapter 2: Architecture Overview

The Data.Common command system is designed with clear separation of concerns:

- Command text generation is provider-agnostic where possible.
- Parameters are typed and escaped according to provider rules.
- Execution is mediated through a data connection abstraction, enabling unit testing with mocks.

### Core Components
- `TDbCommandBuilder`: Base class for building database commands
- `TDbTableInfo`/`TDbTableColumn`: Metadata containers for database schemas
- `TDbMetaData`: Abstract base for retrieving database-specific metadata
- `TDbCommand`: Core command with parameter bindings
- Provider-specific implementations: `TMysqlCommandBuilder`, `TPgsqlCommandBuilder`, etc.

### Integration Flow
1. A `TDataSourceConfig` specifies database connection details including driver name
2. A `TDbConnection` is created using the data source
3. `TDbMetaData::getInstance($connection)` selects provider-specific metadata class
4. `TDbMetaData::createCommandBuilder()` creates appropriate builder for database dialect
5. Builders generate appropriate SQL text using metadata information
6. `TDbCommand` handles parameter binding and execution via PDO

---

## Chapter 3: Core Interfaces and Classes

### Main Abstract Classes

- `TDbCommandBuilder` — Base class for constructing database commands with common operations like limit/offset, ordering, and parameter handling
- `TDbTableInfo` — Container for table metadata including columns, primary keys, and foreign keys
- `TDbTableColumn` — Container for column-specific metadata including type, nullability, and constraints
- `TDbMetaData` — Abstract base for database metadata retrieval

### Command Objects
- `TDbCommand` — Encapsulates command text, parameters, and execution details with methods for binding and execution.

### Core Functionalities

#### TDbCommandBuilder Methods
```php
// Basic operations
public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1, $select = '*')
public function createCountCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1)
public function createDeleteCommand($where, $parameters = [])
public function createInsertCommand($data)
public function createUpdateCommand($data, $where, $parameters = [])
```

#### TDbTableInfo Properties
```php
public function getTableName()        // Table name
public function getTableFullName()     // Full quoted table name
public function getColumns()           // All columns
public function getPrimaryKeys()       // Primary key column names
public function getForeignKeys()       // Foreign key details
```

#### TDbMetaData Methods
```php
public static function getInstance($conn)      // Factory to get provider-specific meta
public function getTableInfo($tableName = null)  // Get table metadata
public function createCommandBuilder($tableName = null)  // Create builder for table
```

---

## Chapter 4: Building Commands

Patterns for building commands reliably and safely:
- Always use parameterized queries; never concatenate user input into command text.
- Abstract provider differences behind a common interface.
- Compose complex statements with modular builders or templates.
- Leverage metadata for accurate schema-aware SQL generation.

### Step-by-Step Guide
1. Identify operation type (SELECT/INSERT/UPDATE/DELETE)
2. Create metadata instance with TDbMetaData::getInstance()
3. Get appropriate command builder for table
4. Use builder methods to construct the command
5. Bind parameters via TDbCommand methods
6. Validate command before execution

### Example: Basic Command Construction
```php
// Using TDataSourceConfig to establish connection
$config = new TDataSourceConfig();
$config->setConnectionString('mysql:host=localhost;dbname=testdb');
$config->setUsername('user');
$config->setPassword('password');
$config->setDriver('mysql');

$connection = new TDbConnection($config);
$connection->setActive(true);

// Get metadata and builder for a table
$metadata = TDbMetaData::getInstance($connection);
$builder = $metadata->createCommandBuilder('users');

// Create a SELECT command using the builder
$command = $builder->createFindCommand(
    'active = :active', 
    ['active' => true], 
    ['name' => 'ASC'],
    10,
    0
);
// Parameters are bound to the TDbCommand using standard bindValue method
```

### Command Building Methods
```php
// Create commands for different operations
// Find command - typically builds SELECT statements
$findCommand = $builder->createFindCommand(
    'status = :status AND created > :date', 
    ['status' => 'active', 'date' => '2024-01-01'],
    ['created' => 'DESC']
);

// Count command - builds COUNT(*) statements
$countCommand = $builder->createCountCommand(
    'region = :region', 
    ['region' => 'north']
);

// Delete command
$deleteCommand = $builder->createDeleteCommand(
    'last_login < :cutoff', 
    ['cutoff' => '2023-01-01']
);

// Insert command
$insertCommand = $builder->createInsertCommand([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'created' => new DateTime()
]);

// Update command
$updateCommand = $builder->createUpdateCommand(
    ['name' => 'Alice Smith', 'updated' => new DateTime()],
    'id = :id',
    ['id' => 42]
);
```

---

## Chapter 5: Parameter Handling

Parameters are integral to safe command execution. The Data.Common system supports both named and positional binding while leveraging PDO's native type handling.

### Binding Methods
- `TDbCommand::bindValue($name, $value, $type)` - Binds a value with explicit type
- `TDbCommand::bindParameter($name, $value, $type)` - Binds a variable reference as parameter

### Parameter Types Mapping
```php
// Direct PDO type mapping used by Data.Common
PDO::PARAM_BOOL    // Boolean values
PDO::PARAM_NULL    // NULL values  
PDO::PARAM_INT     // Integer values
PDO::PARAM_STR     // String values
PDO::PARAM_LOB     // Blob values
```

### Type Resolution Functions
```php
// TDbCommandBuilder::getPdoType() - automatic type resolution
class TDbCommandBuilder extends \Prado\TComponent {
    public static function getPdoType($value) {
        switch (gettype($value)) {
            case 'boolean': return PDO::PARAM_BOOL;
            case 'integer': return PDO::PARAM_INT;
            case 'string': return PDO::PARAM_STR;
            case 'NULL': return PDO::PARAM_NULL;
        }
        return null; // Default to PDO's automatic handling
    }
}
```

### Parameter Binding Examples
```php
// Using TDbCommand with standard binding
$command = new TDbCommand($connection, "SELECT * FROM users WHERE id = :id");

// Direct parameter binding (standard PDO approach)
$command->bindValue(':id', 42, PDO::PARAM_INT);
$command->bindValue(':name', 'Alice', PDO::PARAM_STR);

// Auto-type binding (if you prefer)
$command->bindValue(':active', true); // Automatically detects as boolean
$command->bindValue(':score', 95.5);   // Automatically detects as float/string
```

---

## Chapter 6: Command Builders

The Command Builder system is the core of how Data.Common translates abstract commands into database-specific SQL statements. Each database provider has its own implementation to handle dialect-specific requirements.

### Abstract Builder Interface
```php
abstract class TDbCommandBuilder extends \Prado\TComponent {
    // Base methods
    public function createFindCommand()        // SELECT statements
    public function createCountCommand()       // COUNT(*) statements  
    public function createDeleteCommand()      // DELETE statements
    public function createInsertCommand()      // INSERT statements
    public function createUpdateCommand()      // UPDATE statements
    
    // Utility methods
    public function applyLimitOffset($sql, $limit, $offset)
    public function applyOrdering($sql, $ordering)
    public function bindColumnValues($command, $values)
    public function bindArrayValues($command, $values)
    public function getSelectFieldList($data = '*')
}
```

### Provider-Specific Builders
- `TMysqlCommandBuilder` - MySQL dialect and parameter conventions
- `TMssqlCommandBuilder` - T-SQL nuances and parameter syntax
- `TPgsqlCommandBuilder` - PostgreSQL specifics
- `TOracleCommandBuilder` - Oracle quirks and bind variables  
- `TSqliteCommandBuilder` - SQLite specifics

---

## Chapter 7: Data Adapters and Execution

The execution flow requires a data connection abstraction that can run commands and return results.

### Core Objects
- `TDbConnection` — Manages connection lifecycle and PDO instances
- `TDbCommand` — Carries SQL text and parameters for execution
- `TDbDataReader` — Handles result set traversal and mapping to PHP objects

### Execution Flow
```php
// 1. Open connection via TDbConnection
$connection->setActive(true);

// 2. Prepare TDbCommand with text and parameters
$command = new TDbCommand($connection, "SELECT * FROM users WHERE active = :active");
$command->bindValue(':active', true, PDO::PARAM_BOOL);

// 3. Execute and fetch results
$reader = $command->query(); // Returns TDbDataReader for result sets
$count = $command->execute(); // Returns row count for non-query statements

// 4. Process results
while ($row = $reader->read()) {
    // Process each row...
}

// 5. Cleanup
$reader->close();
```

### Result Handling
```php
// Query result reading
$command = new TDbCommand($connection, "SELECT id, name FROM users");
$reader = $command->query();

// Get all rows
$rows = $reader->readAll();

// Read single row
$row = $reader->read(); // Single row as associative array

// Get scalar value (first column of first row)
$value = $command->queryScalar();

// Close after use
$reader->close();
```

---

## Chapter 8: Transactions and Batching

Transactions ensure data integrity and batch operations reduce database round trips.

### Transaction Support
```php
// Basic transaction pattern
try {
    $connection->beginTransaction();
    
    $command1 = new TDbCommand($connection, "UPDATE users SET last_login = NOW() WHERE id = :id");
    $command1->bindValue(':id', 42);
    $command1->execute();
    
    $command2 = $connection->createCommand("INSERT INTO log VALUES (:msg)");
    $command2->bindValue(':msg', 'User logged in');
    $command2->execute();
    
    $connection->commit();
} catch (Exception $e) {
    $connection->rollback();
    throw $e;
}
```

### Batch Operations
```php
// Example of batch inserting records
$batchSize = 1000;
$records = [];

// Collect records
for ($i = 0; $i < 5000; $i++) {
    $records[] = [
        'name' => 'User ' . $i,
        'email' => 'user' . $i . '@example.com'
    ];
    
    // When batch is full, execute
    if (count($records) >= $batchSize) {
        $builder = $metadata->createCommandBuilder('users');
        
        foreach ($records as $record) {
            $command = $builder->createInsertCommand($record);
            $command->execute();
        }
        
        $records = []; // Reset batch
    }
}
```

---

## Chapter 9: Database Selection and Provider Routing

The system automatically selects and routes to the correct database provider based on configuration.

### TDataSourceConfig Based Routing
```php
// Configuration example
$config = new TDataSourceConfig();
$config->setConnectionString('mysql:host=localhost;dbname=testdb');
$config->setDriver('mysql');
$config->setUsername('user');
$config->setPassword('pass');

// The system automatically selects the correct provider
$connection = new TDbConnection($config);
$connection->setActive(true);

// TDbMetaData::getInstance selects the correct provider
$metadata = TDbMetaData::getInstance($connection);
// Returns TMysqlMetaData for MySQL connections
```

### Provider Selection Logic
1. `TDataSourceConfig` specifies driver type in connection string
2. `TDbMetaData::getInstance($connection)` examines PDO driver name
3. Driver name mapping:
   - 'mysql', 'mysqli' → `TMysqlMetaData`
   - 'pgsql' → `TPgsqlMetaData`
   - 'sqlite', 'sqlite2' → `TSqliteMetaData`
   - 'mssql', 'sqlsrv', 'dblib' → `TMssqlMetaData`
   - 'oci' → `TOracleMetaData`
4. Factory creates appropriate metadata subclass
5. Metadata object's `createCommandBuilder()` creates builder for specific dialect

### Complete Integration Example
```php
// Setup data source
$config = new TDataSourceConfig();
$config->setConnectionString('mysql:host=localhost;dbname=testdb');
$config->setDriver('mysql');
$config->setUsername('user');
$config->setPassword('pass');

// Create connection
$connection = new TDbConnection($config);
$connection->setActive(true);

// Get database-specific metadata  
$metadata = TDbMetaData::getInstance($connection);

// Create builder for specific table
$builder = $metadata->createCommandBuilder('users');

// Generate commands using correct dialect
$findCommand = $builder->createFindCommand('active = :active', ['active' => true]);
$insertCommand = $builder->createInsertCommand([
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);

// Execute with proper parameter handling
$findCommand->execute();
$insertCommand->execute();
```

---

## Chapter 10: Testing, Debugging, and Quality

Tests ensure command builders produce correct SQL and parameters across providers.

### Testing Components
1. Unit tests for builders validate SQL generation
2. Integration tests with real databases validate execution
3. Mock connection tests validate parameter handling
4. Performance tests validate efficiency

### Debugging Helpers
```php
// Debug SQL generation
$command = $builder->createFindCommand('id = :id', ['id' => 42]);
echo $command->getText(); // View generated SQL

// Enable debug output
// In debug mode, TDbCommand can output detailed SQL traces
```

### Quality Metrics
- SQL syntax validation using database engine
- Parameter consistency checking
- Performance benchmarking
- Cross-database compatibility verification

---

## Chapter 11: Practical Examples

### Example A: Complete User Management System
```php
class UserManager {
    private $connection;
    private $builder;
    
    public function __construct($connection) {
        $this->connection = $connection;
        $metadata = TDbMetaData::getInstance($connection);
        $this->builder = $metadata->createCommandBuilder('users');
    }
    
    public function findActiveUsers($limit = 10) {
        return $this->builder->createFindCommand(
            'active = :active', 
            ['active' => true],
            ['created' => 'DESC'], 
            $limit
        );
    }
    
    public function updateUserStatus($userId, $status) {
        return $this->builder->createUpdateCommand(
            ['status' => $status, 'updated' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $userId]
        );
    }
    
    public function createUser($data) {
        $data['created'] = date('Y-m-d H:i:s');
        return $this->builder->createInsertCommand($data);
    }
}
```

### Example B: Complex Search with Multiple Conditions
```php
function advancedSearch($criteria) {
    $connection = new TDbConnection($config);
    $connection->setActive(true);
    
    $metadata = TDbMetaData::getInstance($connection);
    $builder = $metadata->createCommandBuilder('products');
    
    $conditions = [];
    $parameters = [];
    
    if (!empty($criteria['category'])) {
        $conditions[] = 'category = :category';
        $parameters['category'] = $criteria['category'];
    }
    
    if (!empty($criteria['min_price'])) {
        $conditions[] = 'price >= :min_price';
        $parameters['min_price'] = $criteria['min_price'];
    }
    
    if (!empty($criteria['created_after'])) {
        $conditions[] = 'created >= :created_after';
        $parameters['created_after'] = $criteria['created_after'];
    }
    
    $where = implode(' AND ', $conditions);
    
    return $builder->createFindCommand(
        $where,
        $parameters,
        ['created' => 'DESC'],
        $criteria['limit'] ?? 20,
        $criteria['offset'] ?? 0
    );
}
```

### Example C: Data Migration Pattern
```php
function migrateData($sourceTable, $targetTable) {
    $connection = new TDbConnection($config);
    $connection->setActive(true);
    
    // Get source metadata and builder
    $sourceMetadata = TDbMetaData::getInstance($connection);
    $sourceBuilder = $sourceMetadata->createCommandBuilder($sourceTable);
    
    // Get all records from source
    $selectCommand = $sourceBuilder->createFindCommand('1=1');
    $reader = $selectCommand->query();
    
    // Create target builder for destination
    $targetMetadata = TDbMetaData::getInstance($connection);
    $targetBuilder = $targetMetadata->createCommandBuilder($targetTable);
    
    // Process records in batches and insert into target
    while ($row = $reader->read()) {
        $insertCommand = $targetBuilder->createInsertCommand($row);
        $insertCommand->execute();
    }
    
    $reader->close();
}
```

---

## Chapter 12: Extending Data.Common

### Creating New Providers
To add a new database provider:

1. Create metadata class extending `TDbMetaData`
2. Create table info class extending `TDbTableInfo` 
3. Create table column class extending `TDbTableColumn`
4. Create command builder class extending `TDbCommandBuilder`
5. Add driver mapping in `TDbMetaData::getInstance`

### Code Implementation Example
```php
class TNewDatabaseMetaData extends TDbMetaData {
    protected function getTableInfoClass() {
        return 'TNewDatabaseTableInfo';
    }
    
    protected function getTableColumnClass() {
        return 'TNewDatabaseTableColumn';
    }
    
    // Override database-specific methods
    protected function createTableInfo($table) {
        // Implementation specific to new database
    }
    
    public function findTableNames($schema = '') {
        // Implementation specific to new database
    }
}
```

### Provider Registration
In `TDbMetaData::getInstance()`, add mapping:
```php
switch (strtolower($driver)) {
    case 'newdb':
        return new TNewDatabaseMetaData($conn);
    // ... other cases
}
```

---

## Chapter 13: Best Practices

Key recommendations for maintainable and secure command-building:
1. Always use parameterized queries
2. Keep command text provider-agnostic when possible
3. Validate parameter presence before execution
4. Prefer explicit types for parameters
5. Centralize provider-specific logic to facilitate maintenance
6. Use the built-in metadata and builder system rather than custom SQL
7. Handle transactions appropriately for data integrity
8. Leverage the framework's abstraction to support multiple database backends

### Code Style Recommendations
```php
// Good - use the framework builders
$builder = $metadata->createCommandBuilder('users');
$command = $builder->createFindCommand('active = :active', ['active' => true]);

// Avoid - direct SQL construction  
$command = new TDbCommand($connection, "SELECT * FROM users WHERE active = " . $active);
```

---

## Chapter 14: Troubleshooting

### Common Issues
- **Mismatched parameter names**: Check that parameter names match exactly between binding and SQL
- **Incorrect type binding**: Ensure parameter types match database expectations
- **Provider-specific syntax errors**: Use appropriate builder classes for each provider
- **Connection lifecycle mismanagement**: Always set connection as active before use

### Quick Diagnostics
- Inspect generated SQL against expected templates using `TDbCommand->getText()`
- Verify parameter collections contain all required values by debugging output
- Enable verbose SQL logging in the data layer
- Check database-specific requirements like schema quoting in different providers

### Performance Issues
- For large result sets, use limit/offset
- Consider batch operations for bulk inserts/updates
- Profile SQL generation and execution times
- Avoid SELECT * in favor of specific column selections

---

## Appendix A: Glossary

- Command: A representation of a database operation with text and parameters.
- Parameter: A value bound to a command to customize behavior safely.
- Provider: A database-specific library that understands dialects and binding.
- Builder: A component that translates an abstract command into provider-specific SQL.
- Metadata: Schema information about database tables, columns, and constraints.
- Connection: An abstraction managing database connections through PDO.
- TableInfo: Container holding metadata about a database table structure.
- TableColumn: Container for column-specific metadata including type and constraints.

---

## Appendix B: Change Log

- 4.3.3: Added comprehensive Data.Common command-building manual.
- 4.3.2: Minor adjustments and clarifications.
- 4.3.1: Initial Data.Common command documentation release.

---

## Appendix C: Provider Overview

### Provider-Specific Implementations

The Data.Common framework ships with provider-specific implementations under the Data.Common namespace.

#### Core Provider Components

- **MySQL Provider**
  - `TMysqlCommandBuilder`: Extends `TDbCommandBuilder` to handle MySQL dialect
  - `TMysqlTableInfo`: Manages MySQL table metadata with quoted names and schema support 
  - `TMysqlTableColumn`: Maps MySQL column types to PHP types, handles auto-increment detection
  - `TMysqlMetaData`: Retrieves MySQL metadata using SHOW statements and INFORMATION_SCHEMA

- **PostgreSQL Provider**
  - `TPgsqlCommandBuilder`: Extends `TDbCommandBuilder` for PostgreSQL dialect
  - `TPgsqlTableInfo`: Manages PostgreSQL table metadata 
  - `TPgsqlTableColumn`: Maps PostgreSQL column types to PHP types
  - `TPgsqlMetaData`: Retrieves PostgreSQL metadata

- **SQLite Provider**
  - `TSqliteCommandBuilder`: Extends `TDbCommandBuilder` for SQLite dialect
  - `TSqliteTableInfo`: Manages SQLite table metadata
  - `TSqliteTableColumn`: Maps SQLite column types to PHP types
  - `TSqliteMetaData`: Retrieves SQLite metadata

- **MSSQL Provider**
  - `TMssqlCommandBuilder`: Extends `TDbCommandBuilder` for T-SQL dialect
  - `TMssqlTableInfo`: Manages MSSQL table metadata
  - `TMssqlTableColumn`: Maps MSSQL column types to PHP types
  - `TMssqlMetaData`: Retrieves MSSQL metadata

- **Oracle Provider**
  - `TOracleCommandBuilder`: Extends `TDbCommandBuilder` for Oracle dialect
  - `TOracleTableInfo`: Manages Oracle table metadata  
  - `TOracleTableColumn`: Maps Oracle column types to PHP types
  - `TOracleMetaData`: Retrieves Oracle metadata

### Provider Selection Logic

The system automatically selects and maps to the correct provider based on the database driver in the connection string:

1. Driver name extracted from `PDO::ATTR_DRIVER_NAME` 
2. Mapping to metadata class:
   - 'mysql', 'mysqli' → `TMysqlMetaData`
   - 'pgsql' → `TPgsqlMetaData`
   - 'sqlite', 'sqlite2' → `TSqliteMetaData`
   - 'mssql', 'sqlsrv', 'dblib' → `TMssqlMetaData`
   - 'oci' → `TOracleMetaData`
3. Factory class `TDbMetaData::getInstance()` returns appropriate instance
4. `createCommandBuilder()` creates specific provider builder

### Integration and Usage

The framework completely abstracts provider differences:

```php
// Configuration example
$config = new TDataSourceConfig();
$config->setConnectionString('mysql:host=localhost;dbname=mydb');
$config->setDriver('mysql');
$config->setUsername('user');
$config->setPassword('pass');

$connection = new TDbConnection($config);
$connection->setActive(true);

// Framework automatically selects correct builder
$metadata = TDbMetaData::getInstance($connection);
$builder = $metadata->createCommandBuilder('my_table');

// Generated SQL will be MySQL-specific
$command = $builder->createFindCommand('id = :id', ['id' => 1]);
```

---

Notes:
- This manual assumes knowledge of PHP and the Prado Data layer conventions.
- The examples are illustrative; adapt code to your project conventions and version of PHP.