# Web/UI/WebControls/TI18NWebControl

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TI18NWebControl`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TI18NWebControl.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl`
**Uses trait:** `TI18NControlTrait`

## Overview
TI18NWebControl is a base class for web controls that require internationalization (I18N) support. It extends [TWebControl](./TWebControl.md) and mixes in `TI18NControlTrait`, which adds locale and charset awareness.

Use this as the base class for custom web controls that perform number/date formatting or output conversion and need to respect the application globalization settings.

## Key Properties (from `TI18NControlTrait`)

| Property | Type | Description |
|---|---|---|
| `Culture` | `string` | BCP 47 locale tag used for number/date formatting. Falls back to the application globalization culture when not set. |
| `Charset` | `string` | Character encoding for output conversion. Falls back to the application globalization charset, then UTF-8. |

## Usage

```php
// Extend TI18NWebControl for a custom I18N-aware control
class MyFormattedOutput extends TI18NWebControl
{
    public function render($writer)
    {
        $culture = $this->getCulture(); // resolved culture
        // format output using $culture ...
    }
}
```

```xml
<com:MyFormattedOutput Culture="fr_FR" />
```

## See Also

- [TWebControl](./TWebControl.md)

**@since 4.3.3**
