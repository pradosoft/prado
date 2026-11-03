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
		$this->assertSame(20, $meter->getNotches());
		$this->assertSame(TPasswordStrengthMeter::DEFAULT_LEVEL_TEXTS, $meter->getLevelTexts());
		$this->assertSame(['Very weak', 'Weak', 'Medium', 'Strong', 'Very strong'], $meter->getLevelTextList());
		$this->assertSame('default', $meter->getCssUrl());
	}

	public function testNotchesAreClamped()
	{
		$this->meter->setNotches(1);
		$this->assertSame(2, $this->meter->getNotches());
		$this->meter->setNotches('500');
		$this->assertSame(100, $this->meter->getNotches());
		$this->meter->setNotches(40);
		$this->assertSame(40, $this->meter->getNotches());
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

	public function testRendersMeterWithNotches()
	{
		$this->meter->setNotches(8);
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
		$this->assertSame(8, substr_count($html, 'class="prado-password-meter-notch"'));
		$this->assertStringContainsString('<span class="prado-password-meter-bar" aria-hidden="true">', $html);
		$this->assertStringContainsString('<span class="prado-password-meter-text" aria-hidden="true"></span>', $html);
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
		$options = PradoUnit::invoke($this->meter, 'getClientOptions');
		$this->assertSame('meter', $options['ID']);
		$this->assertSame('input', $options['ControlToMeasure']);
		$this->assertSame(100, $options['MaxEntropy']);
		$this->assertSame(['a', 'b', 'c', 'd', 'e'], $options['LevelTexts']);
		$this->assertSame(array_values(TPasswordStrengthValidator::STRENGTH_ENTROPY), $options['LevelEntropy']);
	}
}
