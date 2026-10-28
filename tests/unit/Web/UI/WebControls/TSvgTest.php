<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TSvg;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * Client script manager that records registered style sheets.
 */
class TSvgTestClientScript extends TClientScriptManager
{
	public array $styles = [];

	public function registerStyleSheet($key, $css, $media = '')
	{
		$this->styles[$key] = $css;
	}
}

class TSvgTest extends TestCase
{
	use TWebControlRenderTrait;

	private const ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="g"/></defs><style>.s{fill:url(#g)}</style><circle class="s" r="2" onclick="x()"/></svg>';

	private function svg(string $id = 'icon'): TSvg
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TSvgTestClientScript($page));
		$svg = new TSvg();
		$svg->setID($id);
		$page->getControls()->add($svg);
		return $svg;
	}

	public function testDefaults()
	{
		$svg = new TSvg();
		$this->assertInstanceOf(TWebControl::class, $svg);
		foreach (['SvgFile', 'Svg', 'ViewBox', 'PreserveAspectRatio', 'AlternateText', 'Description'] as $name) {
			$this->assertSame('', $svg->{'get' . $name}(), $name);
		}
		$this->assertSame(-1, $svg->getWidth());
		$this->assertSame(-1, $svg->getHeight());
		$this->assertFalse($svg->getDecorative());
		$this->assertTrue($svg->getSanitize());
		$this->assertTrue($svg->getPrefixIds());
		$this->assertTrue($svg->getScopeStyles());
		$this->assertTrue($svg->getHoistStyles());
		$this->assertFalse($svg->getReducedMotion());
		$this->assertSame('<svg></svg>', $this->render($svg));
	}

	public function testTemplateMarkupWithSizeAndViewBox()
	{
		$svg = new TSvg();
		$svg->setViewBox(' 0 0 24 24 ');
		$svg->setPreserveAspectRatio('xMidYMid meet');
		$svg->setWidth('24');
		$svg->setHeight(24);
		$svg->getControls()->add('<path d="M0 0"/>');
		$this->assertSame('<svg viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" width="24" height="24"><path d="M0 0"/></svg>', $this->render($svg));
		$svg->setWidth(-1);
		$this->assertSame(-1, $svg->getWidth());
	}

	public function testAlternateTextAndDescription()
	{
		$svg = $this->svg();
		$svg->setAlternateText('R&D <chart>');
		$svg->setDescription('Rose "20%"');
		$this->assertSame(
			'<svg role="img" aria-labelledby="icon_title" aria-describedby="icon_desc" id="icon">'
			. '<title id="icon_title">R&amp;D &lt;chart&gt;</title><desc id="icon_desc">Rose &quot;20%&quot;</desc></svg>',
			$this->render($svg)
		);
	}

	public function testRoleReplacesImage()
	{
		$svg = $this->svg();
		$svg->setAlternateText('Map');
		$svg->setRole('group');
		$this->assertStringStartsWith('<svg aria-labelledby="icon_title" id="icon" role="group">', $this->render($svg));
	}

	public function testDecorativeIsHidden()
	{
		$svg = $this->svg();
		$svg->setDecorative(true);
		$svg->setAlternateText('ignored');
		$this->assertSame('<svg aria-hidden="true" id="icon"></svg>', $this->render($svg));
	}

	public function testUnnamedGraphicRendersAsWritten()
	{
		$svg = $this->svg();
		$this->assertTrue(PradoUnit::invoke($svg, 'getNeedsTextAlternative'));
		$this->assertSame('<svg id="icon"></svg>', $this->render($svg));
		$svg->setRole('group');
		$this->assertFalse(PradoUnit::invoke($svg, 'getNeedsTextAlternative'));
	}

	public function testSvgStringIsSanitizedPrefixedAndHoisted()
	{
		$svg = $this->svg();
		$svg->setSvg(self::ICON);
		$svg->setAlternateText('Icon');
		$svg->onPreRender(null);
		$this->assertSame(
			'<svg viewBox="0 0 24 24" fill="none" role="img" aria-labelledby="icon_title" id="icon">'
			. '<title id="icon_title">Icon</title><defs><linearGradient id="icon_g"/></defs><circle class="s" r="2"/></svg>',
			$this->render($svg)
		);
		$this->assertSame(['TSvg:' . md5('#icon .s {fill:url(#icon_g)}') => '#icon .s {fill:url(#icon_g)}'], $svg->getPage()->getClientScript()->styles);
	}

