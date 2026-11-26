<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\JuiControls\TJuiDialog;
use Prado\Web\UI\JuiControls\TJuiDialogButton;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;

/**
 * Unit tests for {@see TJuiDialogButton}.
 */
class TJuiDialogButtonTest extends TestCase
{
	use TPageLifecycleTrait;

	protected function setUp(): void
	{
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	private function newButton(): TJuiDialogButton
	{
		$button = new TJuiDialogButton();
		$button->setID('Ok');
		return $button;
	}

	public function testAdapterAndActiveControl(): void
	{
		$button = $this->newButton();
		$this->assertInstanceOf(TActiveControlAdapter::class, $button->getAdapter());
		$this->assertInstanceOf(TBaseActiveControl::class, $button->getActiveControl());
	}

	public function testText(): void
	{
		$button = $this->newButton();
		$this->assertSame('', $button->getText());
		$button->setText('OK');
		$this->assertSame('OK', $button->getText());
	}

	public function testPostBackOptions(): void
	{
		$button = $this->newButton();
		$button->setText('OK');
		$options = $button->getPostBackOptions();
		$this->assertSame('OK', $options['text']);
		$this->assertInstanceOf(TJavaScriptLiteral::class, $options['click']);
		$this->assertSame("function(){new Prado.Callback('Ok', 'onClick');}", (string) $options['click']);
	}

	public function testRaiseCallbackEventRaisesOnClickOnlyForTheClickParameter(): void
	{
		$button = $this->newButton();
		$raised = [];
		$button->attachEventHandler('OnClick', function ($sender, $param) use (&$raised) {
			$raised[] = $param;
		});
		$click = new TCallbackEventParameter(null, 'onClick');
		$button->raiseCallbackEvent($click);
		$button->raiseCallbackEvent(new TCallbackEventParameter(null, 'other'));
		$this->assertSame([$click], $raised);
	}

	public function testCallbackToTheButtonRaisesOnClick(): void
	{
		$build = function (TTestLifecyclePage $page, TForm $form) {
			$page->getControls()->insertAt(0, new THead());
			$dialog = new TJuiDialog();
			$dialog->setID('Dlg');
			$form->getControls()->add($dialog);
			$button = $this->newButton();
			$button->attachEventHandler('OnClick', fn () => $page->events[] = 'click');
			$dialog->getControls()->add($button);
		};
		$page = new TTestLifecyclePage();
		$page->build = $build;
		$this->runPage($page);
		$page = new TTestLifecyclePage();
		$page->build = $build;
		$this->runCallback($page, 'Ok', 'onClick');
		$this->assertSame(['click'], $page->events);
	}
}
