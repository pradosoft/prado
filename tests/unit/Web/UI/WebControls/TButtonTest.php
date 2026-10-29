<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonType;

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
}
