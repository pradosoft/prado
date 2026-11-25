<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\IRepeatInfoUser;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TRepeatDirection;
use Prado\Web\UI\WebControls\TTableCaptionAlign;
use Prado\Web\UI\WebControls\TRepeatInfo;
use Prado\Web\UI\WebControls\TRepeatLayout;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * Repeat info user that renders "{type}{index}" for each item.
 */
class TRepeatInfoTestUser extends TWebControl implements IRepeatInfoUser
{
	public bool $hasHeader = false;
	public bool $hasFooter = false;
	public bool $hasSeparators = false;
	public int $itemCount = 2;
	public array $styles = [];

	public function getHasFooter()
	{
		return $this->hasFooter;
	}

	public function getHasHeader()
	{
		return $this->hasHeader;
	}

	public function getHasSeparators()
	{
		return $this->hasSeparators;
	}

	public function getItemCount()
	{
		return $this->itemCount;
	}

	public function generateItemStyle($itemType, $index)
	{
		return $this->styles[$itemType . $index] ?? null;
	}

	public function renderItem($writer, $repeatInfo, $itemType, $index)
	{
		$writer->write($itemType . $index);
	}
}

class TRepeatInfoTest extends TestCase
{
	private function render(TRepeatInfo $info, TRepeatInfoTestUser $user): string
	{
		$tw = new TTextWriter();
		$info->renderRepeater(new THtmlWriter($tw), $user);
		return $tw->flush();
	}

	private function createUser(): TRepeatInfoTestUser
	{
		$user = new TRepeatInfoTestUser();
		$user->setID('List');
		return $user;
	}

