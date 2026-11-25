<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\WebControls\TWizardNavigationEventParameter;

class TWizardNavigationEventParameterTest extends TestCase
{
	public function testDefaults()
	{
		$param = new TWizardNavigationEventParameter(2);
		$this->assertSame(2, $param->getCurrentStepIndex());
		$this->assertSame(2, $param->getNextStepIndex());
		$this->assertFalse($param->getCancelNavigation());
	}

	public function testSetters()
	{
		$param = new TWizardNavigationEventParameter(0);
		$param->setNextStepIndex('3');
		$this->assertSame(3, $param->getNextStepIndex());
		$this->assertSame(0, $param->getCurrentStepIndex());
		$param->setCancelNavigation('true');
		$this->assertTrue($param->getCancelNavigation());
	}
}
