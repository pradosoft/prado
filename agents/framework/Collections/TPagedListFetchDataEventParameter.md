# Collections/TPagedListFetchDataEventParameter

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPagedListFetchDataEventParameter`**

## Class Info
**Location:** `framework/Collections/TPagedListFetchDataEventParameter.php`
**Namespace:** `Prado\Collections`

## Overview
Event parameter for the [TPagedList](./TPagedList.md)::OnFetchData event. Used in custom paging mode to request data from a handler.

## Properties

### NewPageIndex

```php
public function getNewPageIndex(): int
```

The zero-based index of the new page being requested.

### Offset

```php
public function getOffset(): int
```

The offset (starting index) of the first item needed.

### Limit

```php
public function getLimit(): int
```

The maximum number of items requested.

### Data

```php
public function getData(): mixed
public function setData(mixed $value): void
```

The data returned by the handler. Set this in your event handler.

## Usage

```php
$list->attachEventHandler('OnFetchData', function($sender, $param) {
    $offset = $param->Offset;    // e.g., 20
    $limit = $param->Limit;     // e.g., 10
    $param->Data = MyModel::find()
        ->offset($offset)
        ->limit($limit)
        ->all();
});
```

## See Also

- [TPagedList](./TPagedList.md) - The list using this event parameter
