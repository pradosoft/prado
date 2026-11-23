<?php

namespace Prado\Test\Unit\Web\Services;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\TControl;

class TPageServiceTest extends TestCase
{
	protected function tearDown(): void
	{
		TControl::setIdSeparator(TControl::DEFAULT_ID_SEPARATOR);
	}

	public function testIdSeparatorIsTheControlIdSeparator()
	{
		$service = new TPageService();
		$this->assertSame('$', $service->getIdSeparator());
		$service->setIdSeparator(':');
		$this->assertSame(':', $service->getIdSeparator());
		$this->assertSame(':', TControl::getIdSeparator());
	}

	public function testSetIdSeparatorRefusesAnInvalidCharacter()
	{
		$service = new TPageService();
		try {
			$service->setIdSeparator('_');
			$this->fail('TInvalidDataValueException expected');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('control_idseparator_invalid', $e->getErrorCode());
		}
		$this->assertSame('$', TControl::getIdSeparator());
	}

	public function testSetIdSeparatorAfterInitializationIsRefused()
	{
		$service = new TPageService();
		PradoUnit::invoke($service, 'markInitialized');
		try {
			$service->setIdSeparator(':');
			$this->fail('TInvalidOperationException expected');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('$', TControl::getIdSeparator());
		}
	}
}
