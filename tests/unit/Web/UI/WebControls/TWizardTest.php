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

	public function testMoveToByStepIdCancelledRestoresTheCurrentStep()
	{
		$wizard = $this->createWizard();
		PradoUnit::setProp($wizard, '_cancelNavigation', true);
		$this->assertTrue($wizard->bubbleEvent($this, $this->command(TWizard::CMD_MOVETO, 'c')));
		$this->assertSame(0, $wizard->getActiveStepIndex());
	}
}
