<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\WebControls\TDataList;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRepeatLayout;
use Prado\Web\UI\WebControls\TWizard;
use Prado\Web\UI\WebControls\TWizardSideBarTemplate;

class TWizardSideBarTemplateTest extends TestCase
{
	public function testSideBarListIsAnOrderedList()
	{
		$panel = new TPanel();
		(new TWizardSideBarTemplate())->instantiateIn($panel);
		$list = $panel->getControls()->itemAt(0);
		$this->assertInstanceOf(TDataList::class, $list);
		$this->assertSame(TWizard::ID_SIDEBAR_LIST, $list->getID());
		$this->assertSame(TRepeatLayout::OrderedList, $list->getRepeatLayout());
	}

	public function testIncludedFiles()
	{
		$this->assertSame([], (new TWizardSideBarTemplate())->getIncludedFiles());
	}
}
