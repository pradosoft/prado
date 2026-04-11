# Prado Class

## Overview
Prado implements a few fundamental static methods. It serves as the base class of Prado and handles framework initialization, error handling, and component loading.

## Key Features
- Static methods for framework operations
- Autoloader initialization and registration
- Error and exception handling setup
- Component creation and namespace management
- Application singleton management

## Core Methods

### Initialization
- `init()` - Initializes the static class
- `initAutoloader()` - Loads classmap and registers autoload function
- `initErrorHandlers()` - Sets up error and exception handlers

### Error Handling
- `phpErrorHandler()` - PHP error handler
- `phpFatalErrorHandler()` - PHP shutdown function for fatal errors
- `exceptionHandler()` - Default exception handler

### Component Management
- `createComponent()` - Creates a component with specified type
- `using()` - Uses a namespace (handles class loading)
- `autoload()` - Class autoload loader

### Application Management
- `setApplication()` - Stores the application instance
- `getApplication()` - Returns the application singleton

### Path and Namespace Management
- `getPathOfNamespace()` - Translates namespace to file path
- `getPathOfAlias()` - Returns path corresponding to an alias
- `getPathAliases()` - Returns list of path aliases
- `setPathOfAlias()` - Sets path for an alias

### Logging and Debugging
- `getLogger()` - Returns message logger
- `log()` - Writes a log message
- `trace()` - Writes a trace message
- `debug()` - Writes a debug message
- `info()` - Writes an info message
- `warning()` - Writes a warning message
- `error()` - Writes an error message
- `fatal()` - Writes a fatal message
- `varDump()` - Converts variable to string representation

### Localization
- `getUserLanguages()` - Returns list of user preferred languages
- `getPreferredLanguage()` - Returns most preferred language by client
- `localize()` - Localizes text to locale/culture

## Static Properties
- `$_aliases` - List of path aliases
- `$_usings` - List of used namespaces
- `$_logger` - Message logger instance
- `$_application` - Current application instance