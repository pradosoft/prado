<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiResizable;

/**
 * Unit tests for {@see TJuiResizable}.
 */
class TJuiResizableTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiResizable::class;
	}

	protected function widget(): string
	{
		return 'resizable';
	}

	protected function events(): array
	{
		return ['create', 'resize', 'start', 'stop'];
	}

	protected function option(): array
	{
		return ['minWidth', '20', '20'];
	}
}
