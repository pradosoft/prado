<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\IRepeatInfoUser;
use Prado\Web\UI\WebControls\TListItemType;
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
}
