# Data/ActiveRecord/TActiveRecordConfig

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [ActiveRecord](./INDEX.md) / **`TActiveRecordConfig`**

## Class Info
**Location:** `framework/Data/ActiveRecord/TActiveRecordConfig.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview
`TActiveRecordConfig` is a module configuration class for setting up the ActiveRecord manager in application.xml.

## Configuration

```xml
<modules>
    <module class="Prado\Data\ActiveRecord\TActiveRecordConfig" EnableCache="true">
        <database ConnectionString="mysql:host=localhost;dbname=test"
            Username="dbuser" Password="dbpass" />
    </module>
</modules>
```

## Properties

- `ConnectionID` - Database connection module ID
- `EnableCache` - Enable metadata caching

## See Also

- [TActiveRecordManager](./TActiveRecordManager.md) - Manager class