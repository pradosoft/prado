<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TInPlaceTextBox;

class TInPlaceTextBoxTest extends TestCase
{
	public function testPostBackOptionsCarryMaxLengthInEveryTextMode()
	{
		$control = new TInPlaceTextBox();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('', $options['MaxLength']);
		$control->setMaxLength(20);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame(20, $options['MaxLength']);
		$control->setTextMode('MultiLine');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame(20, $options['MaxLength']);
	}
}
