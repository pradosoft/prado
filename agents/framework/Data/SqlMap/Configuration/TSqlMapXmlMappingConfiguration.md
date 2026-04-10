# Data/SqlMap/Configuration/TSqlMapXmlMappingConfiguration

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TSqlMapXmlMappingConfiguration`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TSqlMapXmlMappingConfiguration.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TSqlMapXmlMappingConfiguration`

Parses individual SQL mapping XML files.

Inherits from [`TSqlMapXmlConfigBuilder`](./TSqlMapXmlConfigBuilder.md).

## Description

`TSqlMapXmlMappingConfiguration` parses individual SQL mapping XML files. It processes `<select>`, `<insert>`, `<update>`, `<delete>`, `<parameterMap>`, `<resultMap>`, and `<cacheModel>` elements.

## Key Methods

### `configure($filename)`

Configures an XML mapping file.

## Inline Parameter Syntax

| Syntax | Description |
|--------|-------------|
| `#propertyName#` | Parameterized binding (safe) |
| `$propertyName$` | String substitution (unsafe, avoid user input) |

## See Also

- [TSqlMapXmlConfigBuilder](./TSqlMapXmlConfigBuilder.md)
- [TSqlMapXmlConfiguration](./TSqlMapXmlConfiguration.md)

## Category

SqlMap Configuration
