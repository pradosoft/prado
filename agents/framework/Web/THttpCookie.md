# THttpCookie Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpCookie](./THttpCookie.md)

## Overview
THttpCookie class stores a single HTTP cookie, including properties such as name, value, domain, path, expire, and secure.

## Key Features
- Implements standard HTTP cookie properties
- Supports SameSite cookie policy (None, Lax, Strict)
- Implements HttpOnly flag to prevent JavaScript access
- Provides PHP-compatible options for setcookie() and session_set_cookie_params()
- Inherits from TComponent for event and behavior support

## Properties
- `Name` (string): The name of the cookie
- `Value` (string): The value of the cookie
- `Domain` (string): The domain to associate the cookie with (default: empty string)
- `Path` (string): The path on the server where the cookie will be available (default: '/')
- `Expire` (int): The time the cookie expires as Unix timestamp (default: 0 - session cookie)
- `Secure` (bool): Whether the cookie should only be transmitted over HTTPS (default: false)
- `HttpOnly` (bool): Whether the cookie value will be unavailable to JavaScript (default: false)
- `SameSite` ([THttpCookieSameSite](./THttpCookieSameSite.md)): SameSite policy for cross-site requests (default: Lax)

## Constructor
```php
public function __construct($name, $value)
```
Initializes a new THttpCookie with name and value.

## Methods
### Property Getters/Setters
- `getName()`, `setName($value)`: Get/set cookie name
- `getValue()`, `setValue($value)`: Get/set cookie value
- `getDomain()`, `setDomain($value)`: Get/set cookie domain
- `getPath()`, `setPath($value)`: Get/set cookie path
- `getExpire()`, `setExpire($value)`: Get/set cookie expiration time
- `getSecure()`, `setSecure($value)`: Get/set secure flag
- `getHttpOnly()`, `setHttpOnly($value)`: Get/set HttpOnly flag
- `getSameSite()`, `setSameSite($value)`: Get/set SameSite policy

### Utility Methods
- `getPhpOptions($expiresKey = 'expires')`: Returns cookie options as used in PHP's setcookie() and session_set_cookie_params()

## Implementation Details
- Inherits from TComponent to support event handling and behaviors
- Uses TPropertyValue for type conversion and validation
- Supports SameSite policies introduced in modern browsers (PHP 7.3+)
- The `getPhpOptions()` method provides compatibility with PHP's native cookie functions

## Usage Example
```php
$cookie = new THttpCookie('username', 'john_doe');
$cookie->setDomain('.example.com');
$cookie->setPath('/');
$cookie->setExpire(time() + 3600); // 1 hour from now
$cookie->setSecure(true);
$cookie->setHttpOnly(true);
$cookie->setSameSite([THttpCookieSameSite](./THttpCookieSameSite.md)::Strict);
```