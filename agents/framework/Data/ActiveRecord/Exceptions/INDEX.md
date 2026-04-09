# Data/ActiveRecord/Exceptions/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md)] | ActiveRecord Directory |

## Purpose

Exception classes specific to the Active Record ORM subsystem.

## Classes

- **`TActiveRecordException`** — Base exception for all Active Record errors. Extends the framework's standard exception hierarchy; accepts a message key looked up in the Prado exception message catalog.

- **`TActiveRecordConfigurationException`** — Thrown when Active Record is improperly configured (missing `TActiveRecordManager`, invalid `TABLENAME`, misconfigured `COLUMN_MAPPING`, etc.).

## Conventions

- All AR exceptions extend `TActiveRecordException`, which in turn extends the Prado exception hierarchy.
- Active Record Exception message keys are defined in `framework/Data/Exceptions/messages.txt` and must be added there for new error codes.
- Throw `TActiveRecordConfigurationException` for setup/config problems; use `TActiveRecordException` (or a more specific subclass) for runtime data errors.
