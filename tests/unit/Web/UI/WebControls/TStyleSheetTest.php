<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\WebControls\TStyleSheet;

class TStyleSheetTest extends TestCase
{
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		TJavaScript::setScriptNonce(null);
	}

	public function testWithoutChildrenRendersNothing()
	{
		$this->assertSame('', $this->render(new TStyleSheet()));
	}

	public function testRendersChildCssInStyleElement()
	{
		$control = new TStyleSheet();
		$control->getControls()->add('.a { color: red; }');
		$this->assertSame("<style type=\"text/css\">\n/*<![CDATA[*/\n.a { color: red; }\n/*]]>*/\n</style>\n", $this->render($control));
	}

	public function testStyleElementCarriesNonce()
	{
		TJavaScript::setScriptNonce('n0nce');
		$control = new TStyleSheet();
		$control->getControls()->add('.a{}');
		$this->assertStringStartsWith("<style type=\"text/css\" nonce=\"n0nce\">\n/*<![CDATA[*/\n", $this->render($control));
	}
}
