# TSqlMapXmlConfiguration

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TSqlMapXmlConfiguration](./TSqlMapXmlConfiguration.md)

`Prado\Data\SqlMap\Configuration\TSqlMapXmlConfiguration`

Parses the main SqlMap XML configuration file.

Inherits from [`TSqlMapXmlConfigBuilder`](./TSqlMapXmlConfigBuilder.md).

## Description

`TSqlMapXmlConfiguration` parses the top-level SqlMap XML configuration file (`sqlmap.xml`). It registers data sources, type handlers, and triggers loading of all `<sqlMap>` resource files.

## Key Methods

### `configure($filename)`

Configures the `TSqlMapManager` using the given XML configuration file.

## See Also

- [TSqlMapXmlConfigBuilder](./TSqlMapXmlConfigBuilder.md)
- [TSqlMapXmlMappingConfiguration](./TSqlMapXmlMappingConfiguration.md)

## Category

SqlMap Configuration
