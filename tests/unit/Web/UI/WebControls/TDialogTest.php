<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDialog;
use Prado\Web\UI\WebControls\TDialogClosedBy;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TDialogTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersDialogTag()
	{
		$control = new TDialog();
		$output = $this->render($control);
		$this->assertStringContainsString('<dialog', $output);
		$this->assertStringContainsString('</dialog>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TDialog();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testOpenDefaultFalse()
	{
		$control = new TDialog();
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenTrue()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFalse()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenFromString()
	{
		$control = new TDialog();
		$control->setOpen('true');
		$this->assertTrue($control->getOpen());

		$control->setOpen('false');
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenFromNumeric()
	{
		$control = new TDialog();
		$control->setOpen(1);
		$this->assertTrue($control->getOpen());

		$control->setOpen(0);
		$this->assertFalse($control->getOpen());
	}

	public function testOpenAttributeRenderedWhenTrue()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$output = $this->render($control);
		$this->assertStringContainsString('open="open"', $output);
	}

	public function testOpenAttributeNotRenderedWhenFalse()
	{
		$control = new TDialog();
		$control->setOpen(false);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
	}

	public function testClosedByDefaultsAuto()
	{
		$control = new TDialog();
		$this->assertSame(TDialogClosedBy::Auto, $control->getClosedBy());
		$this->assertStringNotContainsString('closedby', $this->render($control));
	}

	public function testClosedByRendersLowercase()
	{
		$control = new TDialog();
		foreach (['Any' => 'any', 'closerequest' => 'closerequest', 'None' => 'none'] as $value => $attribute) {
			$control->setClosedBy($value);
			$this->assertStringContainsString('closedby="' . $attribute . '"', $this->render($control));
		}
	}

	public function testClosedByRejectsUnknown()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TDialog())->setClosedBy('outside');
	}

	public function testModalDefaultFalse()
	{
		$control = new TDialog();
		$this->assertFalse($control->getModal());
		$control->setModal('true');
		$this->assertTrue($control->getModal());
	}

	public function testModalWithoutOpenRendersNothing()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setModal(true);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
		$this->assertFalse($page->getClientScript()->isEndScriptRegistered('prado:dialog:' . $control->getClientID()));
	}

	public function testOpenModalRegistersShowModalScript()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setOpen(true);
		$control->setModal(true);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
		$this->assertStringContainsString('id="' . $control->getClientID() . '"', $output);

		$key = 'prado:dialog:' . $control->getClientID();
		$scripts = PradoUnit::getProp($page->getClientScript(), '_endScripts');
		$this->assertArrayHasKey($key, $scripts);
		$this->assertStringContainsString('showModal()', $scripts[$key]);
		$this->assertStringContainsString(TJavaScript::quoteString($control->getClientID()), $scripts[$key]);
	}

	/**
	 * @return array{TPage, TDialog} a dialog with an ID in a page
	 */
	private function createPageDialog(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$control = new TDialog();
		$control->setID('notice');
		$page->getControls()->add($control);
		return [$page, $control];
	}
}
