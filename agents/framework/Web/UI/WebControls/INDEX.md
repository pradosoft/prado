# Web/UI/WebControls/INDEX.md

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / **`WebControls`**

## Purpose

Standard HTML input, layout, data display, and validation controls for the Prado framework. These are the non-AJAX controls; see `ActiveControls/` for AJAX variants and `JuiControls/` for jQuery UI wrappers.

## Base Classes

- **[`TWebControl`](TWebControl.md)** — Base for all WebControls. Adds HTML attribute management (style, CSS class, AccessKey, TabIndex, ToolTip, etc.) and standard HTML rendering helpers. Extends `TControl`.

- **[`TI18NWebControl`](TI18NWebControl.md)** — Extends `TWebControl` with `TI18NControlTrait`, adding `Culture` and `Charset` properties for I18N-aware controls. @since 4.3.3

- **[`TDataValueFormatterTrait`](TDataValueFormatterTrait.md)** — Formats bound data values for `TListControl` and `TDataGridColumn` with a format string, a `#` PHP expression, or a callable. A callable is kept for the current request only. @since 4.4.0

- **[`TWebControlDecorator`](TWebControlDecorator.md)** — Injects additional HTML before/after a WebControl's rendered output without subclassing.

- **[`TBaseTerm`](TBaseTerm.md)** — Base for `TAbbr`, `TDfn`, and `TVar`: renders a term element and marks the term's other occurrences in a target control through `onRenderFilter`. @since 4.4.0

- **[`TCiteTrait`](TCiteTrait.md)** — Backs the `Cite` property (source or explanation URL) of `TQuote`, `TBlockQuote`, `TIns`, and `TDel`. @since 4.4.0

- **[`TBaseEdit`](TBaseEdit.md)** — Base for `TIns` and `TDel`: `Cite` and `DateTime` of an edit. @since 4.4.0

- **[`TBaseMedia`](TBaseMedia.md)** — Base for `TVideo` and `TAudio`: `Src`, `ShowControls` (default true), `AutoPlay`, `Loop`, `Muted`, `Preload`, `CrossOrigin`. @since 4.4.0

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
| `TTextBox` | `<input>` / `<textarea>` | `TextMode` (SingleLine/MultiLine/Password), `MaxLength`, `ReadOnly`, `AutoPostBack`, `SuggestionList` |
| `TCheckBox` | `<input type="checkbox">` | `Checked`, `TextAlign`, `AutoPostBack`, `OnCheckedChanged` |
| `TRadioButton` | `<input type="radio">` | `GroupName`, `Checked`, `AutoPostBack`, `OnCheckedChanged` |
| `TDropDownList` | `<select>` | `SelectedIndex`, `SelectedValue`, `Items` (TListItemCollection), `AutoPostBack` |
| `TListBox` | `<select multiple>` | `SelectionMode` (Single/Multiple), `Rows` |
| [`TSuggestionList`](TSuggestionList.md) | `<datalist>` | Suggested values for a `TTextBox` through `TTextBox::SuggestionList`; `Items`, data binding (@since 4.4.0) |
| `TCheckBoxList` | multiple `<input type="checkbox">` | `RepeatLayout`, `RepeatColumns`, `RepeatDirection` |
| `TRadioButtonList` | multiple `<input type="radio">` | Same as TCheckBoxList |
| `TFileUpload` | `<input type="file">` | `HasFile`, `FileName`, `FileSize`, `FileType`, `LocalName`; `SaveAs($path)` |
| `THiddenField` | `<input type="hidden">` | `Value`, `OnValueChanged` |

## Display Controls

