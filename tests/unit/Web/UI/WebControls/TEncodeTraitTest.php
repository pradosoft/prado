<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TEncodeTrait;

/**
 * A control with the Encode property.
 */
class TEncodeTraitTestControl extends TControl
{
	use TEncodeTrait;
}

class TEncodeTraitTest extends TestCase
{
	public function testEncodeIsOffByDefaultAndKeptInViewState()
	{
		$control = new TEncodeTraitTestControl();
		$this->assertFalse($control->getEncode());
		$control->setEncode('true');
		$this->assertTrue($control->getEncode());
		$this->assertTrue($control->getViewState('Encode'));
		$control->setEncode(false);
		$this->assertFalse($control->getEncode());
	}

	public function testEncodeTextFollowsEncode()
	{
		$control = new TEncodeTraitTestControl();
		$this->assertSame('<b>a & b</b>', PradoUnit::invoke($control, 'encodeText', '<b>a & b</b>'));
		$control->setEncode(true);
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', PradoUnit::invoke($control, 'encodeText', '<b>a & b</b>'));
	}
}
