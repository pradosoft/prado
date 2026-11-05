# Web/UI/WebControls/TAuthView

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAuthView`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAuthView.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TControl](../TControl.md)

## Overview
TAuthView renders content chosen by the authentication state of `TApplication::getUser()`. Each case is a [TAuthViewTemplate](./TAuthViewTemplate.md) with `Users`, `Roles`, and `Permissions` conditions. TAuthView renders no tag of its own. It generalizes [TConditional](./TConditional.md) from a PHP expression to authorization checks and evaluates no template code. It is part of issue #1404.

The body of TAuthView, the content outside its TAuthViewTemplate children, is the self case: `getSelfTemplate()`, a TAuthViewTemplate whose conditions are the `Users`, `Roles`, and `Permissions` of TAuthView.

## Selection

| Order | Case |
|---|---|
| 1 | TAuthViewTemplate children, in document order; the first that matches |
| 2 | the self case, the fallback of the children |
| 3 | none matches, or no user → nothing |

Every case defaults to `Users="@"`: a case without conditions matches any authenticated user. A guest case sets `Users="?"` or `Users="*"`.

## Template Usage

```xml
<!-- single case -->
<com:TAuthView Permissions="blog.post.edit">
	<a href="...">Edit post</a>
</com:TAuthView>

<!-- several cases; the body is the fallback -->
<com:TAuthView>
	<com:TAuthViewTemplate Users="?"><a href="...">Sign in</a></com:TAuthViewTemplate>
	<com:TAuthViewTemplate Roles="admin"><a href="...">Administration</a></com:TAuthViewTemplate>
	Welcome back.
</com:TAuthView>
```

## Key Methods

| Method | Description |
|---|---|
| `addParsedObject($object)` | Keeps a TAuthViewTemplate as a case; adds other content to the self case |
| `getTemplates()` | TAuthViewTemplate children, in document order |
| `getSelfTemplate()` | The self case |
| `getActiveTemplate()` | The case in the control tree, or null |
| `findMatchingTemplate()` | The case for the current user, or null |
| `createChildControls()` | Adds the matching case (Init stage) |
| `onPreRender($param)` | Chooses again, so a login or logout during a postback renders the new case |
| `applyTemplate(?TAuthViewTemplate)` | Replaces the case in the tree (protected) |

## Lifecycle and State

- Template instantiation builds every case. `addParsedObject()` keeps them in a list, so only the chosen case joins the control tree.
- Content in an unchosen case does not load post data, raise postback events, or render. A crafted postback to its button finds no control.
- A control with an ID in an unchosen case is still registered on the template control (`$this->AdminLink`); setting its properties has no visible effect.
- A case chosen in `onPreRender` is added late and catches up to the Load stage.
- Each case has its own ID, `<view ID>_<index>` or `<view ID>_self`, unless the template sets one. A newly chosen case never loads the view state of another case.

## Accessibility

Audited in Chromium, Firefox, and WebKit.

- TAuthView and TAuthViewTemplate render no element, so the chosen content keeps its own structure and roles, for example `<li>` items inside a `<ul>`.
- Unchosen content is absent from the DOM and the accessibility tree; it is not hidden with CSS.
- A case switched by a postback is part of the new page; there is no live region.

## Gotchas

- The IDs of controls in different cases of one view must differ; they share a naming container.
- `Permissions` uses `can()`, which allows a permission without rules, so a misspelled permission matches.
- Hiding content is not authorization; the page or action behind a link still needs its own rule.

## Tests

- `tests/unit/Web/UI/WebControls/TAuthViewTest.php`
- `tests/playwright/security/TAuthViewTestCase.spec.js` with `tests/harness/security/protected/pages/AuthViewTest.page`: guest and role cases, login and logout by postback, view state in the chosen case, a crafted postback to an unchosen case, ARIA snapshots

## See Also

- [TAuthViewTemplate](./TAuthViewTemplate.md)
- [TConditional](./TConditional.md)
- [TAuthorizationRule](../../../Security/TAuthorizationRule.md)
- [TAuthManager](../../../Security/TAuthManager.md)

**@since 4.4.0**
