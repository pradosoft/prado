<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\JuiControls\TJuiCallbackPageStateTracker;
use Prado\Web\UI\JuiControls\TJuiDraggable;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;

/**
 * Control double with a jQuery UI widget and a page whose client script records end scripts.
 */
class TJuiCallbackPageStateTrackerTestControl
{
	/** @var array<string, string> the registered end scripts, by key. */
	public array $endScripts = [];

	public function getViewState(string $name, $default = null): mixed
	{
		return $default;
	}

	public function getWidgetID(): string
	{
		return 'ctl0_dialog';
	}

	public function getWidget(): string
	{
		return 'dialog';
	}

	public function getPage(): object
	{
		return $this;
	}

	public function getClientScript(): object
	{
		return $this;
	}

	public function registerEndScript(string $key, string $script): void
	{
		$this->endScripts[$key] = $script;
	}
}

/**
 * Exposes {@see TJuiCallbackPageStateTracker::updateJuiOptions()}.
 */
class TJuiCallbackPageStateTrackerTestable extends TJuiCallbackPageStateTracker
{
	public function exposedUpdateJuiOptions(array $options): void
	{
		$this->updateJuiOptions($options);
	}
}

/**
 * Unit tests for {@see \Prado\Web\UI\JuiControls\TJuiCallbackPageStateTracker}.
 */
class TJuiCallbackPageStateTrackerTest extends TestCase
{
	use TPageLifecycleTrait;

	protected function setUp(): void
	{
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	public function testStatesToTrackIncludesJuiOptions(): void
	{
		$tracker = new TJuiCallbackPageStateTrackerTestable(new TJuiCallbackPageStateTrackerTestControl());
		$states = (fn () => $this->getStatesToTrack())->call($tracker);
		$this->assertSame('TMapCollectionDiff', $states['JuiOptions'][0]);
		$this->assertSame([$tracker, 'updateJuiOptions'], $states['JuiOptions'][1]);
	}

	/**
	 * Each changed option value is encoded as JavaScript: a string is quoted and
	 * escaped, an array becomes a JavaScript array or object, and a literal is kept.
	 */
	public function testUpdateJuiOptionsEncodesEachValue(): void
	{
		$control = new TJuiCallbackPageStateTrackerTestControl();
		$tracker = new TJuiCallbackPageStateTrackerTestable($control);

		$tracker->exposedUpdateJuiOptions([
			'title' => "It's",
			'empty' => '',
			'modal' => true,
			'width' => 300,
			'position' => ['my' => 'center', 'at' => 'center'],
			'buttons' => [1, 2],
			'open' => new TJavaScriptLiteral('function() {}'),
			'appendTo' => null,
		]);

		$this->assertCount(1, $control->endScripts);
		$this->assertSame(
			"jQuery('#ctl0_dialog').dialog('option', { title: \"It\\u0027s\", empty: \"\", modal: true, width: 300, "
				. "position: {'my':\"center\",'at':\"center\"}, buttons: [1,2], open: function() {}, appendTo: null });",
			reset($control->endScripts)
		);
	}

	public function testUpdateJuiOptionsWithOneOption(): void
	{
		$control = new TJuiCallbackPageStateTrackerTestControl();
		(new TJuiCallbackPageStateTrackerTestable($control))->exposedUpdateJuiOptions(['modal' => false]);
		$this->assertSame(["jQuery('#ctl0_dialog').dialog('option', { modal: false });"], array_values($control->endScripts));
	}

	/**
	 * An array option changed in a callback is sent with the other changed options, and the unchanged ones are not.
	 */
	public function testCallbackSendsTheChangedArrayOption(): void
	{
		$build = function (TTestLifecyclePage $page, TForm $form) {
			$page->getControls()->insertAt(0, new THead());
			$control = new TJuiDraggable();
			$control->setID('Drag');
			$control->getOptions()->cursorAt = ['top' => 1];
			$control->getOptions()->grid = [10, 10];
			$control->attachEventHandler('OnStop', function ($sender) {
				$sender->getOptions()->cursorAt = ['top' => 5];
				$sender->getOptions()->axis = 'x';
			});
			$form->getControls()->add($control);
		};
		$page = new TTestLifecyclePage();
		$page->build = $build;
		$this->runPage($page);
		$page = new TTestLifecyclePage();
		$page->build = $build;
		$this->runCallback($page, 'Drag', ['event' => 'stop']);
		$this->assertStringContainsString(
			"jQuery('#Drag').draggable('option', { cursorAt: {'top':5}, axis: \"x\" });",
			implode("\n", $this->getCallbackParts())
		);
	}
}
