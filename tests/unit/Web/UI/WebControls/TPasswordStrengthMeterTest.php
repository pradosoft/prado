<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPasswordStrengthMeter;
use Prado\Web\UI\WebControls\TPasswordStrengthValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TWebControl;

class TPasswordStrengthMeterTest extends TestCase
{
	private TTextBox $input;
	private TPasswordStrengthMeter $meter;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->meter = new TPasswordStrengthMeter();
		$this->meter->setID('meter');
		$this->meter->setControlToMeasure('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->meter);
	}

	private function render(): string
	{
		$writer = new THtmlWriter(new TTextWriter());
		$this->meter->renderBeginTag($writer);
		$this->meter->renderContents($writer);
		$this->meter->renderEndTag($writer);
		return $writer->flush();
	}

	public function testExtendsWebControl()
	{
		$this->assertInstanceOf(TWebControl::class, $this->meter);
		$this->assertTrue($this->meter->getEnsureId());
	}

	public function testDefaults()
	{
		$meter = new TPasswordStrengthMeter();
		$this->assertSame('', $meter->getControlToMeasure());
		$this->assertSame(120, $meter->getMaxEntropy());
		$this->assertTrue($meter->getShowText());
		$this->assertFalse($meter->getShowBits());
		$this->assertSame('', $meter->getBitsFormat());
		$this->assertSame(TPasswordStrengthMeter::DEFAULT_LEVEL_TEXTS, $meter->getLevelTexts());
		$this->assertSame(['Very weak', 'Weak', 'Medium', 'Strong', 'Very strong'], $meter->getLevelTextList());
		$this->assertSame('default', $meter->getCssUrl());
	}

	public function testMaxEntropyIsAtLeastOne()
	{
		$this->meter->setMaxEntropy(0);
		$this->assertSame(1, $this->meter->getMaxEntropy());
		$this->meter->setMaxEntropy('80');
		$this->assertSame(80, $this->meter->getMaxEntropy());
	}

	public function testLevelTextListFillsMissingNames()
	{
		$this->meter->setLevelTexts('Sehr schwach, Schwach ,,Stark');
		$this->assertSame(['Sehr schwach', 'Schwach', 'Medium', 'Stark', 'Very strong'], $this->meter->getLevelTextList());
		$this->meter->setLevelTexts('1,2,3,4,5,6');
		$this->assertSame(['1', '2', '3', '4', '5'], $this->meter->getLevelTextList());
	}

	public function testRendersMeter()
	{
		$html = $this->render();
		$this->assertStringStartsWith('<div ', $html);
		$this->assertStringContainsString('id="meter"', $html);
		$this->assertStringContainsString('class="prado-password-meter"', $html);
		$this->assertStringContainsString('role="meter"', $html);
		$this->assertStringContainsString('aria-valuemin="0"', $html);
		$this->assertStringContainsString('aria-valuemax="120"', $html);
		$this->assertStringContainsString('aria-valuenow="0"', $html);
		$this->assertStringContainsString('aria-valuetext="Very weak"', $html);
		$this->assertStringContainsString('aria-label="Password strength"', $html);
		$this->assertStringContainsString('<span class="prado-password-meter-bar" aria-hidden="true"><span class="prado-password-meter-fill"></span></span>', $html);
		$this->assertStringContainsString('<span class="prado-password-meter-text" aria-hidden="true"></span>', $html);
		$this->assertStringNotContainsString('prado-password-meter-bits', $html);
	}

	public function testTextsToggle()
	{
		$this->meter->setShowText('false');
		$this->meter->setShowBits(true);
		$html = $this->render();
		$this->assertStringNotContainsString('prado-password-meter-text', $html);
		$this->assertStringContainsString('<span class="prado-password-meter-bits" aria-hidden="true"></span>', $html);
		$this->assertStringContainsString('prado-password-meter-fill', $html);

		$this->meter->setShowBits(false);
		$html = $this->render();
		$this->assertStringNotContainsString('prado-password-meter-bits', $html);
		$this->assertStringContainsString('aria-valuetext="Very weak"', $html);
	}

	public function testCssClassIsAppended()
	{
		$this->meter->setCssClass('signup');
		$this->assertStringContainsString('class="prado-password-meter signup"', $this->render());
	}

	public function testAccessibleNameCanBeReplaced()
	{
		$this->meter->getAttributes()->add('aria-label', 'Strength of the new password');
		$html = $this->render();
		$this->assertStringContainsString('aria-label="Strength of the new password"', $html);
		$this->assertStringNotContainsString('Password strength', $html);

		$this->meter->getAttributes()->remove('aria-label');
		$this->meter->getAttributes()->add('aria-labelledby', 'heading');
		$html = $this->render();
		$this->assertStringContainsString('aria-labelledby="heading"', $html);
		$this->assertStringNotContainsString('aria-label=', $html);
	}

	public function testBitsPatternsFollowTheCulture()
	{
		$this->meter->setCulture('en');
		$this->assertSame(['one' => '{0} bit', 'other' => '{0} bits'], $this->meter->getBitsPatterns());
		$this->meter->setCulture('ru');
		$this->assertSame(['one' => '{0} бит', 'few' => '{0} бита', 'many' => '{0} бит', 'other' => '{0} бита'], $this->meter->getBitsPatterns());
		$this->meter->setCulture('ja');
		$this->assertSame(['other' => '{0} ビット'], $this->meter->getBitsPatterns());
	}

	public function testBitsPatternsFallBackToShortWidth()
	{
		// The CLDR "ar" data has the short width only.
		$this->meter->setCulture('ar');
		$patterns = $this->meter->getBitsPatterns();
		$this->assertArrayHasKey('other', $patterns);
		$this->assertStringContainsString('{0}', $patterns['other']);
	}

	public function testBitsFormatReplacesTheCulture()
	{
		$this->meter->setCulture('ru');
		$this->meter->setBitsFormat('{0} b');
		$this->assertSame(['other' => '{0} b'], $this->meter->getBitsPatterns());
	}

	public function testMeasureTarget()
	{
		$this->assertSame($this->input, $this->meter->getMeasureTarget());
	}

	public function testMeasureTargetRequired()
	{
		$this->meter->setControlToMeasure('missing');
		$this->expectException(TConfigurationException::class);
		$this->meter->getMeasureTarget();
	}

	public function testClientOptions()
	{
		$this->meter->setMaxEntropy(100);
		$this->meter->setLevelTexts('a,b,c,d,e');
		$this->meter->setCulture('de_DE');
		$options = PradoUnit::invoke($this->meter, 'getClientOptions');
		$this->assertSame('meter', $options['ID']);
		$this->assertSame('input', $options['ControlToMeasure']);
		$this->assertSame(100, $options['MaxEntropy']);
		$this->assertSame(['a', 'b', 'c', 'd', 'e'], $options['LevelTexts']);
		$this->assertSame(array_values(TPasswordStrengthValidator::STRENGTH_ENTROPY), $options['LevelEntropy']);
		$this->assertSame('de-DE', $options['Culture']);
		// The CLDR "one" pattern has a no-break space.
		$this->assertSame(['one' => "{0}\u{00A0}Bit", 'other' => '{0} Bit'], $options['BitsPatterns']);
	}
}
