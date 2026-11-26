<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiProgressbar;

/**
 * Unit tests for {@see TJuiProgressbar}.
 */
class TJuiProgressbarTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiProgressbar::class;
	}

	protected function widget(): string
	{
		return 'progressbar';
	}

	protected function events(): array
	{
		return ['change', 'complete', 'create'];
	}

	protected function option(): array
	{
		return ['max', '50', '50'];
	}
}
