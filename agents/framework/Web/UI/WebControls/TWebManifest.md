# Web/UI/WebControls/TWebManifest

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWebManifest`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWebManifest.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TControl`

## Overview
The web app manifest link of a page and the head tags installed web apps read (#1405). Each property renders one [TLinkTag](TLinkTag.md) or [TMetaTag](TMetaTag.md); an empty property renders nothing. It renders where it is placed, in [THead](THead.md) or a content placeholder of the head.

```xml
<com:THead>
  <com:TWebManifest Href="/app.webmanifest" ThemeColor="#ffffff" ThemeColorDark="#000000"
    AppTitle="Orders" StatusBarStyle="black-translucent" AppleTouchIcon="/img/touch.png" />
</com:THead>
```

## Properties → tags (render order)

| Property | Tag |
|---|---|
| `Href`, `CrossOrigin` | `<link rel="manifest" href crossorigin>`; `use-credentials` sends cookies with the manifest request |
| `ThemeColor` | `<meta name="theme-color">`; with `ThemeColorDark`, `media="(prefers-color-scheme: light)"` |
| `ThemeColorDark` | `<meta name="theme-color" media="(prefers-color-scheme: dark)">` |
| `ApplicationName` | `<meta name="application-name">` |
| `MobileWebAppCapable` | `<meta name="mobile-web-app-capable" content="yes">` |
| `AppTitle` | `<meta name="apple-mobile-web-app-title">` |
| `StatusBarStyle` | `<meta name="apple-mobile-web-app-status-bar-style">`: `default`, `black`, `black-translucent` (`webmanifest_statusbarstyle_invalid`) |
| `AppleTouchIcon`, `AppleTouchIconSizes` | `<link rel="apple-touch-icon" href sizes>` |

## The service worker's manifest

With an empty `Href` and a [TServiceWorkerModule](../../Services/ServiceWorker/TServiceWorkerModule.md), the control links `?sw=manifest` of the page's worker (`Worker`, else the page's selection) when that worker has a manifest, with `&amp;` encoding. An empty `ThemeColor` becomes the manifest's `theme_color`, an empty `AppTitle` its `short_name`, else `name`. `ModuleID` as in [TServiceWorker](TServiceWorker.md). The module adds a TWebManifest to the head of a page without one or without a `TLinkTag` of `Rel="manifest"`.

`createTags()` returns the tags; `render()` writes them, one per line, then the children.

## Accessibility

Audited for #1405: only `<link>` and `<meta>` elements, no visible, focusable, or announced content. `theme-color` with both schemes keeps the browser UI legible in light and dark mode.

## Tests
- `tests/unit/Web/UI/WebControls/TWebManifestTest.php`

**@since 4.4.0**
