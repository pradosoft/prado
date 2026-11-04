<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonTag;
use Prado\Web\UI\WebControls\TButtonType;
use Prado\Web\UI\WebControls\TDialog;

class TButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(): TButton
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$button = new TButton();
		$button->setID('save');
		$page->getControls()->add($button);
		return $button;
	}

	public function testFormOverrideDefaults()
	{
		$button = new TButton();
		$this->assertSame('', $button->getFormAction());
		$this->assertSame('', $button->getFormEncType());
		$this->assertSame('', $button->getFormTarget());
		$output = $this->render($this->createButton());
		$this->assertStringNotContainsString('formaction', $output);
		$this->assertStringNotContainsString('formenctype', $output);
		$this->assertStringNotContainsString('formtarget', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testFormOverridesRender()
	{
		$button = $this->createButton();
		$button->setFormAction(' /export ');
		$button->setFormEncType('Multipart/Form-Data');
		$button->setFormTarget('_blank');
		$output = $this->render($button);
		$this->assertStringContainsString('formaction="/export"', $output);
		$this->assertStringContainsString('formenctype="multipart/form-data"', $output);
		$this->assertStringContainsString('formtarget="_blank"', $output);
	}

	public function testNoValidationRendersFormNoValidate()
	{
		$button = $this->createButton();
		$button->setCausesValidation(false);
		$this->assertStringContainsString('formnovalidate="formnovalidate"', $this->render($button));
	}

	public function testOverridesRenderOnlyOnSubmitButtons()
	{
		$button = $this->createButton();
		$button->setButtonType(TButtonType::Button);
		$button->setFormAction('/export');
		$button->setCausesValidation(false);
		$output = $this->render($button);
		$this->assertStringNotContainsString('formaction', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testCommandDefaults()
	{
		$button = new TButton();
		$this->assertSame('', $button->getCommandFor());
		$this->assertSame('', $button->getCommand());
		$this->assertSame(TButtonTag::Input, $button->getButtonTag());
		$this->assertSame(TButtonType::Submit, $button->getButtonType());
		$output = $this->render($this->createButton());
		$this->assertStringNotContainsString('commandfor', $output);
		$this->assertStringNotContainsString('command=', $output);
	}

	public function testCommandForDefaultsToButtonTagAndType()
	{
		$button = new TButton();
		$button->setCommandFor(' Dialog ');
		$this->assertSame('Dialog', $button->getCommandFor());
		$this->assertSame(TButtonTag::Button, $button->getButtonTag());
		$this->assertSame(TButtonType::Button, $button->getButtonType());

		$button->setButtonType(TButtonType::Reset);
		$this->assertSame(TButtonType::Reset, $button->getButtonType());
	}

	public function testCommandRendersOnButtonElement()
	{
		$button = $this->createButton();
		$dialog = $this->addDialog($button);
		$button->setText('Edit');
		$button->setCommandFor('editor');
		$button->setCommand(' show-modal ');
		$output = $this->render($button);
		$this->assertStringStartsWith('<button', $output);
		$this->assertStringContainsString('type="button"', $output);
		$this->assertStringContainsString('commandfor="' . $dialog->getClientID() . '"', $output);
		$this->assertStringContainsString('command="show-modal"', $output);
		$this->assertStringContainsString('>Edit</button>', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testCommandWithoutCommandForRendersNothing()
	{
		$button = $this->createButton();
		$button->setCommand('close');
		$output = $this->render($button);
		$this->assertStringNotContainsString('command=', $output);
		$this->assertStringContainsString('type="submit"', $output);
	}

	public function testCommandForInvisibleControlIsOmitted()
	{
		$button = $this->createButton();
		$this->addDialog($button)->setVisible(false);
		$button->setCommandFor('editor');
		$button->setCommand('close');
		$output = $this->render($button);
		$this->assertStringNotContainsString('commandfor', $output);
		$this->assertStringNotContainsString('command=', $output);
	}

	public function testCommandForUnknownControlThrows()
	{
		$button = $this->createButton();
		$button->setCommandFor('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->render($button);
	}

	private function addDialog(TButton $button): TDialog
	{
		$dialog = new TDialog();
		$dialog->setID('editor');
		$button->getPage()->getControls()->add($dialog);
		return $dialog;
	}
}
