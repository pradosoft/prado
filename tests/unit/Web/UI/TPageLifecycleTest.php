<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\I18N\TGlobalization;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Web\TTestCookieResponse;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\IPageStatePersister;
use Prado\Web\UI\IPostBackEventHandler;
use Prado\Web\UI\ITheme;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * Returns a URL for a published path without copying files.
 */
class TPageLifecycleTestAssetManager extends TAssetManager
{
	public function getBaseUrl()
	{
		return '/assets';
	}

	public function getBasePath()
	{
		return sys_get_temp_dir();
	}

	public function getPublishedPath($path)
	{
		return $this->getBasePath() . DIRECTORY_SEPARATOR . basename($path);
	}

	public function publishFilePath($path, $checkTimestamp = false)
	{
		return '/assets/' . basename($path);
	}
}

/**
 * Records what is written to the response.
 */
class TPageLifecycleTestResponse extends TTestCookieResponse
{
	public string $written = '';

	public function write($str)
	{
		$this->written .= $str;
	}
}

/**
 * Keeps the page state of the last saved page for the next page.
 */
class TPageLifecycleTestPersister extends TComponent implements IPageStatePersister
{
	public static mixed $state = null;
	private ?TPage $_page = null;

	public function getPage()
	{
		return $this->_page;
	}

	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	public function save($state)
	{
		self::$state = $state;
	}

	public function load()
	{
		return self::$state;
	}
}

/**
 * A page service whose theme manager returns the themes a test sets.
 */
class TPageLifecycleTestService extends TPageService
{
	public array $themes = [];

	public function getThemeManager()
	{
		$themes = $this->themes;
		return new class ($themes) {
			public function __construct(private array $themes)
			{
			}

			public function getTheme($name)
			{
				return $this->themes[$name] ?? null;
			}
		};
	}
}

/**
 * A theme with fixed style sheet and JavaScript files that records the skinned controls.
 */
class TPageLifecycleTestTheme extends TComponent implements ITheme
{
	public array $skinned = [];

	public function __construct(private array $styleSheets = [], private array $scripts = [])
	{
		parent::__construct();
	}

	public function applySkin($control)
	{
		$this->skinned[] = $control;
	}

	public function getStyleSheetFiles()
	{
		return $this->styleSheets;
	}

	public function getJavaScriptFiles()
	{
		return $this->scripts;
	}
}

/**
 * Names the CSS media type of a theme style sheet ending in ".screen.css".
 */
class TPageLifecycleTestMediaBehavior extends TBehavior
{
	public function dyCssMediaType($type, $url, $chain)
	{
		return str_ends_with($url, '.screen.css') ? 'screen' : $chain->dyCssMediaType($type, $url);
	}
}

/**
 * A control that receives postback events without loading post data.
 */
class TPageLifecycleTestEventControl extends TControl implements IPostBackEventHandler
{
	public array $raised = [];

	public function raisePostBackEvent($param)
	{
		$this->raised[] = $param;
	}
}

/**
 * Records the life cycle methods it runs.
 */
class TPageLifecycleTestRecorder extends TControl
{
	public static array $calls = [];

	public function onInit($param)
	{
		self::$calls[] = 'init';
		parent::onInit($param);
	}

	public function onLoad($param)
	{
		self::$calls[] = 'load';
		parent::onLoad($param);
	}

	public function onPreRender($param)
	{
		self::$calls[] = 'prerender';
		parent::onPreRender($param);
	}

	public function onUnload($param)
	{
		self::$calls[] = 'unload';
		parent::onUnload($param);
	}
}

/**
 * A page whose controls are built in code, inside a form, by the test.
 */
class TPageLifecycleTestPage extends TPage
{
	public array $events = [];
	/** @var ?callable builds the controls of the form, receives the page and the form */
	public $build;
	/** @var ?callable runs on load, receives the page */
	public $load;
	/** @var ?callable runs on pre-render, receives the page */
	public $preRender;

	public function __construct()
	{
		parent::__construct();
		$this->setEnableCsrfValidation(false);
		$this->setStatePersisterClass(TPageLifecycleTestPersister::class);
	}

