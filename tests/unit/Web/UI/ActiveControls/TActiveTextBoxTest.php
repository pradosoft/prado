<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TSuggestionList;
use Prado\Web\UI\WebControls\TTextBoxMode;
use PHPUnit\Framework\TestCase;

class TActiveTextBoxTest extends TestCase
{
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
}
