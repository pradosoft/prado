# Web/Services/TFeedService

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TFeedService`**

## Class Info
**Location:** `framework/Web/Services/TFeedService.php`
**Namespace:** `Prado\Web\Services`

## Overview
TFeedService provides feed content (RSS/Atom) to end-users. It manages a set of feed providers that implement the IFeedContentProvider interface.

## Key Features
- Manages multiple feed providers
- Configurable through XML or PHP formats
- Supports different feed types through content type configuration
- Implements standard feed retrieval pattern

## Configuration

Registered as a service (`<services>` tag, not `<modules>`).

**application.xml:**
```xml
<services>
  <service id="feed" class="Prado\Web\Services\TFeedService">
    <feed id="ch1" class="Path\To\FeedClass1" />
    <feed id="ch2" class="Path\To\FeedClass2" />
    <feed id="ch3" class="Path\To\FeedClass3" />
  </service>
</services>
```

**PHP equivalent:**
```php
return [
    'services' => [
        'feed' => [
            'class' => 'Prado\Web\Services\TFeedService',
            'feeds' => [
                'ch1' => ['class' => 'Path\To\FeedClass1', 'properties' => [...]],
                'ch2' => ['class' => 'Path\To\FeedClass2'],
                'ch3' => 'Path\To\FeedClass3',
            ],
        ],
    ],
];
```
`init()` registers each entry of `feeds`. A string entry is the feed class. `run()` validates the class: a missing class raises `feedservice_class_required`, and a class that is not an `IFeedContentProvider` raises `feedservice_feedtype_invalid`.

**Deprecated 4.3 form:** feeds beside the `class` key. The private `loadLegacyPhpFeeds()` registers such an entry when its key is not reserved (`class`, `feeds`, `properties`, or a `TApplicationConfiguration` key: `application`, `errormessages`, `includes`, `modules`, `parameters`, `paths`, `services`) and its class implements `IFeedContentProvider`. Other entries are skipped without error. A feed under `feeds` takes precedence. Remove the method and its call in `init()` to drop the 4.3 form.

An XML-configured service with a `null` configuration registers no feed.

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