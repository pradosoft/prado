<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\THtmlElementTestTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCanvas;
use Prado\Web\UI\WebControls\TCanvasClientScript;
use Prado\Web\UI\WebControls\TCanvasContextType;

/**
 * Client script manager that records the scripts a control registers.
 */
class TCanvasTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];
	public array $endScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	public function registerEndScript($key, $script)
	{
		$this->endScripts[$key] = $script;
	}
}

class TCanvasTest extends TestCase
{
	use THtmlElementTestTrait;
	use TWebControlRenderTrait;

	protected function createElement(): THtmlElement
	{
		return new TCanvas();
	}

	protected function defaultTagName(): string
	{
		return 'canvas';
	}

	public function testWidthAndHeightRenderAsAttributes()
	{
		$control = new TCanvas();
		$this->assertSame(-1, $control->getWidth());
		$this->assertSame(-1, $control->getHeight());
		$this->assertSame('<canvas></canvas>', $this->render($control));
		$control->setWidth('400');
		$control->setHeight(200);
		$this->assertSame(400, $control->getWidth());
		$this->assertSame('<canvas width="400" height="200"></canvas>', $this->render($control));
		$control->setWidth(-5);
		$this->assertSame(-1, $control->getWidth());
	}

	public function testDisplaySizeIsStyle()
	{
		$control = new TCanvas();
		$control->getStyle()->setWidth('100%');
		$control->setWidth(400);
		$this->assertSame('<canvas width="400" style="width:100%;"></canvas>', $this->render($control));
	}

	public function testAlternateTextRendersImageRoleAndLabel()
	{
		$control = new TCanvas();
		$control->setAlternateText('Sales rose');
		$this->assertSame('<canvas role="img" aria-label="Sales rose"></canvas>', $this->render($control));
	}

	public function testRoleAndAriaLabelReplaceAlternateText()
	{
		$control = new TCanvas();
		$control->setAlternateText('Sales rose');
		$control->setRole('figure');
		$control->getAria()->add('aria-label', 'Sales chart');
		$this->assertSame('<canvas aria-label="Sales chart" role="figure"></canvas>', $this->render($control));
	}

	public function testFallbackContentRenders()
	{
		$control = new TCanvas();
		$control->getControls()->add('<table></table>');
		$this->assertSame('<canvas><table></table></canvas>', $this->render($control));
	}

	private function pageCanvas(): TCanvas
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TCanvasTestClientScript($page));
		$canvas = new TCanvas();
		$canvas->setID('chart');
		$page->getControls()->add($canvas);
		return $canvas;
	}

	public function testClientSide()
	{
		$control = new TCanvas();
		$this->assertInstanceOf(TCanvasClientScript::class, $control->getClientSide());
		$this->assertSame($control->getClientSide(), $control->getClientSide());
		$this->assertNull($control->getClientSide()->getOnDraw());
		$control->getClientSide()->setOnDraw('draw(parameter);');
		$this->assertInstanceOf(TJavaScriptLiteral::class, $control->getClientSide()->getOnDraw());
		$this->assertSame('function(sender, parameter){ draw(parameter); }', (string) $control->getClientSide()->getOnDraw());
	}

	public function testMatchDisplaySize()
	{
		$control = new TCanvas();
		$this->assertTrue($control->getMatchDisplaySize());
		$control->setMatchDisplaySize('false');
		$this->assertFalse($control->getMatchDisplaySize());
	}

	public function testContextType()
	{
		$control = new TCanvas();
		$this->assertSame(TCanvasContextType::Context2D, $control->getContextType());
		$control->setContextType('webgl2');
		$this->assertSame(TCanvasContextType::WebGL2, $control->getContextType());
		$this->expectException(TInvalidDataValueException::class);
		$control->setContextType('3d');
	}

	public function testClientOptions()
	{
		$control = new TCanvas();
		$control->setID('chart');
		$control->getClientSide()->setOnDraw('draw();');
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertSame('chart', $options['ID']);
		$this->assertSame('2d', $options['ContextType']);
		$this->assertTrue($options['MatchDisplaySize']);
		$this->assertInstanceOf(TJavaScriptLiteral::class, $options['onDraw']);
		$control->setContextType(TCanvasContextType::BitmapRenderer);
		$control->setMatchDisplaySize(false);
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertSame('bitmaprenderer', $options['ContextType']);
		$this->assertFalse($options['MatchDisplaySize']);
	}

	public function testNoScriptWithoutOnDraw()
	{
		$control = $this->pageCanvas();
		$control->onPreRender(null);
		$cs = $control->getPage()->getClientScript();
		$this->assertSame([], $cs->pradoScripts);
		$this->assertSame([], $cs->endScripts);
	}

	public function testOnDrawRegistersScript()
	{
		$control = $this->pageCanvas();
		$control->getClientSide()->setOnDraw('draw();');
		$control->onPreRender(null);
		$cs = $control->getPage()->getClientScript();
		$this->assertSame(['canvas'], $cs->pradoScripts);
		$script = $cs->endScripts['prado:chart'];
		$this->assertSame('new Prado.WebUI.TCanvas({\'ID\':"chart",\'ContextType\':"2d",\'MatchDisplaySize\':true,\'onDraw\':function(sender, parameter){ draw(); }});', $script);
		$this->assertTrue($control->getEnsureId());
	}

	public function testDisabledRegistersNoScript()
	{
		$control = $this->pageCanvas();
		$control->getClientSide()->setOnDraw('draw();');
		$control->setEnabled(false);
		$control->onPreRender(null);
		$this->assertSame([], $control->getPage()->getClientScript()->endScripts);
	}
}
