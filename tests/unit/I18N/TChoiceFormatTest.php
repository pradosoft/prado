<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\TestCase;
use Prado\I18N\TChoiceFormat;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;

class TChoiceFormatTest extends TestCase
{
	use TOutputBufferRestorationTrait;
	use TWebControlRenderTrait;

	private array $_appSnapshot;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
		$this->_appSnapshot = TTestApplication::snapshotApp(null, ['_globalization']);
		PradoUnit::setProp(Prado::getApplication(), '_globalization', new TGlobalization());
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_appSnapshot);
		$this->restoreOutputBufferLevel();
	}

	private function choice(string $text, mixed $value): TChoiceFormat
	{
		$choice = new TChoiceFormat();
		$choice->setText($text);
		$choice->setValue($value);
		return $choice;
	}

	public function testValueProperty(): void
	{
		$choice = new TChoiceFormat();
		$this->assertSame('', $choice->getValue());
		$choice->setValue(2);
		$this->assertSame(2, $choice->getValue());
	}

	public function testRenderSelectsTheChoiceOfTheValue(): void
	{
		$this->assertSame('One Apple.', $this->render($this->choice('[1] One Apple. |[2] Two Apples', 1)));
		$this->assertSame('Two Apples', $this->render($this->choice('[1] One Apple. |[2] Two Apples', 2)));
	}

	public function testRenderSubstitutesTheValue(): void
	{
		$this->assertSame('5 apples', $this->render($this->choice('[1] one apple|(1,Inf] {Value} apples', 5)));
	}

	public function testRenderOfAChoiceOfZero(): void
	{
		$this->assertSame('0', $this->render($this->choice('[0] 0|[1,Inf] {Value}', 0)));
	}

	public function testRenderWithoutAMatchingChoiceIsEmpty(): void
	{
		$this->assertSame('', $this->render($this->choice('[1] one|[2] two', 3)));
	}
}
