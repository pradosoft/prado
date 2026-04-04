# PRADO Framework Agent Guidelines -- CC0

## Build, Lint, and Test Commands

### Running Tests
- **All Unit Tests**: `vendor/bin/phpunit --testsuite unit` - runs all unit tests
- **Functional Tests**: `composer functionaltest` - runs all functional tests
- **Test Filter**: `vendor/bin/phpunit --testsuite unit --filter <test function, class, or directory>`

### Linting and Code Analysis
- **PHPStan Analysis**: `vendor/bin/phpstan analyse framework/ --memory-limit=512M`
- **PHP CS Fixer (Dry-run)**: `vendor/bin/php-cs-fixer fix --dry-run framework/` (check)
- **PHP CS Fixer (Fix)**: `vendor/bin/php-cs-fixer fix framework/` (apply fixes)

### Build Commands
- **Generate Documentation**: `composer gendoc` - generates API documentation
- **Install Dependencies**: `composer install` - installs all dependencies
- **Updating Dependencies**: `composer update` - updates all dependencies

## Code Style Guidelines
- "if" has a statement block after
- Use php-cs-fixer to correct code styles

### PHP Coding Standards
- Follow PSR-4 autoloading standard
- All PHP files must begin with `<?php` tag (short open tags not allowed)
- Use 1 tab for indentations (no spaces)
- All class names must be in PascalCase
- All method names must be in camelCase
- All variable names must be in camelCase
- Constants must be in SCREAMING_SNAKE_CASE
- Use explicit return types for methods when possible
- All class properties must be declared with visibility modifiers (public, protected, private)

### Naming Conventions
- Class names: `TPascalCase` (e.g., `TComponent`)
- Class name prefix: `T*` (e.g. `TApplication`)
- Method names: `camelCase` (e.g., `getComponent`)
- Variables: `camelCase` (e.g., `$componentName`)
- Constants: `SCREAMING_SNAKE_CASE` (e.g., `MAX_RETRY_COUNT`)
- Namespace: `Prado\{Module}` (e.g., `Prado\Web\UI\TControl`)
- Template file extension: ".tpl"
- Web Page template file extension: ".page"

### Documentation Standards
- All public methods must have PHPDoc comments with:
  - `@param` for parameters
  - `@return` for return values  
  - `@throws` for exceptions
- Classes must have a clear and comprehensive docblock at the top with class description with:
  - Examples, where necessary
  - `@author` for attribution
  - `@since` for version
  - `@method` for dynamic events with prefix 'dy-'; which are called (on "$this->dy-") but not defined.
- Inline comments should be in English and start with `//`
- Use the next release version when adding new files, methods, or classes with "@since" in their docblock

### Error Handling
- Use try/catch blocks for operations that can fail
- Throw appropriate PRADO exceptions (`TInvalidDataValueException`, `TInvalidOperationException`, etc.)
- Return false or null for methods that are designed to fail gracefully
- All methods should handle edge cases and validate input parameters
- PRADO Exceptions use errorCodes specified in framework/Exceptions/messages/messages.txt; the master error Code file in English.  messages.txt is purely for user information display only.
- framework/Exceptions/messages/messages.txt has language specific versions at framework/Exceptions/messages/messages-<language code>.txt

### Imports and Includes
- Use PSR-4 autoloading - no manual includes required
- All framework classes are accessed via namespace prefixes
- Third-party libraries are loaded via Composer
- Use proper `use` statements for namespaces at the top of PHP files

