# TActiveRecordCriteria

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [ActiveRecord](./INDEX.md) > [TActiveRecordCriteria](./TActiveRecordCriteria.md)

**Location:** `framework/Data/ActiveRecord/TActiveRecordCriteria.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview

`TActiveRecordCriteria` extends [`TSqlCriteria`](../DataGateway/TSqlCriteria.md) with Active Record-specific options for finder methods.

## Usage

```php
$criteria = new TActiveRecordCriteria();
$criteria->Condition = 'username = :name';
$criteria->Parameters[':name'] = 'admin';
$criteria->OrdersBy['created'] = 'desc';
$criteria->Limit = 10;
$criteria->Offset = 20;

$users = UserRecord::finder()->findAll($criteria);
```

## See Also

- [TActiveRecord](./TActiveRecord.md) - Base Active Record class
- [TSqlCriteria](../DataGateway/TSqlCriteria.md) - Base criteria class