| Class | HTML | Key Properties |
|---|---|---|
| `TLabel` | `<label>` or `<span>` | `Text`, `Encode` (4.4.0), `ForControl` (associated control ID) |
| [`TOutput`](TOutput.md) | `<output>` | `Text` (encoded), `ForControl` (source control IDs); `status` live region (@since 4.4.0) |
| `TImage` | `<img>` | `ImageUrl`, `AlternateText`; `Srcset`, `Sizes`, `IntrinsicWidth`/`IntrinsicHeight`, `Loading` (`TLoadingMode`), `Decoding`, `FetchPriority`, `CrossOrigin`, `ReferrerPolicy` (`TReferrerPolicy`) (@since 4.4.0) |
| [`TDot`](TDot.md) | `<img>` or inline `<svg>` | SVG status dot; `Color`, `Size`, `Flat`, `PublishStyle`, `AlternateText` names an inline dot (@since 4.4.0) |
| [`TSvg`](TSvg.md) | `<svg>` | Inline SVG from template markup, `SvgFile`, or `Svg`; `AlternateText`/`Decorative`; `Sanitize`, `PrefixIds`, `ScopeStyles`, `HoistStyles` via [TSvgDocument](TSvgDocument.md) (@since 4.4.0) |
| `THyperLink` | `<a>` | `NavigateUrl`, `Text`, `ImageUrl`, `Target`; `Download`, `Rel`, `HrefLang`, `Type`, `ReferrerPolicy`, `Ping` (@since 4.4.0) |
| `TLiteral` | raw text | `Text` (not HTML-encoded), `Encode` |
| `TExpression` | evaluated PHP | Inline expression output (also via `<%= %>` in templates) |
| `TPanel` | `<div>` or `<span>` | `DefaultButton`, `GroupingText`, `Encode` (4.4.0), `Direction` (LTR/RTL); [TFieldSet](TFieldSet.md) renders the fieldset as its own tag |
| `TPlaceHolder` | (none) | Invisible container for dynamic child controls |
| `TBulletedList` | `<ul>` / `<ol>` | `BulletStyle`, `DisplayMode` (Text/HyperLink/LinkButton) |
| [`TMenuList`](TMenuList.md) | `<menu>` | `TBulletedList` items as a toolbar of commands; `list` role, not an ARIA `menu` (@since 4.4.0) |
| `TTable` | `<table>` | `Rows` collection; `TTableRow` → `TTableCell` hierarchy |
| `TImage` | `<img>` | `ImageUrl`, `AlternateText` |
| `TConditional` | (conditional) | `Condition` PHP expression; renders `TrueTemplate` or `FalseTemplate` |

## Layout & Multi-View Controls

| Class | Purpose |
|---|---|
| `TMultiView` / `TView` | Shows one child `TView` at a time; `ActiveViewIndex` |
| `TAccordion` | Animated expand/collapse panels; JS in `controls/accordion.js` |
| `TSafetyCover` | Panel content behind a click-to-open overlay (slide/collapse/none, optional fade), guarding against accidental clicks; JS in `controls/safetycover.js` @since 4.4.0 |
| `TTabPanel` | Tabbed views; JS in `controls/tabpanel.js` |
| `TSlider` | Drag-and-drop range slider; JS in `controls/slider.js` |
| `TColorPicker` | HSB color picker widget; JS in `colorpicker/colorpicker.js` |
| `TDatePicker` | Popup calendar; JS in `datepicker/datepicker.js` |
| `TKeyboard` | Virtual on-screen keyboard; JS in `controls/keyboard.js` |
| `TRatingList` | Star/block rating; JS in `ratings/ratings.js` |
| `TRelativeTime` | Live "time ago" in a `<time>` element; extends `TTime`; JS in `controls/relativetime.js` |

## Data Controls

| Class | Purpose |
|---|---|
| `TDataGrid` | Tabular display with paging/sorting/editing. Columns: `TBoundColumn`, `TButtonColumn`, `TCheckBoxColumn`, `TDropDownListColumn`, `TTemplateColumn`. Events: `OnEditCommand`, `OnDeleteCommand`, `OnUpdateCommand`, `OnPageIndexChanged`, `OnSortCommand`. `SortExpression` / `SortDirection` (`TDataGridSortDirection`) hold the sort and render `aria-sort` on its header |
| `TDataList` | Repeating item list with templates. Templates: `ItemTemplate`, `AlternatingItemTemplate`, `HeaderTemplate`, `FooterTemplate`, `EditItemTemplate`. `EditItemIndex` for inline editing. |
| `TRepeater` | Flexible repeating container. Template-driven, no built-in editing or paging. Lightest-weight data control. |
| `TListView` | Enhanced repeater with paging, sorting, and grouping support |

## Validation Controls

All validators extend `TBaseValidator`. Shared properties: `ControlToValidate`, `ErrorMessage`, `Display` (Fixed/Dynamic/None), `CssClass`, `ValidationGroup`, `EnableClientScript`, `Encode` (HTML-encodes `ErrorMessage` and `Text` everywhere they show; 4.4.0).

