<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TEventParameter;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridCommandEventParameter;
use Prado\Web\UI\WebControls\TDataGridPager;
use Prado\Web\UI\WebControls\TDataGridPagerButtonType;
use Prado\Web\UI\WebControls\TDataGridPagerMode;
use Prado\Web\UI\WebControls\TDataGridPagerPosition;
use Prado\Web\UI\WebControls\TDataGridPagerStyle;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TPanelStyle;
use Prado\Web\UI\WebControls\TStyle;

/**
 * Control that records the events bubbled up to it.
 */
class TDataGridPagerTestContainer extends TControl
{
	private array $_bubbled = [];

	public function getBubbled(): array
	{
		return $this->_bubbled;
	}

	public function bubbleEvent($sender, $param)
	{
		$this->_bubbled[] = [$sender, $param];
		return true;
	}
}

class TDataGridPagerTest extends TestCase
{
	// ================================================================================
	// TDataGridPager
	// ================================================================================

	public function testPager()
	{
		$grid = new TDataGrid();
		$pager = new TDataGridPager($grid);
		$this->assertInstanceOf(TPanel::class, $pager);
		$this->assertInstanceOf(INamingContainer::class, $pager);
		$this->assertSame($grid, $pager->getDataGrid());
		$this->assertSame(TListItemType::Pager, $pager->getItemType());
	}

	public function testPagerBubbleEventWrapsCommand()
	{
		$container = new TDataGridPagerTestContainer();
		$pager = new TDataGridPager(new TDataGrid());
		$container->getControls()->add($pager);
		$button = new TLinkButton();
		$pager->getControls()->add($button);

		$button->onCommand(new TCommandEventParameter('Page', 'Next'));
		$bubbled = $container->getBubbled();
		$this->assertCount(1, $bubbled);
		[$sender, $param] = $bubbled[0];
		$this->assertSame($pager, $sender);
		$this->assertInstanceOf(TDataGridCommandEventParameter::class, $param);
		$this->assertSame($pager, $param->getItem());
		$this->assertSame($button, $param->getCommandSource());
		$this->assertSame('Page', $param->getCommandName());
		$this->assertSame('Next', $param->getCommandParameter());
	}

	public function testPagerBubbleEventReturnValue()
	{
		$container = new TDataGridPagerTestContainer();
		$pager = new TDataGridPager(new TDataGrid());
		$container->getControls()->add($pager);
		$this->assertTrue($pager->bubbleEvent($pager, new TCommandEventParameter('Page', '1')));
		$this->assertFalse($pager->bubbleEvent($pager, new TEventParameter()));
		$this->assertCount(1, $container->getBubbled());
	}

	// ================================================================================
	// TDataGridPagerStyle
	// ================================================================================

	public function testStyleDefaults()
	{
		$style = new TDataGridPagerStyle();
		$this->assertInstanceOf(TPanelStyle::class, $style);
		$this->assertSame(TDataGridPagerMode::NextPrev, $style->getMode());
		$this->assertSame(TDataGridPagerButtonType::LinkButton, $style->getButtonType());
		$this->assertSame('>', $style->getNextPageText());
		$this->assertSame('<', $style->getPrevPageText());
		$this->assertSame('<<', $style->getFirstPageText());
		$this->assertSame('>>', $style->getLastPageText());
		$this->assertSame(10, $style->getPageButtonCount());
		$this->assertSame(TDataGridPagerPosition::Bottom, $style->getPosition());
		$this->assertTrue($style->getVisible());
	}

	public function testStyleSetters()
	{
		$style = new TDataGridPagerStyle();
		$style->setMode('numeric');
		$style->setButtonType('pushbutton');
		$style->setNextPageText('Next');
		$style->setPrevPageText('Prev');
		$style->setFirstPageText('First');
		$style->setLastPageText('Last');
		$style->setPageButtonCount('5');
		$style->setPosition('topandbottom');
		$style->setVisible('false');
		$this->assertSame(TDataGridPagerMode::Numeric, $style->getMode());
		$this->assertSame(TDataGridPagerButtonType::PushButton, $style->getButtonType());
		$this->assertSame('Next', $style->getNextPageText());
		$this->assertSame('Prev', $style->getPrevPageText());
		$this->assertSame('First', $style->getFirstPageText());
		$this->assertSame('Last', $style->getLastPageText());
		$this->assertSame(5, $style->getPageButtonCount());
		$this->assertSame(TDataGridPagerPosition::TopAndBottom, $style->getPosition());
		$this->assertFalse($style->getVisible());

		$style->setFirstPageText('');
		$this->assertSame('', $style->getFirstPageText());
	}

	public function testStyleInvalidEnums()
	{
		foreach (['setMode', 'setButtonType', 'setPosition'] as $setter) {
			$style = new TDataGridPagerStyle();
			try {
				$style->$setter('Invalid');
				$this->fail("Expected TInvalidDataValueException from $setter");
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('propertyvalue_enumvalue_invalid', $e->getErrorCode());
			}
		}
	}

	public function testStylePageButtonCountInvalid()
	{
		$style = new TDataGridPagerStyle();
		foreach ([0, -3] as $value) {
			try {
				$style->setPageButtonCount($value);
				$this->fail('Expected TInvalidDataValueException');
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('datagridpagerstyle_pagebuttoncount_invalid', $e->getErrorCode());
			}
		}
		$this->assertSame(10, $style->getPageButtonCount());
	}

