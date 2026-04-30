<?php

use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TInvalidDataValueException;
use PHPUnit\Framework\TestCase;

class TDropDownListTest extends TestCase
{
	private function invokeFormatDataValue($list, $formatString, $value)
	{
		$method = new ReflectionMethod($list, 'formatDataValue');
		$method->setAccessible(true);
		return $method->invoke($list, $formatString, $value);
	}

	private function getTagName($list)
	{
		$method = new ReflectionMethod($list, 'getTagName');
		$method->setAccessible(true);
		return $method->invoke($list);
	}

	private function getIsMultiSelect($list)
	{
		$method = new ReflectionMethod($list, 'getIsMultiSelect');
		$method->setAccessible(true);
		return $method->invoke($list);
	}

	// ================================================================================
	// Basic Tests
	// ================================================================================

	public function testCreateControl()
	{
		$list = new TDropDownList();
		$this->assertEquals('select', $this->getTagName($list));
		$this->assertFalse($this->getIsMultiSelect($list));
	}

	public function testItemsCollection()
	{
		$list = new TDropDownList();
		$this->assertCount(0, $list->getItems());

		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));
		$this->assertCount(2, $list->getItems());
	}

	public function testSetDataSource()
	{
		$list = new TDropDownList();
		$data = ['a' => 1, 'b' => 2, 'c' => 3];
		$list->setDataSource($data);
		$list->dataBind();

		$this->assertCount(3, $list->getItems());
	}

	public function testDataSourceWithCustomFields()
	{
		$list = new TDropDownList();
		$list->setDataTextField('name');
		$list->setDataValueField('id');
		$data = [
			['id' => 1, 'name' => 'Item 1'],
			['id' => 2, 'name' => 'Item 2'],
		];
		$list->setDataSource($data);
		$list->dataBind();

		$this->assertCount(2, $list->getItems());
		$this->assertEquals('Item 1', $list->getItems()[0]->getText());
		$this->assertEquals('1', $list->getItems()[0]->getValue());
	}

	// ================================================================================
	// Format Data Value Tests
	// ================================================================================

	public function testFormatDataValueNoFormat()
	{
		$list = new TDropDownList();
		$result = $this->invokeFormatDataValue($list, '', 'value');
		$this->assertEquals('value', $result);
	}

	public function testFormatDataValueSprintfFormat()
	{
		$list = new TDropDownList();
		$result = $this->invokeFormatDataValue($list, 'Item: %s', 'value');
		$this->assertEquals('Item: value', $result);
	}

	public function testFormatDataValueExpressionFormat()
	{
		$list = new TDropDownList();
		$result = $this->invokeFormatDataValue($list, '#"Item: " . $value', 'value');
		$this->assertEquals('Item: value', $result);
	}

	// ================================================================================
	// SelectedValues Tests
	// ================================================================================

	public function testGetSelectedValuesSingle()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));
		$list->setSelectedIndex(1);

		$this->assertEquals(['2'], $list->getSelectedValues());
		$this->assertEquals('2', $list->getSelectedValue());
	}

	public function testSetSelectedValue()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));
		$list->getItems()->add(new TListItem('Item 3', '3'));
		$list->setSelectedValue('2');

		$this->assertEquals(1, $list->getSelectedIndex());
		$this->assertEquals('Item 2', $list->getSelectedItem()->getText());
	}

	public function testClearSelection()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));
		$list->setSelectedIndex(0);
		$list->clearSelection();

		$this->assertEquals(-1, $list->getSelectedIndex());
		$this->assertNull($list->getSelectedItem());
	}

	// ================================================================================
	// Prompt Text Tests
	// ================================================================================

	public function testPromptText()
	{
		$list = new TDropDownList();
		$list->setPromptText('Please select');
		$this->assertEquals('Please select', $list->getPromptText());
	}

	// ================================================================================
	// Validation Tests
	// ================================================================================

	public function testIsValidDefault()
	{
		$list = new TDropDownList();
		$this->assertTrue($list->getIsValid());
	}

	// ================================================================================
	// Causes Validation Tests
	// ================================================================================

	public function testCausesValidationDefault()
	{
		$list = new TDropDownList();
		$this->assertTrue($list->getCausesValidation());
	}

	public function testSetCausesValidation()
	{
		$list = new TDropDownList();
		$list->setCausesValidation(true);
		$this->assertTrue($list->getCausesValidation());
	}

	// ================================================================================
	// Client Script Tests
	// ================================================================================

	public function testGetClientClassName()
	{
		$list = new TDropDownList();
		$method = new ReflectionMethod($list, 'getClientClassName');
		$method->setAccessible(true);
		$this->assertEquals('Prado.WebUI.TDropDownList', $method->invoke($list));
	}

	// ================================================================================
	// AutoPostBack Tests
	// ================================================================================

	public function testAutoPostBackDefault()
	{
		$list = new TDropDownList();
		$this->assertFalse($list->getAutoPostBack());
	}

	public function testSetAutoPostBack()
	{
		$list = new TDropDownList();
		$list->setAutoPostBack(true);
		$this->assertTrue($list->getAutoPostBack());
	}

	// ================================================================================
	// ListItem Selection Tests
	// ================================================================================

	public function testSelectedIndexBounds()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));

		$list->setSelectedIndex(0);
		$this->assertEquals(0, $list->getSelectedIndex());

		$list->setSelectedIndex(1);
		$this->assertEquals(1, $list->getSelectedIndex());
	}

	public function testSelectedIndexOutOfBounds()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));

		$list->setSelectedIndex(0);
		$list->setSelectedIndex(99);

		$this->assertEquals(-1, $list->getSelectedIndex());
	}

	public function testSelectedItem()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));
		$list->setSelectedIndex(0);

		$this->assertNotNull($list->getSelectedItem());
		$this->assertEquals('Item 1', $list->getSelectedItem()->getText());
	}

	public function testSelectedItemNoSelection()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));

		$this->assertNull($list->getSelectedItem());
	}

	// ================================================================================
	// Enabled Items Tests
	// ================================================================================

	public function testEnabledItem()
	{
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('Item 1', '1'));
		$list->getItems()->add(new TListItem('Item 2', '2'));

		$list->getItems()[0]->setEnabled(false);
		$this->assertFalse($list->getItems()[0]->getEnabled());
		$this->assertTrue($list->getItems()[1]->getEnabled());
	}
}
