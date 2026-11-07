<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListBox;
use Prado\Web\UI\WebControls\TListControlValidator;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TListSelectionMode;
use Prado\Web\UI\WebControls\TTextBox;

class TListControlValidatorTest extends TestCase
{
	private function createPageValidator(array $selected = [], array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D']): array
	{
		$page = new TPage();
		$form = new TForm();
		$form->setID('Form');
		$page->getControls()->add($form);
		$page->setForm($form);

		$list = new TCheckBoxList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$list->setSelectedValues($selected);
		$page->getControls()->add($list);

		$validator = new TListControlValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('List');
		$page->getControls()->add($validator);
		return [$validator, $list];
	}

	private function isValid(TListControlValidator $validator): bool
	{
		return PradoUnit::invoke($validator, 'evaluateIsValid');
	}

	// ================================================================================
	// Basics
	// ================================================================================

	// ================================================================================
	// Properties
	// ================================================================================

	// ================================================================================
	// evaluateIsValid
	// ================================================================================

	public function testRequiredSelectionZero()
	{
		$items = ['0' => 'Zero', '1' => 'One'];
		[$validator] = $this->createPageValidator([], $items);
		$validator->setRequiredSelections('0');
		$this->assertFalse($this->isValid($validator));

		[$validator] = $this->createPageValidator(['1'], $items);
		$validator->setRequiredSelections('0');
		$this->assertFalse($this->isValid($validator));

		[$validator] = $this->createPageValidator(['0'], $items);
		$validator->setRequiredSelections('0');
		$this->assertTrue($this->isValid($validator));
	}

	public function testRequiredSelectionsMatchValuesExactly()
	{
		foreach ([['1e1', '10'], ['1', '01'], ['1', '1.0'], ['1', ' 1']] as [$required, $selected]) {
			[$validator] = $this->createPageValidator([$selected], [$required => 'R', $selected => 'S']);
			$validator->setRequiredSelections($required);
			$this->assertFalse($this->isValid($validator), "$required matched $selected");
		}
	}

		public function testRequiredSelectionsFewerSelectedThanRequired()
	{
		[$validator] = $this->createPageValidator(['a']);
		$validator->setRequiredSelections('a,b');
		$this->assertFalse($this->isValid($validator));
	}

	// ================================================================================
	// Client script options
	// ================================================================================

}
