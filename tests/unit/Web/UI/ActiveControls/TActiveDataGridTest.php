<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TActiveDataGrid;
use Prado\Web\UI\ActiveControls\TActiveDataGridPager;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\WebControls\TDataGridPagerButtonType;
use Prado\Web\UI\WebControls\TLabel;

class TActiveDataGridTest extends TestCase
{
	public function testCreatePagerButtonEncodesDefaultTextOfLinksAndLabels()
	{
		$grid = new TActiveDataGrid();
		$pager = new TActiveDataGridPager($grid);
		$cases = [
			[TDataGridPagerButtonType::LinkButton, true, TActiveLinkButton::class, '&gt;&gt;'],
			[TDataGridPagerButtonType::LinkButton, false, TLabel::class, '&gt;&gt;'],
			[TDataGridPagerButtonType::PushButton, true, TActiveButton::class, '>>'],
			[TDataGridPagerButtonType::PushButton, false, TActiveButton::class, '>>'],
		];
		foreach ($cases as [$type, $enabled, $class, $text]) {
			$button = PradoUnit::invoke($grid, 'createPagerButton', $pager, $type, $enabled, '>>', TActiveDataGrid::CMD_PAGE, 'Last');
			$this->assertInstanceOf($class, $button);
			$this->assertSame($text, $button->getText(), "$type " . (int) $enabled);
		}

		$button = PradoUnit::invoke($grid, 'createPagerButton', $pager, TDataGridPagerButtonType::LinkButton, true, 'Last &raquo;', TActiveDataGrid::CMD_PAGE, 'Last');
		$this->assertSame('Last &raquo;', $button->getText());
	}
}
