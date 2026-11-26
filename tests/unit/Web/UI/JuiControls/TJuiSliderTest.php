<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiSlider;

/**
 * Unit tests for {@see TJuiSlider}.
 */
class TJuiSliderTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiSlider::class;
	}

	protected function widget(): string
	{
		return 'slider';
	}

	protected function events(): array
	{
		return ['change', 'create', 'slide', 'start', 'stop'];
	}

	protected function option(): array
	{
		return ['orientation', 'vertical', '"vertical"'];
	}
}
