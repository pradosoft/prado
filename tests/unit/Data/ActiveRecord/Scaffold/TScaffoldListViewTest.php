<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldListView;
use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TPager;
use Prado\Web\UI\WebControls\TPagerPageChangedEventParameter;
use Prado\Web\UI\WebControls\TRepeater;
use Prado\Web\UI\WebControls\TRepeaterItem;

class TScaffoldListViewTest extends ScaffoldTestCase
{
	private function newListView(?BubbleRecorder $container = null): TScaffoldListView
	{
		$list = new TScaffoldListView();
		$list->setID('list');
		$list->setRecordClass(ScaffoldItemRecord::class);
		if ($container !== null) {
			$container->getControls()->add($list);
			$this->add($container);
		} else {
			$this->add($list);
		}
		return $list;
	}

	private function display(bool $postBack = false): void
	{
		$this->page->setIsPostBack($postBack);
		$this->page->runInit();
		$this->page->runLoad();
		$this->page->runPreRender();
	}

	/**
	 * @param TScaffoldListView $list
	 * @return string[] the item names shown by the list.
	 */
	private function shownNames(TScaffoldListView $list): array
	{
		$names = [];
		foreach ($list->getList()->getItems() as $item) {
			$names[] = $item->getData()->item_name;
		}
		return $names;
	}

	private function itemButton(TRepeaterItem $item, string $command): TButton
	{
		foreach ($item->findControlsByType(TButton::class) as $button) {
			if ($button->getCommandName() === $command) {
				return $button;
			}
		}
		$this->fail("No {$command} button");
	}

	public function testChildControls()
	{
		$list = $this->newListView();

		$this->assertInstanceOf(TRepeater::class, $list->getList());
		$this->assertInstanceOf(TRepeater::class, $list->getHeader());
		$this->assertInstanceOf(TDropDownList::class, $list->getSort());
		$this->assertInstanceOf(TPager::class, $list->getPager());
		$this->assertSame('_list', $list->getList()->getID());
		$this->assertSame('_header', $list->getHeader()->getID());
		$this->assertSame('_sort', $list->getSort()->getID());
		$this->assertSame('_pager', $list->getPager()->getID());
	}

	public function testProperties()
	{
		$list = new TScaffoldListView();

		$this->assertNull($list->getSearchCondition());
		$this->assertSame([], $list->getSearchParameters());
		$this->assertNull($list->getEditViewID());

		$list->setSearchCondition('id > :id');
		$list->setSearchParameters([':id' => 3]);
		$list->setEditViewID('edit');
		$this->assertSame('id > :id', $list->getSearchCondition());
		$this->assertSame([':id' => 3], $list->getSearchParameters());
		$this->assertSame('edit', $list->getEditViewID());
	}

	public function testFirstDisplay_showsHeaderSortAndFirstPage()
	{
		$list = $this->newListView();
		$this->display();

		$headers = [];
		foreach ($list->getHeader()->getItems() as $item) {
			$headers[] = $item->getData();
		}
		$this->assertSame(['Id', 'Item Name', 'Notes', 'Price'], $headers);

		$sorts = [];
		foreach ($list->getSort()->getItems() as $item) {
			$sorts[$item->getValue()] = $item->getText();
		}
		$this->assertSame([
			'0' => 'Sort By',
			'1' => str_repeat('-', 15),
			'id ASC' => 'Id Ascending',
			'id DESC' => 'Id Descending',
			'item_name ASC' => 'Item Name Ascending',
			'item_name DESC' => 'Item Name Descending',
			'notes ASC' => 'Notes Ascending',
			'notes DESC' => 'Notes Descending',
			'price ASC' => 'Price Ascending',
			'price DESC' => 'Price Descending',
		], $sorts);

		$this->assertSame(ScaffoldFixture::ROWS, $list->getList()->getVirtualItemCount());
		$this->assertSame(array_slice(ScaffoldFixture::names(), 0, 10), $this->shownNames($list));
		$this->assertSame(ScaffoldItemRecord::class, $list->getViewState('CurrentClass'));
	}

	public function testItems_carryKeyAndPropertyValues()
	{
		$list = $this->newListView();
		$this->display();

		$items = $list->getList()->getItems();
		$this->assertSame([3], $items->itemAt(2)->getCustomData());
		$values = [];
		foreach ($items->itemAt(2)->_properties->getItems() as $property) {
			$values[] = $property->getData();
		}
		$this->assertSame([3, 'item 03', null, 3.5], $values);
		$this->assertSame([4, 'item 04', 'note 4', 4.5], array_map(
			fn ($p) => $p->getData(),
			$items->itemAt(3)->_properties->getItems()->toArray()
		));
	}

