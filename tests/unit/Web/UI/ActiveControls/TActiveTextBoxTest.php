<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TTextBoxMode;
use PHPUnit\Framework\TestCase;

class TActiveTextBoxTest extends TestCase
{
	private function createLoadedTextBox(bool $callback = true): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$textbox = new TActiveTextBox();
		$textbox->setID('City');
		$list = new TActiveSuggestionList();
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

	public function testSetSuggestionListSetsClientAttribute()
	{
		[$page, $textbox, $list] = $this->createLoadedTextBox();
		$textbox->setSuggestionList('Cities');
		$this->assertSame(
			[['Prado.Element.setAttribute' => ['City', 'list', $list->getClientID()]]],
			$page->getClientFunctions()
		);
	}

	public function testClearSuggestionListRemovesClientAttribute()
	{
		[$page, $textbox] = $this->createLoadedTextBox();
		$textbox->setSuggestionList('Cities');
		$textbox->setSuggestionList('');
		$this->assertSame(
			['Prado.Element.removeAttribute' => ['City', 'list']],
			$page->getClientFunctions()[1]
		);
	}

	public function testPasswordModeRemovesClientAttribute()
	{
		[$page, $textbox] = $this->createLoadedTextBox();
		$textbox->setTextMode(TTextBoxMode::Password);
		$textbox->setSuggestionList('Cities');
		$this->assertSame(
			[['Prado.Element.removeAttribute' => ['City', 'list']]],
			$page->getClientFunctions()
		);
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
