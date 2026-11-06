<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\TScaffoldListView;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldSearch;
use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TTextBox;

class TScaffoldSearchTest extends ScaffoldTestCase
{
	private BubbleRecorder $_container;

	private function newSearch(?string $listViewId = 'list'): TScaffoldSearch
	{
		$this->_container = new BubbleRecorder();
		$search = new TScaffoldSearch();
		$search->setID('search');
		$search->setRecordClass(ScaffoldItemRecord::class);
		if ($listViewId !== null) {
			$search->setListViewID($listViewId);
		}
		$this->_container->getControls()->add($search);
		$this->add($this->_container);
		return $search;
	}

	private function newList(): TScaffoldListView
	{
		$list = new TScaffoldListView();
		$list->setID('list');
		$list->setRecordClass(ScaffoldItemRecord::class);
		$this->_container->getControls()->add($list);
		return $list;
	}

	private function search(TScaffoldSearch $search, string $text)
	{
		$search->getSearchText()->setText($text);
		return $search->bubbleEvent($search->getSearchButton(), $this->command('search'));
	}

	public function testChildControlsAndProperties()
	{
		$search = $this->newSearch(null);

		$this->assertInstanceOf(TTextBox::class, $search->getSearchText());
		$this->assertInstanceOf(TButton::class, $search->getSearchButton());
		$this->assertSame('search', $search->getSearchButton()->getCommandName());
		$this->assertNull($search->getListViewID());
		$this->assertSame('', $search->getSearchableFields());

		$search->setListViewID('list');
		$search->setSearchableFields('item_name');
		$this->assertSame('list', $search->getListViewID());
		$this->assertSame('item_name', $search->getSearchableFields());
	}

	public function testSearch_setsConditionOverAllColumns()
	{
		$search = $this->newSearch();
		$list = $this->newList();

		$this->assertFalse($this->search($search, 'item 1'));

		$this->assertSame(
			"((\"id\" LIKE '%item%' ESCAPE '\\' AND \"id\" LIKE '%1%' ESCAPE '\\') OR (\"item_name\" LIKE '%item%' ESCAPE '\\' AND \"item_name\" LIKE '%1%' ESCAPE '\\')"
			. " OR (\"notes\" LIKE '%item%' ESCAPE '\\' AND \"notes\" LIKE '%1%' ESCAPE '\\') OR (\"price\" LIKE '%item%' ESCAPE '\\' AND \"price\" LIKE '%1%' ESCAPE '\\'))",
			$list->getSearchCondition()
		);
		$this->assertSame([], $this->_container->events, 'a handled search is left to bubble on by the caller');
	}

	public function testSearch_filtersTheListView()
	{
		$search = $this->newSearch();
		$list = $this->newList();
		$this->page->runInit();
		$this->page->runLoad();

		$search->getSearchButton()->onCommand($this->command('search'));
		$search->getSearchText()->setText('note');
		$search->getSearchButton()->onCommand($this->command('search'));
		$this->page->runPreRender();

		$this->assertSame(6, $list->getList()->getVirtualItemCount());
		$names = array_map(fn ($item) => $item->getData()->item_name, $list->getList()->getItems()->toArray());
		$this->assertSame(['item 02', 'item 04', 'item 06', 'item 08', 'item 10', 'item 12'], $names);
	}

	public function testSearch_emptyTextClearsTheCondition()
	{
		$search = $this->newSearch();
		$list = $this->newList();
		$list->setSearchCondition('id = 1');

		$this->search($search, '');

		$this->assertSame('', $list->getSearchCondition());
	}

	public function testSearch_limitedToSearchableFields()
	{
		$search = $this->newSearch();
		$list = $this->newList();

		$search->setSearchableFields('item_name');
		$this->search($search, '03');
		$this->assertSame("((\"item_name\" LIKE '%03%' ESCAPE '\\'))", $list->getSearchCondition());

		$search->setSearchableFields(' item_name ,notes ');
		$this->search($search, '03');
		$this->assertSame("((\"item_name\" LIKE '%03%' ESCAPE '\\') OR (\"notes\" LIKE '%03%' ESCAPE '\\'))", $list->getSearchCondition());

		$search->setSearchableFields('   ');
		$this->search($search, '03');
		$this->assertStringContainsString('"price" LIKE', $list->getSearchCondition());
	}

	public function testSearch_withoutListViewBubbles()
	{
		$search = $this->newSearch(null);

		$this->assertTrue($this->search($search, 'x'));
		$this->assertSame([[$search, 'search']], $this->_container->events);
	}

	public function testSearch_missingListViewThrows()
	{
		$search = $this->newSearch('missing');

		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage("'missing'");
		$this->search($search, 'x');
	}

	public function testOtherCommand_bubbles()
	{
		$search = $this->newSearch();
		$this->newList();

		$this->assertTrue($search->bubbleEvent($search, $this->command('other')));
		$this->assertSame([[$search, 'other']], $this->_container->events);
	}
}
