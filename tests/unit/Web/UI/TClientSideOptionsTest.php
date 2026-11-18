<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Collections\TMap;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\TClientSideOptions;

/**
 * Exposes the protected option accessors.
 */
class TClientSideOptionsTestOptions extends TClientSideOptions
{
	public function option($name)
	{
		return $this->getOption($name);
	}

	public function setOptionValue($name, $value)
	{
		$this->setOption($name, $value);
	}

	public function setFunctionCode($name, $code)
	{
		$this->setFunction($name, $code);
	}
}

class TClientSideOptionsTest extends \PHPUnit\Framework\TestCase
{
	public function testGetOptionWithoutOptions(): void
	{
		$options = new TClientSideOptionsTestOptions();
		$this->assertNull($options->option('Missing'));
	}

	public function testSetAndGetOption(): void
	{
		$options = new TClientSideOptionsTestOptions();
		$options->setOptionValue('Delay', 5);
		$this->assertSame(5, $options->option('Delay'));
		$this->assertNull($options->option('Missing'));
		$this->assertInstanceOf(TMap::class, $options->getOptions());
		$this->assertSame($options->getOptions(), $options->getOptions());
		$this->assertSame(['Delay' => 5], $options->getOptions()->toArray());
	}

	public function testSetFunctionWrapsStatements(): void
	{
		$options = new TClientSideOptionsTestOptions();
		$options->setFunctionCode('OnDone', 'alert(1);');
		$this->assertInstanceOf(TJavaScriptLiteral::class, $options->option('OnDone'));
		$this->assertSame('function(sender, parameter){ alert(1); }', (string) $options->option('OnDone'));
	}

	public function testSetFunctionKeepsAJavaScriptLiteral(): void
	{
		$options = new TClientSideOptionsTestOptions();
		$literal = TJavaScript::quoteJsLiteral('handler');
		$options->setFunctionCode('OnDone', $literal);
		$this->assertSame($literal, $options->option('OnDone'));
	}
}
