# Web/UI/WebControls/INDEX.md

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md)

## Purpose

Standard HTML input, layout, data display, and validation controls for the Prado framework. These are the non-AJAX controls; see `ActiveControls/` for AJAX variants and `JuiControls/` for jQuery UI wrappers.

## Base Classes

- **[`TWebControl`](TWebControl.md)** — Base for all WebControls. Adds HTML attribute management (style, CSS class, AccessKey, TabIndex, ToolTip, etc.) and standard HTML rendering helpers. Extends `TControl`.

- **[`TWebControlDecorator`](TWebControlDecorator.md)** — Injects additional HTML before/after a WebControl's rendered output without subclassing.

- **[`TContent`](TContent.md)** / **[`TContentPlaceHolder`](TContentPlaceHolder.md)** — Master page content injection. `TContent` blocks are injected into matching `TContentPlaceHolder` elements in the master template. Supports hierarchy matching.

## Subdirectories

| Directory | Purpose |
|---|---|
| [`assets/`](assets/INDEX.md) | Static assets published to the web by `TAssetManager` |

## Input Controls

| Class | HTML | Key Properties / Events |
|---|---|---|
| `TButton` | `<input type="submit">` or `<button>` | `ButtonType`, `ButtonTag`, `CausesValidation`, `ValidationGroup`, `OnClick`, `OnCommand` |
| `TLinkButton` | `<a>` with postback | Same as TButton |
| `TImageButton` | `<input type="image">` | Sends x,y coordinates; `OnClick` with `TImageClickEventParameter` |
| `TTextBox` | `<input>` / `<textarea>` | `TextMode` (SingleLine/MultiLine/Password), `MaxLength`, `ReadOnly`, `AutoPostBack` |
| `TCheckBox` | `<input type="checkbox">` | `Checked`, `TextAlign`, `AutoPostBack`, `OnCheckedChanged` |
| `TRadioButton` | `<input type="radio">` | `GroupName`, `Checked`, `AutoPostBack`, `OnCheckedChanged` |
| `TDropDownList` | `<select>` | `SelectedIndex`, `SelectedValue`, `Items` (TListItemCollection), `AutoPostBack` |
| `TListBox` | `<select multiple>` | `SelectionMode` (Single/Multiple), `Rows` |
| `TCheckBoxList` | multiple `<input type="checkbox">` | `RepeatLayout`, `RepeatColumns`, `RepeatDirection` |
| `TRadioButtonList` | multiple `<input type="radio">` | Same as TCheckBoxList |
| `TFileUpload` | `<input type="file">` | `HasFile`, `FileName`, `FileSize`, `FileType`, `LocalName`; `SaveAs($path)` |
| `THiddenField` | `<input type="hidden">` | `Value`, `OnValueChanged` |

## Display Controls

| Class | HTML | Key Properties |
|---|---|---|
| `TLabel` | `<label>` or `<span>` | `Text`, `For` (associated control ID), `AssociatedControlID` |
| `TImage` | `<img>` | `ImageUrl`, `AlternateText`, `ImageAlign` |
| `THyperLink` | `<a>` | `NavigateUrl`, `Text`, `ImageUrl`, `Target` |
| `TLiteral` | raw text | `Text` (not HTML-encoded), `Encode` |
| `TExpression` | evaluated PHP | Inline expression output (also via `<%= %>` in templates) |
| `TPanel` | `<div>` or `<span>` | `DefaultButton`, `GroupingText`, `Direction` (LTR/RTL) |
| `TPlaceHolder` | (none) | Invisible container for dynamic child controls |
| `TBulletedList` | `<ul>` / `<ol>` | `BulletStyle`, `DisplayMode` (Text/HyperLink/LinkButton) |
| `TTable` | `<table>` | `Rows` collection; `TTableRow` → `TTableCell` hierarchy |
| `TImage` | `<img>` | `ImageUrl`, `AlternateText` |
| `TConditional` | (conditional) | `Condition` PHP expression; renders `TrueTemplate` or `FalseTemplate` |

## Layout & Multi-View Controls

| Class | Purpose |
|---|---|
| `TMultiView` / `TView` | Shows one child `TView` at a time; `ActiveViewIndex` |
| `TAccordion` | Animated expand/collapse panels; JS in `controls/accordion.js` |
| `TTabPanel` | Tabbed views; JS in `controls/tabpanel.js` |
| `TSlider` | Drag-and-drop range slider; JS in `controls/slider.js` |
| `TColorPicker` | HSB color picker widget; JS in `colorpicker/colorpicker.js` |
| `TDatePicker` | Popup calendar; JS in `datepicker/datepicker.js` |
| `TKeyboard` | Virtual on-screen keyboard; JS in `controls/keyboard.js` |
| `TRatingList` | Star/block rating; JS in `ratings/ratings.js` |

## Data Controls

