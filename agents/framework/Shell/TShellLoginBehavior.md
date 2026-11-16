# Shell/TShellLoginBehavior

### Directories
[framework](../INDEX.md) / [Shell](./INDEX.md) / **`TShellLoginBehavior`**

## Class Info
**Location:** `framework/Shell/TShellLoginBehavior.php`
**Namespace:** `Prado\Shell`

## Overview
Behavior for [TAuthManager](../Security/TAuthManager.md) that prompts for CLI login credentials before shell commands execute.

## Usage

Attach to [TAuthManager](../Security/TAuthManager.md) using [TBehaviorsModule](../Util/TBehaviorsModule.md) in application configuration:

```xml
<modules>
    <module id="auth" class="Prado\Security\TAuthManager">
        <auth>
            <users>
                <user name="admin" password="secret" roles="admin"/>
            </users>
        </auth>
    </module>

    <module id="behaviors" class="Prado\Util\TBehaviorsModule">
        <behavior name="shellLogin" Class="Prado\Shell\TShellLoginBehavior" AttachTo="module:auth"/>
    </module>
</modules>
```

Note: `<behavior>` children are not supported directly within `<module>` configuration. Use `TBehaviorsModule` to attach behaviors.

## Behavior

When attached and running in CLI:
1. Uses the `--user` option, or prompts for the username with `readLine()`
2. Uses the `--password` option, or prompts for the password with `readPassword()`; on a terminal outside Windows, `setTerminalEcho()` turns echo off with `stty -echo` while it is read (4.4.0)
3. Calls `TAuthManager::login()` with the credentials and discards the password
4. On failure, displays an error and throws `TExitException`, which ends the process

## Options

| Option | Alias | Description |
|--------|-------|-------------|
| `--user <name>` | `-u` | Set username |
| `--password <pass>` | `-p` | Set password |

A `--password` on the command line is visible to other users in the process list and stays in the shell history. Omit it to be prompted.

## Testing Seams (4.4.0)

`readLine()`, `setTerminalEcho()`, and `isStdInTerminal()` are protected; a test subclass scripts the input and the terminal.

## Events

Intercepts `OnAuthenticate` event to perform login.

## See Also

- [TAuthManager](../Security/TAuthManager.md) - Authentication manager
- [TShellApplication](./TShellApplication.md) - CLI application