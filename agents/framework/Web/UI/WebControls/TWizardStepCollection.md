# Web/UI/WebControls/TWizardStepCollection

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWizardStepCollection`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWizardStepCollection.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TWizardStepCollection represents the collection of wizard steps owned by a TWizard. It extends TList and ensures only TWizardStep instances can be added. Adding/removing items automatically syncs with the underlying MultiView.

## Key Properties/Methods

- `insertAt($index, $item)` - Inserts a TWizardStep and syncs with MultiView
- `removeAt($index)` - Removes step and syncs with MultiView

## See Also

- [TWizard](./TWizard.md)
- [TWizardStep](./TWizardStep.md)
