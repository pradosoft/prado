# Data/SqlMap/Configuration/TDiscriminator

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TDiscriminator`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TDiscriminator.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TDiscriminator`

Supports polymorphic result mapping based on column values.

## Description

`TDiscriminator` maps a column value to a sub-result-map for polymorphic result mapping (similar to Hibernate's discriminators).

## Key Methods

### `getMapping()`

Returns the [`TResultProperty`](./TResultProperty.md) for the discriminator column.

### `getSubMap($value)`

Returns the [`TSubMap`](./TSubMap.md) for a specific discriminator value.

## See Also

- [TResultMap](./TResultMap.md)
- [TSubMap](./TSubMap.md)

## Category

SqlMap Configuration