	public function testRender_escapesValuesAndAcceptsNull()
	{
		$list = $this->newListView();
		$this->display();
		ScaffoldFixture::getConnection()->createCommand("UPDATE scaffold_items SET notes = '<b>&' WHERE id = 2")->execute();
		$list->onPreRender(null);

		$html = $this->page->renderHtml();
		$this->assertStringContainsString('&lt;b&gt;&amp;', $html);
		$this->assertStringNotContainsString('<b>&', $html);
		$this->assertStringContainsString('item 01', $html);
	}

	public function testDefaultStyle_registeredOnce()
	{
		$this->newListView();
		$this->display();

		$sheets = $this->styleSheets();
		$this->assertCount(1, $sheets);
		$this->assertStringEndsWith('/style.css', $sheets[0]);
	}

	public function testEditButtons_visibleOnlyWithEditView()
	{
		$list = $this->newListView();
		$this->display();
		$item = $list->getList()->getItems()->itemAt(0);
		$this->assertFalse($this->itemButton($item, 'edit')->getVisible(false));
		$this->assertTrue($this->itemButton($item, 'delete')->getVisible(false));

		$list->setEditViewID('edit');
		$list->onPreRender(null);
		$item = $list->getList()->getItems()->itemAt(0);
		$this->assertTrue($this->itemButton($item, 'edit')->getVisible(false));
	}

	public function testPager_changesThePage()
	{
		$list = $this->newListView();
		$this->display();

		$list->getPager()->onPageIndexChanged(new TPagerPageChangedEventParameter($list->getPager(), 1));
		$this->assertSame(1, $list->getList()->getCurrentPageIndex());
		$list->onPreRender(null);

		$this->assertSame(['item 11', 'item 12'], $this->shownNames($list));
	}

	public function testSort_ordersTheRecords()
	{
		$list = $this->newListView();
		$this->display();

		$list->getSort()->setSelectedValue('item_name DESC');
		$list->onPreRender(null);
		$this->assertSame(array_slice(ScaffoldFixture::names('1=1', 'item_name DESC'), 0, 10), $this->shownNames($list));

		$list->getSort()->setSelectedValue('price ASC');
		$list->onPreRender(null);
		$this->assertSame('item 01', $this->shownNames($list)[0]);

		$list->getSort()->setSelectedValue('1');
		$list->onPreRender(null);
		$this->assertSame(array_slice(ScaffoldFixture::names(), 0, 10), $this->shownNames($list));
	}

	public function testPostBack_keepsSortSelectionUntilRecordClassChanges()
	{
		$list = $this->newListView();
		$this->display();

		$this->page->setIsPostBack(true);
		$list->getSort()->setSelectedValue('id DESC');
		$list->onPreRender(null);
		$this->assertSame('id DESC', $list->getSort()->getSelectedValue());
		$this->assertSame('item 12', $this->shownNames($list)[0]);

		$list->getHeader()->getItems()->clear();
		$list->onPreRender(null);
		$this->assertCount(0, $list->getHeader()->getItems(), 'header is bound once per record class');

		$list->setViewState('CurrentClass', 'OtherRecord');
		$list->onPreRender(null);
		$this->assertCount(4, $list->getHeader()->getItems());
		$this->assertSame(ScaffoldItemRecord::class, $list->getViewState('CurrentClass'));
	}

	public function testSearchConditionAndParameters_filterTheRecords()
	{
		$list = $this->newListView();
		$list->setSearchCondition('item_name LIKE :name');
		$list->setSearchParameters([':name' => 'item 1%']);
		$this->display();

		$this->assertSame(3, $list->getList()->getVirtualItemCount());
		$this->assertSame(['item 10', 'item 11', 'item 12'], $this->shownNames($list));
	}

