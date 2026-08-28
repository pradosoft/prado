<?php

use Prado\IO\TTextWriter;
use Prado\Web\UI\ActiveControls\TInPlaceTextBox;
use Prado\Web\UI\THtmlWriter;
use PHPUnit\Framework\TestCase;

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

	public function testEditTriggerControlID()
	{
		$control = new TInPlaceTextBox();
		$this->assertNull($control->getEditTriggerControlID());
		$control->setEditTriggerControlID('editButton');
		$this->assertSame('editButton', $control->getEditTriggerControlID());
	}
}
