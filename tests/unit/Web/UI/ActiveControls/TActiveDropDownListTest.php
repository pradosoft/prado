<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;
use Prado\Web\UI\ActiveControls\TActiveListControlAdapter;
use Prado\Web\UI\ActiveControls\TActiveListItemCollection;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TListItem;

/**
 * Client script manager that records script registrations without publishing assets.
 */
class TActiveDropDownListTestClientScript extends TClientScriptManager
{
	public array $callbackControls = [];

	public array $postBackControls = [];

	public array $pradoScripts = [];

	public function registerCallbackControl($class, $options)
	{
		$this->callbackControls[] = [$class, $options];
	}

	public function registerPostBackControl($class, $options)
	{
		$this->postBackControls[] = [$class, $options];
	}

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TActiveDropDownListTest extends TestCase
{
	/**
	 * @param bool $callback
	 * @return array [TTestCallbackPage, TActiveDropDownList, TActiveDropDownListTestClientScript]
	 */
	private function createPageList(bool $callback = true): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$cs = new TActiveDropDownListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$list = new TActiveDropDownList();
		$list->setID('List');
		$list->getItems()->add(new TListItem('Alpha', 'a'));
		$list->getItems()->add(new TListItem('Beta', 'b'));
		$page->getControls()->add($list);
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_LOADED);
		return [$page, $list, $cs];
	}

	private function renderControl(TControl $control): string
	{
		$textWriter = new TTextWriter();
		$control->renderControl(new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testExtendsTDropDownList()
	{
		$this->assertInstanceOf(TDropDownList::class, new TActiveDropDownList());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$list = new TActiveDropDownList();
		$this->assertInstanceOf(IActiveControl::class, $list);
		$this->assertInstanceOf(ICallbackEventHandler::class, $list);
	}

	public function testConstructorSetsAdapterAndAutoPostBack()
	{
		$list = new TActiveDropDownList();
		$this->assertInstanceOf(TActiveListControlAdapter::class, $list->getAdapter());
		$this->assertTrue($list->getAutoPostBack());
	}

	public function testGetActiveControl()
	{
		$list = new TActiveDropDownList();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $list->getActiveControl());
		$this->assertSame($list->getAdapter()->getBaseActiveControl(), $list->getActiveControl());
	}

	public function testGetClientSide()
	{
		$list = new TActiveDropDownList();
		$this->assertInstanceOf(TCallbackClientSide::class, $list->getClientSide());
		$this->assertSame($list->getActiveControl()->getClientSide(), $list->getClientSide());
	}

	public function testItemsAreActiveListItemCollection()
	{
		$list = new TActiveDropDownList();
		$items = $list->getItems();
		$this->assertInstanceOf(TActiveListItemCollection::class, $items);
		$this->assertSame($list, $items->getControl());
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TActiveDropDownList', PradoUnit::invoke(new TActiveDropDownList(), 'getClientClassName'));
	}

	// ================================================================================
	// Callback
	// ================================================================================

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		$list = new TActiveDropDownList();
		$received = null;
		$list->attachEventHandler('OnCallback', function ($sender, $param) use (&$received, $list) {
			$this->assertSame($list, $sender);
			$received = $param;
		});
		$param = new TCallbackEventParameter(null, 'value');
		$list->raiseCallbackEvent($param);
		$this->assertSame($param, $received);
	}

	// ================================================================================
	// Selection during a callback
	// ================================================================================

	public function testSetSelectedValueUpdatesClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setSelectedValue('b');
		$this->assertSame('b', $list->getSelectedValue());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'selectIndex', 0, 2]],
			['Prado.Element.select' => ['List', 'selectValue', 'b', 2]],
		], $page->getClientFunctions());
	}

	public function testClearSelectionSelectsFirstItemOnClient()
	{
		[$page, $list] = $this->createPageList();
		$list->clearSelection();
		$this->assertSame([
			['Prado.Element.select' => ['List', 'selectIndex', 0, 2]],
		], $page->getClientFunctions());
	}

	public function testSelectionWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->setSelectedIndex(1);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// onPreRender
	// ================================================================================

	public function testOnPreRenderSendsChangedItems()
	{
		[$page, $list] = $this->createPageList();
		$list->setPromptText('Choose');
		$list->getItems()->add(new TListItem('Gamma', 'c'));
		$list->onPreRender(null);
		$this->assertSame([
			['Prado.Element.setOptions' => ['List', [['Choose', 'Choose'], ['Alpha', 'a'], ['Beta', 'b'], ['Gamma', 'c']]]],
		], $page->getClientFunctions());
	}

	public function testOnPreRenderUnchangedItemsSendsNothing()
	{
		[$page, $list] = $this->createPageList();
		$list->onPreRender(null);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testOnPreRenderWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->getItems()->add(new TListItem('Gamma', 'c'));
		$list->onPreRender(null);
		$this->assertFalse($list->getItems()->getListHasChanged());
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderRegistersCallbackControl()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setSelectedValue('b');
		$html = $this->renderControl($list);
		$this->assertStringStartsWith('<select', $html);
		$this->assertStringContainsString('name="List"', $html);
		$this->assertStringContainsString('id="List"', $html);
		$this->assertStringContainsString('<option selected="selected" value="b">Beta</option>', $html);
		$this->assertSame([], $cs->postBackControls);
		$this->assertCount(1, $cs->callbackControls);
		[$class, $options] = $cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveDropDownList', $class);
		$this->assertSame('List', $options['ID']);
		$this->assertSame('List', $options['EventTarget']);
		$this->assertContains('ajax', $cs->pradoScripts);
	}

	public function testRenderWithoutAutoPostBackRegistersNoScript()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setAutoPostBack(false);
		$html = $this->renderControl($list);
		$this->assertStringContainsString('id="List"', $html);
		$this->assertSame([], $cs->callbackControls);
		$this->assertSame([], $cs->postBackControls);
	}

	public function testRenderClientControlScriptRendersNothing()
	{
		[, $list, $cs] = $this->createPageList(false);
		$textWriter = new TTextWriter();
		PradoUnit::invoke($list, 'renderClientControlScript', new THtmlWriter($textWriter));
		$this->assertSame('', $textWriter->flush());
		$this->assertSame([], $cs->postBackControls);
	}
}
