<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TFieldSet;
use Prado\Web\UI\WebControls\TLegend;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTextBox;

class TFieldSetTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testExtendsTPanel()
	{
		$this->assertInstanceOf(TPanel::class, new TFieldSet());
	}

	public function testRendersFieldSetWithoutLegend()
	{
		$this->assertSame('<fieldset></fieldset>', $this->render(new TFieldSet()));
	}

	public function testLegendIsGroupingText()
	{
		$fieldSet = new TFieldSet();
		$this->assertSame('', $fieldSet->getLegend());
		$fieldSet->setLegend('Contact');
		$this->assertSame('Contact', $fieldSet->getLegend());
		$this->assertSame('Contact', $fieldSet->getGroupingText());
	}

	public function testRendersLegendAsFirstChildWithoutInnerFieldSet()
	{
		$fieldSet = new TFieldSet();
		$fieldSet->setLegend('Contact');
		$fieldSet->getControls()->add('body');
		$this->assertSame('<fieldset><legend>Contact</legend>body</fieldset>', $this->render($fieldSet));
	}

	public function testLegendEncodedWhenEncode()
	{
		$fieldSet = new TFieldSet();
		$fieldSet->setLegend('<b>a & b</b>');
		$this->assertStringContainsString('<legend><b>a & b</b></legend>', $this->render($fieldSet));
		$fieldSet->setEncode(true);
		$this->assertStringContainsString('<legend>&lt;b&gt;a & b&lt;/b&gt;</legend>', $this->render($fieldSet));
	}

	public function testDisabledRendersDisabledAndDisablesChildren()
	{
		$fieldSet = new TFieldSet();
		$checkBox = new TCheckBox();
		$textBox = new TTextBox();
		$fieldSet->getControls()->add($checkBox);
		$fieldSet->getControls()->add($textBox);
		$fieldSet->setEnabled(false);
		$this->assertStringStartsWith('<fieldset disabled="disabled">', $this->renderBeginTag($fieldSet));
		$this->assertFalse($checkBox->getEnabled(true));
		$this->assertFalse($textBox->getEnabled(true));
	}

	public function testPanelGroupingTextStillNestsFieldSet()
	{
		$panel = new TPanel();
		$panel->setGroupingText('Group');
		$panel->getControls()->add('body');
		$this->assertSame('<div><fieldset><legend>Group</legend>body</fieldset></div>', $this->render($panel));
	}

	public function testLegendControl()
	{
		$legend = new TLegend();
		$this->assertSame('legend', $legend->getTagName());
		$legend->getControls()->add('Contact');
		$fieldSet = new TFieldSet();
		$fieldSet->getControls()->add($legend);
		$this->assertSame('<fieldset><legend>Contact</legend></fieldset>', $this->render($fieldSet));
	}
}
