<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IValidatable;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\IRepeatInfoUser;
use Prado\Web\UI\WebControls\TCheckBoxItem;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRepeatDirection;
use Prado\Web\UI\WebControls\TRepeatInfo;
use Prado\Web\UI\WebControls\TRepeatLayout;
use Prado\Web\UI\WebControls\TTableStyle;
use Prado\Web\UI\WebControls\TTextAlign;

/**
 * Client script manager that records postback control registrations.
 */
class TCheckBoxListTestClientScript extends TClientScriptManager
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

/**
 * Callback client that records attribute updates.
 */
class TCheckBoxListTestCallbackClient
{
	private array $_attributes = [];

	public function getAttributes(): array
	{
		return $this->_attributes;
	}

	public function setAttribute($element, $attribute, $value)
	{
		$this->_attributes[] = [$element, $attribute, $value];
	}
}

/**
 * Page whose callback state and callback client are set by the test.
 */
class TCheckBoxListTestCallbackPage extends TPage
{
	private bool $_isCallback = false;

	private TCheckBoxListTestCallbackClient $_callbackClient;

	public function __construct()
	{
		parent::__construct();
		$this->_callbackClient = new TCheckBoxListTestCallbackClient();
	}

	public function setIsCallback(bool $value): void
	{
		$this->_isCallback = $value;
	}

	public function getIsCallback()
	{
		return $this->_isCallback;
	}

	public function getCallbackClient()
	{
		return $this->_callbackClient;
	}
}

/**
 * Active control options with a settable client-side update flag.
 */
class TCheckBoxListTestActiveOptions
{
	public bool $canUpdate = true;

	public function canUpdateClientSide()
	{
		return $this->canUpdate;
	}
}

/**
 * Checkbox list implementing IActiveControl to exercise the callback branch of setEnabled.
 */
class TCheckBoxListTestActiveList extends TCheckBoxList implements IActiveControl
{
	private ?TCheckBoxListTestActiveOptions $_activeOptions = null;

	public function getActiveControl()
	{
		if ($this->_activeOptions === null) {
			$this->_activeOptions = new TCheckBoxListTestActiveOptions();
		}
		return $this->_activeOptions;
	}
}

class TCheckBoxListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TCheckBoxListTestClientScript($page));
		$list = new TCheckBoxList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	/**
	 * Renders the control, then an empty div with the same writer, so attributes left in
	 * the writer show on the div.
	 * @param TCheckBoxList $list
	 * @return string the output
	 */
	private function renderBeforeDiv(TCheckBoxList $list): string
	{
		$textWriter = new TTextWriter();
		$writer = new THtmlWriter($textWriter);
		$list->renderControl($writer);
		$writer->renderBeginTag('div');
		$writer->renderEndTag();
		return $textWriter->flush();
	}

	// ================================================================================
	// Basics
	// ================================================================================

	// ================================================================================
	// Properties
	// ================================================================================

	// ================================================================================
	// setEnabled
	// ================================================================================

	private function createActivePageList(bool $isCallback): array
	{
		$page = new TCheckBoxListTestCallbackPage();
		$page->setIsCallback($isCallback);
		$list = new TCheckBoxListTestActiveList();
		$list->setID('List');
		$list->getItems()->add(new TListItem('A', 'a'));
		$list->getItems()->add(new TListItem('B', 'b'));
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Post data
	// ================================================================================

	// ================================================================================
	// Events
	// ================================================================================

	// ================================================================================
	// onPreRender
	// ================================================================================

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderLeavesNoAttributesInWriter()
	{
		foreach ([TRepeatLayout::Table, TRepeatLayout::Flow, TRepeatLayout::Raw] as $layout) {
			foreach ([['a' => 'A'], []] as $items) {
				[, $list] = $this->createPageList($items);
				$list->setRepeatLayout($layout);
				$list->setAutoPostBack(true);
				$list->setCssClass('cls');
				$this->assertStringEndsWith('<div></div>', $this->renderBeforeDiv($list), $layout);
			}
		}
	}

	public function testRenderContainerHasNoMultipleOrTabIndex()
	{
		foreach ([TRepeatLayout::Table, TRepeatLayout::Flow, TRepeatLayout::Raw] as $layout) {
			[, $list] = $this->createPageList(['a' => 'A']);
			$list->setRepeatLayout($layout);
			$output = $this->render($list);
			$this->assertStringNotContainsString('multiple', $output, $layout);
			$this->assertStringNotContainsString('tabindex', $output, $layout);
		}
	}

	public function testGetTagName()
	{
		$list = new TCheckBoxList();
		$this->assertSame('table', PradoUnit::invoke($list, 'getTagName'));
		$list->setRepeatLayout(TRepeatLayout::Flow);
		$this->assertSame('span', PradoUnit::invoke($list, 'getTagName'));
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$this->assertSame('span', PradoUnit::invoke($list, 'getTagName'));
	}

	// ================================================================================
	// TCheckBoxItem
	// ================================================================================

}
