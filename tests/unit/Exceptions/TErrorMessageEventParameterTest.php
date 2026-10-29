<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Exceptions;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TErrorMessageEventParameter;
use Prado\Exceptions\THttpException;

class TErrorMessageEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testDescribesATException(): void
	{
		$e = new THttpException(404, 'pageservice_page_unknown', 'Home');
		$param = new TErrorMessageEventParameter($e, 'fr');
		$this->assertSame($e, $param->getException());
		$this->assertSame('pageservice_page_unknown', $param->getErrorCode());
		$this->assertSame(['Home'], $param->getErrorArguments());
		$this->assertSame('fr', $param->getLanguage());
		$this->assertSame("Page 'Home' Not Found", $param->getMessage());
		$this->assertSame($param->getMessage(), $param->getParameter());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testDescribesAnotherThrowable(): void
	{
		$e = new \RuntimeException('plain');
		$param = new TErrorMessageEventParameter($e, 'en');
		$this->assertSame('', $param->getErrorCode());
		$this->assertSame([], $param->getErrorArguments());
		$this->assertSame('plain', $param->getMessage());
	}

	public function testStartMessageMayDifferFromTheException(): void
	{
		$param = new TErrorMessageEventParameter(new \RuntimeException('plain'), 'en', 'start');
		$this->assertSame('start', $param->getMessage());
		$this->assertFalse($param->getParameterChanged());
	}

	public function testSetMessageIsTheParameterAndMarksTheChange(): void
	{
		$param = new TErrorMessageEventParameter(new TConfigurationException('component_property_undefined', 'A', 'B'), 'de');
		$param->setMessage('Übersetzt');
		$this->assertSame('Übersetzt', $param->getMessage());
		$this->assertSame('Übersetzt', $param->getParameter());
		$this->assertTrue($param->getParameterChanged());
		$param->setParameter('again');
		$this->assertSame('again', $param->getMessage());
	}

	public function testSettingTheSameMessageIsNotAChange(): void
	{
		$param = new TErrorMessageEventParameter(new \RuntimeException('plain'), 'en');
		$param->setMessage('plain');
		$this->assertFalse($param->getParameterChanged());
	}
}
