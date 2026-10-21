<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCheckBoxList;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;
use Prado\Web\UI\ActiveControls\TActiveListBox;
use Prado\Web\UI\ActiveControls\TActiveListControlAdapter;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IListControlAdapter;
use Prado\Web\UI\WebControls\TListControl;
use Prado\Web\UI\WebControls\TListItem;

class TActiveListControlAdapterTest extends TestCase
{
	/**
	 * Creates a list control on a callback page at the loaded control stage.
	 * @param string $class the active list control class
	 * @param bool $callback whether the page reports a callback request
	 * @param array $items value => text of the list items
	 * @return array [TTestCallbackPage, TListControl, TActiveListControlAdapter]
	 */
	private function createList(string $class = TActiveListBox::class, bool $callback = true, array $items = ['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma']): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$list = new $class();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_LOADED);
		return [$page, $list, $list->getAdapter()];
	}

	private function select(string $method, $value, int $total = 3, string $type = 'select', string $id = 'List'): array
	{
		return ['Prado.Element.select' => [$id, $type . $method, $value, $total]];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testIsActiveListControlAdapter()
	{
		$adapter = (new TActiveListBox())->getAdapter();
		$this->assertInstanceOf(TActiveListControlAdapter::class, $adapter);
		$this->assertInstanceOf(TActiveControlAdapter::class, $adapter);
		$this->assertInstanceOf(IListControlAdapter::class, $adapter);
	}

	public function testBaseActiveControlIsCallbackControl()
	{
		$list = new TActiveListBox();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $list->getAdapter()->getBaseActiveControl());
	}

	public function testCanUpdateClientSideDuringCallback()
	{
		[, , $adapter] = $this->createList();
		$this->assertTrue(PradoUnit::invoke($adapter, 'canUpdateClientSide'));
	}

	public function testCannotUpdateClientSideWithoutCallback()
	{
		[, , $adapter] = $this->createList(TActiveListBox::class, false);
		$this->assertFalse(PradoUnit::invoke($adapter, 'canUpdateClientSide'));
	}

	public function testCannotUpdateClientSideWithEnableUpdateFalse()
	{
		[, $list, $adapter] = $this->createList();
		$list->getActiveControl()->setEnableUpdate(false);
		$this->assertFalse(PradoUnit::invoke($adapter, 'canUpdateClientSide'));
	}

	public function testCannotUpdateClientSideBeforeChildInitialized()
	{
		$page = new TTestCallbackPage();
		$list = new TActiveListBox();
		$page->getControls()->add($list);
		$this->assertFalse(PradoUnit::invoke($list->getAdapter(), 'canUpdateClientSide'));
	}

	public function testCannotUpdateClientSideWhenInvisible()
	{
		[, $list, $adapter] = $this->createList();
		$list->setVisible(false);
		$this->assertFalse(PradoUnit::invoke($adapter, 'canUpdateClientSide'));
	}

	// ================================================================================
	// No client update outside a callback
	// ================================================================================

	public function testNoCallbackQueuesNothing()
	{
		[$page, $list, $adapter] = $this->createList(TActiveListBox::class, false);
		$list->getItems()->add(new TListItem('Delta', 'd'));
		$adapter->setSelectedIndex(1);
		$adapter->setSelectedIndices([0, 1]);
		$adapter->setSelectedValue('a');
		$adapter->setSelectedValues(['a', 'b']);
		$adapter->clearSelection();
		$adapter->updateListItems();
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testEnableUpdateFalseQueuesNothing()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->getActiveControl()->setEnableUpdate(false);
		$adapter->setSelectedIndex(1);
		$adapter->setSelectedIndices([0]);
		$adapter->setSelectedValue('a');
		$adapter->setSelectedValues(['a']);
		$adapter->clearSelection();
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// setSelectedIndex
	// ================================================================================

	public function testSetSelectedIndex()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedIndex(1);
		$this->assertSame([$this->select('Index', 1)], $page->getClientFunctions());
	}

	public function testSetSelectedIndexAtItemCountIsNotSent()
	{
		[$page, $list, $adapter] = $this->createList();
		$adapter->setSelectedIndex(3);
		$this->assertSame([], $page->getClientFunctions());

		$list->setPromptText('Choose');
		$adapter->setSelectedIndex(3);
		$this->assertSame([], $page->getClientFunctions());
		$adapter->setSelectedIndex(2);
		$this->assertSame([$this->select('Index', 3)], $page->getClientFunctions());
	}

	public function testSetSelectedIndexOutOfRangeIsNotSent()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedIndex(-1);
		$adapter->setSelectedIndex(4);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetSelectedIndexSkipsPromptText()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->setPromptText('Choose');
		$adapter->setSelectedIndex(0);
		$this->assertSame([$this->select('Index', 1)], $page->getClientFunctions());
	}

	public function testSetSelectedIndexSkipsPromptValue()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->setPromptValue('none');
		$adapter->setSelectedIndex(-1);
		$this->assertSame([$this->select('Index', 0)], $page->getClientFunctions());
	}

	public function testSetSelectedIndexThroughListControl()
	{
		[$page, $list] = $this->createList();
		$list->setSelectedIndex(2);
		$this->assertSame([
			$this->select('Clear', null),
			$this->select('Index', 2),
		], $page->getClientFunctions());
		$this->assertSame(2, $list->getSelectedIndex());
	}

	// ================================================================================
	// setSelectedIndices
	// ================================================================================

	public function testSetSelectedIndices()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedIndices([0, '2', -1, 3, 7]);
		$this->assertSame([$this->select('Indices', [0, 2])], $page->getClientFunctions());
	}

	public function testSetSelectedIndicesSkipsPrompt()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->setPromptText('Choose');
		$adapter->setSelectedIndices([-1, 0, 2, 3]);
		$this->assertSame([$this->select('Indices', [0, 1, 3])], $page->getClientFunctions());
	}

	public function testSetSelectedIndicesSkipsPromptValue()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->setPromptValue('none');
		$adapter->setSelectedIndices([1]);
		$this->assertSame([$this->select('Indices', [2])], $page->getClientFunctions());
	}

	public function testSetSelectedIndicesAllOutOfRangeIsNotSent()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedIndices([-5, 9]);
		$adapter->setSelectedIndices([]);
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// setSelectedValue / setSelectedValues
	// ================================================================================

	public function testSetSelectedValue()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedValue('b');
		$this->assertSame([$this->select('Value', 'b')], $page->getClientFunctions());
	}

	public function testSetSelectedValues()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedValues(['x' => 'a', 'y' => 'c']);
		$this->assertSame([$this->select('Values', ['a', 'c'])], $page->getClientFunctions());
	}

	public function testSetSelectedValuesEmptyIsNotSent()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->setSelectedValues([]);
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// clearSelection
	// ================================================================================

	public function testClearSelectionOfListBox()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->clearSelection();
		$this->assertSame([$this->select('Clear', null)], $page->getClientFunctions());
	}

	public function testClearSelectionOfDropDownListSelectsFirstItem()
	{
		[$page, , $adapter] = $this->createList(TActiveDropDownList::class);
		$adapter->clearSelection();
		$this->assertSame([$this->select('Index', 0)], $page->getClientFunctions());
	}

	public function testCheckBoxListSelectionUsesCheckType()
	{
		[$page, , $adapter] = $this->createList(TActiveCheckBoxList::class);
		$adapter->setSelectedValue('a');
		$adapter->clearSelection();
		$this->assertSame([
			$this->select('Value', 'a', 3, 'check'),
			$this->select('Clear', null, 3, 'check'),
		], $page->getClientFunctions());
	}

	// ================================================================================
	// updateListItems
	// ================================================================================

	public function testUpdateListItemsSendsChangedOptions()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->getItems()->add(new TListItem('Delta', 'd'));
		$this->assertTrue($list->getItems()->getListHasChanged());
		$adapter->updateListItems();
		$this->assertSame([
			['Prado.Element.setOptions' => ['List', [
				['Alpha', 'a'],
				['Beta', 'b'],
				['Gamma', 'c'],
				['Delta', 'd'],
			]]],
		], $page->getClientFunctions());
		$this->assertFalse($list->getItems()->getListHasChanged());
	}

	public function testUpdateListItemsUnchangedSendsNothing()
	{
		[$page, , $adapter] = $this->createList();
		$adapter->updateListItems();
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testUpdateListItemsIgnoresPlainItemCollection()
	{
		[$page, $list, $adapter] = $this->createList(TActiveCheckBoxList::class);
		$list->getItems()->add(new TListItem('Delta', 'd'));
		$adapter->updateListItems();
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSelectionSendsChangedOptionsFirst()
	{
		[$page, $list, $adapter] = $this->createList();
		$list->getItems()->removeAt(2);
		$adapter->setSelectedValue('b');
		$this->assertSame([
			['Prado.Element.setOptions' => ['List', [['Alpha', 'a'], ['Beta', 'b']]]],
			$this->select('Value', 'b', 2),
		], $page->getClientFunctions());
	}

	public function testEverySelectionSendsChangedOptions()
	{
		foreach (['setSelectedIndex' => 0, 'setSelectedIndices' => [0], 'setSelectedValues' => ['a'], 'clearSelection' => null] as $method => $arg) {
			[$page, $list, $adapter] = $this->createList();
			$list->getItems()->add(new TListItem('Delta', 'd'));
			$adapter->$method($arg);
			$functions = $page->getClientFunctions();
			$this->assertCount(2, $functions, $method);
			$this->assertArrayHasKey('Prado.Element.setOptions', $functions[0], $method);
			$this->assertArrayHasKey('Prado.Element.select', $functions[1], $method);
		}
	}

	public function testUpdateListItemsSendsPromptOption()
	{
		[$page, $list, $adapter] = $this->createList(TActiveDropDownList::class, true, ['a' => 'Alpha']);
		$list->setPromptText('Choose');
		$list->setPromptValue('none');
		$list->getItems()->add(new TListItem('Beta', 'b'));
		$adapter->updateListItems();
		$this->assertSame([
			['Prado.Element.setOptions' => ['List', [
				['Choose', 'none'],
				['Alpha', 'a'],
				['Beta', 'b'],
			]]],
		], $page->getClientFunctions());
	}
}