### Framework Specific Guidelines
- You are a professional Software Engineer/Architect working on a PHP web framework library for developers.
- All components inherit from `TComponent` base class
- `TComponent` has features for dynamic event and extension by attached Behaviors (__call, __callStatic), dynamic properties (__get, __set, __isset, __unset), __clone, __sleep, __wakeup, and _getZappableSleepProps
- Behaviors can be attached to any `TComponent` to alter its behavior and functionality.
- Use the event-driven programming model with events; like `onLoad`, `onInit`, `onPreRender`
- Methods with prefix 'dy' are dynamic events to call attached and active Behaviors; like 'dyShouldContinue', 'dyClone', and 'dyValidate'
- Called Dynamic Events must be documented in the class phpdoc with "@method"
- Dynamic event are implemented by attached behaviors not in the calling class
- The first parameter of a dynamic event is always filtered and returned.
- Optional class methods can directly be called on non-behavior classes as "dynamic events"
- Methods with prefix 'fx' are global events that may or may not be automatically registered depending on getAutoGlobalListen(); like 'fxAttachClassBehavior'
- getAutoGlobalListen() is optimized by class hierarchy for utility and speed
- Follow the TApplication Lifecycle: onInitComplete (at end of TApplication::initApplication) → onBeginRequest → onLoadState → onLoadStateComplete → onAuthentication → onAuthenticationComplete → onAuthorization → onAuthorizationComplete → onPreRunService → runService → onSaveState → onSaveStateComplete → onPreFlushOutput → flushOutput → onEndRequest or onError (both at end of TApplication::run)
- Follow the TPage Lifecycle (via TPageService::runPage): onPreInit → initRecursive → onInitComplete → loadPageState (POST/Callback) → processPostData (POST/Callback) → onPreLoad → loadRecursive → processPostData (POST/Callback) → raiseChangedEvents (POST/Callback) → raisePostBackEvent (POST-only) → processCallbackEvent (Callback-only) → onLoadComplete → preRenderRecursive  onPreRenderComplete → savePageState → onSaveStateComplete → renderControl (GET/POST) → renderCallbackResponse (Callback-only) → unloadRecursive
- XML and PHP is supported for application configuration 
- TPageService::onPreRunPage gives PRADO Modules event access to the TPage Lifecycle before it runs
- 'framework/classes.php' MUST be updated with all new classes.
- Web Pages are PHP classes with a ".page" TTemplate file with the same base name
- UI Portlets are PHP classes with a ".tpl" TTemplate file with the same base name
- Data components should support `TActiveRecord` pattern
- All UI controls should have proper template support and state management
- All changes must be backward compatible
- A full check consists of the 4 checks (in order): php compile, php-cs-fixer, phpstan, composer unittest (all checks must pass successfully)
- A full check must be done for code to be ready for git commit.
- The current version is 4.3.2. The next release version is 4.3.3

### ActiveControls JavaScript

Client-side JavaScript for ActiveControls and related controls lives at `framework/Web/Javascripts/source/prado/`. PHP controls register their JS via `registerClientScriptFile()` or the adapter pattern. JavaScript class names mirror PHP class names under the `Prado.WebUI.*` namespace.

**Javascript Directory structure and file-to-control mapping:**

| JS File | PHP Control(s) | Purpose |
|---|---|---|
| `prado/prado.js` | (all controls) | Core namespace, jQuery extensions, `Prado.Registry`, `Prado.PostBack`, `Prado.RequestManager` |
| `prado/logger/logger.js` | (global utility) | Client-side debug console, `Logger`, `LogConsole`, `Prado.Inspector` |
| `prado/controls/controls.js` | TButton, TLinkButton, TImageButton, TTextBox | Base control classes: `Prado.WebUI.Control`, `Prado.WebUI.PostBackControl`, `Prado.WebUI.TTextBox` |
| `prado/controls/tabpanel.js` | TTabPanel | Tab switching, view show/hide, active CSS class management |
| `prado/controls/accordion.js` | TAccordion | Animated expand/collapse sections |
| `prado/controls/slider.js` | TSlider | Drag-and-drop range slider (custom, not jQuery-UI) |
| `prado/controls/keyboard.js` | TKeyboard | Virtual on-screen keyboard overlay |
| `prado/controls/htmlarea.js` | THtmlArea | TinyMCE v3/v4 wrapper with AJAX lifecycle hooks |
| `prado/controls/htmlarea5.js` | THtmlArea5 | TinyMCE v5+ wrapper (async init) |
| `prado/datepicker/datepicker.js` | TDatePicker | Popup calendar, format/parse, dropdown/textbox input modes |
| `prado/colorpicker/colorpicker.js` | TColorPicker | HSB color picker: `Rico.Color`, `Rico.ColorPicker` |
| `prado/ratings/ratings.js` | TRatingList, TActiveRatingList | Star/block rating widget with hover and half-star support |
| `prado/validator/validation3.js` | All TBaseValidator subclasses, TValidationSummary | Client-side validation: `Prado.Validation`, `Prado.ValidationManager`, validator classes |
| `prado/activecontrols/ajax3.js` | (all active controls) | AJAX callback engine: `Prado.CallbackRequestManager`, `Prado.CallbackRequest`, `Prado.ScriptManager`. Defines X-PRADO-* response headers |
| `prado/activecontrols/activecontrols3.js` | TActiveButton, TActiveLinkButton, TActiveImageButton, TActiveCheckBox, TActiveRadioButton, TActiveCheckBoxList, TActiveRadioButtonList, TActiveTextBox, TActiveDropDownList, TActiveListBox, TJuiAutoComplete, TTimeTriggeredCallback | All standard active control wrappers |
| `prado/activecontrols/inlineeditor.js` | TInPlaceTextBox | Click-to-edit label→textbox, optional server text reload |
| `prado/activecontrols/activedatepicker.js` | TActiveDatePicker | Extends `TDatePicker` with callback on date change |
| `prado/activefileupload/activefileupload.js` | TActiveFileUpload | Async file upload via hidden iframe, progress indicator |

