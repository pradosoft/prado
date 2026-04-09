# TWizard

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TWizard](./TWizard.md)

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
TWizard::CMD_MOVETO   = 'MoveTo'   // jump to specific step by index

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
| `ActiveStepIndex` | int | Zero-based index of the current step |
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

The wizard maintains a history stack of visited step indices in viewstate. The Previous button pops from this stack rather than simply decrementing the index, supporting non-linear navigation.

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
- **Step validation** — validators inside a step should use a `ValidationGroup` matching the step's Next button. The built-in navigation templates do this automatically with default layouts.
- **`ActiveStepIndex` vs `ActiveStep`** — use index for serializable state; use `ActiveStep` for the control reference.
- **Sidebar requires `ShowSideBar=true`** — enabled by default. The sidebar renders a list of step titles with click-through navigation (subject to `AllowReturn`).
- **`UseDefaultLayout=false`** — when using fully custom navigation templates, disable this to avoid double-rendering default layout containers.
