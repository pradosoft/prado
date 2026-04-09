# TSimpleDynamicParser

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TSimpleDynamicParser](./TSimpleDynamicParser.md)

`Prado\Data\SqlMap\Configuration\TSimpleDynamicParser`

Handles `$propertyName$` direct string substitution in SQL.

## Description

`TSimpleDynamicParser` handles `$propertyName$` direct string substitution (not parameterized) in SQL. **Warning:** This is unsafe with user input as it doesn't use parameter binding.

## Difference from Inline Parameters

| Syntax | Type | Safety |
|--------|------|--------|
| `#param#` | Parameterized | Safe |
| `$param$` | String substitution | Unsafe with user input |

## See Also

- [TSqlMapXmlMappingConfiguration](./TSqlMapXmlMappingConfiguration.md)

## Category

SqlMap Configuration
