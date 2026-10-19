<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRadioButtonItem;
use Prado\Web\UI\WebControls\TRadioButtonList;

/**
 * Client script manager that records postback control registrations.
 */
class TRadioButtonListTestClientScript extends TClientScriptManager
{
	private array $_postBackControls = [];

	public function getPostBackControls(): array
	{
		return $this->_postBackControls;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->_postBackControls[] = [$class, $options];
	}
}

class TRadioButtonListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TRadioButtonListTestClientScript($page));
		$list = new TRadioButtonList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testExtendsCheckBoxList()
	{
		$this->assertInstanceOf(TCheckBoxList::class, new TRadioButtonList());
	}

	public function testRepeatedControlIsRadioButtonItem()
	{
		$list = new TRadioButtonList();
		$this->assertInstanceOf(TRadioButtonItem::class, $list->getControls()->itemAt(0));
		$this->assertInstanceOf(TRadioButtonItem::class, PradoUnit::invoke($list, 'createRepeatedControl'));
	}

	public function testProtectedOverrides()
	{
		$list = new TRadioButtonList();
		$this->assertFalse(PradoUnit::invoke($list, 'getIsMultiSelect'));
		$this->assertSame('Prado.WebUI.TRadioButtonList', PradoUnit::invoke($list, 'getClientClassName'));
		$this->assertSame('radiogroup', PradoUnit::invoke($list, 'getGroupRole'));
	}

	// ================================================================================
	// Selection
	// ================================================================================

	public function testSetSelectedIndicesNotSupported()
	{
		$list = new TRadioButtonList();
		try {
			$list->setSelectedIndices([0]);
			$this->fail('TNotSupportedException expected.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('radiobuttonlist_selectedindices_unsupported', $e->getErrorCode());
		}
	}

	public function testSetSelectedValuesNotSupported()
	{
		[, $list] = $this->createPageList();
		$this->expectException(TNotSupportedException::class);
		$list->setSelectedValues(['a']);
	}

	public function testSingleSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedValue('c');
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame('c', $list->getValidationPropertyValue());
	}

	// ================================================================================
	// Post data
	// ================================================================================

	public function testLoadPostDataSelectsPostedValue()
	{
		[, $list] = $this->createPageList();
		$this->assertTrue($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(1, $list->getSelectedIndex());
	}

	public function testLoadPostDataChangesSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(0);
		$this->assertTrue($list->loadPostData('List', ['List' => 'c']));
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame([2], $list->getSelectedIndices());
	}

	public function testLoadPostDataUnchangedSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(1);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(1, $list->getSelectedIndex());
	}

	public function testLoadPostDataSkipsDisabledItem()
	{
		[, $list] = $this->createPageList();
		$list->getItems()->itemAt(1)->setEnabled(false);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testLoadPostDataUnknownValueKeepsSelection()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(0);
		$this->assertFalse($list->loadPostData('List', ['List' => 'missing']));
		$this->assertSame(0, $list->getSelectedIndex());
	}

	public function testLoadPostDataItemKeyMatchesNothing()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(2);
		$this->assertFalse($list->loadPostData('List$c0', ['List' => 'a']));
		$this->assertSame(2, $list->getSelectedIndex());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderRadioGroup()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setSelectedIndex(1);
		$list->setToolTip('Choose');
		$output = $this->render($list);
		$this->assertMatchesRegularExpression('/^<table id="List"[^>]*role="radiogroup" aria-label="Choose">/', $output);
		$this->assertStringNotContainsString('multiple', $output);
		$this->assertStringContainsString(
			'<input id="List_c0" type="radio" name="List" value="a" /><label for="List_c0">A</label>',
			$output
		);
		$this->assertStringContainsString(
			'<input id="List_c1" type="radio" name="List" value="b" checked="checked" /><label for="List_c1">B</label>',
			$output
		);
	}

	public function testRenderAutoPostBackRegistersListScript()
	{
		[$page, $list] = $this->createPageList(['a' => 'A']);
		$list->setAutoPostBack(true);
		$output = $this->render($list);
		$this->assertStringNotContainsString('onclick', $output);
		$registered = $page->getClientScript()->getPostBackControls();
		$this->assertNotEmpty($registered);
		foreach ($registered as $entry) {
			$this->assertSame('Prado.WebUI.TRadioButtonList', $entry[0]);
			$this->assertSame('List', $entry[1]['ListName']);
		}
	}

	// ================================================================================
	// TRadioButtonItem
	// ================================================================================

	public function testRadioButtonItemEmitsNoClientScript()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TRadioButtonListTestClientScript($page));
		$item = new TRadioButtonItem();
		$item->setID('Radio');
		$item->setAutoPostBack(true);
		$page->getControls()->add($item);
		$output = $this->render($item);
		$this->assertStringContainsString('<input id="Radio" type="radio" name="Radio"', $output);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}
}
