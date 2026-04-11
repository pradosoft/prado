# Data/ActiveRecord/Scaffold/SUMMARY.md

Auto-generated CRUD UI controls for Active Record classes; introspects table metadata to produce list, search, and edit interfaces.

## Classes

- **`TScaffoldBase`** — Abstract base extending `TTemplateControl`; properties: `RecordClass`, `ConnectionID`; publishes default `style.css`.

- **`TScaffoldView`** — Composite control combining `TScaffoldListView` and `TScaffoldEditView` in a single panel.

- **`TScaffoldListView`** — Renders data grid of all records; properties: `PageSize`, `SortField`, `SortOrder`; raises `OnRecordSelected`.

- **`TScaffoldEditView`** — Renders form for creating/editing a record; delegates input control creation to driver-specific `InputBuilder`; handles `save` and `delete` postbacks.

- **`TScaffoldSearch`** — Optional search/filter bar wired to `TScaffoldListView`; adds WHERE clause.

- **`IScaffoldEditRenderer`** — Interface for custom edit view renderers.
