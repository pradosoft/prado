# Data/Common/Oracle/INDEX.md - DATA_COMMON_ORACLE_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Oracle driver-specific implementations of the database metadata and query-builder abstractions.

## Classes

- **`TOracleMetaData`** — Extends `TDbMetaData`. Queries Oracle data dictionary views (`ALL_COLUMNS`, `ALL_CONSTRAINTS`, `ALL_CONS_COLUMNS`) to build table/column metadata. Handles Oracle's uppercase-by-default identifiers.

- **`TOracleTableInfo`** — Extends `TDbTableInfo`. Stores Oracle-specific table metadata; schema/owner name is tracked separately.

- **`TOracleTableColumn`** — Extends `TDbTableColumn`. Maps Oracle types (`NUMBER`, `VARCHAR2`, `CLOB`, `BLOB`, `DATE`, `TIMESTAMP`) to generic PHP types.

- **`TOracleCommandBuilder`** — Extends `TDbCommandBuilder`. Generates Oracle dialect: double-quote `"IDENTIFIER"` quoting for case-sensitive names, `ROWNUM` or `FETCH FIRST` for row limiting, sequence-based insert ID retrieval.

## Conventions

- Oracle identifiers are uppercase by default; quoted identifiers preserve case.
- There is no native `AUTO_INCREMENT`; sequences + triggers are used for surrogate keys.
- `DATE` in Oracle includes time components (unlike SQL standard); use `TIMESTAMP` for explicit time precision.
