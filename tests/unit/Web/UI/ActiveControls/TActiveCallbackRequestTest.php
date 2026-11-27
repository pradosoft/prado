<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Test\Unit\Harness\Web\UI\TTestPageStatePersister;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TActiveUpdateMode;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TCallbackPageStateTracker;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\UI\ActiveControls\TInvalidCallbackException;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * Callback requests run through {@see \Prado\Web\UI\TPage::run()}: the response parts
 * {@see TActivePageAdapter} renders, the redirect of {@see TCallbackResponseAdapter},
 * the callback event of {@see TCallback}, and the page state tracking and rendering
 * of {@see TActiveControlAdapter}.
 */
class TActiveCallbackRequestTest extends TestCase
{
	use TPageLifecycleTrait;

	/**
	 * @var ?\Closure runs in the OnCallback handler of the callback `Act`, receives the page and the event parameter
	 */
	private ?\Closure $onCallback = null;

	protected function setUp(): void
	{
		$this->resetPageState();
		$this->onCallback = null;
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	/**
	 * @param ?callable $build adds further controls to the form, receives the page and the form
	 * @return TTestLifecyclePage a page with the callback `Act` and an active panel `Panel` in its form
	 */
	private function newPage(?callable $build = null): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($build) {
			$callback = new TCallback();
			$callback->setID('Act');
			$callback->attachEventHandler('OnCallback', function ($sender, $param) use ($page) {
				$page->events[] = 'callback';
				if ($this->onCallback) {
					($this->onCallback)($page, $param);
				}
			});
			$form->getControls()->add($callback);
			$panel = new TActivePanel();
			$panel->setID('Panel');
			$form->getControls()->add($panel);
			if ($build) {
				$build($page, $form);
			}
		};
		return $page;
	}

	/**
	 * Runs the page for a GET, then a new page for a callback to the target.
	 * @return TTestLifecyclePage the callback page
	 */
	private function runActCallback(string $target = 'Act', mixed $parameter = null, ?callable $build = null, array $items = []): TTestLifecyclePage
	{
		$this->runPage($this->newPage($build));
		$page = $this->newPage($build);
		$this->runCallback($page, $target, $parameter, $items);
		return $page;
	}

	/**
	 * @return array the client functions of the last callback response
	 */
	private function actions(array $parts): array
	{
		return json_decode($parts[TActivePageAdapter::CALLBACK_ACTION_HEADER], true);
	}

