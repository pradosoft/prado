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

	// ================================================================================
	// Callback
	// ================================================================================

	// ================================================================================
	// Selection during a callback
	// ================================================================================

	// ================================================================================
	// Rendering
	// ================================================================================

	// ================================================================================
	// TActiveCheckBoxListItem
	// ================================================================================


	public function testRenderListWithItemsUsesContainerIdOnce()
	{
		[, $list] = $this->createPageList(false);
		$html = $this->renderControl($list);
		$this->assertStringStartsWith('<table id="List"', $html);
		$this->assertSame(1, substr_count($html, 'id="List"'));
	}

	public function testRenderEmptyListKeepsTheSpan()
	{
		[, $list] = $this->createPageList(false, []);
		$this->assertStringStartsWith('<span id="List"', $this->renderControl($list));
	}
}
