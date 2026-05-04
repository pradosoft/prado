# Data/SqlMap/Configuration/TSqlMapXmlConfigBuilder

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TSqlMapXmlConfigBuilder`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TSqlMapXmlConfigBuilder.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TSqlMapXmlConfigBuilder`

Abstract base builder for parsing SqlMap XML configuration.

## Description

`TSqlMapXmlConfigBuilder` is the abstract base class that processes the parsed XML DOM into live `TSqlMapManager` state.

## Key Methods

### `createObjectFromNode($node)`

Creates an object from a node's `class` attribute and sets its properties.

### `setObjectPropFromNode($obj, $node, $except)`

Sets object properties from XML node attributes.

### `getAbsoluteFilePath($basefile, $resource)`

Resolves a relative file path.

## See Also

- [TSqlMapXmlConfiguration](./TSqlMapXmlConfiguration.md)
- [TSqlMapXmlMappingConfiguration](./TSqlMapXmlMappingConfiguration.md)

## Category

SqlMap Configuration
