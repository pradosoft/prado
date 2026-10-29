<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Web\UI\WebControls\TLabel;

/**
 * The content methods of the callback client: setText() sends text and update() sends HTML.
 */
class TCallbackClientScriptTest extends TestCase
{
	public function testSetTextSendsTheTextMethod()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->setText('Target', '<b>a & b</b>');
		$this->assertSame(
			[['Prado.Element.j' => ['Target', 'text', ['<b>a & b</b>']]]],
			$page->getClientFunctions()
		);
	}

	public function testSetTextTakesAControlAndCastsTheText()
	{
		$page = new TTestCallbackPage();
		$label = new TLabel();
		$label->setID('Target');
		$page->getControls()->add($label);
		$page->getCallbackClient()->setText($label, 42);
		$this->assertSame(
			[['Prado.Element.j' => [$label->getClientID(), 'text', ['42']]]],
			$page->getClientFunctions()
		);
	}

	public function testUpdateSendsTheHtmlMethod()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->update('Target', '<b>a</b>');
		$this->assertSame(
			[['Prado.Element.j' => ['Target', 'html', ['<b>a</b>']]]],
			$page->getClientFunctions()
		);
	}

	public function testReplaceContentSendsNoMorphArgumentByDefault()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->replaceContent('Target', '<b>a</b>');
		$this->assertSame(
			[['Prado.Element.replace' => ['Target', '<b>a</b>', null, true]]],
			$page->getClientFunctions()
		);
	}

	public function testReplaceContentSendsTheMorphArgument()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->replaceContent('Target', '<b>a</b>', false, true);
		$this->assertSame(
			[['Prado.Element.replace' => ['Target', '<b>a</b>', null, false, true]]],
			$page->getClientFunctions()
		);
	}
}
