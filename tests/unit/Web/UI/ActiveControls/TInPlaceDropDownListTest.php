<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\IO\TTextWriter;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TInPlaceDropDownList;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TListItem;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;

class TInPlaceDropDownListTest extends TestCase
{
	private $_obLevel;

	protected function setUp(): void
	{
		$this->_obLevel = ob_get_level();
	}

	protected function tearDown(): void
	{
		while (ob_get_level() > $this->_obLevel) {
			ob_end_clean();
		}
	}

	public function testExtendsTActiveDropDownList()
	{
		$this->assertInstanceOf(TActiveDropDownList::class, new TInPlaceDropDownList());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$control = new TInPlaceDropDownList();
		$this->assertInstanceOf(IActiveControl::class, $control);
		$this->assertInstanceOf(ICallbackEventHandler::class, $control);
	}

	public function testAutoPostBackDefaultsToTrue()
	{
		$control = new TInPlaceDropDownList();
		$this->assertTrue($control->getAutoPostBack());
	}

	public function testAutoHideEditor()
	{
		$control = new TInPlaceDropDownList();
		$this->assertTrue($control->getAutoHideEditor());
		$control->setAutoHideEditor(false);
		$this->assertFalse($control->getAutoHideEditor());
	}

	public function testDisplayEditor()
	{
		$control = new TInPlaceDropDownList();
		$this->assertFalse($control->getDisplayEditor());
		$control->setDisplayEditor(true);
		$this->assertTrue($control->getDisplayEditor());
	}

	public function testReadOnly()
	{
		$control = new TInPlaceDropDownList();
		$this->assertFalse($control->getReadOnly());
		$control->setReadOnly(true);
		$this->assertTrue($control->getReadOnly());
	}

	public function testEmptyDisplayText()
	{
		$control = new TInPlaceDropDownList();
		$this->assertSame('', $control->getEmptyDisplayText());
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('(none)', $control->getEmptyDisplayText());
	}

	public function testEmptyDisplayTextIsUnchangedWhenSetToTheSameValue()
	{
		$control = new TInPlaceDropDownList();
		$control->setEmptyDisplayText('(none)');
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('(none)', $control->getEmptyDisplayText());
	}

	public function testItemListStartsUnchanged()
	{
		// onPreRender refreshes the label only when the item list changed;
		// the callback path itself is covered by the functional test.
		$control = new TInPlaceDropDownList();
		$control->getItems()->add('Alpha');
		$this->assertFalse($control->getItems()->getListHasChanged());
	}

	public function testAutoHideEditorAndDisplayEditorComeFromTheTrait()
	{
		$control = new TInPlaceDropDownList();
		$this->assertTrue(method_exists($control, 'getAutoHideEditor'));
		$this->assertTrue(method_exists($control, 'getDisplayEditor'));
		// The textbox-flavored names belong to TInPlaceTextBox only.
		$this->assertFalse(method_exists($control, 'getAutoHideTextBox'));
	}

	public function testEditTriggerControlID()
	{
		$control = new TInPlaceDropDownList();
		$this->assertNull($control->getEditTriggerControlID());
		$control->setEditTriggerControlID('editButton');
		$this->assertSame('editButton', $control->getEditTriggerControlID());
	}

	public function testLabelClientID()
	{
		$control = new TInPlaceDropDownList();
		$control->setID('dropdown');
		$this->assertSame(
			$control->getClientID() . '__label',
			PradoUnit::invoke($control, 'getLabelClientID')
		);
	}

	public function testClientClassName()
	{
		$control = new TInPlaceDropDownList();
		$this->assertSame(
			'Prado.WebUI.TInPlaceDropDownList',
			PradoUnit::invoke($control, 'getClientClassName')
		);
	}

	// --- getSelectedDisplayText ---

	public function testSelectedDisplayTextWithoutItemsIsEmptyDisplayText()
	{
		$control = new TInPlaceDropDownList();
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('(none)', PradoUnit::invoke($control, 'getSelectedDisplayText'));
	}

