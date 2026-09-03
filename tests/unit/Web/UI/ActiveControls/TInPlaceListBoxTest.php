<?php

use Prado\IO\TTextWriter;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveListBox;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TInPlaceListBox;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TListSelectionMode;
use PHPUnit\Framework\TestCase;

class TInPlaceListBoxTest extends TestCase
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

	private function multiSelectListBox(): TInPlaceListBox
	{
		$control = new TInPlaceListBox();
		$control->setID('listbox');
		$control->setSelectionMode(TListSelectionMode::Multiple);
		$control->getItems()->add('Alpha');
		$control->getItems()->add('Beta');
		$control->getItems()->add('Gamma');
		return $control;
	}

	public function testExtendsTActiveListBox()
	{
		$this->assertInstanceOf(TActiveListBox::class, new TInPlaceListBox());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$control = new TInPlaceListBox();
		$this->assertInstanceOf(IActiveControl::class, $control);
		$this->assertInstanceOf(ICallbackEventHandler::class, $control);
	}

	public function testInPlaceFamilyProperties()
	{
		$control = new TInPlaceListBox();
		$this->assertTrue($control->getAutoHideEditor());
		$this->assertFalse($control->getDisplayEditor());
		$this->assertFalse($control->getReadOnly());
		$this->assertSame('', $control->getEmptyDisplayText());
	}

	public function testSelectionSeparatorDefaultsToCommaSpace()
	{
		$control = new TInPlaceListBox();
		$this->assertSame(', ', $control->getSelectionSeparator());
		$control->setSelectionSeparator(' | ');
		$this->assertSame(' | ', $control->getSelectionSeparator());
	}

	public function testClientClassName()
	{
		$control = new TInPlaceListBox();
		$this->assertSame(
			'Prado.WebUI.TInPlaceListBox',
			PradoUnit::invoke($control, 'getClientClassName')
		);
	}

	// --- getSelectedItemText: joined, encoded ---

	public function testSelectedItemTextIsEmptyWithoutSelection()
	{
		$control = $this->multiSelectListBox();
		$this->assertSame('', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextJoinsSelectedTexts()
	{
		$control = $this->multiSelectListBox();
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(2)->setSelected(true);
		$this->assertSame('Alpha, Gamma', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextUsesTheSeparator()
	{
		$control = $this->multiSelectListBox();
		$control->setSelectionSeparator(' | ');
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		$this->assertSame('Alpha | Beta', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextEncodesEachText()
	{
		$control = new TInPlaceListBox();
		$control->setSelectionMode(TListSelectionMode::Multiple);
		$control->getItems()->add('a < b');
		$control->getItems()->add('c > d');
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		$this->assertSame('a &lt; b, c &gt; d', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextSkipsEmptyTextItems()
	{
		$control = new TInPlaceListBox();
		$control->setSelectionMode(TListSelectionMode::Multiple);
		$control->getItems()->add('');
		$control->getItems()->add('Apple');
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		// The empty-text item contributes no text and no stray separator.
		$this->assertSame('Apple', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextHonorsAnEmptySeparator()
	{
		$control = new TInPlaceListBox();
		$control->setSelectionMode(TListSelectionMode::Multiple);
		$control->setSelectionSeparator('');
		$control->getItems()->add('A');
		$control->getItems()->add('B');
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		$this->assertSame('AB', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	public function testSelectedItemTextEncodesAnHtmlSpecialSeparator()
	{
		$control = new TInPlaceListBox();
		$control->setSelectionMode(TListSelectionMode::Multiple);
		$control->setSelectionSeparator('<br>');
		$control->getItems()->add('A');
		$control->getItems()->add('B');
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		// The separator is encoded like the item texts, matching the client;
		// it does not render as live markup.
		$this->assertSame('A&lt;br&gt;B', PradoUnit::invoke($control, 'getSelectedItemText'));
	}

	// --- renderLabel ---

	private function renderLabel(TInPlaceListBox $control): string
	{
		$textWriter = new TTextWriter();
		PradoUnit::invoke($control, 'renderLabel', new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	public function testRenderLabelShowsJoinedSelection()
	{
		$control = $this->multiSelectListBox();
		$control->getItems()->itemAt(0)->setSelected(true);
		$control->getItems()->itemAt(1)->setSelected(true);
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('id="' . $control->getClientID() . '__label"', $label);
		$this->assertStringContainsString('>Alpha, Beta<', $label);
		$this->assertStringNotContainsString('data-prado-empty', $label);
	}

	public function testRenderLabelMarksTheEmptyDisplayText()
	{
		$control = $this->multiSelectListBox();
		$control->setEmptyDisplayText('(none selected)');
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('data-prado-empty="1"', $label);
		$this->assertStringContainsString('>(none selected)<', $label);
	}


	public function testRenderLabelIsAnOperableButtonWhenEditable()
	{
		$control = $this->multiSelectListBox();
		$label = $this->renderLabel($control);
		$this->assertStringContainsString('role="button"', $label);
		$this->assertStringContainsString('tabindex="0"', $label);
		$this->assertStringContainsString('aria-live="polite"', $label);
	}

	public function testRenderLabelIsPlainTextWhenReadOnly()
	{
		$control = $this->multiSelectListBox();
		$control->setReadOnly(true);
		$label = $this->renderLabel($control);
		$this->assertStringNotContainsString('role="button"', $label);
		$this->assertStringNotContainsString('tabindex', $label);
		$this->assertStringContainsString('aria-live="polite"', $label);
	}

	public function testPostBackOptionsCarryEditorLabelFromToolTip()
	{
		$control = $this->multiSelectListBox();
		$control->setToolTip('Pick a color');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Pick a color', $options['EditorLabel']);
	}

	public function testEditorLabelDefaultsWhenToolTipIsEmpty()
	{
		// The editor must never be nameless; without a translation module
		// Prado::localize() returns the English literal
		$control = $this->multiSelectListBox();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Edit value', $options['EditorLabel']);
	}

	// --- getPostBackOptions ---

	public function testPostBackOptions()
	{
		$control = $this->multiSelectListBox();
		$control->setEmptyDisplayText('(none)');
		$control->setSelectionSeparator(' / ');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame($control->getClientID() . '__label', $options['ID']);
		$this->assertSame($control->getClientID(), $options['EditorID']);
		$this->assertTrue($options['AutoHide']);
		$this->assertTrue($options['AutoPostBack']);
		$this->assertSame('(none)', $options['EmptyDisplayText']);
		$this->assertFalse($options['DisplayEditor']);
		$this->assertFalse($options['ReadOnly']);
		$this->assertSame(' / ', $options['SelectionSeparator']);
	}

	public function testPostBackOptionsWithLoadingItemsHandler()
	{
		$control = $this->multiSelectListBox();
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) {
		});
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertTrue($options['LoadItemsOnEdit']);
	}

	// --- callback events ---

	public function testOnCallbackRaisesOnCallbackEvent()
	{
		$control = $this->multiSelectListBox();
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
		$control = $this->multiSelectListBox();
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

	public function testOnCallbackWithEmptyArrayParameterDoesNotWarnOrMisfire()
	{
		// The list box sends an array selection snapshot; an empty selection is
		// []. onCallback must not raise OnLoadingItems or warn on a missing key 0.
		$control = $this->multiSelectListBox();
		$loadingRaised = false;
		$control->attachEventHandler('OnLoadingItems', function ($sender, $param) use (&$loadingRaised) {
			$loadingRaised = true;
		});
		$param = new TCallbackEventParameter($control->getResponse(), []);
		$control->onCallback($param);
		$this->assertFalse($loadingRaised);
	}
}