| Class | Purpose |
|---|---|
| `TDataGrid` | Tabular display with paging/sorting/editing. Columns: `TBoundColumn`, `TButtonColumn`, `TCheckBoxColumn`, `TDropDownListColumn`, `TTemplateColumn`. Events: `OnEditCommand`, `OnDeleteCommand`, `OnUpdateCommand`, `OnPageIndexChanged`, `OnSortCommand` |
| `TDataList` | Repeating item list with templates. Templates: `ItemTemplate`, `AlternatingItemTemplate`, `HeaderTemplate`, `FooterTemplate`, `EditItemTemplate`. `EditItemIndex` for inline editing. |
| `TRepeater` | Flexible repeating container. Template-driven, no built-in editing or paging. Lightest-weight data control. |
| `TListView` | Enhanced repeater with paging, sorting, and grouping support |

## Validation Controls

All validators extend `TBaseValidator`. Shared properties: `ControlToValidate`, `ErrorMessage`, `Display` (Static/Dynamic/None), `CssClass`, `ValidationGroup`, `EnableClientScript`.

| Class | Validates |
|---|---|
| `TRequiredFieldValidator` | Field is not empty |
| `TCompareValidator` | Value matches another control or constant |
| `TRangeValidator` | Value within min/max range |
| `TRegularExpressionValidator` | Value matches regex pattern |
| `TCustomValidator` | Custom server-side (and optional client-side) logic |
| `TEmailAddressValidator` | Valid email format |
| `TDataTypeValidator` | Value is correct data type (integer, date, etc.) |
| `TValidationSummary` | Displays all errors in a group; `DisplayMode` (List/BulletList/SingleParagraph) |

## Rich Text & Special Controls

| Class | Purpose |
|---|---|
| `THtmlArea` | TinyMCE v3/v4 rich text editor |
| `THtmlArea5` | TinyMCE v5+ rich text editor |
| `TMarkdown` | Renders Markdown content as HTML |
| `TSafeHtml` | Sanitises HTML input (strips dangerous tags) |
| `TXmlTransform` | Applies XSLT transformation to XML |
| `TGravatar` | Renders Gravatar image from email hash |
| `TInlineFrame` | `<iframe>` wrapper |
| `TWizard` | Multi-step wizard with `TWizardStep` navigation |
| `TOutputCache` | Caches rendered output of child controls |

## Style Classes

`TPanelStyle`, `TTableStyle`, `TTableItemStyle`, `TWebControlStyle` — CSS property objects (BackColor, ForeColor, Font, BorderColor, BorderStyle, BorderWidth, Width, Height, CssClass). Applied to controls via `getStyle()`.

## Conventions

- **`IPostBackEventHandler`** — Implement on controls that raise events from postback (buttons, links). `raisePostBackEvent($param)` is the handler.
- **`IPostBackDataHandler`** — Implement on input controls that receive posted values. `loadPostData()` reads the value; `raiseChangedEvent()` fires if it changed.
- **`AutoPostBack`** — Input controls with `AutoPostBack=true` submit the form via JavaScript on change. A corresponding JavaScript observer is registered by `TClientScriptManager`.
- **`ValidationGroup`** — Empty string validates all validators on the page. Non-empty groups are validated independently — useful for multiple forms on one page.
- **`TDataGrid` columns** — Always define columns explicitly for production; `AutoGenerateColumns=true` is for prototyping only (no control over rendering, sorting, or editing).
- **Templates in data controls** — Must be set programmatically in `createColumnGroup()` / `createItemTemplate()` or via `.tpl` syntax. Templates cannot be changed after `initRecursive()`.

## Subdirectory: `assets/`

Static assets published to the web by `TAssetManager`:

- **CSS:** `accordion.css`, `tabpanel.css`, `keyboard.css`, `TSlider/TSlider.css` — default styles for the corresponding controls.
- **Images:** `TSlider/TSliderHandleHorizontal.png`, `TSlider/TSliderHandleVertical.png`.
- **`captcha.php`** — Server-side CAPTCHA image generator for `TCaptcha`. Supports themes: `opaque_bubble`, `noisy`, `grid`, `scribble`, `morph`, `shadowed`. Requires `verase.ttf` (bundled). Validates a private key before rendering.
- **`verase.ttf`** — TrueType font for CAPTCHA rendering.

Override default control styles by setting the control's `CssUrl` property to a custom stylesheet.

## Gotchas

- `TFileUpload` requires `enctype="multipart/form-data"` on the `TForm` — set `TForm.Enctype` property.
- `TDataGrid` postback with sorting/paging raises `OnPageIndexChanged` / `OnSortCommand` — you must re-bind data in those handlers.
- `TValidationSummary` with `ShowMessageBox=true` uses a JavaScript `alert()` — avoid this in modern UIs.
- `TOutputCache` caches the rendered HTML; dynamic controls inside it will not update until the cache expires.
- `TConditional` evaluates the `Condition` PHP expression on every render; keep it lightweight.
