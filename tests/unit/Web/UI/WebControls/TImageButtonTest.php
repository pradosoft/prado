<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLoadingMode;

class TImageButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(): TImageButton
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$button = new TImageButton();
		$button->setID('go');
		$button->setImageUrl('go.png');
		$page->getControls()->add($button);
		return $button;
	}

	public function testFormOverridesRender()
	{
		$button = $this->createButton();
		$button->setFormAction('/search');
		$button->setFormEncType('text/plain');
		$button->setFormTarget('results');
		$output = $this->render($button);
		$this->assertStringContainsString('type="image"', $output);
		$this->assertStringContainsString('formaction="/search"', $output);
		$this->assertStringContainsString('formenctype="text/plain"', $output);
		$this->assertStringContainsString('formtarget="results"', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testNoValidationRendersFormNoValidate()
	{
		$button = $this->createButton();
		$button->setCausesValidation(false);
		$this->assertStringContainsString('formnovalidate="formnovalidate"', $this->render($button));
	}

	public function testRendersOnlyIntrinsicSizeOfImageLoadingProperties()
	{
		$button = $this->createButton();
		$button->setIntrinsicWidth(24);
		$button->setIntrinsicHeight(24);
		$button->setLoading(TLoadingMode::Lazy);
		$button->setSrcset('go@2x.png 2x');
		$output = $this->render($button);
		$this->assertStringContainsString('width="24" height="24"', $output);
		$this->assertStringNotContainsString('loading', $output);
		$this->assertStringNotContainsString('srcset', $output);
	}
}
