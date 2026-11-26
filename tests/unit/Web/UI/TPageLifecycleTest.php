<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Test\Unit\Harness\Web\UI\TTestPageStatePersister;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TBehavior;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\IPostBackEventHandler;
use Prado\Web\UI\ITheme;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\THead;

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

class TPageLifecycleTest extends \PHPUnit\Framework\TestCase
{
	use TPageLifecycleTrait;

	protected function setUp(): void
	{
		$this->resetPageState();
		TPageLifecycleTestRecorder::$calls = [];
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	/**
	 * @param ?callable $build builds the controls of the form, receives the page and the form
	 * @return TTestLifecyclePage a page with a text box "Name" and a button "Go" in its form, and the given controls
	 */
	private function newPage(?callable $build = null): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($build) {
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
		$this->assertSame(1, TTestPageStatePersister::$state[0]['Visits']);
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
		$this->assertSame(2, TTestPageStatePersister::$state[0]['Visits']);
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
		$this->assertSame(['Missing' => true, 'Agree' => true], TTestPageStatePersister::$state[0]['ControlsRequiringPostBack']);

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
		$this->assertSame('early', $this->getLifecycleResponse()->written);
		$this->assertStringNotContainsString('early', $html);
		$this->assertNull(PradoUnit::getProp($page, '_writer'));
	}
}
