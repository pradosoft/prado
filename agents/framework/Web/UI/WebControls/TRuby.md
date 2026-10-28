# Web/UI/WebControls/TRuby

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRuby`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRuby.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`
**Uses:** [TEncodeTrait](./TEncodeTrait.md)

## Overview
TRuby represents the HTML `<ruby>` element: base text with small annotations above or beside it, such as the reading of East Asian characters. `Base` and `Annotation` render the base text and its `<rt>`, with `<rp>` parentheses while `ShowParentheses` is true; the child controls ([TRubyText](./TRubyText.md), [TRubyParenthesis](./TRubyParenthesis.md), text) render after them.

The default tag is `ruby`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TRuby Lang="ja" Base="漢字" Annotation="かんじ" />
<!-- <ruby lang="ja">漢字<rp>(</rp><rt>かんじ</rt><rp>)</rp></ruby> -->

<com:TRuby Lang="ja">東<com:TRubyParenthesis>（</com:TRubyParenthesis><com:TRubyText>とう</com:TRubyText><com:TRubyParenthesis>）</com:TRubyParenthesis></com:TRuby>
```

## Key Properties

| Property | Description |
|---|---|
| `Base` | Base text, rendered first |
| `Annotation` | Rendered in an `<rt>` after Base when set |
| `ShowParentheses` | `<rp>` parentheses around Annotation; default true |
| `OpenParenthesis`, `CloseParenthesis` | Default `(` and `)`; full-width `（` `）` for Japanese |
| `Encode` | HTML-encodes Base, Annotation, and the parentheses; default false |

## Accessibility

- Screen readers read the base text, then the annotation; a browser that renders ruby hides the `<rp>` parentheses (`display: none`).
- Text in another language sets `Lang`, so a screen reader uses that language's voice.

## Tests

- `tests/unit/Web/UI/WebControls/TRubyTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TRubyText](./TRubyText.md), [TRubyParenthesis](./TRubyParenthesis.md)

**@since 4.4.0**
