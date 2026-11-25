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
	public function testInsertAddsTheStepToTheMultiView()
	{
		$wizard = new TWizard();
		$steps = new TWizardStepCollection($wizard);
		$first = new TWizardStep();
		$second = new TWizardStep();
		$steps->add($second);
		$steps->insertAt(0, $first);
		$this->assertSame([$first, $second], $steps->toArray());
		$this->assertSame([$first, $second], $wizard->getMultiView()->getViews()->toArray());
		$this->assertSame($wizard, $first->getWizard());
		$this->assertSame($wizard, $second->getWizard());
	}

	public function testInsertRejectsOtherItems()
	{
		$steps = new TWizardStepCollection(new TWizard());
		$this->expectException(TInvalidDataTypeException::class);
		$steps->add(new TView());
	}

	public function testRemoveAtRemovesTheStepFromTheWizard()
	{
		$wizard = new TWizard();
		$steps = $wizard->getWizardSteps();
		$step = new TWizardStep();
		$steps->add($step);
		$this->assertSame($step, $steps->removeAt(0));
		$this->assertSame(0, $steps->getCount());
		$this->assertSame(0, $wizard->getMultiView()->getViews()->getCount());
		$this->assertNull($step->getWizard());
	}

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
