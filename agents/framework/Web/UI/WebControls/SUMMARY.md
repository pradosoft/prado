# Web/UI/WebControls/SUMMARY.md

Standard HTML input, layout, data display, and validation controls for the Prado framework.

## Classes

- **`TWebControl`** — Base for all WebControls; adds HTML attribute management (style, CSS class, AccessKey, TabIndex, ToolTip).

- **`TWebControlDecorator`** — Injects additional HTML before/after a WebControl's rendered output without subclassing.

- **`TContent`** / **`TContentPlaceHolder`** — Master page content injection; `TContent` blocks injected into matching `TContentPlaceHolder`.

### Input Controls

- **`TButton`** — `<input type="submit">` or `<button>`; properties: `ButtonType`, `ButtonTag`, `CausesValidation`, `ValidationGroup`.

- **`TLinkButton`** — `<a>` with postback; same properties as TButton.

- **`TImageButton`** — `<input type="image">`; sends x,y coordinates.

- **`TTextBox`** — `<input>` or `<textarea>`; properties: `TextMode`, `MaxLength`, `ReadOnly`, `AutoPostBack`.

- **`TCheckBox`** / **`TRadioButton`** — properties: `Checked`, `TextAlign`, `AutoPostBack`, `OnCheckedChanged`.

- **`TDropDownList`** / **`TListBox`** — properties: `SelectedIndex`, `SelectedValue`, `Items`.

- **`TCheckBoxList`** / **`TRadioButtonList`** — properties: `RepeatLayout`, `RepeatColumns`, `RepeatDirection`.

- **`TFileUpload`** — methods: `HasFile`, `FileName`, `FileSize`, `SaveAs($path)`.

- **`THiddenField`** — properties: `Value`, `OnValueChanged`.

### Display Controls

- **`TLabel`** — `<label>` or `<span>`; properties: `Text`, `For`.

- **`TImage`** — `<img>`; properties: `ImageUrl`, `AlternateText`.

- **`THyperLink`** — `<a>`; properties: `NavigateUrl`, `Text`, `Target`.

- **`TLiteral`** — raw text; property: `Text` (not HTML-encoded).

- **`TPanel`** — `<div>` or `<span>`; properties: `DefaultButton`, `GroupingText`.

- **`TPlaceHolder`** — invisible container for dynamic child controls.

- **`TBulletedList`** — `<ul>` or `<ol>`; properties: `BulletStyle`, `DisplayMode`.

- **`TTable`** — `<table>`; `Rows` collection with `TTableRow` → `TTableCell` hierarchy.

- **`TConditional`** — conditional rendering based on PHP expression.

### Layout & Multi-View Controls

- **`TMultiView`** / **`TView`** — Shows one child `TView` at a time; `ActiveViewIndex`.

- **`TAccordion`** / **`TTabPanel`** / **`TSlider`** / **`TColorPicker`** / **`TDatePicker`** / **`TKeyboard`** / **`TRatingList`** — Widget controls with JS implementations.

### Data Controls

- **`TDataGrid`** — Tabular display with paging/sorting/editing; column types: `TBoundColumn`, `TButtonColumn`, `TCheckBoxColumn`, `TDropDownListColumn`, `TTemplateColumn`.

- **`TDataList`** — Repeating item list with templates; `EditItemIndex` for inline editing.

- **`TRepeater`** — Flexible repeating container; template-driven, no built-in editing or paging.

- **`TListView`** — Enhanced repeater with paging, sorting, and grouping support.

### Validation Controls

- **`TBaseValidator`** — Base class for all validators; properties: `ControlToValidate`, `ErrorMessage`, `Display`, `ValidationGroup`.

- **`TRequiredFieldValidator`** / **`TCompareValidator`** / **`TRangeValidator`** / **`TRegularExpressionValidator`** / **`TCustomValidator`** / **`TEmailAddressValidator`** / **`TDataTypeValidator`** — Specific validation logic.

- **`TValidationSummary`** — Displays all errors in a group; `DisplayMode`: `List`, `BulletList`, `SingleParagraph`.

### Rich Text & Special Controls

- **`THtmlArea`** / **`THtmlArea5`** — TinyMCE rich text editors (v3/v4 and v5+).

- **`TMarkdown`** — Renders Markdown content as HTML.

- **`TSafeHtml`** — Sanitizes HTML input.

- **`TXmlTransform`** — Applies XSLT transformation to XML.

- **`TGravatar`** — Renders Gravatar image from email hash.

- **`TInlineFrame`** — `<iframe>` wrapper.

- **`TWizard`** — Multi-step wizard with `TWizardStep` navigation.

- **`TOutputCache`** — Caches rendered output of child controls.

### Style Classes

- **`TPanelStyle`** / **`TTableStyle`** / **`TTableItemStyle`** / **`TWebControlStyle`** — CSS property objects applied via `getStyle()`.
