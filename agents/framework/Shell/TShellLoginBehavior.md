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
```

Note: `<behavior>` children are not supported directly within `<module>` configuration. Use `TBehaviorsModule` to attach behaviors.

## Behavior

When attached and running in CLI:
1. Prompts for username (or uses `--user` option)
2. Prompts for password (or uses `--password` option, hidden input)
3. Calls `TAuthManager::login()` with credentials
4. On failure, displays error and exits

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