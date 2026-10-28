# Web/UI/WebControls/TBaseEdit

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBaseEdit`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBaseEdit.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement` (abstract)
**Uses:** [TCiteTrait](./TCiteTrait.md)

## Overview
TBaseEdit is the base class of the HTML edit elements, [TIns](./TIns.md) and [TDel](./TDel.md), which mark text added to or removed from a document.

## Key Properties

| Property | Description |
|---|---|
| `Cite` | URL of the explanation of the edit; renders `cite` when set |
| `DateTime` | When the edit was made; renders `datetime` when set |

`DateTime` is stored as the string it renders:

| Value | Stored |
|---|---|
| `DateTimeInterface` | `Y-m-d\TH:i:sP` (`DateTimeInterface::ATOM`) |
| int, float, or numeric string | Unix timestamp, formatted as ATOM in UTC |
| Other string | Trimmed, as given; a valid HTML date string with an optional time |

## Accessibility

- Browsers expose `<ins>` with the `insertion` role and `<del>` with the `deletion` role.
- Most screen readers do not announce them by default, and none reads `cite` or `datetime`.
- An edit that readers need to know about says so in its text or a visually hidden span, such as "removed:" before the deleted text.
- Browsers strike through deleted text and underline inserted text, so color is not the only mark.

## Tests

- `tests/unit/Web/UI/WebControls/TInsTest.php`, `TDelTest.php`

**@since 4.4.0**
