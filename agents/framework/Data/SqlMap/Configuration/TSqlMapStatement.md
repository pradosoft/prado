# Data/SqlMap/Configuration/TSqlMapStatement

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TSqlMapStatement`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TSqlMapStatement.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TSqlMapStatement`

Base mapped statement class.

Inherits from `Prado\TComponent`.

## Description

`TSqlMapStatement` corresponds to the `<statement>` element. Mapped statements can hold any SQL statement and use Parameter Maps and Result Maps for input and output.

The `<statement>` element is a general "catch all" element for any type of SQL statement. More specific statement-type elements (`<select>`, `<insert>`, `<update>`, `<delete>`) provide better error-checking and additional functionality.

## Key Properties

### `ID`

The unique name for this statement.

### `ParameterClass`

The PHP class name for parameters (instead of using ParameterMap).

### `ParameterMap`

The name of a parameter map.

### `ResultClass`

The PHP class name for results (instead of using ResultMap).

### `ResultMap`

The name of a result map.

### `CacheModel`

The name of a cache model for statement caching.

### `SqlText`

The `TStaticSql` containing the SQL text.

### `ListClass`

A PHP class implementing `\ArrayAccess` for collection handling.

### `Extends`

Name of another statement to extend/inherit from.

## Key Methods

### `initialize($manager)`

Initializes the statement, setting result and parameter maps.

### `createInstanceOfListClass($registry)`

Creates a new instance of the list class.

### `createInstanceOfResultClass($registry, $row)`

Creates a new instance of the result class.

## See Also

- [TSqlMapSelect](./TSqlMapSelect.md)
- [TSqlMapInsert](./TSqlMapInsert.md)
- [TSqlMapUpdate](./TSqlMapUpdate.md)
- [TSqlMapDelete](./TSqlMapDelete.md)
- [TResultMap](./TResultMap.md)
- [TParameterMap](./TParameterMap.md)

## Category

SqlMap Configuration
