<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\ActiveControls\TActiveCheckBox;
use Prado\Web\UI\ActiveControls\TActiveRadioButton;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;

/**
 * TActiveCheckBox and TActiveRadioButton update their label and check state in a callback.
 */
class TActiveCheckBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	public static function classProvider(): array
	{
		return [
			'TActiveCheckBox' => [TActiveCheckBox::class, 'Prado.WebUI.TActiveCheckBox'],
			'TActiveRadioButton' => [TActiveRadioButton::class, 'Prado.WebUI.TActiveRadioButton'],
		];
	}

	private function createBox(string $class, bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$box = $page->addControl(new $class(), 'Box');
		return [$page, $box];
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testConstructorTurnsOnAutoPostBack(string $class)
	{
		$box = new $class();
		$this->assertTrue($box->getAutoPostBack());
		$this->assertInstanceOf(TCallbackClientSide::class, $box->getClientSide());
		$this->assertSame($box->getActiveControl()->getClientSide(), $box->getClientSide());
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testRaiseCallbackEventRaisesOnCallback(string $class)
	{
		[, $box] = $this->createBox($class);
		$params = [];
		$box->attachEventHandler('OnCallback', function ($sender, $param) use (&$params) {
			$params[] = $param->getCallbackParameter();
		});
		$box->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['p'], $params);
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testSetTextUpdatesTheLabelOnce(string $class)
	{
		[$page, $box] = $this->createBox($class);
		$box->setText('<b>a</b>');
		$box->setText('<b>a</b>');
		$box->setEncode(true);
		$box->setText('<i>b</i>');
		$this->assertSame([
			['Prado.Element.j' => ['Box_label', 'html', ['<b>a</b>']]],
			['Prado.Element.j' => ['Box_label', 'html', ['&lt;i&gt;b&lt;/i&gt;']]],
		], $page->getClientFunctions());
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testSetCheckedChecksTheClientOnce(string $class)
	{
		[$page, $box] = $this->createBox($class);
		$box->setChecked('true');
		$box->setChecked(true);
		$box->setChecked(false);
		$this->assertSame([
			['Prado.Element.select' => ['Box', 'checkValue', true, 1]],
			['Prado.Element.select' => ['Box', 'checkValue', false, 1]],
		], $page->getClientFunctions());
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testSettersOutsideACallbackUpdateNothing(string $class)
	{
		[$page, $box] = $this->createBox($class, false);
		$box->setText('a');
		$box->setChecked(true);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('a', $box->getText());
		$this->assertTrue($box->getChecked());
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testRenderRegistersTheCallbackControlAndTheLabelId(string $class, string $clientClass)
	{
		[$page, $box] = $this->createBox($class);
		$box->setText('Agree');
		$html = $this->render($box);
		$this->assertStringContainsString('<label id="Box_label" for="Box">Agree</label>', $html);
		$this->assertStringNotContainsString('onclick', $html);
		$this->assertCount(1, $page->cs->callbackControls);
		$this->assertSame($clientClass, $page->cs->callbackControls[0][0]);
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testRenderWithoutAutoPostBackRegistersNoCallbackControl(string $class)
	{
		[$page, $box] = $this->createBox($class);
		$box->setAutoPostBack(false);
		$this->render($box);
		$this->assertSame([], $page->cs->callbackControls);
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testLabelAttributesCarryTheDefaultLabelId(string $class)
	{
		[$page, $box] = $this->createBox($class);
		$box->getLabelAttributes()->add('class', 'note');
		$this->assertSame('Box_label', $box->getLabelAttributes()->itemAt('id'));
		$box->setText('Agree');
		$this->assertSame([['Prado.Element.j' => ['Box_label', 'html', ['Agree']]]], $page->getClientFunctions());
		$this->assertStringContainsString('id="Box_label"', $this->render($box));
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testLabelAttributesKeepAnIdTheyAlreadyHave(string $class)
	{
		[$page, $box] = $this->createBox($class);
		$box->getLabelAttributes()->add('id', 'Mine');
		$this->assertSame('Mine', $box->getLabelAttributes()->itemAt('id'));
		$box->setText('Agree');
		$this->assertSame([['Prado.Element.j' => ['Mine', 'html', ['Agree']]]], $page->getClientFunctions());
		$this->assertStringContainsString('<label id="Mine" for="Box">Agree</label>', $this->render($box));
	}
}
