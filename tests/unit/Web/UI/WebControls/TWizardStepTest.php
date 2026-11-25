<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\UI\WebControls\TCompleteWizardStep;
use Prado\Web\UI\WebControls\TView;
use Prado\Web\UI\WebControls\TWizard;
use Prado\Web\UI\WebControls\TWizardStep;
use Prado\Web\UI\WebControls\TWizardStepType;

/**
 * Wizard that counts the calls to wizardStepsChanged().
 */
class TWizardStepTestWizard extends TWizard
{
	public int $changes = 0;

	public function wizardStepsChanged()
	{
		$this->changes++;
		parent::wizardStepsChanged();
	}
}

class TWizardStepTest extends TestCase
{
	public function testDefaults()
	{
		$step = new TWizardStep();
		$this->assertInstanceOf(TView::class, $step);
		$this->assertNull($step->getWizard());
		$this->assertSame('', $step->getTitle());
		$this->assertTrue($step->getAllowReturn());
		$this->assertSame(TWizardStepType::Auto, $step->getStepType());
	}

	public function testSetters()
	{
		$step = new TWizardStep();
		$wizard = new TWizard();
		$step->setWizard($wizard);
		$this->assertSame($wizard, $step->getWizard());
		$step->setTitle('Account');
		$this->assertSame('Account', $step->getTitle());
		$step->setAllowReturn('false');
		$this->assertFalse($step->getAllowReturn());
		$step->setStepType('finish');
		$this->assertSame(TWizardStepType::Finish, $step->getStepType());

		$this->expectException(TInvalidDataValueException::class);
		$step->setStepType('Unknown');
	}

	public function testChangesNotifyTheWizard()
	{
		$wizard = new TWizardStepTestWizard();
		$step = new TWizardStep();
		$wizard->getWizardSteps()->add($step);
		$this->assertSame(1, $wizard->changes);

		$step->setTitle('Account');
		$this->assertSame(2, $wizard->changes);
		$step->setStepType(TWizardStepType::Step);
		$this->assertSame(3, $wizard->changes);
		// the same type again is no change
		$step->setStepType(TWizardStepType::Step);
		$this->assertSame(3, $wizard->changes);
	}

	public function testChangesWithoutAWizard()
	{
		$step = new TWizardStep();
		$step->setTitle('Account');
		$step->setStepType(TWizardStepType::Start);
		$this->assertSame('Account', $step->getTitle());
		$this->assertSame(TWizardStepType::Start, $step->getStepType());
	}

	public function testCompleteWizardStep()
	{
		$step = new TCompleteWizardStep();
		$this->assertInstanceOf(TWizardStep::class, $step);
		$this->assertSame(TWizardStepType::Complete, $step->getStepType());
		$this->expectException(TInvalidOperationException::class);
		$step->setStepType(TWizardStepType::Step);
	}

	public function testStepTypeConstants()
	{
		$this->assertSame(['Auto', 'Complete', 'Start', 'Step', 'Finish'], array_values((new \ReflectionClass(TWizardStepType::class))->getConstants()));
	}
}
