# Web/THttpSession

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpSession`**

## Class Info
**Location:** `framework/Web/THttpSession.php`
**Namespace:** `Prado\Web`

## Overview
THttpSession provides session-level data management and the related configurations for PHP web applications. It implements PHP session handling with additional PRADO-specific features.

## Key Features
- Session-level data storage with array-like interface
- Custom storage handler support for specialized session storage
- Cookie configuration for session ID management
- Configurable session parameters (timeout, GC probability, auto-start)
- Transparent session ID support
- Security features including HttpOnly cookie setting
- Integration with PRADO's application lifecycle

## Core Properties
- `AutoStart` (bool): Whether to automatically start session when module is initialized, defaults to false
- `CookieMode` ([THttpSessionCookieMode](./THttpSessionCookieMode.md)): How to use cookie to store session ID (None, Allow, Only), defaults to Allow
- `SavePath` (string): Session save path, defaults to '/tmp'
- `UseCustomStorage` (bool): Whether to use user-specified handlers, defaults to false
- `GCProbability` (int): Probability (percentage) that garbage collection is started, defaults to 1
- `Timeout` (int): Number of seconds after which data is seen as 'garbage', defaults to 1440
- `UseTransparentSessionID` (bool): Whether to enable transparent session IDs, defaults to false
- `SessionName` (string): Session name for current session, defaults to PHPSESSID
- `SessionID` (string): Current session ID
- `IsStarted` (bool): Whether session has started

## Configuration
### XML Format
```xml
<module id="session" class="THttpSession" 
        SessionName="SSID" 
        SavePath="/tmp"
        CookieMode="Allow" 
        UseCustomStorage="false" 
        AutoStart="true" 
        GCProbability="1"
        UseTransparentSessionID="true" 
        TimeOut="3600" />
```

## Core Methods

### Session Management
- `open()`: Starts the session if not already started
- `close()`: Ends session and stores data
- `destroy()`: Destroys all data registered to session
- `regenerate($deleteOld = false)`: Update session ID with new one
- `getSessionID()`: Gets current session ID
- `setSessionID($value)`: Sets session ID for current session

### Session Configuration
- `getAutoStart()`, `setAutoStart($value)`: Gets/sets auto start flag
- `getCookieMode()`, `setCookieMode($value)`: Gets/sets cookie mode
- `getSavePath()`, `setSavePath($value)`: Gets/sets save path
- `getUseCustomStorage()`, `setUseCustomStorage($value)`: Gets/sets custom storage flag
- `getGCProbability()`, `setGCProbability($value)`: Gets/sets GC probability
- `getTimeout()`, `setTimeout($value)`: Gets/sets timeout value
- `getUseTransparentSessionID()`, `setUseTransparentSessionID($value)`: Gets/sets transparent session ID

### Cookie Handling
- `getCookie()`: Returns [THttpCookie](./THttpCookie.md) used to store session ID
- `setCookie($name, $value)`: Sets cookie properties

### Custom Storage Handlers
When `UseCustomStorage` is true, the following methods can be overridden:
- `_open($savePath, $sessionName)`: Session open handler
- `_close()`: Session close handler  
- `_read($id)`: Session read handler
- `_write($id, $data)`: Session write handler
- `_destroy($id)`: Session destroy handler
- `_gc($maxLifetime)`: Session garbage collection handler

## Array Access Interface
THttpSession implements ArrayAccess, IteratorAggregate, and Countable interfaces:
- `offsetExists($offset)`: Checks if offset exists
- `offsetGet($offset)`: Gets item at offset
- `offsetSet($offset, $item)`: Sets item at offset
- `offsetUnset($offset)`: Removes item at offset
- `getIterator()`: Returns iterator for traversing session variables
- `count()`: Returns number of items
- `getCount()`: Returns number of items
- `itemAt($key)`: Gets item at key
- `add($key, $value)`: Adds item
- `remove($key)`: Removes item
- `contains($key)`: Checks if key exists
- `toArray()`: Returns all items as array
- `clear()`: Clears all items
- `getKeys()`: Returns key list

## Security Considerations
- HttpOnly flag can be set on session cookie for security
- Session IDs are protected from modification after start
- Custom storage enables specialized security considerations
- Configurable session timeouts for automatic cleanup

## Integration with PRADO
- Registered with [TApplication](../TApplication.md) via `getApplication()->setSession()`
- Integrates with [THttpSessionHandler](./THttpSessionHandler.md) for custom storage
- Works with [THttpCookie](./THttpCookie.md) for session cookie management
- Auto-start functionality managed through module initialization

## Usage Example
```php
// Start session
$session = $this->getApplication()->getSession();
$session->open();

// Store data
$session['username'] = 'john_doe';
$session['last_login'] = time();

// Access data
$username = $session['username'];
$lastLogin = $session['last_login'];

// Iterate through session
foreach ($session as $name => $value) {
    echo "$name: $value\n";
}

// Close session
$session->close();
```