	public function testListLayoutsAreEnumerated()
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout('OrderedList');
		$this->assertSame(TRepeatLayout::OrderedList, $info->getRepeatLayout());
		$info->setRepeatLayout('UnorderedList');
		$this->assertSame(TRepeatLayout::UnorderedList, $info->getRepeatLayout());
	}

	public function testOrderedListLayout()
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout(TRepeatLayout::OrderedList);
		$this->assertSame("<ol id=\"List\">\n<li>Item0</li>\n<li>Item1</li>\n</ol>", $this->render($info, $this->createUser()));
	}

	public function testUnorderedListLayout()
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout(TRepeatLayout::UnorderedList);
		$this->assertSame("<ul id=\"List\">\n<li>Item0</li>\n<li>Item1</li>\n</ul>", $this->render($info, $this->createUser()));
	}

	public function testListLayoutRendersHeaderAndFooterOmitsSeparatorsAndCaption()
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout(TRepeatLayout::OrderedList);
		$info->setCaption('Steps');
		$info->setRepeatColumns(3);
		$user = $this->createUser();
		$user->hasHeader = true;
		$user->hasFooter = true;
		$user->hasSeparators = true;
		$this->assertSame("<ol id=\"List\">\n<li>Header-1</li>\n<li>Item0</li>\n<li>Item1</li>\n<li>Footer-1</li>\n</ol>", $this->render($info, $user));
	}

	public function testListLayoutItemStyleOnListItem()
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout(TRepeatLayout::OrderedList);
		$user = $this->createUser();
		$style = new TStyle();
		$style->setCssClass('current');
		$user->styles[TListItemType::Item . '1'] = $style;
		$this->assertStringContainsString('<li class="current">Item1</li>', $this->render($info, $user));
	}

	// ------------------------------------------------------------------
	// Properties (#1497)
	// ------------------------------------------------------------------

	private function createInfo(string $layout, string $direction = TRepeatDirection::Vertical, int $columns = 0): TRepeatInfo
	{
		$info = new TRepeatInfo();
		$info->setRepeatLayout($layout);
		$info->setRepeatDirection($direction);
		$info->setRepeatColumns($columns);
		return $info;
	}

	private function createItemsUser(int $count, bool $header = false, bool $footer = false, bool $separators = false): TRepeatInfoTestUser
	{
		$user = $this->createUser();
		$user->itemCount = $count;
		$user->hasHeader = $header;
		$user->hasFooter = $footer;
		$user->hasSeparators = $separators;
		return $user;
	}

	private function style(string $class): TStyle
	{
		$style = new TStyle();
		$style->setCssClass($class);
		return $style;
	}

	public function testPropertyDefaults()
	{
		$info = new TRepeatInfo();
		$this->assertSame('', $info->getCaption());
		$this->assertSame(TTableCaptionAlign::NotSet, $info->getCaptionAlign());
		$this->assertSame(0, $info->getRepeatColumns());
		$this->assertSame(TRepeatDirection::Vertical, $info->getRepeatDirection());
		$this->assertSame(TRepeatLayout::Table, $info->getRepeatLayout());
	}

	public function testPropertySetters()
	{
		$info = new TRepeatInfo();
		$info->setCaption('Names');
		$info->setCaptionAlign('top');
		$info->setRepeatColumns('3');
		$info->setRepeatDirection('horizontal');
		$info->setRepeatLayout('flow');
		$this->assertSame('Names', $info->getCaption());
		$this->assertSame(TTableCaptionAlign::Top, $info->getCaptionAlign());
		$this->assertSame(3, $info->getRepeatColumns());
		$this->assertSame(TRepeatDirection::Horizontal, $info->getRepeatDirection());
		$this->assertSame(TRepeatLayout::Flow, $info->getRepeatLayout());
	}

	public function testRepeatColumnsRejectsANegativeNumber()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TRepeatInfo())->setRepeatColumns(-1);
	}

	public function testEnumeratedPropertiesRejectUnknownValues()
	{
		$info = new TRepeatInfo();
		foreach (['setCaptionAlign', 'setRepeatDirection', 'setRepeatLayout'] as $setter) {
			try {
				$info->$setter('Diagonal');
				$this->fail("$setter accepted an unknown value");
			} catch (TInvalidDataValueException $e) {
				$this->addToAssertionCount(1);
			}
		}
	}

	// ------------------------------------------------------------------
	// renderRepeater()
	// ------------------------------------------------------------------

	public function testTableLayoutRendersTheCaptionAndUserStyle()
	{
		$info = $this->createInfo(TRepeatLayout::Table);
		$info->setCaption('Names');
		$info->setCaptionAlign(TTableCaptionAlign::Bottom);
		$user = $this->createItemsUser(1);
		$user->getStyle()->setCssClass('grid');
		$user->setToolTip('tip');
		$html = $this->render($info, $user);
		$this->assertStringStartsWith('<table id="List" title="tip" class="grid"><caption style="caption-side:bottom;">Names</caption>', $html);
		$this->assertStringContainsString('<td>Item0</td>', $html);
	}

	public function testTableLayoutWithoutItems()
	{
		$this->assertSame("<table id=\"List\">\n<tbody></tbody></table>", $this->render($this->createInfo(TRepeatLayout::Table), $this->createItemsUser(0)));
	}

	public function testFlowLayoutRendersASpanWithTheUserStyle()
	{
		$user = $this->createItemsUser(2);
		$user->getStyle()->setCssClass('flow');
		$this->assertSame("<span id=\"List\" class=\"flow\">\nItem0\n<br/>Item1\n</span>", $this->render($this->createInfo(TRepeatLayout::Flow), $user));
	}

	public function testRawLayoutRendersHeaderItemsSeparatorsAndFooter()
	{
		$this->assertSame('Header-1Item0Separator0Item1Footer-1', $this->render($this->createInfo(TRepeatLayout::Raw), $this->createItemsUser(2, true, true, true)));
		$this->assertSame('Item0Item1', $this->render($this->createInfo(TRepeatLayout::Raw), $this->createItemsUser(2)));
	}

	// ------------------------------------------------------------------
	// Horizontal
	// ------------------------------------------------------------------

	public function testHorizontalTableWrapsIntoRowsWithHeaderAndFooter()
	{
		$this->assertSame(
			"<table id=\"List\">\n"
			. "<thead><tr><th colspan=\"2\" scope=\"col\">Header-1</th></tr></thead>\n"
			. "<tbody><tr><td>Item0</td>\n<td>Item1</td>\n</tr>\n<tr><td>Item2</td>\n<td></td>\n</tr>\n</tbody>"
			. "<tfoot><tr><td colspan=\"2\">Footer-1</td></tr></tfoot>\n</table>",
			$this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Horizontal, 2), $this->createItemsUser(3, true, true))
		);
	}

	public function testHorizontalTableSingleColumnHasNoColspan()
	{
		$html = $this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Horizontal, 1), $this->createItemsUser(2, true, true));
		$this->assertStringContainsString('<thead><tr><th scope="col">Header-1</th></tr></thead>', $html);
		$this->assertStringContainsString("<tbody><tr><td>Item0</td>\n</tr>\n<tr><td>Item1</td>\n</tr>\n</tbody>", $html);
		$this->assertStringContainsString('<tfoot><tr><td>Footer-1</td></tr></tfoot>', $html);
	}

	public function testHorizontalTableWithSeparatorsFillsTheLastRow()
	{
		$info = $this->createInfo(TRepeatLayout::Table, TRepeatDirection::Horizontal, 2);
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Separator0</td>\n<td>Item1</td>\n<td>Separator1</td>\n</tr>\n"
			. "<tr><td>Item2</td>\n<td>Separator2</td>\n<td>Item3</td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($info, $this->createItemsUser(4, false, false, true)),
			'a full last row fills the last separator cell'
		);
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Separator0</td>\n<td>Item1</td>\n<td>Separator1</td>\n</tr>\n"
			. "<tr><td>Item2</td>\n<td></td>\n<td></td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($info, $this->createItemsUser(3, false, false, true)),
			'a short last row fills its missing items and separators'
		);
	}

	public function testHorizontalTableAppliesTheItemStyles()
	{
		$user = $this->createItemsUser(2, true, true, true);
		$user->styles = [
			TListItemType::Header . '-1' => $this->style('h'),
			TListItemType::Item . '1' => $this->style('i'),
			TListItemType::Separator . '0' => $this->style('s'),
			TListItemType::Footer . '-1' => $this->style('f'),
		];
		$html = $this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Horizontal), $user);
		$this->assertStringContainsString('<th colspan="4" scope="col" class="h">Header-1</th>', $html);
		$this->assertStringContainsString('<td class="s">Separator0</td>', $html);
		$this->assertStringContainsString('<td class="i">Item1</td>', $html);
		$this->assertStringContainsString('<td colspan="4" class="f">Footer-1</td>', $html);
	}

	public function testHorizontalFlowBreaksAfterEachRow()
	{
		$this->assertSame(
			"<span id=\"List\">\nHeader-1<br/>\nItem0Separator0\nItem1Separator1<br/>\nItem2<br/>\nFooter-1\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow, TRepeatDirection::Horizontal, 2), $this->createItemsUser(3, true, true, true))
		);
	}

	public function testHorizontalFlowInOneRowHasNoBreak()
	{
		$this->assertSame(
			"<span id=\"List\">\nHeader-1\nItem0\nItem1\nFooter-1\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow, TRepeatDirection::Horizontal), $this->createItemsUser(2, true, true))
		);
	}

	// ------------------------------------------------------------------
	// Vertical
	// ------------------------------------------------------------------

	public function testVerticalTableSingleColumn()
	{
		$this->assertSame(
			"<table id=\"List\">\n<thead><tr><th scope=\"col\">Header-1</th></tr></thead>\n"
			. "<tbody><tr><td>Item0</td>\n</tr>\n<tr><td>Item1</td>\n</tr>\n</tbody>"
			. "<tfoot><tr><td>Footer-1</td></tr></tfoot>\n</table>",
			$this->render($this->createInfo(TRepeatLayout::Table), $this->createItemsUser(2, true, true))
		);
	}

	public function testVerticalTableFillsColumnsTopToBottom()
	{
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Item2</td>\n<td>Item4</td>\n</tr>\n"
			. "<tr><td>Item1</td>\n<td>Item3</td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Vertical, 3), $this->createItemsUser(5))
		);
	}

	public function testVerticalTableWithAShortLastColumn()
	{
		$this->assertSame(
			"<table id=\"List\">\n<thead><tr><th colspan=\"2\" scope=\"col\">Header-1</th></tr></thead>\n"
			. "<tbody><tr><td>Item0</td>\n<td>Item2</td>\n</tr>\n<tr><td>Item1</td>\n<td></td>\n</tr>\n</tbody>"
			. "<tfoot><tr><td colspan=\"2\">Footer-1</td></tr></tfoot>\n</table>",
			$this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Vertical, 2), $this->createItemsUser(3, true, true))
		);
	}

	public function testVerticalTableSkipsTheCellsPastTheLastColumns()
	{
		// 7 items in 3 columns: the columns hold 3, 2, and 2 items.
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Item3</td>\n<td>Item5</td>\n</tr>\n"
			. "<tr><td>Item1</td>\n<td>Item4</td>\n<td>Item6</td>\n</tr>\n"
			. "<tr><td>Item2</td>\n<td></td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Vertical, 3), $this->createItemsUser(7))
		);
	}

	public function testVerticalTableAppliesTheItemStyle()
	{
		$user = $this->createItemsUser(2);
		$user->styles = [TListItemType::Item . '1' => $this->style('i')];
		$this->assertStringContainsString('<td class="i">Item1</td>', $this->render($this->createInfo(TRepeatLayout::Table, TRepeatDirection::Vertical, 2), $user));
	}

	public function testVerticalFlowBreaksBetweenRows()
	{
		$this->assertSame(
			"<span id=\"List\">\nHeader-1\nItem0\nItem2\n<br/>Item1\n<br/>Footer-1\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow, TRepeatDirection::Vertical, 2), $this->createItemsUser(3, true, true))
		);
		$this->assertSame(
			"<span id=\"List\">\nItem0\n<br/>Item1\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow), $this->createItemsUser(2)),
			'no break after the last row without a footer'
		);
	}

	public function testVerticalFlowSkipsTheCellsPastTheLastColumns()
	{
		$this->assertSame(
			"<span id=\"List\">\nItem0\nItem3\nItem5\n<br/>Item1\nItem4\nItem6\n<br/>Item2\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow, TRepeatDirection::Vertical, 3), $this->createItemsUser(7))
		);
	}

	public function testVerticalTableRendersASeparatorAfterEachItemButTheLast()
	{
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n</tr><tr><td>Separator0</td>\n</tr>\n"
			. "<tr><td>Item1</td>\n</tr><tr><td>Separator1</td>\n</tr>\n<tr><td>Item2</td>\n</tr>\n</tbody></table>",
			$this->render($this->createInfo(TRepeatLayout::Table), $this->createItemsUser(3, false, false, true))
		);
	}

	public function testVerticalTableColumnsRenderASeparatorAfterEachItemButTheLast()
	{
		$info = $this->createInfo(TRepeatLayout::Table, TRepeatDirection::Vertical, 2);
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Separator0</td>\n<td>Item2</td>\n<td>Separator2</td>\n</tr>\n"
			. "<tr><td>Item1</td>\n<td>Separator1</td>\n<td>Item3</td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($info, $this->createItemsUser(4, false, false, true))
		);
		$this->assertSame(
			"<table id=\"List\">\n<tbody><tr><td>Item0</td>\n<td>Separator0</td>\n<td>Item2</td>\n<td></td>\n</tr>\n"
			. "<tr><td>Item1</td>\n<td>Separator1</td>\n<td></td>\n<td></td>\n</tr>\n</tbody></table>",
			$this->render($info, $this->createItemsUser(3, false, false, true)),
			'the separator of the last item is never requested'
		);
	}

	public function testVerticalTableAppliesTheSeparatorStyle()
	{
		$user = $this->createItemsUser(2, false, false, true);
		$user->styles = [TListItemType::Separator . '0' => $this->style('s')];
		$this->assertStringContainsString('<td class="s">Separator0</td>', $this->render($this->createInfo(TRepeatLayout::Table), $user));
	}

	public function testVerticalFlowRendersASeparatorAfterEachItemButTheLast()
	{
		$this->assertSame(
			"<span id=\"List\">\nItem0\n<br/>Separator0\n<br/>Item1\n<br/>Separator1\n<br/>Item2\n\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow), $this->createItemsUser(3, false, false, true))
		);
		$this->assertSame(
			"<span id=\"List\">\nItem0\nSeparator0\nItem2\n\n<br/>Item1\nSeparator1\n</span>",
			$this->render($this->createInfo(TRepeatLayout::Flow, TRepeatDirection::Vertical, 2), $this->createItemsUser(3, false, false, true))
		);
	}
}
