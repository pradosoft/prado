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
1. Uses the `--user` option, or prompts for the username
2. Uses the `--password` option, or prompts for the password with hidden input
3. Calls `TAuthManager::login()` with the credentials and discards the password
4. On failure, displays an error and throws `TExitException`, which ends the process

## Options

| Option | Alias | Description |
|--------|-------|-------------|
| `--user <name>` | `-u` | Set username |
| `--password <pass>` | `-p` | Set password |

## Events

Intercepts `OnAuthenticate` event to perform login.

## See Also

- [TAuthManager](../Security/TAuthManager.md) - Authentication manager
- [TShellApplication](./TShellApplication.md) - CLI application