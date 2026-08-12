<?php

use Prado\Web\UI\WebControls\TDialog;
use PHPUnit\Framework\TestCase;

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
}
