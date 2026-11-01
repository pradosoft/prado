# TApplicationComponent

### Directories
[framework](./INDEX.md) / **`TApplicationComponent`**

## Class Info
**Location:** `framework/TApplicationComponent.php`
**Namespace:** `Prado`

## Overview
TApplicationComponent is the base class for all application-related components in PRADO, including controls, modules, services, and other framework elements. It provides shortcuts to commonly used application methods and services.

## Key Features
- **Application Integration**: Provides shortcuts to application, service, request, response, session, and user
- **Asset Publishing**: Supports publishing of private assets and files
- **Global Event Listening**: Automatically listens to global events by default
- **Caching Support**: Implements caching for global event handling
- **Framework Component Base**: Foundation for all PRADO components

## Core Properties and Methods

### Application Integration
- `getApplication()`: Returns the current application instance
- `getService()`: Returns the current running service
- `getRequest()`: Returns the current user request module
- `getResponse()`: Returns the response module
- `getSession()`: Returns the user session module
- `getUser()`: Returns the current user instance

### Asset Management
- `publishAsset()`: Publishes a private asset and returns its URL
- `publishFilePath()`: Publishes a file or directory and returns its URL

### Global Event Support
- `getAutoGlobalListen()`: Returns whether the component should automatically listen to global events (always returns true)
- `getClassFxEvents()`: Returns a class's 'fx' events; Performance mode keeps them in the `fxevent.cache` runtime file
- `readFxCache($cacheFile)`: Protected static. Reads the cache file without objects; a missing, corrupt, or pre-Composer-change file gives `[]` (4.4.0)

## Framework Integration
- Works as base class for TModule, TService, and UI controls
- Integrates with PRADO's application lifecycle and component architecture
- Supports TComponent's behavior and event systems
- Connects to TApplication's module, service, and state management

## Caching Features
| Mode | fx-event lists |
|------|----------------|
| Debug, Normal, Off | Computed per process from the loaded classes |
| Performance | Read once per process from `runtime/fxevent.cache` and written as classes are added |

- The Performance file is discarded when it is older than `TComposerReflection::getInstalledManifestsTime()`, so a package install, update, or removal refreshes it.
- A deployment that changes an application class's fx handlers runs `prado-cli cache/flush-all`, which deletes the file.
- The runtime directory carries the Prado version, so a Prado upgrade starts a new file.

## Standards Followed
- PSR-4 autoloading compatibility
- Proper PHPDoc comments with @param, @return, @throws
- Consistent naming conventions (PascalCase for classes, camelCase for methods)
- Extends TComponent for full framework integration

## Usage Examples
```
// Accessing application components
$application = $this->getApplication();
$service = $this->getService();
$request = $this->getRequest();
$response = $this->getResponse();

// Publishing assets
$assetUrl = $this->publishAsset('images/logo.png');
$assetUrl = $this->publishFilePath('/path/to/assets');

// Inheritance in custom classes
class MyControl extends TApplicationComponent
{
    public function myMethod()
    {
        $request = $this->getRequest(); // Access to request
        $user = $this->getUser();       // Access to user
        $app = $this->getApplication(); // Access to application
    }
}
```

## Inheritance
TApplicationComponent extends TComponent, providing application-level functionality to all PRADO components while maintaining full compatibility with the component architecture and behavior system.