# CLAUDE.md

This file provides guidance to Agents when working with code in this repository.

## What This Is

**Prado** is a component-based, event-driven PHP web framework. All framework source lives under `framework/` (PSR-4 namespace `Prado\`). Tests mirror that structure under `tests/unit/`. Current version: **4.3.2**; next release: **4.3.3**.

## Commands

```bash
# Run all unit tests
vendor/bin/phpunit --testsuite unit

# Run tests for a specific class, function, or directory
vendor/bin/phpunit --testsuite unit --filter <test function, class, or directory>

# Static analysis
vendor/bin/phpstan analyse framework/ --memory-limit=512M

# Code style check (dry-run)
vendor/bin/php-cs-fixer fix --dry-run framework/

# Code style fix (apply)
vendor/bin/php-cs-fixer fix framework/

# Generate API documentation
composer gendoc

# Functional tests (require Selenium Server + prado-demos repo)
composer functionaltest
```

### Full Check (required before git commit)

Run these four checks **in order** — all must pass:

1. PHP compile check (`php -l`)
2. `vendor/bin/php-cs-fixer fix --dry-run framework/`
3. `vendor/bin/phpstan analyse framework/ --memory-limit=512M`
4. `vendor/bin/phpunit --testsuite unit`

> **Never add or change phpunit command options** when unit testing — only run project unit tests as specified above. When testing a single class or cluster, only run tests for that class/directory.

## Architecture

### Core Abstractions

- **`TComponent`** (`framework/TComponent.php`) — base class for nearly everything. Implements the property system (getters/setters via magic `__get`/`__set`), event system (`attachEventHandler`/`raiseEvent`), priority based behavior attachment, `__clone`, `__sleep`/`__wakeup`, and `_getZappableSleepProps`.
- **`TApplication`** (`framework/TApplication.php`) — top-level service container. Modules and services register with it via XML (or PHP) configuration; tests bootstrap one via `tests/test_tools/phpunit_bootstrap.php`.
- **`TModule` / `TService`** — pluggable units registered with `TApplication`.

### Layer Breakdown

```
TApplication
 └─ Web Layer      framework/Web/
      THttpRequest, THttpResponse, THttpSession
      TUrlManager / TUrlMapping (routing)
      TAssetManager
      Services/      (TPageService, TJsonService, TFeedService, TRpcService, TSoapService)
      UI/            (TControl, TPage, TTemplateControl, WebControls/, ActiveControls/, JuiControls/)
 └─ Data Layer     framework/Data/
      TDbConnection  (PDO wrapper)
      ActiveRecord/  (ORM)
      SqlMap/        (SQL mapping, iBatis-style)
      DataGateway/
 └─ Caching        framework/Caching/    (APC, Database, Etcd, Memcached, Redis, file-based)
 └─ Security       framework/Security/   (RBAC, TAuthManager, TSecurityManager, TPermissionsManager)
 └─ I18N           framework/I18N/       (TGlobalization, MessageSource, CultureInfo)
 └─ Xml            framework/Xml/        (TXmlDocument/TXmlElement with XPath, DOM compat, ArrayAccess)
 └─ Util           framework/Util/       (logging, behaviors, cron, helpers, TCallChain, TVarDumper)
 └─ PHPStan        framework/PHPStan/    (custom static-analysis extensions for dynamic Prado properties)
```

### Application Lifecycle

`onInitComplete` → `onBeginRequest` → `onLoadState` → `onLoadStateComplete` → `onAuthentication` → `onAuthenticationComplete` → `onAuthorization` → `onAuthorizationComplete` → `onPreRunService` → `runService` → `onSaveState` → `onSaveStateComplete` → `onPreFlushOutput` → `flushOutput` → `onEndRequest` / `onError`

### Page Lifecycle (via TPageService)

`onPreInit` → `initRecursive` → `onInitComplete` → `loadPageState` *(POST/Callback)* → `processPostData` *(POST/Callback)* → `onPreLoad` → `loadRecursive` → `processPostData` *(POST/Callback)* → `raiseChangedEvents` *(POST/Callback)* → `raisePostBackEvent` *(POST-only)* → `processCallbackEvent` *(Callback-only)* → `onLoadComplete` → `preRenderRecursive` → `onPreRenderComplete` → `savePageState` → `onSaveStateComplete` → `renderControl` *(GET/POST)* / `renderCallbackResponse` *(Callback-only)* → `unloadRecursive`

### Event / Property / Behavior System

- Properties: defined via `getXxx()`/`setXxx()` — no public fields.
- Events: 
  - **`on` prefix** — `onEventName(TEventParameter $param)` raised with `$this->raiseEvent('OnEventName', $this, $param)`.
  - **`dy` prefix** — dynamic events called on attached/active Behaviors (e.g., `dyShouldContinue`, `dyClone`, `dyValidate`).
  - **`fx` prefix** — global events, auto-registered depending on `getAutoGlobalListen()` (e.g., `fxAttachClassBehavior`).
- All events are raised in specified priority order.
- `@method` PHPDoc tags document dynamic `dy-` events on classes.


### Custom PHPStan Extensions

`framework/PHPStan/DynamicMethodsClassReflectionExtension` and `TComponentIsaTypeSpecifyingExtension` teach PHPStan about the dynamic property/event/behavior system. Update these when adding new dynamic accessors.

## Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| Classes | `TPascalCase` | `TComponent`, `TApplication` |
| Methods | `camelCase` | `getComponent` |
| Variables | `camelCase` | `$componentName` |
| Constants | `SCREAMING_SNAKE_CASE` | `MAX_RETRY_COUNT` |
| Namespaces | `Prado\{Module}` | `Prado\Web\UI\TControl` |
| Template files | `.tpl` | `MyPortlet.tpl` |
| Web Page templates | `.page` | `Home.page` |

## Important Rules

- **`framework/classes.php`** — must be updated whenever a new class is added to the framework.
- **`if` statements** always use a block (`{}`), never a single-line body.
- **Error codes** for PRADO exceptions are defined in `framework/Exceptions/messages/messages.txt` (English master); language variants are `messages-<lang>.txt` in the same directory.
- **Backward compatibility** — all changes must be backward compatible.
- **`@since` tag** — use the next release version (`4.3.3`) when adding new methods or classes.

## Test Bootstrap

Tests require a running `TApplication`. The bootstrap (`tests/test_tools/phpunit_bootstrap.php`) instantiates one from `tests/test_tools/Security/app/`. Database tests need MySQL/PostgreSQL initialized from `tests/initdb_mysql.sql` / `tests/initdb_pgsql.sql`.

## Code Style

- Indentation: **tabs** (not spaces)
- Line endings: Unix (`\n`)
- PHP minimum: 8.1 (CI tests 8.1, 8.2, 8.3)
- PSR-12 enforced via php-cs-fixer

## Working Knowledge (`agents/`)

Working Knowledge (`agents/`)

`framework/` maps to `agents/framework/` for documentation and knowledge files. The typical `CLAUDE.md` file is named "LIBRARY.md". The directory hierarchy uses UPPER CASE with '-' between directories and the file, ending in "LIBRARY.md". Scan with `find agents/ -type f -name '*.md'` for relevant context. There may be other useful ".md" files.

## Anti-Patterns (Required Safeguards)

- **Never** run `git clone/checkout/mv/restore/rm/branch/add/commit/merge/rebase/reset/pull/push/fetch` without developer approval first.
- **Never** run `rm` on any path without developer approval first.
- **Never** remove `composer --dev` dependencies.
- **Never** erase or overwrite files during unit testing — the file changes being tested must be preserved.
