# Collections/TPriorityPropertyTrait

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPriorityPropertyTrait`**

## Class Info
**Location:** `framework/Collections/TPriorityPropertyTrait.php`
**Namespace:** `Prado\Collections`

## Overview
Trait implementing [IPriorityProperty](./IPriorityProperty.md) for objects that need priority capability. Used by subscription classes to provide priority functionality.

## Usage

```php
class MyItem
{
    use TPriorityPropertyTrait;
    
    // Also needs: implements IPriorityItem
}
```

## Methods

### getPriority

```php
public function getPriority(): ?float
```

Returns the current priority. Default is `null`.

### setPriority

```php
public function setPriority($value): static
```

Sets the priority. Empty string becomes `null`.

## Notes

- Priorities are stored as floats
- Null priority means no explicit priority set
- Part of `IPriorityProperty` interface implementation

## See Also

- [IPriorityProperty](./IPriorityProperty.md) - The interface this trait implements
- [TPriorityCollectionTrait](./TPriorityCollectionTrait.md) - For collections
