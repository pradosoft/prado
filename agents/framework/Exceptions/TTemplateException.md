# Exceptions/TTemplateException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TTemplateException`**

## Class Info
**Location:** `framework/Exceptions/TTemplateException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Represents an exception caused by invalid template syntax. Extends `TConfigurationException`.

## Hierarchy

```
TTemplateException
└── [TConfigurationException](./TConfigurationException.md)
    └── [TSystemException](./TSystemException.md)
        └── [TException](./TException.md)
            └── Exception
```

## Key Features

- Stores template source or file
- Tracks line number of error
- Used for .page and .tpl templates

## Properties

### TemplateSource / TemplateFile

```php
public function getTemplateSource(): string
public function setTemplateSource(string $value): void
```

The inline template source code (if no file).

```php
public function getTemplateFile(): string
public function setTemplateFile(string $value): void
```

The template file path.

### LineNumber

```php
public function getLineNumber(): int
public function setLineNumber(int $value): void
```

The line number where the error occurred.

## Usage

```php
$ex = new TTemplateException();
$ex->setTemplateFile('/path/to/template.page');
$ex->setLineNumber(42);
throw $ex;
```

## See Also

- `[TConfigurationException](./TConfigurationException.md)` - Parent exception
- `TTemplate` - Template class
