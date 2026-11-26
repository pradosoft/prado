<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\TActiveHtmlArea;
use Prado\Web\UI\ActiveControls\TActiveHtmlArea5;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;

/**
 * TActiveHtmlArea and TActiveHtmlArea5 set the editor content, or the textarea
 * value without the visual editor, in a callback.
 */
class TActiveHtmlAreaTest extends TestCase
{
	public static function editorProvider(): array
	{
		return [
			'TActiveHtmlArea' => [TActiveHtmlArea::class, 'tinyMCE.getInstanceById("Area").execCommand(\'mceSetContent\',false,%s)'],
			'TActiveHtmlArea5' => [TActiveHtmlArea5::class, 'tinymce.get("Area").setContent(%s)'],
		];
	}

	private function createArea(string $class, bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$area = $page->addControl(new $class(), 'Area', $stage);
		return [$page, $area];
	}

	/**
	 * @dataProvider editorProvider
	 */
	public function testClientSideAndOnCallback(string $class)
	{
		[, $area] = $this->createArea($class);
		$this->assertInstanceOf(TCallbackClientSide::class, $area->getClientSide());
		$this->assertSame($area->getActiveControl()->getClientSide(), $area->getClientSide());
		$params = [];
		$area->attachEventHandler('OnCallback', function ($sender, $param) use (&$params) {
			$params[] = $param->getCallbackParameter();
		});
		$area->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['p'], $params);
	}

	/**
	 * @dataProvider editorProvider
	 */
	public function testSetTextSetsTheEditorContentQuoted(string $class, string $script)
	{
		[$page, $area] = $this->createArea($class);
		$area->setText("<p>It's \"a\"</p>\r\n<p>b</p>");
		$quoted = TJavaScript::quoteString('<p>It\'s "a"</p><p>b</p>');
		$this->assertSame([['Prado.Element.evaluateScript' => [sprintf($script, $quoted), null]]], $page->getClientFunctions());
		$this->assertStringNotContainsString("'s", $quoted);
		$this->assertStringNotContainsString('"a"', $quoted);
	}

	/**
	 * @dataProvider editorProvider
	 */
	public function testSetTextWithoutTheVisualEditorSetsTheValue(string $class)
	{
		[$page, $area] = $this->createArea($class);
		$area->setEnableVisualEdit(false);
		$area->setText("a\nb");
		$this->assertSame([['Prado.Element.j' => ['Area', 'val', ["a\nb"]]]], $page->getClientFunctions());
	}

	/**
	 * @dataProvider editorProvider
	 */
	public function testSetTextBeforePostDataOrOutsideACallbackUpdatesNothing(string $class)
	{
		[$page, $area] = $this->createArea($class, true, TControl::CS_INITIALIZED);
		$area->setText('a');
		$this->assertSame([], $page->getClientFunctions());

		[$page, $area] = $this->createArea($class, false);
		$area->setText('a');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('a', $area->getText());
	}
}
