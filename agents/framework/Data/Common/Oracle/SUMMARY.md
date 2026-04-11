# Data/Common/Oracle/SUMMARY.md

Oracle driver-specific implementations of database metadata and query-builder abstractions.

## Classes

- **`TOracleMetaData`** — Extends `TDbMetaData`; queries Oracle data dictionary views (`ALL_COLUMNS`, `ALL_CONSTRAINTS`, `ALL_CONS_COLUMNS`); handles uppercase-by-default identifiers.

- **`TOracleTableInfo`** — Extends `TDbTableInfo`; stores Oracle-specific table metadata; schema/owner name tracked separately.

- **`TOracleTableColumn`** — Extends `TDbTableColumn`; maps Oracle types (`NUMBER`, `VARCHAR2`, `CLOB`, `BLOB`, `DATE`, `TIMESTAMP`) to generic PHP types.

- **`TOracleCommandBuilder`** — Extends `TDbCommandBuilder`; generates Oracle dialect with double-quote quoting for case-sensitive names, `ROWNUM` or `FETCH FIRST`, sequence-based insert ID.
