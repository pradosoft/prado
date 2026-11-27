<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TActiveRadioButton;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\THttpResponse;
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

	/**
	 * Sets the application response to a callback response, so a control renders into
	 * a response part, and returns the saved response.
	 */
	private function useCallbackResponse(): THttpResponse
	{
		$app = Prado::getApplication();
		$saved = PradoUnit::invoke($app, 'getResponseDirect');
		$response = new THttpResponse();
		$response->setAdapter(new TCallbackResponseAdapter($response));
		$app->setResponse($response);
		return $saved;
	}

	public function testSetForControlChangingTheTagReplacesTheLabel()
	{
		$saved = $this->useCallbackResponse();
		try {
			[$page, $label] = $this->createLabel();
			$label->setForControl('Name');
			$label->setForControl('Name');
			$functions = $page->getClientFunctions();
			$this->assertCount(1, $functions);
			$this->assertSame(['Label', null], array_slice($functions[0]['Prado.Element.replace'], 0, 2));
			$this->assertSame(['Label' => $label], $page->pageAdapter->rendered);

			[$page, $label] = $this->createLabel();
			$label->setForControl('Name');
			$label->setForControl('');
			$this->assertCount(2, $page->getClientFunctions());
			$this->assertSame('Label', $page->getClientFunctions()[1]['Prado.Element.replace'][0]);
			$this->assertSame('', $label->getForControl());
		} finally {
			PradoUnit::setProp(Prado::getApplication(), '_response', $saved);
		}
	}

	public function testSetForControlToAnotherControlSetsTheForAttribute()
	{
		[$page, $label] = $this->createLabel();
		$page->addControl(new TTextBox(), 'Other');
		$page->callback = false;
		$label->setForControl('Name');
		$page->callback = true;
		$label->setForControl('Other');
		$this->assertSame([['Prado.Element.setAttribute' => ['Label', 'for', 'Other']]], $page->getClientFunctions());
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
