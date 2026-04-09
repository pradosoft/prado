# TTextProcessor

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTextProcessor](./TTextProcessor.md)

**Location:** `framework/Web/UI/WebControls/TTextProcessor.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTextProcessor is the base class for controls that process or transform text content. The text to be processed can be specified via the Text property or enclosed within the control. Subclasses must implement the processText method.

## Key Properties/Methods

- `getText()` / `setText($value)` - Text to be processed
- `processText($text)` - Abstract method to process text (must be implemented by subclasses)
- `renderContents($writer)` - Renders processed text

## See Also

- [TWebControl](./TWebControl.md)
