# SUMMARY.md

Relationship implementations for Active Record ORM — HasOne, HasMany, BelongsTo, and many-to-many association loading.

## Classes

- **`TActiveRecordRelation`** — Abstract base for all relation types; holds `TActiveRecordRelationContext` and criteria object.

- **`TActiveRecordRelationContext`** — Carries metadata: source record, property being populated, relation definition.

- **`TActiveRecordHasOne`** — Loads single associated record where foreign key lives in related table (one-to-one).

- **`TActiveRecordHasMany`** — Loads collection of related records where FK is on related table's side (one-to-many).

- **`TActiveRecordHasManyAssociation`** — Loads many-to-many collection via intermediate join/association table.

- **`TActiveRecordBelongsTo`** — Loads "parent" record where FK lives on source table (many-to-one).
