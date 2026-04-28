<?php

use Prado\Web\UI\WebControls\TI18NWebControl;
use Prado\Web\UI\WebControls\TWebControl;
use Prado\I18N\TI18NControlTrait;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TI18NWebControlTest extends TestCase
{
	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	public function testExtendsWebControl()
	{
		$control = new TI18NWebControl();
		$this->assertInstanceOf(TWebControl::class, $control);
	}

	public function testUsesTrait()
	{
		$traits = class_uses(TI18NWebControl::class);
		$this->assertArrayHasKey(TI18NControlTrait::class, $traits);
	}

	public function testCultureDefaultEmpty()
	{
		$control = new TI18NWebControl();
		// When no globalization module, getCulture() falls back via dy event or returns ''
		$this->assertIsString($control->getCulture());
	}

	public function testSetCulture()
	{
		$control = new TI18NWebControl();
		$control->setCulture('fr_FR');
		$this->assertEquals('fr_FR', $control->getCulture());
	}

	public function testSetCultureEmpty()
	{
		$control = new TI18NWebControl();
		$control->setCulture('en_US');
		$control->setCulture('');
		// When cleared, falls back to application/default
		$this->assertIsString($control->getCulture());
	}

	public function testCharsetDefault()
	{
		$control = new TI18NWebControl();
		// Without globalization, falls back to UTF-8 via dy event
		$charset = $control->getCharset();
		$this->assertIsString($charset);
	}

	public function testSetCharset()
	{
		$control = new TI18NWebControl();
		$control->setCharset('UTF-8');
		$this->assertEquals('UTF-8', $control->getCharset());
	}

	public function testSetCharsetEmpty()
	{
		$control = new TI18NWebControl();
		$control->setCharset('ISO-8859-1');
		$control->setCharset('');
		// Falls back to app or UTF-8
		$this->assertIsString($control->getCharset());
	}

	public function testRendersTag()
	{
		$control = new TI18NWebControl();
		// TI18NWebControl inherits TWebControl's default tag (span or div)
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('<', $output);
	}
}
