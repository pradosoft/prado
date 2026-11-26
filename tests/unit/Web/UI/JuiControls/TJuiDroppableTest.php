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
