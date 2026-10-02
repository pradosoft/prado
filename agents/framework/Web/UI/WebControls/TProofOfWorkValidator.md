# Web/UI/WebControls/TProofOfWorkValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TProofOfWorkValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TProofOfWorkValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TProofOfWorkValidator validates the [TProofOfWork](./TProofOfWork.md) named by `ControlToValidate` by calling its `validate()`. It throws `proofofworkvalidator_control_invalid` when the target is another control. Client script is off: the client control holds the submission until the solution is ready, so a client check has nothing to add.

## See Also

- [TProofOfWork](./TProofOfWork.md)
- [TBaseValidator](./TBaseValidator.md)
