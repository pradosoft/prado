# Web/UI/WebControls/TLoginName

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TLoginName`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TLoginName.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TWebControl](./TWebControl.md)

## Overview
TLoginName displays the name of the current user, `TApplication::getUser()`, in a `<span>`. [TAuthManager](../../../Security/TAuthManager.md) sets the user during authentication. It is the first of the authentication view controls of issue #1404.

| User | Renders |
|---|---|
| authenticated | the name, formatted by `FormatString` |
| guest, `DisplayGuest` false (default) | nothing |
| guest, `DisplayGuest` true | the guest name (`TUserManager.GuestName`), formatted |
| none, or an empty name | nothing |

## Properties

| Property | Default | Description |
|---|---|---|
| `FormatString` | `''` | HTML from the template around the name; `{0}` (`NAME_PLACEHOLDER`) is the encoded name. Empty renders the name alone. |
| `NameProperty` | `Name` | User property path read by `TComponent::getSubProperty()`, such as `DisplayName`. A user that is not a `TComponent` always supplies `IUser::getName()`. An undefined property throws `TInvalidOperationException`. |
| `DisplayGuest` | `false` | Whether a guest's name renders. |

`getUserName()` returns the name the control displays, or `''` when it renders nothing.

## Template Usage

```xml
<com:TLoginName />
<com:TLoginName FormatString="Signed in as <strong>{0}</strong>" />
<com:TLoginName NameProperty="DisplayName" DisplayGuest="true" />
```

## Security

The name is plain text from the user store and is encoded by `htmlspecialchars()` with `ENT_QUOTES`, `&` included. `THttpUtility::htmlEncode()` leaves `&` unencoded, so it is not used. `FormatString` is not encoded.

## Accessibility

Audited in Chromium, Firefox, and WebKit.

- The `<span>` has no role (`generic` in Chromium) and no accessible name of its own; screen readers read the name as text in its context.
- No live region: the name changes only with a page load or a re-render.
- A guest sees no element, so a label such as "User:" around the control is text without a value. Put such text inside `FormatString`.

## Tests

- `tests/unit/Web/UI/WebControls/TLoginNameTest.php`
- `tests/playwright/security/TLoginNameTestCase.spec.js` with `tests/harness/security/protected/pages/LoginNameTest.page`: guest and signed-in rendering, attributes, ARIA snapshot, and the Chromium accessibility role

## See Also

- [TAuthManager](../../../Security/TAuthManager.md)
- [IUser](../../../Security/IUser.md)
- [TLabel](./TLabel.md)

**@since 4.4.0**