	public function testDeleteCommand_deletesTheRowRecord()
	{
		$container = new BubbleRecorder();
		$list = $this->newListView($container);
		$this->display();

		$item = $list->getList()->getItems()->itemAt(2);
		$this->itemButton($item, 'delete')->onCommand($this->command('delete'));

		$this->assertNotContains('item 03', ScaffoldFixture::names());
		$this->assertCount(ScaffoldFixture::ROWS - 1, ScaffoldFixture::names());
		$this->assertSame([], $container->events, 'delete does not bubble');

		$list->onPreRender(null);
		$this->assertSame(ScaffoldFixture::ROWS - 1, $list->getList()->getVirtualItemCount());
		$this->assertNotContains('item 03', $this->shownNames($list));
	}

	public function testDeleteLastRowOfLastPage_showsThePreviousPage()
	{
		$list = $this->newListView();
		$this->display();
		ScaffoldFixture::getConnection()->createCommand('DELETE FROM scaffold_items WHERE id = 12')->execute();
		$list->getList()->setCurrentPageIndex(1);
		$list->onPreRender(null);
		$this->assertSame(['item 11'], $this->shownNames($list));

		$this->itemButton($list->getList()->getItems()->itemAt(0), 'delete')->onCommand($this->command('delete'));
		$list->onPreRender(null);

		$this->assertSame(10, $list->getList()->getVirtualItemCount());
		$this->assertCount(10, $this->shownNames($list));
		$this->assertSame(0, $list->getList()->getCurrentPageIndex());
	}

	public function testDeleteCommand_ignoresNonRepeaterParameter()
	{
		$list = $this->newListView();
		$this->display();

		$this->assertNull($list->bubbleEvent($list, $this->command('delete')));
		$this->assertCount(ScaffoldFixture::ROWS, ScaffoldFixture::names());
	}

	public function testEditCommand_loadsEditViewAndBubbles()
	{
		$container = new BubbleRecorder();
		$list = $this->newListView($container);
		$list->setEditViewID('edit');
		$edit = new TScaffoldEditView();
		$edit->setID('edit');
		$edit->setRecordClass(ScaffoldItemRecord::class);
		$edit->setVisible(false);
		$container->getControls()->add($edit);
		$this->display();

		$item = $list->getList()->getItems()->itemAt(4);
		$this->itemButton($item, 'edit')->onCommand($this->command('edit'));

		$this->assertSame([5], $edit->getRecordPk());
		$this->assertSame('item 05', $edit->_repeater->getItems()->itemAt(1)->_input->findControl('scaffold_input')->getText());
		$this->assertCount(1, $container->events);
		$this->assertSame($list, $container->events[0][0]);
		$this->assertSame('edit', $container->events[0][1]);
	}

	public function testEditCommand_withoutEditViewOnlyBubbles()
	{
		$container = new BubbleRecorder();
		$list = $this->newListView($container);
		$this->display();

		$this->assertTrue($list->bubbleEvent($list, $this->itemCommand($list->getList()->getItems()->itemAt(0), 'edit')));
		$this->assertSame('edit', $container->events[0][1]);
	}

	public function testEditCommand_nonRepeaterParameterOnlyBubbles()
	{
		$container = new BubbleRecorder();
		$list = $this->newListView($container);
		$list->setEditViewID('edit');
		$edit = new TScaffoldEditView();
		$edit->setID('edit');
		$edit->setVisible(false);
		$container->getControls()->add($edit);
		$this->display();

		$this->assertTrue($list->bubbleEvent($list, $this->command('edit')));
		$this->assertNull($edit->getRecordPk());
		$this->assertSame('edit', $container->events[0][1]);
	}

	public function testEditCommand_missingEditViewThrows()
	{
		$list = $this->newListView(new BubbleRecorder());
		$list->setEditViewID('missing');
		$this->display();

		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage("'missing'");
		$list->bubbleEvent($list, $this->itemCommand($list->getList()->getItems()->itemAt(0), 'edit'));
	}

	public function testOtherCommand_bubbles()
	{
		$container = new BubbleRecorder();
		$list = $this->newListView($container);
		$this->display();

		$this->assertTrue($list->bubbleEvent($list, $this->command('Custom')));
		$this->assertSame([[$list, 'Custom']], $container->events);
	}

	public function testCustomItemTemplate_withoutPropertiesRepeater()
	{
		$list = $this->newListView();
		$list->getList()->setItemTemplate(new \Prado\Web\UI\TTemplate('<com:TLiteral ID="cell" />', ''));
		$this->display();

		$item = $list->getList()->getItems()->itemAt(0);
		$this->assertSame([1], $item->getCustomData());
		$this->assertNull($item->findControl('_properties'));
	}
}
