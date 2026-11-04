# Web/UI/WebControls/TTeleport

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTeleport`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTeleport.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `TWebControl`
**Since:** 4.4.0 (issue #1408)

## Overview
TTeleport renders its content after `</form>` of the page [TForm](../TForm.md), outside the form (Vue `<Teleport>`, React portals). The content is a `<div id="<ClientID>" data-prado-teleport>`. In the control tree the children stay where the template puts them, so postback data, events, validators, and callbacks work as in place.

JavaScript: `controls/teleport.js`, client class `Prado.WebUI.TTeleport`, the `teleport` package in `Web/Javascripts/packages.php`.

Uses:
- A native `<form method="dialog">` in a `TDialog`. Inside the page form the HTML parser drops the nested `<form>` start tag, so the dialog form does not exist and its buttons submit the page form.
- Overlays and toasts free of a transformed, clipped, or stacking ancestor at the template position. `position: fixed` content is positioned by the viewport.

## Template Usage

```xml
<button type="button" onclick="document.getElementById('<%= $this->EditDialog->ClientID %>').showModal()">Edit</button>
<com:TTeleport>
	<com:TDialog ID="EditDialog" Attributes.aria-labelledby="EditTitle">
		<h2 id="EditTitle">Edit name</h2>
		<com:TLabel ForControl="Name" Text="Name" />
		<com:TTextBox ID="Name" />
		<com:TButton Text="Save" OnClick="saveClicked" />
		<form method="dialog">
			<button value="cancel">Cancel</button>
		</form>
	</com:TDialog>
</com:TTeleport>
```

Save posts back with `Name`; Cancel closes the dialog without a request and sets `returnValue` to `cancel`.

## Rendering

| Render | Output |
|---|---|
| Page render, inside the page form | Content registered with `TForm::registerTeleportContent()`; written after `</form>`, then the end script files and end scripts |
| Inside another TTeleport | In place, in the outer content (no client control of its own) |
| Outside the page form, or no form | In place |
| Callback, in an ancestor's re-render | Nothing in place; `Prado.WebUI.TTeleport.replace(id, formId, content, boundary)` replaces the `<div>` after the form, or adds it after the form and the teleports that follow it |

The page render captures the content by swapping the writer's inner text writer (`renderContent()`), so the writer keeps its render filter context. The filters of the TTeleport's ancestors up to the form do not see the content.

TForm renders the end scripts after the teleported content only when there is some; a page without a TTeleport renders as before. The end scripts (client control constructors) find the content elements because they come after them.

## Form association (client)

`Prado.WebUI.TTeleport` sets `form="<page form id>"` on each `button, fieldset, input, object, output, select, textarea` in the content. Skipped: an element inside a form (a nested dialog form, or the page form when the TTeleport renders in place inside it), an element with its own `form` attribute, and `<template>` content. A `MutationObserver` (childList, subtree, and the `form` attribute) applies it to content a callback or script adds, and restores the attribute after a morph removes it. `onDone()` disconnects it when a callback registers the control again.

| Path | Teleported field |
|---|---|
| Native submit or `Prado.PostBack` (`form.submit()`) | Submitted through its `form` attribute |
| Callback (`Prado.CallbackRequest_serializePageInputs`) | Posted: the serializer reads every `input, select, textarea` on the page, whatever the form, the fields of a dialog form included |
| Field in a nested `<form method="dialog">` | Belongs to the dialog form; not in a postback; posted by a callback |

`Prado.CallbackRequest.getForm()` uses the target's form owner (`el.form`, so the `form` attribute counts) and skips a `method="dialog"` form, falling back to the form of `PRADO_PAGESTATE`. An active control inside a dialog form calls back through the page form.

## Callbacks

- An active control inside the content updates by its ID; `ActiveControl.UpdateMode` Morph applies.
- An ancestor re-render (e.g. a TActivePanel that holds the TTeleport) re-renders the TTeleport through its callback path; the TTeleport writes nothing into the ancestor's output.
- A TTeleport whose Visible is false on the first render is added after the form when an ancestor re-render shows it.

## TDialog and TActiveDialog

A dialog inside a TTeleport can hold a `<form method="dialog">`: its submit buttons close the dialog script-free, set `returnValue` from the button `value`, and run the constraint check of the dialog form's fields first (`formnovalidate` skips it). A `TActiveDialog` raises OnClose by callback after such a close, and that callback posts the dialog form's fields and `returnValue` (`TActiveDialog.ReturnValue`).

A [TButton](TButton.md) with `CommandFor` and `Command` (Invoker Commands) opens and closes a [TDialog](TDialog.md) script-free inside the page form. Prefer invokers for a dialog whose fields post back with the page and need no dialog form; prefer a TTeleport for the dialog form (constraint check before closing) and for content that must escape its ancestors.

## Accessibility

| Condition | Result |
|---|---|
| Any render | The `<div>` has no role; `Role`, `Attributes` pass through (e.g. `role="status"` for a toast region). |
| Content after the form | Reading and Tab order follow the DOM, so the content comes last (WCAG 1.3.2, 2.4.3). |
| Modal `<dialog>` (`showModal()`) | Top layer; the page is inert; focus moves into the dialog; Escape closes it and focus returns to the opener. |
| Non-modal content | Comes last in Tab order; use it only where its position in the reading order does not matter, or move focus to it. |
| Toast | Put the live region (`role="status"`) in the page before its text changes. |

## Behavior to know

- Without JavaScript a teleported field has no form owner and a postback does not submit it; the native dialog form works.
- Inside a TOutputCache the cached output does not hold the teleported content.
- A TTeleport removed or hidden by an ancestor's callback re-render leaves its last content after the form; clear it through an active control inside it.
- Nested TTeleports flatten into the outermost one.

## See Also
- [TForm](../TForm.md): `registerTeleportContent()`, `getTeleportContents()`, `getHasTeleportContent()`
- `TDialog`, `TActiveDialog`
- [TActiveUpdateMode](../ActiveControls/TActiveUpdateMode.md)
