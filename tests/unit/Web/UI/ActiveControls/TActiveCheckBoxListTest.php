<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TListItemCollection;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveCheckBoxList;
use Prado\Web\UI\ActiveControls\TActiveCheckBoxListItem;
use Prado\Web\UI\ActiveControls\TActiveListControlAdapter;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListItem;

/**
 * Client script manager that records script registrations without publishing assets.
 */
class TActiveCheckBoxListTestClientScript extends TClientScriptManager
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

class TActiveCheckBoxListTest extends TestCase
{
	/**
	 * @param bool $callback
	 * @param array $items
	 * @return array [TTestCallbackPage, TActiveCheckBoxList, TActiveCheckBoxListTestClientScript]
	 */
	private function createPageList(bool $callback = true, array $items = ['a' => 'Alpha', 'b' => 'Beta']): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$cs = new TActiveCheckBoxListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$list = new TActiveCheckBoxList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
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
	// TActiveCheckBoxList basics
	// ================================================================================

	public function testExtendsTCheckBoxList()
	{
		$this->assertInstanceOf(TCheckBoxList::class, new TActiveCheckBoxList());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$list = new TActiveCheckBoxList();
		$this->assertInstanceOf(IActiveControl::class, $list);
		$this->assertInstanceOf(ICallbackEventHandler::class, $list);
	}

	public function testConstructorSetsAdapterAndAutoPostBack()
	{
		$list = new TActiveCheckBoxList();
		$this->assertInstanceOf(TActiveListControlAdapter::class, $list->getAdapter());
		$this->assertTrue($list->getAutoPostBack());
	}

	public function testGetActiveControl()
	{
		$list = new TActiveCheckBoxList();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $list->getActiveControl());
		$this->assertSame($list->getAdapter()->getBaseActiveControl(), $list->getActiveControl());
	}

	public function testGetClientSide()
	{
		$list = new TActiveCheckBoxList();
		$this->assertInstanceOf(TCallbackClientSide::class, $list->getClientSide());
		$this->assertSame($list->getActiveControl()->getClientSide(), $list->getClientSide());
	}

	public function testItemsArePlainListItemCollection()
	{
		$items = (new TActiveCheckBoxList())->getItems();
		$this->assertInstanceOf(TListItemCollection::class, $items);
		$this->assertNotInstanceOf(\Prado\Web\UI\ActiveControls\TActiveListItemCollection::class, $items);
	}

	public function testSpanIsAlwaysNeeded()
	{
		$this->assertTrue(PradoUnit::invoke(new TActiveCheckBoxList(), 'getSpanNeeded'));
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TActiveCheckBoxList', PradoUnit::invoke(new TActiveCheckBoxList(), 'getClientClassName'));
	}

	public function testRepeatedControlSharesTheListActiveControl()
	{
		$list = new TActiveCheckBoxList();
		$item = PradoUnit::invoke($list, 'createRepeatedControl');
		$this->assertInstanceOf(TActiveCheckBoxListItem::class, $item);
		$this->assertSame($list->getActiveControl(), $item->getActiveControl());
		$this->assertInstanceOf(TActiveCheckBoxListItem::class, $list->findControl('c0', true));
	}

	// ================================================================================
	// Callback
	// ================================================================================

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		$list = new TActiveCheckBoxList();
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

	public function testSetSelectedIndicesChecksOnClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setSelectedIndices([0, 1]);
		$this->assertSame([0, 1], $list->getSelectedIndices());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'checkClear', null, 2]],
			['Prado.Element.select' => ['List', 'checkIndices', [0, 1], 2]],
		], $page->getClientFunctions());
	}

	public function testSetSelectedValuesChecksOnClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setSelectedValues(['b']);
		$this->assertSame(['b'], $list->getSelectedValues());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'checkClear', null, 2]],
			['Prado.Element.select' => ['List', 'checkValues', ['b'], 2]],
		], $page->getClientFunctions());
	}

	public function testSelectionWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->setSelectedIndices([1]);
		$this->assertSame([1], $list->getSelectedIndices());
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderRegistersOneCallbackControl()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setSelectedValue('b');
		$html = $this->renderControl($list);
		$this->assertStringStartsWith('<span id="List" role="group">', $html);
		$this->assertStringContainsString('id="List_c0"', $html);
		$this->assertStringContainsString('id="List_c1"', $html);
		$this->assertStringContainsString('value="b"', $html);
		$this->assertMatchesRegularExpression('/<input[^>]*id="List_c1"[^>]*checked="checked"|<input[^>]*checked="checked"[^>]*id="List_c1"/', $html);
		$this->assertStringContainsString('id="List_c0_label"', $html);
		$this->assertSame([], $cs->postBackControls);
		$this->assertCount(1, $cs->callbackControls);
		[$class, $options] = $cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveCheckBoxList', $class);
		$this->assertSame('List', $options['ID']);
		$this->assertSame('List', $options['ListName']);
		$this->assertSame(2, $options['ItemCount']);
		$this->assertContains('ajax', $cs->pradoScripts);
	}

	public function testRenderEmptyListKeepsTheSpan()
	{
		[, $list, $cs] = $this->createPageList(false, []);
		$html = $this->renderControl($list);
		$this->assertSame('<span id="List" role="group"></span>', $html);
		$this->assertSame([], $cs->callbackControls);
	}

	public function testRenderWithoutAutoPostBackRegistersNoScript()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setAutoPostBack(false);
		$this->renderControl($list);
		$this->assertSame([], $cs->callbackControls);
		$this->assertSame([], $cs->postBackControls);
	}

	public function testRenderWithoutClientScriptRegistersNoScript()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setEnableClientScript(false);
		$this->renderControl($list);
		$this->assertSame([], $cs->callbackControls);
	}

	public function testRenderWithoutJavaScriptSupportRegistersNoScript()
	{
		[$page, $list, $cs] = $this->createPageList(false);
		$page->setClientSupportsJavaScript(false);
		$this->renderControl($list);
		$this->assertSame([], $cs->callbackControls);
	}

	public function testRenderClientControlScriptRendersNothing()
	{
		[, $list, $cs] = $this->createPageList(false);
		$textWriter = new TTextWriter();
		PradoUnit::invoke($list, 'renderClientControlScript', new THtmlWriter($textWriter));
		$this->assertSame('', $textWriter->flush());
		$this->assertSame([], $cs->postBackControls);
		$this->assertSame([], $cs->callbackControls);
	}

	// ================================================================================
	// TActiveCheckBoxListItem
	// ================================================================================

	public function testItemExtendsTActiveCheckBox()
	{
		$this->assertInstanceOf(TActiveCheckBox::class, new TActiveCheckBoxListItem());
	}

	public function testItemRendersInputWithoutCallbackScript()
	{
		$page = new TTestCallbackPage();
		$page->callback = false;
		$cs = new TActiveCheckBoxListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$item = new TActiveCheckBoxListItem();
		$item->setID('Item');
		$page->getControls()->add($item);
		$this->assertTrue($item->getAutoPostBack());
		$textWriter = new TTextWriter();
		PradoUnit::invoke($item, 'renderInputTag', new THtmlWriter($textWriter), 'Item', '');
		$html = $textWriter->flush();
		$this->assertStringStartsWith('<input', $html);
		$this->assertStringContainsString('type="checkbox"', $html);
		$this->assertStringContainsString('id="Item"', $html);
		$this->assertSame([], $cs->callbackControls);
	}
}
