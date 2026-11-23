<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\TCompositeControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TRadioButton;

class TRadioButtonTest extends TestCase
{
	/** Builds a radio button `Box<separator>Radio` in a naming container on a page. */
	private function makeRadio(string $groupName): TRadioButton
	{
		$page = new TPage();
		$container = new TCompositeControl();
		$container->setID('Box');
		$page->getControls()->add($container);
		$radio = new TRadioButton();
		$radio->setID('Radio');
		$radio->setGroupName($groupName);
		$container->getControls()->add($radio);
		return $radio;
	}

	public function testUniqueGroupNameIsTheGroupNameInTheNamingContainer()
	{
		$this->assertSame('Box:Group', $this->makeRadio('Group')->getUniqueGroupName());
	}

	public function testUniqueGroupNameWithoutAGroupNameIsTheUniqueID()
	{
		$this->assertSame('Box:Radio', $this->makeRadio('')->getUniqueGroupName());
	}

	public function testUniqueGroupNameFollowsASetIdSeparator()
	{
		try {
			TRadioButton::setIdSeparator('$');
			$this->assertSame('Box$Group', $this->makeRadio('Group')->getUniqueGroupName());
		} finally {
			TRadioButton::setIdSeparator(TRadioButton::DEFAULT_ID_SEPARATOR);
		}
	}
}
