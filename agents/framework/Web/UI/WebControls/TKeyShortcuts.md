# Web/UI/WebControls/TKeyShortcuts

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TKeyShortcuts`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TKeyShortcuts.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0 (#1408)

## Overview
Static helper that validates and normalizes keyboard shortcuts in the `aria-keyshortcuts` syntax, and lists the reserved shortcuts. [TWebControl](TWebControl.md)`.KeyShortcuts` uses it; the client registry `Prado.KeyShortcuts` (`keyshortcuts` package, `prado/keyshortcuts/keyshortcuts.js`) holds a copy of the same rules.

```php
TKeyShortcuts::normalize('ctrl+s cmd+s');   // 'Control+S Meta+S'
TKeyShortcuts::normalize('shift+alt+p');    // 'Alt+Shift+P'
TKeyShortcuts::isReserved('Control+Z');     // true
```

| Method | Result |
|---|---|
| `normalize($value)` | normalized space-separated list; `''` for empty; throws `TInvalidDataValueException` (`keyshortcuts_invalid`) |
| `normalizeShortcut($shortcut)` | one normalized shortcut, or null |
| `isReserved($shortcut)` / `getReservedShortcuts()` | the reserved set |

## Syntax

- Modifiers in order `Control`, `Alt`, `Shift`, `Meta`; aliases `Ctrl`, `Option`, `Cmd`, `Command`.
- One key: a single character (letters uppercase, unless the uppercase is two characters, as `ß`), or a `KeyboardEvent.key` name: `Space`, `Plus`, `Enter`, `Escape`, `Tab`, `Backspace`, `Delete`, `Insert`, `Home`, `End`, `PageUp`, `PageDown`, arrows, `F1`–`F24`. Aliases `Esc`, `Return`, `Del`, `Ins`, `Spacebar`, `Up`, `Down`, `Left`, `Right`.
- `Space` and `Plus` name the space and plus keys. Repeats are removed.

Reserved: Tab, Shift+Tab, Control+(Shift+)Tab, Enter, Space, arrows, Home, End, PageUp, PageDown, F5, F6, F11, F12, Alt+ArrowLeft/Right, and with Control or Meta: A C V X Z Y L N Q R T W Plus `-` `=` `0`, Shift+N T W Z. Control+S, Control+P, Control+F, and Escape are not reserved.

## Client registry (`Prado.KeyShortcuts`)

One document `keydown` listener (bubble phase). Bindings:

| Source | Shape |
|---|---|
| Declared element | `aria-keyshortcuts` + `data-prado-keyshortcuts="<scope id or ''>"` (+ `data-prado-keyshortcuts-reserved`). Read from the DOM at each key press, so callback replace and morph need nothing. |
| `register(keys, action, {scope, allowReserved})` | action is an element, an element ID, or `fn(event, shortcut)`; returns a remover. An element replaced by a callback is looked up again by its ID. |

Action: button, `a[href]`, `area[href]`, summary, button/submit/reset/image/checkbox/radio input, or a button-like role → `click()`; otherwise `focus()`. A cancelable bubbling `prado:keyshortcut` event (`detail.shortcut`, `detail.event`) precedes it.

| Condition | Result |
|---|---|
| `defaultPrevented`, `isComposing`, keyCode 229, AltGraph held | ignored |
| focus in an editable field (text input, textarea, select, contenteditable, textbox/searchbox/combobox/spinbutton role) and no Control/Alt/Meta and not F1–F24 | ignored |
| reserved shortcut without opt-in | ignored |
| element disabled, inert, hidden (`checkVisibility()`), disconnected | binding skipped |
| focus outside the scope element | binding skipped |
| modal `<dialog>` open and the element (or a function's scope) outside it | binding skipped |
| page-wide character-key shortcut and `setCharacterKeysEnabled(false)` | binding skipped |
| match | `preventDefault()`; runs unless `event.repeat` |

Precedence: deepest scope containing focus, then page-wide; at equal depth registrations (latest first) beat declared elements (document order).

Key matching: `event.key`; a shifted symbol also matches without Shift (`?`); when `event.key` is not an ASCII letter or digit, the `event.code` letter or digit also matches (other layouts, macOS Option characters). Control and Meta are distinct; list `Control+S Meta+S` for Windows and macOS.

## Accessibility (WCAG 2.1.4 Character Key Shortcuts)

- A character-key shortcut (letter, digit, punctuation, or symbol, alone or with Shift) is either scoped (active only while focus is inside its scope) or page-wide and turn-off-able: `Prado.KeyShortcuts.setCharacterKeysEnabled(false)`, stored in localStorage under `prado:keyshortcuts:characterKeys`. A page with page-wide character keys offers the user a control that calls it.
- Remapping: the application sets `KeyShortcuts` from a user preference.
- `aria-keyshortcuts` names the keys to assistive technology; it stays rendered when character keys are off.
- `AccessKey` is separate: browsers bind it to Alt, Alt+Shift, or Control+Option. An `Alt+<letter>` shortcut can collide with an access key of the same letter.
- TKeyboard keys handle Enter and Space and call `preventDefault()`, so the registry ignores those presses.

## Without mbstring

`normalizeKey()` tests a token for one character and uppercases it through `isCharacter()` and `upperCharacter()`: `mb_strlen()` and `mb_strtoupper()` with the extension loaded, else one UTF-8 code point by `preg_match()` and `\IntlChar::toupper()` (simple case mapping, so `ß` stays `ß` either way). The static `_mbstring` flag is resolved once; tests force it to `false`.

## Omissions

- No command palette (P3 in #1408).
- Declared elements inside a shadow root are not found; use `register()`.
- List controls (TCheckBoxList, TRadioButtonList, TBulletedList) render the shortcut on their container element.
