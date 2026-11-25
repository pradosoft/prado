<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TStack;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\THttpResponse;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TCompleteWizardStep;
use Prado\Web\UI\WebControls\TDataList;
use Prado\Web\UI\WebControls\TDataListCommandEventParameter;
use Prado\Web\UI\WebControls\TDataListItem;
use Prado\Web\UI\WebControls\TDataListItemEventParameter;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TMultiView;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TPanelStyle;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTemplatedWizardStep;
use Prado\Web\UI\WebControls\TWizard;
use Prado\Web\UI\WebControls\TWizardNavigationButtonStyle;
use Prado\Web\UI\WebControls\TWizardNavigationButtonType;
use Prado\Web\UI\WebControls\TWizardNavigationContainer;
use Prado\Web\UI\WebControls\TWizardNavigationEventParameter;
use Prado\Web\UI\WebControls\TWizardSideBarTemplate;
use Prado\Web\UI\WebControls\TWizardStep;
use Prado\Web\UI\WebControls\TWizardStepCollection;
use Prado\Web\UI\WebControls\TWizardStepType;

/**
 * Page whose validity a test sets.
 */
class TWizardTestPage extends TPage
{
	public bool $valid = true;

	public function getIsValid()
	{
		return $this->valid;
	}
}

/**
 * Client script manager that records the default buttons a wizard registers.
 */
class TWizardTestClientScript extends TClientScriptManager
{
	public array $defaultButtons = [];

	public function registerDefaultButton($panel, $button)
	{
		$this->defaultButtons[] = [$panel, $button];
	}

	public function registerPostBackControl($class, $options)
	{
	}
}

/**
 * Response that records redirects.
 */
class TWizardTestResponse extends THttpResponse
{
	public array $redirects = [];

	public function redirect($url)
	{
		$this->redirects[] = $url;
	}
}

/**
 * Control that has a style but is not a TWebControl.
 */
class TWizardTestStyledControl extends TControl
{
	private ?TStyle $_style = null;

	public function getStyle()
	{
		return $this->_style ??= new TStyle();
	}

	public function setText($value)
	{
	}

	public function setCommandName($value)
	{
	}

	public function setCommandParameter($value)
	{
	}
}

/**
 * Wizard that creates no child controls.
 */
class TWizardTestEmptyWizard extends TWizard
{
	public function createChildControls()
	{
	}
}

class TWizardTest extends TestCase
{
	use TOutputBufferRestorationTrait;

