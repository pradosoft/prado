<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TSuggestionList;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TTextBoxMode;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TTextBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageWithTextBox(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$textbox = new TTextBox();
		$textbox->setID('City');
		$textbox->setEnableClientScript(false);
		$list = new TSuggestionList();
		$list->setID('Cities');
		$page->getControls()->add($textbox);
		$page->getControls()->add($list);
		return [$page, $textbox, $list];
	}

	// --- SuggestionList ---

	public function testSuggestionListDefaultsToEmpty()
	{
		$this->assertSame('', (new TTextBox())->getSuggestionList());
	}

	public function testSetSuggestionList()
	{
		$textbox = new TTextBox();
		$textbox->setSuggestionList('Cities');
		$this->assertSame('Cities', $textbox->getSuggestionList());
		$textbox->setSuggestionList('');
		$this->assertSame('', $textbox->getSuggestionList());
	}

	public function testNoListAttributeByDefault()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$this->assertStringNotContainsString('list=', $this->render($textbox));
	}

	public function testListAttributeRendersClientId()
	{
		[, $textbox, $list] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Cities');
		$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox));
	}

	public function testListAttributeRendersForInputModes()
	{
		[, $textbox, $list] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::SingleLine, TTextBoxMode::Email, TTextBoxMode::Number, TTextBoxMode::Range, TTextBoxMode::Search, TTextBoxMode::Url] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox), $mode);
		}
	}

	public function testNoListAttributeForPasswordAndMultiLine()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::Password, TTextBoxMode::MultiLine] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringNotContainsString('list=', $this->render($textbox), $mode);
		}
	}

	public function testNoListAttributeWhenListNotVisible()
	{
		[, $textbox, $list] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Cities');
		$list->setVisible(false);
		$this->assertStringNotContainsString('list=', $this->render($textbox));
	}

	public function testMissingSuggestionListThrows()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->render($textbox);
	}
}
