<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\I18N\TTranslateParameter;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Web\UI\WebControls\TLiteral;

class TTranslateParameterTest extends TestCase
{
	use TOutputBufferRestorationTrait;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreOutputBufferLevel();
	}

	public function testKeyIsRequired(): void
	{
		$parameter = new TTranslateParameter();
		try {
			$parameter->getKey();
			$this->fail('Expected TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('translateparameter_key_required', $e->getErrorCode());
		}
		$parameter->setKey('name');
		$this->assertSame('name', $parameter->getKey());
	}

	public function testTrimProperty(): void
	{
		$parameter = new TTranslateParameter();
		$this->assertTrue($parameter->getTrim());
		$parameter->setTrim('false');
		$this->assertFalse($parameter->getTrim());
	}

	public function testValueIsTheParameter(): void
	{
		$parameter = new TTranslateParameter();
		$this->assertSame('', $parameter->getValue());
		$parameter->setValue(' Ann ');
		$this->assertSame(' Ann ', $parameter->getValue());
		$parameter->getControls()->add('Body');
		$this->assertSame(' Ann ', $parameter->getParameter());
	}

	public function testBodyIsTheParameterWithoutAValue(): void
	{
		$parameter = new TTranslateParameter();
		$literal = new TLiteral();
		$literal->setText('Ann');
		$parameter->getControls()->add('  Hello ');
		$parameter->getControls()->add($literal);
		$parameter->getControls()->add(' ');
		$this->assertSame('Hello Ann', $parameter->getParameter());
		$parameter->setTrim(false);
		$this->assertSame('  Hello Ann ', $parameter->getParameter());
	}
}
