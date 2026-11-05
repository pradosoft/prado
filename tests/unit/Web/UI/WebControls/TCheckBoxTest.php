<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TRadioButton;

class TCheckBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	private $_service;

	protected function setUp(): void
	{
		// the page's client script manager comes from the page service
		$app = \Prado\Prado::getApplication();
		$this->_service = $app->getService();
		$app->setService(new \Prado\Web\Services\TPageService());
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setService($this->_service);
	}

	private function renderLabel(TCheckBox $checkBox, string $text): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderLabel', new THtmlWriter($tw), 'cb', $text);
		return $tw->flush();
	}

	public function testRenderLabelEncodesTextWhenEncode()
	{
		$checkBox = new TCheckBox();
		$this->assertFalse($checkBox->getEncode());
		$this->assertSame('<label for="cb"><b>a & b</b></label>', $this->renderLabel($checkBox, '<b>a & b</b>'));

		$checkBox->setEncode(true);
		$this->assertTrue($checkBox->getEncode());
		$this->assertSame('<label for="cb">&lt;b&gt;a & b&lt;/b&gt;</label>', $this->renderLabel($checkBox, '<b>a & b</b>'));
	}

	public function testRadioButtonInheritsEncode()
	{
		$radio = new TRadioButton();
		$radio->setEncode(true);
		$this->assertSame('<label for="cb">&lt;i&gt;</label>', $this->renderLabel($radio, '<i>'));
	}

	private function renderInput(TCheckBox $checkBox, string $clientID = 'cb'): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderInputTag', new THtmlWriter($tw), $clientID, '');
		return $tw->flush();
	}

	private function createCheckBox(string $class = TCheckBox::class): TCheckBox
	{
		$page = new TPage();
		$checkBox = new $class();
		$checkBox->setID('cb');
		$page->getControls()->add($checkBox);
		return $checkBox;
	}

	public function testIndeterminateAndSwitchDefaults()
	{
		$checkBox = new TCheckBox();
		$this->assertFalse($checkBox->getIndeterminate());
		$this->assertFalse($checkBox->getSwitch());
	}

	public function testIndeterminateRegistersScript()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setIndeterminate('true');
		$output = $this->renderInput($checkBox);
		$this->assertStringNotContainsString('indeterminate', $output);
		$cs = $checkBox->getPage()->getClientScript();
		$this->assertTrue($cs->isEndScriptRegistered('prado:indeterminate:cb'));
		$this->assertSame(
			'document.getElementById("cb").indeterminate = true;',
			PradoUnit::getProp($cs, '_endScripts')['prado:indeterminate:cb']
		);
	}

	public function testSwitchRendersRoleAndIgnoresIndeterminate()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setSwitch(true);
		$checkBox->setIndeterminate(true);
		$output = $this->renderInput($checkBox);
		$this->assertStringContainsString('switch="switch"', $output);
		$this->assertStringContainsString('role="switch"', $output);
		$this->assertFalse($checkBox->getPage()->getClientScript()->isEndScriptRegistered('prado:indeterminate:cb'));
	}

	public function testRadioButtonRendersNeither()
	{
		$radio = $this->createCheckBox(TRadioButton::class);
		$radio->setSwitch(true);
		$radio->setIndeterminate(true);
		$output = $this->renderInput($radio);
		$this->assertStringNotContainsString('switch', $output);
		$this->assertFalse($radio->getPage()->getClientScript()->isEndScriptRegistered('prado:indeterminate:cb'));
	}

	public function testChangedPostbackClearsIndeterminate()
	{
		$checkBox = new TCheckBox();
		$checkBox->setIndeterminate(true);
		$checkBox->loadPostData('cb', []);
		$this->assertTrue($checkBox->getIndeterminate());

		$checkBox->loadPostData('cb', ['cb' => 'on']);
		$this->assertTrue($checkBox->getChecked());
		$this->assertFalse($checkBox->getIndeterminate());
	}
}
