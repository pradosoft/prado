# Util/Cron/TCronTaskInfo

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronTaskInfo`**

## Class Info
**Location:** `framework/Util/Cron/TCronTaskInfo.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TCronTaskInfo is a helper class that distributes metadata for application cron tasks. It encapsulates task information including name, task class/module method, module ID, title, and description for adding cron tasks via TDbCronModule.

## Key Properties/Methods

- `getName()` / `setName($name)` - The short reference name of the task
- `getTask()` / `setTask($task)` - The class name or module/method string for the task
- `getModuleId()` / `setModuleId($moduleId)` - The module ID servicing the task
- `getModule()` - Gets the module instance from module ID
- `getTitle()` / `setTitle($title)` - The title of the task
- `getDescription()` / `setDescription($description)` - A short description of the task
- `__construct($name, $task, $moduleid, $title, $description)` - Constructor setting all main properties

## See Also

- [TDbCronModule](TDbCronModule.md)
- [TCronTask](TCronTask.md)
