# Web/UI/WebControls/TWebControl

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWebControl`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWebControl.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Base for all HTML-rendering WebControls. Extends `TControl` with HTML attribute management.

```php
$control->setCssClass('my-class');
$control->setStyle('color: red; font-size: 14px;');
$control->setAccessKey('A');
$control->setTabIndex(3);
$control->setToolTip('Hover text');
$control->setAttributes(['data-id' => '42']);
```

Properties: `CssClass`, `Style`, `AccessKey`, `TabIndex`, `ToolTip`, `Attributes` (TAttributeCollection).

---

## Input Controls

### TTextBox

```php
// Template:
// <com:TTextBox ID="username" TextMode="SingleLine" MaxLength="50" />

$this->username->Text = 'Alice';
$text = $this->username->Text;
```

| Property | Description |
|----------|-------------|
| `TextMode` | `SingleLine`, `MultiLine`, `Password` |
| `MaxLength` | Max characters (not applied in MultiLine) |
| `ReadOnly` | Render as readonly input |
| `AutoPostBack` | Post form on change |
| `AutoCompleteType` | HTML autocomplete hint |

### TCheckBox / TRadioButton

```php
$this->agree->Checked = true;
if ($this->agree->Checked) { ... }
```

`AutoPostBack=true` posts form on check change. `OnCheckedChanged` event.

### TDropDownList / TListBox

```php
// Populate programmatically:
$this->status->Items->add(new TListItem('Active', '1'));
$this->status->Items->add(new TListItem('Inactive', '0'));
$this->status->SelectedValue = '1';

// Selected:
$val = $this->status->SelectedValue;
$idx = $this->status->SelectedIndex;
```

`TListBox` adds `SelectionMode` (Single/Multiple) and `Rows`.

### TFileUpload

```php
if ($this->upload->HasFile) {
    $this->upload->SaveAs('/path/to/uploads/' . $this->upload->FileName);
}
```

Properties: `HasFile`, `FileName`, `FileSize`, `FileType`, `LocalName`.

---

## Buttons

```php
// Template: <com:TButton Text="Save" OnClick="saveClicked" />

public function saveClicked($sender, $param): void
{
    // $param: TEventParameter
}
```

`CausesValidation=true` (default) runs validators before `OnClick`. Use `ValidationGroup` for scoped validation.

`TLinkButton` — same API, renders as `<a>` with JavaScript postback.
`TImageButton` — `<input type="image">`; `OnClick` receives `TImageClickEventParameter` with `x`/`y`.

---

## Validators

All extend `TBaseValidator`. Properties shared: `ControlToValidate` (ID), `ErrorMessage`, `Display` (Static/Dynamic/None), `ValidationGroup`, `EnableClientScript`.

```xml
<com:TRequiredFieldValidator ControlToValidate="email"
    ErrorMessage="Email is required." />

<com:TRegularExpressionValidator ControlToValidate="email"
    RegularExpression="\w+@\w+\.\w+"
    ErrorMessage="Invalid email format." />

<com:TRangeValidator ControlToValidate="age"
    DataType="Integer" MinimumValue="18" MaximumValue="120"
    ErrorMessage="Age must be between 18 and 120." />

<com:TCompareValidator ControlToValidate="password2"
    ControlToCompare="password"
    ErrorMessage="Passwords must match." />

<com:TCustomValidator ControlToValidate="code"
    OnServerValidate="validateCode"
    ErrorMessage="Invalid code." />

<com:TValidationSummary ValidationGroup="regForm"
    DisplayMode="BulletList" />
```

---

## Data Controls

### TDataGrid

Tabular data display with paging, sorting, and inline editing.

```php
// Bind data:
$this->grid->DataSource = PostRecord::finder()->findAll();
$this->grid->dataBind();

// Events to handle:
// OnEditCommand, OnDeleteCommand, OnUpdateCommand,
// OnPageIndexChanged, OnSortCommand
```

Column types: `TBoundColumn`, `TButtonColumn`, `TCheckBoxColumn`, `TDropDownListColumn`, `TTemplateColumn`.

### TRepeater

Lightweight template-based repeating container:

```xml
<com:TRepeater ID="list">
    <prop:ItemTemplate>
        <div><%# $this->Data->title %></div>
    </prop:ItemTemplate>
</com:TRepeater>
```

```php
$this->list->DataSource = $records;
$this->list->dataBind();
```

### TDataList

Like TRepeater but with `EditItemIndex` for inline editing and `AlternatingItemTemplate`.

---

## Layout Controls

```xml
<!-- TPanel: generic container with optional grouping text -->
<com:TPanel CssClass="form-section" DefaultButton="submitBtn">
    <!-- controls inside -->
</com:TPanel>

<!-- TMultiView: show one view at a time -->
<com:TMultiView ID="wizard" ActiveViewIndex="0">
    <com:TView ID="step1">Step 1 content</com:TView>
    <com:TView ID="step2">Step 2 content</com:TView>
</com:TMultiView>
```

---

## Rich Input Controls

| Class | Purpose |
|-------|---------|
| `TDatePicker` | Calendar popup; `Date`, `DateFormat` properties |
| `TColorPicker` | HSB color picker; `Color` property |
| `TSlider` | Range slider; `Value`, `Min`, `Max`, `Step` |
| `THtmlArea` | TinyMCE 3/4 WYSIWYG; `Text` property |
| `THtmlArea5` | TinyMCE 5+ |

---

## Patterns & Gotchas

- **`CausesValidation`** — set to `false` on Cancel buttons to skip validation.
- **`ValidationGroup`** — group validators and buttons to allow partial-page validation.
- **Data control binding** — always set `DataSource` and call `dataBind()` on `onLoad` for non-postback requests. On postback, viewstate restores the rendered output automatically.
- **`TDataGrid` columns** — use `TTemplateColumn` for custom cell content; `SortExpression` property on columns enables sort events.
- **Client-side validation** — built-in validators generate JavaScript. Disable with `EnableClientScript=false` for server-only validation.
