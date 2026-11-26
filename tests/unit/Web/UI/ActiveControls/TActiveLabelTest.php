<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActiveRadioButton;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * The client update of a text set during a callback follows the control's Encode.
 */
class TActiveLabelTest extends TestCase
{
	use TWebControlRenderTrait;

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

	private function createLabel(bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$label = $page->addControl(new TActiveLabel(), 'Label');
		$page->addControl(new TTextBox(), 'Name');
		return [$page, $label];
	}

	public function testSetTextUnchangedOrOutsideACallbackUpdatesNothing()
	{
		[$page, $label] = $this->createLabel();
		$label->setText('a');
		$label->setText('a');
		$this->assertCount(1, $page->getClientFunctions());

		[$page, $label] = $this->createLabel(false);
		$label->setText('a');
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetForControlSetsTheForAttributeOnce()
	{
		[$page, $label] = $this->createLabel();
		$label->setForControl('Name');
		$label->setForControl('Name');
		$this->assertSame([['Prado.Element.setAttribute' => ['Label', 'for', 'Name']]], $page->getClientFunctions());
	}

	public function testClearingForControlRemovesTheForAttribute()
	{
		[$page, $label] = $this->createLabel();
		$label->setForControl('Name');
		$label->setForControl('');
		$this->assertSame(['Prado.Element.removeAttribute' => ['Label', 'for']], $page->getClientFunctions()[1]);
		$this->assertSame('', $label->getForControl());
	}

	public function testSetForControlToAMissingControlThrows()
	{
		[, $label] = $this->createLabel();
		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage("TLabel.AssociatedControl 'Missing' cannot be found.");
		$label->setForControl('Missing');
	}

	public function testSetForControlOutsideACallbackUpdatesNothing()
	{
		[$page, $label] = $this->createLabel(false);
		$label->setForControl('Missing');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('Missing', $label->getForControl());
	}

	public function testRenderAddsTheId()
	{
		[, $label] = $this->createLabel();
		$label->setText('Hi');
		$this->assertSame('<span id="Label">Hi</span>', $this->render($label));
	}
}
