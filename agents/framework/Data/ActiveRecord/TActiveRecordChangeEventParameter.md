# TActiveRecordChangeEventParameter

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](./INDEX.md) > [TActiveRecordChangeEventParameter](./TActiveRecordChangeEventParameter.md)

**Location:** `framework/Data/ActiveRecord/TActiveRecordChangeEventParameter.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview

`TActiveRecordChangeEventParameter` encapsulates parameter data for ActiveRecord change events (insert, update, delete).

## Properties

- `IsValid` - Set to false to prevent the change operation from being performed

## Usage

```php
$record->onInsert[] = function($sender, $param) {
    if ($someCondition) {
        $param->IsValid = false; // Prevent insert
    }
};
```

## See Also

- [TActiveRecord](./TActiveRecord.md) - Base Active Record class