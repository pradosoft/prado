<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TColorPicker;
use Prado\Web\UI\WebControls\TColorPickerClientSide;
use Prado\Web\UI\WebControls\TColorPickerMode;

class TColorPickerTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPicker(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['getPradoScriptAssetUrl', 'registerEndScript', 'registerPradoScript', 'isStyleSheetFileRegistered', 'registerStyleSheetFile', 'registerPostBackControl'])->getMock();
		$cs->method('getPradoScriptAssetUrl')->willReturn('/js');
		PradoUnit::setProp($page, '_clientScript', $cs);
		$picker = new TColorPicker();
		$picker->setID('color');
		$page->getControls()->add($picker);
		return [$picker, $cs];
	}

	private function postBackOptions(TColorPicker $picker): array
	{
		return PradoUnit::invoke($picker, 'getPostBackOptions');
	}

	public function testPropertyDefaultsAndSetters()
	{
		$picker = new TColorPicker();
		$this->assertTrue($picker->getShowColorPicker());
		$this->assertSame(TColorPickerMode::Basic, $picker->getMode());
		$this->assertSame('default', $picker->getColorPickerStyle());
		$this->assertSame('OK', $picker->getOKButtonText());
		$this->assertSame('Cancel', $picker->getCancelButtonText());
		$this->assertSame('Prado.WebUI.TColorPicker', PradoUnit::invoke($picker, 'getClientClassName'));
		$picker->setShowColorPicker('false');
		$picker->setMode('Full');
		$picker->setColorPickerStyle('dark');
		$picker->setOKButtonText('Yes');
		$picker->setCancelButtonText('No');
		$this->assertFalse($picker->getShowColorPicker());
		$this->assertSame(TColorPickerMode::Full, $picker->getMode());
		$this->assertSame('dark', $picker->getColorPickerStyle());
		$this->assertSame('Yes', $picker->getOKButtonText());
		$this->assertSame('No', $picker->getCancelButtonText());
	}

	public function testClientSideIsCreatedOnce()
	{
		$picker = new TColorPicker();
		$client = $picker->getClientSide();
		$this->assertInstanceOf(TColorPickerClientSide::class, $client);
		$this->assertSame($client, $picker->getClientSide());
		$client->setOnColorSelected('picked(color);');
		$this->assertStringContainsString('picked(color);', (string) $client->getOnColorSelected());
	}

	public static function modeProvider(): array
	{
		return [
			'Basic' => [TColorPickerMode::Basic, null, null],
			'Full' => [TColorPickerMode::Full, 'Full', null],
			'Simple' => [TColorPickerMode::Simple, null, 'Tiny'],
		];
	}

	/**
	 * @dataProvider modeProvider
	 */
	public function testPostBackOptionsDescribeTheMode(string $mode, ?string $modeOption, ?string $palette)
	{
		[$picker] = $this->createPicker();
		$picker->setCssClass('pick');
		$picker->setMode($mode);
		$picker->getClientSide()->setOnColorSelected('picked();');
		$options = $this->postBackOptions($picker);
		$this->assertSame('pick', $options['ClassName']);
		$this->assertTrue($options['ShowColorPicker']);
		$this->assertSame($modeOption, $options['Mode'] ?? null);
		$this->assertSame($palette, $options['Palette'] ?? null);
		$this->assertSame('OK', $options['OKButtonText']);
		$this->assertSame('Cancel', $options['CancelButtonText']);
		$this->assertArrayHasKey('OnColorSelected', $options);
	}

	public function testPostBackOptionsWithoutThePicker()
	{
		[$picker] = $this->createPicker();
		$picker->setShowColorPicker(false);
		$picker->setMode(TColorPickerMode::Full);
		$options = $this->postBackOptions($picker);
		$this->assertFalse($options['ShowColorPicker']);
		$this->assertArrayNotHasKey('Mode', $options);
		$this->assertArrayNotHasKey('OKButtonText', $options);
	}

	public function testOnPreRenderPublishesTheImagesScriptAndStyleSheet()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setColorPickerStyle('dark');
		$cs->expects($this->once())->method('registerEndScript')->with('prado:' . TColorPicker::class,
			'Prado.WebUI.TColorPicker.UIImages = {\'button.gif\':"\/js\/colorpicker\/button.gif",\'background.png\':"\/js\/colorpicker\/background.png"};');
		$cs->expects($this->once())->method('registerPradoScript')->with('colorpicker');
		$cs->method('isStyleSheetFileRegistered')->with('/js/colorpicker/dark.css')->willReturn(false);
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/js/colorpicker/dark.css', '/js/colorpicker/dark.css');
		$picker->onPreRender(null);
	}

	public function testOnPreRenderRegistersTheStyleSheetOnce()
	{
		[$picker, $cs] = $this->createPicker();
		$cs->method('isStyleSheetFileRegistered')->willReturn(true);
		$cs->expects($this->never())->method('registerStyleSheetFile');
		$picker->onPreRender(null);
	}

	public function testRenderAddsAnInteractiveButton()
	{
		[$picker] = $this->createPicker();
		$picker->setText('#ff0000');
		$output = $this->render($picker);
		$this->assertStringEndsWith('<span class="TColorPicker_button"><img id="color_button" src="/js/colorpicker/button.gif" style="background-color:#ff0000;" alt="" role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false" aria-label="Choose color" /></span>', $output);
	}

	public function testRenderUsesTheToolTipAsTheButtonLabel()
	{
		[$picker] = $this->createPicker();
		$picker->setToolTip('Pen color');
		$this->assertStringContainsString('aria-label="Pen color"', $this->render($picker));
	}

	public function testRenderWithoutThePickerHidesTheButton()
	{
		[$picker] = $this->createPicker();
		$picker->setShowColorPicker(false);
		$output = $this->render($picker);
		$this->assertStringEndsWith('<span class="TColorPicker_button" aria-hidden="true"><img id="color_button" src="/js/colorpicker/button.gif" alt="" /></span>', $output);
	}

	public static function buttonProvider(): array
	{
		// interactive, color, toolTip
		return [
			'interactive color tooltip' => [true, '#00f', 'Pen'],
			'interactive color' => [true, '#00f', ''],
			'interactive' => [true, '', ''],
			'mirror color' => [false, '#00f', ''],
		];
	}

	/**
	 * @dataProvider buttonProvider
	 */
	public function testButtonCombinations(bool $interactive, string $color, string $toolTip)
	{
		[$picker] = $this->createPicker();
		$picker->setShowColorPicker($interactive);
		$picker->setText($color);
		$picker->setToolTip($toolTip);
		$output = $this->render($picker);
		$this->assertSame(!$interactive, str_contains($output, 'aria-hidden="true"'));
		$this->assertSame($color !== '', str_contains($output, 'style="background-color:#00f;"'));
		$this->assertSame($interactive, str_contains($output, 'role="button"'));
		if ($interactive) {
			$this->assertStringContainsString('aria-label="' . ($toolTip !== '' ? $toolTip : 'Choose color') . '"', $output);
		}
	}

	public static function colorProvider(): array
	{
		return [
			'short hex' => ['#f00', true],
			'hex with alpha' => ['#ff000080', true],
			'rgb' => ['rgb(1, 2, 3)', true],
			'hsl with alpha' => ['hsl(120 50% 50% / 0.5)', true],
			'name' => ['red', true],
			'empty' => ['', false],
			'five hex digits' => ['#12345', false],
			'added declaration' => ['red;background-image:url(//host/x)', false],
			'function then declaration' => ['rgb(1);x:y', false],
			'other function' => ['expression(alert(1))', false],
			'trailing newline' => ["red\n", false],
		];
	}

	/**
	 * @dataProvider colorProvider
	 */
	public function testButtonShowsOnlyACssColor(string $color, bool $shown)
	{
		[$picker] = $this->createPicker();
		$picker->setText($color);
		$output = $this->render($picker);
		$this->assertSame($shown, str_contains($output, 'background-color:'));
		$this->assertStringNotContainsString('background-image', substr($output, strpos($output, 'TColorPicker_button')));
	}
}