	/** @var array<string> event log written by the handlers */
	private array $events = [];

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreOutputBufferLevel();
	}

	private function createPage(): TWizardTestPage
	{
		$page = new TWizardTestPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TWizardTestClientScript($page));
		return $page;
	}

	/**
	 * Creates a wizard on a page with a step per entry, a string being the step ID.
	 * @param array<string|TWizardStep> $steps
	 */
	private function createWizard(array $steps = ['a', 'b', 'c'], bool $init = true, ?TWizard $wizard = null): TWizard
	{
		$wizard ??= new TWizard();
		$wizard->setID('wiz');
		foreach ($steps as $step) {
			$wizard->addParsedObject($this->createStep($step));
		}
		$this->createPage()->getControls()->add($wizard);
		if ($init) {
			$this->init($wizard);
		}
		return $wizard;
	}

	private function createStep(string|TWizardStep $step, string $type = TWizardStepType::Auto): TWizardStep
	{
		if (is_string($step)) {
			$id = $step;
			$step = new TWizardStep();
			$step->setID($id);
			$text = new TLiteral();
			$text->setText("[$id]");
			$step->getControls()->add($text);
		}
		if ($type !== TWizardStepType::Auto) {
			$step->setStepType($type);
		}
		return $step;
	}

	private function init(TControl $control): void
	{
		PradoUnit::invoke($control, 'initRecursive');
	}

	private function render(TControl $control): string
	{
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	private function command(string $name, $parameter = null): TCommandEventParameter
	{
		return new TCommandEventParameter($name, $parameter);
	}

	private function history(TWizard $wizard): array
	{
		return PradoUnit::invoke($wizard, 'getHistory')->toArray();
	}

	/**
	 * Logs the wizard's events, with each navigation's current and next index.
	 */
	private function recordEvents(TWizard $wizard): void
	{
		foreach (['OnActiveStepChanged', 'OnNextButtonClick', 'OnPreviousButtonClick', 'OnCancelButtonClick', 'OnCompleteButtonClick', 'OnSideBarButtonClick'] as $event) {
			$wizard->attachEventHandler($event, function ($sender, $param) use ($event) {
				$entry = substr($event, 2);
				if ($param instanceof TWizardNavigationEventParameter) {
					$entry .= ':' . $param->getCurrentStepIndex() . '>' . $param->getNextStepIndex();
				}
				$this->events[] = $entry;
			});
		}
	}

	private function defaultButtons(TWizard $wizard): array
	{
		return array_map(fn ($entry) => $entry[1], $wizard->getPage()->getClientScript()->defaultButtons);
	}

	private function withResponse(callable $callback): TWizardTestResponse
	{
		$app = Prado::getApplication();
		$saved = $app->getResponse();
		$response = new TWizardTestResponse();
		$app->setResponse($response);
		try {
			$callback();
		} finally {
			$app->setResponse($saved);
		}
		return $response;
	}

	private function sideBarCommand(TWizard $wizard, string $command, $parameter, $source = null): void
	{
		$items = $wizard->getSideBar()->findControl(TWizard::ID_SIDEBAR_LIST)->getItems();
		$item = $items->itemAt(0);
		$wizard->dataListItemCommand(null, new TDataListCommandEventParameter($item, $source ?? $this, $this->command($command, $parameter)));
	}

	// --- properties ---

	public function testPropertyDefaults()
	{
		$wizard = new TWizard();
		$this->assertSame('div', PradoUnit::invoke($wizard, 'getTagName'));
		$this->assertFalse($wizard->getShowCancelButton());
		$this->assertTrue($wizard->getShowSideBar());
		$this->assertSame('', $wizard->getHeaderText());
		$this->assertSame('', $wizard->getCancelDestinationUrl());
		$this->assertSame('', $wizard->getFinishDestinationUrl());
		$this->assertTrue($wizard->getUseDefaultLayout());
		$this->assertNull($wizard->getStartNavigationTemplate());
		$this->assertNull($wizard->getStepNavigationTemplate());
		$this->assertNull($wizard->getFinishNavigationTemplate());
		$this->assertNull($wizard->getHeaderTemplate());
		$this->assertNull($wizard->getSideBarTemplate());
		$this->assertNull($wizard->getHeader());
		$this->assertNull($wizard->getStepContent());
		$this->assertNull($wizard->getSideBar());
		$this->assertNull($wizard->getStartNavigation());
		$this->assertNull($wizard->getStepNavigation());
		$this->assertNull($wizard->getFinishNavigation());
		$this->assertNull($wizard->getActiveStep());
		$this->assertSame(-1, $wizard->getActiveStepIndex());
	}

	public function testPropertySetters()
	{
		$wizard = new TWizard();
		$wizard->setShowCancelButton('true');
		$this->assertTrue($wizard->getShowCancelButton());
		$wizard->setShowSideBar('false');
		$this->assertFalse($wizard->getShowSideBar());
		$wizard->setHeaderText('Sign up');
		$this->assertSame('Sign up', $wizard->getHeaderText());
		$wizard->setCancelDestinationUrl('/home');
		$this->assertSame('/home', $wizard->getCancelDestinationUrl());
		$wizard->setFinishDestinationUrl('/done');
		$this->assertSame('/done', $wizard->getFinishDestinationUrl());
		$wizard->setUseDefaultLayout('false');
		$this->assertFalse($wizard->getUseDefaultLayout());
	}

	public function testStyleGettersCreateTheirStyleOnce()
	{
		$wizard = new TWizard();
		$panelStyles = ['getSideBarStyle', 'getHeaderStyle', 'getStepStyle', 'getNavigationStyle'];
		foreach ($panelStyles as $getter) {
			$style = $wizard->$getter();
			$this->assertInstanceOf(TPanelStyle::class, $style, $getter);
			$this->assertSame($style, $wizard->$getter(), $getter);
		}
		foreach (['getSideBarButtonStyle', 'getNavigationButtonStyle'] as $getter) {
			$style = $wizard->$getter();
			$this->assertSame(TStyle::class, $style::class, $getter);
			$this->assertSame($style, $wizard->$getter(), $getter);
		}
		$buttonStyles = [
			'getStartNextButtonStyle' => 'Next',
			'getStepNextButtonStyle' => 'Next',
			'getStepPreviousButtonStyle' => 'Previous',
			'getFinishCompleteButtonStyle' => 'Complete',
			'getFinishPreviousButtonStyle' => 'Previous',
			'getCancelButtonStyle' => 'Cancel',
		];
		foreach ($buttonStyles as $getter => $text) {
			$style = $wizard->$getter();
			$this->assertInstanceOf(TWizardNavigationButtonStyle::class, $style, $getter);
			$this->assertSame($text, $style->getButtonText(), $getter);
			$this->assertSame($style, $wizard->$getter(), $getter);
		}
	}

	public function testTemplateSettersRequireControlsRecreation()
	{
		$wizard = $this->createWizard();
		$setters = ['setStartNavigationTemplate', 'setStepNavigationTemplate', 'setFinishNavigationTemplate', 'setHeaderTemplate', 'setSideBarTemplate'];
		foreach ($setters as $setter) {
			$wizard->ensureChildControls();
			$this->assertTrue(PradoUnit::invoke($wizard, 'getChildControlsCreated'), $setter);
			$template = new TWizardTestTemplate(fn ($parent) => null);
			$wizard->$setter($template);
			$getter = 'g' . substr($setter, 1);
			$this->assertSame($template, $wizard->$getter(), $setter);
			$this->assertFalse(PradoUnit::invoke($wizard, 'getChildControlsCreated'), $setter);
		}
		// before the controls are created there is nothing to recreate
		$fresh = new TWizard();
		$fresh->setHeaderTemplate(null);
		$this->assertFalse(PradoUnit::invoke($fresh, 'getChildControlsCreated'));
	}

	// --- steps ---

	public function testAddParsedObjectAddsSteps()
	{
		$wizard = new TWizard();
		$step = new TWizardStep();
		$wizard->addParsedObject($step);
		$wizard->addParsedObject('text');
		$wizard->addParsedObject(new TLiteral());
		$steps = $wizard->getWizardSteps();
		$this->assertInstanceOf(TWizardStepCollection::class, $steps);
		$this->assertSame($steps, $wizard->getWizardSteps());
		$this->assertSame([$step], $steps->toArray());
		$this->assertSame($wizard, $step->getWizard());
		$this->assertSame([$step], $wizard->getMultiView()->getViews()->toArray());
	}

	public function testMultiView()
	{
		$wizard = new TWizard();
		$multiView = $wizard->getMultiView();
		$this->assertInstanceOf(TMultiView::class, $multiView);
		$this->assertSame($multiView, $wizard->getMultiView());
		$this->assertSame('WizardMultiView', $multiView->getID());
		$this->assertTrue(PradoUnit::getProp($multiView, '_ignoreBubbleEvents'));
		$this->assertTrue($multiView->getEventHandlers('OnActiveViewChanged')->contains([$wizard, 'onActiveStepChanged']));
	}

	public function testRemovedWizardStep()
	{
		$wizard = new TWizard();
		$step = $this->createStep('a');
		$wizard->addParsedObject($step);
		$wizard->getWizardSteps()->remove($step);
		$this->assertNull($step->getWizard());
		$this->assertSame(0, $wizard->getMultiView()->getViews()->getCount());
	}

	public function testStepChangesRebindTheSideBar()
	{
		$wizard = $this->createWizard(['a', 'b']);
		$list = $wizard->getSideBar()->findControl(TWizard::ID_SIDEBAR_LIST);
		$this->assertSame(2, $list->getItems()->getCount());
		$wizard->addParsedObject($this->createStep('c'));
		$this->assertSame(3, $list->getItems()->getCount());
		$wizard->getWizardSteps()->itemAt(2)->setTitle('Third');
		$this->assertSame('Third', $list->getItems()->itemAt(2)->findControl(TWizard::ID_SIDEBAR_BUTTON)->getText());
	}

	// --- active step ---

	public function testOnInitActivatesTheFirstStep()
	{
		$wizard = $this->createWizard();
		$this->assertSame(0, $wizard->getActiveStepIndex());
		$this->assertSame($wizard->getWizardSteps()->itemAt(0), $wizard->getActiveStep());
		$this->assertTrue($wizard->getEnsureId());
	}

	public function testOnInitActivatesStepsAddedAfterTheControlsWereCreated()
	{
		$wizard = new TWizard();
		$wizard->ensureChildControls();
		$this->createWizard(['a', 'b'], false, $wizard);
		$this->assertSame(-1, $wizard->getActiveStepIndex());
		$this->init($wizard);
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testOnInitWithoutSteps()
	{
		$wizard = $this->createWizard([]);
		$this->assertSame(-1, $wizard->getActiveStepIndex());
		$this->assertNull($wizard->getActiveStep());
	}

	public function testSetActiveStep()
	{
		$wizard = $this->createWizard();
		$this->recordEvents($wizard);
		$step = $wizard->getWizardSteps()->itemAt(2);
		$wizard->setActiveStep($step);
		$this->assertSame(2, $wizard->getActiveStepIndex());
		$this->assertSame($step, $wizard->getActiveStep());
		$this->assertSame(['ActiveStepChanged'], $this->events);

		// the same index again changes nothing
		$wizard->setActiveStepIndex('2');
		$this->assertSame(['ActiveStepChanged'], $this->events);

		$this->expectException(TInvalidOperationException::class);
		$wizard->setActiveStep(new TWizardStep());
	}

	public function testGetActiveStepIsNullForAViewThatIsNotAStep()
	{
		$wizard = $this->createWizard(['a']);
		$view = new \Prado\Web\UI\WebControls\TView();
		$wizard->getMultiView()->getViews()->add($view);
		$wizard->getMultiView()->setActiveViewIndex(1);
		$this->assertNull($wizard->getActiveStep());
	}

	public function testCreatingTheControlsActivatesAViewThatIsNotAStep()
	{
		$wizard = $this->createWizard([]);
		$wizard->getMultiView()->getViews()->add(new \Prado\Web\UI\WebControls\TView());
		$wizard->setHeaderTemplate(null);
		$wizard->ensureChildControls();
		$this->assertSame(0, $wizard->getActiveStepIndex());
		$wizard->setHeaderTemplate(null);
		$wizard->ensureChildControls();
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testSetActiveStepIndexRebindsACustomSideBar()
	{
		$wizard = new TWizard();
		$wizard->setSideBarTemplate(new TWizardSideBarTemplate());
		$this->createWizard(['a', 'b'], true, $wizard);
		$list = $wizard->getSideBar()->findControl(TWizard::ID_SIDEBAR_LIST);
		$wizard->setActiveStepIndex(1);
		$this->assertSame(1, $list->getSelectedItemIndex());
	}

	public function testActiveStepIndexSetBeforeInitIsKept()
	{
		$wizard = new TWizard();
		$wizard->setActiveStepIndex(2);
		$this->createWizard(['a', 'b', 'c'], true, $wizard);
		$this->assertSame(2, $wizard->getActiveStepIndex());
	}

	public function testRecreatingTheControlsKeepsTheActiveStep()
	{
		$wizard = $this->createWizard();
		$wizard->setActiveStepIndex(1);
		$wizard->setHeaderTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('Header')));
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertSame(1, $wizard->getActiveStepIndex());
		$this->assertStringContainsString('[b]', $output);
	}

	// --- state ---

	public function testSaveStatePushesTheActiveIndexOnce()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->saveState();
		$this->assertSame([0], $this->history($wizard));
		$wizard->setActiveStepIndex(2);
		$wizard->saveState();
		$this->assertSame([0, 2], $this->history($wizard));
		$history = PradoUnit::invoke($wizard, 'getHistory');
		$this->assertInstanceOf(TStack::class, $history);
		$this->assertSame($history, PradoUnit::invoke($wizard, 'getHistory'));
	}

	// --- step types ---

	public function testStepTypeOfAutoSteps()
	{
		$wizard = $this->createWizard(['a', 'b', 'c']);
		$steps = $wizard->getWizardSteps();
		$type = fn ($i) => PradoUnit::invoke($wizard, 'getStepType', $steps->itemAt($i));
		$this->assertSame(TWizardStepType::Start, $type(0));
		$this->assertSame(TWizardStepType::Step, $type(1));
		$this->assertSame(TWizardStepType::Finish, $type(2));
	}

	public function testStepTypeOfASingleStep()
	{
		$wizard = $this->createWizard(['a']);
		$this->assertSame(TWizardStepType::Finish, PradoUnit::invoke($wizard, 'getStepType', $wizard->getWizardSteps()->itemAt(0)));
	}

	public function testStepTypeBeforeACompleteStepIsFinish()
	{
		$wizard = $this->createWizard(['a', 'b']);
		$complete = new TCompleteWizardStep();
		$wizard->addParsedObject($complete);
		$steps = $wizard->getWizardSteps();
		$this->assertSame(TWizardStepType::Start, PradoUnit::invoke($wizard, 'getStepType', $steps->itemAt(0)));
		$this->assertSame(TWizardStepType::Finish, PradoUnit::invoke($wizard, 'getStepType', $steps->itemAt(1)));
		$this->assertSame(TWizardStepType::Complete, PradoUnit::invoke($wizard, 'getStepType', $complete));
	}

	public function testStepTypeOfAStepOutsideTheWizardOrSetExplicitly()
	{
		$wizard = $this->createWizard(['a', 'b']);
		$this->assertSame(TWizardStepType::Auto, PradoUnit::invoke($wizard, 'getStepType', new TWizardStep()));
		$step = $wizard->getWizardSteps()->itemAt(0);
		$step->setStepType(TWizardStepType::Step);
		$this->assertSame(TWizardStepType::Step, PradoUnit::invoke($wizard, 'getStepType', $step));
	}

	// --- rendering ---

	public function testRenderDefaultLayout()
	{
		$wizard = $this->createWizard();
		$wizard->setHeaderText('Sign up');
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringStartsWith('<div id="wiz">', $output);
		$this->assertStringContainsString('<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">', $output);
		$this->assertStringContainsString('<ol id="wiz_SideBarList">', $output);
		$this->assertStringContainsString('aria-current="step"', $output);
		$this->assertStringContainsString('<div>Sign up</div>', $output);
		$this->assertStringContainsString('[a]', $output);
		$this->assertStringNotContainsString('[b]', $output);
		$this->assertStringContainsString('value="Next"', $output);
		$this->assertStringNotContainsString('value="Cancel"', $output);
		$this->assertStringNotContainsString('value="Previous"', $output);
		$this->assertStringEndsWith("</td></tr></table>\n</div>", $output);
		$this->assertSame([$wizard->getStartNavigation()->getNextButton()], $this->defaultButtons($wizard));
	}

	public function testRenderWithoutDefaultLayout()
	{
		$wizard = $this->createWizard();
		$wizard->setUseDefaultLayout(false);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringNotContainsString('<table', $output);
		$this->assertStringStartsWith('<div id="wiz"><div><ol id="wiz_SideBarList">', $output);
		$this->assertStringContainsString('[a]', $output);
	}

	public function testRenderWithoutChildControls()
	{
		$wizard = new TWizardTestEmptyWizard();
		$this->assertSame('', $this->render($wizard));
	}

	public function testRenderAMiddleStep()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('[b]', $output);
		$this->assertStringContainsString('value="Previous"', $output);
		$this->assertStringContainsString('value="Next"', $output);
		$this->assertSame([$wizard->getStepNavigation()->getNextButton()], $this->defaultButtons($wizard));
		$this->assertTrue($wizard->getStepNavigation()->getVisible(false));
		$this->assertFalse($wizard->getStartNavigation()->getVisible(false));
		$this->assertFalse($wizard->getFinishNavigation()->getVisible(false));
	}

	public function testRenderTheFinishStep()
	{
		$wizard = $this->createWizard();
		$wizard->setShowCancelButton(true);
		$wizard->saveState();
		$wizard->setActiveStepIndex(2);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('[c]', $output);
		$this->assertStringContainsString('value="Complete"', $output);
		$this->assertStringContainsString('value="Cancel"', $output);
		$this->assertSame([$wizard->getFinishNavigation()->getCompleteButton()], $this->defaultButtons($wizard));
		$this->assertTrue($wizard->getFinishNavigation()->getPreviousButton()->getVisible(false));
	}

	public function testPreviousButtonFollowsAllowReturn()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$wizard->getWizardSteps()->itemAt(0)->setAllowReturn(false);
		$this->render($wizard);
		$this->assertFalse($wizard->getStepNavigation()->getPreviousButton()->getVisible(false));
	}

	public function testRenderWithoutHistoryHidesThePreviousButton()
	{
		$wizard = $this->createWizard();
		$wizard->setActiveStepIndex(1);
		$output = $this->render($wizard);
		$this->assertStringContainsString('[b]', $output);
		$this->assertFalse($wizard->getStepNavigation()->getPreviousButton()->getVisible(false));
	}

	public function testRenderACompleteStepHidesTheSideBarAndHeader()
	{
		$wizard = $this->createWizard(['a']);
		$wizard->setHeaderText('Header');
		$wizard->addParsedObject(new TCompleteWizardStep());
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringNotContainsString('SideBarList', $output);
		$this->assertStringNotContainsString('Header', $output);
		$this->assertStringNotContainsString('<input', $output);
		$this->assertSame([], $this->defaultButtons($wizard));
	}

	public function testRenderWithoutStepsHidesTheNavigation()
	{
		$wizard = $this->createWizard([]);
		$output = $this->render($wizard);
		$this->assertFalse(PradoUnit::getProp($wizard, '_navigation')->getVisible(false));
		$this->assertStringNotContainsString('<input', $output);
	}

	public function testApplyNavigationPropertiesBeforeTheControlsExist()
	{
		$wizard = new TWizard();
		$wizard->addParsedObject($this->createStep('a'));
		PradoUnit::invoke($wizard, 'applyNavigationProperties');
		$this->assertNull(PradoUnit::getProp($wizard, '_navigation'));
	}

	public function testRenderAppliesTheStyles()
	{
		$wizard = new TWizard();
		// the button text is set when the buttons are created
		$wizard->getCancelButtonStyle()->setButtonText('Stop');
		$this->createWizard(['a', 'b', 'c'], true, $wizard);
		$wizard->setShowCancelButton(true);
		$wizard->getHeaderStyle()->setCssClass('hdr');
		$wizard->getSideBarStyle()->setCssClass('side');
		$wizard->getStepStyle()->setCssClass('step');
		$wizard->getNavigationStyle()->setCssClass('nav');
		$wizard->getSideBarButtonStyle()->setCssClass('sbtn');
		$wizard->getNavigationButtonStyle()->setCssClass('nbtn');
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('<div class="side">', $output);
		$this->assertStringContainsString('<div class="hdr">', $output);
		$this->assertStringContainsString('<div class="step">', $output);
		$this->assertStringContainsString('<div class="nav">', $output);
		$this->assertStringContainsString('class="sbtn"', $output);
		$this->assertStringContainsString('value="Next" class="nbtn"', $output);
		$this->assertStringContainsString('value="Stop" formnovalidate="formnovalidate" class="nbtn"', $output);
	}

	public function testRenderWithoutTheSideBar()
	{
		$wizard = new TWizard();
		$wizard->setShowSideBar(false);
		$this->createWizard(['a'], true, $wizard);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringNotContainsString('SideBarList', $output);
		$this->assertFalse($wizard->getSideBar()->getVisible(false));
		$this->assertNull(PradoUnit::getProp($wizard, '_sideBarDataList'));
	}

	public function testHeaderTemplate()
	{
		$wizard = new TWizard();
		$wizard->setHeaderText('Text');
		$wizard->setHeaderTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('From template')));
		$this->createWizard(['a'], true, $wizard);
		$wizard->getHeaderStyle()->setCssClass('hdr');
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('<div class="hdr">From template</div>', $output);
		$this->assertStringNotContainsString('Text', $output);
	}

	public function testSideBarTemplateWithoutAList()
	{
		$wizard = new TWizard();
		$wizard->setSideBarTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('Steps')));
		$this->createWizard(['a', 'b'], true, $wizard);
		$wizard->saveState();
		$this->assertNull(PradoUnit::getProp($wizard, '_sideBarDataList'));
		$wizard->setActiveStepIndex(1);
		$output = $this->render($wizard);
		$this->assertStringContainsString('<div>Steps</div>', $output);
	}

	public function testSideBarItemsWithoutAButtonOrWithAStyledControl()
	{
		$wizard = new TWizard();
		$wizard->setSideBarTemplate(new TWizardTestTemplate(function ($parent) {
			$list = new TDataList();
			$list->setID(TWizard::ID_SIDEBAR_LIST);
			$list->setItemTemplate(new TWizardTestTemplate(function ($item) {
				if ($item->getItemIndex() === 1) {
					$control = new TWizardTestStyledControl();
					$control->setID(TWizard::ID_SIDEBAR_BUTTON);
					$item->getControls()->add($control);
				} else {
					$item->getControls()->add('step');
				}
			}));
			$parent->getControls()->add($list);
		}));
		$this->createWizard(['a', 'b'], true, $wizard);
		$wizard->getSideBarButtonStyle()->setCssClass('sbtn');
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$this->render($wizard);
		$control = $wizard->getSideBar()->findControl(TWizard::ID_SIDEBAR_LIST)->getItems()->itemAt(1)->findControl(TWizard::ID_SIDEBAR_BUTTON);
		$this->assertInstanceOf(TWizardTestStyledControl::class, $control);
		$this->assertSame('sbtn', $control->getStyle()->getCssClass());
	}

	public function testCustomNavigationTemplatesWithoutButtons()
	{
		$wizard = new TWizard();
		$empty = new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('custom'));
		$wizard->setStartNavigationTemplate($empty);
		$wizard->setStepNavigationTemplate($empty);
		$wizard->setFinishNavigationTemplate($empty);
		$this->createWizard(['a', 'b'], true, $wizard);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('custom', $output);
		$this->assertStringNotContainsString('<input', $output);
		$this->assertSame([], $this->defaultButtons($wizard));
	}

	public function testTemplatedStepNavigationReplacesTheStandardNavigation()
	{
		$wizard = new TWizard();
		$templated = new TTemplatedWizardStep();
		$templated->setID('t');
		$templated->setContentTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('templated content')));
		$templated->setNavigationTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('templated nav')));
		$wizard->addParsedObject($templated);
		$wizard->addParsedObject($this->createStep('b'));
		$this->createWizard([], true, $wizard);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringContainsString('templated content', $output);
		$this->assertStringContainsString('templated nav', $output);
		$this->assertFalse($wizard->getStartNavigation()->getVisible(false));
		$this->assertStringNotContainsString('value="Next"', $output);

		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertStringNotContainsString('templated nav', $output);
		$this->assertTrue($templated->getNavigationContainer()->getParent() === PradoUnit::getProp($wizard, '_navigation'));
	}

	public function testWizardOfOnlyTemplatedSteps()
	{
		$wizard = new TWizard();
		$templated = new TTemplatedWizardStep();
		$templated->setNavigationTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('templated nav')));
		$wizard->addParsedObject($templated);
		$this->createWizard([], true, $wizard);
		$this->assertSame(0, $wizard->getActiveStepIndex());
		$this->assertSame($templated->getNavigationContainer()->getParent(), PradoUnit::getProp($wizard, '_navigation'));

		// recreated controls keep the active step
		$wizard->setHeaderTemplate(null);
		$wizard->saveState();
		$this->assertStringContainsString('templated nav', $this->render($wizard));
		$this->assertSame(0, $wizard->getActiveStepIndex());

		$wizard = new TWizard();
		$plain = new TTemplatedWizardStep();
		$wizard->addParsedObject($plain);
		$this->createWizard([], true, $wizard);
		$this->assertNull($plain->getNavigationContainer());
	}

	public function testTemplatedStepWithoutANavigationTemplate()
	{
		$wizard = new TWizard();
		$templated = new TTemplatedWizardStep();
		$wizard->addParsedObject($templated);
		$this->createWizard(['b'], true, $wizard);
		$wizard->saveState();
		$output = $this->render($wizard);
		$this->assertNull($templated->getNavigationContainer());
		$this->assertStringContainsString('value="Next"', $output);
	}

	// --- side bar list ---

	public function testSideBarItemDataBound()
	{
		$wizard = $this->createWizard(['a', 'b']);
		$wizard->getWizardSteps()->itemAt(0)->setTitle('First');
		$wizard->addParsedObject(new TCompleteWizardStep());
		$items = $wizard->getSideBar()->findControl(TWizard::ID_SIDEBAR_LIST)->getItems();
		$first = $items->itemAt(0)->findControl(TWizard::ID_SIDEBAR_BUTTON);
		$this->assertSame('First', $first->getText());
		$this->assertSame(TWizard::CMD_MOVETO, $first->getCommandName());
		$this->assertSame('0', $first->getCommandParameter());
		$this->assertTrue($first->getEnabled());
		$second = $items->itemAt(1)->findControl(TWizard::ID_SIDEBAR_BUTTON);
		$this->assertSame('b', $second->getText());
		$this->assertSame('1', $second->getCommandParameter());
		$complete = $items->itemAt(2)->findControl(TWizard::ID_SIDEBAR_BUTTON);
		$this->assertFalse($complete->getEnabled());
	}

	public function testSideBarItemDataBoundSkipsOtherItemTypesAndItemsWithoutAButton()
	{
		$wizard = $this->createWizard(['a']);
		$item = new TDataListItem();
		$item->setItemType(TListItemType::Header);
		$button = new TLinkButton();
		$button->setID(TWizard::ID_SIDEBAR_BUTTON);
		$item->getControls()->add($button);
		$wizard->dataListItemDataBound(null, new TDataListItemEventParameter($item));
		$this->assertSame('', $button->getCommandName());

		$item = new TDataListItem();
		$item->setItemType(TListItemType::Item);
		$item->getControls()->add('no button');
		$wizard->dataListItemDataBound(null, new TDataListItemEventParameter($item));
		$this->assertSame(1, $item->getControls()->getCount());
	}

	public function testSideBarMoveTo()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$this->recordEvents($wizard);
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '2');
		$this->assertSame(['SideBarButtonClick:0>2', 'ActiveStepChanged'], $this->events);
		$this->assertSame(2, $wizard->getActiveStepIndex());
	}

	public function testSideBarIgnoresOtherCommands()
	{
		$wizard = $this->createWizard();
		$this->recordEvents($wizard);
		$this->sideBarCommand($wizard, 'Other', '2');
		$this->assertSame([], $this->events);
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testSideBarMoveToCancelledByTheHandler()
	{
		$wizard = $this->createWizard();
		$wizard->attachEventHandler('OnSideBarButtonClick', fn ($sender, $param) => $param->setCancelNavigation(true));
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '2');
		$this->assertSame(0, $wizard->getActiveStepIndex());
		$this->assertTrue(PradoUnit::getProp($wizard, '_cancelNavigation'));
	}

	public function testSideBarMoveToCancelledByFailedValidation()
	{
		$wizard = $this->createWizard();
		$wizard->getPage()->valid = false;
		$button = new TLinkButton();
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '2', $button);
		$this->assertSame(0, $wizard->getActiveStepIndex());

		// a button that does not validate moves
		$button->setCausesValidation(false);
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '2', $button);
		$this->assertSame(2, $wizard->getActiveStepIndex());
	}

	public function testSideBarMoveToWhenTheHandlerSetsTheStep()
	{
		$wizard = $this->createWizard();
		$wizard->attachEventHandler('OnSideBarButtonClick', fn ($sender, $param) => $sender->setActiveStepIndex(1));
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '2');
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	public function testSideBarMoveToAStepThatDisallowsReturn()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$wizard->getWizardSteps()->itemAt(0)->setAllowReturn(false);
		$this->sideBarCommand($wizard, TWizard::CMD_MOVETO, '0');
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	// --- navigation history ---

	public function testPreviousStepIndex()
	{
		$wizard = $this->createWizard();
		$this->assertSame(-1, PradoUnit::invoke($wizard, 'getPreviousStepIndex', false));
		$this->assertSame(-1, PradoUnit::invoke($wizard, 'getPreviousStepIndex', true));
		$this->assertFalse(PradoUnit::invoke($wizard, 'allowNavigationToPreviousStep'));

		$wizard->saveState();
		// only the active step in the history
		$this->assertSame(-1, PradoUnit::invoke($wizard, 'getPreviousStepIndex', false));
		$this->assertSame([0], $this->history($wizard));

		$wizard->setActiveStepIndex(2);
		$wizard->saveState();
		$this->assertSame(0, PradoUnit::invoke($wizard, 'getPreviousStepIndex', false));
		$this->assertSame([0, 2], $this->history($wizard));
		$this->assertTrue(PradoUnit::invoke($wizard, 'allowNavigationToPreviousStep'));

		$this->assertSame(0, PradoUnit::invoke($wizard, 'getPreviousStepIndex', true));
		$this->assertSame([], $this->history($wizard));
	}

	public function testPreviousStepIndexWhenTheTopIsNotTheActiveStep()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		// history [0], active 1
		$this->assertSame(0, PradoUnit::invoke($wizard, 'getPreviousStepIndex', false));
		$this->assertSame(0, PradoUnit::invoke($wizard, 'getPreviousStepIndex', true));
		$this->assertSame([], $this->history($wizard));
	}

	public function testPopStackWithOnlyTheActiveStep()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$this->assertSame(-1, PradoUnit::invoke($wizard, 'getPreviousStepIndex', true));
		$this->assertSame([], $this->history($wizard));
	}

	public function testAllowNavigationToStep()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$this->assertTrue(PradoUnit::invoke($wizard, 'allowNavigationToStep', 2));
		$this->assertTrue(PradoUnit::invoke($wizard, 'allowNavigationToStep', 0));
		$wizard->getWizardSteps()->itemAt(0)->setAllowReturn(false);
		$this->assertFalse(PradoUnit::invoke($wizard, 'allowNavigationToStep', 0));
	}

	// --- bubble events ---

	public function testBubbleEventIgnoresOtherParametersAndCommands()
	{
		$wizard = $this->createWizard();
		$this->assertFalse($wizard->bubbleEvent($this, new TEventParameter()));
		$this->assertFalse($wizard->bubbleEvent($this, $this->command('Other')));
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testCommandsWithoutAnActiveStepAreNotHandled()
	{
		$wizard = $this->createWizard([]);
		$this->recordEvents($wizard);
		$this->assertFalse($wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT)));
		$this->assertFalse($wizard->bubbleEvent($this, $this->command('Save')));
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_CANCEL)));
		$this->assertSame(['CancelButtonClick'], $this->events);
	}

	public function testNextAndPrevious()
	{
		$wizard = $this->createWizard();
		$this->recordEvents($wizard);
		$wizard->saveState();
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT)));
		$this->assertSame(1, $wizard->getActiveStepIndex());
		$wizard->saveState();
		$this->assertTrue($wizard->bubbleEvent($this, $this->command('nextstep')));
		$this->assertSame(2, $wizard->getActiveStepIndex());
		$wizard->saveState();
		$this->assertSame([0, 1, 2], $this->history($wizard));

		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_PREVIOUS)));
		$this->assertSame(1, $wizard->getActiveStepIndex());
		$this->assertSame([0], $this->history($wizard));
		$this->assertSame([
			'NextButtonClick:0>1', 'ActiveStepChanged',
			'NextButtonClick:1>2', 'ActiveStepChanged',
			'PreviousButtonClick:2>1', 'ActiveStepChanged',
		], $this->events);
	}

	public function testNextOnTheLastStartStepStays()
	{
		$wizard = $this->createWizard(['a']);
		$wizard->getWizardSteps()->itemAt(0)->setStepType(TWizardStepType::Start);
		$this->recordEvents($wizard);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT)));
		$this->assertSame(['NextButtonClick:0>0'], $this->events);
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testNextOnAFinishStepThrows()
	{
		$wizard = $this->createWizard(['a']);
		$this->expectException(TInvalidDataValueException::class);
		$wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT));
	}

	public function testPreviousOnAStartStepThrows()
	{
		$wizard = $this->createWizard();
		$this->expectException(TInvalidDataValueException::class);
		$wizard->bubbleEvent($this, $this->command(TWizard::CMD_PREVIOUS));
	}

	public function testPreviousWithoutHistoryStays()
	{
		$wizard = $this->createWizard();
		$wizard->setActiveStepIndex(1);
		$this->recordEvents($wizard);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_PREVIOUS)));
		$this->assertSame(['PreviousButtonClick:1>1'], $this->events);
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	public function testPreviousToAStepThatDisallowsReturn()
	{
		$wizard = $this->createWizard();
		$wizard->saveState();
		$wizard->setActiveStepIndex(1);
		$wizard->saveState();
		$wizard->getWizardSteps()->itemAt(0)->setAllowReturn(false);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_PREVIOUS)));
		$this->assertSame(1, $wizard->getActiveStepIndex());
		$this->assertSame([0, 1], $this->history($wizard));
	}

	public function testComplete()
	{
		$wizard = $this->createWizard(['a', 'b']);
		$wizard->addParsedObject(new TCompleteWizardStep());
		$wizard->setActiveStepIndex(1);
		$this->recordEvents($wizard);
		$response = $this->withResponse(fn () => $this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_COMPLETE))));
		$this->assertSame(['CompleteButtonClick:1>2', 'ActiveStepChanged'], $this->events);
		$this->assertSame(2, $wizard->getActiveStepIndex());
		$this->assertSame([], $response->redirects);
	}

	public function testCompleteOnTheLastStepRedirects()
	{
		$wizard = $this->createWizard(['a']);
		$wizard->setFinishDestinationUrl('/done');
		$this->recordEvents($wizard);
		$response = $this->withResponse(fn () => $wizard->bubbleEvent($this, $this->command(TWizard::CMD_COMPLETE)));
		$this->assertSame(['CompleteButtonClick:0>0'], $this->events);
		$this->assertSame(['/done'], $response->redirects);
	}

	public function testCompleteOnAStartStepThrows()
	{
		$wizard = $this->createWizard();
		$this->expectException(TInvalidDataValueException::class);
		$wizard->bubbleEvent($this, $this->command(TWizard::CMD_COMPLETE));
	}

	public function testCancel()
	{
		$wizard = $this->createWizard();
		$this->recordEvents($wizard);
		$response = $this->withResponse(fn () => $this->assertTrue($wizard->bubbleEvent($this, $this->command('cancel'))));
		$this->assertSame(['CancelButtonClick'], $this->events);
		$this->assertSame([], $response->redirects);

		$wizard->setCancelDestinationUrl('/home');
		$response = $this->withResponse(fn () => $wizard->bubbleEvent($this, $this->command(TWizard::CMD_CANCEL)));
		$this->assertSame(['/home'], $response->redirects);
	}

	public function testNavigationCancelledByTheHandlerRestoresTheStep()
	{
		$wizard = $this->createWizard();
		$wizard->attachEventHandler('OnNextButtonClick', fn ($sender, $param) => $param->setCancelNavigation(true));
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT)));
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}

	public function testNavigationCancelledByFailedValidation()
	{
		$wizard = $this->createWizard();
		$wizard->getPage()->valid = false;
		$button = new TButton();
		$this->assertTrue($wizard->bubbleEvent($button, $this->command(TWizard::CMD_NEXT)));
		$this->assertSame(0, $wizard->getActiveStepIndex());

		$button->setCausesValidation(false);
		$wizard->bubbleEvent($button, $this->command(TWizard::CMD_NEXT));
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	public function testNavigationWhenTheHandlerSetsTheStep()
	{
		$wizard = $this->createWizard();
		$wizard->attachEventHandler('OnNextButtonClick', fn ($sender, $param) => $sender->setActiveStepIndex(2));
		$wizard->bubbleEvent($this, $this->command(TWizard::CMD_NEXT));
		$this->assertSame(2, $wizard->getActiveStepIndex());
	}

	public function testMoveToByIndex()
	{
		$wizard = $this->createWizard();
		$this->recordEvents($wizard);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, '2')));
		$this->assertSame(['ActiveStepChanged'], $this->events);
		$this->assertSame(2, $wizard->getActiveStepIndex());
	}

	public function testMoveToByStepId()
	{
		$wizard = $this->createWizard();
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, 'b')));
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	public function testMoveToAnUnknownStepIdThrows()
	{
		$wizard = $this->createWizard();
		$this->expectException(TConfigurationException::class);
		$wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, 'missing'));
	}

	public function testMoveToCancelledByTheSideBarHandler()
	{
		$wizard = $this->createWizard();
		$wizard->setActiveStepIndex(1);
		PradoUnit::setProp($wizard, '_cancelNavigation', true);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, '2')));
		$this->assertSame(1, $wizard->getActiveStepIndex());
	}

	public function testMoveToByStepIdCancelledRestoresTheCurrentStep()
	{
		$wizard = $this->createWizard();
		PradoUnit::setProp($wizard, '_cancelNavigation', true);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, 'c')));
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}
}
