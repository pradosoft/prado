# Data/ActiveRecord/Scaffold/INDEX.md - DATA_ACTIVERECORD_SCAFFOLD_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Auto-generated CRUD UI controls for Active Record classes. Scaffold views introspect table metadata to produce list, search, and edit interfaces with zero hand-written template code.

## Classes

- **`TScaffoldBase`** — Abstract base extending `TTemplateControl`. Common properties: `RecordClass` (AR class name), `ConnectionID`. Publishes the default `style.css` during `onPreRender`. Retrieves `TDbTableInfo` metadata for the target AR class.

- **`TScaffoldView`** — Composite control combining a `TScaffoldListView` and a `TScaffoldEditView` in a single panel. Wires the list's selection to the edit view automatically.

- **`TScaffoldListView`** — Renders a data grid of all records. Properties: `PageSize`, `SortField`, `SortOrder`. Raises `OnRecordSelected` when the user chooses a row. Supports paging and column sorting.

- **`TScaffoldEditView`** — Renders a form for creating or editing a single record. Properties: `RecordPk` (when editing an existing row). Delegates input control creation to the driver-specific `InputBuilder`. Handles `save` and `delete` postbacks.

- **`TScaffoldSearch`** — Optional search/filter bar that can be wired to a `TScaffoldListView` to add a WHERE clause.

- **`IScaffoldEditRenderer`** — Interface for custom edit view renderers. Implement this to replace the default edit form while keeping the scaffold save/delete logic.

## Subdirectory: `InputBuilder/`

Driver-specific classes that map database column types to Prado form controls. See `InputBuilder/CLAUDE.md`.

## Patterns & Gotchas

- Scaffold controls are intended for **admin/prototype UI** — they generate functional but unstyled interfaces. Override the default CSS or use `IScaffoldEditRenderer` for production UIs.
- The target `RecordClass` must be a fully qualified class name of a `TActiveRecord` subclass with `const TABLENAME` defined.
- Column metadata is fetched via `TDbMetaData` — the correct driver subclass is selected automatically from the connection.
- **Do not use scaffold controls with tables lacking a primary key** — PK is required for `findByPk()` and `delete()`.
