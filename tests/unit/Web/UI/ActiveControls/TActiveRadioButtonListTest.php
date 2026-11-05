<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveListControlAdapter;
use Prado\Web\UI\ActiveControls\TActiveRadioButton;
use Prado\Web\UI\ActiveControls\TActiveRadioButtonItem;
use Prado\Web\UI\ActiveControls\TActiveRadioButtonList;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRadioButtonList;

/**
 * Client script manager that records script registrations without publishing assets.
 */
class TActiveRadioButtonListTestClientScript extends TClientScriptManager
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

class TActiveRadioButtonListTest extends TestCase
{
	/**
	 * @param bool $callback
	 * @param array $items
	 * @return array [TTestCallbackPage, TActiveRadioButtonList, TActiveRadioButtonListTestClientScript]
	 */
	private function createPageList(bool $callback = true, array $items = ['a' => 'Alpha', 'b' => 'Beta']): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$cs = new TActiveRadioButtonListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$list = new TActiveRadioButtonList();
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
	// TActiveRadioButtonList basics
	// ================================================================================

	public function testExtendsTRadioButtonList()
	{
		$this->assertInstanceOf(TRadioButtonList::class, new TActiveRadioButtonList());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$list = new TActiveRadioButtonList();
		$this->assertInstanceOf(IActiveControl::class, $list);
		$this->assertInstanceOf(ICallbackEventHandler::class, $list);
	}

	public function testConstructorSetsAdapterAndAutoPostBack()
	{
		$list = new TActiveRadioButtonList();
		$this->assertInstanceOf(TActiveListControlAdapter::class, $list->getAdapter());
		$this->assertTrue($list->getAutoPostBack());
	}

	public function testGetActiveControl()
	{
		$list = new TActiveRadioButtonList();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $list->getActiveControl());
		$this->assertSame($list->getAdapter()->getBaseActiveControl(), $list->getActiveControl());
	}

	public function testGetClientSide()
	{
		$list = new TActiveRadioButtonList();
		$this->assertInstanceOf(TCallbackClientSide::class, $list->getClientSide());
		$this->assertSame($list->getActiveControl()->getClientSide(), $list->getClientSide());
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TActiveRadioButtonList', PradoUnit::invoke(new TActiveRadioButtonList(), 'getClientClassName'));
	}

	public function testRepeatedControlSharesTheListActiveControl()
	{
		$list = new TActiveRadioButtonList();
		$item = PradoUnit::invoke($list, 'createRepeatedControl');
		$this->assertInstanceOf(TActiveRadioButtonItem::class, $item);
		$this->assertSame($list->getActiveControl(), $item->getActiveControl());
		$this->assertInstanceOf(TActiveRadioButtonItem::class, $list->findControl('c0', true));
	}

	// ================================================================================
	// Callback
	// ================================================================================

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		$list = new TActiveRadioButtonList();
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

	public function testSetSelectedIndexChecksOnClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setSelectedIndex(1);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'checkClear', null, 2]],
			['Prado.Element.select' => ['List', 'checkIndex', 1, 2]],
		], $page->getClientFunctions());
	}

	public function testSetSelectedValueChecksOnClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setSelectedValue('a');
		$this->assertSame('a', $list->getSelectedValue());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'checkClear', null, 2]],
			['Prado.Element.select' => ['List', 'checkValue', 'a', 2]],
		], $page->getClientFunctions());
	}

	public function testSetSelectedIndicesIsNotSupported()
	{
		[, $list] = $this->createPageList();
		$this->expectException(TNotSupportedException::class);
		$list->setSelectedIndices([0]);
	}

	public function testSelectionWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->setSelectedIndex(1);
		$this->assertSame(1, $list->getSelectedIndex());
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
		$this->assertStringContainsString('role="radiogroup"', $html);
		$this->assertStringContainsString('id="List_c0"', $html);
		$this->assertStringContainsString('id="List_c1"', $html);
		$this->assertSame(2, substr_count($html, 'type="radio"'));
		$this->assertSame(2, substr_count($html, 'name="List"'));
		$this->assertStringContainsString('value="b" checked="checked"', $html);
		$this->assertStringContainsString('id="List_c1_label"', $html);
		$this->assertSame([], $cs->postBackControls);
		$this->assertCount(1, $cs->callbackControls);
		[$class, $options] = $cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveRadioButtonList', $class);
		$this->assertSame('List', $options['ID']);
		$this->assertSame('List', $options['ListName']);
		$this->assertSame(2, $options['ItemCount']);
		$this->assertContains('ajax', $cs->pradoScripts);
	}

	public function testRenderRegistersCallbackControlWithoutAutoPostBack()
	{
		// The radio button list registers its callback script unconditionally.
		[, $list, $cs] = $this->createPageList(false);
		$list->setAutoPostBack(false);
		$this->renderControl($list);
		$this->assertCount(1, $cs->callbackControls);
		$this->assertSame('Prado.WebUI.TActiveRadioButtonList', $cs->callbackControls[0][0]);
	}

	public function testRenderEmptyListRegistersNoScript()
	{
		[, $list, $cs] = $this->createPageList(false, []);
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
	// TActiveRadioButtonItem
	// ================================================================================

	public function testItemExtendsTActiveRadioButton()
	{
		$this->assertInstanceOf(TActiveRadioButton::class, new TActiveRadioButtonItem());
	}

	public function testItemRendersInputWithoutCallbackScript()
	{
		$page = new TTestCallbackPage();
		$page->callback = false;
		$cs = new TActiveRadioButtonListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$item = new TActiveRadioButtonItem();
		$item->setID('Item');
		$page->getControls()->add($item);
		$this->assertTrue($item->getAutoPostBack());
		$textWriter = new TTextWriter();
		PradoUnit::invoke($item, 'renderInputTag', new THtmlWriter($textWriter), 'Item', '');
		$html = $textWriter->flush();
		$this->assertStringStartsWith('<input', $html);
		$this->assertStringContainsString('type="radio"', $html);
		$this->assertStringContainsString('id="Item"', $html);
		$this->assertStringContainsString('name="Item"', $html);
		$this->assertSame([], $cs->callbackControls);
	}
}
