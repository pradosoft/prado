<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLinkTag;
use Prado\Web\UI\WebControls\TMetaTag;
use Prado\Web\UI\WebControls\TWebManifest;
use Prado\Xml\TXmlDocument;

class TWebManifestTest extends TestCase
{
	private ?TTestApplication $_app = null;
	private array $_server = [];

	protected function setUp(): void
	{
		$this->_server = ['SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null, 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null];
		$_SERVER['SCRIPT_NAME'] = '/app/index.php';
		$_SERVER['REQUEST_URI'] = '/app/index.php';
		$this->_app = new TTestApplication();
		$assets = new TAssetManager();
		$assets->setBaseUrl('/app/assets');
		$this->_app->setAssetManager($assets);
		$this->_app->setService(new TPageService());
	}

	protected function tearDown(): void
	{
		$this->_app?->restoreApplication();
		$this->_app = null;
		foreach ($this->_server as $key => $value) {
			if ($value === null) {
				unset($_SERVER[$key]);
			} else {
				$_SERVER[$key] = $value;
			}
		}
	}

	private function createModule(array $properties = [], ?string $xml = null): TServiceWorkerModule
	{
		$config = null;
		if ($xml !== null) {
			$config = new TXmlDocument();
			$config->loadFromString($xml);
		}
		$module = new TServiceWorkerModule();
		$module->setID('serviceworker');
		$this->_app->setModule('serviceworker', $module);
		$module->setSubProperties($properties);
		$module->init($config);
		return $module;
	}

	private function createControl(): TWebManifest
	{
		$page = new TPage();
		$control = new TWebManifest();
		$page->getControls()->add($control);
		return $control;
	}

	private function renderControl(TWebManifest $control): string
	{
		$tw = new TTextWriter();
		$control->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testDefaults()
	{
		$control = new TWebManifest();
		foreach (['Href', 'CrossOrigin', 'Worker', 'ThemeColor', 'ThemeColorDark', 'ApplicationName', 'AppTitle', 'StatusBarStyle', 'AppleTouchIcon', 'AppleTouchIconSizes'] as $property) {
			$this->assertSame('', $control->{'get' . $property}(), $property);
		}
		$this->assertSame(TServiceWorkerModule::AUTO, $control->getModuleID());
		$this->assertFalse($control->getMobileWebAppCapable());
	}

	public function testRendersNothingWithoutPropertiesOrModule()
	{
		$this->assertSame('', $this->renderControl($this->createControl()));
		$this->assertSame([], $this->createControl()->createTags());
	}

	public function testRendersEveryTagInOrder()
	{
		$control = $this->createControl();
		$control->setHref('/app.webmanifest');
		$control->setCrossOrigin('Use-Credentials');
		$control->setThemeColor('#ffffff');
		$control->setThemeColorDark('#000000');
		$control->setApplicationName('Orders');
		$control->setMobileWebAppCapable('true');
		$control->setAppTitle('Ord');
		$control->setStatusBarStyle('Black-Translucent');
		$control->setAppleTouchIcon('/img/touch.png');
		$control->setAppleTouchIconSizes('180x180');
		$this->assertSame(
			'<link rel="manifest" href="/app.webmanifest" crossorigin="use-credentials" />' . "\n"
			. '<meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff" />' . "\n"
			. '<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000000" />' . "\n"
			. '<meta name="application-name" content="Orders" />' . "\n"
			. '<meta name="mobile-web-app-capable" content="yes" />' . "\n"
			. '<meta name="apple-mobile-web-app-title" content="Ord" />' . "\n"
			. '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />' . "\n"
			. '<link rel="apple-touch-icon" href="/img/touch.png" sizes="180x180" />' . "\n",
			$this->renderControl($control)
		);
		$tags = $control->createTags();
		$this->assertInstanceOf(TLinkTag::class, $tags[0]);
		$this->assertInstanceOf(TMetaTag::class, $tags[1]);
	}

	public function testThemeColorAloneHasNoMedia()
	{
		$control = $this->createControl();
		$control->setThemeColor('#123456');
		$this->assertSame('<meta name="theme-color" content="#123456" />' . "\n", $this->renderControl($control));
	}

	public function testEmptyHrefLinksTheWorkerManifest()
	{
		$this->createModule(['Manifest.Name' => 'Orders App', 'Manifest.ShortName' => 'Orders', 'Manifest.ThemeColor' => '#2b5797']);
		$control = $this->createControl();
		$this->assertSame('default', $control->getServiceWorkerDefinition()->getName());
		$this->assertSame(
			'<link rel="manifest" href="/app/index.php?sw=manifest" />' . "\n"
			. '<meta name="theme-color" content="#2b5797" />' . "\n"
			. '<meta name="apple-mobile-web-app-title" content="Orders" />' . "\n",
			$this->renderControl($control)
		);

		$control->setThemeColor('#000000');
		$control->setAppTitle('Mine');
		$output = $this->renderControl($control);
		$this->assertStringContainsString('content="#000000"', $output);
		$this->assertStringContainsString('content="Mine"', $output);
	}

	public function testAppTitleFallsBackToTheName()
	{
		$this->createModule(['Manifest.Name' => 'Orders App']);
		$this->assertStringContainsString('<meta name="apple-mobile-web-app-title" content="Orders App" />', $this->renderControl($this->createControl()));
	}

	public function testWorkerSelectsTheManifest()
	{
		$this->createModule(['Manifest.Name' => 'Orders'], '<module><worker name="admin" Scope="/app/admin/" Manifest.Name="Admin" /></module>');
		$control = $this->createControl();
		$control->setWorker('admin');
		$this->assertStringContainsString('href="/app/index.php?sw=manifest&amp;w=admin"', $this->renderControl($control));
		$control->setWorker('missing');
		$this->assertNull($control->getServiceWorkerDefinition());
		$this->assertSame('', $this->renderControl($control));
	}

	public function testWorkerWithoutAManifestLinksNothing()
	{
		$this->createModule();
		$control = $this->createControl();
		$this->assertNull($control->getServiceWorkerDefinition());
		$this->assertSame('', $this->renderControl($control));
	}

	public function testExplicitHrefWins()
	{
		$this->createModule(['Manifest.Name' => 'Orders', 'Manifest.ThemeColor' => '#2b5797']);
		$control = $this->createControl();
		$control->setHref('/mine.webmanifest');
		$this->assertSame('<link rel="manifest" href="/mine.webmanifest" />' . "\n", $this->renderControl($control));
	}

	public function testInvalidValuesThrow()
	{
		$control = new TWebManifest();
		try {
			$control->setStatusBarStyle('white');
			$this->fail('StatusBarStyle accepted white.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('webmanifest_statusbarstyle_invalid', $e->getErrorCode());
		}
		$this->expectException(TInvalidDataValueException::class);
		$control->setCrossOrigin('always');
	}

	public function testModuleIdOption()
	{
		$control = new TWebManifest();
		$control->setModuleID('');
		$this->assertSame(TServiceWorkerModule::AUTO, $control->getModuleID());
		$control->setModuleID('sw');
		$this->assertSame('sw', $control->getModuleID());
		$control->setWorker(' admin ');
		$this->assertSame('admin', $control->getWorker());
	}
}