	public function createChildControls()
	{
		$form = new TForm();
		$form->setID('Form');
		$this->getControls()->add($form);
		if ($this->build) {
			($this->build)($this, $form);
		}
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->load) {
			($this->load)($this);
		}
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->preRender) {
			($this->preRender)($this);
		}
	}

	/**
	 * Adds a text box that records its changes.
	 * @param TControl $parent the parent of the text box
	 * @param string $id the ID of the text box
	 * @return TTextBox the text box
	 */
	public function addTextBox(TControl $parent, string $id): TTextBox
	{
		$box = new TTextBox();
		$box->setID($id);
		$box->attachEventHandler('OnTextChanged', fn () => $this->events[] = 'changed:' . $id . '=' . $box->getText());
		$parent->getControls()->add($box);
		return $box;
	}

	/**
	 * Adds a button that records its clicks.
	 * @param TControl $parent the parent of the button
	 * @param string $id the ID of the button
	 * @return TButton the button
	 */
	public function addButton(TControl $parent, string $id): TButton
	{
		$button = new TButton();
		$button->setID($id);
		$button->attachEventHandler('OnClick', fn () => $this->events[] = 'click:' . $id);
		$parent->getControls()->add($button);
		return $button;
	}
}

class TPageLifecycleTest extends \PHPUnit\Framework\TestCase
{
	private ?TPageLifecycleTestResponse $_response = null;

	protected function setUp(): void
	{
		TPageLifecycleTestPersister::$state = null;
		TPageLifecycleTestRecorder::$calls = [];
	}

	protected function tearDown(): void
	{
		TPageLifecycleTestPersister::$state = null;
		$this->_response = null;
	}

