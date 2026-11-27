<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TLiteral;

class TLiteralTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersTheTextAsIsOrEncoded()
	{
		$literal = new TLiteral();
		$this->assertSame('', $literal->getText());
		$literal->setText('<b>a</b>');
		$this->assertSame('<b>a</b>', $this->render($literal));
		$literal->setEncode(true);
		$this->assertSame('&lt;b&gt;a&lt;/b&gt;', $this->render($literal));
	}

	public function testDataIsTheText()
	{
		$literal = new TLiteral();
		$literal->setData('value');
		$this->assertSame('value', $literal->getText());
		$this->assertSame('value', $literal->getData());
	}

	public function testWithoutTextRendersTheChildren()
	{
		$literal = new TLiteral();
		$literal->getControls()->add('child');
		$this->assertSame('child', $this->render($literal));
	}
}
