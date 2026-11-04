# Web/UI/WebControls/TUnicodeRestrictionLevel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TUnicodeRestrictionLevel`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TUnicodeRestrictionLevel.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TEnumerable`

## Overview
The UTS #39 restriction levels for [TUnicodeValidator](./TUnicodeValidator.md), from the most to the least restrictive.

| Value | Allows | Spoofchecker |
|---|---|---|
| `Ascii` | ASCII only | `ASCII` |
| `SingleScript` | one script, plus Common and Inherited | `SINGLE_SCRIPT_RESTRICTIVE` |
| `HighlyRestrictive` | SingleScript, or Latin + Han + Hiragana + Katakana, Latin + Han + Bopomofo, Latin + Han + Hangul | `HIGHLY_RESTRICTIVE` |
| `ModeratelyRestrictive` | HighlyRestrictive, or Latin and one other recommended script except Cyrillic and Greek | `MODERATELY_RESTRICTIVE` |
| `MinimallyRestrictive` | any mix of scripts | `MINIMALLY_RESTRICTIVE` |
| `Unrestricted` | no restriction level check | none |

**@since 4.4.0**
