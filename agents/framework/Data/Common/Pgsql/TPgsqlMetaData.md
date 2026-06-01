# Data/Common/Pgsql/TPgsqlMetaData

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Pgsql](./INDEX.md) / **`TPgsqlMetaData`**

## Class Info
**Location:** `framework/Data/Common/Pgsql/TPgsqlMetaData.php`
**Namespace:** `Prado\Data\Common\Pgsql`

## Overview
`TPgsqlMetaData` provides PostgreSQL-specific database metadata introspection. Column data is read from `pg_catalog.pg_attribute` joined with `pg_attrdef` (defaults) and `pg_type`. Sequence detection checks for an internally-dependent `pg_class` entry of type `S`.

**PHP 8 fix:** The sequence detection expression `substr($col['adsrc'] ?? '', 0, 8)` uses the null-coalescing operator to handle the `adsrc` value being `null` in PHP 8+ (previously `substr(null, ...)` issued a deprecation warning).

## Key Characteristics

- Default schema: `public`
- Identifier quoting: double-quotes `"identifier"`
- `assertIdentifier()` rejects names containing double-quote characters
- `DefaultSchema` property — configurable; defaults to `'public'`
- Detects views via `pg_catalog.pg_class.relkind = 'v'`

## See Also

- [TDbMetaData](../TDbMetaData.md) - Base class