**Javascript Dependency chain:**
```
prado.js
 ├── logger/logger.js
 ├── controls/controls.js
 │    ├── controls/tabpanel.js
 │    ├── controls/accordion.js
 │    ├── controls/slider.js
 │    ├── controls/keyboard.js
 │    ├── controls/htmlarea.js
 │    └── controls/htmlarea5.js
 ├── datepicker/datepicker.js
 │    └── activecontrols/activedatepicker.js
 ├── colorpicker/colorpicker.js
 ├── ratings/ratings.js
 ├── validator/validation3.js
 └── activecontrols/ajax3.js
      ├── activecontrols/activecontrols3.js
      ├── activecontrols/inlineeditor.js
      └── activefileupload/activefileupload.js
```

**Key AJAX callback headers** (set by PHP, read by `ajax3.js`):
- `X-PRADO-REDIRECT` — redirect client to URL
- `X-PRADO-DATA` — arbitrary return data
- `X-PRADO-ACTIONS` — JSON array of client-side DOM update commands
- `X-PRADO-ERROR` — error message
- `X-PRADO-PAGESTATE` — updated page state token
- `X-PRADO-SCRIPTLIST` / `X-PRADO-STYLESHEET` — dynamic asset loading

**Class definition pattern** (all JS controls):
```javascript
Prado.WebUI.TActiveButton = jQuery.klass(Prado.WebUI.CallbackControl, { /* overrides */ });
```
All instances self-register in `Prado.Registry[controlId]` on construction and are cleaned up via `deinitialize()`.

## Testing Guidelines
- The testing platform is "phpunit"
- All new code must include unit tests
- Unit test functions must comprehensively assert both typical and edge cases
- Maximal coverage of code execution paths of a class is required
- Test error conditions and exception handling
- Use mock objects where appropriate
- Functional tests should verify complete user workflows
- Tests should be isolated from each other (no shared state)
- When unit testing one or cluster of classes, only run the unit tests for that class or cluster/directory.
- NEVER add/change phpunit command options when unit testing; only run project unit tests as specified

## Development Environment
- PHP 8.1 or higher required
- PHP extensions: ctype, dom, intl, json, pcre, spl (required)
- Optional extensions for additional features: apcu, mbstring, openssl, pdo, soap, xsl, zlib
- Composer for dependency management
- Required developer dependencies for code checking: phpunit/phpunit, phpstan/phpstan, friendsofphp/php-cs-fixer
- Presume that project dependencies are installed