	private function configuredStyle(): TDataGridPagerStyle
	{
		$style = new TDataGridPagerStyle();
		$style->setCssClass('pager');
		$style->setMode(TDataGridPagerMode::Numeric);
		$style->setButtonType(TDataGridPagerButtonType::PushButton);
		$style->setNextPageText('N');
		$style->setPrevPageText('P');
		$style->setPageButtonCount(4);
		$style->setPosition(TDataGridPagerPosition::Top);
		$style->setVisible(false);
		return $style;
	}

	public function testStyleReset()
	{
		$style = $this->configuredStyle();
		$style->reset();
		$this->assertSame('', $style->getCssClass());
		$this->assertSame(TDataGridPagerMode::NextPrev, $style->getMode());
		$this->assertSame(TDataGridPagerButtonType::LinkButton, $style->getButtonType());
		$this->assertSame('>', $style->getNextPageText());
		$this->assertSame('<', $style->getPrevPageText());
		$this->assertSame(10, $style->getPageButtonCount());
		$this->assertSame(TDataGridPagerPosition::Bottom, $style->getPosition());
		$this->assertTrue($style->getVisible());
	}

	public function testStyleCopyFrom()
	{
		$target = new TDataGridPagerStyle();
		$target->setNextPageText('keep');
		$target->setPageButtonCount(7);
		$target->copyFrom(new TDataGridPagerStyle());
		$this->assertSame('keep', $target->getNextPageText());
		$this->assertSame(7, $target->getPageButtonCount());

		$target->copyFrom($this->configuredStyle());
		$this->assertSame('pager', $target->getCssClass());
		$this->assertSame(TDataGridPagerMode::Numeric, $target->getMode());
		$this->assertSame(TDataGridPagerButtonType::PushButton, $target->getButtonType());
		$this->assertSame('N', $target->getNextPageText());
		$this->assertSame('P', $target->getPrevPageText());
		$this->assertSame(4, $target->getPageButtonCount());
		$this->assertSame(TDataGridPagerPosition::Top, $target->getPosition());
		$this->assertFalse($target->getVisible());
	}

	public function testStyleCopyFromPlainStyle()
	{
		$source = new TStyle();
		$source->setCssClass('plain');
		$target = new TDataGridPagerStyle();
		$target->setMode(TDataGridPagerMode::Numeric);
		$target->copyFrom($source);
		$this->assertSame('plain', $target->getCssClass());
		$this->assertSame(TDataGridPagerMode::Numeric, $target->getMode());
	}

	public function testStyleMergeWith()
	{
		$target = new TDataGridPagerStyle();
		$target->setNextPageText('keep');
		$target->setVisible(true);
		$target->mergeWith($this->configuredStyle());
		$this->assertSame('pager', $target->getCssClass());
		$this->assertSame('keep', $target->getNextPageText());
		$this->assertTrue($target->getVisible());
		$this->assertSame(TDataGridPagerMode::Numeric, $target->getMode());
		$this->assertSame(TDataGridPagerButtonType::PushButton, $target->getButtonType());
		$this->assertSame('P', $target->getPrevPageText());
		$this->assertSame(4, $target->getPageButtonCount());
		$this->assertSame(TDataGridPagerPosition::Top, $target->getPosition());

		$empty = new TDataGridPagerStyle();
		$empty->mergeWith($this->configuredStyle());
		$this->assertSame('N', $empty->getNextPageText());
		$this->assertFalse($empty->getVisible());

		$configured = $this->configuredStyle();
		$configured->mergeWith(new TDataGridPagerStyle());
		$this->assertSame('N', $configured->getNextPageText());
		$this->assertSame(TDataGridPagerPosition::Top, $configured->getPosition());
		$this->assertFalse($configured->getVisible());
	}

	public function testStyleMergeWithPlainStyle()
	{
		$source = new TStyle();
		$source->setCssClass('plain');
		$target = new TDataGridPagerStyle();
		$target->mergeWith($source);
		$this->assertSame('plain', $target->getCssClass());
		$this->assertSame(TDataGridPagerMode::NextPrev, $target->getMode());
	}

	public function testStyleSerializationOmitsUnsetProperties()
	{
		$properties = ['_mode', '_nextText', '_prevText', '_firstText', '_lastText', '_buttonCount', '_position', '_visible', '_buttonType'];
		$serialized = serialize(new TDataGridPagerStyle());
		foreach ($properties as $property) {
			$this->assertStringNotContainsString("\0*\0" . $property . '"', $serialized, $property);
		}

		$style = $this->configuredStyle();
		$style->setFirstPageText('F');
		$style->setLastPageText('L');
		$serialized = serialize($style);
		foreach ($properties as $property) {
			$this->assertStringContainsString("\0*\0" . $property . '"', $serialized, $property);
		}

		$restored = unserialize($serialized);
		$this->assertSame(TDataGridPagerMode::Numeric, $restored->getMode());
		$this->assertSame('F', $restored->getFirstPageText());
		$this->assertSame('L', $restored->getLastPageText());
		$this->assertSame(4, $restored->getPageButtonCount());
		$this->assertFalse($restored->getVisible());
	}
}
