<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TSlider;
use Prado\Web\UI\WebControls\TSliderClientScript;
use Prado\Web\UI\WebControls\TSliderDirection;

class TSliderTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
		parent::tearDown();
	}

	private function createSlider(?TPanel $parent = null): array
	{
		$page = new TPage();
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['registerStyleSheetFile', 'registerPradoScript', 'registerHiddenField', 'registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$slider = new TSlider();
		$slider->setID('sl');
		if ($parent !== null) {
			$page->getControls()->add($parent);
			$parent->getControls()->add($slider);
		} else {
			$page->getControls()->add($slider);
		}
		return [$slider, $cs, $page];
	}

	private function options(TSlider $slider): array
	{
		return PradoUnit::invoke($slider, 'getSliderOptions');
	}

	public function testPropertyDefaultsAndSetters()
	{
		$slider = new TSlider();
		$this->assertSame(TSliderDirection::Horizontal, $slider->getDirection());
		$this->assertSame('', $slider->getCssUrl());
		$this->assertSame(100.0, $slider->getMaxValue());
		$this->assertSame(0.0, $slider->getMinValue());
		$this->assertSame(1.0, $slider->getStepSize());
		$this->assertTrue($slider->getProgressIndicator());
		$this->assertSame(0.0, $slider->getValue());
		$this->assertSame([], $slider->getValues());
		$this->assertFalse($slider->getAutoPostBack());
		$this->assertFalse($slider->getDataChanged());
		$this->assertSame('div', $slider->getTagName());
		$this->assertSame('Prado.WebUI.TSlider', PradoUnit::invoke($slider, 'getClientClassName'));

		$slider->setDirection('Vertical');
		$slider->setCssUrl('/my.css');
		$slider->setMaxValue('50');
		$slider->setMinValue('10');
		$slider->setStepSize('5');
		$slider->setProgressIndicator('false');
		$slider->setData('20');
		$slider->setValues('10,20,30');
		$slider->setAutoPostBack('true');
		$this->assertSame(TSliderDirection::Vertical, $slider->getDirection());
		$this->assertSame('/my.css', $slider->getCssUrl());
		$this->assertSame(50.0, $slider->getMaxValue());
		$this->assertSame(10.0, $slider->getMinValue());
		$this->assertSame(5.0, $slider->getStepSize());
		$this->assertFalse($slider->getProgressIndicator());
		$this->assertSame(20.0, $slider->getValue());
		$this->assertSame(20.0, $slider->getData());
		$this->assertSame([10, 20, 30], $slider->getValues());
		$this->assertTrue($slider->getAutoPostBack());
	}

	public function testClientSideIsCreatedOnce()
	{
		$slider = new TSlider();
		$client = $slider->getClientSide();
		$this->assertInstanceOf(TSliderClientScript::class, $client);
		$this->assertSame($client, $slider->getClientSide());
	}

	public function testClientScriptEvents()
	{
		$client = new TSliderClientScript();
		$client->setOnChange('changed(value);');
		$client->setOnSlide('slid(value);');
		$this->assertStringContainsString('function (value) { changed(value); }', (string) $client->getOnChange());
		$this->assertStringContainsString('function (value) { slid(value); }', (string) $client->getOnSlide());
	}

	// --- Post data ---

	public function testLoadPostDataReadsTheHiddenField()
	{
		[$slider] = $this->createSlider();
		$this->assertTrue($slider->loadPostData('sl', ['sl_1' => '42.5']));
		$this->assertSame(42.5, $slider->getValue());
		$this->assertTrue($slider->getDataChanged());
		$this->assertFalse($slider->loadPostData('sl', ['sl_1' => '42.5']));
	}

	public function testLoadPostDataWithoutTheHiddenFieldKeepsTheValue()
	{
		[$slider] = $this->createSlider();
		$slider->setValue(7);
		$this->assertFalse($slider->loadPostData('sl', []));
		$this->assertSame(7.0, $slider->getValue());
		$this->assertFalse($slider->getDataChanged());
	}

	public function testRaisePostDataChangedEventRaisesOnValueChanged()
	{
		$slider = new TSlider();
		$raised = [];
		$slider->attachEventHandler('OnValueChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$slider->raisePostDataChangedEvent();
		$this->assertSame([[$slider, null]], $raised);
	}

	// --- Rendering ---

	public function testRenderHorizontal()
	{
		[$slider] = $this->createSlider();
		$slider->setValue(30);
		$slider->setToolTip('Volume');
		$this->assertSame('<div id="sl" title="Volume" class="Slider HorizontalSlider">'
			. '<div class="Track" id="sl_track"><div class="Progress" id="sl_progress"></div></div>'
			. '<div class="Handle" id="sl_handle" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="30" aria-orientation="horizontal" aria-label="Volume" tabindex="0"></div>'
			. '</div>', $this->render($slider));
	}

	public function testRenderVerticalWithoutProgressAndDisabled()
	{
		[$slider] = $this->createSlider();
		$slider->setDirection(TSliderDirection::Vertical);
		$slider->setProgressIndicator(false);
		$slider->setEnabled(false);
		$this->assertSame('<div id="sl" disabled="disabled" class="Slider VerticalSlider">'
			. '<div class="Track" id="sl_track"></div>'
			. '<div class="Handle" id="sl_handle" role="slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-orientation="vertical" aria-disabled="true"></div>'
			. '</div>', $this->render($slider));
	}

	public function testCssClassReplacesTheDefaultClass()
	{
		[$slider] = $this->createSlider();
		$slider->setCssClass('mine');
		$output = $this->renderBeginTag($slider);
		$this->assertStringContainsString('class="mine"', $output);
		$this->assertStringNotContainsString('Slider', $output);
	}

	// --- PreRender ---

	public function testOnPreRenderPublishesTheDefaultStyleSheetAndRegistersTheScript()
	{
		$this->mockAssetManager();
		[$slider, $cs, $page] = $this->createSlider();
		$slider->setValue(5);
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/assets/TSlider/TSlider.css', '/assets/TSlider/TSlider.css');
		$cs->expects($this->once())->method('registerPradoScript')->with('slider');
		$cs->expects($this->once())->method('registerHiddenField')->with('sl_1', 5.0);
		$cs->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TSlider', $this->callback(fn ($options) => $options['ID'] === 'sl'));
		$slider->onPreRender(null);
		$this->assertArrayHasKey('sl', PradoUnit::getProp($page, '_controlsRegisteredForPostData'));
	}

	public function testOnPreRenderUsesTheCssUrl()
	{
		[$slider, $cs] = $this->createSlider();
		$slider->setCssUrl('/my.css');
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/my.css', '/my.css');
		$slider->onPreRender(null);
	}

	// --- Options ---

	public function testOptionsGenerateTheValuesFromTheStep()
	{
		[$slider] = $this->createSlider();
		$slider->setMinValue(0);
		$slider->setMaxValue(10);
		$slider->setStepSize(4);
		$slider->setValue(4);
		$slider->setAutoPostBack(true);
		$this->assertSame([
			'ID' => 'sl',
			'EventTarget' => 'sl',
			'AutoPostBack' => true,
			'axis' => 'horizontal',
			'maximum' => 10.0,
			'minimum' => 0.0,
			'range' => [0.0, 10.0],
			'step' => 4.0,
			'sliderValue' => 4.0,
			'disabled' => false,
			'values' => [0.0, 4.0, 8.0, 10.0],
		], $this->options($slider));
	}

	public function testOptionsKeepTheMaxReachedByTheStep()
	{
		[$slider] = $this->createSlider();
		$slider->setMaxValue(4);
		$slider->setStepSize(2);
		$this->assertSame([0.0, 2.0, 4.0], $this->options($slider)['values']);
	}

	public function testOptionsLimitTheGeneratedSteps()
	{
		[$slider] = $this->createSlider();
		$slider->setMaxValue(1000);
		$values = $this->options($slider)['values'];
		$this->assertCount(TSlider::MAX_STEPS + 1, $values);
		$this->assertSame(5.0, $values[1]);
	}

	public function testOptionsListTheGivenValuesInRangeWithMinAndMax()
	{
		[$slider] = $this->createSlider();
		$slider->setMinValue(10);
		$slider->setMaxValue(50);
		$slider->setValues([5, 20, 60, 30]);
		$this->assertSame([20, 30, 10.0, 50.0], $this->options($slider)['values']);
	}

	public function testOptionsKeepGivenMinAndMax()
	{
		[$slider] = $this->createSlider();
		$slider->setValues([0, 50, 100]);
		$this->assertSame([0, 50, 100], $this->options($slider)['values']);
	}

	public function testOptionsAreDisabledWithADisabledParent()
	{
		$panel = new TPanel();
		[$slider] = $this->createSlider($panel);
		$this->assertFalse($this->options($slider)['disabled']);
		$panel->setEnabled(false);
		$this->assertTrue($this->options($slider)['disabled']);
	}

	public function testOptionsMergeTheClientSideOptions()
	{
		[$slider] = $this->createSlider();
		$slider->getClientSide()->setOnChange('go();');
		$this->assertArrayHasKey('onChange', $this->options($slider));
	}

	public function testRenderContentsForEveryOptionCombination()
	{
		foreach ([true, false] as $progress) {
			foreach (['', 'Volume'] as $toolTip) {
				foreach ([true, false] as $enabled) {
					foreach ([TSliderDirection::Horizontal, TSliderDirection::Vertical] as $direction) {
						$panel = new TPanel();
						[$slider] = $this->createSlider($panel);
						$panel->setEnabled($enabled);
						$slider->setProgressIndicator($progress);
						$slider->setToolTip($toolTip);
						$slider->setDirection($direction);
						$output = $this->renderContents($slider);
						$case = "progress:$progress tooltip:$toolTip enabled:$enabled $direction";
						$this->assertSame($progress, str_contains($output, 'sl_progress'), $case);
						$this->assertSame($toolTip !== '', str_contains($output, 'aria-label="Volume"'), $case);
						$this->assertStringContainsString($enabled ? 'tabindex="0"' : 'aria-disabled="true"', $output, $case);
						$this->assertStringContainsString('aria-orientation="' . strtolower($direction) . '"', $output, $case);
					}
				}
			}
		}
	}

	public function testLoadPostDataIgnoresAnArrayValue()
	{
		[$slider] = $this->createSlider();
		$slider->setValue(7);
		$this->assertFalse($slider->loadPostData('sl', ['sl_1' => ['9']]));
		$this->assertSame(7.0, $slider->getValue());
	}
}
