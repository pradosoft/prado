# TService Class

## Overview
TService is the base class for all application services in PRADO. It implements the basic methods required by IService and may be used as the foundation for creating custom application services.

## Key Features
- **Service Base Implementation**: Provides essential functionality for application services
- **Initialization Support**: Implements the init() method required by IService interface
- **Service Enable/Disable**: Supports enabling and disabling services
- **Service Identification**: Provides ID-based service management
- **Execution Framework**: Defines the run() method for service execution

## Core Properties
- `ID`: Unique identifier for the service
- `Enabled`: Flag indicating whether the service is enabled
- `_id`: Internal storage for service identifier
- `_enabled`: Internal storage for service enable flag

## Core Methods

### Service Management
- `init()`: Initializes the service (empty implementation by default)
- `run()`: Main execution method for the service (empty implementation by default)
- `getID()`: Returns the service's unique identifier
- `setID()`: Sets the service's unique identifier
- `getEnabled()`: Returns whether the service is enabled
- `setEnabled()`: Sets whether the service is enabled

### Lifecycle Integration
- Works with TApplication's service dispatching system
- Integrates with PRADO's application lifecycle events
- Supports service configuration through XML elements

## Framework Integration
- Works with TApplication's service management system
- Integrates with PRADO's application lifecycle management
- Supports module and component architecture
- Handles request routing and execution

## Standards Followed
- PSR-4 autoloading compatibility
- Proper PHPDoc comments with @param, @return, @throws
- Consistent naming conventions (PascalCase for classes, camelCase for methods)
- Abstract class design for service inheritance

## Usage Examples
```
// Creating a custom service
class MyService extends TService
{
    public function run()
    {
        // Custom service execution code
        echo "My service is running";
    }
}

// Service configuration in application
/*
<services>
    <myService class="MyService" id="myService" enabled="true"/>
</services>
*/
```

## Inheritance
TService extends TApplicationComponent and implements IService interface, providing a solid foundation for all application services in PRADO.

## Service States
- **Enabled**: Service can process requests (default)
- **Disabled**: Service skips processing (useful for temporary disabling)