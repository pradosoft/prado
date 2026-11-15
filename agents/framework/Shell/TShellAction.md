# Shell/TShellAction

### Directories
[framework](../INDEX.md) / [Shell](./INDEX.md) / **`TShellAction`**

## Class Info
**Location:** `framework/Shell/TShellAction.php`
**Namespace:** `Prado\Shell`

## Overview
Abstract base class for CLI shell commands. Subclass to create new commands.

## Creating a Command

```php
class MyAction extends TShellAction
{
    protected $action = 'mycmd';      // Command name
    protected $methods = ['run'];     // Subcommands
    protected $parameters = ['arg1']; // Required params per method
    protected $optional = [null];      // Optional params
    protected $description = [
        'Does something useful.',     // Command description
        'Runs the main operation.'     // Method description
    ];

    public function actionRun($args)
    {
        $arg = $args[1];  // First argument
        $this->getWriter()->writeLine("Running with: $arg");
        return true;
    }
}
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `action` | string | Command name, matched literally by `isValidAction()` |
| `defaultMethod` | int | Index of the method run without `/<method>`, default `0` |
| `methods` | array | Subcommand names; `action<Method>()` implements each, a `-` dropped |
| `parameters` | array | Required parameters per method |
| `optional` | array | Optional parameters per method |
| `description` | array | [0]=command, [1+]=method descriptions |

## Methods

### `getWriter(): TShellWriter` / `setWriter(TShellWriter $writer)`

The output writer; `TShellApplication::runService()` sets it before dispatch.

### `getApplication(): ?TShellApplication`

The running application, or null when it is not a `TShellApplication`.

### `options($actionID): array`

Override to define CLI options for the action.

### `optionAliases(): array`

Override to define option aliases (e.g., `['u' => 'user']`).

### `isValidAction($args): ?string`

Returns the method named by `$args[0]` (`<action>` or `<action>/<method>`), or null when the action name differs, the method is unknown, or fewer arguments than the method's `$parameters` follow.

### `renderHelpCommand($cmd)`

Render help for a specific command.

### `renderHelp(): string`

Render general help for the action.

### Helper Methods

- `createDirectory($dir, $mask)` - Create directory with permissions
- `createFile($filename, $content)` - Create file with content

## See Also

- [TShellApplication](./TShellApplication.md) - Application that hosts actions
- [THelpAction](./Actions/THelpAction.md) - Built-in help command