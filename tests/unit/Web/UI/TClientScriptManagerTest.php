<?php

use Prado\Collections\TMap;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptAsset;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TButton;

class TClientScriptManagerTestable extends TClientScriptManager
{
	public static function resetJavascriptCache()
	{
		$reflection = new ReflectionClass(TClientScriptManager::class);
		$prop = $reflection->getProperty('_scripts');
		$prop->setAccessible(true);
		$prop->setValue(null, null);

		$prop = $reflection->getProperty('_scriptsPackages');
		$prop->setAccessible(true);
		$prop->setValue(null, null);

		$prop = $reflection->getProperty('_scriptsFolders');
		$prop->setAccessible(true);
		$prop->setValue(null, null);
	}
}

class MockFxLoadPradoJavascriptBehavior extends \Prado\Util\TBehavior
{
	public static array $callCount = [];
	public static array $receivedData = [];
	public ?array $feedForwardData = null;

	public function fxLoadPradoJavascript($sender, $foldersPackagesDependencies): array
	{
		self::$callCount[$sender] = (self::$callCount[$sender] ?? 0) + 1;
		self::$receivedData[$sender] = $foldersPackagesDependencies;

		if ($this->feedForwardData !== null) {
			return $this->feedForwardData;
		}

		[$folders, $packages, $dependencies] = $foldersPackagesDependencies;

		$folders['testplugin'] = 'Test\\Javascript';
		$packages['testplugin'] = ['testplugin/test.js'];
		$dependencies['testplugin'] = ['jquery', 'prado'];

		return [$folders, $packages, $dependencies];
	}
}

class MockCallbackControl extends TControl implements IActiveControl, ICallbackEventHandler
{
	private $_activeControl;

	public function __construct()
	{
		parent::__construct();
	}

	public function getActiveControl()
	{
		if ($this->_activeControl === null) {
			$this->_activeControl = new MockBaseActiveCallbackControl($this);
		}
		return $this->_activeControl;
	}

	public function raiseCallbackEvent($eventArgument)
	{
	}
}

class MockBaseActiveCallbackControl extends TBaseActiveControl
{
	private $_clientSide;

	public function __construct(TControl $control)
	{
		parent::__construct($control);
	}

	public function getClientSide(): TCallbackClientSide
	{
		if ($this->_clientSide === null) {
			$this->_clientSide = new TCallbackClientSide();
		}
		return $this->_clientSide;
	}

	public function getClientSideOptions(): array
	{
		return $this->getClientSide()->getOptions()->toArray();
	}
}

class MockButtonControl extends TControl implements \Prado\Web\UI\IButtonControl
{
	private $_text = '';
	private $_causesValidation = true;
	private $_commandName = '';
	private $_commandParameter = '';
	private $_validationGroup = '';
	private $_isDefaultButton = false;

	public function getText(): string
	{
		return $this->_text;
	}

	public function setText($value): void
	{
		$this->_text = $value;
	}

	public function getCausesValidation(): bool
	{
		return $this->_causesValidation;
	}

	public function setCausesValidation($value): void
	{
		$this->_causesValidation = $value;
	}

	public function getCommandName(): string
	{
		return $this->_commandName;
	}

	public function setCommandName($value): void
	{
		$this->_commandName = $value;
	}

	public function getCommandParameter(): string
	{
		return $this->_commandParameter;
	}

	public function setCommandParameter($value): void
	{
		$this->_commandParameter = $value;
	}

	public function getValidationGroup(): string
	{
		return $this->_validationGroup;
	}

	public function setValidationGroup($value): void
	{
		$this->_validationGroup = $value;
	}

	public function onClick($param): void
	{
	}

	public function onCommand($param): void
	{
	}

	public function setIsDefaultButton($value): void
	{
		$this->_isDefaultButton = $value;
	}

	public function getIsDefaultButton(): bool
	{
		return $this->_isDefaultButton;
	}
}

function rrmdir($src) {
	$dir = opendir($src);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			$full = $src . '/' . $file;
			if ( is_dir($full) ) {
				rrmdir($full);
			}
			else {
				unlink($full);
			}
		}
	}
	closedir($dir);
	rmdir($src);
}

class TClientScriptManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app;

	private $_page;
	private $_cs;

	protected function setUp(): void
	{
		self::$app = null;
		if (self::$app === null) {
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['SERVER_NAME'] = 'localhost';
			$_SERVER['SERVER_PORT'] = '80';
			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['REQUEST_URI'] = '/test/index.php';
			$_SERVER['PHP_SELF'] = '/test/index.php';
			$_SERVER['QUERY_STRING'] = '';
			$_SERVER['PATH_INFO'] = __FILE__;
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

			$_SERVER['SCRIPT_FILENAME'] = dirname(__FILE__) . '/../../../FunctionalTests/features/protected/';
			$_SERVER['SCRIPT_NAME'] = '/index.php';
			self::$app = new TApplication(realpath(dirname(__FILE__) . '/../app'));

			self::$app->initApplication();
		}

		$this->_page = new TPage();
		$this->_cs = new TClientScriptManager($this->_page);

		$assetManager = self::$app->getAssetManager();
	}

	protected function tearDown(): void
	{
		$this->_page = null;
		$this->_cs = null;

		if (self::$app !== null) {
			$assetManager = self::$app->getAssetManager();
			$basePath = $assetManager->getBasePath();
			
			$dirIterator = opendir($basePath);
			while(false !== ( $file = readdir($dirIterator)) ) {
				if ( $file[0] != '.' ) {
					$full = $basePath . '/' . $file;
					if ( is_dir($full) ) {
						rrmdir($full);
					}
				}
			}
			closedir($dirIterator);
		}
	}

	public function testConstruct()
	{
		$cs = new TClientScriptManager($this->_page);
		$this->assertSame($this->_page, $this->getPrivateProperty($cs, '_page'));
	}

	public function testGetRequiresHeadEmpty()
	{
		$this->assertFalse($this->_cs->getRequiresHead());
	}

	public function testGetRequiresHeadWithStyleSheetFile()
	{
		$this->_cs->registerStyleSheetFile('key1', 'http://example.com/style.css');
		$this->assertTrue($this->_cs->getRequiresHead());
	}

	public function testGetRequiresHeadWithStyleSheet()
	{
		$this->_cs->registerStyleSheet('key1', '.myclass { color: red; }');
		$this->assertTrue($this->_cs->getRequiresHead());
	}

	public function testGetRequiresHeadWithHeadScriptFile()
	{
		$this->_cs->registerHeadScriptFile('key1', 'http://example.com/script.js');
		$this->assertTrue($this->_cs->getRequiresHead());
	}

	public function testGetRequiresHeadWithHeadScript()
	{
		$this->_cs->registerHeadScript('key1', 'alert("hello");');
		$this->assertTrue($this->_cs->getRequiresHead());
	}

	public function testGetPradoPackages()
	{
		$packages = TClientScriptManager::getPradoPackages();
		$this->assertIsArray($packages);
		$this->assertArrayHasKey('prado', $packages);
		$this->assertArrayHasKey('ajax', $packages);
		$this->assertArrayHasKey('jquery', $packages);
	}

	public function testGetPradoScripts()
	{
		$scripts = TClientScriptManager::getPradoScripts();
		$this->assertIsArray($scripts);
		$this->assertArrayHasKey('jquery', $scripts);
		$this->assertArrayHasKey('prado', $scripts);
	}

	public function testRegisterStyleSheetFile()
	{
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style.css');
		$this->assertTrue($this->_cs->isStyleSheetFileRegistered('mycss'));

		$files = $this->getPrivateProperty($this->_cs, '_styleSheetFiles');
		$this->assertEquals('http://example.com/style.css', $files['mycss']);
	}

	public function testRegisterStyleSheetFileWithMedia()
	{
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style.css', 'print');
		$files = $this->getPrivateProperty($this->_cs, '_styleSheetFiles');
		$this->assertIsArray($files['mycss']);
		$this->assertEquals('http://example.com/style.css', $files['mycss'][0]);
		$this->assertEquals('print', $files['mycss'][1]);
	}

	public function testRegisterStyleSheetFileEmptyMediaIsNotArray()
	{
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style.css', '');
		$files = $this->getPrivateProperty($this->_cs, '_styleSheetFiles');
		$this->assertEquals('http://example.com/style.css', $files['mycss']);
	}

	public function testRegisterStyleSheet()
	{
		$css = '.myclass { color: red; }';
		$this->_cs->registerStyleSheet('mycss', $css);
		$this->assertTrue($this->_cs->isStyleSheetRegistered('mycss'));

		$sheets = $this->getPrivateProperty($this->_cs, '_styleSheets');
		$this->assertEquals($css, $sheets['mycss']);
	}

	public function testRegisterHeadScriptFile()
	{
		$this->_cs->registerHeadScriptFile('myjs', 'http://example.com/script.js');
		$this->assertTrue($this->_cs->isHeadScriptFileRegistered('myjs'));
	}

	public function testRegisterHeadScriptFileAsync()
	{
		$this->_cs->registerHeadScriptFile('myjs', 'http://example.com/script.js', true);
		$this->assertTrue($this->_cs->isHeadScriptFileRegistered('myjs'));

		$scripts = $this->getPrivateProperty($this->_cs, '_headScriptFiles');
		$this->assertInstanceOf(TJavaScriptAsset::class, $scripts['myjs']);
		$this->assertTrue($scripts['myjs']->getAsync());
	}

	public function testRegisterHeadScriptFileNotAsync()
	{
		$this->_cs->registerHeadScriptFile('myjs', 'http://example.com/script.js', false);
		$scripts = $this->getPrivateProperty($this->_cs, '_headScriptFiles');
		$this->assertInstanceOf(TJavaScriptAsset::class, $scripts['myjs']);
		$this->assertFalse($scripts['myjs']->getAsync());
	}

	public function testRegisterHeadScript()
	{
		$script = 'alert("hello");';
		$this->_cs->registerHeadScript('myjs', $script);
		$this->assertTrue($this->_cs->isHeadScriptRegistered('myjs'));

		$scripts = $this->getPrivateProperty($this->_cs, '_headScripts');
		$this->assertEquals($script, $scripts['myjs']);
	}

	public function testRegisterScriptFile()
	{
		$this->_cs->registerScriptFile('myjs', 'http://example.com/script.js');
		$this->assertTrue($this->_cs->isScriptFileRegistered('myjs'));

		$files = $this->getPrivateProperty($this->_cs, '_scriptFiles');
		$this->assertEquals('http://example.com/script.js', $files['myjs']);
	}

	public function testRegisterBeginScript()
	{
		$script = 'console.log("start");';
		$this->_cs->registerBeginScript('start', $script);
		$this->assertTrue($this->_cs->isBeginScriptRegistered('start'));

		$scripts = $this->getPrivateProperty($this->_cs, '_beginScripts');
		$this->assertEquals($script, $scripts['start']);
	}

	public function testRegisterEndScript()
	{
		$script = 'console.log("end");';
		$this->_cs->registerEndScript('end', $script);
		$this->assertTrue($this->_cs->isEndScriptRegistered('end'));

		$scripts = $this->getPrivateProperty($this->_cs, '_endScripts');
		$this->assertEquals($script, $scripts['end']);
	}

	public function testRegisterHiddenField()
	{
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->assertTrue($this->_cs->isHiddenFieldRegistered('field1'));

		$fields = $this->getPrivateProperty($this->_cs, '_hiddenFields');
		$this->assertEquals('value1', $fields['field1']);
	}

	public function testRegisterHiddenFieldArrayValue()
	{
		$this->_cs->registerHiddenField('field1', ['value1', 'value2']);
		$fields = $this->getPrivateProperty($this->_cs, '_hiddenFields');
		$this->assertEquals(['value1', 'value2'], $fields['field1']);
	}

	public function testIsStyleSheetFileRegistered()
	{
		$this->assertFalse($this->_cs->isStyleSheetFileRegistered('mycss'));
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style.css');
		$this->assertTrue($this->_cs->isStyleSheetFileRegistered('mycss'));
	}

	public function testIsStyleSheetRegistered()
	{
		$this->assertFalse($this->_cs->isStyleSheetRegistered('mycss'));
		$this->_cs->registerStyleSheet('mycss', '.myclass { color: red; }');
		$this->assertTrue($this->_cs->isStyleSheetRegistered('mycss'));
	}

	public function testIsHeadScriptFileRegistered()
	{
		$this->assertFalse($this->_cs->isHeadScriptFileRegistered('myjs'));
		$this->_cs->registerHeadScriptFile('myjs', 'http://example.com/script.js');
		$this->assertTrue($this->_cs->isHeadScriptFileRegistered('myjs'));
	}

	public function testIsHeadScriptRegistered()
	{
		$this->assertFalse($this->_cs->isHeadScriptRegistered('myjs'));
		$this->_cs->registerHeadScript('myjs', 'alert("hello");');
		$this->assertTrue($this->_cs->isHeadScriptRegistered('myjs'));
	}

	public function testIsScriptFileRegistered()
	{
		$this->assertFalse($this->_cs->isScriptFileRegistered('myjs'));
		$this->_cs->registerScriptFile('myjs', 'http://example.com/script.js');
		$this->assertTrue($this->_cs->isScriptFileRegistered('myjs'));
	}

	public function testIsBeginScriptRegistered()
	{
		$this->assertFalse($this->_cs->isBeginScriptRegistered('start'));
		$this->_cs->registerBeginScript('start', 'console.log("start");');
		$this->assertTrue($this->_cs->isBeginScriptRegistered('start'));
	}

	public function testIsEndScriptRegistered()
	{
		$this->assertFalse($this->_cs->isEndScriptRegistered('end'));
		$this->_cs->registerEndScript('end', 'console.log("end");');
		$this->assertTrue($this->_cs->isEndScriptRegistered('end'));
	}

	public function testIsHiddenFieldRegistered()
	{
		$this->assertFalse($this->_cs->isHiddenFieldRegistered('field1'));
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->assertTrue($this->_cs->isHiddenFieldRegistered('field1'));
	}

	public function testHasEndScripts()
	{
		$this->assertFalse($this->_cs->hasEndScripts());
		$this->_cs->registerEndScript('end', 'console.log("end");');
		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testHasBeginScripts()
	{
		$this->assertFalse($this->_cs->hasBeginScripts());
		$this->_cs->registerBeginScript('start', 'console.log("start");');
		$this->assertTrue($this->_cs->hasBeginScripts());
	}

	public function testHasEndScriptsEmpty()
	{
		$this->assertFalse($this->_cs->hasEndScripts());
	}

	public function testHasBeginScriptsEmpty()
	{
		$this->assertFalse($this->_cs->hasBeginScripts());
	}

	public function testGetScriptUrls()
	{
		$this->_cs->registerHeadScriptFile('headjs', 'http://example.com/head.js');
		$this->_cs->registerScriptFile('bodyjs', 'http://example.com/body.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertTrue(in_array('http://example.com/head.js', $urls, true));
		$this->assertTrue(in_array('http://example.com/body.js', $urls, true));
	}

	public function testGetScriptUrlsDuplicate()
	{
		$this->_cs->registerHeadScriptFile('headjs', 'http://example.com/script.js');
		$this->_cs->registerScriptFile('bodyjs', 'http://example.com/script.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertCount(1, $urls);
	}

	public function testGetScriptUrlsEmpty()
	{
		$urls = $this->_cs->getScriptUrls();
		$this->assertIsArray($urls);
		$this->assertEmpty($urls);
	}

	public function testGetStyleSheetUrls()
	{
		$this->_cs->registerStyleSheetFile('css1', 'http://example.com/style1.css');
		$this->_cs->registerStyleSheetFile('css2', 'http://example.com/style2.css', 'print');

		$urls = $this->_cs->getStyleSheetUrls();
		$this->assertTrue(in_array('http://example.com/style1.css', $urls, true));
		$this->assertTrue(in_array('http://example.com/style2.css', $urls, true));
	}

	public function testGetStyleSheetUrlsEmpty()
	{
		$urls = $this->_cs->getStyleSheetUrls();
		$this->assertIsArray($urls);
	}

	public function testGetStyleSheetCodes()
	{
		$css1 = '.myclass1 { color: red; }';
		$css2 = '.myclass2 { color: blue; }';
		$this->_cs->registerStyleSheet('css1', $css1);
		$this->_cs->registerStyleSheet('css2', $css2);

		$codes = $this->_cs->getStyleSheetCodes();
		$this->assertTrue(in_array($css1, $codes, true));
		$this->assertTrue(in_array($css2, $codes, true));
	}

	public function testGetStyleSheetCodesDuplicate()
	{
		$css = '.myclass { color: red; }';
		$this->_cs->registerStyleSheet('css1', $css);
		$this->_cs->registerStyleSheet('css2', $css);

		$codes = $this->_cs->getStyleSheetCodes();
		$this->assertCount(1, $codes);
	}

	public function testGetStyleSheetCodesEmpty()
	{
		$codes = $this->_cs->getStyleSheetCodes();
		$this->assertIsArray($codes);
		$this->assertEmpty($codes);
	}

	public function testRenderStyleSheetFiles()
	{
		$this->_cs->registerStyleSheetFile('css1', 'http://example.com/style1.css');
		$this->_cs->registerStyleSheetFile('css2', 'http://example.com/style2.css', 'print');

		$writer = $this->createWriter();
		$this->_cs->renderStyleSheetFiles($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('href="http://example.com/style1.css"', $output);
		$this->assertStringContainsString('href="http://example.com/style2.css"', $output);
		$this->assertStringContainsString('media="print"', $output);
	}

	public function testRenderStyleSheetFilesEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderStyleSheetFiles($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderStyleSheets()
	{
		$css = '.myclass { color: red; }';
		$this->_cs->registerStyleSheet('mycss', $css);

		$writer = $this->createWriter();
		$this->_cs->renderStyleSheets($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('<style type="text/css">', $output);
		$this->assertStringContainsString($css, $output);
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
		$this->assertStringContainsString('/*]]>*/', $output);
	}

	public function testRenderStyleSheetsEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderStyleSheets($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderHeadScriptFiles()
	{
		$this->_cs->registerHeadScriptFile('js1', 'http://example.com/script1.js');

		$writer = $this->createWriter();
		$this->_cs->renderHeadScriptFiles($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('src="http://example.com/script1.js"', $output);
	}

	public function testRenderHeadScriptFilesAsync()
	{
		$this->_cs->registerHeadScriptFile('js1', 'http://example.com/script1.js', true);

		$writer = $this->createWriter();
		$this->_cs->renderHeadScriptFiles($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('async ', $output);
		$this->assertStringContainsString('src="http://example.com/script1.js"', $output);
	}

	public function testRenderHeadScriptFilesEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderHeadScriptFiles($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderHeadScripts()
	{
		$script = 'alert("hello");';
		$this->_cs->registerHeadScript('myjs', $script);

		$writer = $this->createWriter();
		$this->_cs->renderHeadScripts($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('<script>', $output);
		$this->assertStringContainsString($script, $output);
		$this->assertStringContainsString('/*<![CDATA[*/', $output);
	}

	public function testRenderBeginScripts()
	{
		$script = 'console.log("start");';
		$this->_cs->registerBeginScript('start', $script);

		$writer = $this->createWriter();
		$this->_cs->renderBeginScripts($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('<script>', $output);
		$this->assertStringContainsString($script, $output);
	}

	public function testRenderBeginScriptsEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderBeginScripts($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderEndScripts()
	{
		$script = 'console.log("end");';
		$this->_cs->registerEndScript('end', $script);

		$writer = $this->createWriter();
		$this->_cs->renderEndScripts($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('<script>', $output);
		$this->assertStringContainsString($script, $output);
	}

	public function testRenderEndScriptsEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderEndScripts($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderBeginScriptsCallback()
	{
		$script = 'console.log("start");';
		$this->_cs->registerBeginScript('start', $script);

		$writer = $this->createWriter();
		$this->_cs->renderBeginScriptsCallback($writer);
		$output = $writer->flush();

		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString($script, $output);
	}

	public function testRenderEndScriptsCallback()
	{
		$script = 'console.log("end");';
		$this->_cs->registerEndScript('end', $script);

		$writer = $this->createWriter();
		$this->_cs->renderEndScriptsCallback($writer);
		$output = $writer->flush();

		$this->assertStringNotContainsString('<script>', $output);
		$this->assertStringContainsString($script, $output);
	}

	public function testRenderBeginScriptsCallbackEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderBeginScriptsCallback($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderEndScriptsCallbackEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderEndScriptsCallback($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRegisterCallbackControlNoOptions()
	{
		$control = new MockCallbackControl();
		$control->setID('callback1');
		$control->setPage($this->_page);

		$this->_cs->registerCallbackControl('Prado.WebUI.CallbackControl', []);

		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testRegisterCallbackControlWithOptions()
	{
		$control = new MockCallbackControl();
		$control->setID('callback1');
		$control->setPage($this->_page);

		$options = ['param1' => 'value1'];
		$this->_cs->registerCallbackControl('Prado.WebUI.CallbackControl', $options);

		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testRegisterPostBackControlNullClass()
	{
		$this->_cs->registerPostBackControl(null, []);
		$this->assertFalse($this->_cs->hasEndScripts());
	}

	public function testRegisterPostBackControlWithOptions()
	{
		$control = new MockButtonControl();
		$control->setID('button1');
		$control->setPage($this->_page);

		$options = ['EventTarget' => 'button1'];
		$this->_cs->registerPostBackControl('Prado.WebUI.PostBackControl', $options);

		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testRegisterDefaultButtonWithPanelAndButtonIds()
	{
		$panelId = 'panel1';
		$buttonId = 'button1';

		$this->_cs->registerDefaultButton($panelId, $buttonId);

		$this->assertTrue($this->_cs->isEndScriptRegistered('prado:' . $panelId));
		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testRegisterDefaultButtonWithControlObjects()
	{
		$panel = new MockButtonControl();
		$panel->setID('panel1');
		$panel->setPage($this->_page);

		$button = new MockButtonControl();
		$button->setID('button1');
		$button->setPage($this->_page);

		$this->_cs->registerDefaultButton($panel, $button);

		$this->assertTrue($this->_cs->isEndScriptRegistered('prado:' . $panel->getUniqueID()));
		$this->assertTrue($button->getIsDefaultButton());
	}

	public function testRegisterDefaultButtonWithInvalidButton()
	{
		$panelId = 'panel1';
		$button = new \stdClass();

		$this->_cs->registerDefaultButton($panelId, $button);
		$this->assertFalse($this->_cs->hasEndScripts());
	}

	public function testRegisterFocusControl()
	{
		$target = 'control1';

		$this->_cs->registerFocusControl($target);

		$this->assertTrue($this->_cs->isEndScriptRegistered('prado:focus'));
		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testRegisterFocusControlWithTControl()
	{
		$control = new TControl();
		$control->setID('control1');
		$control->setPage($this->_page);

		$this->_cs->registerFocusControl($control);

		$this->assertTrue($this->_cs->isEndScriptRegistered('prado:focus'));
	}

	public function testRegisterFocusControlEmptyString()
	{
		$this->_cs->registerFocusControl('');
		$this->assertTrue($this->_cs->isEndScriptRegistered('prado:focus'));
		$this->assertTrue($this->_cs->hasEndScripts());
	}

	public function testMarkScriptFileAsRendered()
	{
		$url = 'http://example.com/script.js';

		$this->_cs->markScriptFileAsRendered($url);

		$rendered = $this->getPrivateProperty($this->_cs, '_renderedScriptFiles');
		$this->assertArrayHasKey($url, $rendered);
	}

	public function testMarkScriptFileAsRenderedWithTJavaScriptAsset()
	{
		$asset = new TJavaScriptAsset('http://example.com/script.js');

		$this->_cs->markScriptFileAsRendered($asset);

		$rendered = $this->getPrivateProperty($this->_cs, '_renderedScriptFiles');
		$this->assertArrayHasKey('http://example.com/script.js', $rendered);
	}

	public function testRenderAllPendingScriptFiles()
	{
		$this->_cs->registerScriptFile('js1', 'http://example.com/script1.js');
		$this->_cs->registerScriptFile('js2', 'http://example.com/script2.js');

		$writer = $this->createWriter();
		$this->_cs->renderAllPendingScriptFiles($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('src="http://example.com/script1.js"', $output);
		$this->assertStringContainsString('src="http://example.com/script2.js"', $output);

		$rendered = $this->getPrivateProperty($this->_cs, '_renderedScriptFiles');
		$this->assertArrayHasKey('http://example.com/script1.js', $rendered);
		$this->assertArrayHasKey('http://example.com/script2.js', $rendered);
	}

	public function testRenderAllPendingScriptFilesSkipsRendered()
	{
		$this->_cs->registerScriptFile('js1', 'http://example.com/script1.js');
		$this->_cs->markScriptFileAsRendered('http://example.com/script1.js');

		$writer = $this->createWriter();
		$this->_cs->renderAllPendingScriptFiles($writer);
		$output = $writer->flush();

		$this->assertStringNotContainsString('script1.js', $output);
	}

	public function testRenderAllPendingScriptFilesEmpty()
	{
		$writer = $this->createWriter();
		$this->_cs->renderAllPendingScriptFiles($writer);
		$output = $writer->flush();

		$this->assertEquals('', $output);
	}

	public function testRenderHiddenFieldsBegin()
	{
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->_cs->registerHiddenField('field2', 'value2');

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsBegin($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('type="text"', $output);
		$this->assertStringContainsString('name="field1"', $output);
		$this->assertStringContainsString('name="field2"', $output);
		$this->assertStringContainsString('autocomplete="off"', $output);
	}

	public function testRenderHiddenFieldsBeginThenEnd()
	{
		$this->_cs->registerHiddenField('field1', 'value1');

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsBegin($writer);
		$output1 = $writer->flush();
		$this->assertStringContainsString('value="value1"', $output1);

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsEnd($writer);
		$output2 = $writer->flush();
		$this->assertEquals('', $output2);
	}

	public function testRenderHiddenFieldsSkipsDuplicateNames()
	{
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->_cs->registerHiddenField('field1', 'value2');

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsBegin($writer);
		$output1 = $writer->flush();

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsEnd($writer);
		$output2 = $writer->flush();

		$this->assertStringContainsString('value="value2"', $output1);
		$this->assertStringNotContainsString('value="value1"', $output2);
	}

	public function testGetHiddenFields()
	{
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->_cs->registerHiddenField('field2', 'value2');

		$fields = $this->_cs->getHiddenFields();

		$this->assertCount(2, $fields);
		$this->assertEquals('value1', $fields['field1']);
		$this->assertEquals('value2', $fields['field2']);
	}

	public function testHiddenFieldNameSpecialCharacters()
	{
		$this->_cs->registerHiddenField('field:name', 'value');

		$writer = $this->createWriter();
		$this->_cs->renderHiddenFieldsBegin($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('id="field_name"', $output);
	}

	public function testRegisterScriptFileSameKeyOverwrites()
	{
		$this->_cs->registerScriptFile('myjs', 'http://example.com/script1.js');
		$this->_cs->registerScriptFile('myjs', 'http://example.com/script2.js');

		$files = $this->getPrivateProperty($this->_cs, '_scriptFiles');
		$this->assertEquals('http://example.com/script2.js', $files['myjs']);
	}

	public function testRegisterStyleSheetFileSameKeyOverwrites()
	{
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style1.css');
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style2.css');

		$files = $this->getPrivateProperty($this->_cs, '_styleSheetFiles');
		$this->assertEquals('http://example.com/style2.css', $files['mycss']);
	}

	public function testRegisterHeadScriptSameKeyOverwrites()
	{
		$this->_cs->registerHeadScript('myjs', 'alert("first");');
		$this->_cs->registerHeadScript('myjs', 'alert("second");');

		$scripts = $this->getPrivateProperty($this->_cs, '_headScripts');
		$this->assertEquals('alert("second");', $scripts['myjs']);
	}

	public function testRegisterBeginScriptSameKeyOverwrites()
	{
		$this->_cs->registerBeginScript('start', 'console.log("first");');
		$this->_cs->registerBeginScript('start', 'console.log("second");');

		$scripts = $this->getPrivateProperty($this->_cs, '_beginScripts');
		$this->assertEquals('console.log("second");', $scripts['start']);
	}

	public function testRegisterEndScriptSameKeyOverwrites()
	{
		$this->_cs->registerEndScript('end', 'console.log("first");');
		$this->_cs->registerEndScript('end', 'console.log("second");');

		$scripts = $this->getPrivateProperty($this->_cs, '_endScripts');
		$this->assertEquals('console.log("second");', $scripts['end']);
	}

	public function testRegisterStyleSheetSameKeyOverwrites()
	{
		$this->_cs->registerStyleSheet('mycss', '.first { color: red; }');
		$this->_cs->registerStyleSheet('mycss', '.second { color: blue; }');

		$sheets = $this->getPrivateProperty($this->_cs, '_styleSheets');
		$this->assertEquals('.second { color: blue; }', $sheets['mycss']);
	}

	public function testRegisterHiddenFieldSameKeyOverwrites()
	{
		$this->_cs->registerHiddenField('field1', 'value1');
		$this->_cs->registerHiddenField('field1', 'value2');

		$fields = $this->getPrivateProperty($this->_cs, '_hiddenFields');
		$this->assertEquals('value2', $fields['field1']);
	}

	public function testRegisterHiddenFieldEmptyValue()
	{
		$this->_cs->registerHiddenField('field1', '');
		$this->assertTrue($this->_cs->isHiddenFieldRegistered('field1'));
	}

	public function testRenderStyleSheetFilesWithSpecialCharactersInUrl()
	{
		$this->_cs->registerStyleSheetFile('mycss', 'http://example.com/style.css?foo=bar&baz=qux');

		$writer = $this->createWriter();
		$this->_cs->renderStyleSheetFiles($writer);
		$output = $writer->flush();

		$this->assertStringContainsString('href="http://example.com/style.css?foo=bar', $output);
	}

	public function testGetDefaultButtonOptions()
	{
		$panelId = 'panel1';
		$buttonId = 'button1';

		$options = $this->invokePrivateMethod($this->_cs, 'getDefaultButtonOptions', [$panelId, $buttonId]);

		$this->assertArrayHasKey('ID', $options);
		$this->assertArrayHasKey('Panel', $options);
		$this->assertArrayHasKey('Target', $options);
		$this->assertArrayHasKey('EventTarget', $options);
		$this->assertArrayHasKey('Event', $options);
		$this->assertEquals($buttonId, $options['EventTarget']);
		$this->assertEquals('click', $options['Event']);
		$this->assertEquals($panelId, $options['ID']);
		$this->assertEquals($panelId, $options['Panel']);
		$this->assertEquals($buttonId, $options['Target']);
	}

	public function testGetScriptPackageFolder()
	{
		$script = 'prado/prado.js';
		[$base, $subPath] = $this->invokePrivateMethod($this->_cs, 'getScriptPackageFolder', [$script]);

		$this->assertIsString($base);
		$this->assertEquals('prado.js', $subPath);
	}

	public function testGetScriptPackageFolderInvalidBase()
	{
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->invokePrivateMethod($this->_cs, 'getScriptPackageFolder', ['invalid_base/script.js']);
	}

	public function testGetStylePackageFolder()
	{
		$this->_cs->registerPradoStyle('jquery-ui');
		$style = 'jquery-ui/jquery-ui.css';
		[$base, $subPath] = $this->invokePrivateMethod($this->_cs, 'getStylePackageFolder', [$style]);

		$this->assertIsString($base);
		$this->assertEquals('jquery-ui.css', $subPath);
	}

	public function testGetStylePackageFolderInvalidBase()
	{
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->invokePrivateMethod($this->_cs, 'getStylePackageFolder', ['invalid_base/style.css']);
	}

	public function testRegisterHeadScriptInRenderThrowsException()
	{
		$reflection = new ReflectionClass($this->_page);
		$prop = $reflection->getProperty('_inFormRender');
		$prop->setAccessible(true);
		$prop->setValue($this->_page, true);

		$this->expectException(\Exception::class);
		$this->_cs->registerHeadScript('myjs', 'alert("hello");');
	}

	public function testRegisterHeadScriptFileInRenderThrowsException()
	{
		$reflection = new ReflectionClass($this->_page);
		$prop = $reflection->getProperty('_inFormRender');
		$prop->setAccessible(true);
		$prop->setValue($this->_page, true);

		$this->expectException(\Exception::class);
		$this->_cs->registerHeadScriptFile('myjs', 'http://example.com/script.js');
	}

	public function testRegisterBeginScriptInRenderThrowsException()
	{
		$reflection = new ReflectionClass($this->_page);
		$prop = $reflection->getProperty('_inFormRender');
		$prop->setAccessible(true);
		$prop->setValue($this->_page, true);

		$this->expectException(\Exception::class);
		$this->_cs->registerBeginScript('start', 'console.log("start");');
	}

	public function testGetRenderedScriptFiles()
	{
		$this->_cs->registerScriptFile('js1', 'http://example.com/script1.js');

		$rendered = $this->invokePrivateMethod($this->_cs, 'getRenderedScriptFiles');
		$this->assertIsArray($rendered);
	}

	public function testGetScriptUrlsWithHeadScriptFiles()
	{
		$this->_cs->registerHeadScriptFile('headjs', 'http://example.com/head.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertTrue(in_array('http://example.com/head.js', $urls, true));
	}

	public function testGetScriptUrlsWithScriptFiles()
	{
		$this->_cs->registerScriptFile('bodyjs', 'http://example.com/body.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertTrue(in_array('http://example.com/body.js', $urls, true));
	}

	public function testMultipleScriptRegistrationsSameUrl()
	{
		$this->_cs->registerHeadScriptFile('headjs', 'http://example.com/script.js');
		$this->_cs->registerScriptFile('bodyjs', 'http://example.com/script.js');
		$this->_cs->registerHeadScriptFile('headjs2', 'http://example.com/script.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertCount(1, $urls);
	}

	public function testClearAndGetScriptUrls()
	{
		$this->_cs->registerScriptFile('js1', 'http://example.com/script1.js');
		$this->_cs->registerScriptFile('js2', 'http://example.com/script2.js');

		$urls = $this->_cs->getScriptUrls();
		$this->assertCount(2, $urls);
	}

	public function testRegisterPradoScriptJquery()
	{
		$this->_cs->registerPradoScript('jquery');
		$this->assertTrue($this->getPrivateProperty($this->_cs, '_registeredScripts')['jquery'] ?? false);
	}

	public function testRegisterPradoScriptPrado()
	{
		$this->_cs->registerPradoScript('prado');
		$this->assertTrue($this->getPrivateProperty($this->_cs, '_registeredScripts')['prado'] ?? false);
	}

	public function testRegisterPradoScriptInvalid()
	{
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->_cs->registerPradoScript('invalid_script_name');
	}

	public function testRegisterPradoStyleJqueryUi()
	{
		$this->_cs->registerPradoStyle('jquery-ui');
		$this->assertTrue($this->getPrivateProperty($this->_cs, '_registeredStyles')['jquery-ui'] ?? false);
	}

	public function testRegisterPradoStyleInvalid()
	{
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->_cs->registerPradoStyle('invalid_style_name');
	}

	public function testGetPradoScriptAssetUrl()
	{
		$url = $this->_cs->getPradoScriptAssetUrl('jquery');
		$this->assertIsString($url);
	}

	public function testGetPradoScriptAssetPath()
	{
		$path = $this->_cs->getPradoScriptAssetPath('jquery');
		$this->assertIsString($path);
	}

	public function testGetCallbackReference()
	{
		$control = new MockCallbackControl();
		$control->setID('callback1');
		$control->setPage($this->_page);

		$options = ['onSuccess' => 'function() { }'];
		$ref = $this->_cs->getCallbackReference($control, $options);

		$this->assertIsString($ref);
		$this->assertStringContainsString('Prado.CallbackRequest', $ref);
		$this->assertStringContainsString($control->getUniqueID(), $ref);
	}

	public function testGetCallbackReferenceWithEmptyOptions()
	{
		$control = new MockCallbackControl();
		$control->setID('callback1');
		$control->setPage($this->_page);

		$ref = $this->_cs->getCallbackReference($control);

		$this->assertIsString($ref);
		$this->assertStringContainsString('Prado.CallbackRequest', $ref);
	}

	public function testGetCallbackReferenceWithClientSideOptions()
	{
		$control = new MockCallbackControl();
		$control->setID('callback1');
		$control->setPage($this->_page);

		$activeControl = $control->getActiveControl();
		$this->assertNotNull($activeControl);
		$this->assertInstanceOf(MockBaseActiveCallbackControl::class, $activeControl, 'instanceof ' . $activeControl::class);

		$ref = $this->_cs->getCallbackReference($control);

		$this->assertIsString($ref);
		$this->assertStringContainsString('Prado.CallbackRequest', $ref);
	}

	public function testEnsurePradoJavascriptCallsFxLoadPradoJavascript()
	{
		TClientScriptManagerTestable::resetJavascriptCache();

		$behavior = new MockFxLoadPradoJavascriptBehavior();
		$behavior->feedForwardData = null;

		$sender = TClientScriptManagerTestable::class;
		Prado::getApplication()->attachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);

		try {
			TClientScriptManagerTestable::ensurePradoJavascript();

			$this->assertArrayHasKey($sender, MockFxLoadPradoJavascriptBehavior::$callCount);
			$this->assertEquals(1, MockFxLoadPradoJavascriptBehavior::$callCount[$sender]);

			$this->assertArrayHasKey($sender, MockFxLoadPradoJavascriptBehavior::$receivedData);
			$received = MockFxLoadPradoJavascriptBehavior::$receivedData[$sender];
			$this->assertCount(3, $received);

			[$folders, $packages, $dependencies] = $received;
			$this->assertArrayHasKey('prado', $folders);
			$this->assertArrayHasKey('jquery', $folders);
			$this->assertArrayHasKey('prado', $packages);
			$this->assertArrayHasKey('jquery', $dependencies);
		} finally {
			Prado::getApplication()->detachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);
			TClientScriptManagerTestable::resetJavascriptCache();
		}
	}

	public function testEnsurePradoJavascriptWithFeedForwardData()
	{
		TClientScriptManagerTestable::resetJavascriptCache();

		$customFolders = ['custom' => 'Custom\\Js'];
		$customPackages = ['custom' => ['custom/custom.js']];
		$customDeps = ['custom' => ['jquery']];

		$behavior = new MockFxLoadPradoJavascriptBehavior();
		$behavior->feedForwardData = [$customFolders, $customPackages, $customDeps];

		Prado::getApplication()->attachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);

		try {
			TClientScriptManagerTestable::ensurePradoJavascript();

			$scriptsFolders = TClientScriptManagerTestable::getPradoScriptsFolders();
			$scriptsPackages = TClientScriptManagerTestable::getPradoPackages();
			$scripts = TClientScriptManagerTestable::getPradoScripts();

			$this->assertArrayHasKey('custom', $scriptsFolders);
			$this->assertArrayHasKey('custom', $scriptsPackages);
			$this->assertArrayHasKey('custom', $scripts);

			$this->assertEquals('Custom\\Js', $scriptsFolders['custom']);
			$this->assertEquals(['custom/custom.js'], $scriptsPackages['custom']);
			$this->assertEquals(['jquery'], $scripts['custom']);
		} finally {
			Prado::getApplication()->detachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);
			TClientScriptManagerTestable::resetJavascriptCache();
		}
	}

	public function testEnsurePradoJavascriptNoFeedForwardWhenEmpty()
	{
		TClientScriptManagerTestable::resetJavascriptCache();

		$behavior = new MockFxLoadPradoJavascriptBehavior();
		$behavior->feedForwardData = null;

		Prado::getApplication()->attachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);

		try {
			TClientScriptManagerTestable::ensurePradoJavascript();

			$scriptsFolders = TClientScriptManagerTestable::getPradoScriptsFolders();
			$scriptsPackages = TClientScriptManagerTestable::getPradoPackages();
			$scripts = TClientScriptManagerTestable::getPradoScripts();

			$this->assertArrayHasKey('testplugin', $scriptsFolders);
			$this->assertArrayHasKey('testplugin', $scriptsPackages);
			$this->assertArrayHasKey('testplugin', $scripts);

			$this->assertEquals('Test\\Javascript', $scriptsFolders['testplugin']);
			$this->assertEquals(['testplugin/test.js'], $scriptsPackages['testplugin']);
			$this->assertEquals(['jquery', 'prado'], $scripts['testplugin']);
		} finally {
			Prado::getApplication()->detachEventHandler('fxLoadPradoJavascript', [$behavior, 'fxLoadPradoJavascript']);
			TClientScriptManagerTestable::resetJavascriptCache();
		}
	}

	private function createWriter()
	{
		return new THtmlWriter(new TTextWriter());
	}

	private function getPrivateProperty($object, $property)
	{
		$reflection = new ReflectionClass($object);
		$prop = $reflection->getProperty($property);
		$prop->setAccessible(true);
		return $prop->getValue($object);
	}

	private function invokePrivateMethod($object, $method, $args = [])
	{
		$reflection = new ReflectionClass($object);
		$method = $reflection->getMethod($method);
		$method->setAccessible(true);
		return $method->invokeArgs($object, $args);
	}
}
