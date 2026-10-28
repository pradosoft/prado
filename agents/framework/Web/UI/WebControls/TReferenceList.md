# Web/UI/WebControls/TReferenceList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TReferenceList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TReferenceList.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement` (default tag `section`)
**Uses:** [TEncodeTrait](./TEncodeTrait.md)

## Overview
TReferenceList renders the notes of the [TFootnote](./TFootnote.md) references on the page as an `<ol>` in a `<section>` headed by `HeaderText`.

```html
<section id="notes" aria-labelledby="notes_label">
	<h2 id="notes_label">Notes</h2>
	<ol>
		<li id="notes_note1">Like ASP.NET Web Forms. <a href="#fn_ref" aria-label="Back to reference 1">↩</a></li>
		<li id="notes_note2">See the manual. <a href="#a_ref" aria-label="Back to reference 2a">↩a</a> <a href="#b_ref" aria-label="Back to reference 2b">↩b</a></li>
	</ol>
</section>
```

## Key Properties

| Property | Description |
|---|---|
| `HeaderText` | Default `Notes`; '' → no heading, no `aria-labelledby`, no reference description |
| `HeaderLevel` | 1–6; default 2; another value throws `referencelist_headerlevel_invalid` |
| `BackLinkText` | Default `↩`; lettered a, b, … when a note has several references |
| `BackLinkLabel` | Accessible name; `{0}` is the reference (`1`, `2a`); default `Back to reference {0}` |
| `Encode` | HTML-encodes HeaderText and BackLinkText; default false |

## Numbering

- `getNotes()` collects the visible footnotes of this list with `TPage::findControlsByType(TFootnote)` in page order, joining keyed ones; `onPreRender()` clears the cache. The list may be before or after its footnotes.
- `getNoteNumber($footnote)` → 1-based number, or null when the footnote is not in this list.
- IDs: section `<ClientID>` (EnsureId), heading `<ClientID>_label`, note `<ClientID>_note<n>`.
- A list without notes renders nothing.
- A callback that renders footnotes again also renders their list again, so numbers and notes match.

## Accessibility

- The named section is a `region` landmark; its heading is in the heading list.
- Each back link's `aria-label` says which reference it returns to; the arrow alone does not.

## Tests

- `tests/unit/Web/UI/WebControls/TFootnoteTest.php`
- `tests/playwright/web/TFootnoteTestCase.spec.js` with `tests/harness/web/protected/pages/FootnoteTest.page`

## See Also

- [TFootnote](./TFootnote.md)

**@since 4.4.0**
