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
	private function createPageValidator(array $selected = []): array
	{
		$page = new TPage();
		$form = new TForm();
		$form->setID('Form');
		$page->getControls()->add($form);
		$page->setForm($form);

		$list = new TCheckBoxList();
		$list->setID('List');
		foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $value => $text) {
			$list->getItems()->add(new TListItem($text, $value));
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

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, new TListControlValidator());
	}

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TListControlValidator', PradoUnit::invoke(new TListControlValidator(), 'getClientClassName'));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testMinSelection()
	{
		$validator = new TListControlValidator();
		$this->assertSame(-1, $validator->getMinSelection());
		$validator->setMinSelection('2');
		$this->assertSame(2, $validator->getMinSelection());
		$validator->setMinSelection(0);
		$this->assertSame(0, $validator->getMinSelection());
		$validator->setMinSelection(-5);
		$this->assertSame(-1, $validator->getMinSelection());
	}

	public function testMaxSelection()
	{
		$validator = new TListControlValidator();
		$this->assertSame(-1, $validator->getMaxSelection());
		$validator->setMaxSelection('3');
		$this->assertSame(3, $validator->getMaxSelection());
		$validator->setMaxSelection(-2);
		$this->assertSame(-1, $validator->getMaxSelection());
	}

	public function testRequiredSelections()
	{
		$validator = new TListControlValidator();
		$this->assertSame('', $validator->getRequiredSelections());
		$validator->setRequiredSelections('a, b');
		$this->assertSame('a, b', $validator->getRequiredSelections());
	}

	public function testGetRequiredValues()
	{
		$validator = new TListControlValidator();
		$this->assertSame([], PradoUnit::invoke($validator, 'getRequiredValues'));
		$validator->setRequiredSelections('a,b,  c');
		$this->assertSame(['a', 'b', 'c'], PradoUnit::invoke($validator, 'getRequiredValues'));
	}

	public function testGetSelection()
	{
		[$validator, $list] = $this->createPageValidator(['d', 'b']);
		$this->assertSame(['b', 'd'], PradoUnit::invoke($validator, 'getSelection', $list));
	}

	// ================================================================================
	// evaluateIsValid
	// ================================================================================

	public function testValidWithoutConstraints()
	{
		[$validator] = $this->createPageValidator();
		$this->assertTrue($this->isValid($validator));
	}

	public function testMinSelectionConstraint()
	{
		[$validator] = $this->createPageValidator(['a']);
		$validator->setMinSelection(2);
		$this->assertFalse($this->isValid($validator));

		[$validator] = $this->createPageValidator(['a', 'b']);
		$validator->setMinSelection(2);
		$this->assertTrue($this->isValid($validator));
	}

	public function testMaxSelectionConstraint()
	{
		[$validator] = $this->createPageValidator(['a', 'b', 'c']);
		$validator->setMaxSelection(2);
		$this->assertFalse($this->isValid($validator));

		[$validator] = $this->createPageValidator(['a', 'b']);
		$validator->setMaxSelection(2);
		$this->assertTrue($this->isValid($validator));
	}

	public function testRequiredSelectionsPresent()
	{
		[$validator] = $this->createPageValidator(['a', 'c', 'd']);
		$validator->setRequiredSelections('a, c');
		$this->assertTrue($this->isValid($validator));
	}

	public function testRequiredSelectionsMissingValue()
	{
		[$validator] = $this->createPageValidator(['a', 'b']);
		$validator->setRequiredSelections('a, c');
		$this->assertFalse($this->isValid($validator));
	}

	public function testRequiredSelectionsFewerSelectedThanRequired()
	{
		[$validator] = $this->createPageValidator(['a']);
		$validator->setRequiredSelections('a,b');
		$this->assertFalse($this->isValid($validator));
	}

	public function testRequiredAndMinSelection()
	{
		[$validator] = $this->createPageValidator(['a']);
		$validator->setRequiredSelections('a');
		$validator->setMinSelection(2);
		$this->assertFalse($this->isValid($validator));

		[$validator] = $this->createPageValidator(['a', 'd']);
		$validator->setRequiredSelections('a');
		$validator->setMinSelection(2);
		$this->assertTrue($this->isValid($validator));
	}

	public function testValidatesListBox()
	{
		$page = new TPage();
		$list = new TListBox();
		$list->setID('Box');
		$list->setSelectionMode(TListSelectionMode::Multiple);
		$list->getItems()->add(new TListItem('A', 'a'));
		$list->getItems()->add(new TListItem('B', 'b'));
		$list->setSelectedIndices([0, 1]);
		$page->getControls()->add($list);
		$validator = new TListControlValidator();
		$validator->setControlToValidate('Box');
		$page->getControls()->add($validator);

		$validator->setMaxSelection(1);
		$this->assertFalse($this->isValid($validator));
		$validator->setMaxSelection(2);
		$this->assertTrue($this->isValid($validator));
	}

	// ================================================================================
	// Client script options
	// ================================================================================

	public function testClientScriptOptionsDefaults()
	{
		[$validator] = $this->createPageValidator();
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('Valid', $options['ID']);
		$this->assertSame('Form', $options['FormID']);
		$this->assertSame('List', $options['ControlToValidate']);
		$this->assertSame(4, $options['TotalItems']);
		$this->assertArrayNotHasKey('Min', $options);
		$this->assertArrayNotHasKey('Max', $options);
		$this->assertArrayNotHasKey('Required', $options);
	}

	public function testClientScriptOptionsConstraints()
	{
		[$validator] = $this->createPageValidator();
		$validator->setMinSelection(1);
		$validator->setMaxSelection(3);
		$validator->setRequiredSelections('a,b');
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame(1, $options['Min']);
		$this->assertSame(3, $options['Max']);
		$this->assertSame('a,b', $options['Required']);
		$this->assertSame(4, $options['TotalItems']);
	}

	public function testClientScriptOptionsRejectNonListControl()
	{
		$page = new TPage();
		$form = new TForm();
		$page->getControls()->add($form);
		$page->setForm($form);
		$box = new TTextBox();
		$box->setID('Text');
		$page->getControls()->add($box);
		$validator = new TListControlValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Text');
		$page->getControls()->add($validator);
		try {
			PradoUnit::invoke($validator, 'getClientScriptOptions');
			$this->fail('TConfigurationException expected.');
		} catch (TConfigurationException $e) {
			$this->assertSame('listcontrolvalidator_invalid_control', $e->getErrorCode());
		}
	}
}
