# Web/UI/WebControls/TTextHighlighter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTextHighlighter`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTextHighlighter.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TTextHighlighter performs syntax highlighting on its body content using highlight.js. It supports many programming languages and styles. Content can be shown with line numbers and a copy code button. Requires THead on the page.

## Key Properties/Methods

- `getLanguage()` / `setLanguage(string)` - Syntax language (default: 'php')
- `getSyntaxStyle()` / `setSyntaxStyle(string)` - highlight.js style (default: 'default')
- `getShowLineNumbers()` / `setShowLineNumbers(bool)` - Show line numbers
- `getEnableCopyCode()` / `setEnableCopyCode(bool)` - Show copy code link
- `getTabSize()` / `setTabSize(int)` - Tab size (default: 4)
- `getEncodeHtml()` / `setEncodeHtml(bool)` - HTML encode content (default: true)
- `processText($text)` - Processes text for highlighting

## See Also

- [TTextProcessor](./TTextProcessor.md)
- [THead](./THead.md)
