<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TBlockQuote;

class TBlockQuoteTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TBlockQuote();
	}

	protected function defaultTagName(): string
	{
		return 'blockquote';
	}

	public function testCiteDefaultsToEmpty()
	{
		$control = new TBlockQuote();
		$this->assertSame('', $control->getCite());
		$this->assertSame('<blockquote></blockquote>', $this->render($control));
	}

	public function testCiteRendersTrimmed()
	{
		$control = new TBlockQuote();
		$control->setCite(' https://example.com/source?q="a" ');
		$this->assertSame('https://example.com/source?q="a"', $control->getCite());
		$this->assertSame('<blockquote cite="https://example.com/source?q=&quot;a&quot;"></blockquote>', $this->render($control));
	}
}
