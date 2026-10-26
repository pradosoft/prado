<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TRadioButton;

class TCheckBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	private function renderLabel(TCheckBox $checkBox, string $text): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderLabel', new THtmlWriter($tw), 'cb', $text);
		return $tw->flush();
	}

	public function testRenderLabelEncodesTextWhenEncode()
	{
		$checkBox = new TCheckBox();
		$this->assertFalse($checkBox->getEncode());
		$this->assertSame('<label for="cb"><b>a & b</b></label>', $this->renderLabel($checkBox, '<b>a & b</b>'));

		$checkBox->setEncode(true);
		$this->assertTrue($checkBox->getEncode());
		$this->assertSame('<label for="cb">&lt;b&gt;a & b&lt;/b&gt;</label>', $this->renderLabel($checkBox, '<b>a & b</b>'));
	}

	public function testRadioButtonInheritsEncode()
	{
		$radio = new TRadioButton();
		$radio->setEncode(true);
		$this->assertSame('<label for="cb">&lt;i&gt;</label>', $this->renderLabel($radio, '<i>'));
	}
}
