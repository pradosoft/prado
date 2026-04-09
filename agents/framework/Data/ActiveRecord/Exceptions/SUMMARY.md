# SUMMARY.md

Exception classes specific to the Active Record ORM subsystem.

## Classes

- **`TActiveRecordException`** — Base exception for all Active Record errors; extends Prado exception hierarchy; accepts message key looked up in exception catalog.

- **`TActiveRecordConfigurationException`** — Thrown when Active Record is improperly configured (missing manager, invalid `TABLENAME`, misconfigured `COLUMN_MAPPING`).
