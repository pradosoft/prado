<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\TPage;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IValidatable;
use Prado\Exceptions\TNotSupportedException;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TDropDownListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$list = new TDropDownList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	private function invokeFormatDataValue($list, $formatString, $value)
	{
		return PradoUnit::invoke($list, 'formatDataValue', $formatString, $value);
	}

	private function getTagName($list)
	{
		return PradoUnit::invoke($list, 'getTagName');
	}

	private function getIsMultiSelect($list)
	{
		return PradoUnit::invoke($list, 'getIsMultiSelect');
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
		$this->assertEquals('Prado.WebUI.TDropDownList', PradoUnit::invoke($list, 'getClientClassName'));
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

	// ================================================================================
	// Interfaces
	// ================================================================================

	public function testImplementsPostBackDataHandlerAndValidatable()
	{
		$list = new TDropDownList();
		$this->assertInstanceOf(IPostBackDataHandler::class, $list);
		$this->assertInstanceOf(IValidatable::class, $list);
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderAddsNameAttribute()
	{
		[, $list] = $this->createPageList();
		$output = $this->renderBeginTag($list);
		$this->assertStringContainsString('name="' . $list->getUniqueID() . '"', $output);
		$this->assertStringNotContainsString('multiple', $output);
	}

	// ================================================================================
	// Post data
	// ================================================================================

	public function testLoadPostDataSelectsPostedValue()
	{
		[, $list] = $this->createPageList();
		$this->assertFalse($list->getDataChanged());
		$this->assertTrue($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataUnchangedSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(2);
		$this->assertFalse($list->loadPostData('List', ['List' => 'c']));
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertFalse($list->getDataChanged());
	}

	public function testLoadPostDataUnknownValueClearsSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(0);
		$this->assertTrue($list->loadPostData('List', ['List' => 'missing']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testLoadPostDataMissingKeyKeepsSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(1);
		$this->assertFalse($list->loadPostData('List', []));
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertFalse($list->getDataChanged());
	}

	public function testLoadPostDataDisabledIsIgnored()
	{
		[, $list] = $this->createPageList();
		$list->setEnabled(false);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testLoadPostDataDisabledByParentIsIgnored()
	{
		$page = new TPage();
		$panel = new TPanel();
		$page->getControls()->add($panel);
		$list = new TDropDownList();
		$list->getItems()->add(new TListItem('A', 'a'));
		$panel->getControls()->add($list);
		$panel->setEnabled(false);
		$this->assertFalse($list->loadPostData('x', ['x' => 'a']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testRaisePostDataChangedEventRaisesSelectedIndexChanged()
	{
		[$page, $list] = $this->createPageList();
		$raised = [];
		$list->attachEventHandler('OnSelectedIndexChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$list->raisePostDataChangedEvent();
		$this->assertSame([[$list, null]], $raised);
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	public function testRaisePostDataChangedEventValidatesOnAutoPostBack()
	{
		[$page, $list] = $this->createPageList();
		$list->setAutoPostBack(true);
		$list->raisePostDataChangedEvent();
		$this->assertTrue(PradoUnit::getProp($page, '_validated'));
	}

	public function testRaisePostDataChangedEventSkipsValidationWithoutCausesValidation()
	{
		[$page, $list] = $this->createPageList();
		$list->setAutoPostBack(true);
		$list->setCausesValidation(false);
		$list->raisePostDataChangedEvent();
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	// ================================================================================
	// Selection restrictions
	// ================================================================================

	public function testSetSelectedIndicesNotSupported()
	{
		$list = new TDropDownList();
		try {
			$list->setSelectedIndices([0]);
			$this->fail('TNotSupportedException expected.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('dropdownlist_selectedindices_unsupported', $e->getErrorCode());
		}
	}

	public function testSetSelectedValuesNotSupported()
	{
		$list = new TDropDownList();
		$this->expectException(TNotSupportedException::class);
		$list->setSelectedValues(['a']);
	}

	// ================================================================================
	// Validation
	// ================================================================================

	public function testGetValidationPropertyValue()
	{
		[, $list] = $this->createPageList();
		$this->assertSame('', $list->getValidationPropertyValue());
		$list->setSelectedValue('c');
		$this->assertSame('c', $list->getValidationPropertyValue());
	}

	public function testSetIsValid()
	{
		$list = new TDropDownList();
		$list->setIsValid('false');
		$this->assertFalse($list->getIsValid());
		$list->setIsValid(true);
		$this->assertTrue($list->getIsValid());
	}
}
