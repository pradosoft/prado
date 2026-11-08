# Web/Services/ServiceWorker/TWebAppManifest

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`TWebAppManifest`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/TWebAppManifest.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`
**Extends:** `TComponent`

## Overview
The members of a web app manifest. [TServiceWorkerDefinition](TServiceWorkerDefinition.md) holds one per worker; [TServiceWorkerService](TServiceWorkerService.md) serves it at `?sw=manifest`.

## Members

| Property | Member | Validation |
|---|---|---|
| `Id`, `Name`, `ShortName`, `Description`, `StartUrl`, `Scope`, `ThemeColor`, `BackgroundColor`, `Lang` | `id`, `name`, `short_name`, `description`, `start_url`, `scope`, `theme_color`, `background_color`, `lang` | String |
| `Display` | `display` | `DISPLAY_MODES`, lowercased |
| `DisplayOverride` | `display_override` | Display modes + `window-controls-overlay`, `tabbed`; list or comma text |
| `Orientation` | `orientation` | `ORIENTATIONS` |
| `Dir` | `dir` | `ltr`, `rtl`, `auto` |
| `Categories` | `categories` | List or comma text |
| `Icons`, `Screenshots`, `Shortcuts` | `icons`, `screenshots`, `shortcuts` | Array or JSON text of a list |

An empty value removes the member. Errors: `webappmanifest_value_invalid`, `webappmanifest_json_invalid`, `webappmanifest_file_invalid`.

## Sources

`toArray()` = `File` (a path, or a namespace whose `.json` file is read) merged with `Members` (any other members, array or JSON object) merged with the properties, later winning. `getIsEmpty()` is true with no file, members, or property.

## Tests
- `tests/unit/Web/Services/ServiceWorker/TWebAppManifestTest.php`

**@since 4.4.0**
