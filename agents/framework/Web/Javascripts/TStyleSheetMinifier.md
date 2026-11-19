# Web/Javascripts/TStyleSheetMinifier

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Javascripts](./INDEX.md) / **`TStyleSheetMinifier`**

## Class Info
**Location:** `framework/Web/Javascripts/TStyleSheetMinifier.php`
**Namespace:** `Prado\Web\Javascripts`
**Since:** 4.4.0

## Overview
The default [IMinifier](IMinifier.md) of `TStyleSheetHelper::minify()`: a single-pass, dependency-free CSS minifier. It removes comments and whitespace and leaves every other byte in place. Strings, escapes (`\ `), and unquoted `url(...)` values are single tokens copied unchanged. It works on bytes and changes only ASCII, so UTF-8 passes through.

## Rules

| Source | Result |
|---|---|
| comment | removed; it separates the tokens on either side as whitespace does (`a/**/b` → `a b`) |
| comment opening `/*!` | kept (license notices) |
| whitespace run | one space, or none next to the characters below |
| `;` before `}`, repeated `;` | removed |

| Character | Space removed |
|---|---|
| `{` `}` `;` `,` | either side |
| `:` | after only; `a :hover` and `a:hover` differ |
| `!` | before (`red !important` → `red!important`) |
| `(` | after only; `and (` and `and(` differ in `@media` |
| `)` | before only; `translate(1px) rotate(2deg)` needs the space after |
| `>` `+` `~` | either side at parenthesis depth 0 (selector combinators); inside parentheses `calc()`, `an+b`, and media ranges keep them |

It is conservative: a space before `:` and around `-`, `*`, and `/` stays. It does not rewrite values (colors, zeros, units) or merge rules.

## Tests
- `tests/unit/Web/Javascripts/TStyleSheetMinifierTest.php` — a case table and the jquery-ui `core.css` package (smaller, idempotent, license kept)
