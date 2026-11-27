<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TTemplateControl;
use Prado\Web\UI\WebControls\TContent;
use Prado\Web\UI\WebControls\TContentPlaceHolder;

class TContentTest extends TestCase
{
	/**
	 * Instantiates a template in a new template control.
	 * @param string $template the template
	 * @return TTemplateControl the template control
	 */
	private function instantiate(string $template): TTemplateControl
	{
		$control = new TTemplateControl();
		(new TTemplate($template, sys_get_temp_dir()))->instantiateIn($control);
		return $control;
	}

	/**
	 * Returns the error code a callable throws as a TConfigurationException.
	 * @param callable $fn the callable
	 * @return string the error code, or '' when it does not throw
	 */
	private function errorCode(callable $fn): string
	{
		try {
			$fn();
		} catch (TConfigurationException $e) {
			return $e->getErrorCode();
		}
		return '';
	}

	public function testContentIsANamingContainer()
	{
		$this->assertInstanceOf(INamingContainer::class, new TContent());
	}

	public function testContentRegistersWithTheTemplateControl()
	{
		$control = $this->instantiate('<com:TContent ID="Main">body</com:TContent>');
		$contents = PradoUnit::getProp($control, '_contents');
		$this->assertSame(['Main'], array_keys($contents));
		$this->assertInstanceOf(TContent::class, $contents['Main']);
	}

	public function testContentWithoutIDThrows()
	{
		$this->assertSame('content_id_required', $this->errorCode(fn () => $this->instantiate('<com:TContent>body</com:TContent>')));
	}

	public function testPlaceHolderRegistersAndJoinsItsParent()
	{
		$control = $this->instantiate('head<com:TContentPlaceHolder ID="Main" />foot');
		$placeholders = PradoUnit::getProp($control, '_placeholders');
		$this->assertSame(['Main'], array_keys($placeholders));
		$this->assertInstanceOf(TContentPlaceHolder::class, $placeholders['Main']);
		$this->assertSame($control, $placeholders['Main']->getParent());
		$this->assertContains($placeholders['Main'], $control->getControls()->toArray());
	}

	public function testPlaceHolderWithoutIDThrows()
	{
		$this->assertSame('contentplaceholder_id_required', $this->errorCode(fn () => $this->instantiate('<com:TContentPlaceHolder />')));
	}
}
