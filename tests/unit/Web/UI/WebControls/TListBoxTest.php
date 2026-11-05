<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IValidatable;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TListBox;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TListSelectionMode;
use Prado\Web\UI\WebControls\TPanel;

class TListBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(bool $multi = false, array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$list = new TListBox();
		$list->setID('List');
		if ($multi) {
			$list->setSelectionMode(TListSelectionMode::Multiple);
		}
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testImplementsPostBackDataHandlerAndValidatable()
	{
		$list = new TListBox();
		$this->assertInstanceOf(IPostBackDataHandler::class, $list);
		$this->assertInstanceOf(IValidatable::class, $list);
	}

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TListBox', PradoUnit::invoke(new TListBox(), 'getClientClassName'));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testRows()
	{
		$list = new TListBox();
		$this->assertSame(4, $list->getRows());
		$list->setRows('7');
		$this->assertSame(7, $list->getRows());
		$list->setRows(0);
		$this->assertSame(4, $list->getRows());
		$list->setRows(-3);
		$this->assertSame(4, $list->getRows());
	}

	public function testSelectionMode()
	{
		$list = new TListBox();
		$this->assertSame(TListSelectionMode::Single, $list->getSelectionMode());
		$this->assertFalse(PradoUnit::invoke($list, 'getIsMultiSelect'));
		$list->setSelectionMode('Multiple');
		$this->assertSame(TListSelectionMode::Multiple, $list->getSelectionMode());
		$this->assertTrue(PradoUnit::invoke($list, 'getIsMultiSelect'));
		$list->setSelectionMode(TListSelectionMode::Single);
		$this->assertSame(TListSelectionMode::Single, $list->getSelectionMode());
	}

	public function testSelectionModeInvalid()
	{
		$list = new TListBox();
		$this->expectException(TInvalidDataValueException::class);
		$list->setSelectionMode('Many');
	}

	public function testMultipleModeAllowsSelectedIndices()
	{
		[, $list] = $this->createPageList(true);
		$list->setSelectedIndices([0, 2]);
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertSame(['a', 'c'], $list->getSelectedValues());
	}

	public function testIsValid()
	{
		$list = new TListBox();
		$this->assertTrue($list->getIsValid());
		$list->setIsValid('false');
		$this->assertFalse($list->getIsValid());
	}

	public function testGetValidationPropertyValue()
	{
		[, $list] = $this->createPageList(true);
		$this->assertSame('', $list->getValidationPropertyValue());
		$list->setSelectedIndices([1, 2]);
		$this->assertSame('b', $list->getValidationPropertyValue());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderSingleAttributes()
	{
		[, $list] = $this->createPageList();
		$list->setRows(6);
		$output = $this->renderBeginTag($list);
		$this->assertStringContainsString('size="6"', $output);
		$this->assertStringContainsString('name="' . $list->getUniqueID() . '"', $output);
		$this->assertStringNotContainsString('multiple', $output);
	}

	public function testRenderMultipleAttributes()
	{
		[, $list] = $this->createPageList(true);
		$output = $this->renderBeginTag($list);
		$this->assertStringContainsString('size="4"', $output);
		$this->assertStringContainsString('name="' . $list->getUniqueID() . '[]"', $output);
		$this->assertStringContainsString('multiple="multiple"', $output);
	}

	// ================================================================================
	// onPreRender
	// ================================================================================

	public function testOnPreRenderRegistersForPostData()
	{
		[$page, $list] = $this->createPageList();
		$list->onPreRender(null);
		$this->assertSame([$list->getUniqueID() => true], PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	public function testOnPreRenderDisabledDoesNotRegister()
	{
		[$page, $list] = $this->createPageList();
		$list->setEnabled(false);
		$list->onPreRender(null);
		$this->assertSame([], PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	// ================================================================================
	// Post data: single
	// ================================================================================

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
		$list = new TListBox();
		$list->getItems()->add(new TListItem('A', 'a'));
		$panel->getControls()->add($list);
		$panel->setEnabled(false);
		$this->assertFalse($list->loadPostData('x', ['x' => 'a']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testLoadPostDataSingleSelectsValue()
	{
		[, $list] = $this->createPageList();
		$this->assertTrue($list->loadPostData('List', ['List' => 'c']));
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataSingleUsesFirstArrayEntry()
	{
		[, $list] = $this->createPageList();
		$this->assertTrue($list->loadPostData('List', ['List' => ['b', 'c']]));
		$this->assertSame([1], $list->getSelectedIndices());
	}

	public function testLoadPostDataSingleUnchanged()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(1);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertFalse($list->getDataChanged());
	}

	public function testLoadPostDataSingleUnknownValueClearsSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(0);
		$this->assertTrue($list->loadPostData('List', ['List' => 'missing']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	// ================================================================================
	// Post data: none posted
	// ================================================================================

	public function testLoadPostDataMissingKeyClearsSelection()
	{
		[, $list] = $this->createPageList(true);
		$list->setSelectedIndices([0, 1]);
		$this->assertTrue($list->loadPostData('List', []));
		$this->assertSame([], $list->getSelectedIndices());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataMissingKeyWithoutSelection()
	{
		[, $list] = $this->createPageList(true);
		$this->assertFalse($list->loadPostData('List', []));
		$this->assertFalse($list->getDataChanged());
	}

	// ================================================================================
	// Post data: multiple
	// ================================================================================

	public function testLoadPostDataMultipleSelectsValues()
	{
		[, $list] = $this->createPageList(true);
		$this->assertTrue($list->loadPostData('List', ['List' => ['c', 'a']]));
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataMultipleScalarValue()
	{
		[, $list] = $this->createPageList(true);
		$this->assertTrue($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame([1], $list->getSelectedIndices());
	}

	public function testLoadPostDataMultipleUnchanged()
	{
		[, $list] = $this->createPageList(true);
		$list->setSelectedIndices([0, 2]);
		$this->assertFalse($list->loadPostData('List', ['List' => ['c', 'a']]));
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertFalse($list->getDataChanged());
	}

	public function testLoadPostDataMultipleSameCountDifferentItems()
	{
		[, $list] = $this->createPageList(true);
		$list->setSelectedIndices([0, 1]);
		$this->assertTrue($list->loadPostData('List', ['List' => ['a', 'c']]));
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataMultipleDifferentCount()
	{
		[, $list] = $this->createPageList(true);
		$list->setSelectedIndices([0]);
		$this->assertTrue($list->loadPostData('List', ['List' => ['a', 'b', 'c']]));
		$this->assertSame([0, 1, 2], $list->getSelectedIndices());
	}

	// ================================================================================
	// Events
	// ================================================================================

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
}