	/**
	 * Runs a page through its life cycle with the given request items, restoring the globals afterwards.
	 * @param TPage $page the page to run
	 * @param array $items the request items, as GET or POST data
	 * @param ?TPageService $service the page service, a new one when null
	 * @return string the rendered output
	 */
	private function runPage(TPage $page, array $items = [], ?TPageService $service = null): string
	{
		$app = Prado::getApplication();
		$obLevel = ob_get_level();
		$savedResponse = $app->getResponse();
		$savedService = $app->getService();
		$savedHandler = $app->getErrorHandler();
		$savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$request = $app->getRequest();
		$savedItems = PradoUnit::getProp($request, '_items');
		$savedAssets = PradoUnit::getProp($app, '_assetManager');
		$app->setAssetManager(new TPageLifecycleTestAssetManager());
		$app->setResponse($this->_response = new TPageLifecycleTestResponse());
		$app->setService($service ?? new TPageService());
		PradoUnit::setProp($request, '_items', $items);
		try {
			$writer = new THtmlWriter(new TTextWriter());
			$page->run($writer);
			return $writer->flush();
		} finally {
			PradoUnit::setProp($request, '_items', $savedItems);
			PradoUnit::setProp($app, '_assetManager', $savedAssets);
			PradoUnit::setProp($app, '_globalization', $savedGlobalization);
			$app->setResponse($savedResponse);
			$app->setService($savedService);
			$app->setErrorHandler($savedHandler);
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	/**
	 * @param ?callable $build builds the controls of the form, receives the page and the form
	 * @return TPageLifecycleTestPage a page with a text box "Name" and a button "Go" in its form, and the given controls
	 */
	private function newPage(?callable $build = null): TPageLifecycleTestPage
	{
		$page = new TPageLifecycleTestPage();
		$page->build = function (TPageLifecycleTestPage $page, TForm $form) use ($build) {
			$page->addTextBox($form, 'Name');
			$page->addButton($form, 'Go');
			if ($build) {
				$build($page, $form);
			}
		};
		return $page;
	}

	// -----------------------------------------------------------------------
	// Normal request
	// -----------------------------------------------------------------------

	public function testNormalRequestRunsTheLifeCycleAndRendersTheForm(): void
	{
		$page = $this->newPage(fn ($page, $form) => $form->getControls()->add(new TPageLifecycleTestRecorder()));
		$page->load = fn ($page) => $page->setViewState('Visits', 1);
		$html = $this->runPage($page);

		$this->assertFalse($page->getIsPostBack());
		$this->assertFalse($page->getIsCallback());
		$this->assertSame(['init', 'load', 'prerender', 'unload'], TPageLifecycleTestRecorder::$calls);
		$this->assertStringContainsString('<form id="Form" method="post"', $html);
		$this->assertStringContainsString('name="Name"', $html);
		$this->assertStringContainsString('name="' . TPage::FIELD_PAGESTATE . '"', $html);
		$this->assertSame(1, TPageLifecycleTestPersister::$state[0]['Visits']);
		$this->assertNull($page->getRequestClientState());
		$this->assertNull($page->getPostBackEventTarget());
		$this->assertNull($page->getPostBackEventParameter());
	}

	// -----------------------------------------------------------------------
	// Postback
	// -----------------------------------------------------------------------

	public function testPostBackLoadsTheStateAndRaisesTheChangedAndClickEvents(): void
	{
		$page = $this->newPage();
		$page->load = fn ($page) => $page->getIsPostBack() || $page->setViewState('Visits', 1);
		$this->runPage($page);

		$page = $this->newPage();
		$seen = [];
		$page->load = function ($page) use (&$seen) {
			$page->setViewState('Visits', $page->getViewState('Visits') + 1);
			$seen = [$page->getIsCallback(), $page->getRequestClientState()];
		};
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state', 'Name' => 'Ann', 'Go' => 'Go']);

		$this->assertTrue($page->getIsPostBack());
		$this->assertSame([false, 'state'], $seen);
		$this->assertSame(['changed:Name=Ann', 'click:Go'], $page->events);
		$this->assertSame(2, TPageLifecycleTestPersister::$state[0]['Visits']);
		$this->assertSame('Go', $page->getPostBackEventTarget()->getID());
		$this->assertSame('', $page->getPostBackEventParameter());
	}

	public function testPostBackTargetAloneMakesAPostBack(): void
	{
		$page = $this->newPage();
		$this->runPage($page, [TPage::FIELD_POSTBACK_TARGET => 'Go', TPage::FIELD_POSTBACK_PARAMETER => 'arg']);
		$this->assertTrue($page->getIsPostBack());
		$this->assertSame(['click:Go'], $page->events);
		$this->assertSame('arg', $page->getPostBackEventParameter());
	}

	public function testPostBackKeepsTheTargetNamedByTheRequest(): void
	{
		$page = $this->newPage(fn ($page, $form) => $page->addButton($form, 'Other'));
		$this->runPage($page, [TPage::FIELD_POSTBACK_TARGET => 'Go', 'Other' => 'Other']);
		$this->assertSame(['click:Go'], $page->events);
	}

	public function testPostBackOfAnUnchangedValueRaisesNoChangedEvent(): void
	{
		$page = $this->newPage(fn ($page, $form) => $form->findControl('Name')->setText('Ann'));
		$this->runPage($page);
		$page = $this->newPage();
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state', 'Name' => 'Ann']);
		$this->assertSame([], $page->events);
	}

	public function testPostBackWithoutATargetValidatesThePage(): void
	{
		$page = $this->newPage();
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state', 'Name' => 'Ann']);
		$this->assertSame(['changed:Name=Ann'], $page->events);
		$this->assertTrue($page->getIsValid());
		$this->assertTrue(PradoUnit::getProp($page, '_validated'));
	}

	public function testPostBackTargetThatHandlesNoEventIsIgnored(): void
	{
		$page = $this->newPage(function ($page, $form) {
			$plain = new TControl();
			$plain->setID('Plain');
			$form->getControls()->add($plain);
		});
		$this->runPage($page, [TPage::FIELD_POSTBACK_TARGET => 'Plain', 'Plain' => 'x']);
		$this->assertSame([], $page->events);
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	public function testPostBackTargetFromTheDataOfAnEventHandler(): void
	{
		$handler = null;
		$page = $this->newPage(function ($page, $form) use (&$handler) {
			$handler = new TPageLifecycleTestEventControl();
			$handler->setID('Handler');
			$form->getControls()->add($handler);
		});
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state', 'Handler' => 'x', TPage::FIELD_POSTBACK_PARAMETER => 'p']);
		$this->assertSame(['p'], $handler->raised);
	}

	public function testPostDataOfAControlAddedOnLoadLoadsAfterLoad(): void
	{
		$page = $this->newPage();
		$page->load = fn ($page) => $page->addTextBox($page->getForm(), 'Later');
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state', 'Later' => 'Bob', 'Unknown' => 'x']);
		$this->assertSame(['changed:Later=Bob'], $page->events);
		$this->assertFalse($page->getIsLoadingPostData());
	}

	public function testPostBackLoadsTheControlsThatRequirePostData(): void
	{
		$build = fn (bool $checked) => function ($page, $form) use ($checked) {
			$box = new TCheckBox();
			$box->setID('Agree');
			$box->setChecked($checked);
			$box->attachEventHandler('OnCheckedChanged', fn () => $page->events[] = 'checked:' . var_export($box->getChecked(), true));
			$form->getControls()->add($box);
		};
		$page = $this->newPage($build(true));
		$page->preRender = fn ($page) => $page->registerRequiresPostData('Missing');
		$this->runPage($page);
		$this->assertSame(['Missing' => true, 'Agree' => true], TPageLifecycleTestPersister::$state[0]['ControlsRequiringPostBack']);

		$page = $this->newPage($build(true));
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state']);
		$this->assertSame(['checked:false'], $page->events);
		$this->assertSame(['Missing' => true], PradoUnit::getProp($page, '_controlsRequiringPostData'));

		$page = $this->newPage($build(false));
		$this->runPage($page, [TPage::FIELD_PAGESTATE => 'state']);
		$this->assertSame([], $page->events);
	}

	public function testPostBackRejectsARequiredControlThatLoadsNoPostData(): void
	{
		$page = $this->newPage(function ($page, $form) {
			$plain = new TControl();
			$plain->setID('Plain');
			$form->getControls()->add($plain);
		});
		$page->preRender = fn ($page) => $page->registerRequiresPostData($page->getForm()->findControl('Plain'));
		$this->runPage($page);

		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage('Plain');
		$this->runPage($this->newPage(function ($page, $form) {
			$plain = new TControl();
			$plain->setID('Plain');
			$form->getControls()->add($plain);
		}), [TPage::FIELD_PAGESTATE => 'state']);
	}

	public function testRegisterRequiresPostDataRecordsTheActionInTheCachingStack(): void
	{
		$cache = new class () {
			public array $actions = [];

			public function registerAction($context, $funcName, $funcParams)
			{
				$this->actions[] = [$context, $funcName, $funcParams];
			}
		};
		$page = new TPage();
		$page->getCachingStack()->push($cache);
		$page->registerRequiresPostData('Box');
		$box = new TControl();
		$box->setID('Ctl');
		$page->getControls()->add($box);
		$page->registerRequiresPostData($box);
		$page->registerCachingAction('Page.ClientScript', 'registerStyleSheetFile', ['a']);
		$this->assertSame([
			['Page', 'registerRequiresPostData', ['Box']],
			['Page', 'registerRequiresPostData', ['Ctl']],
			['Page.ClientScript', 'registerStyleSheetFile', ['a']],
		], $cache->actions);

		$page = new TPage();
		$page->registerCachingAction('Page', 'none', []);
		$page->getCachingStack();
		$page->registerCachingAction('Page', 'none', []);
		$this->assertSame(0, $page->getCachingStack()->getCount());
	}

	// -----------------------------------------------------------------------
	// Callback
	// -----------------------------------------------------------------------

	public function testCallbackRaisesTheCallbackEventAndRendersTheResponse(): void
	{
		$build = function ($page, $form) {
			$button = new TActiveButton();
			$button->setID('Act');
			$button->attachEventHandler('OnCallback', fn ($sender, $param) => $page->events[] = 'callback:' . json_encode($param->getCallbackParameter()));
			$form->getControls()->add($button);
		};
		$this->runPage($this->newPage($build));

		$page = $this->newPage($build);
		$isCallback = null;
		$page->load = function ($page) use (&$isCallback) {
			$isCallback = $page->getIsCallback();
		};
		$output = $this->runPage($page, [
			TPage::FIELD_PAGESTATE => 'state',
			TPage::FIELD_CALLBACK_TARGET => 'Act',
			TPage::FIELD_CALLBACK_PARAMETER => '{"n":1}',
			'Name' => 'Ann',
		]);
		$this->assertTrue($isCallback);
		$this->assertSame(['changed:Name=Ann', 'callback:{"n":1}'], $page->events);
		$this->assertSame(' ', $output);
		$this->assertSame('Act', $page->getCallbackEventTarget()->getID());
		$this->assertEquals((object) ['n' => 1], $page->getCallbackEventParameter());
		$page->setCallbackEventParameter('p');
		$this->assertSame('p', $page->getCallbackEventParameter());
		$page->setCallbackEventTarget($page->getForm());
		$this->assertSame($page->getForm(), $page->getCallbackEventTarget());
		$this->assertNotSame($page->getCallbackClient(), new \Prado\Web\UI\ActiveControls\TCallbackClientScript());
		$this->assertSame($page->getCallbackClient(), $page->getCallbackClient());
	}

	public function testCallbackDecodesThePostDataToTheApplicationCharset(): void
	{
		$build = function ($page, $form) {
			$button = new TActiveButton();
			$button->setID('Act');
			$form->getControls()->add($button);
		};
		$this->runPage($this->newPage($build));

		$app = Prado::getApplication();
		$savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$globalization = new TGlobalization();
		$globalization->setCharset('ISO-8859-1');
		$page = $this->newPage($build);
		$page->load = fn ($page) => Prado::getApplication()->setGlobalization($globalization);
		try {
			$page->load = null;
			$app->setGlobalization($globalization);
			$this->runPage($page, [
				TPage::FIELD_PAGESTATE => 'state',
				TPage::FIELD_CALLBACK_TARGET => 'Act',
				TPage::FIELD_CALLBACK_PARAMETER => '',
				'Name' => "caf\u{e9}",
			]);
		} finally {
			PradoUnit::setProp($app, '_globalization', $savedGlobalization);
		}
		$this->assertSame(["changed:Name=caf\xe9"], $page->events);
	}

	public function testCallbackKeepsUtf8PostDataForAUtf8Charset(): void
	{
		$build = function ($page, $form) {
			$button = new TActiveButton();
			$button->setID('Act');
			$form->getControls()->add($button);
		};
		$this->runPage($this->newPage($build));

		$app = Prado::getApplication();
		$savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$globalization = new TGlobalization();
		$globalization->setCharset('utf-8');
		try {
			$app->setGlobalization($globalization);
			$page = $this->newPage($build);
			$this->runPage($page, [
				TPage::FIELD_PAGESTATE => 'state',
				TPage::FIELD_CALLBACK_TARGET => 'Act',
				'Name' => "caf\u{e9}",
			]);
		} finally {
			PradoUnit::setProp($app, '_globalization', $savedGlobalization);
		}
		$this->assertSame(["changed:Name=caf\u{e9}"], $page->events);
	}

	// -----------------------------------------------------------------------
	// Themes in onPreRenderComplete
	// -----------------------------------------------------------------------

	public function testPreRenderCompleteRegistersTheThemeFiles(): void
	{
		$theme = new TPageLifecycleTestTheme(['/t/a.css', '/t/a.print.css', '/t/b.screen.css'], ['/t/a.js']);
		$theme->attachBehavior('media', new TPageLifecycleTestMediaBehavior());
		$sheet = new TPageLifecycleTestTheme(['/s/c.css'], ['/s/c.js']);
		$service = new TPageLifecycleTestService();
		$service->themes = ['Basic' => $theme, 'Sheet' => $sheet];
		$page = $this->newPage(fn ($page) => $page->getControls()->insertAt(0, new THead()));
		$page->setTheme('Basic');
		$page->setStyleSheetTheme('Sheet');
		$html = $this->runPage($page, [], $service);

		$this->assertSame($theme, $page->getTheme());
		$this->assertSame($sheet, $page->getStyleSheetTheme());
		$this->assertMatchesRegularExpression('#type="text/css" href="/t/a.css"#', $html);
		$this->assertMatchesRegularExpression('#media="print" href="/t/a.print.css"#', $html);
		$this->assertMatchesRegularExpression('#media="screen" href="/t/b.screen.css"#', $html);
		$this->assertStringContainsString('href="/s/c.css"', $html);
		$this->assertStringContainsString('/t/a.js', $html);
		$this->assertStringContainsString('/s/c.js', $html);
		$this->assertContains($page->getForm(), $theme->skinned);
		$control = new TControl();
		$page->applyControlStyleSheet($control);
		$this->assertSame([$control], $sheet->skinned);
	}

	public function testPreRenderCompleteRequiresAHeadForHeadFiles(): void
	{
		$service = new TPageLifecycleTestService();
		$service->themes = ['Basic' => new TPageLifecycleTestTheme(['/t/a.css'])];
		$page = $this->newPage();
		$page->setTheme('Basic');
		try {
			$this->runPage($page, [], $service);
			$this->fail('a page without a head rendered head files');
		} catch (TConfigurationException $e) {
			$this->assertSame('page_head_required', $e->getErrorCode());
		}
	}

	public function testThemesAreOptional(): void
	{
		$page = new TPage();
		$page->setTheme('');
		$page->setStyleSheetTheme(null);
		$this->assertNull($page->getTheme());
		$this->assertNull($page->getStyleSheetTheme());
		$control = new TControl();
		$page->applyControlSkin($control);
		$page->applyControlStyleSheet($control);
		$this->assertFalse($control->getIsSkinApplied());
	}

	// -----------------------------------------------------------------------
	// Writer
	// -----------------------------------------------------------------------

	public function testFlushWriterWritesTheRenderedOutputToTheResponse(): void
	{
		$page = $this->newPage();
		$page->flushWriter();
		$page->preRender = function ($page) {
			PradoUnit::getProp($page, '_writer')->write('early');
			$page->flushWriter();
		};
		$html = $this->runPage($page);
		$this->assertSame('early', $this->_response->written);
		$this->assertStringNotContainsString('early', $html);
		$this->assertNull(PradoUnit::getProp($page, '_writer'));
	}
}
