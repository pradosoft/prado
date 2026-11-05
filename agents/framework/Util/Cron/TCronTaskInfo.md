# Util/Cron/TCronTaskInfo

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronTaskInfo`**

## Class Info
**Location:** `framework/Util/Cron/TCronTaskInfo.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TComponent`](../../TComponent.md)

## Overview
`TCronTaskInfo` is a value object that carries metadata for a cron task that a module wants to make available to [`TDbCronManager`](TDbCronManager.md). Modules return instances of this class from their `fxGetCronTaskInfos` global event handler so that the cron module can discover and register the task.

## Constructor

```php
new TCronTaskInfo(
    string $name,                      // short reference name (used as task name in DB)
    string|callable $task,             // class name or 'moduleId->method(args)' string
    string|IModule|null $moduleid = null,   // owning module ID
    string|null $title = null,         // human-readable title
    string|null $description = null    // short description
)
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Name` | string | Short reference name |
| `Task` | string | Task class or module method expression |
| `ModuleId` | string | ID of the module that owns this task |
| `Module` | TModule | Module instance (resolved from `ModuleId`) |
| `Title` | string | Human-readable title |
| `Description` | string | Short description |

## Usage

```php
public function fxGetCronTaskInfos($sender, $param): TCronTaskInfo
{
    return new TCronTaskInfo(
        'myclean',
        MyCleanupTask::class,
        $this->getId(),
        Prado::localize('My Cleanup Task'),
        Prado::localize('Cleans old records from the database.')
    );
}
```

## See Also

- [`TDbCronManager`](TDbCronManager.md)
- [`TCronTask`](TCronTask.md)
