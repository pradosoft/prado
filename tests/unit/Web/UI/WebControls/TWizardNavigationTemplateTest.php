<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TWizard;
use Prado\Web\UI\WebControls\TWizardFinishNavigationTemplate;
use Prado\Web\UI\WebControls\TWizardNavigationButtonStyle;
use Prado\Web\UI\WebControls\TWizardNavigationButtonType;
use Prado\Web\UI\WebControls\TWizardNavigationContainer;
use Prado\Web\UI\WebControls\TWizardNavigationTemplate;
use Prado\Web\UI\WebControls\TWizardSideBarListItemTemplate;
use Prado\Web\UI\WebControls\TWizardStartNavigationTemplate;
use Prado\Web\UI\WebControls\TWizardStepNavigationTemplate;

class TWizardNavigationTemplateTest extends TestCase
{
	private function createButton(TWizardNavigationTemplate $template, TWizardNavigationButtonStyle $style, bool $validate, string $command)
	{
		return PradoUnit::invoke($template, 'createNavigationButton', $style, $validate, $command);
	}

	public function testBaseTemplate()
	{
		$wizard = new TWizard();
		$template = new TWizardNavigationTemplate($wizard);
		$this->assertSame($wizard, $template->getWizard());
		$this->assertSame([], $template->getIncludedFiles());
		$container = new TWizardNavigationContainer();
		$template->instantiateIn($container);
		$this->assertSame(0, $container->getControls()->getCount());
	}

	public function testCreateNavigationButtonOfEachType()
	{
		$template = new TWizardNavigationTemplate(new TWizard());
		$style = new TWizardNavigationButtonStyle();
		$style->setButtonText('Go');

		$button = $this->createButton($template, $style, true, TWizard::CMD_NEXT);
		$this->assertSame(TButton::class, $button::class);
		$this->assertSame('Go', $button->getText());
		$this->assertTrue($button->getCausesValidation());
		$this->assertSame(TWizard::CMD_NEXT, $button->getCommandName());

		$style->setButtonType(TWizardNavigationButtonType::Link);
		$button = $this->createButton($template, $style, false, TWizard::CMD_CANCEL);
		$this->assertSame(TLinkButton::class, $button::class);
		$this->assertFalse($button->getCausesValidation());
		$this->assertSame(TWizard::CMD_CANCEL, $button->getCommandName());

		$style->setButtonType(TWizardNavigationButtonType::Image);
		$style->setImageUrl('/go.png');
		$button = $this->createButton($template, $style, true, TWizard::CMD_COMPLETE);
		$this->assertSame(TImageButton::class, $button::class);
		$this->assertSame('/go.png', $button->getImageUrl());
		$this->assertSame('Go', $button->getText());
	}

	public function testCreateNavigationButtonOfAnUnknownTypeThrows()
	{
		$template = new TWizardNavigationTemplate(new TWizard());
		$style = new TWizardNavigationButtonStyle();
		PradoUnit::setProp($style, '_buttonType', 'Unknown');
		$this->expectException(TInvalidDataValueException::class);
		$this->createButton($template, $style, true, TWizard::CMD_NEXT);
	}

	public function testStartNavigation()
	{
		$wizard = new TWizard();
		$container = new TWizardNavigationContainer();
		(new TWizardStartNavigationTemplate($wizard))->instantiateIn($container);
		$controls = $container->getControls()->toArray();
		$this->assertSame([$container->getNextButton(), "\n", $container->getCancelButton()], $controls);
		$this->assertSame(TWizard::CMD_NEXT, $container->getNextButton()->getCommandName());
		$this->assertTrue($container->getNextButton()->getCausesValidation());
		$this->assertSame('Next', $container->getNextButton()->getText());
		$this->assertSame(TWizard::CMD_CANCEL, $container->getCancelButton()->getCommandName());
		$this->assertFalse($container->getCancelButton()->getCausesValidation());
		$this->assertSame('Cancel', $container->getCancelButton()->getText());
		$this->assertNull($container->getPreviousButton());
		$this->assertNull($container->getCompleteButton());
	}

	public function testStepNavigation()
	{
		$wizard = new TWizard();
		$container = new TWizardNavigationContainer();
		(new TWizardStepNavigationTemplate($wizard))->instantiateIn($container);
		$this->assertSame([$container->getPreviousButton(), "\n", $container->getNextButton(), "\n", $container->getCancelButton()], $container->getControls()->toArray());
		$this->assertSame(TWizard::CMD_PREVIOUS, $container->getPreviousButton()->getCommandName());
		$this->assertFalse($container->getPreviousButton()->getCausesValidation());
		$this->assertSame('Previous', $container->getPreviousButton()->getText());
		$this->assertSame(TWizard::CMD_NEXT, $container->getNextButton()->getCommandName());
		$this->assertTrue($container->getNextButton()->getCausesValidation());
		$this->assertSame(TWizard::CMD_CANCEL, $container->getCancelButton()->getCommandName());
		$this->assertNull($container->getCompleteButton());
	}

	public function testFinishNavigation()
	{
		$wizard = new TWizard();
		$container = new TWizardNavigationContainer();
		(new TWizardFinishNavigationTemplate($wizard))->instantiateIn($container);
		$this->assertSame([$container->getPreviousButton(), "\n", $container->getCompleteButton(), "\n", $container->getCancelButton()], $container->getControls()->toArray());
		$this->assertSame(TWizard::CMD_PREVIOUS, $container->getPreviousButton()->getCommandName());
		$this->assertSame(TWizard::CMD_COMPLETE, $container->getCompleteButton()->getCommandName());
		$this->assertTrue($container->getCompleteButton()->getCausesValidation());
		$this->assertSame('Complete', $container->getCompleteButton()->getText());
		$this->assertSame(TWizard::CMD_CANCEL, $container->getCancelButton()->getCommandName());
		$this->assertNull($container->getNextButton());
	}

	public function testNavigationContainer()
	{
		$container = new TWizardNavigationContainer();
		$this->assertInstanceOf(INamingContainer::class, $container);
		$buttons = [];
		foreach (['Previous', 'Next', 'Cancel', 'Complete'] as $name) {
			$this->assertNull($container->{"get{$name}Button"}());
			$buttons[$name] = new TButton();
			$container->{"set{$name}Button"}($buttons[$name]);
		}
		foreach ($buttons as $name => $button) {
			$this->assertSame($button, $container->{"get{$name}Button"}());
		}
	}

	public function testSideBarListItemTemplate()
	{
		$template = new TWizardSideBarListItemTemplate();
		$this->assertSame([], $template->getIncludedFiles());
		$container = new TWizardNavigationContainer();
		$template->instantiateIn($container);
		$button = $container->getControls()->itemAt(0);
		$this->assertSame(TLinkButton::class, $button::class);
		$this->assertSame(TWizard::ID_SIDEBAR_BUTTON, $button->getID());
	}
}
