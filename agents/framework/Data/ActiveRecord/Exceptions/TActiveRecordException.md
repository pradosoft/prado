# TActiveRecordException

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](../INDEX.md) > [Exceptions](./INDEX.md) > [TActiveRecordException](./TActiveRecordException.md)

`Prado\Data\ActiveRecord\Exceptions\TActiveRecordException`

Base exception class for Active Records.

Inherits from `Prado\Exceptions\TDbException`.

## Description

`TActiveRecordException` is the base exception class for all Active Record errors. It extends the framework's standard exception hierarchy via `TDbException`. Accepts a message key that is looked up in the Prado exception message catalog.

The exception message file is determined by the preferred language set in the application. It searches for a language-specific message file (e.g., `messages-fr.txt`) before falling back to the default `messages.txt`.

## See Also

- [TActiveRecordConfigurationException](./TActiveRecordConfigurationException.md)
- `Prado\Exceptions\TDbException`
- `Prado\Prado::getPreferredLanguage()`

## Category

ActiveRecord Exceptions
