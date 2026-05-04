# Web/UI/SUMMARY.md

Core UI component system: control hierarchy, template engine, page lifecycle, theming, and client-side script/style management.

## Classes

- **`TControl`** — Base class for all UI components; manages control tree, ID management, visibility, enabled state, viewstate/controlstate, databinding, and lifecycle states.

- **`TPage`** — Root control and page container; manages form postback/callback processing, validator collection, theme/skin application, page state serialization, `TClientScriptManager`.

- **`TTemplateControl`** — Base for controls using `.tpl` template files; supports master pages and `TContentPlaceHolder`/`TContent` pairs.

- **`TTemplate`** — Parses `.page`/`.tpl` syntax: `<com:ClassName>`, `<prop:PropertyName>`, `<%@ %>`, `<%= %>`, `<%# %>`, `<%-- --%>`.

- **`TForm`** — Renders HTML `<form>` tag; integrates with `TClientScriptManager`; property: `DefaultButton`.

- **`TClientScriptManager`** — Manages all page-level JavaScript and CSS; provides script package dependency resolution.

- **`TThemeManager`** / **`TTheme`** — Theme management; `TTheme` applies property values (skins) from `.skin` files.

- **`TControlAdapter`** — Adapter pattern for customizing rendering or behaviour of a control without subclassing.

- **`THtmlWriter`** — Writes HTML tags and attributes with proper encoding.