| Class | Validates |
|---|---|
| `TRequiredFieldValidator` | Field is not empty |
| `TCompareValidator` | Value matches another control or constant |
| `TRangeValidator` | Value within min/max range |
| `TRegularExpressionValidator` | Value matches regex pattern |
| `TCustomValidator` | Custom server-side (and optional client-side) logic |
| `TEmailAddressValidator` | Valid email format |
| `TDataTypeValidator` | Value is correct data type (integer, date, etc.) |
| `TFileValidator` | Files selected in a `TFileUpload`: size, count, extension, MIME type (@since 4.4.0) |
| `TImageValidator` | Image files selected in a `TFileUpload`: TFileValidator checks plus pixel dimensions and a readable-image check (@since 4.4.0) |
| `TCaptchaValidator` | Input matches a `TCaptcha` token; client checks only that the input is not empty |
| `TProofOfWorkValidator` | A `TProofOfWork` solution is valid and unused; server only (@since 4.4.0) |
| `TFormGuard` | The form itself: honeypot, signed fill time, rate limit; no `ControlToValidate`; server only (@since 4.4.0) |
| `TValidationSummary` | Displays all errors in a group; `DisplayMode` (List/BulletList/SingleParagraph) |

## Anti-Bot Controls

Vision models read image CAPTCHAs, so these controls raise the cost of each automated submission instead. Single-use tokens and rate limits use the cache through `TCacheModuleIDTrait`.

| Class | Purpose |
|---|---|
| [`TFormGuard`](./TFormGuard.md) | Validator: hidden honeypot, signed render stamp (`MinFillTime`/`MaxFillTime`), per-client `RateLimit`; `FailureReason` (@since 4.4.0) |
| [`TProofOfWork`](./TProofOfWork.md) | Browser solves a signed single-use SHA-256 challenge in a Web Worker before the form posts; `role="status"`; needs a cache; JS in `controls/proofofwork.js` (@since 4.4.0) |
| [`TCaptcha`](./TCaptcha.md) | Distorted-text image; HMAC-SHA256 tokens, single-use through the cache (`SingleUse`) |
| [`TReCaptcha2`](./TReCaptcha2.md) | Google reCAPTCHA v2 widget |

## Semantic HTML5 Controls (@since 4.3.3)

All extend [THtmlElement](./THtmlElement.md). Each wraps the named HTML5 element with full `TWebControl` attribute support. The `TagName` property can be overridden via a theme or template.

