<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\JuiControls\TJuiCallbackPageStateTracker;

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
}
