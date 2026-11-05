<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TDataGridPagerButtonType;
use Prado\Web\UI\WebControls\TDataGridPagerMode;
use Prado\Web\UI\WebControls\TDataGridPagerPosition;
use Prado\Web\UI\WebControls\TDataGridPagerStyle;
use Prado\Web\UI\WebControls\TPanelStyle;

class TDataGridPagerStyleTest extends TestCase
{
	/**
	 * Creates a style with every pager property set.
	 * @return TDataGridPagerStyle the style
	 */
	private function createFullStyle(): TDataGridPagerStyle
	{
		$style = new TDataGridPagerStyle();
		$style->setMode(TDataGridPagerMode::Numeric);
		$style->setButtonType(TDataGridPagerButtonType::PushButton);
		$style->setNextPageText('Next');
		$style->setPrevPageText('Prev');
		$style->setFirstPageText('First');
		$style->setLastPageText('Last');
		$style->setPageButtonCount(5);
		$style->setPosition(TDataGridPagerPosition::TopAndBottom);
		$style->setVisible(false);
		$style->setListLayout(true);
		return $style;
	}

	/**
	 * Returns every pager property of a style.
	 * @param TDataGridPagerStyle $style
	 * @return array the property values by name
	 */
	private function properties(TDataGridPagerStyle $style): array
	{
		return [
			'Mode' => $style->getMode(),
			'ButtonType' => $style->getButtonType(),
			'NextPageText' => $style->getNextPageText(),
			'PrevPageText' => $style->getPrevPageText(),
			'FirstPageText' => $style->getFirstPageText(),
			'LastPageText' => $style->getLastPageText(),
			'PageButtonCount' => $style->getPageButtonCount(),
			'Position' => $style->getPosition(),
			'Visible' => $style->getVisible(),
			'ListLayout' => $style->getListLayout(),
		];
	}

	private function defaults(): array
	{
		return [
			'Mode' => TDataGridPagerMode::NextPrev,
			'ButtonType' => TDataGridPagerButtonType::LinkButton,
			'NextPageText' => '>',
			'PrevPageText' => '<',
			'FirstPageText' => '<<',
			'LastPageText' => '>>',
			'PageButtonCount' => 10,
			'Position' => TDataGridPagerPosition::Bottom,
			'Visible' => true,
			'ListLayout' => false,
		];
	}

	private function fullValues(): array
	{
		return [
			'Mode' => TDataGridPagerMode::Numeric,
			'ButtonType' => TDataGridPagerButtonType::PushButton,
			'NextPageText' => 'Next',
			'PrevPageText' => 'Prev',
			'FirstPageText' => 'First',
			'LastPageText' => 'Last',
			'PageButtonCount' => 5,
			'Position' => TDataGridPagerPosition::TopAndBottom,
			'Visible' => false,
			'ListLayout' => true,
		];
	}

	public function testExtendsTPanelStyle()
	{
		$this->assertInstanceOf(TPanelStyle::class, new TDataGridPagerStyle());
	}

	public function testDefaults()
	{
		$this->assertSame($this->defaults(), $this->properties(new TDataGridPagerStyle()));
	}

	public function testSetters()
	{
		$this->assertSame($this->fullValues(), $this->properties($this->createFullStyle()));
	}

	public function testPageButtonCountMustBePositive()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TDataGridPagerStyle())->setPageButtonCount(0);
	}

	public function testReset()
	{
		$style = $this->createFullStyle();
		$style->reset();
		$this->assertSame($this->defaults(), $this->properties($style));
	}

	public function testCopyFrom()
	{
		$style = new TDataGridPagerStyle();
		$style->copyFrom($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($style));

		$style->copyFrom(new TDataGridPagerStyle());
		$this->assertSame($this->fullValues(), $this->properties($style));
	}

	public function testMergeWith()
	{
		$style = new TDataGridPagerStyle();
		$style->mergeWith($this->createFullStyle());
		$this->assertSame($this->fullValues(), $this->properties($style));

		$style = new TDataGridPagerStyle();
		$style->setFirstPageText('Start');
		$style->setLastPageText('End');
		$style->mergeWith($this->createFullStyle());
		$this->assertSame('Start', $style->getFirstPageText());
		$this->assertSame('End', $style->getLastPageText());
		$this->assertSame('Next', $style->getNextPageText());
	}

	public function testSleepZapsDefaultProperties()
	{
		$copy = unserialize(serialize(new TDataGridPagerStyle()));
		$this->assertSame($this->defaults(), $this->properties($copy));

		$copy = unserialize(serialize($this->createFullStyle()));
		$this->assertSame($this->fullValues(), $this->properties($copy));
	}
}
