<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TShadowRootMode;
use Prado\Web\UI\WebControls\TSlot;
use Prado\Web\UI\WebControls\TWebControl;
use Prado\Web\UI\WebControls\TWebTemplate;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TSlotTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersSlotTag()
	{
		$output = $this->render(new TSlot());
		$this->assertStringContainsString('<slot', $output);
		$this->assertStringContainsString('</slot>', $output);
	}

	public function testExtendsWebControl()
	{
		$this->assertInstanceOf(TWebControl::class, new TSlot());
	}

	public function testNameDefaultEmpty()
	{
		$this->assertSame('', (new TSlot())->getName());
	}

	public function testSetNameTrims()
	{
		$control = new TSlot();
		$control->setName('  title ');
		$this->assertSame('title', $control->getName());
	}

	public function testNameNotRenderedWhenEmpty()
	{
		$this->assertSame('<slot></slot>', $this->render(new TSlot()));
	}

	public function testNameRendered()
	{
		$control = new TSlot();
		$control->setName('title');
		$this->assertStringContainsString('name="title"', $this->render($control));
	}

	public function testNameAttributeEncoded()
	{
		$control = new TSlot();
		$control->setName('a"b');
		$this->assertStringContainsString('name="a&quot;b"', $this->render($control));
	}

	public function testFallbackContentRenderedInsideTag()
	{
		$control = new TSlot();
		$control->setName('note');
		$control->getControls()->add('No note.');
		$this->assertSame('<slot name="note">No note.</slot>', $this->render($control));
	}

	public function testPassesWebTemplateContentValidation()
	{
		$template = new TWebTemplate();
		$template->setShadowRootMode(TShadowRootMode::Open);
		$slot = new TSlot();
		$slot->setName('title');
		$template->getControls()->add($slot);
		PradoUnit::invoke($template, 'validateContent', $template);
		$this->assertStringContainsString('<slot name="title"></slot>', $this->render($template));
	}

	public function testPairsWithSlotAttribute()
	{
		$label = new TLabel();
		$label->setSlot('title');
		$label->setText('Report');
		$this->assertStringContainsString('slot="title"', $this->render($label));
	}
}
