# Web/UI/WebControls/TBaseTerm

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBaseTerm`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBaseTerm.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`THtmlElement`](THtmlElement.md) (abstract)
**Implements:** `IDataRenderer`
**Subclasses:** [`TAbbr`](TAbbr.md) (`<abbr>`), [`TDfn`](TDfn.md) (`<dfn>`), [`TVar`](TVar.md) (`<var>`)

## Overview
TBaseTerm renders a term element and marks the other occurrences of its terms in a target control. In `onPreRender` it attaches `markTerms` as an `onRenderFilter` handler on the target ([render-filter pipeline](../IFilterRenderable.md)). The handler wraps each occurrence in the target's rendered output in an element with the control's tag and the term's `title`.

## Template Usage

```xml
<com:TPanel>
  <p><com:TAbbr Text="HTML" Title="HyperText Markup Language" /> pages use HTML and CSS.</p>
  <com:TAbbr Terms="CSS => Cascading Style Sheets" MarkLimit="1" />
</com:TPanel>
```

## Properties

| Property | Description |
|---|---|
| `Text` / `Data` | The term, HTML-encoded. Empty → body content renders. |
| `Title` | `title` of the element and of each mark of its term; the `ToolTip`. |
| `Terms` | More terms: `term => title`; a list item has no title. A string parses with `TPropertyValue::ensureArray` (`"CSS => Cascading Style Sheets, URL"`). |
| `TargetControl` | ID located with `findControl`; empty → the parent. Missing → `baseterm_targetcontrol_invalid`. |
| `MarkOccurrences` | Whether the target is marked. Default `true`. |
| `MarkLimit` | Marks per term; `0` → every occurrence. Default `0`; `TDfn` `1`. Negative → `baseterm_marklimit_invalid`. |
| `CaseSensitive` | Default `true`. A mark keeps the text as written. |
| `Term` | Read-only: `Text`, or the text of a body that holds only strings. |
| `TermTitles` | Read-only: `Terms` plus the control's term with its `Title` (the control's term wins). |
| `MarkTarget` | Read-only: the resolved target control. |
| `HasContent` | Read-only: `Text` or body present. Without content the control renders nothing and only marks its `Terms`. |

## Matching

| Text | Marked |
|---|---|
| Whole word (Unicode letters, digits, marks, `_` bound a word) | yes; the longest term first; whitespace in a term matches any whitespace |
| Inside `SKIP_TAGS`: `abbr`, `dfn`, `var`, `code`, `pre`, `kbd`, `samp`, `script`, `style`, `textarea`, `title`, `option`, `optgroup`, `select`, `datalist`, `svg`, `math`, `noscript`, `iframe` | no |
| Attribute values, comments | no |
| Exempt markup (`data-prado-filter-exempt`) or an exempt control (`RenderFilterExempt`) | no; they are placeholders when the handler runs |
| Inside a skipped tag, under an element with `data-prado-filter-exempt="false"` | yes; the nearest attribute or skipped tag decides |
| Past `MarkLimit` | no; elements of the control's tag that hold the term count toward the limit |

- `getSkipTags()` is the override point for the skip list.
- With `MarkLimit` `1`, the control's own term is dropped when the control renders inside its target (its element is the term's mark), so a `TDfn` term costs no parse.

## Cost

- `markTerms` first runs one regex over the target's HTML (`getUnmarkedTermPattern`): `(*SKIP)(*FAIL)` passes over comments, tags, raw text elements, and skipped elements that hold only text. No unmarked term → no parse; ~2 ms on a 367 KB page.
- A parse that marks nothing restores the HTML string with `setFilterText`, so the output is byte-identical.
- Marking parses and serializes the target once (~30 ms for 367 KB, 10,000 elements); handlers on the same target share the parse.

## Callbacks

`TActivePageAdapter` renders a re-rendered active control with `render()`, so that control's own `onRenderFilter` handlers do not run. A term control whose target is a `TActivePanel` marks nothing in the callback response; target a plain control inside it, such as a `TPanel`.

## Accessibility

| Element | Chromium (`Accessibility.getFullAXTree`) |
|---|---|
| `<abbr title>` | role `Abbr`, name from `title` |
| `<dfn>` | role `term`, name from content |
| `<var>` | ignored; `generic` with an `id` |
| link or button holding a mark | name unchanged (`Learn HTML`) |

- Marks keep the text; only the element is added.
- Most screen readers do not announce an `abbr` `title` by default; a pointer shows it on hover only. Expand an abbreviation in the text at first use (WCAG 3.1.4); `MarkLimit="1"` marks the first use.
- Do not add `role` or `aria-label` to the control.
- Playwright's computed tree in Firefox and WebKit matches for the link and button names and the `term` role.

## Tests

- `tests/unit/Web/UI/WebControls/TBaseTermTest.php` (all three controls)
- `tests/playwright/web/TTermTestCase.spec.js` with `tests/harness/web/protected/pages/TermTest.page`

## See Also

- [TRenderFilterParameter](../TRenderFilterParameter.md)
- [TRenderFilterContext](../TRenderFilterContext.md)
- [TMark](TMark.md)

**@since 4.4.0**
