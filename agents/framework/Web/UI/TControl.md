# Web/UI/TControl

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TControl`**

## Class Info
**Location:** `framework/Web/UI/TControl.php`
**Namespace:** `Prado\Web\UI`

## Overview
Base class for all Prado UI components (~57KB). Manages the control tree, IDs, visibility, viewstate, databinding, and the control lifecycle.

### Control Tree

```php
$parent->Controls->add($child);    // add child
$parent->Controls->remove($child);

$control->getParent();             // TControl or null
$control->getPage();               // TPage (root)
$control->getNamingContainer();    // nearest INamingContainer ancestor
$control->findControl($id);        // find descendant by ID
```

### IDs

```php
$control->setID('myId');           // logical ID (within naming container)
$control->getID();                 // 'myId'
$control->getClientID();           // 'container_myId' (HTML id attribute)
$control->getUniqueID();           // 'container$myId' (form field name)
```

If `ID` is not set, auto-assigned as `ctl0`, `ctl1`, … Don't rely on auto-IDs in JavaScript.

### Visibility & State

```php
$control->setVisible(false);       // hides and suppresses rendering
$control->setEnabled(false);       // sets disabled state
$control->getVisible();
$control->getEnabled();
```

### Viewstate

Viewstate persists property values across postbacks (serialized into the page's hidden field).

```php
// In TControl subclass — store in viewstate:
public function getText(): string
{
    return $this->getViewState('Text', '');
}
public function setText(string $v): void
{
    $this->setViewState('Text', $v, '');  // third arg = default
}

// Store in controlstate (always saved, even if viewstate disabled):
// Override onSaveState() / onLoadState()
```

### Databinding

```php
// In template:
// <com:TTextBox Text="<%# $this->Data->name %>"/>

// In code:
$control->dataBind();              // evaluates <%# %> expressions
$control->OnDataBinding->add([$this, 'handleBind']);  // event handler
```

### Control Lifecycle States

```
CS_CONSTRUCTED (0) → CS_INITIALIZED (1) → CS_STATE_LOADED (2)
→ CS_LOADED (3) → CS_PRERENDERED (4)
```

### Lifecycle Events / Methods (override in subclasses)

```php
protected function onPreInit($param) {}     // before init
protected function onInit($param) {}        // control added to page tree
protected function onLoad($param) {}        // after state loaded
protected function onPreRender($param) {}   // before rendering
protected function onUnload($param) {}      // cleanup
public function render($writer) {}          // produce HTML output
```

---

## TPage

Root control and page container. Accessed via `$this->getPage()` from any control.

### Key Features

- **Form postback processing** — coordinates `IPostBackDataHandler` and `IPostBackEventHandler` controls.
- **Validators** — `$page->getValidators($group)` returns the validator collection.
- **Theme/skin application** — `TThemeManager` applies skin files to controls on `onPreInit`.
- **Page state** — serialized, HMAC-signed (via [TSecurityManager](../Security/TSecurityManager.md)), optionally encrypted and compressed.
- **`TClientScriptManager`** — accessed via `$page->getClientScript()`.

### Key Properties

| Property | Description |
|----------|-------------|
| `IsPostBack` | `true` if current request is a form postback |
| `IsCallback` | `true` if current request is an AJAX callback |
| `ClientScript` | [TClientScriptManager](./TClientScriptManager.md) instance |
| `Theme` | Applied [TTheme](./TTheme.md) object |
| `Title` | HTML `<title>` content |
| `DefaultButton` | Control ID that submits on Enter |
| `EnableViewState` | Enable/disable page viewstate |

### Validation

```php
if ($this->Page->Validate('myGroup')) {
    // all validators in 'myGroup' passed
}
$this->Page->Validate();  // validates the default (empty-string) group
```

---

## TTemplateControl

Base for controls with `.tpl` template files. Subclass for reusable portlet/widget components.

```php
class MyPortlet extends TTemplateControl
{
    // Template file: MyPortlet.tpl (same directory, same name)

    public function onLoad($param): void
    {
        parent::onLoad($param);
        // Access child controls declared in template:
        $this->MyTextBox->Text = 'Hello';
    }
}
```

Template syntax:
```xml
<com:TTextBox ID="MyTextBox" Text="Enter here" />
<com:TButton Text="Submit" OnClick="handleClick" />
```

**Master pages:** Set `MasterClass` on a page to inherit a shared layout. Use [TContentPlaceHolder](./WebControls/TContentPlaceHolder.md) / [TContent](./WebControls/TContent.md) pairs:

```xml
<!-- master template (layouts/MainLayout.tpl) -->
<com:TContentPlaceHolder ID="MainContent" />

<!-- page template -->
<%@ MasterClass="layouts.MainLayout" %>
<com:TContent ID="MainContent">
    Page-specific content here.
</com:TContent>
```

---

## TClientScriptManager

Manages all JS/CSS on the page. Access via `$this->Page->ClientScript`.

```php
$cs = $this->Page->ClientScript;

// Register a JS package (with dependency resolution):
$cs->registerPackage('prado');
$cs->registerPackage('validator');

// Register inline scripts:
$cs->registerEndScript('mykey', 'alert("ready");');
$cs->registerBeginScript('mykey', 'var x = 1;');

// Register a CSS file:
$cs->registerStyleSheetFile('mykey', '/path/to/style.css');

// Register a JS file:
$cs->registerScriptFile('mykey', '/path/to/script.js');
```

---

## Template Syntax Reference

| Syntax | Purpose |
|--------|---------|
| `<com:ClassName ID="..." Prop="value">` | Instantiate a control |
| `<prop:PropertyName>...</prop:PropertyName>` | Multi-line property value |
| `<%@ Directive="value" %>` | Template directive (MasterClass, etc.) |
| `<%= expression %>` | Output PHP expression (HTML-encoded) |
| `<%# expression %>` | Databinding expression (evaluated on `dataBind()`) |
| `<%-- comment --%>` | Template comment (stripped at parse time) |

---

## Gotchas

- **Auto-generated IDs change** — adding/removing controls shifts `ctl0`/`ctl1` IDs. Always set explicit IDs for controls referenced in JS.
- **INamingContainer prefix** — client IDs include the naming container prefix: `container_childId`. Use `$control->ClientID` in templates.
- **Viewstate size** — set `EnableViewState=false` on read-only/static controls to reduce page weight.
- **Page state signing** — [TSecurityManager](../Security/TSecurityManager.md) must be configured; set an explicit `ValidationKey` in production.
- **Template caching** — `.tpl` files parsed once and cached. In `Performance` mode, changes won't be picked up without clearing the runtime cache.

(End of file - total 209 lines)
