<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TActiveImageButton;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActivePager;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPagerButtonType;

class TActivePagerTest extends TestCase
{
	public function testCreatePagerButtonEncodesDefaultTextOfLinksAndLabels()
	{
		$pager = new TActivePager();
		$cases = [
			[TPagerButtonType::LinkButton, true, TActiveLinkButton::class, '&lt;&lt;'],
			[TPagerButtonType::LinkButton, false, TLabel::class, '&lt;&lt;'],
			[TPagerButtonType::PushButton, true, TActiveButton::class, '<<'],
			[TPagerButtonType::ImageButton, true, TActiveImageButton::class, '<<'],
		];
		foreach ($cases as [$type, $enabled, $class, $text]) {
			$button = PradoUnit::invoke($pager, 'createPagerButton', $type, $enabled, '<<', TActivePager::CMD_PAGE_FIRST, '');
			$this->assertInstanceOf($class, $button);
			$this->assertSame($text, $button->getText(), "$type " . (int) $enabled);
		}

		$button = PradoUnit::invoke($pager, 'createPagerButton', TPagerButtonType::LinkButton, true, '<b>First</b>', TActivePager::CMD_PAGE_FIRST, '');
		$this->assertSame('<b>First</b>', $button->getText());
	}
}
