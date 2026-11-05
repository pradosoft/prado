<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\WebControls\TLabel;

/**
 * The content methods of the callback client (setText() sends text and update() sends
 * HTML) and the browser methods (notify(), the app badge, the document title, vibrate()).
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

	public function testNotifySendsTheTitleAndOptions()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->notify('Order shipped', ['body' => 'Today', 'tag' => 'order-1']);
		$this->assertSame(
			[['Prado.Notification.show' => ['Order shipped', ['body' => 'Today', 'tag' => 'order-1']]]],
			$page->getClientFunctions()
		);
	}

	public function testNotifySendsTheClickTargetAndParameter()
	{
		$page = new TTestCallbackPage();
		$callback = new TCallback();
		$callback->setID('onOpen');
		$page->getControls()->add($callback);
		$page->getCallbackClient()->notify('Hi', [], $callback, ['order' => 7]);
		$page->getCallbackClient()->notify('Hi', ['body' => 'b'], 'ctl0$other');
		$this->assertSame(
			[
				['Prado.Notification.show' => ['Hi', ['EventTarget' => $callback->getUniqueID(), 'CallbackParameter' => ['order' => 7]]]],
				['Prado.Notification.show' => ['Hi', ['body' => 'b', 'EventTarget' => 'ctl0$other']]],
			],
			$page->getClientFunctions()
		);
	}

	public function testAppBadgeSendsTheCountOrNullAndClears()
	{
		$page = new TTestCallbackPage();
		$client = $page->getCallbackClient();
		$client->setAppBadge('3');
		$client->setAppBadge();
		$client->setAppBadge(0);
		$client->clearAppBadge();
		$this->assertSame(
			[
				['Prado.Browser.setAppBadge' => [3]],
				['Prado.Browser.setAppBadge' => [null]],
				['Prado.Browser.setAppBadge' => [0]],
				['Prado.Browser.clearAppBadge' => []],
			],
			$page->getClientFunctions()
		);
	}

	public function testAppBadgeRejectsANegativeCount()
	{
		$page = new TTestCallbackPage();
		$this->expectException(\InvalidArgumentException::class);
		$page->getCallbackClient()->setAppBadge(-1);
	}

	public function testSetDocumentTitleSendsTheTitle()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->setDocumentTitle(42);
		$this->assertSame([['Prado.Browser.setTitle' => ['42']]], $page->getClientFunctions());
	}

	public function testVibrateSendsAnIntegerOrAPattern()
	{
		$page = new TTestCallbackPage();
		$page->getCallbackClient()->vibrate('200');
		$page->getCallbackClient()->vibrate([100, '50', 100]);
		$page->getCallbackClient()->vibrate(0);
		$this->assertSame(
			[
				['Prado.Browser.vibrate' => [200]],
				['Prado.Browser.vibrate' => [[100, 50, 100]]],
				['Prado.Browser.vibrate' => [0]],
			],
			$page->getClientFunctions()
		);
	}
}
