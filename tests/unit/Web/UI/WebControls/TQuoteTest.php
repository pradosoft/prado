<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TQuote;

class TQuoteTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TQuote();
	}

	protected function defaultTagName(): string
	{
		return 'q';
	}

	public function testCiteDefaultsToEmpty()
	{
		$control = new TQuote();
		$this->assertSame('', $control->getCite());
		$this->assertSame('<q></q>', $this->render($control));
	}

	public function testCiteRendersTrimmed()
	{
		$control = new TQuote();
		$control->setCite(' https://example.com/source?q="a" ');
		$this->assertSame('https://example.com/source?q="a"', $control->getCite());
		$this->assertSame('<q cite="https://example.com/source?q=&quot;a&quot;"></q>', $this->render($control));
	}
}
