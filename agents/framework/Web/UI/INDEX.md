# Web/UI/INDEX.md

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / **`UI`**

## Purpose

Core UI component system for the Prado framework: the control hierarchy, template engine, page lifecycle, theming, and client-side script/style management.

## Key Classes

- **[`TControl`](TControl.md)** — Base class for all UI components (~57KB). Manages:
  - Control tree (parent/child relationships, `INamingContainer` for unique IDs)
  - ID management (auto-generated `ctl0`, `ctl1`, … if not set; client IDs use `_` separator)
  - Visibility and enabled state
  - Viewstate (serialized across postbacks) and Controlstate (always saved, even when viewstate disabled)
  - Databinding (`dataBind()`, `<%# %>` expressions)
  - Lifecycle states: `CS_CONSTRUCTED` → `CS_INITIALIZED` → `CS_STATE_LOADED` → `CS_LOADED` → `CS_PRERENDERED`
  - Implements `IRenderable`, `IBindable`

- **[`TPage`](TPage.md)** — Root control and page container. Manages:
  - Form postback/callback request processing
  - Validator collection
  - Theme/skin application
  - Page state serialization (HMAC-signed, optionally encrypted and compressed)
  - `TClientScriptManager` for JS/CSS
  - `TForm` requirement (controls must be inside a form for postback)

- **[`TTemplateControl`](TTemplateControl.md)** — Base for controls using `.tpl` template files. Caches parsed templates per class. Supports master pages (template inheritance via `MasterClass` property) and `TContentPlaceHolder` / `TContent` pairs.

- **[`TTemplate`](TTemplate.md)** — Parses `.page` / `.tpl` syntax:
  - `<com:ClassName>` — control instantiation
  - `<prop:PropertyName>` — property value block
  - `<%@ %>` — template directive
  - `<%= %>` — expression output
  - `<%# %>` — databinding expression
  - `<%-- --%>` — template comment (stripped at parse time)

- **[`TForm`](TForm.md)** — Renders the HTML `<form>` tag. Integrates with `TClientScriptManager` for hidden fields, scripts, and stylesheets. `DefaultButton` property sets which button submits on Enter.

- **[`TClientScriptManager`](TClientScriptManager.md)** — Manages all page-level JavaScript and CSS. Script packages with dependency resolution. Separates `<head>` scripts from inline scripts. Manages client-side validation registration, default button behaviour, and focus management.

- **[`TThemeManager`](TThemeManager.md)** / **[`TTheme`](TTheme.md)** — Theme management. `TTheme` applies property values (skins) to controls from `.skin` files. Supports per-theme CSS/JS file registration and RTL language variants.

- **[`TControlAdapter`](TControlAdapter.md)** — Adapter pattern for customizing rendering or behaviour of a control without subclassing. Implements [IAdapterControl](./IAdapterControl.md).

- **[`THtmlWriter`](THtmlWriter.md)** — Writes HTML tags and attributes with proper encoding. Updated void-element list matches HTML5 spec; legacy elements (`basefont`, `bgsound`, `frame`, `isindex`) deprecated.

- **[`TModuleView`](TModuleView.md)** — Conditionally shows child controls when a named application module is present and an optional condition expression is true. Falls back to a `FallbackTemplate` when inactive. @since 4.3.3

- **[`TRenderFilterParameter`](TRenderFilterParameter.md)** — Event parameter for the `onRenderFilter` event. Carries rendered HTML as both a raw string and a lazily-parsed `DOMDocument`. @since 4.3.3

- **[`TWebColor`](TWebColor.md)** — `TEnumerable` subclass defining all CSS named colors as `#RRGGBB` constants. Replaces the deprecated `TWebColors`. @since 4.3.0

## Subdirectories

| Directory | Purpose |
|---|---|
| [`ActiveControls/`](ActiveControls/INDEX.md) | AJAX-enabled controls with callback mechanism |
| [`JuiControls/`](JuiControls/INDEX.md) | jQuery UI widget wrappers |
| [`Traits/`](Traits/INDEX.md) | PHP traits for the UI layer (e.g., `TFilterRenderableTrait`) |
| [`WebControls/`](WebControls/INDEX.md) | Standard HTML input, layout, data display, and validation controls. Contains `assets/` subdirectory with published CSS, images, and the `captcha.php` server-side CAPTCHA generator |
| [`WebControls/assets/`](WebControls/assets/INDEX.md) | Static assets published to the web by `TAssetManager` |

## Interfaces

| Interface | Purpose |
|---|---|
| `ITemplate` | Template instantiation contract |
| `IRenderable` | Controls that produce HTML output |
| [`IFilterRenderable`](IFilterRenderable.md) | Controls supporting `onRenderFilter` render-output filtering. @since 4.3.3 |
| [`IAdapterControl`](IAdapterControl.md) | Contract for objects returned by `TControl::getAdapterControl()`. @since 4.3.3 |
| `IBindable` | Data binding support |
| `INamingContainer` | Uniquely names child controls (prefixes client IDs) |
| `IPostBackEventHandler` | Receives postback events (buttons, links) |
| `IPostBackDataHandler` | Processes postback data (inputs, checkboxes) |
| `IPageStatePersister` | Pluggable page state serialization strategy |
| [`TPageStatePersister`](TPageStatePersister.md) | Default persister: serialises view-state into a hidden `__VIEWSTATE` form field |
| [`TSessionPageStatePersister`](TSessionPageStatePersister.md) | Session-based persister: stores view-state in the PHP session with a FIFO history queue |

## Control Lifecycle (Page Request)

**GET:** `onPreInit` → `initRecursive` → `onInitComplete` → `onPreLoad` → `loadRecursive` → `onLoadComplete` → `preRenderRecursive` → `onPreRenderComplete` → `savePageState` → `renderControl` → `unloadRecursive`

**POST (Postback):** Same as GET, plus after `loadRecursive`: `processPostData` → `raiseChangedEvents` → `raisePostBackEvent`

**Callback (AJAX):** Same as POST except: `processCallbackEvent` instead of `raisePostBackEvent`; renders via `renderCallbackResponse` (headers only) instead of full HTML.

## Gotchas

- **Auto-generated IDs** — Controls without explicit `ID` get `ctl0`, `ctl1`, … These change when controls are added/removed; never rely on auto-IDs in client-side JS.
- **`INamingContainer`** — Child control client IDs are prefixed: `parentId_childId`. This affects jQuery selectors — use `$('#<%=$control->ClientID%>')` in templates.
- **Viewstate size** — Viewstate is serialized into the page. Disable it (`EnableViewState=false`) on controls that don't need it to reduce page weight.
- **Page state security** — Page state is HMAC-signed by `TSecurityManager`. Tampering causes an exception. Encryption is optional but recommended.
- **`TTemplateControl` caching** — Templates are parsed once and cached per class. Changes to `.tpl` files require cache clearing in production.