	public function testProcessingCanBeTurnedOff()
	{
		$svg = $this->svg();
		$svg->setSvg(self::ICON);
		$svg->setSanitize(false);
		$svg->setPrefixIds(false);
		$svg->setScopeStyles(false);
		$svg->setHoistStyles(false);
		$svg->onPreRender(null);
		$output = $this->render($svg);
		$this->assertStringContainsString('<linearGradient id="g"/>', $output);
		$this->assertStringContainsString('<style>.s{fill:url(#g)}</style>', $output);
		$this->assertStringContainsString('onclick="x()"', $output);
		$this->assertSame([], $svg->getPage()->getClientScript()->styles);
	}

	public function testControlAttributesReplaceRootAttributes()
	{
		$svg = $this->svg();
		$svg->setSvg(self::ICON);
		$svg->setViewBox('0 0 48 48');
		$svg->setDecorative(true);
		$this->assertStringStartsWith('<svg viewBox="0 0 48 48" fill="none" aria-hidden="true" id="icon">', $this->render($svg));
	}

	public function testDocumentTitleNamesTheGraphic()
	{
		$svg = $this->svg();
		$svg->setSvg('<svg xmlns="http://www.w3.org/2000/svg"><title>Logo</title><circle r="1"/></svg>');
		$svg->onPreRender(null);
		$this->assertFalse(PradoUnit::invoke($svg, 'getNeedsTextAlternative'));
		$this->assertSame('<svg role="img" aria-labelledby="icon_title" id="icon"><title id="icon_title">Logo</title><circle r="1"/></svg>', $this->render($svg));
	}

	public function testAlternateTextReplacesDocumentTitle()
	{
		$svg = $this->svg();
		$svg->setSvg('<svg xmlns="http://www.w3.org/2000/svg"><title>Old</title><desc>Old</desc><circle r="1"/></svg>');
		$svg->setAlternateText('New');
		$this->assertSame('<svg role="img" aria-labelledby="icon_title" id="icon"><title id="icon_title">New</title><circle r="1"/></svg>', $this->render($svg));
	}

	public function testSvgFile()
	{
		$path = tempnam(sys_get_temp_dir(), 'svg') . '.svg';
		file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0"/></svg>');
		try {
			$svg = $this->svg();
			$svg->setSvgFile($path);
			$svg->setDecorative(true);
			$this->assertSame('<svg viewBox="0 0 1 1" aria-hidden="true" id="icon"><path d="M0 0"/></svg>', $this->render($svg));
		} finally {
			unlink($path);
		}
	}

	public function testMissingSvgFileThrows()
	{
		$svg = $this->svg();
		$svg->setSvgFile('/no/such/icon.svg');
		$this->expectException(TInvalidDataValueException::class);
		$this->render($svg);
	}

	public function testChildrenRenderAfterDocumentContent()
	{
		$svg = $this->svg();
		$svg->setSvg('<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/></svg>');
		$svg->getControls()->add('<rect/>');
		$this->assertStringEndsWith('<circle r="1"/><rect/></svg>', $this->render($svg));
	}

	public function testReducedMotionRegistersScopedCss()
	{
		$svg = $this->svg();
		$svg->setReducedMotion(true);
		$svg->onPreRender(null);
		$this->assertSame(
			['TSvg:motion:icon' => '@media (prefers-reduced-motion: reduce) { #icon, #icon * { animation: none !important; transition: none !important; } }'],
			$svg->getPage()->getClientScript()->styles
		);
		$this->assertTrue($svg->getEnsureId());
	}

	public function testTwoCopiesKeepTheirOwnIds()
	{
		$a = $this->svg('a');
		$b = new TSvg();
		$b->setID('b');
		$a->getPage()->getControls()->add($b);
		foreach ([$a, $b] as $svg) {
			$svg->setSvg(self::ICON);
			$svg->setDecorative(true);
		}
		$this->assertStringContainsString('id="a_g"', $this->render($a));
		$this->assertStringContainsString('id="b_g"', $this->render($b));
	}
}
