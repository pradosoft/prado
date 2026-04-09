# TMarkdown

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TMarkdown](./TMarkdown.md)

**Location:** `framework/Web/UI/WebControls/TMarkdown.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Renders Markdown text as HTML. Extends `TTextHighlighter`, which in turn handles syntax highlighting of fenced code blocks. `TMarkdown` post-processes the Markdown output to apply syntax highlighting to `<pre><code class="language-X">` blocks.

Requires a Markdown library (e.g., `league/commonmark` or `michelf/php-markdown`) installed via Composer.

Extends `[TTextHighlighter](./TTextHighlighter.md)`.

## Key Method

```php
protected function processText(string $text): string
// Called by TTextHighlighter to transform the raw text.
// 1. Renders Markdown → HTML via the configured Markdown library
// 2. Applies syntax highlighting to fenced code blocks matching:
//    <pre><code class="language-{lang}">...</code></pre>
```

The regex used:
```
/<pre><code class="language-(\w+)">((.|\n)*?)<\/code><\/pre>/im
```

## Key Properties (inherited from TTextHighlighter)

| Property | Description |
|----------|-------------|
| `Text` | Markdown text to render |
| `Language` | Default language for code blocks without a class |
| `ShowLineNumbers` | Show line numbers in highlighted blocks |
| `TabSize` | Tab width for code blocks |
| `CssClass` | CSS class for the container |

## Usage

```xml
<com:TMarkdown>
# Hello World

This is **markdown** text.

```php
echo "Hello!";
```
</com:TMarkdown>
```

Or programmatically:

```php
$md = new TMarkdown();
$md->Text = '# Hello World';
```

## Patterns & Gotchas

- **Requires Markdown library** — `TMarkdown` calls a Markdown parser. The specific library depends on the PRADO version and Composer dependencies. `league/commonmark` is the standard choice.
- **Fenced code block syntax highlighting** — only blocks with an explicit language class (`language-php`, `language-js`, etc.) are highlighted. Plain `<pre><code>` blocks are not.
- **`processText()` override** — if you need to customize Markdown rendering (e.g., add extensions, configure the parser), extend `TMarkdown` and override `processText()`.
- **XSS** — Markdown rendering can include raw HTML if the parser allows it. Configure the Markdown library to strip unsafe HTML if accepting user input.
