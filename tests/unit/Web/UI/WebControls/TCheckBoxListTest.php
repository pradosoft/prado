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

	// ================================================================================
	// Basics
	// ================================================================================

	public function testInterfaces()
	{
		$list = new TCheckBoxList();
		$this->assertInstanceOf(IRepeatInfoUser::class, $list);
		$this->assertInstanceOf(INamingContainer::class, $list);
		$this->assertInstanceOf(IPostBackDataHandler::class, $list);
		$this->assertInstanceOf(IValidatable::class, $list);
	}

	public function testConstructorAddsRepeatedControl()
	{
		$list = new TCheckBoxList();
		$this->assertSame(1, $list->getControls()->getCount());
		$item = $list->getControls()->itemAt(0);
		$this->assertInstanceOf(TCheckBoxItem::class, $item);
		$this->assertSame('c0', $item->getID());
		$this->assertFalse($item->getEnableViewState());
		$this->assertSame($item, PradoUnit::getProp($list, '_repeatedControl'));
	}

	public function testCreateRepeatedControl()
	{
		$this->assertInstanceOf(TCheckBoxItem::class, PradoUnit::invoke(new TCheckBoxList(), 'createRepeatedControl'));
	}

	public function testFindControlReturnsSelf()
	{
		[, $list] = $this->createPageList();
		$this->assertSame($list, $list->findControl('anything'));
		$this->assertSame($list, $list->findControl('c0'));
	}

	public function testFindControlRealSearchesChildren()
	{
		[, $list] = $this->createPageList();
		$this->assertSame(PradoUnit::getProp($list, '_repeatedControl'), $list->findControl('c0', true));
		$this->assertNull($list->findControl('missing', true));
	}

	public function testProtectedDefaults()
	{
		$list = new TCheckBoxList();
		$this->assertTrue(PradoUnit::invoke($list, 'getIsMultiSelect'));
		$this->assertInstanceOf(TTableStyle::class, PradoUnit::invoke($list, 'createStyle'));
		$this->assertInstanceOf(TTableStyle::class, $list->getStyle());
		$this->assertSame('Prado.WebUI.TCheckBoxList', PradoUnit::invoke($list, 'getClientClassName'));
		$this->assertSame('group', PradoUnit::invoke($list, 'getGroupRole'));
		$this->assertNull($list->generateItemStyle(TListItemType::Item, 0));
	}

	public function testRepeatInfoUserFlags()
	{
		$list = new TCheckBoxList();
		$this->assertFalse($list->getHasHeader());
		$this->assertFalse($list->getHasFooter());
		$this->assertFalse($list->getHasSeparators());
	}

	public function testGetPostBackOptions()
	{
		[, $list] = $this->createPageList();
		$list->setValidationGroup('g');
		$list->setCausesValidation(false);
		$this->assertSame([
			'ID' => 'List',
			'ValidationGroup' => 'g',
			'CausesValidation' => false,
			'ListName' => 'List',
			'ItemCount' => 3,
		], PradoUnit::invoke($list, 'getPostBackOptions'));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testTextAlign()
	{
		$list = new TCheckBoxList();
		$this->assertSame(TTextAlign::Right, $list->getTextAlign());
		$list->setTextAlign('Left');
		$this->assertSame(TTextAlign::Left, $list->getTextAlign());
	}

	public function testTextAlignInvalid()
	{
		$list = new TCheckBoxList();
		$this->expectException(TInvalidDataValueException::class);
		$list->setTextAlign('Center');
	}

	public function testGetRepeatInfoCreatesOnce()
	{
		$list = new TCheckBoxList();
		$info = PradoUnit::invoke($list, 'getRepeatInfo');
		$this->assertInstanceOf(TRepeatInfo::class, $info);
		$this->assertSame($info, PradoUnit::invoke($list, 'getRepeatInfo'));
		$this->assertSame($info, $list->getViewState('RepeatInfo'));
	}

	public function testRepeatProperties()
	{
		$list = new TCheckBoxList();
		$this->assertSame(0, $list->getRepeatColumns());
		$this->assertSame(TRepeatDirection::Vertical, $list->getRepeatDirection());
		$this->assertSame(TRepeatLayout::Table, $list->getRepeatLayout());

		$list->setRepeatColumns(3);
		$list->setRepeatDirection(TRepeatDirection::Horizontal);
		$list->setRepeatLayout(TRepeatLayout::Flow);
		$this->assertSame(3, $list->getRepeatColumns());
		$this->assertSame(TRepeatDirection::Horizontal, $list->getRepeatDirection());
		$this->assertSame(TRepeatLayout::Flow, $list->getRepeatLayout());
		$info = PradoUnit::invoke($list, 'getRepeatInfo');
		$this->assertSame(3, $info->getRepeatColumns());
	}

	public function testCellSpacingAndPadding()
	{
		$list = new TCheckBoxList();
		$this->assertSame(-1, $list->getCellSpacing());
		$this->assertSame(-1, $list->getCellPadding());
		$this->assertFalse($list->getHasStyle());

		$list->setCellSpacing(2);
		$list->setCellPadding(5);
		$this->assertSame(2, $list->getCellSpacing());
		$this->assertSame(5, $list->getCellPadding());
	}

	public function testCellPaddingWithStyleButNoValue()
	{
		$list = new TCheckBoxList();
		$list->setCellSpacing(1);
		$this->assertSame(-1, $list->getCellPadding());
	}

	public function testGetSpanNeeded()
	{
		$list = new TCheckBoxList();
		$this->assertFalse(PradoUnit::invoke($list, 'getSpanNeeded'));
		$list->setRepeatLayout(TRepeatLayout::Flow);
		$this->assertFalse(PradoUnit::invoke($list, 'getSpanNeeded'));
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$this->assertTrue(PradoUnit::invoke($list, 'getSpanNeeded'));
	}

	public function testIsValid()
	{
		$list = new TCheckBoxList();
		$this->assertTrue($list->getIsValid());
		$list->setIsValid('false');
		$this->assertFalse($list->getIsValid());
	}

	public function testGetValidationPropertyValue()
	{
		[, $list] = $this->createPageList();
		$this->assertSame('', $list->getValidationPropertyValue());
		$list->setSelectedValues(['c', 'b']);
		$this->assertSame('b', $list->getValidationPropertyValue());
	}

	public function testMultipleSelectionSupported()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndices([0, 2]);
		$this->assertSame(['a', 'c'], $list->getSelectedValues());
	}

	// ================================================================================
	// setEnabled
	// ================================================================================

	public function testSetEnabled()
	{
		[, $list] = $this->createPageList();
		$list->setEnabled('false');
		$this->assertFalse($list->getEnabled());
		$list->setEnabled(true);
		$this->assertTrue($list->getEnabled());
	}

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

	public function testSetEnabledActiveOnCallbackUpdatesItems()
	{
		[$page, $list] = $this->createActivePageList(true);
		$list->setEnabled(false);
		$this->assertFalse($list->getEnabled());
		$list->setEnabled(true);
		$this->assertSame([
			['List_c0', 'disabled', true],
			['List_c1', 'disabled', true],
			['List_c0', 'disabled', false],
			['List_c1', 'disabled', false],
		], $page->getCallbackClient()->getAttributes());
	}

	public function testSetEnabledActiveWithoutCallback()
	{
		[$page, $list] = $this->createActivePageList(false);
		$list->setEnabled(false);
		$this->assertFalse($list->getEnabled());
		$this->assertSame([], $page->getCallbackClient()->getAttributes());
	}

	public function testSetEnabledActiveCannotUpdateClientSide()
	{
		[$page, $list] = $this->createActivePageList(true);
		$list->getActiveControl()->canUpdate = false;
		$list->setEnabled(false);
		$this->assertSame([], $page->getCallbackClient()->getAttributes());
	}

	// ================================================================================
	// Post data
	// ================================================================================

	public function testLoadPostDataChecksItem()
	{
		[, $list] = $this->createPageList();
		$this->assertFalse($list->getDataChanged());
		$this->assertTrue($list->loadPostData('List$c1', ['List$c1' => 'b']));
		$this->assertSame([1], $list->getSelectedIndices());
		$this->assertTrue($list->getDataChanged());
	}

	public function testLoadPostDataUnchecksItem()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndices([0, 2]);
		$this->assertTrue($list->loadPostData('List$c2', []));
		$this->assertSame([0], $list->getSelectedIndices());
	}

	public function testLoadPostDataReportsChangeOnlyOnce()
	{
		[, $list] = $this->createPageList();
		$values = ['List$c0' => 'a', 'List$c2' => 'c'];
		$this->assertTrue($list->loadPostData('List$c0', $values));
		$this->assertFalse($list->loadPostData('List$c1', $values));
		$this->assertFalse($list->loadPostData('List$c2', $values));
		$this->assertSame([0, 2], $list->getSelectedIndices());
	}

	public function testLoadPostDataUnchangedItem()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndices([1]);
		$this->assertFalse($list->loadPostData('List$c1', ['List$c1' => 'b']));
		$this->assertFalse($list->loadPostData('List$c0', []));
		$this->assertSame([1], $list->getSelectedIndices());
		$this->assertFalse($list->getDataChanged());
	}

	public function testLoadPostDataDisabledItemIsIgnored()
	{
		[, $list] = $this->createPageList();
		$list->getItems()->itemAt(1)->setEnabled(false);
		$this->assertFalse($list->loadPostData('List$c1', ['List$c1' => 'b']));
		$this->assertSame([], $list->getSelectedIndices());
	}

	public function testLoadPostDataOutOfRangeIndexIsIgnored()
	{
		[, $list] = $this->createPageList();
		$this->assertFalse($list->loadPostData('List$c9', ['List$c9' => 'x']));
		$this->assertSame([], $list->getSelectedIndices());
	}

	public function testLoadPostDataDisabledListIsIgnored()
	{
		[, $list] = $this->createPageList();
		$list->setEnabled(false);
		$this->assertFalse($list->loadPostData('List$c0', ['List$c0' => 'a']));
		$this->assertSame([], $list->getSelectedIndices());
	}

	public function testLoadPostDataDisabledByParentIsIgnored()
	{
		$page = new TPage();
		$panel = new TPanel();
		$panel->setID('P');
		$page->getControls()->add($panel);
		$list = new TCheckBoxList();
		$list->setID('List');
		$list->getItems()->add(new TListItem('A', 'a'));
		$panel->getControls()->add($list);
		$panel->setEnabled(false);
		$this->assertFalse($list->loadPostData('P$List$c0', ['P$List$c0' => 'a']));
		$this->assertSame([], $list->getSelectedIndices());
	}

	// ================================================================================
	// Events
	// ================================================================================

	public function testRaisePostDataChangedEventRaisesSelectedIndexChanged()
	{
		[$page, $list] = $this->createPageList();
		$raised = [];
		$list->attachEventHandler('OnSelectedIndexChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$list->raisePostDataChangedEvent();
		$this->assertSame([[$list, null]], $raised);
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	public function testRaisePostDataChangedEventValidatesOnAutoPostBack()
	{
		[$page, $list] = $this->createPageList();
		$list->setAutoPostBack(true);
		$list->raisePostDataChangedEvent();
		$this->assertTrue(PradoUnit::getProp($page, '_validated'));
	}

	public function testRaisePostDataChangedEventSkipsValidationWithoutCausesValidation()
	{
		[$page, $list] = $this->createPageList();
		$list->setAutoPostBack(true);
		$list->setCausesValidation(false);
		$list->raisePostDataChangedEvent();
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	// ================================================================================
	// onPreRender
	// ================================================================================

	public function testOnPreRenderConfiguresRepeatedControlAndRegistersItems()
	{
		[$page, $list] = $this->createPageList();
		$list->setAutoPostBack(true);
		$list->setCausesValidation(false);
		$list->setValidationGroup('g');
		$list->onPreRender(null);

		$item = PradoUnit::getProp($list, '_repeatedControl');
		$this->assertTrue($item->getAutoPostBack());
		$this->assertFalse($item->getCausesValidation());
		$this->assertSame('g', $item->getValidationGroup());
		$this->assertSame(
			['List$c0' => true, 'List$c1' => true, 'List$c2' => true],
			PradoUnit::getProp($page, '_controlsRegisteredForPostData')
		);
	}

	public function testOnPreRenderWithoutItemsRegistersNothing()
	{
		[$page, $list] = $this->createPageList([]);
		$list->onPreRender(null);
		$this->assertSame([], PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testAddGroupAttributesToRender()
	{
		[, $list] = $this->createPageList();
		$writer = new THtmlWriter(new TTextWriter());
		PradoUnit::invoke($list, 'addGroupAttributesToRender', $writer);
		$this->assertSame(['role' => 'group'], PradoUnit::getProp($writer, '_attributes'));

		$list->setToolTip('Pick some');
		$writer = new THtmlWriter(new TTextWriter());
		PradoUnit::invoke($list, 'addGroupAttributesToRender', $writer);
		$this->assertSame(['role' => 'group', 'aria-label' => 'Pick some'], PradoUnit::getProp($writer, '_attributes'));
	}

	public function testRenderWithoutItemsRendersNothing()
	{
		[, $list] = $this->createPageList([]);
		$this->assertSame('', $this->render($list));
	}

	public function testRenderRawWithoutItemsRendersEmptyGroupSpan()
	{
		[, $list] = $this->createPageList([]);
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$list->setToolTip('Tip');
		$this->assertSame('<span id="List" role="group" aria-label="Tip"></span>', $this->render($list));
	}

	public function testRenderTableLayout()
	{
		[, $list] = $this->createPageList();
		$list->getItems()->itemAt(1)->setSelected(true);
		$list->setToolTip('Tip');
		$output = $this->render($list);
		$this->assertMatchesRegularExpression('/^<table [^>]*id="List"[^>]*role="group" aria-label="Tip">/', $output);
		$this->assertStringEndsWith('</table>', $output);
		$this->assertSame(3, substr_count($output, '<td>'));
		$this->assertStringContainsString(
			'<input id="List_c0" type="checkbox" value="a" name="List$c0" /><label for="List_c0">A</label>',
			$output
		);
		$this->assertStringContainsString(
			'<input id="List_c1" type="checkbox" value="b" name="List$c1" checked="checked" />',
			$output
		);
	}

	public function testRenderFlowLayout()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setRepeatLayout(TRepeatLayout::Flow);
		$output = $this->render($list);
		$this->assertMatchesRegularExpression('/^<span [^>]*id="List"[^>]*role="group">/', $output);
		$this->assertStringContainsString('<br/>', $output);
		$this->assertStringNotContainsString('<table', $output);
	}

	public function testRenderRawLayout()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$list->setToolTip('Tip');
		$output = $this->render($list);
		$this->assertStringStartsWith('<span id="List" role="group" aria-label="Tip">', $output);
		$this->assertStringEndsWith('<label for="List_c1">B</label></span></span>', $output);
		$this->assertSame(1, substr_count($output, 'role="group"'));
	}

	public function testRenderTextAlignLeft()
	{
		[, $list] = $this->createPageList(['a' => 'A']);
		$list->setTextAlign(TTextAlign::Left);
		$output = $this->render($list);
		$this->assertStringContainsString('<label for="List_c0">A</label><input id="List_c0"', $output);
	}

	public function testRenderMovesAccessKeyAndTabIndexToItems()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setAccessKey('k');
		$list->setTabIndex(3);
		$output = $this->render($list);
		$this->assertSame(2, substr_count($output, 'accesskey="k" tabindex="3"'));
		$this->assertSame('k', $list->getAccessKey());
		$this->assertSame(3, $list->getTabIndex());
	}

	public function testRenderItemAttributesAreCopiedAndCleared()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->getItems()->itemAt(0)->setAttribute('class', 'hot');
		$output = $this->render($list);
		$this->assertStringContainsString('<span class="hot" id="List_c0_parent">', $output);
		$this->assertStringContainsString('<span id="List_c1_parent">', $output);
		$this->assertSame(1, substr_count($output, 'class="hot"'));
	}

	public function testRenderDisabledItem()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->getItems()->itemAt(1)->setEnabled(false);
		$output = $this->render($list);
		$this->assertStringContainsString('name="List$c0" />', $output);
		$this->assertStringContainsString('name="List$c1" disabled="disabled" />', $output);
	}

	public function testRenderDisabledListDisablesAllItems()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setEnabled(false);
		$output = $this->render($list);
		$this->assertStringContainsString('name="List$c0" disabled="disabled" />', $output);
		$this->assertStringContainsString('name="List$c1" disabled="disabled" />', $output);
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
			$this->assertSame(['Prado.WebUI.TCheckBoxList', PradoUnit::invoke($list, 'getPostBackOptions')], $entry);
		}
	}

	public function testRenderAutoPostBackWithoutClientScript()
	{
		[$page, $list] = $this->createPageList(['a' => 'A']);
		$list->setAutoPostBack(true);
		$list->setEnableClientScript(false);
		$this->render($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}

	public function testRenderAutoPostBackDisabled()
	{
		[$page, $list] = $this->createPageList(['a' => 'A']);
		$list->setAutoPostBack(true);
		$list->setEnabled(false);
		$this->render($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}

	public function testRenderAutoPostBackWithoutJavaScriptSupport()
	{
		[$page, $list] = $this->createPageList(['a' => 'A']);
		$list->setAutoPostBack(true);
		PradoUnit::setProp($page, '_enableJavaScript', false);
		$this->render($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}

	// ================================================================================
	// TCheckBoxItem
	// ================================================================================

	public function testCheckBoxItemEmitsNoClientScript()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TCheckBoxListTestClientScript($page));
		$item = new TCheckBoxItem();
		$item->setID('Box');
		$item->setAutoPostBack(true);
		$page->getControls()->add($item);
		$output = $this->render($item);
		$this->assertStringContainsString('<input id="Box" type="checkbox"', $output);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}
}
