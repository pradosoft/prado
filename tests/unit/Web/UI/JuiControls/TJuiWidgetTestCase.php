<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\JuiControls\IJuiOptions;
use Prado\Web\UI\JuiControls\TJuiControlAdapter;
use Prado\Web\UI\JuiControls\TJuiControlOptions;
use Prado\Web\UI\JuiControls\TJuiEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;

/**
 * TJuiWidgetTestCase tests what every jQuery UI widget control shares: its widget name and
 * element, its options in view state, its events, the script that creates the widget,
 * and the callback that raises an event.
 *
 * A subclass names the control class, its widget, its events, an option to set, and how
 * the option is encoded. {@see expectedInitOptions()} gives the encoded options of the
 * script that creates the widget. The control has the ID `Widget` in the form of a
 * {@see TTestLifecyclePage}.
 */
abstract class TJuiWidgetTestCase extends TestCase
{
	use TPageLifecycleTrait;

	/**
	 * @return string the class of the control under test
	 */
	abstract protected function controlClass(): string;

	/**
	 * @return string the jQuery UI widget method
	 */
	abstract protected function widget(): string;

	/**
	 * @return string[] the jQuery UI events the control raises, each with its `on` method
	 */
	abstract protected function events(): array;

	/**
	 * @return string[] the jQuery UI events the control lists as valid
	 */
	protected function validEvents(): array
	{
		return $this->events();
	}

	/**
	 * @return array{0: string, 1: string, 2: string} an option name, its template value, and its JavaScript encoding
	 */
	abstract protected function option(): array;

	/**
	 * @return string the suffix of the widget element ID after the control's client ID
	 */
	protected function widgetIDSuffix(): string
	{
		return '';
	}

	/**
	 * @param string $name the option set
	 * @param string $encoded the JavaScript encoding of its value
	 * @param string $event the event with a handler
	 * @return string the encoded options of the script that creates the widget
	 */
	protected function expectedInitOptions(string $name, string $encoded, string $event): string
	{
		return "{'" . $name . "':" . $encoded . ",'" . $event . "':" . $this->eventCallback($event) . '}';
	}

	/**
	 * @param string $event
	 * @return string the JavaScript function that raises the callback for the event
	 */
	protected function eventCallback(string $event): string
	{
		return 'function( event, ui ) { Prado.JuiCallback("Widget", "' . $event . '", event, ui, this); }';
	}

	protected function setUp(): void
	{
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	/**
	 * @return IJuiOptions&TControl a new control of the class under test
	 */
	protected function newControl(): TControl
	{
		$class = $this->controlClass();
		$control = new $class();
		$control->setID('Widget');
		return $control;
	}

	/**
	 * @param ?callable $configure configures the control on every run, receives the control and the page
	 * @return TTestLifecyclePage a page with the control `Widget` in its form
	 */
	protected function newPage(?callable $configure = null): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($configure) {
			$page->getControls()->insertAt(0, new THead());
			$control = $this->newControl();
			$form->getControls()->add($control);
			if ($configure) {
				$configure($control, $page);
			}
		};
		return $page;
	}

	/**
	 * @param array $parts the callback response parts, from {@see getCallbackParts()}
	 * @return array the arguments of each `Prado.Element.replace` client function in the parts
	 */
	protected function replaceActions(array $parts): array
	{
		$actions = json_decode($parts[TActivePageAdapter::CALLBACK_ACTION_HEADER] ?? '[]', true);
		return array_values(array_map(
			fn ($action) => $action['Prado.Element.replace'],
			array_filter($actions, fn ($action) => isset($action['Prado.Element.replace']))
		));
	}

	public function testWidgetAndAdapter(): void
	{
		$control = $this->newControl();
		$this->assertInstanceOf(IJuiOptions::class, $control);
		$this->assertInstanceOf(TJuiControlAdapter::class, $control->getAdapter());
		$this->assertSame($this->widget(), $control->getWidget());
		$this->assertSame($control->getClientID() . $this->widgetIDSuffix(), $control->getWidgetID());
		$this->assertSame($this->validEvents(), $control->getValidEvents());
	}

	public function testOptionsAreKeptInViewState(): void
	{
		$control = $this->newControl();
		$options = $control->getOptions();
		$this->assertInstanceOf(TJuiControlOptions::class, $options);
		$this->assertSame($options, $control->getOptions());
		$this->assertSame($options, $control->getViewState('JuiOptions'));
	}

	public function testEveryValidOptionIsAccepted(): void
	{
		$control = $this->newControl();
		foreach ($control->getValidOptions() as $option) {
			$control->getOptions()->$option = 'v';
		}
		$this->assertSame(array_fill_keys($control->getValidOptions(), 'v'), $control->getOptions()->toArray());
	}

	public function testEachEventMethodRaisesItsEvent(): void
	{
		$control = $this->newControl();
		$raised = [];
		foreach ($this->events() as $event) {
			$control->attachEventHandler('On' . $event, function ($sender, $param) use (&$raised, $event, $control) {
				$this->assertSame($control, $sender);
				$raised[] = [$event, $param];
			});
		}
		$param = new TJuiEventParameter(null, null);
		foreach ($this->events() as $event) {
			$control->{'on' . $event}($param);
		}
		$this->assertSame(array_map(fn ($event) => [$event, $param], $this->events()), $raised);
	}

	public function testRenderRegistersTheWidgetScript(): void
	{
		[$name, $value, $encoded] = $this->option();
		$event = $this->events()[0];
		$output = $this->runPage($this->newPage(function ($control) use ($name, $value, $event) {
			$control->getOptions()->$name = $value;
			$control->attachEventHandler('On' . $event, fn () => null);
		}));
		$this->assertStringContainsString('id="Widget"', $output);
		$this->assertStringContainsString(
			"jQuery('#Widget" . $this->widgetIDSuffix() . "')." . $this->widget() . '(' . $this->expectedInitOptions($name, $encoded, $event) . ');',
			$output
		);
		$this->assertStringContainsString('jquery-ui.css', $output);
		$this->assertStringContainsString('jquery-ui.js', $output);
	}

	public function testCallbackRaisesTheEventWithTheUiParameter(): void
	{
		$event = $this->events()[0];
		$configure = function ($control, $page) use ($event) {
			$control->attachEventHandler('On' . $event, function ($sender, $param) use ($page) {
				$page->events[] = $param;
			});
		};
		$this->runPage($this->newPage($configure));
		$page = $this->newPage($configure);
		$this->runCallback($page, 'Widget', ['event' => $event, 'ui' => ['n' => 1]]);
		$this->assertCount(1, $page->events);
		$this->assertInstanceOf(TJuiEventParameter::class, $page->events[0]);
		$this->assertEquals((object) ['n' => 1], $page->events[0]->getCallbackParameter());
	}

	public function testOptionChangedInACallbackUpdatesTheWidget(): void
	{
		[$name, $value, $encoded] = $this->option();
		$event = $this->events()[0];
		$configure = function ($control) use ($name, $value, $event) {
			$control->getOptions();
			$control->attachEventHandler('On' . $event, function ($sender) use ($name, $value) {
				$sender->getOptions()->$name = $value;
			});
		};
		$this->runPage($this->newPage($configure));
		$this->runCallback($this->newPage($configure), 'Widget', ['event' => $event]);
		$script = implode("\n", $this->getCallbackParts());
		$this->assertStringContainsString(
			"jQuery('#Widget" . $this->widgetIDSuffix() . "')." . $this->widget() . "('option', { " . $name . ': ' . $encoded . ' });',
			$script
		);
	}
}
