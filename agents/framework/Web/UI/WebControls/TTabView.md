# Web/UI/WebControls/TTabView

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTabView`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTabView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
A single tab pane within a `TTabPanel`. Renders a `<div>` for content and a tab header. The tab header text is determined from `Caption`, `Text`, or `NavigateUrl` (in that order of precedence).

Extends `[TWebControl](./TWebControl.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Caption` | string | '' | Tab header label. If set, used as the tab title (highest precedence) |
| `Text` | string | '' | Alternative tab label (used if Caption is empty) |
| `NavigateUrl` | string | '' | URL for tab header link; if set and Caption/Text are empty, the URL is used as the label |
| `Active` | bool | — | Whether this tab is the currently active view (read-only from outside; set by `TTabPanel`) |

## Tab Header Rendering Logic

```
if Caption != ''  → use Caption
else if Text != '' → use Text
else if NavigateUrl != '' → render as <a href="NavigateUrl">NavigateUrl</a>
else → empty tab header
```

## Key Methods

```php
$view->getCaption(): string
$view->setCaption(string $v): void
$view->getText(): string
$view->setText(string $v): void
$view->getNavigateUrl(): string
$view->setNavigateUrl(string $v): void
$view->getActive(): bool
$view->setActive(bool $v): void         // called by TTabPanel
$view->renderContents($writer): void    // renders content pane
$view->renderTab($writer): void         // renders tab header <div>
```

## Template Usage

```xml
<com:TTabView ID="myTab" Caption="My Tab">
    <p>Tab content goes here.</p>
    <com:TTextBox ID="input" />
</com:TTabView>
```

## Patterns & Gotchas

- **Always place inside `TTabPanel`** — `TTabView` is only meaningful as a direct child of `TTabPanel`. The parent manages which view is active.
- **`Active` is managed by `TTabPanel`** — do not set `Active` directly; use `TTabPanel::setActiveView()` or `setActiveViewIndex()` / `setActiveViewID()` instead.
- **Caption takes precedence over Text** — if both are set, `Caption` wins for the tab header. `Text` is provided as an alias for compatibility with other controls.
- **`NavigateUrl`** — renders the tab header as a hyperlink. Useful for tabs that should open a different page rather than switching content in place.
