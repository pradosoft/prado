# Web/UI/WebControls/TTabPanel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTabPanel`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTabPanel.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Tabbed panel control. Renders a `<div>` container with tab headers and content panes. Each tab corresponds to a `TTabView` child. The active tab is tracked via viewstate and handled via postback data (`IPostBackDataHandler`).

JavaScript: `controls/tabpanel.js` (published via `TAssetManager`).

Extends `[TWebControl](./TWebControl.md)`, implements `[IPostBackDataHandler](./IPostBackDataHandler.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `ActiveViewIndex` | int | 0 | Zero-based index of the visible tab |
| `ActiveViewID` | string | '' | ID of the active `TTabView` (takes precedence over index if set) |
| `ActiveView` | TTabView | — | The active TTabView control |
| `AutoSwitch` | bool | true | Whether clicking a tab switches the view client-side |
| `CssUrl` | string | '' | URL to custom CSS stylesheet |
| `CssClass` | string | `tab-panel` | CSS class for outer div |
| `TabCssClass` | string | `tab-normal` | CSS class for inactive tabs |
| `ActiveTabCssClass` | string | `tab-active` | CSS class for active tab |
| `ViewCssClass` | string | `tab-view` | CSS class for content area |

## Style Properties

`TabStyle`, `ActiveTabStyle`, `ViewStyle` — `TStyle` objects for additional CSS.

## Key Methods

```php
$panel->getActiveView(): TTabView
$panel->setActiveView(TTabView $view): void
$panel->getActiveViewIndex(): int
$panel->setActiveViewIndex(int $index): void
$panel->getActiveViewID(): string
$panel->setActiveViewID(string $id): void

// IPostBackDataHandler:
$panel->loadPostData(string $key, array $values): bool
$panel->raisePostDataChangedEvent(): void
$panel->getDataChanged(): bool
```

## IPostBackDataHandler

`TTabPanel` implements `IPostBackDataHandler` to receive the active tab index from the hidden postback field. On postback, if the active tab changed, `raisePostDataChangedEvent()` is called (no public event raised — the active view is simply updated).

**`ActiveViewID` takes precedence** — if `ActiveViewID` is set, it overrides `ActiveViewIndex` for determining the active tab.

## Template Usage

```xml
<com:TTabPanel ID="tabs" ActiveViewIndex="0">
    <com:TTabView ID="tab1" Caption="First Tab">
        Content for first tab.
    </com:TTabView>
    <com:TTabView ID="tab2" Caption="Second Tab">
        Content for second tab.
    </com:TTabView>
    <com:TTabView ID="tab3" Caption="Link Tab"
                  NavigateUrl="http://example.com" Text="External">
        Content shown if NavigateUrl is not set.
    </com:TTabView>
</com:TTabPanel>
```

## CSS Customization

Override default styles by setting `CssUrl` to a custom stylesheet. Default CSS classes:

| Element | Default Class |
|---------|--------------|
| Outer div | `tab-panel` |
| Inactive tab | `tab-normal` |
| Active tab | `tab-active` |
| Content area | `tab-view` |

## Patterns & Gotchas

- **`ActiveViewID` vs `ActiveViewIndex`** — `ActiveViewID` takes precedence. Set `ActiveViewID` to `''` to use index-based selection.
- **JavaScript required** — tab switching without a page reload requires the published JS. Ensure assets are published (`TAssetManager`).
- **`AutoSwitch=false`** — disables client-side tab switching; tab changes cause a postback. Useful when tab content requires server-side re-render.
- **Tab headers are rendered as part of the same `<div>`** — not as `<ul>/<li>`. The JS manages visibility of the content panes.
- **Custom CSS** — the `CssUrl` overrides the default asset CSS. The JS still expects the same class name convention unless you also override the client-side class name.
