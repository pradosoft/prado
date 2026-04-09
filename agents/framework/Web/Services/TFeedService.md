# TFeedService Class

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TFeedService](./TFeedService.md)

## Overview
TFeedService provides feed content (RSS/Atom) to end-users. It manages a set of feed providers that implement the IFeedContentProvider interface.

## Key Features
- Manages multiple feed providers
- Configurable through XML or PHP formats
- Supports different feed types through content type configuration
- Implements standard feed retrieval pattern

## Configuration
### XML Format
```xml
<service id="feed" class="Prado\Web\Services\TFeedService">
  <feed id="ch1" class="Path\To\FeedClass1" />
  <feed id="ch2" class="Path\To\FeedClass2" />
  <feed id="ch3" class="Path\To\FeedClass3" />
</service>
```

### PHP Format
```php
array(
  'feed' => array(
    'ch1' => array(
      'class' => 'Path\To\FeedClass1',
      'properties' => array(
        ...
      ),
    ),
  ),
)
```

## Methods

### Service Management
- `init($config)` - Initializes the service with configuration
- `run()` - Runs the service, handling feed requests
- `determineRequestedFeedPath()` - Determines the requested feed path

### Request Handling
- Uses service parameter to identify which feed to provide
- Supports URL format: `/path/to/index.php?feed=ch2`
- Returns feed content with appropriate content type
- Implements IFeedContentProvider interface for feed content generation

## Feed Provider Requirements
- Feed class must implement IFeedContentProvider interface
- Class must implement `getFeedContent()` method
- Class must implement `getContentType()` method
- Feed content is returned directly with proper content type header

## Content Types
- Supports RSS and Atom feed formats
- Uses content type returned by provider class