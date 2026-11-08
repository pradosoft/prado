<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TPager;
use Prado\Web\UI\WebControls\TPagerButtonType;

class TPagerTest extends TestCase
{
	public function testDefaultPageTexts()
	{
		$pager = new TPager();
		$this->assertSame('<<', $pager->getFirstPageText());
		$this->assertSame('<', $pager->getPrevPageText());
		$this->assertSame('>', $pager->getNextPageText());
		$this->assertSame('>>', $pager->getLastPageText());
	}

	public function testEncodeDefaultPagerText()
	{
		$pager = new TPager();
		foreach (['<' => '&lt;', '>' => '&gt;', '<<' => '&lt;&lt;', '>>' => '&gt;&gt;', '<b>' => '<b>', '3' => '3', '' => ''] as $text => $expected) {
			$this->assertSame($expected, PradoUnit::invoke($pager, 'encodeDefaultPagerText', (string) $text));
		}
	}

	public function testCreatePagerButtonEncodesDefaultTextOfLinksAndLabels()
	{
		$pager = new TPager();
		$cases = [
			[TPagerButtonType::LinkButton, true, TLinkButton::class, '&lt;&lt;'],
			[TPagerButtonType::LinkButton, false, TLabel::class, '&lt;&lt;'],
			[TPagerButtonType::PushButton, true, TButton::class, '<<'],
			[TPagerButtonType::PushButton, false, TButton::class, '<<'],
			[TPagerButtonType::ImageButton, true, TImageButton::class, '<<'],
		];
		foreach ($cases as [$type, $enabled, $class, $text]) {
			$button = PradoUnit::invoke($pager, 'createPagerButton', $type, $enabled, '<<', TPager::CMD_PAGE_FIRST, '');
			$this->assertInstanceOf($class, $button);
			$this->assertSame($text, $button->getText(), "$type " . (int) $enabled);
		}

		$button = PradoUnit::invoke($pager, 'createPagerButton', TPagerButtonType::LinkButton, true, '<b>First</b>', TPager::CMD_PAGE_FIRST, '');
		$this->assertSame('<b>First</b>', $button->getText());
	}
}
