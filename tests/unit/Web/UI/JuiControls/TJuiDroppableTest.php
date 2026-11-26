<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiDroppable;

/**
 * Unit tests for {@see TJuiDroppable}.
 */
class TJuiDroppableTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiDroppable::class;
	}

	protected function widget(): string
	{
		return 'droppable';
	}

	protected function events(): array
	{
		return ['activate', 'create', 'deactivate', 'drop', 'out', 'over'];
	}

	protected function option(): array
	{
		return ['tolerance', 'fit', '"fit"'];
	}

	/**
	 * `callback` is not a droppable event, so a callback naming it does not raise OnCallback.
	 */
	public function testCallbackEventIsNotADroppableEvent(): void
	{
		$configure = function ($control, $page) {
			$control->attachEventHandler('OnCallback', fn () => $page->events[] = 'callback');
		};
		$this->runPage($this->newPage($configure));
		$page = $this->newPage($configure);
		$this->runCallback($page, 'Widget', ['event' => 'callback']);
		$this->assertSame([], $page->events);
	}

	public function testOnCallbackRaisesItsEvent(): void
	{
		$control = $this->newControl();
		$raised = [];
		$control->attachEventHandler('OnCallback', function ($sender, $param) use (&$raised) {
			$raised[] = $param;
		});
		$control->onCallback('p');
		$this->assertSame(['p'], $raised);
	}
}
