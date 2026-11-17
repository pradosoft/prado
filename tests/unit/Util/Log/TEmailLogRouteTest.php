<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TEmailLogRoute;
use Prado\Util\Log\TLogger;

class TEmailLogRouteTest extends \PHPUnit\Framework\TestCase
{
	public function testSubjectDefaultsToDefaultSubject()
	{
		$route = new TEmailLogRoute();
		$this->assertSame(TEmailLogRoute::DEFAULT_SUBJECT, $route->getSubject());

		$route->setSubject('Errors');
		$this->assertSame('Errors', $route->getSubject());

		$route->setSubject(null);
		$this->assertSame(TEmailLogRoute::DEFAULT_SUBJECT, $route->getSubject());
	}
}
