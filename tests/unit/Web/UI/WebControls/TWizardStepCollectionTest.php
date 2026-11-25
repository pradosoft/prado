<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\WebControls\TView;
use Prado\Web\UI\WebControls\TWizard;
use Prado\Web\UI\WebControls\TWizardStep;
use Prado\Web\UI\WebControls\TWizardStepCollection;

class TWizardStepCollectionTest extends TestCase
{
	public function testAStepMovesToAnotherWizard()
	{
		$from = new TWizard();
		$to = new TWizard();
		$step = new TWizardStep();
		$from->getWizardSteps()->add($step);

		$to->getWizardSteps()->add($step);
		$this->assertSame(0, $from->getWizardSteps()->getCount());
		$this->assertSame(0, $from->getMultiView()->getViews()->getCount());
		$this->assertSame([$step], $to->getWizardSteps()->toArray());
		$this->assertSame([$step], $to->getMultiView()->getViews()->toArray());
		$this->assertSame($to, $step->getWizard());
		$this->assertSame($to->getMultiView(), $step->getParent());
	}
}
