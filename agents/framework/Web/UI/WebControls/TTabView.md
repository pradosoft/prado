# Web/UI/WebControls/TTabView

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTabView`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTabView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
A single tab pane within a `TTabPanel`. Renders a `<div>` for content and a tab header. The tab header shows `Caption`; `Text` is the pane's content.

Extends `[TWebControl](./TWebControl.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Caption` | string | '' | Tab header label; an empty caption renders `&nbsp;` |
| `Text` | string | '' | Pane content; when set it renders in place of the child controls |
| `Encode` | bool | false | Whether `Text` and `Caption` are HTML-encoded when rendered (4.4.0) |
| `NavigateUrl` | string | '' | URL for the tab header link, attribute-encoded in `href`; the link text is the caption |
| `Active` | bool | — | Whether this tab is the currently active view (read-only from outside; set by `TTabPanel`) |

## Tab Header Rendering Logic

```
label = Caption, or &nbsp; when Caption is ''   (encoded when Encode is true)
NavigateUrl != '' → <a href="NavigateUrl">label</a>, no role="tab"
NavigateUrl == '' → label, with role="tab", aria-selected, aria-controls, roving tabindex
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
- **Caption and Text** — `Caption` is the tab header; `Text` replaces the pane's child controls.
- **`NavigateUrl`** — renders the tab header as a hyperlink. Useful for tabs that should open a different page rather than switching content in place.
