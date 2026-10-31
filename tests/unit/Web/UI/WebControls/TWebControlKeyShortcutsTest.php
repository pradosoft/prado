<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\THyperLink;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRadioButton;
use Prado\Web\UI\WebControls\TWebControl;
use Prado\Web\UI\WebControls\TWebControlAttribute;

/**
 * Client script manager that records the Prado script packages a control registers.
 */
class TWebControlKeyShortcutsTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}
}

/**
 * Tests the TWebControl KeyShortcuts, KeyShortcutScope, and KeyShortcutAllowReserved
 * properties and their rendering.
 */
class TWebControlKeyShortcutsTest extends TestCase
{
	use TWebControlRenderTrait;

	private function onPage(TWebControl $control, string $id = 'save'): TWebControl
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TWebControlKeyShortcutsTestClientScript($page));
		$control->setID($id);
		$page->getControls()->add($control);
		return $control;
	}

	public function testDefaults()
	{
		$control = new TWebControl();
		$this->assertSame('', $control->getKeyShortcuts());
		$this->assertSame('', $control->getKeyShortcutScope());
		$this->assertSame('', $control->getKeyShortcutScopeClientID());
		$this->assertFalse($control->getKeyShortcutAllowReserved());
		$this->assertSame([], $control->getKeyShortcutsAttributes());
		$this->assertStringNotContainsString('keyshortcuts', $this->render($control));
	}

	public function testKeyShortcutsAreNormalized()
	{
		$control = new TWebControl();
		$control->setKeyShortcuts(' ctrl+s  cmd+s ');
		$this->assertSame('Control+S Meta+S', $control->getKeyShortcuts());
		$control->setKeyShortcuts('');
		$this->assertSame('', $control->getKeyShortcuts());
		$control->setKeyShortcuts(null);
		$this->assertSame('', $control->getKeyShortcuts());
	}

	public function testInvalidKeyShortcutsThrow()
	{
		$control = new TWebControl();
		$this->expectException(TInvalidDataValueException::class);
		$control->setKeyShortcuts('Control+Shift');
	}

	public function testRenderPageWide()
	{
		$control = new TWebControl();
		$control->setKeyShortcuts('Control+S');
		$this->assertSame(
			'<span aria-keyshortcuts="Control+S" data-prado-keyshortcuts=""></span>',
			$this->render($control)
		);
	}

	public function testRenderAfterAriaCollection()
	{
		$control = new TWebControl();
		$control->getAria()->add('keyshortcuts', 'Alt+X');
		$control->getAria()->add('label', 'Save');
		$control->setKeyShortcuts('Control+S');
		$this->assertSame(
			'<span aria-keyshortcuts="Control+S" aria-label="Save" data-prado-keyshortcuts=""></span>',
			$this->render($control)
		);
	}

	public function testRenderRegistersScriptPackage()
	{
		$control = $this->onPage(new THyperLink());
		$control->setKeyShortcuts('Control+S');
		$output = $this->render($control);
		$this->assertStringContainsString('aria-keyshortcuts="Control+S" data-prado-keyshortcuts=""', $output);
		$this->assertContains('keyshortcuts', $control->getPage()->getClientScript()->pradoScripts);
	}

	public function testNoShortcutsRegistersNoPackage()
	{
		$control = $this->onPage(new TWebControl());
		$this->render($control);
		$this->assertNotContains('keyshortcuts', $control->getPage()->getClientScript()->pradoScripts);
	}

	public function testScopeResolvesToControlClientID()
	{
		$button = $this->onPage(new THyperLink(), 'bold');
		$panel = new TPanel();
		$panel->setID('toolbar');
		$button->getPage()->getControls()->add($panel);
		$button->setKeyShortcuts('B');
		$button->setKeyShortcutScope(' toolbar ');
		$this->assertSame('toolbar', $button->getKeyShortcutScope());
		$this->assertSame($panel->getClientID(), $button->getKeyShortcutScopeClientID());
		$this->assertStringContainsString('data-prado-keyshortcuts="' . $panel->getClientID() . '"', $this->render($button));
	}

	public function testScopeWithoutControlIsElementID()
	{
		$control = new TWebControl();
		$control->setKeyShortcuts('B');
		$control->setKeyShortcutScope('editor-toolbar');
		$this->assertSame('editor-toolbar', $control->getKeyShortcutScopeClientID());
		$this->assertStringContainsString('data-prado-keyshortcuts="editor-toolbar"', $this->render($control));
	}

	public function testReservedShortcutThrowsOnRender()
	{
		$control = new TWebControl();
		$control->setKeyShortcuts('Control+S Control+Z');
		try {
			$this->render($control);
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('webcontrol_keyshortcuts_reserved', $e->getErrorCode());
			$this->assertStringContainsString("'Control+Z'", $e->getMessage());
		}
	}

	public function testAllowReservedRendersOptIn()
	{
		$control = new TWebControl();
		$control->setKeyShortcuts('Escape ArrowDown');
		$control->setKeyShortcutAllowReserved('true');
		$this->assertTrue($control->getKeyShortcutAllowReserved());
		$this->assertSame(
			'<span aria-keyshortcuts="Escape ArrowDown" data-prado-keyshortcuts="" data-prado-keyshortcuts-reserved="true"></span>',
			$this->render($control)
		);
		$this->assertSame([
			'aria-keyshortcuts' => 'Escape ArrowDown',
			'data-prado-keyshortcuts' => '',
			'data-prado-keyshortcuts-reserved' => 'true',
		], $control->getKeyShortcutsAttributes());
	}

	public function testCopyBaseAttributesCopiesResolvedScope()
	{
		$source = $this->onPage(new THyperLink(), 'bold');
		$panel = new TPanel();
		$panel->setID('toolbar');
		$source->getPage()->getControls()->add($panel);
		$source->setKeyShortcuts('Alt+B');
		$source->setKeyShortcutScope('toolbar');
		$source->setKeyShortcutAllowReserved(true);

		$target = new TWebControl();
		$target->copyBaseAttributes($source);
		$this->assertSame('Alt+B', $target->getKeyShortcuts());
		$this->assertSame($panel->getClientID(), $target->getKeyShortcutScopeClientID());
		$this->assertTrue($target->getKeyShortcutAllowReserved());

		$other = new TWebControl();
		$other->copyBaseAttributes($source, TWebControlAttribute::All & ~TWebControlAttribute::KeyShortcuts);
		$this->assertSame('', $other->getKeyShortcuts());
	}

	private function renderInput(TCheckBox $checkBox): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderInputTag', new THtmlWriter($tw), 'cb', '');
		return $tw->flush();
	}

	public function testCheckBoxRendersShortcutsOnInput()
	{
		$checkBox = $this->onPage(new TCheckBox(), 'cb');
		$checkBox->setText('Bold');
		$checkBox->setKeyShortcuts('Alt+B');
		$input = $this->renderInput($checkBox);
		$this->assertStringContainsString('type="checkbox"', $input);
		$this->assertStringContainsString('aria-keyshortcuts="Alt+B" data-prado-keyshortcuts=""', $input);
	}

	public function testRadioButtonRendersShortcutsOnInput()
	{
		$radio = $this->onPage(new TRadioButton(), 'cb');
		$radio->setKeyShortcuts('Alt+1');
		$input = $this->renderInput($radio);
		$this->assertStringContainsString('type="radio"', $input);
		$this->assertStringContainsString('aria-keyshortcuts="Alt+1" data-prado-keyshortcuts=""', $input);
	}
}
