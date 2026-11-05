# Web/UI/WebControls/TFootnote

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFootnote`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFootnote.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TSup](./TSup.md) (default tag `sup`)

## Overview
TFootnote is a numbered footnote reference: a superscript link to its note. Its child controls are the note, rendered in a [TReferenceList](./TReferenceList.md) through `renderNote()`; `renderContents()` renders only the link.

## Template Usage

```xml
<p>Prado is component based.<com:TFootnote>Like ASP.NET Web Forms.</com:TFootnote></p>
<p>Shared source.<com:TFootnote Key="manual">See the manual.</com:TFootnote> Again.<com:TFootnote Key="manual" /></p>
<com:TReferenceList />
```

`<sup id="…"><a id="…_ref" href="#…_note1" aria-describedby="…_label">1</a></sup>`

## Key Properties

| Property | Description |
|---|---|
| `ReferenceList` | ID path of the list, from the footnote's naming container; '' → first TReferenceList on the page; none found → `footnote_referencelist_missing` |
| `Key` | Footnotes with the same Key in a list are one note: one number, content from the first with child controls, a back link to each |

| Method | Description |
|---|---|
| `getReferenceListControl()` | The resolved list |
| `getReferenceClientID()` | `<ClientID>_ref`, the back link target |
| `renderNote($writer)` | Renders the child controls (the note) |

- A footnote that is not visible (itself or an ancestor) has no number and no note.
- A footnote whose list does not number it renders an empty `<sup>`.

## Accessibility

- The reference is a link named by the number and described by the list heading: "1, link, Notes".
- Following it moves to the note; the next Tab reaches the note's back link (Chromium, Firefox).
- An inline link in a sentence is exempt from the 24 px target size (WCAG 2.5.8).

## Tests

- `tests/unit/Web/UI/WebControls/TFootnoteTest.php`
- `tests/playwright/web/TFootnoteTestCase.spec.js` with `tests/harness/web/protected/pages/FootnoteTest.page`: names, descriptions, navigation, back links, and the Chromium accessibility tree

## See Also

- [TReferenceList](./TReferenceList.md)
- [TSup](./TSup.md)

**@since 4.4.0**
