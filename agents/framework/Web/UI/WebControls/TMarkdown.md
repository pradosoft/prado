# Web/UI/WebControls/TMarkdown

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TMarkdown`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TMarkdown.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Renders Markdown, its body or `Text`, as HTML in a `<div>`. [Parsedown](https://parsedown.org) (`erusev/parsedown`, a Composer dependency) converts it. Fenced code blocks render as `<pre><code class="language-x">`, and the client class `Prado.WebUI.TMarkdown` highlights each block with highlight.js.

Extends [TTextHighlighter](./TTextHighlighter.md).

## Rendering

- `getTagName()` is `div`; `renderBeginTag()` registers the `texthighlight` script package and renders the `div` with the ClientID.
- `processText()` returns Parsedown's HTML unchanged.
- The client highlights every `pre > code` in the `div`; a block without a language has its language detected. Prose and inline `code` are not highlighted.

## Key Properties (inherited from TTextHighlighter)

| Property | Applies to TMarkdown |
|----------|----------------------|
| `Text` | Markdown text; when empty, the rendered body is the Markdown |
| `ShowLineNumbers` | Every code block of more than one line |
| `EnableCopyCode` | A copy button on every code block |
| `TabSize`, `SyntaxStyle` | Every code block |
| `Language`, `EncodeHtml` | Do not apply |

## Usage

```xml
<com:TMarkdown ShowLineNumbers="true">
# Hello World

This is **markdown** text.

~~~php
echo "Hello!";
~~~
</com:TMarkdown>
```

## Patterns & Gotchas

- **Raw HTML** — Parsedown renders HTML written in the Markdown as is. Render Markdown from users inside a [TSafeHtml](./TSafeHtml.md).
- **Template entities** — `TTextProcessor::addParsedObject()` decodes HTML entities in the template body, so `&lt;b&gt;` in the body is the Markdown `<b>`.
- **Before 4.4.0** — TMarkdown rendered its HTML inside TTextHighlighter's `<pre><code>`, highlighted the whole document as one language, and wrote the last code block's language into `Language`. `highlightCode()` is deprecated and unused.
