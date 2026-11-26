<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiDraggable;

/**
 * Unit tests for {@see TJuiDraggable}.
 */
class TJuiDraggableTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiDraggable::class;
	}

	protected function widget(): string
	{
		return 'draggable';
	}

	protected function events(): array
	{
		return ['create', 'drag', 'start', 'stop'];
	}

	protected function option(): array
	{
		return ['axis', 'x', '"x"'];
	}
}
