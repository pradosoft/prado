# Web/UI/WebControls/TWizard

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TWizard`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TWizard.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Multi-step wizard control. Manages a sequence of `TWizardStep` views with navigation (Previous/Next/Complete/Cancel) and an optional sidebar. Tracks navigation history so users can go back. Implements `INamingContainer`.

Extends `[TWebControl](./TWebControl.md)`.

## Command Constants

```php
TWizard::CMD_PREVIOUS = 'PreviousStep'
TWizard::CMD_NEXT     = 'NextStep'
TWizard::CMD_CANCEL   = 'Cancel'
TWizard::CMD_COMPLETE = 'Complete'
TWizard::CMD_MOVETO   = 'MoveTo'   // jump to a step by index or step ID

TWizard::ID_SIDEBAR_BUTTON = 'SideBarButton'
TWizard::ID_SIDEBAR_LIST   = 'SideBarList'
```

## Step Types (TWizardStepType enum)

```php
TWizardStepType::Auto      // first step = Start; last = Finish; others = Step
TWizardStepType::Start     // renders Start navigation (Next + Cancel)
TWizardStepType::Step      // renders Step navigation (Previous + Next + Cancel)
TWizardStepType::Finish    // renders Finish navigation (Previous + Complete + Cancel)
TWizardStepType::Complete  // final step; no back navigation
```

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `ActiveStepIndex` | int | Zero-based index of the current step; an index set before the steps exist (a template attribute) applies at init |
| `ActiveStep` | TWizardStep | Current step control |
| `WizardSteps` | TWizardStepCollection | All step controls |
| `ShowCancelButton` | bool | Show Cancel button in navigation (default: false) |
| `ShowSideBar` | bool | Show step-list sidebar (default: true) |
| `HeaderText` | string | Text shown in header area |
| `UseDefaultLayout` | bool | Use built-in layout (default: true) |
| `CancelDestinationUrl` | string | Redirect URL on Cancel |
| `FinishDestinationUrl` | string | Redirect URL on Complete |

## Navigation Templates

Override the default navigation buttons:

| Property | Replaces |
|----------|---------|
| `StartNavigationTemplate` | Navigation on first step |
| `StepNavigationTemplate` | Navigation on middle steps |
| `FinishNavigationTemplate` | Navigation on last step |
| `HeaderTemplate` | Header area |
| `SideBarTemplate` | Sidebar step list |

## Style Properties

`SideBarStyle`, `HeaderStyle`, `StepStyle`, `NavigationStyle`, `SideBarButtonStyle`, `NavigationButtonStyle`, `StartNextButtonStyle`, `StepNextButtonStyle`, `StepPreviousButtonStyle`, `FinishCompleteButtonStyle`, `FinishPreviousButtonStyle`, `CancelButtonStyle`

## Events

| Event | Description | CancelNavigation |
|-------|-------------|-----------------|
| `OnActiveStepChanged` | Step changed | — |
| `OnNextButtonClick` | Next button clicked | ✓ via `$param->setCancelNavigation(true)` |
| `OnPreviousButtonClick` | Previous button clicked | ✓ |
| `OnCancelButtonClick` | Cancel button clicked | ✓ |
| `OnCompleteButtonClick` | Complete/Finish clicked | ✓ |
| `OnSideBarButtonClick` | Sidebar step link clicked | ✓ |

Setting `$param->setCancelNavigation(true)` in any navigation event handler prevents the wizard from advancing/retreating.

## Key Methods

```php
$wizard->getActiveStep(): TWizardStep
$wizard->setActiveStep(TWizardStep $step): void
$wizard->getActiveStepIndex(): int
$wizard->setActiveStepIndex(int $index): void
$wizard->getWizardSteps(): TWizardStepCollection
$wizard->getMultiView(): TMultiView           // underlying TMultiView
$wizard->addedWizardStep(TWizardStep $step): void
$wizard->removedWizardStep(TWizardStep $step): void
```

## History / Back Navigation

The wizard keeps a `TStack` of visited step indices in control state; `saveState()` pushes the active index when it differs from the top. The Previous button moves to the step below the active one on the stack and pops both, so navigation can be non-linear. With an empty history there is no previous step, and the Previous button is hidden. A navigation command does not return to a step in the history whose `AllowReturn` is false.

## TWizardStep

```xml
<com:TWizardStep ID="step1" Title="Account Info" StepType="Start">
    ...form fields...
</com:TWizardStep>
```

| Property | Description |
|----------|-------------|
| `Title` | Displayed in sidebar |
| `StepType` | TWizardStepType enum (Auto/Start/Step/Finish/Complete) |
| `AllowReturn` | Whether Previous can navigate back to this step (default: true) |

## Template Usage

```xml
<com:TWizard ID="wizard" OnNextButtonClick="onNext"
             OnCompleteButtonClick="onComplete"
             ShowCancelButton="true"
             CancelDestinationUrl="Home">
    <com:TWizardStep ID="step1" Title="Step 1" StepType="Start">
        <com:TTextBox ID="name" />
    </com:TWizardStep>
    <com:TWizardStep ID="step2" Title="Step 2">
        <com:TTextBox ID="email" />
    </com:TWizardStep>
    <com:TWizardStep ID="finish" Title="Done" StepType="Finish">
        Summary...
    </com:TWizardStep>
</com:TWizard>
```

## Patterns & Gotchas

- **`CancelNavigation`** — always check whether you need `$param->setCancelNavigation(true)` in navigation events when validation fails.
- **Step validation** — the built-in Next and Complete buttons cause validation; Previous and Cancel do not. When the page is invalid after a validating button, the navigation is cancelled. Give each step's validators and its buttons a `ValidationGroup` to validate one step at a time.
- **`ActiveStepIndex` vs `ActiveStep`** — use index for serializable state; use `ActiveStep` for the control reference.
- **Sidebar requires `ShowSideBar=true`** — enabled by default. The sidebar renders a list of step titles with click-through navigation (subject to `AllowReturn`).
- **Sidebar step indicator** — the default `TWizardSideBarTemplate` sets the `SideBarList` `TDataList` to `TRepeatLayout::OrderedList`, so the steps render as an `<ol>` (a `<table>` before 4.4.0). `applySideBarProperties()` puts `aria-current="step"` on the active step's button. A custom `SideBarTemplate` sets `RepeatLayout="OrderedList"` on its data list for the same structure.
- **`UseDefaultLayout=false`** — renders the sidebar, header, step, and navigation panels one after another inside the wizard `div`, without the layout `<table>`.
- **Recreated controls** — setting a template after init recreates the child controls on the next `ensureChildControls()`; the active step is kept.