## Directory Structure
```
./
├── agents/                     # The Coding Agents directory
│   ├── PRADO_ANALYSIS.md       # An efficient understanding of PRADO
│   └── working/                # Working Memory of the PRADO framework
│       └── classes/            # Working Memory Documentation of PRADO framework classes
│                               #    and follows the relative directory hierarchy from 'framework/'
├── framework/                  # The Source Code directory
│   ├── Caching/                # APC, Database, Etcd, Mem, Redis
│   ├── Collections/            # TList, TMap, TNull, TPriorityList, TPriorityMap, TQueue, TStack, TWeakList, TArraySubscription
│   ├── Data/                   # Database classes: TDbCommand, TDbConnection, TDbDataReader, TDbTransaction, TDataSourceConfig
│   │   └── ActiveRecord/       # The Active Record classes
│   ├── Exceptions/             # T*Exceptions directory
│   ├── I18N/                   # Internationalization: TTranslate, TDateFormat
│   │   ├── TGlobalization      # settings for culture, charset and translation configuration
│   │   ├── core                # CultureInfo (ICU), MessageSource
│   │   └── schema              # MessageSource data schema
│   ├── IO/                     # Input Output classes directories
│   ├── PHPStan/                # PHPStan Extensions for dynamic events and TComponent::isa()
│   ├── Prado.php               # Framework static class
│   ├── Security/               # TAuthManager, TSecurityManager, TUser, TUserManager, TDbUser, TDbUserManager
│   │   └── Permissions         # TPermissionsManager
│   ├── Shell/                  # TShellAction, TShellApplication, TShellWriter
│   │   └── Actions/            # Framework Shell Actions: TWebServerAction, THelpAction, TFlushCachesAction, TDbParameterAction, TActiveRecordAction
│   ├── classes.php             # List of All PRADO classes and namespaces; format: PHP Array
│   ├── TComponent.php          # Base class
│   ├── TApplicationComponent.php      # base class for application components
│   ├── TApplication.php        # Main Application class
│   ├── TApplicationConfiguration.php  # Configuration of Application class
│   ├── TEventHandler.php       # Invokable to run "on" event handlers with associated hierarchical data
│   ├── TEventSubscription.php  # Temporarily subscribes an handler to an "on" event
│   ├── TModule.php             # Base class for PRADO modules and managers
│   ├── TService.php            # Base class for application services
│   ├── Util/                   # Page templates directory
│   │   ├── Behaviors/          # Installable System Behaviors
│   │   ├── Cron/               # Manages time based processes: TCronModule, TCronTask, TDbCronModule, TTimeScheduler
│   │   ├── Helpers/            # TBitHelper, TProcessHelper
│   │   ├── Math/               # Rational Numbers
│   │   ├── TBaseBehavior.php   # Base Behavior class
│   │   ├── TBehavior.php       # Base class for Regular Behaviors
│   │   ├── TBehaviorsModule.php      # Module for loading Behaviors from Configuration
│   │   ├── TCallChain.php      # Used by dynamic events to chain Behaviors' methods
│   │   ├── TClassBehavior.php  # Base class for Class Behaviors
│   │   ├── TLogger.php         # Logs messages as the app runs
│   │   ├── TLogRouter.php      # Module for capturing logs
│   │   ├── TParameterModule.php      # Module for loading parameters
│   │   ├── TDbParameterModule.php    # Module for loading parameters
│   │   ├── TPluginModule.php   # Base class for PRADO 4 Extensions (via composer)
│   │   ├── TDbPluginModule.php # Base class for PRADO 4 Extensions with a database connection
│   │   ├── TSignalsDispatcher.php    # Routes linux process signals
│   │   ├── TUtf8Converted.php  # UTF8 converter
│   │   └── TVarDumper.php      # outputs a PRADO object dump
│   ├── Web/                    # Page templates directory
│   │   ├── Behaviors/          # Behaviors changing Web/* objects
│   │   ├── Javascript/         # Javascript classes: TJavaScript, TJavaScriptAsset, TJavaScriptLiteral, TJavaScriptString, "source/prado" (browser javascript for WebControls)
│   │   ├── Services/           # TPageService, TJsonService, TFeedService, TRpcService, TSoapService
│   │   ├── TAssetManager.php   # Manages Page Response Assets
│   │   ├── THttpRequest.php
│   │   ├── THttpResponse.php
│   │   ├── THttpSession.php
│   │   ├── TUrlManager.php     # Manages the mapping of URLS
│   │   ├── TUrlMapping.php     # A specific url mapping 
│   │   └── UI/                 # Base Page UI directory
│   │       ├── ActiveControls/    # JUI Controls
│   │       ├── JuiControls/    # JUI Controls
│   │       ├── TTemplateManager.php  # Manages the Templates
│   │       ├── TTemplate.php         # Content Template
│   │       ├── TSkinTemplate.php     # TTemplate but no validation; for TTheme
│   │       ├── TThemeManager.php     # Manages the selected TPage Themes
│   │       ├── TTheme.php      # A theme that applies to TPage
│   │       ├── TControl.php    # Base class for controls
│   │       ├── TTemplateControl.php
│   │       ├── TCompositeControl.php
│   │       ├── TPage.php       # Base class for a web page
│   │       ├── TForm.php       # mimicks <form>
│   │       ├── TWebColors      # Standardized web colors 
│   │       ├── TControlAdapter.php    # Automatically adapts a control to be Active
│   │       └── WebControls/    # Base Page UI directory: TButton, TCheckbox, TFileUpload, TPanel, TImage, TLabel, Lists, TMarkdown, TMultiView, Repeaters, TRadioButton, ReCaptcha, TSafeHtml, TScrollBars, TTable, TGravatar, TTextBox, Validation, TView, Wizard, TXmlTransform, and much more
│   │           ├── TContent.php               # Injected into TContentPlaceHolder
│   │           ├── TContentPlaceHolder.php    # A place holder for TContent; 
│   │           ├── TWebControl.php            # Base class for WebControls
│   │           └── TWebControlDecorator.php   # Adds html around WebControls
│   └── Xml/                    # TXmlDocument, TXmlElement
├── bin/                        # The Command Line Executable `prado-cli`
├── tests/                      # Test files
│   ├── FunctionalTests/        # Functional tests for Active Controls
│   ├── initdb_*.sql            # Database initialization files
│   ├── test_tools/             # phpunit, selenium 2 and Test Listener Bootstraps
│   └── unit/                   # phpunit tests for './framework/' classes
├── CLAUDE.md                   # The Memory file for the directory
├── composer.json               # Package configuration
├── HISTORY.md                  # Version History of important changes
├── README.md                   # Documentation
└── vendor/                     # the container for composer dependencies
```
- This is an abbreviated Directory Structure
- All Directories have more files in them than listed

