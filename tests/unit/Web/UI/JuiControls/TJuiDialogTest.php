<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\JuiControls\TJuiDialog;
use Prado\Web\UI\JuiControls\TJuiDialogButton;

/**
 * Unit tests for {@see TJuiDialog}.
 */
class TJuiDialogTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiDialog::class;
	}

	protected function widget(): string
	{
		return 'dialog';
	}

	protected function events(): array
	{
		return ['open'];
	}

	protected function validEvents(): array
	{
		return ['beforeClose', 'close', 'create', 'drag', 'dragStart', 'dragStop', 'focus', 'open', 'resize', 'resizeStart', 'resizeStop'];
	}

	protected function option(): array
	{
		return ['title', 'Hi', '"Hi"'];
	}

	/**
	 * The dialog is appended to the first form, so the form posts its inputs.
	 * @param string $name
	 * @param string $encoded
	 * @param string $event
	 */
	protected function expectedInitOptions(string $name, string $encoded, string $event): string
	{
		return "{'" . $name . "':" . $encoded . ",'" . $event . "':" . $this->eventCallback($event) . ",'appendTo':\"form:first\"}";
	}

	public function testRenderKeepsTheAppendToOptionAndAddsTheButtons(): void
	{
		$output = $this->runPage($this->newPage(function ($dialog) {
			$dialog->getOptions()->appendTo = '#Main';
			$button = new TJuiDialogButton();
			$button->setID('Ok');
			$button->setText('OK');
			$dialog->getControls()->add($button);
		}));
		$this->assertStringContainsString(
			"jQuery('#Widget').dialog({'appendTo':\"#Main\",'buttons':[{'text':\"OK\",'click':function(){new Prado.Callback('Ok', 'onClick');}}]});",
			$output
		);
	}

	/**
	 * Each child dialog button adds a button; other children add none.
	 */
	public static function postBackOptionsProvider(): array
	{
		return [
			'no children' => [null, [], ['appendTo' => 'form:first']],
			'appendTo, no children' => ['#Main', [], ['appendTo' => '#Main']],
			'other child' => [null, ['text'], ['appendTo' => 'form:first']],
			'appendTo, other child' => ['#Main', ['text'], ['appendTo' => '#Main']],
			'button' => [null, ['Ok'], ['appendTo' => 'form:first', 'buttons' => ['Ok']]],
			'other child and buttons' => [null, ['text', 'Ok', 'No'], ['appendTo' => 'form:first', 'buttons' => ['Ok', 'No']]],
		];
	}

	/**
	 * @dataProvider postBackOptionsProvider
	 * @param ?string $appendTo the appendTo option, null for none
	 * @param string[] $children `text` for a literal, any other value the text of a dialog button
	 * @param array $expected the options, with the text of each button
	 */
	public function testPostBackOptions(?string $appendTo, array $children, array $expected): void
	{
		$dialog = $this->newControl();
		if ($appendTo !== null) {
			$dialog->getOptions()->appendTo = $appendTo;
		}
		foreach ($children as $child) {
			if ($child === 'text') {
				$dialog->getControls()->add('text');
			} else {
				$button = new TJuiDialogButton();
				$button->setID($child);
				$button->setText($child);
				$dialog->getControls()->add($button);
			}
		}
		$options = PradoUnit::invoke($dialog, 'getPostBackOptions');
		if (isset($options['buttons'])) {
			$options['buttons'] = array_map(fn ($button) => $button['text'], $options['buttons']);
		}
		$this->assertSame($expected, $options);
	}

	public function testOpenAndCloseRegisterTheClientMethod(): void
	{
		$output = $this->runPage($this->newPage(function ($dialog, $page) {
			$page->load = function () use ($dialog) {
				$dialog->open();
				$dialog->close();
			};
		}));
		$this->assertStringContainsString("jQuery(document).ready(function() { jQuery('#Widget').dialog('open'); })", $output);
		$this->assertStringContainsString("jQuery(document).ready(function() { jQuery('#Widget').dialog('close'); })", $output);
	}

	public function testGroupingTextIsNotSupported(): void
	{
		$this->expectException(TNotSupportedException::class);
		$this->newControl()->setGroupingText('Legend');
	}

	/**
	 * A dialog rendered in a callback replaces its contents on the client, so the widget keeps its element.
	 */
	public function testRenderInACallbackReplacesTheContents(): void
	{
		$configure = function ($dialog) {
			$dialog->getControls()->add('Body');
			$dialog->attachEventHandler('OnOpen', function ($sender, $param) {
				$sender->render($param->getNewWriter());
			});
		};
		$this->runPage($this->newPage($configure));
		$this->runCallback($this->newPage($configure), 'Widget', ['event' => 'open']);
		$parts = $this->getCallbackParts();
		$replace = $this->replaceActions($parts);
		$this->assertCount(1, $replace);
		[$id, $content, $boundary, $self] = $replace[0];
		$this->assertSame('Widget', $id);
		$this->assertNull($content);
		$this->assertFalse($self);
		$this->assertSame('Body', $parts[$boundary]);
	}
}
