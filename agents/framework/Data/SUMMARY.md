# Data/SUMMARY.md

Database access layer providing PDO wrapper plus three complementary data-access patterns: raw commands, stateless table gateway, and XML-based SQL mapping. The Active Record ORM is the `pradosoft/prado-activerecord` package.

SqlMap result construction reports type-handler failures through contextual `TSqlMapException` instances.

## Classes

- **`TDbConnection`** — PDO wrapper; properties: `ConnectionString`, `Username`, `Password`, `Charset`, `Attributes`; methods: `open()`, `close()`, `createCommand($sql)`, `beginTransaction()`, `quoteTableName()`, `quoteColumnName()`.

- **`TDbCommand`** — Wraps prepared PDO statement; methods: `execute()`, `query()`, `queryRow()`, `queryColumn()`, `queryScalar()`, `bindParameter()`, `bindValue()`.

- **`TDbDataReader`** — Iterator over PDO result set; methods: `read()`, `readAll()`, `nextResult()`; implements `Iterator` and `Countable`.

- **`TDbTransaction`** — Transaction wrapper; methods: `commit()`, `rollBack()`; property: `Active`.

- **`TDataSourceConfig`** — Configuration holder for connection pooling and datasource settings.

- **`TDbColumnCaseMode`** — Enum: `Preserved`, `LowerCase`, `UpperCase`.

- **`TDbNullConversionMode`** — Enum: `Preserved`, `EmptyStringToNull`, `NullToEmptyString`.