## Cursor/Copilot Instructions
No specific Cursor or Copilot rules currently defined for this project.

# PRADO Framework Agent Safeguards -- ANTI-PATTERNS
Between the next brackets, it is required without exception:
{
- NEVER (without exception) execute the following "git" commands without asking the developer for approval first: clone, checkout, mv, restore, rm, branch, add, commit, merge, rebase, reset, pull, push, fetch
- NEVER (without exception) execute "rm" commands on any paths without asking the developer for approval first
- NEVER remove composer --dev dependencies because those are a required for development on the Project
- NEVER perform an action that erases or overwrites files for the task of unit testing and fixing; file changes are important and must be kept, because the changes themselves are being unit tested.
}

# Search URL References
- To Search the PHP language and libraries, use url: "https://www.php.net/search.php#gsc.tab=0&gsc.sort=&gsc.q=<replace with query string>"
- To look up inherent PHP functions, use url: "https://www.php.net/manual/en/function.<replace with PHP function>.php"

# Directory Working Knowledge
- Every directory in the project might contain a CLAUDE.md file about the directory
- Changes to files and sub-directories in the directory should update the CLAUDE.md
- Sub-Directories should be briefly summarized from their containing CLAUDE.md
- Class Specific Summaries are at "agents/working/classes/" with the same directory hierarchy as "framework/"
- A 'CLAUDE.md' file is the extensive summary of its:
  - containing directory
  - the classes within the directory and their purpose
  - sub directories and their purpose/content