| Class | Default Tag | HTML5 Semantic Meaning |
|---|---|---|
| [`TAbbr`](TAbbr.md) | `abbr` | Abbreviation; `Title` is the expansion; marks the abbreviation's other occurrences ([TBaseTerm](TBaseTerm.md), @since 4.4.0) |
| [`TAddress`](TAddress.md) | `address` | Contact information for the article or page; `group` role (@since 4.4.0) |
| [`TArticle`](TArticle.md) | `article` | Self-contained composition (blog post, news article) |
| [`TAside`](TAside.md) | `aside` | Tangentially related content; sidebar |
| [`TBdi`](TBdi.md) | `bdi` | Text isolated from the surrounding direction (@since 4.4.0) |
| [`TBdo`](TBdo.md) | `bdo` | Text displayed in a forced direction; `Dir` required (@since 4.4.0) |
| [`TBlockQuote`](TBlockQuote.md) | `blockquote` | Block quotation; `blockquote` role; `Cite` source URL (@since 4.4.0) |
| [`TCite`](TCite.md) | `cite` | Title of a creative work (@since 4.4.0) |
| [`TCanvas`](TCanvas.md) | `canvas` | Bitmap for client drawing; `Width`/`Height` bitmap size; `AlternateText` → `role="img"`; children are fallback content; `ClientSide.OnDraw` draws through `controls/canvas.js` (@since 4.4.0) |
| [`TCode`](TCode.md) | `code` | Fragment of computer code; `code` role (@since 4.4.0) |
| [`TDataValue`](TDataValue.md) | `data` | Content with a machine-readable `Value` (@since 4.4.0) |
| [`TDel`](TDel.md) | `del` | Deleted text; `deletion` role; `Cite`, `DateTime` (@since 4.4.0) |
| [`TDescriptionDetails`](TDescriptionDetails.md) | `dd` | Details of a description list group; `Term` renders its `<dt>`; `definition` role (@since 4.4.0) |
| [`TDescriptionList`](TDescriptionList.md) | `dl` | Description list of term-details groups (@since 4.4.0) |
| [`TDescriptionTerm`](TDescriptionTerm.md) | `dt` | Term of a description list group; `term` role (@since 4.4.0) |
| [`TDfn`](TDfn.md) | `dfn` | Defining instance of a term; marks the first occurrence of `Terms` ([TBaseTerm](TBaseTerm.md), @since 4.4.0) |
| [`TFieldSet`](TFieldSet.md) | `fieldset` | Group of form controls; `Legend` caption; `Enabled=false` disables the group natively and on the server; extends TPanel (@since 4.4.0) |
| [`TFootnote`](TFootnote.md) | `sup` | Numbered footnote reference; child controls are the note, rendered in a `TReferenceList`; `Key` joins references (@since 4.4.0) |
| [`TFooter`](TFooter.md) | `footer` | Footer of nearest sectioning ancestor |
| [`THeader`](THeader.md) | `header` | Introductory content or navigational links |
| [`THGroup`](THGroup.md) | `hgroup` | Heading with subtitle; `HeaderText` in `<h1>`–`<h6>`; `group` role (@since 4.4.0) |
| [`TIns`](TIns.md) | `ins` | Inserted text; `insertion` role; `Cite`, `DateTime` (@since 4.4.0) |
| [`TKbd`](TKbd.md) | `kbd` | User input: key, key combination, spoken command (@since 4.4.0) |
| [`TLegend`](TLegend.md) | `legend` | Caption of a TFieldSet with markup or controls; first child (@since 4.4.0) |
| [`TMain`](TMain.md) | `main` | Dominant page content; one per document |
| [`TMark`](TMark.md) | `mark` | Highlighted or marked text |
| [`TNav`](TNav.md) | `nav` | Navigation links section |
| [`TPre`](TPre.md) | `pre` | Preformatted text; whitespace kept (@since 4.4.0) |
| [`TQuote`](TQuote.md) | `q` | Inline quotation; `Cite` source URL (@since 4.4.0) |
| [`TSamp`](TSamp.md) | `samp` | Sample output from a program (@since 4.4.0) |
| [`TRuby`](TRuby.md) | `ruby` | Base text with annotations; `Base`, `Annotation`, `<rp>` parentheses (@since 4.4.0) |
| [`TRubyParenthesis`](TRubyParenthesis.md) | `rp` | Parenthesis around a ruby annotation; hidden where ruby renders (@since 4.4.0) |
| [`TRubyText`](TRubyText.md) | `rt` | Ruby annotation (@since 4.4.0) |
| [`TReferenceList`](TReferenceList.md) | `section` | Numbered notes of the page's `TFootnote` references with back links; region named by `HeaderText` (@since 4.4.0) |
| [`TSearch`](TSearch.md) | `search` | Search or filter controls; landmark; another tag renders `role="search"` (@since 4.4.0) |
| [`TSection`](TSection.md) | `section` | Generic thematic document section |
| [`TSeparator`](TSeparator.md) | `hr` | Thematic break; void; another tag renders `role="separator"` (@since 4.4.0) |
| [`TSub`](TSub.md) | `sub` | Subscript; `subscript` role (@since 4.4.0) |
| [`TSup`](TSup.md) | `sup` | Superscript; `superscript` role (@since 4.4.0) |
| [`TVar`](TVar.md) | `var` | Variable or placeholder; marks the variable's other occurrences ([TBaseTerm](TBaseTerm.md), @since 4.4.0) |

## Media Controls (@since 4.4.0)

[TVideo](TVideo.md) and [TAudio](TAudio.md) extend [TBaseMedia](TBaseMedia.md); `ShowControls` defaults to true. Children render as sources, tracks, then fallback content.

| Class | HTML | Key Properties |
|---|---|---|
| [`TMediaSource`](TMediaSource.md) | `<source>` | `Src`, `Type`, `Media`; `Srcset`, `Sizes`, `Width`, `Height` in a TPicture |
| [`TTextTrack`](TTextTrack.md) | `<track>` | `Src`, `Kind` ([TTextTrackKind](TTextTrackKind.md)), `SrcLang`, `Label`, `IsDefault` |
| [`TVideo`](TVideo.md) | `<video>` | `Src`, `ShowControls`, `AutoPlay`, `Loop`, `Muted`, `Preload`, `CrossOrigin`; `Width`, `Height`, `Poster`, `PlaysInline` |
| [`TAudio`](TAudio.md) | `<audio>` | `Src`, `ShowControls`, `AutoPlay`, `Loop`, `Muted`, `Preload`, `CrossOrigin` |
| [`TPicture`](TPicture.md) | `<picture>` + `<img>` | Extends `TImage`; child `TMediaSource` sources render before the `<img>`, which carries the attributes |

## Rich Text & Special Controls

