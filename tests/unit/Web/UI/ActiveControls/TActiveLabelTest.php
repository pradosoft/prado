<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActiveRadioButton;
use Prado\Web\UI\TControl;

/**
 * The client update of a text set during a callback follows the control's Encode.
 */
class TActiveLabelTest extends TestCase
{
	/**
	 * Returns the page and a control of the class, loaded, so a setText() reaches the client.
	 */
	private function createControl(string $class): array
	{
		$page = new TTestCallbackPage();
		$control = new $class();
		$control->setID('Target');
		$page->getControls()->add($control);
		PradoUnit::invoke($control, 'setControlStage', TControl::CS_LOADED);
		return [$page, $control];
	}

	public static function controlClasses(): array
	{
		return [
			[TActiveLabel::class],
			[TActiveLinkButton::class],
			[TActiveCheckBox::class],
			[TActiveRadioButton::class],
		];
	}

	/**
	 * @dataProvider controlClasses
	 */
	public function testSetTextUpdatesTheClientEncodedWhenEncode(string $class)
	{
		[$page, $control] = $this->createControl($class);
		$control->setText('<b>a & b</b>');
		$functions = json_encode($page->getClientFunctions());
		$this->assertStringContainsString(json_encode('<b>a & b</b>'), $functions, $class);

		[$page, $control] = $this->createControl($class);
		$control->setEncode(true);
		$control->setText('<b>a & b</b>');
		$functions = json_encode($page->getClientFunctions());
		$this->assertStringContainsString(json_encode('&lt;b&gt;a & b&lt;/b&gt;'), $functions, $class);
		$this->assertStringNotContainsString(json_encode('<b>a & b</b>'), $functions, $class);
		$this->assertSame('<b>a & b</b>', $control->getText(), $class);
	}
}
