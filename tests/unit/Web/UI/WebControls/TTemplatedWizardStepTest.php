<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\TEventParameter;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\WebControls\TTemplatedWizardStep;
use Prado\Web\UI\WebControls\TWizardNavigationContainer;

class TTemplatedWizardStepTest extends TestCase
{
	public function testDefaults()
	{
		$step = new TTemplatedWizardStep();
		$this->assertInstanceOf(INamingContainer::class, $step);
		$this->assertNull($step->getContentTemplate());
		$this->assertNull($step->getNavigationTemplate());
		$this->assertNull($step->getNavigationContainer());
	}

	public function testContentTemplateReplacesTheChildren()
	{
		$step = new TTemplatedWizardStep();
		$template = new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('content'));
		$step->setContentTemplate($template);
		$this->assertSame($template, $step->getContentTemplate());
		$step->getControls()->add('old');
		$step->createChildControls();
		$this->assertSame(['content'], $step->getControls()->toArray());
	}

	public function testCreateChildControlsWithoutAContentTemplate()
	{
		$step = new TTemplatedWizardStep();
		$step->getControls()->add('old');
		$step->createChildControls();
		$this->assertSame(0, $step->getControls()->getCount());
	}

	public function testOnInitCreatesTheChildren()
	{
		$step = new TTemplatedWizardStep();
		$step->setContentTemplate(new TWizardTestTemplate(fn ($parent) => $parent->getControls()->add('content')));
		$step->onInit(new TEventParameter());
		$this->assertSame(['content'], $step->getControls()->toArray());
	}

	public function testInstantiateNavigationTemplateOnce()
	{
		$step = new TTemplatedWizardStep();
		$step->instantiateNavigationTemplate();
		$this->assertNull($step->getNavigationContainer());

		$calls = 0;
		$template = new TWizardTestTemplate(function ($parent) use (&$calls) {
			$calls++;
			$parent->getControls()->add('nav');
		});
		$step->setNavigationTemplate($template);
		$this->assertSame($template, $step->getNavigationTemplate());
		$step->instantiateNavigationTemplate();
		$container = $step->getNavigationContainer();
		$this->assertInstanceOf(TWizardNavigationContainer::class, $container);
		$this->assertSame(['nav'], $container->getControls()->toArray());

		$step->instantiateNavigationTemplate();
		$this->assertSame($container, $step->getNavigationContainer());
		$this->assertSame(1, $calls);
	}
}