| Class | Purpose |
|---|---|
| `THtmlArea` | TinyMCE v3/v4 rich text editor |
| `THtmlArea5` | TinyMCE v5+ rich text editor |
| `TMarkdown` | Renders Markdown content as HTML |
| `TSafeHtml` | Sanitises HTML input (strips dangerous tags) |
| `TXmlTransform` | Applies XSLT transformation to XML |
| `TGravatar` | Renders Gravatar image from email hash |
| `TInlineFrame` | `<iframe>` wrapper; `SrcDoc`, `Sandbox`, `Allow`, `Loading`, `ReferrerPolicy` (@since 4.4.0) |
| `TWebTemplate` | `<template>`; client stamping with `{{...}}` placeholders, or a declarative shadow root via `ShadowRootMode` (@since 4.4.0) |
| `TSlot` | `<slot>` in a shadow root; `Name` matches the light DOM `slot` attribute (`TWebControl::Slot`); content is the fallback (@since 4.4.0) |
| `TWizard` | Multi-step wizard with `TWizardStep` navigation |
| `TOutputCache` | Caches rendered output of child controls |

## Style Classes

`TPanelStyle`, `TTableStyle`, `TTableItemStyle`, `TWebControlStyle` — CSS property objects (BackColor, ForeColor, Font, BorderColor, BorderStyle, BorderWidth, Width, Height, CssClass). Applied to controls via `getStyle()`.

## Conventions

- **`IPostBackEventHandler`** — Implement on controls that raise events from postback (buttons, links). `raisePostBackEvent($param)` is the handler.
- **`IPostBackDataHandler`** — Implement on input controls that receive posted values. `loadPostData()` reads the value; `raiseChangedEvent()` fires if it changed.
- **`AutoPostBack`** — Input controls with `AutoPostBack=true` submit the form via JavaScript on change. A corresponding JavaScript observer is registered by `TClientScriptManager`.
- **`Encode`** — A control that renders a text property as HTML has an `Encode` property from [TEncodeTrait](./TEncodeTrait.md), off by default; on, the text is HTML-encoded as it is rendered (4.4.0).
- **`ValidationGroup`** — Empty string validates all validators on the page. Non-empty groups are validated independently — useful for multiple forms on one page.
- **`TDataGrid` columns** — Always define columns explicitly for production; `AutoGenerateColumns=true` is for prototyping only (no control over rendering, sorting, or editing).
- **Templates in data controls** — Must be set programmatically in `createColumnGroup()` / `createItemTemplate()` or via `.tpl` syntax. Templates cannot be changed after `initRecursive()`.

## Subdirectory: `assets/`

Static assets published to the web by `TAssetManager`:

- **CSS:** `accordion.css`, `tabpanel.css`, `keyboard.css`, `TSlider/TSlider.css` — default styles for the corresponding controls.
- **Images:** `TSlider/TSliderHandleHorizontal.png`, `TSlider/TSliderHandleVertical.png`.
- **`captcha.php`** — Standalone CAPTCHA image script for `TCaptcha`, requested directly by the browser with no autoloader; references no framework class. Theme bits: opaque, noise, grid, scribble, morph, shadow. Verifies the HMAC-SHA256 signature of its options with the private key from `captcha_key.php`. Requires `verase.ttf` (bundled).
- **`verase.ttf`** — TrueType font for CAPTCHA rendering.

Override default control styles by setting the control's `CssUrl` property to a custom stylesheet.

The colors in these stylesheets follow the `color-scheme` the application
declares, through `light-dark()` and the `Canvas`/`CanvasText` system colors. See
[assets/INDEX.md](./assets/INDEX.md) for the contract.

## Gotchas

- `TFileUpload` requires `enctype="multipart/form-data"` on the `TForm` — set `TForm.Enctype` property.
- `TDataGrid` postback with sorting/paging raises `OnPageIndexChanged` / `OnSortCommand` — you must re-bind data in those handlers. A sort handler also sets the grid's `SortExpression` and `SortDirection` from the parameter.
- `TValidationSummary` with `ShowMessageBox=true` uses a JavaScript `alert()` — avoid this in modern UIs.
- `TOutputCache` caches the rendered HTML; dynamic controls inside it will not update until the cache expires.
- `TAbbr`, `TDfn`, and `TVar` filter their parent by default; a large parent, such as a `TContent`, is parsed whenever it holds an unmarked term. Set `TargetControl` to a small container, and exempt markup with `data-prado-filter-exempt`.
- `TConditional` evaluates the `Condition` PHP expression on every render; keep it lightweight.
