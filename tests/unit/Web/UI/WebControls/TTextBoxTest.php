<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
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

	public static function suggestionListClassProvider(): array
	{
		return [
			'TSuggestionList' => [TSuggestionList::class],
			'TActiveSuggestionList' => [TActiveSuggestionList::class],
		];
	}

	private function createPageWithTextBox(string $listClass = TSuggestionList::class): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$textbox = new TTextBox();
		$textbox->setID('City');
		$textbox->setEnableClientScript(false);
		$list = new $listClass();
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

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testListAttributeRendersClientId(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox));
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testListAttributeRendersForInputModes(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::SingleLine, TTextBoxMode::Email, TTextBoxMode::Number, TTextBoxMode::Range, TTextBoxMode::Search, TTextBoxMode::Url] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox), $mode);
		}
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testNoListAttributeForPasswordAndMultiLine(string $listClass)
	{
		[, $textbox] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::Password, TTextBoxMode::MultiLine] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringNotContainsString('list=', $this->render($textbox), $mode);
		}
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testNoListAttributeWhenListNotVisible(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
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

	// --- Constraints ---

	private function constrainedTextBox(string $mode): TTextBox
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode($mode);
		$textbox->setMin(' 1 ');
		$textbox->setMax('9');
		$textbox->setStep('2');
		$textbox->setPattern('[a-z]+');
		$textbox->setPlaceholder('Hint');
		$textbox->setRequired('true');
		$textbox->setMultiple(true);
		return $textbox;
	}

	public function testConstraintDefaults()
	{
		$textbox = new TTextBox();
		foreach (['Min', 'Max', 'Step', 'Pattern', 'Placeholder'] as $name) {
			$this->assertSame('', $textbox->{'get' . $name}(), $name);
		}
		$this->assertFalse($textbox->getRequired());
		$this->assertFalse($textbox->getMultiple());
		[, $textbox] = $this->createPageWithTextBox();
		$output = $this->render($textbox);
		foreach (['min=', 'max=', 'step=', 'pattern=', 'placeholder=', 'required', 'multiple'] as $attribute) {
			$this->assertStringNotContainsString($attribute, $output);
		}
	}

	public static function constraintModeProvider(): array
	{
		// mode => [min/max/step, pattern, multiple, placeholder, required]
		return [
			'SingleLine' => [TTextBoxMode::SingleLine, false, true, false, true, true],
			'MultiLine' => [TTextBoxMode::MultiLine, false, false, false, true, true],
			'Password' => [TTextBoxMode::Password, false, true, false, true, true],
			'Color' => [TTextBoxMode::Color, false, false, false, false, false],
			'Date' => [TTextBoxMode::Date, true, false, false, false, true],
			'DatetimeLocal' => [TTextBoxMode::DatetimeLocal, true, false, false, false, true],
			'Email' => [TTextBoxMode::Email, false, true, true, true, true],
			'Month' => [TTextBoxMode::Month, true, false, false, false, true],
			'Number' => [TTextBoxMode::Number, true, false, false, true, true],
			'Range' => [TTextBoxMode::Range, true, false, false, false, false],
			'Search' => [TTextBoxMode::Search, false, true, false, true, true],
			'Tel' => [TTextBoxMode::Tel, false, true, false, true, true],
			'Time' => [TTextBoxMode::Time, true, false, false, false, true],
			'Url' => [TTextBoxMode::Url, false, true, false, true, true],
			'Week' => [TTextBoxMode::Week, true, false, false, false, true],
		];
	}

	/**
	 * @dataProvider constraintModeProvider
	 */
	public function testConstraintAttributesRenderForTheirTextModes(string $mode, bool $ranged, bool $pattern, bool $multiple, bool $placeholder, bool $required)
	{
		$output = $this->render($this->constrainedTextBox($mode));
		$check = fn (bool $expected, string $needle) => $expected
			? $this->assertStringContainsString($needle, $output, "$mode $needle")
			: $this->assertStringNotContainsString($needle, $output, "$mode $needle");
		$check($ranged, 'min="1" max="9" step="2"');
		$check($pattern, 'pattern="[a-z]+"');
		$check($multiple, 'multiple="multiple"');
		$check($placeholder, 'placeholder="Hint"');
		$check($required, 'required="required"');
	}

	public function testStepAny()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::Number);
		$textbox->setStep('any');
		$this->assertStringContainsString('step="any"', $this->render($textbox));
	}
}
