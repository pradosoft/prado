# Data/SqlMap/DataMapper/TSqlMapException

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [DataMapper](./INDEX.md) / **`TSqlMapException`**

## Class Info
**Location:** `framework/Data/SqlMap/DataMapper/TSqlMapException.php`
**Namespace:** `Prado\Data\SqlMap\DataMapper`

## Overview
`Prado\Data\SqlMap\DataMapper\TSqlMapException`

Base SqlMap exception.

## Description

`TSqlMapException` is the base exception for all SqlMap errors.

Its constructor follows `TException`: `new TSqlMapException('message_code', $param0, ...)`, or an integer error code first, `new TSqlMapException($errorCode, 'message_code', ...)`; a trailing `Throwable` is the previous exception. A `SimpleXMLElement` parameter expands to its tag name with attribute names and values. Messages come from `DataMapper/messages.txt`, ICU MessageFormat patterns like the framework files. Since 4.3.3, `getErrorCode()` returns the message code; it returned the message text.

## See Also

- [TSqlMapConfigurationException](./TSqlMapConfigurationException.md)
- [TSqlMapExecutionException](./TSqlMapExecutionException.md)
- [TSqlMapDuplicateException](./TSqlMapDuplicateException.md)
- [TSqlMapUndefinedException](./TSqlMapUndefinedException.md)

## Category

SqlMap DataMapper
