<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TSuggestionList;
use Prado\Web\UI\WebControls\TTextBoxMode;
use PHPUnit\Framework\TestCase;

class TActiveTextBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	public static function suggestionListClassProvider(): array
	{
		return [
			'TSuggestionList' => [TSuggestionList::class],
			'TActiveSuggestionList' => [TActiveSuggestionList::class],
		];
	}

	private function createLoadedTextBox(bool $callback = true, string $listClass = TActiveSuggestionList::class): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$textbox = new TActiveTextBox();
		$textbox->setID('City');
		$list = new $listClass();
		$list->setID('Cities');
		$page->getControls()->add($textbox);
		$page->getControls()->add($list);
		PradoUnit::invoke($textbox, 'setControlStage', TControl::CS_LOADED);
		return [$page, $textbox, $list];
	}

	// --- SuggestionList ---

	public function testSetSuggestionListWithoutPage()
	{
		$textbox = new TActiveTextBox();
		$textbox->setSuggestionList('Cities');
		$this->assertSame('Cities', $textbox->getSuggestionList());
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testSetSuggestionListSetsClientAttribute(string $listClass)
	{
		[$page, $textbox, $list] = $this->createLoadedTextBox(true, $listClass);
		$textbox->setSuggestionList('Cities');
		$this->assertSame(
			[['Prado.Element.setAttribute' => ['City', 'list', $list->getClientID()]]],
			$page->getClientFunctions()
		);
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testClearSuggestionListRemovesClientAttribute(string $listClass)
	{
		[$page, $textbox] = $this->createLoadedTextBox(true, $listClass);
		$textbox->setSuggestionList('Cities');
		$textbox->setSuggestionList('');
		$this->assertSame(
			['Prado.Element.removeAttribute' => ['City', 'list']],
			$page->getClientFunctions()[1]
		);
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testPasswordModeRemovesClientAttribute(string $listClass)
	{
		[$page, $textbox] = $this->createLoadedTextBox(true, $listClass);
		$textbox->setTextMode(TTextBoxMode::Password);
		$textbox->setSuggestionList('Cities');
		$this->assertSame(
			[['Prado.Element.removeAttribute' => ['City', 'list']]],
			$page->getClientFunctions()
		);
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testListAttributeValueResolvesList(string $listClass)
	{
		[, $textbox, $list] = $this->createLoadedTextBox(false, $listClass);
		$textbox->setSuggestionList('Cities');
		$this->assertSame($list->getClientID(), PradoUnit::invoke($textbox, 'getListAttributeValue'));
	}

	public function testSameSuggestionListSendsNothing()
	{
		[$page, $textbox] = $this->createLoadedTextBox();
		$textbox->setSuggestionList('Cities');
		$textbox->setSuggestionList('Cities');
		$this->assertCount(1, $page->getClientFunctions());
	}

	public function testNoCallbackSendsNothing()
	{
		[$page, $textbox] = $this->createLoadedTextBox(false);
		$textbox->setSuggestionList('Cities');
		$this->assertSame([], $page->getClientFunctions());
	}

	// --- Text, callback, and render ---

	private function createTextBox(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$textbox = $page->addControl(new TActiveTextBox(), 'Name', $stage);
		return [$page, $textbox];
	}

	public function testClientSideIsTheActiveControlClientSide()
	{
		$textbox = new TActiveTextBox();
		$this->assertInstanceOf(TCallbackClientSide::class, $textbox->getClientSide());
		$this->assertSame($textbox->getActiveControl()->getClientSide(), $textbox->getClientSide());
	}

	public function testSetTextAfterPostDataSetsTheValueOnce()
	{
		[$page, $textbox] = $this->createTextBox();
		$textbox->setText('Ann');
		$textbox->setText('Ann');
		$this->assertSame([['Prado.Element.j' => ['Name', 'val', ['Ann']]]], $page->getClientFunctions());
	}

	public function testSetTextBeforePostDataOrOutsideACallbackUpdatesNothing()
	{
		[$page, $textbox] = $this->createTextBox(true, TControl::CS_INITIALIZED);
		$textbox->setText('Ann');
		$this->assertSame([], $page->getClientFunctions());

		[$page, $textbox] = $this->createTextBox(false);
		$textbox->setText('Ann');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('Ann', $textbox->getText());
	}

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		[, $textbox] = $this->createTextBox();
		$params = [];
		$textbox->attachEventHandler('OnCallback', function ($sender, $param) use (&$params) {
			$params[] = $param->getCallbackParameter();
		});
		$textbox->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['p'], $params);
	}

	public function testRenderRegistersTheCallbackControl()
	{
		[$page, $textbox] = $this->createTextBox();
		$textbox->setAutoPostBack(true);
		$html = $this->render($textbox);
		$this->assertStringContainsString('id="Name"', $html);
		$this->assertStringNotContainsString('onchange', $html);
		$this->assertCount(1, $page->cs->callbackControls);
		$this->assertSame('Prado.WebUI.TActiveTextBox', $page->cs->callbackControls[0][0]);
	}
}
