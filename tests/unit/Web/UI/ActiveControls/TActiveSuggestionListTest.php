<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveListItemCollection;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IListControlAdapter;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TSuggestionList;
use PHPUnit\Framework\TestCase;

class TActiveSuggestionListTest extends TestCase
{
	private function createLoadedList(bool $callback = true): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$list = new TActiveSuggestionList();
		$list->setID('Cities');
		$list->getItems()->add(new TListItem('Rome'));
		$page->getControls()->add($list);
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_LOADED);
		return [$page, $list];
	}

	// --- Basics ---

	public function testExtendsTSuggestionList()
	{
		$this->assertInstanceOf(TSuggestionList::class, new TActiveSuggestionList());
	}

	public function testImplementsIActiveControl()
	{
		$this->assertInstanceOf(IActiveControl::class, new TActiveSuggestionList());
	}

	public function testGetActiveControlReturnsBaseActiveControl()
	{
		$this->assertInstanceOf(TBaseActiveControl::class, (new TActiveSuggestionList())->getActiveControl());
	}

	public function testAdapterIsNotListControlAdapter()
	{
		$this->assertNotInstanceOf(IListControlAdapter::class, (new TActiveSuggestionList())->getAdapter());
	}

	public function testItemsAreActiveListItemCollection()
	{
		$list = new TActiveSuggestionList();
		$items = $list->getItems();
		$this->assertInstanceOf(TActiveListItemCollection::class, $items);
		$this->assertSame($list, $items->getControl());
	}

	// --- Client update ---

	public function testAddedItemReplacesClientOptions()
	{
		[$page, $list] = $this->createLoadedList();
		$list->getItems()->add(new TListItem('New York City', 'NYC'));
		$list->onPreRender(null);
		$this->assertSame([
			['Prado.Element.setDataListOptions' => ['Cities', [
				['Rome', '', []],
				['NYC', 'New York City', []],
			]]],
		], $page->getClientFunctions());
	}

	public function testRemovedItemReplacesClientOptions()
	{
		[$page, $list] = $this->createLoadedList();
		$list->getItems()->removeAt(0);
		$list->onPreRender(null);
		$this->assertSame([['Prado.Element.setDataListOptions' => ['Cities', []]]], $page->getClientFunctions());
	}

	public function testDataBindReplacesClientOptions()
	{
		[$page, $list] = $this->createLoadedList();
		$list->setDataSource(['Paris', 'London']);
		$list->dataBind();
		$list->onPreRender(null);
		$this->assertSame([
			['Prado.Element.setDataListOptions' => ['Cities', [
				['Paris', '', []],
				['London', '', []],
			]]],
		], $page->getClientFunctions());
	}

	public function testClientOptionsSkipDisabledAndExcludeGroup()
	{
		[$page, $list] = $this->createLoadedList();
		$disabled = new TListItem('Oslo');
		$disabled->setEnabled(false);
		$grouped = new TListItem('Paris');
		$grouped->getAttributes()->add('Group', 'Europe');
		$grouped->getAttributes()->add('data-country', 'FR');
		$list->getItems()->add($disabled);
		$list->getItems()->add($grouped);
		$list->onPreRender(null);
		$this->assertSame([
			['Prado.Element.setDataListOptions' => ['Cities', [
				['Rome', '', []],
				['Paris', '', ['data-country' => 'FR']],
			]]],
		], $page->getClientFunctions());
	}

	public function testUnchangedItemsSendNothing()
	{
		[$page, $list] = $this->createLoadedList();
		$list->onPreRender(null);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testUpdateSentOnce()
	{
		[$page, $list] = $this->createLoadedList();
		$list->getItems()->add(new TListItem('Paris'));
		$list->updateListItems();
		$list->updateListItems();
		$this->assertCount(1, $page->getClientFunctions());
	}

	public function testItemsChangedBeforeLoadSendNothing()
	{
		$page = new TTestCallbackPage();
		$list = new TActiveSuggestionList();
		$list->setID('Cities');
		$page->getControls()->add($list);
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_INITIALIZED);
		$list->getItems()->add(new TListItem('Paris'));
		$list->updateListItems();
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testNoCallbackSendsNothing()
	{
		[$page, $list] = $this->createLoadedList(false);
		$list->getItems()->add(new TListItem('Paris'));
		$list->onPreRender(null);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testEnableUpdateFalseSendsNothing()
	{
		[$page, $list] = $this->createLoadedList();
		$list->getActiveControl()->setEnableUpdate(false);
		$list->getItems()->add(new TListItem('Paris'));
		$list->onPreRender(null);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSelectionSendsNothing()
	{
		[$page, $list] = $this->createLoadedList();
		$list->setSelectedIndex(0);
		$list->setSelectedValue('Rome');
		$list->clearSelection();
		$this->assertSame([], $page->getClientFunctions());
	}
}
