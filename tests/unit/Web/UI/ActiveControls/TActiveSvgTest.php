<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveOutput;
use Prado\Web\UI\ActiveControls\TActiveSvg;
use Prado\Web\UI\ActiveControls\TActiveUpdateMode;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TSvg;

/**
 * Page adapter that records the controls registered for a deferred render.
 */
class TActiveSvgTestPageAdapter extends TControlAdapter
{
	public array $rendered = [];

	public function registerControlToRender($control, $writer)
	{
		$this->rendered[$control->getUniqueID()] ??= $control;
	}

	public function getCallbackEventTarget()
	{
		return null;
	}
}

class TActiveSvgTest extends TestCase
{
	private const CHART = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect id="bar" width="5" height="5"/></svg>';

	/**
	 * Initializes the application response, whose output buffer starts with it, outside a test.
	 */
	public static function setUpBeforeClass(): void
	{
		Prado::getApplication()->getResponse();
	}

	/**
	 * Returns a loaded callback page with the recording adapter, holding the graphic Chart
	 * with the properties set before it loaded.
	 */
	private function createLoadedSvg(bool $callback = true, array $properties = []): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$adapter = new TActiveSvgTestPageAdapter($page);
		$page->setAdapter($adapter);
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$svg = new TActiveSvg();
		$svg->setID('Chart');
		$page->getControls()->add($svg);
		foreach ($properties as $name => $value) {
			$svg->{'set' . $name}($value);
		}
		PradoUnit::invoke($svg, 'setControlStage', TControl::CS_LOADED);
		return [$page, $adapter, $svg];
	}

	public function testExtendsTSvg()
	{
		$svg = new TActiveSvg();
		$this->assertInstanceOf(TSvg::class, $svg);
		$this->assertInstanceOf(IActiveControl::class, $svg);
		$this->assertInstanceOf(TBaseActiveControl::class, $svg->getActiveControl());
	}

	public function testEnsureIdAlwaysTrue()
	{
		$this->assertTrue((new TActiveSvg())->getEnsureId());
	}

	public function testUpdateModeIsMorph()
	{
		$page = new TPage();
		$svg = new TActiveSvg();
		$page->getControls()->add($svg);
		$this->assertSame(TActiveUpdateMode::Replace, $page->getCallbackUpdateMode());
		$this->assertSame(TActiveUpdateMode::Morph, $svg->getActiveControl()->getUpdateMode());
		$this->assertTrue($svg->getActiveControl()->getIsMorphUpdate());
		$svg->getActiveControl()->setUpdateMode(TActiveUpdateMode::Default);
		$this->assertFalse($svg->getActiveControl()->getIsMorphUpdate());
	}

	public function testSettersWithoutPage()
	{
		$svg = new TActiveSvg();
		$svg->setSvg(self::CHART);
		$svg->setViewBox('0 0 20 20');
		$svg->setPixelWidth(40);
		$svg->setDecorative(true);
		$this->assertSame(self::CHART, $svg->getSvg());
		$this->assertSame('0 0 20 20', $svg->getViewBox());
		$this->assertSame(40, $svg->getPixelWidth());
		$this->assertTrue($svg->getDecorative());
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testChangeRegistersTheRender(string $property, $value)
	{
		[, $adapter, $svg] = $this->createLoadedSvg();
		$svg->{'set' . $property}($value);
		$this->assertSame(['Chart' => $svg], $adapter->rendered, $property);
	}

	/**
	 * @dataProvider propertyProvider
	 */
	public function testTheSameValueRegistersNothing(string $property, $value)
	{
		[, $adapter, $svg] = $this->createLoadedSvg(true, [$property => $value]);
		$svg->{'set' . $property}($value);
		$this->assertSame([], $adapter->rendered, $property);
	}

	public static function propertyProvider(): array
	{
		return [
			'Svg' => ['Svg', self::CHART],
			'SvgFile' => ['SvgFile', 'Application.icons.chart'],
			'ViewBox' => ['ViewBox', '0 0 20 20'],
			'PreserveAspectRatio' => ['PreserveAspectRatio', 'xMinYMin slice'],
			'PixelWidth' => ['PixelWidth', 40],
			'PixelHeight' => ['PixelHeight', 30],
			'AlternateText' => ['AlternateText', 'Sales'],
			'Description' => ['Description', 'Sales by month'],
			'Decorative' => ['Decorative', true],
		];
	}

	public function testSeveralChangesRegisterOnce()
	{
		[, $adapter, $svg] = $this->createLoadedSvg();
		$svg->setSvg(self::CHART);
		$svg->setAlternateText('Sales');
		$svg->setPixelWidth(40);
		$this->assertCount(1, $adapter->rendered);
	}

	public function testNoRegistrationOutsideACallback()
	{
		[, $adapter, $svg] = $this->createLoadedSvg(false);
		$svg->setSvg(self::CHART);
		$this->assertSame([], $adapter->rendered);
	}

	public function testNoRegistrationWithoutEnableUpdate()
	{
		[, $adapter, $svg] = $this->createLoadedSvg();
		$svg->getActiveControl()->setEnableUpdate(false);
		$svg->setSvg(self::CHART);
		$this->assertSame([], $adapter->rendered);
	}

	public function testRegistrationStopsTheUpdatesOfActiveControlsInside()
	{
		[, , $svg] = $this->createLoadedSvg();
		$output = new TActiveOutput();
		$svg->getControls()->add($output);
		$svg->setAlternateText('Sales');
		$this->assertFalse($output->getActiveControl()->getEnableUpdate());
	}

	public function testRenderBeforePreRenderIsDeferred()
	{
		[$page, $adapter, $svg] = $this->createLoadedSvg();
		$svg->render(new THtmlWriter(new TTextWriter()));
		$this->assertSame(['Chart' => $svg], $adapter->rendered);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testRenderAfterPreRenderMorphsTheElement()
	{
		[$page, , $svg] = $this->createLoadedSvg();
		$svg->setSvg(self::CHART);
		$svg->setAlternateText('Sales');
		PradoUnit::invoke($svg, 'setControlStage', TControl::CS_PRERENDERED);
		$writer = new THtmlWriter(new TTextWriter());
		$svg->render($writer);
		$calls = $page->getClientFunctions();
		$this->assertCount(1, $calls);
		$params = $calls[0]['Prado.Element.replace'];
		$this->assertSame('Chart', $params[0]);
		$this->assertCount(5, $params);
		$this->assertTrue($params[4]);
		$markup = $writer->flush();
		$this->assertStringContainsString('<title id="Chart_title">Sales</title>', $markup);
		$this->assertStringContainsString('id="Chart_bar"', $markup);
	}

	public function testViewTransitionTurnsOnTheResponseTransition()
	{
		[$page, , $svg] = $this->createLoadedSvg();
		PradoUnit::invoke($svg, 'setControlStage', TControl::CS_PRERENDERED);
		$svg->render(new THtmlWriter(new TTextWriter()));
		$this->assertFalse($page->getCallbackClient()->getViewTransition());

		[$page, , $svg] = $this->createLoadedSvg();
		$svg->getActiveControl()->setViewTransition(true);
		PradoUnit::invoke($svg, 'setControlStage', TControl::CS_PRERENDERED);
		$svg->render(new THtmlWriter(new TTextWriter()));
		$this->assertTrue($page->getCallbackClient()->getViewTransition());
		$this->assertTrue($page->getClientFunctions()[0]['Prado.Element.replace'][4], 'The graphic still morphs.');
	}

	public function testReplaceModeSendsNoMorphArgument()
	{
		[$page, , $svg] = $this->createLoadedSvg();
		$svg->getActiveControl()->setUpdateMode(TActiveUpdateMode::Replace);
		PradoUnit::invoke($svg, 'setControlStage', TControl::CS_PRERENDERED);
		$svg->render(new THtmlWriter(new TTextWriter()));
		$this->assertCount(4, $page->getClientFunctions()[0]['Prado.Element.replace']);
	}
}
