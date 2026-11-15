# Shell/Actions/SUMMARY.md

Built-in CLI action handlers for the Prado shell (`prado-cli`); each class provides one or more subcommands.

## Classes

- **`TDbParameterAction`** — Action `param`; lists, gets, and sets `TDbParameterModule` parameters; subcommands: `index`, `get`, `set`.

- **`TFlushCachesAction`** — Action `cache`; lists and flushes application cache modules implementing `ICache`; subcommands: `index`, `flush`, `flush-all`.

- **`THelpAction`** — Action `help`; prints the general help, or the subcommands of one command.

- **`TPhpShellAction`** — Action `shell`; launches the PsySH REPL with the Prado application bound.

- **`TWebServerAction`** — Action `http`; `http/serve` starts the PHP built-in development web server with `--address=host:port`.

The `cron` action, `TShellCronAction` and `TShellDbCronAction`, lives in `Util/Cron/`.