	public function testCallbackRaisesTheEventAndSendsThePageStateAndActions()
	{
		$this->onCallback = function ($page, $param) {
			$this->assertInstanceOf(TCallbackEventParameter::class, $param);
			$this->assertEquals((object) ['n' => 1], $param->getCallbackParameter());
			$page->getCallbackClient()->hide('X');
		};
		$page = $this->runActCallback('Act', ['n' => 1]);
		$this->assertSame(['callback'], $page->events);
		$this->assertInstanceOf(TActivePageAdapter::class, $page->getAdapter());
		$this->assertInstanceOf(TCallbackResponseAdapter::class, $this->getLifecycleResponse()->getAdapter());
		$parts = $this->getCallbackParts();
		$this->assertSame([['Prado.Element.j' => ['X', 'hide', []]]], $this->actions($parts));
		$this->assertSame(TTestPageStatePersister::CLIENT_STATE, $parts[TActivePageAdapter::CALLBACK_PAGESTATE_HEADER]);
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_DATA_HEADER, $parts);
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_CSRFTOKEN_HEADER, $parts);
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_VIEWTRANSITION_HEADER, $parts);
	}

	public function testCallbackSendsTheResponseDataAndTheRegisteredClientScript()
	{
		$this->onCallback = function ($page, $param) {
			$param->setResponseData(['a' => 1]);
			$cs = $page->getClientScript();
			$cs->registerStyleSheetFile('s', '/s.css');
			$cs->registerStyleSheet('c', 'body{color:red}');
			$cs->registerScriptFile('j', '/j.js');
			$cs->registerHiddenField('h', 'v');
			$cs->registerEndScript('e', 'go();');
		};
		$this->runActCallback('Act', null, fn ($page) => $page->getControls()->insertAt(0, new THead()));
		$parts = $this->getCallbackParts();
		$this->assertSame('{"a":1}', $parts[TActivePageAdapter::CALLBACK_DATA_HEADER]);
		$this->assertStringContainsString('/s.css', $parts[TActivePageAdapter::CALLBACK_STYLESHEETLIST_HEADER]);
		$this->assertStringContainsString('body{color:red}', $parts[TActivePageAdapter::CALLBACK_STYLESHEET_HEADER]);
		$this->assertStringContainsString('/j.js', $parts[TActivePageAdapter::CALLBACK_SCRIPTLIST_HEADER]);
		$this->assertSame('{"h":"v"}', $parts[TActivePageAdapter::CALLBACK_HIDDENFIELDLIST_HEADER]);
		$actions = $this->actions($parts);
		$this->assertSame('Prado.Element.evaluateScript', array_key_first(end($actions)));
		$boundary = end($actions)['Prado.Element.evaluateScript'][1];
		$this->assertStringContainsString('go();', $parts[$boundary]);
	}

	public function testCallbackWithoutPageStateUpdateSendsNoPageState()
	{
		$build = fn ($page, $form) => $page->findControl('Act')->getClientSide()->setEnablePageStateUpdate(false);
		$this->onCallback = fn ($page) => $page->findControl('Panel')->setToolTip('tip');
		$this->runActCallback('Act', null, $build);
		$parts = $this->getCallbackParts();
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_PAGESTATE_HEADER, $parts);
		$this->assertSame([], $this->actions($parts));
	}

	/**
	 * A handler may set a target without callback client-side options; the response
	 * then sends the page state.
	 */
	public static function retargetProvider(): array
	{
		return [
			'active control' => ['Panel'],
			'plain control' => ['Form'],
		];
	}

	/**
	 * @dataProvider retargetProvider
	 */
	public function testCallbackRetargetedToAControlWithoutClientSideSendsThePageState(string $target)
	{
		$this->onCallback = fn ($page) => $page->getAdapter()->setCallbackEventTarget($page->findControl($target));
		$this->runActCallback();
		$parts = $this->getCallbackParts();
		$this->assertSame(TTestPageStatePersister::CLIENT_STATE, $parts[TActivePageAdapter::CALLBACK_PAGESTATE_HEADER]);
	}

	public function testCallbackSendsAFreshCsrfToken()
	{
		$request = Prado::getApplication()->getRequest();
		$saved = PradoUnit::getProp($request, '_csrfToken');
		PradoUnit::setProp($request, '_csrfToken', str_repeat('k', 32));
		try {
			$this->onCallback = fn ($page) => $page->setEnableCsrfValidation(true);
			$this->runActCallback();
		} finally {
			PradoUnit::setProp($request, '_csrfToken', $saved);
		}
		$token = $this->getCallbackParts()[TActivePageAdapter::CALLBACK_CSRFTOKEN_HEADER];
		$this->assertNotSame('', $token);
	}

	public function testCallbackOfAViewTransitionSendsTheTransitionPart()
	{
		$this->onCallback = function ($page) {
			$page->setCallbackViewTransition(true);
			$page->getCallbackClient()->hide('X');
		};
		$this->runActCallback();
		$this->assertSame('1', $this->getCallbackParts()[TActivePageAdapter::CALLBACK_VIEWTRANSITION_HEADER]);
	}

	public static function redirectProvider(): array
	{
		return [
			'absolute path' => ['/next?a=1&amp;b=2', '/next?a=1&b=2', true],
			'url' => ['https://example.com/next', 'https://example.com/next', false],
		];
	}

	/**
	 * @dataProvider redirectProvider
	 */
	public function testCallbackRedirectSendsTheUrlInstead(string $url, string $expected, bool $prefixed)
	{
		$this->onCallback = fn ($page) => $page->getResponse()->redirect($url);
		$this->runActCallback();
		$parts = $this->getCallbackParts();
		$baseUrl = $prefixed ? Prado::getApplication()->getRequest()->getBaseUrl() : '';
		$this->assertSame($baseUrl . $expected, $parts[TActivePageAdapter::CALLBACK_REDIRECT]);
		$this->assertArrayNotHasKey(TActivePageAdapter::CALLBACK_ACTION_HEADER, $parts);
	}

	public function testResponseAdapterFlushesItsWritersBeforeTheResponse()
	{
		$this->runActCallback();
		$response = $this->getLifecycleResponse();
		$response->setBufferOutput(false);
		$adapter = $response->getAdapter();
		ob_start();
		try {
			$adapter->flushContent();
		} finally {
			$output = ob_get_clean();
		}
		$this->assertStringContainsString('<!--' . TActivePageAdapter::CALLBACK_ACTION_HEADER . '-->[]<!--//' . TActivePageAdapter::CALLBACK_ACTION_HEADER . '-->', $output);
	}

	public function testCallbackToAMissingTargetThrows()
	{
		$this->runPage($this->newPage());
		$this->expectException(TInvalidCallbackException::class);
		$this->expectExceptionMessage('Missing');
		$this->runCallback($this->newPage(), 'Missing');
	}

	public function testCallbackToAControlWithoutCallbackEventsThrows()
	{
		$this->runPage($this->newPage());
		$this->expectException(TInvalidCallbackException::class);
		$this->expectExceptionMessage('Form');
		$this->runCallback($this->newPage(), 'Form');
	}

	public function testCallbackValidatesTheValidationGroupOfTheCallback()
	{
		$build = function ($page, $form) {
			$box = new TTextBox();
			$box->setID('Name');
			$form->getControls()->add($box);
			$validator = new TRequiredFieldValidator();
			$validator->setID('Required');
			$validator->setControlToValidate('Name');
			$validator->setValidationGroup('G');
			$form->getControls()->add($validator);
			$page->findControl('Act')->getActiveControl()->setValidationGroup('G');
		};
		$this->onCallback = fn ($page) => $page->events[] = $page->getIsValid() ? 'valid' : 'invalid';
		$page = $this->runActCallback('Act', null, $build, ['Name' => '']);
		$this->assertSame(['callback', 'invalid'], $page->events);
		$page = $this->runActCallback('Act', null, $build, ['Name' => 'Ann']);
		$this->assertSame(['callback', 'valid'], $page->events);
	}

	public function testStateTrackerSendsTheChangedStateOfAnActiveControl()
	{
		$this->onCallback = function ($page) {
			$panel = $page->findControl('Panel');
			$panel->setToolTip('tip');
			$panel->setAccessKey('k');
		};
		$page = $this->runActCallback();
		$adapter = $page->findControl('Panel')->getAdapter();
		$this->assertInstanceOf(TCallbackPageStateTracker::class, $adapter->getStateTracker());
		$panelID = $page->findControl('Panel')->getClientID();
		$this->assertSame([
			['Prado.Element.setAttribute' => [$panelID, 'title', 'tip']],
			['Prado.Element.setAttribute' => [$panelID, 'accesskey', 'k']],
		], $this->actions($this->getCallbackParts()));
	}

	public function testStateTrackerClassIsSet()
	{
		$build = fn ($page, $form) => $page->findControl('Panel')->getAdapter()->setStateTracker(TActiveCallbackRequestTestTracker::class);
		$page = $this->runActCallback('Act', null, $build);
		$this->assertInstanceOf(TActiveCallbackRequestTestTracker::class, $page->findControl('Panel')->getAdapter()->getStateTracker());
	}

	public function testAnInvisibleActiveControlRendersAPlaceholder()
	{
		$build = function ($page, $form) {
			$panel = $page->findControl('Panel');
			$panel->setVisible(false);
			$panel->getActiveControl()->setUpdateMode(TActiveUpdateMode::Morph);
		};
		$page = $this->newPage($build);
		$output = $this->runPage($page);
		$this->assertStringContainsString('<span id="Panel" style="display:none"></span>', $output);
		$registered = PradoUnit::getProp($page->getClientScript(), '_registeredScripts');
		$this->assertArrayHasKey('ajax', $registered);
		$this->assertArrayHasKey('morph', $registered);
	}

	public function testAGetRequestTracksNoState()
	{
		$page = $this->newPage();
		$this->runPage($page);
		$this->assertNull($page->findControl('Panel')->getAdapter()->getStateTracker());
	}

	public function testAdapterCreatesTheBaseActiveControlOfItsType()
	{
		$panel = new TActivePanel();
		$this->assertInstanceOf(TBaseActiveControl::class, $panel->getActiveControl());
		$this->assertNotInstanceOf(TBaseActiveCallbackControl::class, $panel->getActiveControl());
		$callback = new TCallback();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $callback->getActiveControl());
		$adapter = new TActiveControlAdapter($panel, TBaseActiveCallbackControl::class);
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $adapter->getBaseActiveControl());
		$base = new TBaseActiveControl($panel);
		$adapter->setBaseActiveControl($base);
		$this->assertSame($base, $adapter->getBaseActiveControl());
	}

	public function testCallbackRendersARegisteredControlIntoItsBoundary()
	{
		$this->onCallback = function ($page) {
			$panel = $page->findControl('Panel');
			$panel->getControls()->add('fresh');
			$page->getCallbackClient()->replaceContent($panel, $panel);
		};
		$this->runActCallback();
		$parts = $this->getCallbackParts();
		$boundary = $this->actions($parts)[0]['Prado.Element.replace'][2];
		$this->assertStringContainsString('fresh', $parts[$boundary]);
	}

	public function testCallbackEventTargetAndParameterAreReadOnceAndSet()
	{
		$this->onCallback = function ($page) {
			$adapter = $page->getAdapter();
			$this->assertSame('p', $adapter->getCallbackEventParameter());
			$this->assertSame('p', $adapter->getCallbackEventParameter());
			$adapter->setCallbackEventParameter('q');
			$this->assertSame('q', $adapter->getCallbackEventParameter());
			$adapter->setCallbackEventTarget($page->findControl('Act'));
			$this->assertSame($page->findControl('Act'), $adapter->getCallbackEventTarget());
		};
		$this->runActCallback('Act', 'p');
	}

	public function testStateTrackerSendsNothingWhileUpdatesAreOff()
	{
		$this->onCallback = function ($page) {
			$panel = $page->findControl('Panel');
			$panel->getActiveControl()->setEnableUpdate(false);
			$panel->setToolTip('tip');
		};
		$page = $this->runActCallback();
		$this->assertNotNull($page->findControl('Panel')->getAdapter()->getStateTracker());
		$this->assertSame([], $this->actions($this->getCallbackParts()));
	}

	public function testAVisibleMorphingControlRegistersTheMorphScript()
	{
		$build = fn ($page) => $page->findControl('Panel')->getActiveControl()->setUpdateMode(TActiveUpdateMode::Morph);
		$page = $this->newPage($build);
		$output = $this->runPage($page);
		$this->assertStringContainsString('<div id="Panel">', $output);
		$this->assertArrayHasKey('morph', PradoUnit::getProp($page->getClientScript(), '_registeredScripts'));
	}

	public function testResponseAdapterWithoutWritersFlushesOnlyTheResponse()
	{
		$response = new TTestRecordingResponse();
		$response->setBufferOutput(false);
		$adapter = new TCallbackResponseAdapter($response);
		ob_start();
		try {
			$adapter->flushContent();
		} finally {
			$output = ob_get_clean();
		}
		$this->assertSame('', $output);
		$this->assertTrue($response->getHttpHeaderSent());
	}
}

/**
 * A state tracker that a test names by class.
 */
class TActiveCallbackRequestTestTracker extends TCallbackPageStateTracker
{
}
