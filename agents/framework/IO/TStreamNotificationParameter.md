# IO/TStreamNotificationParameter

### Directories
[framework](../INDEX.md) / [IO](./INDEX.md) / **`TStreamNotificationParameter`**

## Class Info
**Location:** `framework/IO/TStreamNotificationParameter.php`
**Namespace:** `Prado\IO`

## Overview
Parameter class for stream notification callbacks. Wraps PHP stream notification constants into a structured object for PRADO events.

## Usage

Used with `stream_notification_callback`:
```php
$context = stream_context_create([
    'notification' => function($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
        $param = new TStreamNotificationParameter(
            $notification_code, $severity, $message, 
            $message_code, $bytes_transferred, $bytes_max
        );
        // Handle notification
    }
]);
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `NotificationCode` | int | `STREAM_NOTIFY_*` constant |
| `Severity` | int | `STREAM_NOTIFY_SEVERITY_*` constant |
| `Message` | ?string | Descriptive message |
| `MessageCode` | int | Message code |
| `BytesTransferred` | int | Bytes transferred (if applicable) |
| `BytesMax` | int | Total bytes expected (if applicable) |

## See Also

PHP Manual: `stream_notification_callback`
