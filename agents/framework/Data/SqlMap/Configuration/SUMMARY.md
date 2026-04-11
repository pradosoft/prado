# Data/SqlMap/Configuration/SUMMARY.md

XML configuration parsing and in-memory object model for SqlMap mapped statements, parameter maps, result maps, and cache models.

## Classes

- **`TSqlMapXmlConfiguration`** — Parses top-level SqlMap XML config file (`sqlmap.xml`).

- **`TSqlMapXmlConfigBuilder`** — Builder processing parsed XML DOM into live `TSqlMapManager` state.

- **`TSqlMapXmlMappingConfiguration`** — Parses individual SQL mapping XML files; processes `<select>`, `<insert>`, `<update>`, `<delete>`, `<parameterMap>`, `<resultMap>`, `<cacheModel>` elements.

- **`TSqlMapStatement`** — Base mapped statement; properties: `Id`, `ParameterClass`, `ResultClass`, `ParameterMap`, `ResultMap`.

- **`TSqlMapSelect`** / **`TSqlMapInsert`** / **`TSqlMapUpdate`** / **`TSqlMapDelete`** — Statement type classes.

- **`TSqlMapSelectKey`** — Nested `<selectKey>` inside insert; supports pre/post insert using query or sequence.

- **`TParameterMap`** — Named parameter map defining ordered/named parameter properties and type handlers.

- **`TParameterProperty`** — One entry in parameter map: property name, column name, type handler, jdbc type, null value.

- **`TResultMap`** — Named result map defining how result row is mapped back to PHP object or array; supports inheritance, discriminators, nested result maps.

- **`TResultProperty`** — One column-to-property mapping: column name/index, property name, type handler, lazy-load setting, nested select statement.

- **`TDiscriminator`** — Maps column value to sub-result-map for polymorphic result mapping.

- **`TSubMap`** — Discriminator sub-map entry: value → result map name.

- **`TSqlMapCacheModel`** — Cache configuration for statement; properties: `Type` (`LRU`, `FIFO`, `OSCache`, `MemoryCache`), `FlushInterval`, `Size`.

- **`TSqlMapCacheTypes`** — Enum-like class listing valid cache type strings.

- **`TSqlMapCacheKey`** — Generates unique cache key from statement ID + parameter object hash.

- **`TInlineParameterMapParser`** — Parses inline `#propertyName#` parameter syntax into `TParameterMap` entries.

- **`TSimpleDynamicParser`** — Handles `$propertyName$` direct string substitution in SQL.