	public function testSelectedDisplayTextIsSelectedItemText()
	{
		$control = new TInPlaceDropDownList();
		$control->getItems()->add('Alpha');
		$control->getItems()->add('Beta');
		$control->setSelectedIndex(1);
		$this->assertSame('Beta', PradoUnit::invoke($control, 'getSelectedDisplayText'));
	}

	public function testSelectedDisplayTextIsHtmlEncoded()
	{
		$control = new TInPlaceDropDownList();
		$control->getItems()->add('a < b & c');
		$control->setSelectedIndex(0);
		// THttpUtility::htmlEncode translates angle brackets and quotes only,
		// matching the option text rendering of TListControl.
		$this->assertSame('a &lt; b & c', PradoUnit::invoke($control, 'getSelectedDisplayText'));
	}

	public function testSelectedItemTextIsEmptyWithoutSelection()
	{
		$control = new TInPlaceDropDownList();
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextIsTheEncodedItemText()
	{
		$control = new TInPlaceDropDownList();
		$control->getItems()->add('a < b');
		$control->setSelectedIndex(0);
		$this->assertSame('a &lt; b', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	// --- renderLabel ---

	private function renderLabel(TInPlaceDropDownList $control): string
	{
		$textWriter = new TTextWriter();
		PradoUnit::invoke($control, 'renderLabel', new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	public function testRenderLabelShowsSelectedText()
	{
		$control = new TInPlaceDropDownList();
		$control->setID('dropdown');
		$control->getItems()->add('Alpha');
		$control->setSelectedIndex(0);
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('id="' . $control->getClientID() . '__label"', $label);
		$this->assertStringContainsString('>Alpha<', $label);
		$this->assertStringNotContainsString('data-prado-empty', $label);
	}

	public function testRenderLabelMarksTheEmptyDisplayText()
	{
		$control = new TInPlaceDropDownList();
		$control->setEmptyDisplayText('(none)');
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('data-prado-empty="1"', $label);
		$this->assertStringContainsString('>(none)<', $label);
	}

	public function testRenderLabelCarriesTheControlStyleAndToolTip()
	{
		$control = new TInPlaceDropDownList();
		$control->setCssClass('editable');
		$control->getStyle()->setCustomStyle('color:red');
		$control->setToolTip('click to edit');
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('class="editable"', $label);
		$this->assertStringContainsString('color:red', $label);
		$this->assertStringContainsString('title="click to edit"', $label);
	}

	public function testRenderLabelIsAnOperableButtonWhenEditable()
	{
		$control = new TInPlaceDropDownList();
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('role="button"', $label);
		$this->assertStringContainsString('tabindex="0"', $label);
		$this->assertStringContainsString('aria-live="polite"', $label);
	}

	public function testRenderLabelIsPlainTextWhenReadOnly()
	{
		$control = new TInPlaceDropDownList();
		$control->setReadOnly(true);
		$label = $this->renderLabel($control);
		$this->assertStringNotContainsString('role="button"', $label);
		$this->assertStringNotContainsString('tabindex', $label);
		$this->assertStringContainsString('aria-live="polite"', $label);
	}

	public function testPostBackOptionsCarryEditorLabelFromToolTip()
	{
		$control = new TInPlaceDropDownList();
		$control->setToolTip('Pick a color');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Pick a color', $options['EditorLabel']);
	}

	public function testEditorLabelDefaultsWhenToolTipIsEmpty()
	{
		// The editor must never be nameless; without a translation module
		// Prado::localize() returns the English literal
		$control = new TInPlaceDropDownList();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Edit value', $options['EditorLabel']);
	}

	// --- getPostBackOptions ---

	public function testPostBackOptions()
	{
		$control = new TInPlaceDropDownList();
		$control->setID('dropdown');
		$control->setEmptyDisplayText('(none)');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame($control->getClientID() . '__label', $options['ID']);
		$this->assertSame($control->getClientID(), $options['EditorID']);
		$this->assertSame('', $options['ExternalControl']);
		$this->assertTrue($options['AutoHide']);
		$this->assertTrue($options['AutoPostBack']);
		$this->assertSame('(none)', $options['EmptyDisplayText']);
		$this->assertFalse($options['DisplayEditor']);
		$this->assertFalse($options['ReadOnly']);
		$this->assertSame($control->getUniqueID(), $options['EventTarget']);
		$this->assertArrayNotHasKey('LoadItemsOnEdit', $options);
	}

	public function testPostBackOptionsWithFalseFlags()
	{
		$control = new TInPlaceDropDownList();
		$control->setAutoHideEditor(false);
		$control->setAutoPostBack(false);
		$control->setReadOnly(true);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertFalse($options['AutoHide']);
		$this->assertFalse($options['AutoPostBack']);
		$this->assertTrue($options['ReadOnly']);
	}

	public function testPostBackOptionsCarryDisplayEditor()
	{
		$control = new TInPlaceDropDownList();
		$control->setDisplayEditor(true);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertTrue($options['DisplayEditor']);
	}

	public function testPostBackOptionsWithLoadingItemsHandler()
	{
		$control = new TInPlaceDropDownList();
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) {
		});
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertTrue($options['LoadItemsOnEdit']);
	}

	// --- callback events ---

	public function testOnCallbackRaisesOnCallbackEvent()
	{
		$control = new TInPlaceDropDownList();
		$raised = false;
		$control->attachEventHandler('OnCallback', function ($sender, $param) use (&$raised) {
			$raised = true;
		});
		$param = new TCallbackEventParameter($control->getResponse(), 'value');
		$control->onCallback($param);
		$this->assertTrue($raised);
	}

	public function testOnCallbackRaisesOnLoadingItemsForLoadItemsAction()
	{
		$control = new TInPlaceDropDownList();
		$loadingValue = null;
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) use (&$loadingValue) {
			$loadingValue = $param->getCallbackParameter();
		});
		$param = new TCallbackEventParameter(
			$control->getResponse(),
			['__InlineEditor_loadItems__', 'current']
		);
		$control->onCallback($param);
		$this->assertSame('current', $loadingValue);
	}

	public function testOnCallbackDoesNotRaiseOnLoadingItemsForPlainParameter()
	{
		$control = new TInPlaceDropDownList();
		$raised = false;
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) use (&$raised) {
			$raised = true;
		});
		$param = new TCallbackEventParameter($control->getResponse(), 'current');
		$control->onCallback($param);
		$this->assertFalse($raised);
	}

	public function testOnCallbackLoadsItemsWithoutAValue()
	{
		$control = new TInPlaceDropDownList();
		$values = [];
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) use (&$values) {
			$values[] = $param->getCallbackParameter();
		});
		$control->onCallback(new TCallbackEventParameter(null, ['__InlineEditor_loadItems__']));
		$this->assertSame([null], $values);
	}

	private function createList(bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$list = $page->addControl(new TInPlaceDropDownList(), 'Pick');
		foreach (['a' => 'Apple', '' => '', 'c' => '<C>'] as $value => $text) {
			$list->getItems()->add(new TListItem($text, $value));
		}
		return [$page, $list];
	}

	public function testSetReadOnlyUpdatesTheClientOnce()
	{
		[$page, $list] = $this->createList();
		$list->setReadOnly('true');
		$list->setReadOnly(true);
		$this->assertSame([['Prado.WebUI.TInPlaceDropDownList.setReadOnly' => ['Pick', true]]], $page->getClientFunctions());

		[$page, $list] = $this->createList(false);
		$list->setReadOnly(true);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertTrue($list->getReadOnly());
	}

	public function testPreRenderInACallbackUpdatesTheLabel()
	{
		[$page, $list] = $this->createList();
		$list->setSelectedIndex(2);
		$list->onPreRender(null);
		$this->assertContains('inlineeditor', $page->cs->pradoScripts);
		$functions = $page->getClientFunctions();
		$this->assertSame(['Prado.WebUI.TInPlaceDropDownList.setLabelText' => ['Pick', '&lt;C&gt;']], end($functions));

		[$page, $list] = $this->createList(false);
		$list->onPreRender(null);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSelectedItemWithoutTextShowsTheEmptyDisplayText()
	{
		[, $list] = $this->createList(false);
		$list->setSelectedIndex(1);
		$list->setEmptyDisplayText('(none)');
		$this->assertSame('', PradoUnit::invoke($list, 'getSelectedItemText'));
		$this->assertSame('(none)', PradoUnit::invoke($list, 'getSelectedDisplayText'));
	}

	public function testRenderShowsTheLabelAndTheHiddenSelect()
	{
		[$page, $list] = $this->createList();
		$list->setSelectedIndex(0);
		$list->setToolTip('Fruit');
		$list->getStyle()->setCustomStyle('color:red');
		$writer = new THtmlWriter(new TTextWriter());
		$list->render($writer);
		$html = $writer->flush();
		$this->assertStringStartsWith('<span id="Pick__label" title="Fruit" aria-live="polite" role="button" tabindex="0" style="color:red;">Apple</span><select', $html);
		$this->assertMatchesRegularExpression('/<select[^>]* id="Pick"[^>]* style="[^"]*display:none;/', $html);
		$this->assertSame('Prado.WebUI.TInPlaceDropDownList', $page->cs->callbackControls[0][0]);
		$this->assertSame('Fruit', $page->cs->callbackControls[0][1]['EditorLabel']);
	}

	public static function labelProvider(): array
	{
		$rows = [];
		foreach ([false, true] as $style) {
			foreach ([false, true] as $toolTip) {
				foreach ([false, true] as $displayEditor) {
					$rows[sprintf('style %d tooltip %d editor %d', $style, $toolTip, $displayEditor)] = [$style, $toolTip, $displayEditor];
				}
			}
		}
		return $rows;
	}

	/**
	 * @dataProvider labelProvider
	 */
	public function testRenderLabelAttributes(bool $style, bool $toolTip, bool $displayEditor)
	{
		[, $list] = $this->createList(false);
		if ($style) {
			$list->getStyle()->setCustomStyle('color:red');
		}
		if ($toolTip) {
			$list->setToolTip('Fruit');
		}
		$list->setDisplayEditor($displayEditor);
		$writer = new THtmlWriter(new TTextWriter());
		PradoUnit::invoke($list, 'renderLabel', $writer);
		$html = $writer->flush();
		$this->assertSame($toolTip, str_contains($html, 'title="Fruit"'));
		$this->assertSame($style, str_contains($html, 'color:red'));
		$this->assertSame($displayEditor, str_contains($html, 'display:none'));
	}

	public function testPostBackOptionsWithAToolTipAndALoadingItemsHandler()
	{
		[, $list] = $this->createList(false);
		$list->setToolTip('Fruit');
		$list->attachEventHandler('OnLoadingItems', fn () => null);
		$options = PradoUnit::invoke($list, 'getPostBackOptions');
		$this->assertSame('Fruit', $options['EditorLabel']);
		$this->assertTrue($options['LoadItemsOnEdit']);
	}

	public function testRenderWithTheEditorDisplayedHidesTheLabel()
	{
		[, $list] = $this->createList();
		$list->setDisplayEditor(true);
		$list->setReadOnly(true);
		$writer = new THtmlWriter(new TTextWriter());
		$list->render($writer);
		$html = $writer->flush();
		$this->assertStringStartsWith('<span id="Pick__label" aria-live="polite" style="display:none;">', $html);
		$this->assertDoesNotMatchRegularExpression('/<select[^>]*display:none/', $html);
	}
}
