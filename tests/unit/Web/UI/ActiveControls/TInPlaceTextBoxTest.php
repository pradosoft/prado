<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TInPlaceTextBox;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TButton;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;

class TInPlaceTextBoxTest extends TestCase
{
	private function renderContents(TInPlaceTextBox $control): string
	{
		$textWriter = new TTextWriter();
		$control->renderContents(new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	public function testEmptyDisplayText()
	{
		$control = new TInPlaceTextBox();
		$this->assertSame('', $control->getEmptyDisplayText());
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('(none)', $control->getEmptyDisplayText());
	}

	public function testRenderContentsShowsText()
	{
		$control = new TInPlaceTextBox();
		$control->setText('Hello');
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('Hello', $this->renderContents($control));
	}

	public function testRenderContentsEncodesTheTextWhenEncode()
	{
		$control = new TInPlaceTextBox();
		$control->setText('<b>a & b</b>');
		$this->assertFalse($control->getEncode());
		$this->assertSame('<b>a & b</b>', $this->renderContents($control));
		$this->assertSame('', PradoUnit::invoke($control, 'getPostBackOptions')['Encode']);
		$control->setEncode(true);
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($control));
		$this->assertTrue(PradoUnit::invoke($control, 'getPostBackOptions')['Encode']);
	}

	public function testRenderContentsShowsEmptyDisplayTextWhenTextIsEmpty()
	{
		$control = new TInPlaceTextBox();
		$control->setEmptyDisplayText('(none)');
		$this->assertSame('(none)', $this->renderContents($control));
	}

	public function testRenderContentsIsEmptyWithoutEmptyDisplayText()
	{
		$control = new TInPlaceTextBox();
		$this->assertSame('', $this->renderContents($control));
	}

	public function testPostBackOptionsIncludeEmptyText()
	{
		$control = new TInPlaceTextBox();
		$control->setEmptyDisplayText('(none)');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('(none)', $options['EmptyDisplayText']);
	}

	public function testPostBackOptionsCarryDisplayEditor()
	{
		$control = new TInPlaceTextBox();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertFalse($options['DisplayEditor']);
		$control->setDisplayTextBox(true);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertTrue($options['DisplayEditor']);
	}

	public function testAutoHideTextBoxIsAnAliasOfAutoHideEditor()
	{
		$control = new TInPlaceTextBox();
		$this->assertTrue($control->getAutoHideTextBox());
		$this->assertTrue($control->getAutoHideEditor());

		$control->setAutoHideTextBox(false);
		$this->assertFalse($control->getAutoHideEditor());
		$this->assertFalse($control->getAutoHideTextBox());

		$control->setAutoHideEditor(true);
		$this->assertTrue($control->getAutoHideTextBox());
	}

	public function testDisplayTextBoxIsAnAliasOfDisplayEditor()
	{
		$control = new TInPlaceTextBox();
		$this->assertFalse($control->getDisplayTextBox());
		$this->assertFalse($control->getDisplayEditor());

		$control->setDisplayTextBox(true);
		$this->assertTrue($control->getDisplayEditor());
		$this->assertTrue($control->getDisplayTextBox());

		$control->setDisplayEditor(false);
		$this->assertFalse($control->getDisplayTextBox());
	}

	public function testPostBackOptionsCarryEditorID()
	{
		$control = new TInPlaceTextBox();
		$control->setID('textbox');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		// EditorID is the family-wide registry key; TextBoxID stays for the input id.
		$this->assertSame($control->getClientID(), $options['EditorID']);
		$this->assertSame($control->getClientID(), $options['TextBoxID']);
	}

	public function testPostBackOptionsCarryMaxLengthInEveryTextMode()
	{
		$control = new TInPlaceTextBox();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('', $options['MaxLength']);
		$control->setMaxLength(20);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame(20, $options['MaxLength']);
		$control->setTextMode('MultiLine');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame(20, $options['MaxLength']);
	}

	private function accessibilityAttributes(TInPlaceTextBox $control, bool $readOnly): string
	{
		$textWriter = new TTextWriter();
		$writer = new THtmlWriter($textWriter);
		PradoUnit::invoke($control, 'renderLabelAccessibilityAttributes', $writer, $readOnly);
		$writer->renderBeginTag('span');
		$writer->renderEndTag();
		return $textWriter->flush();
	}

	public function testLabelIsAnOperableButtonWhenEditable()
	{
		$control = new TInPlaceTextBox();
		$html = $this->accessibilityAttributes($control, false);
		$this->assertStringContainsString('role="button"', $html);
		$this->assertStringContainsString('tabindex="0"', $html);
		$this->assertStringContainsString('aria-live="polite"', $html);
	}

	public function testReadOnlyLabelIsPlainTextWithoutButtonRole()
	{
		$control = new TInPlaceTextBox();
		$html = $this->accessibilityAttributes($control, true);
		$this->assertStringNotContainsString('role="button"', $html);
		$this->assertStringNotContainsString('tabindex', $html);
		// A read only value still announces programmatic changes.
		$this->assertStringContainsString('aria-live="polite"', $html);
	}

	public function testPostBackOptionsCarryEditorLabelFromToolTip()
	{
		$control = new TInPlaceTextBox();
		$control->setToolTip('Favorite color');
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Favorite color', $options['EditorLabel']);
	}

	public function testEditorLabelDefaultsWhenToolTipIsEmpty()
	{
		// The editor must never be nameless; without a translation module
		// Prado::localize() returns the English literal
		$control = new TInPlaceTextBox();
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('Edit value', $options['EditorLabel']);
	}

	public function testEditTriggerControlID()
	{
		$control = new TInPlaceTextBox();
		$this->assertNull($control->getEditTriggerControlID());
		$control->setEditTriggerControlID('editButton');
		$this->assertSame('editButton', $control->getEditTriggerControlID());
	}

	private function createEditor(bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$editor = $page->addControl(new TInPlaceTextBox(), 'Edit');
		return [$page, $editor];
	}

	public function testSetTextWithEncodeUpdatesTheLabelEncodedAndTheValueAsIs()
	{
		[$page, $editor] = $this->createEditor();
		$editor->setEncode(true);
		$editor->setText('<i>Ann</i>');
		$this->assertSame([
			['Prado.WebUI.TInPlaceTextBox.setLabelText' => ['Edit', '&lt;i&gt;Ann&lt;/i&gt;']],
			['Prado.Element.j' => ['Edit', 'val', ['<i>Ann</i>']]],
		], $page->getClientFunctions());
	}

	public function testSetTextUpdatesTheLabelAndTheValueOnce()
	{
		[$page, $editor] = $this->createEditor();
		$editor->setText('Ann');
		$editor->setText('Ann');
		$this->assertSame([
			['Prado.WebUI.TInPlaceTextBox.setLabelText' => ['Edit', 'Ann']],
			['Prado.Element.j' => ['Edit', 'val', ['Ann']]],
		], $page->getClientFunctions());

		[$page, $editor] = $this->createEditor(false);
		$editor->setText('Ann');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('Ann', $editor->getText());
	}

	public function testSetReadOnlyUpdatesTheClientOnce()
	{
		[$page, $editor] = $this->createEditor();
		$editor->setReadOnly('true');
		$editor->setReadOnly(true);
		$this->assertTrue($editor->getReadOnly());
		$this->assertSame([['Prado.WebUI.TInPlaceTextBox.setReadOnly' => ['Edit', true]]], $page->getClientFunctions());

		[$page, $editor] = $this->createEditor(false);
		$editor->setReadOnly(true);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetDisplayEditorAndEmptyDisplayTextUpdateTheClient()
	{
		[$page, $editor] = $this->createEditor();
		$editor->setDisplayEditor(true);
		$editor->setEmptyDisplayText('(none)');
		$editor->setEmptyDisplayText('(none)');
		$this->assertSame([
			['Prado.WebUI.TInPlaceTextBox.setDisplayEditor' => ['Edit', true]],
			['Prado.WebUI.TInPlaceTextBox.setEmptyDisplayText' => ['Edit', '(none)']],
		], $page->getClientFunctions());

		[$page, $editor] = $this->createEditor(false);
		$editor->setDisplayEditor(true);
		$editor->setEmptyDisplayText('(none)');
		$this->assertSame([], $page->getClientFunctions());
	}

	/**
	 * The load action creates its event parameter with the response; a recording response
	 * keeps the application from creating its buffered response here.
	 */
	public function testOnCallbackLoadsTheExternalText()
	{
		$snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response']);
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		try {
			$this->runLoadExternalText();
		} finally {
			PradoUnit::restore(Prado::getApplication(), $snapshot);
		}
	}

	private function runLoadExternalText(): void
	{
		[, $editor] = $this->createEditor();
		$events = [];
		$editor->attachEventHandler('OnLoadingText', function ($sender, $param) use (&$events) {
			$events[] = ['loading', $param->getCallbackParameter()];
		});
		$editor->attachEventHandler('OnCallback', function ($sender, $param) use (&$events) {
			$events[] = ['callback', $param->getCallbackParameter()];
		});
		$editor->onCallback(new TCallbackEventParameter(null, ['__InlineEditor_loadExternalText__', 'current']));
		$editor->onCallback(new TCallbackEventParameter(null, ['__InlineEditor_loadExternalText__']));
		$editor->onCallback(new TCallbackEventParameter(null, []));
		$editor->onCallback(new TCallbackEventParameter(null, ['other', 'x']));
		$editor->onCallback(new TCallbackEventParameter(null, 'text'));
		$this->assertSame([
			['loading', 'current'],
			['callback', ['__InlineEditor_loadExternalText__', 'current']],
			['loading', null],
			['callback', ['__InlineEditor_loadExternalText__']],
			['callback', []],
			['callback', ['other', 'x']],
			['callback', 'text'],
		], $events);
	}

	public function testPostBackOptionsOfTheOptionalSettings()
	{
		$control = new TInPlaceTextBox();
		$control->setAutoHideEditor(false);
		$control->setAutoPostBack(false);
		$control->setTextMode('MultiLine');
		$control->setRows(3);
		$control->setWrap(false);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame('', $options['AutoHide']);
		$this->assertSame('', $options['AutoPostBack']);
		$this->assertSame(3, $options['Rows']);
		$this->assertSame('', $options['Wrap']);
		$this->assertArrayNotHasKey('LoadTextOnEdit', $options);

		$control->setWrap(true);
		$control->attachEventHandler('OnLoadingText', fn () => null);
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertTrue($options['Wrap']);
		$this->assertTrue($options['LoadTextOnEdit']);
	}

	public function testExternalControlIDResolvesAControl()
	{
		[$page, $editor] = $this->createEditor();
		$page->addControl(new TButton(), 'Pen');
		$this->assertSame('', PradoUnit::invoke($editor, 'getExternalControlID'));
		$editor->setEditTriggerControlID('Pen');
		$this->assertSame('Pen', PradoUnit::invoke($editor, 'getExternalControlID'));
		$editor->setEditTriggerControlID('elsewhere');
		$this->assertSame('elsewhere', PradoUnit::invoke($editor, 'getExternalControlID'));
	}

	public function testRenderIsALabelSpanThatRegistersTheEditor()
	{
		[$page, $editor] = $this->createEditor();
		$editor->setEmptyDisplayText('(none)');
		$editor->onPreRender(null);
		$this->assertContains('inlineeditor', $page->cs->pradoScripts);
		$writer = new THtmlWriter(new TTextWriter());
		$editor->render($writer);
		$html = $writer->flush();
		$this->assertStringStartsWith('<span id="Edit__label" data-prado-empty="1" aria-live="polite" role="button" tabindex="0">', $html);
		$this->assertStringEndsWith('(none)</span>', $html);
		$this->assertSame('Prado.WebUI.TInPlaceTextBox', $page->cs->callbackControls[0][0]);
		$this->assertSame('Edit', $page->cs->callbackControls[0][1]['TextBoxID']);

		$editor->setText('Ann');
		$writer = new THtmlWriter(new TTextWriter());
		$editor->render($writer);
		$this->assertStringNotContainsString('data-prado-empty', $writer->flush());
	}
}
