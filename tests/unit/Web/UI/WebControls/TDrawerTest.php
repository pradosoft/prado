<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDialog;
use Prado\Web\UI\WebControls\TDialogClosedBy;
use Prado\Web\UI\WebControls\TDrawer;
use Prado\Web\UI\WebControls\TDrawerPlacement;

/**
 * Records published paths instead of copying files.
 */
class TDrawerTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = basename($path);
		return '/assets/' . basename($path);
	}
}

/**
 * Client script manager that records the script packages a control registers.
 */
class TDrawerTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	protected function registerPradoScriptInternal($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TDrawerTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TDrawerTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @return array{TPage, TDrawer} a drawer with the ID "nav" in a page
	 */
	private function newPageDrawer(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TDrawerTestClientScript($page));
		$drawer = new TDrawer();
		$drawer->setID('nav');
		$page->getControls()->add($drawer);
		return [$page, $drawer];
	}

	private function endScripts(TPage $page): string
	{
		return implode("\n", PradoUnit::getProp($page->getClientScript(), '_endScripts'));
	}

	private function styleBlocks(TPage $page): array
	{
		return PradoUnit::getProp($page->getClientScript(), '_styleSheets');
	}

	public function testDefaults()
	{
		$drawer = new TDrawer();
		$this->assertInstanceOf(TDialog::class, $drawer);
		$this->assertSame(TDrawerPlacement::Start, $drawer->getPlacement());
		$this->assertSame('', $drawer->getDockAt());
		$this->assertTrue($drawer->getSwipe());
		$this->assertSame(TDialogClosedBy::Any, $drawer->getClosedBy());
		$this->assertTrue($drawer->getShowCloseButton());
	}

	public function testDefaultRendering()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->getControls()->add('<p>Body</p>');
		$this->assertSame(
			'<dialog id="nav" closedby="any" class="prado-drawer" data-placement="start">'
			. '<div class="dialog-header"><button type="button" class="dialog-close" commandfor="nav" command="close" aria-label="Close">&times;</button></div>'
			. '<p>Body</p></dialog>',
			$this->render($drawer)
		);
	}

	public function testPlacement()
	{
		$drawer = new TDrawer();
		foreach (['End' => 'end', 'top' => 'top', 'Bottom' => 'bottom', 'Start' => 'start'] as $value => $attribute) {
			$drawer->setPlacement($value);
			$this->assertStringContainsString('data-placement="' . $attribute . '"', $this->render($drawer));
		}
		$this->expectException(TInvalidDataValueException::class);
		$drawer->setPlacement('Left');
	}

	public function testCssClassFollowsDrawerClass()
	{
		$drawer = new TDrawer();
		$drawer->setCssClass('prado-drawer wide');
		$this->assertStringContainsString('class="prado-drawer wide"', $this->render($drawer));
	}

	public function testClosedByAndCloseButtonSettersKeepTheirDefaults()
	{
		$drawer = new TDrawer();
		$drawer->setClosedBy(TDialogClosedBy::Auto);
		$this->assertSame(TDialogClosedBy::Auto, $drawer->getClosedBy());
		$this->assertStringNotContainsString('closedby', $this->render($drawer));
		$drawer->setShowCloseButton(false);
		$this->assertFalse($drawer->getShowCloseButton());
		$this->assertFalse($drawer->getHasHeader());
		$this->expectException(TInvalidDataValueException::class);
		$drawer->setClosedBy('outside');
	}

	public function testDockAtAcceptsWidths()
	{
		$drawer = new TDrawer();
		foreach (['64rem', '1024px', '62.5em', ' 40rem '] as $value) {
			$drawer->setDockAt($value);
			$this->assertSame(trim($value), $drawer->getDockAt());
		}
		$drawer->setDockAt('');
		$this->assertSame('', $drawer->getDockAt());
	}

	public function testDockAtRejectsOtherValues()
	{
		$drawer = new TDrawer();
		foreach (['64', '0rem', '-5px', '10vw', '1rem) { x', 'wide'] as $value) {
			try {
				$drawer->setDockAt($value);
				$this->fail('DockAt ' . $value . ' was accepted');
			} catch (TInvalidDataValueException $e) {
				$this->assertSame('drawer_dockat_invalid', $e->getErrorCode());
			}
		}
	}

	public function testDockWidthIsForStartAndEnd()
	{
		$drawer = new TDrawer();
		$drawer->setDockAt('64rem');
		$this->assertSame('64rem', $drawer->getDockWidth());
		$drawer->setPlacement(TDrawerPlacement::End);
		$this->assertSame('64rem', $drawer->getDockWidth());
		$drawer->setPlacement(TDrawerPlacement::Top);
		$this->assertSame('', $drawer->getDockWidth());
		$drawer->setPlacement(TDrawerPlacement::Bottom);
		$this->assertSame('', $drawer->getDockWidth());
	}

	public function testDockingRendersOpenWithoutClosedByThenNarrowScript()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setDockAt('64rem');
		$output = $this->render($drawer);
		$this->assertStringStartsWith('<dialog id="nav" class="prado-drawer" data-placement="start" open="open">', $output);
		$this->assertStringNotContainsString('closedby=', $output);
		$script = substr($output, strpos($output, '</dialog>'));
		$this->assertStringContainsString('<script', $script);
		$this->assertStringContainsString("matchMedia(\"(min-width: 64rem)\").matches", $script);
		$this->assertStringContainsString('d.close();', $script);
		$this->assertStringContainsString('d.setAttribute("closedby","any");', $script);
		$this->assertStringNotContainsString('d.showModal()', $script);
		$this->assertStringContainsString('document.getElementById("nav")', $script);
	}

	public function testDockingClosedByAutoSetsNoClosedBy()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setDockAt('64rem');
		$drawer->setClosedBy(TDialogClosedBy::Auto);
		$this->assertStringNotContainsString('closedby', $this->render($drawer));
	}

	public function testDockingOpenShowsModallyOnNarrowOnly()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setDockAt('64rem');
		$drawer->setOpen(true);
		$drawer->setModal(true);
		$output = $this->render($drawer);
		$this->assertStringContainsString('open="open"', $output);
		$this->assertStringContainsString('d.showModal();', substr($output, strpos($output, '</dialog>')));
		$this->assertStringNotContainsString('prado:dialog:', implode("\n", array_keys(PradoUnit::getProp($page->getClientScript(), '_endScripts'))));
	}

	public function testPlainDrawerKeepsDialogOpening()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setOpen(true);
		$drawer->setModal(true);
		$output = $this->render($drawer);
		$this->assertStringNotContainsString('open=', $output);
		$this->assertStringNotContainsString('<script', $output);
		$this->assertArrayHasKey('prado:dialog:nav', PradoUnit::getProp($page->getClientScript(), '_endScripts'));
	}

	public function testPreRenderRegistersStyleSheetsAndClientControl()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setPlacement(TDrawerPlacement::End);
		$drawer->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertSame(['dialog.css', 'drawer.css'], array_values(array_unique(Prado::getApplication()->getAssetManager()->published)));
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/drawer.css'));
		$this->assertContains('drawer', $cs->pradoScripts);
		$this->assertStringContainsString(
			"new Prado.WebUI.TDrawer({'ID':\"nav\",'Placement':\"end\",'ClosedBy':\"any\",'Swipe':true",
			$this->endScripts($page)
		);
		$this->assertSame([], $this->styleBlocks($page));
	}

	public function testPreRenderWithoutHeaderStillRegistersStyleSheets()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setShowCloseButton(false);
		$drawer->onPreRender(null);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/assets/drawer.css'));
	}

	public function testCustomAndEmptyCssUrl()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setCssUrl('/theme/drawer.css');
		$drawer->onPreRender(null);
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/theme/drawer.css'));

		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setCssUrl('');
		$drawer->onPreRender(null);
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);
		$this->assertFalse($page->getClientScript()->isStyleSheetFileRegistered('/assets/drawer.css'));
	}

	public function testDockRulesAreScopedToTheDrawer()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setDockAt('64rem');
		$drawer->onPreRender(null);
		$css = $this->styleBlocks($page)['prado:drawer:nav'];
		$this->assertStringContainsString('@media (min-width: 64rem) {', $css);
		$this->assertStringContainsString('#nav:not(:modal) { position: static;', $css);
		$this->assertStringContainsString('@media (scripting: enabled) and (not (min-width: 64rem)) {', $css);
		$this->assertStringContainsString('#nav:not(:modal) { display: none; }', $css);
		$this->assertStringContainsString('@media (scripting: none) and (not (min-width: 64rem)) {', $css);
		$this->assertSame(2, substr_count($css, '[commandfor="nav"] { display: none; }'));
		$this->assertStringContainsString("'DockAt':\"64rem\"", $this->endScripts($page));
	}

	public function testClosedByNoneTurnsOffSwipe()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setClosedBy(TDialogClosedBy::None);
		$drawer->onPreRender(null);
		$this->assertStringContainsString("'ClosedBy':\"none\",'Swipe':false", $this->endScripts($page));
	}

	public function testSwipeOff()
	{
		[$page, $drawer] = $this->newPageDrawer();
		$drawer->setSwipe('false');
		$this->assertFalse($drawer->getSwipe());
		$drawer->onPreRender(null);
		$this->assertStringContainsString("'Swipe':false", $this->endScripts($page));
	}
}
