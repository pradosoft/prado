# Shell/TShellWriter

### Directories
[framework](../INDEX.md) / [Shell](./INDEX.md) / **`TShellWriter`**

## Class Info
**Location:** `framework/Shell/TShellWriter.php`
**Namespace:** `Prado\Shell`

## Overview
Terminal output writer with ANSI color formatting, cursor movement, and UI widgets.

## Color Constants

**Text Colors:**
| Constant | Value |
|----------|-------|
| `BLACK` | 30 |
| `RED` | 31 |
| `GREEN` | 32 |
| `YELLOW` | 33 |
| `BLUE` | 34 |
| `MAGENTA` | 35 |
| `CYAN` | 36 |
| `LIGHT_GRAY` | 37 |
| `DARK_GRAY` | 90 |
| `LIGHT_RED` | 91 |
| `LIGHT_GREEN` | 92 |
| `LIGHT_YELLOW` | 93 |
| `LIGHT_BLUE` | 94 |
| `LIGHT_MAGENTA` | 95 |
| `LIGHT_CYAN` | 96 |
| `WHITE` | 97 |
| `DEFAULT` | 39 |

**Style Constants:**
`BOLD`, `DARK`, `ITALIC`, `UNDERLINE`, `BLINK`, `REVERSE`, `CONCEALED`, `CROSSED`, `FRAMED`, `ENCIRCLED`, `OVERLINED`

**Background Colors:** `BG_BLACK`, `BG_RED`, `BG_GREEN`, etc. (40-47, 100-107)

## Usage

```php
$writer = new TShellWriter(new TStdOutWriter());

// Simple colored output
$writer->writeLine('Success!', [TShellWriter::GREEN, [TShellWriter](./TShellWriter.md)::BOLD]);

// Error block
$writer->writeError('Something went wrong');

// Table widget
$writer->writeLine($writer->tableWidget([
    'headers' => ['Name', 'Status'],
    'rows' => [['Alice', 'Active'], ['Bob', 'Inactive']]
]));
```

## Methods

### Output
- `write($str, $attr)` - Write with optional formatting
- `writeLine($str, $attr)` - Write with newline
- `writeError($text)` - Formatted error block
- `flush()` - Flush underlying writer

### Formatting
- `format($str, $attr)` - Apply ANSI formatting
- `unformat($str)` - Remove ANSI formatting
- `pad($str, $len, $pad, $place)` - Pad string

### Cursor Movement
- `moveCursorUp($rows)` - Move cursor up
- `moveCursorDown($rows)` - Move cursor down
- `moveCursorForward($steps)` - Move cursor right
- `moveCursorBackward($steps)` - Move cursor left
- `moveCursorNextLine($lines)` - Move to next line
- `moveCursorPrevLine($lines)` - Move to previous line
- `moveCursorTo($column, $row)` - Move to position

### Screen Control
- `clearScreen()` - Clear entire screen
- `clearScreenBeforeCursor()` - Clear to beginning
- `clearScreenAfterCursor()` - Clear to end
- `clearLine()` - Clear current line
- `hideCursor()` / `showCursor()` - Cursor visibility
- `saveCursorPosition()` / `restoreCursorPosition()` - Save/restore

### Utilities
- `wrapText($text, $indent, $refresh)` - Word wrap
- `getScreenSize($refresh)` - Get terminal dimensions
- `tableWidget($table)` - Render table