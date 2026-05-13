<?php

use Prado\Exceptions\TExitException;
use Prado\Exceptions\THttpException;
use Prado\TApplicationMode;

/**
 * A TApplication subclass that records the call order of every lifecycle
 * method and can be configured to throw exceptions or call completeRequest()
 * at any named step. Designed to let tests drive run() without real HTTP
 * infrastructure, config-file I/O, or process exit.
 */
class LifecycleTrackingApp extends TTestApplication
{
	/** Ordered list of lifecycle method names as they are called. */
	public array $callLog = [];

	/** step name → Throwable to raise when that step is entered. */
	private array $_throwAt = [];

	/** When non-null, completeRequest() is called when this step is entered. */
	private ?string $_completeAt = null;

	// -----------------------------------------------------------------------
	// Configuration
	// -----------------------------------------------------------------------

	public function throwAt(string $step, \Throwable $e): void
	{
		$this->_throwAt[$step] = $e;
	}

	public function completeAt(string $step): void
	{
		$this->_completeAt = $step;
	}

	// -----------------------------------------------------------------------
	// Internal helper
	// -----------------------------------------------------------------------

	private function _record(string $step): void
	{
		$this->callLog[] = $step;
		if ($this->_completeAt === $step) {
			$this->completeRequest();
		}
		if (isset($this->_throwAt[$step])) {
			throw $this->_throwAt[$step];
		}
	}

	// -----------------------------------------------------------------------
	// initApplication — skips config loading, service startup, and request
	// resolution; just fires the two events in documented order.
	// -----------------------------------------------------------------------

	protected function initApplication(): void
	{
		$this->_record('onConfiguration');
		$this->_record('onInitComplete');
	}

	// -----------------------------------------------------------------------
	// Lifecycle step overrides — record the call, optionally throw.
	// -----------------------------------------------------------------------

	public function onBeginRequest(): void           { $this->_record('onBeginRequest'); }
	public function onLoadState(): void              { $this->_record('onLoadState'); }
	public function onLoadStateComplete(): void      { $this->_record('onLoadStateComplete'); }
	public function onAuthentication(): void         { $this->_record('onAuthentication'); }
	public function onAuthenticationComplete(): void { $this->_record('onAuthenticationComplete'); }
	public function onAuthorization(): void          { $this->_record('onAuthorization'); }
	public function onAuthorizationComplete(): void  { $this->_record('onAuthorizationComplete'); }
	public function onPreRunService(): void          { $this->_record('onPreRunService'); }
	public function runService(): void               { $this->_record('runService'); }
	public function onSaveState(): void              { $this->_record('onSaveState'); }
	public function onSaveStateComplete(): void      { $this->_record('onSaveStateComplete'); }
	public function onPreFlushOutput(): void         { $this->_record('onPreFlushOutput'); }
	public function flushOutput($continueBuffering = true): void { $this->_record('flushOutput'); }

	/** onEndRequest is NOT routed through _record() so it never re-throws. */
	public function onEndRequest(): void { $this->callLog[] = 'onEndRequest'; }

	/** onError is NOT routed through _record() to avoid masking exceptions. */
	public function onError($param): void
	{
		$this->callLog[] = 'onError';
		$this->capturedError = $param;
	}

}

// ---------------------------------------------------------------------------

/**
 * A LifecycleTrackingApp subclass that overrides getSteps() to return a
 * reduced lifecycle, used to verify that the run() loop delegates to getSteps().
 */
class LifecycleCustomStepsApp extends LifecycleTrackingApp
{
	/** The reduced step set this subclass exposes. */
	public const CUSTOM_STEPS = [
		'onBeginRequest',
		'runService',
		'onSaveState',
	];

	protected function getSteps(): array
	{
		return self::CUSTOM_STEPS;
	}
}

// ---------------------------------------------------------------------------

/**
 * Tests for TApplication::run() lifecycle ordering and exception handling.
 *
 * Uses LifecycleTrackingApp — a subclass that overrides every lifecycle method
 * to record call order and can inject exceptions or early request completion
 * at any step, without real HTTP infrastructure or process exit.
 *
 * @package System
 */
class TApplicationLifecycleTest extends PHPUnit\Framework\TestCase
{
	private LifecycleTrackingApp $_app;

	/** The full 16-entry sequence expected from a clean run(). */
	private const FULL_LIFECYCLE = [
		'onConfiguration',
		'onInitComplete',
		'onBeginRequest',
		'onLoadState',
		'onLoadStateComplete',
		'onAuthentication',
		'onAuthenticationComplete',
		'onAuthorization',
		'onAuthorizationComplete',
		'onPreRunService',
		'runService',
		'onSaveState',
		'onSaveStateComplete',
		'onPreFlushOutput',
		'flushOutput',
		'onEndRequest',
	];

	protected function setUp(): void
	{
		$ref = new \ReflectionClass(LifecycleTrackingApp::class);
		$this->_app = $ref->newInstanceWithoutConstructor();

		// setMode() writes to TApplication's private $_mode via the parent method.
		$this->_app->setMode(TApplicationMode::Debug);
	}

	// -----------------------------------------------------------------------
	// Happy path — full lifecycle
	// -----------------------------------------------------------------------

	public function testRun_happyPath_fullLifecycleInOrder(): void
	{
		$this->_app->run();

		$this->assertSame(self::FULL_LIFECYCLE, $this->_app->callLog);
	}

	public function testRun_happyPath_onConfigurationBeforeOnInitComplete(): void
	{
		$this->_app->run();

		$configIdx = array_search('onConfiguration', $this->_app->callLog, true);
		$initIdx   = array_search('onInitComplete', $this->_app->callLog, true);

		$this->assertLessThan($initIdx, $configIdx,
			'onConfiguration must fire before onInitComplete');
	}

	public function testRun_happyPath_onInitCompleteBeforeOnBeginRequest(): void
	{
		$this->_app->run();

		$initIdx  = array_search('onInitComplete', $this->_app->callLog, true);
		$beginIdx = array_search('onBeginRequest', $this->_app->callLog, true);

		$this->assertLessThan($beginIdx, $initIdx,
			'onInitComplete must fire before onBeginRequest');
	}

	public function testRun_happyPath_onEndRequestIsLast(): void
	{
		$this->_app->run();

		$this->assertSame('onEndRequest', end($this->_app->callLog));
	}

	public function testRun_happyPath_noErrorCaptured(): void
	{
		$this->_app->run();

		$this->assertNull($this->_app->capturedError);
		$this->assertNull($this->_app->capturedExitCode);
		$this->assertNotContains('onError', $this->_app->callLog);
	}

	// -----------------------------------------------------------------------
	// TExitException — caught, onEndRequest fired, no process exit
	// -----------------------------------------------------------------------

	public function testRun_exitException_onEndRequestCalledOnce(): void
	{
		$this->_app->throwAt('onAuthentication', new TExitException(0));

		$this->_app->run();

		$this->assertSame(1, count(array_keys($this->_app->callLog, 'onEndRequest', true)),
			'onEndRequest must be called exactly once on TExitException');
	}

	public function testRun_exitException_stepsAfterThrowNotCalled(): void
	{
		$this->_app->throwAt('onAuthentication', new TExitException(0));

		$this->_app->run();

		$stepsAfter = [
			'onAuthenticationComplete', 'onAuthorization', 'onAuthorizationComplete',
			'onPreRunService', 'runService', 'onSaveState', 'onSaveStateComplete',
			'onPreFlushOutput', 'flushOutput',
		];
		foreach ($stepsAfter as $step) {
			$this->assertNotContains($step, $this->_app->callLog,
				"$step must not be called after TExitException");
		}
	}

	public function testRun_exitException_onErrorNotCalled(): void
	{
		$this->_app->throwAt('onPreRunService', new TExitException(42));

		$this->_app->run();

		$this->assertNotContains('onError', $this->_app->callLog);
	}

	public function testRun_exitException_exitCodeCaptured(): void
	{
		$this->_app->throwAt('onBeginRequest', new TExitException(7));

		$this->_app->run();

		$this->assertSame(7, $this->_app->capturedExitCode);
	}

	public function testRun_exitException_logOrder(): void
	{
		$this->_app->throwAt('onAuthentication', new TExitException(0));

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onBeginRequest',
			'onLoadState',
			'onLoadStateComplete',
			'onAuthentication',
			'onEndRequest',
		], $this->_app->callLog);
	}

	// -----------------------------------------------------------------------
	// Regular Exception — onError + onEndRequest both called
	// -----------------------------------------------------------------------

	public function testRun_regularException_onErrorCalled(): void
	{
		$exception = new \RuntimeException('boom');
		$this->_app->throwAt('onAuthorization', $exception);

		$this->_app->run();

		$this->assertContains('onError', $this->_app->callLog);
		$this->assertSame($exception, $this->_app->capturedError);
	}

	public function testRun_regularException_onEndRequestCalledAfterOnError(): void
	{
		$this->_app->throwAt('onAuthorization', new \RuntimeException('boom'));

		$this->_app->run();

		$errorIdx  = array_search('onError', $this->_app->callLog, true);
		$endIdx    = array_search('onEndRequest', $this->_app->callLog, true);

		$this->assertNotFalse($errorIdx, 'onError must appear in callLog');
		$this->assertNotFalse($endIdx,   'onEndRequest must appear in callLog');
		$this->assertLessThan($endIdx, $errorIdx,
			'onError must fire before onEndRequest');
	}

	public function testRun_regularException_stepsAfterThrowNotCalled(): void
	{
		$this->_app->throwAt('onAuthorization', new \RuntimeException('boom'));

		$this->_app->run();

		$stepsAfter = [
			'onAuthorizationComplete', 'onPreRunService', 'runService',
			'onSaveState', 'onSaveStateComplete', 'onPreFlushOutput', 'flushOutput',
		];
		foreach ($stepsAfter as $step) {
			$this->assertNotContains($step, $this->_app->callLog,
				"$step must not run after a regular exception");
		}
	}

	public function testRun_regularException_logOrder(): void
	{
		$this->_app->throwAt('onAuthorization', new \RuntimeException('boom'));

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onBeginRequest',
			'onLoadState',
			'onLoadStateComplete',
			'onAuthentication',
			'onAuthenticationComplete',
			'onAuthorization',
			'onError',
			'onEndRequest',
		], $this->_app->callLog);
	}

	// -----------------------------------------------------------------------
	// Exception during initApplication
	// -----------------------------------------------------------------------

	public function testRun_exceptionInConfigurationComplete_onErrorThenOnEndRequest(): void
	{
		$this->_app->throwAt('onConfiguration', new \RuntimeException('config failed'));

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onError',
			'onEndRequest',
		], $this->_app->callLog);
	}

	public function testRun_exceptionInInitComplete_onErrorThenOnEndRequest(): void
	{
		$this->_app->throwAt('onInitComplete', new \RuntimeException('init failed'));

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onError',
			'onEndRequest',
		], $this->_app->callLog);
	}

	public function testRun_exitExceptionInConfigurationComplete_onEndRequestCalledOnce(): void
	{
		$this->_app->throwAt('onConfiguration', new TExitException(1));

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onEndRequest',
		], $this->_app->callLog);
		$this->assertSame(1, $this->_app->capturedExitCode);
	}

	// -----------------------------------------------------------------------
	// TApplicationMode::Off — loop throws THttpException → onError + onEndRequest
	// -----------------------------------------------------------------------

	public function testRun_applicationOff_onErrorCalledWithHttpException(): void
	{
		$this->_app->setMode(TApplicationMode::Off);

		$this->_app->run();

		$this->assertContains('onError', $this->_app->callLog);
		$this->assertInstanceOf(THttpException::class, $this->_app->capturedError);
		$this->assertSame(503, $this->_app->capturedError->getStatusCode());
	}

	public function testRun_applicationOff_logOrder(): void
	{
		// Set mode to Off after initApplication would complete (we set it before run(),
		// so the loop's first iteration sees Off and throws immediately).
		$this->_app->setMode(TApplicationMode::Off);

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onError',
			'onEndRequest',
		], $this->_app->callLog);
		$this->assertInstanceOf(THttpException::class, $this->_app->capturedError);
	}

	// -----------------------------------------------------------------------
	// completeRequest() — remaining steps skipped, onEndRequest still fires
	// -----------------------------------------------------------------------

	public function testRun_completeRequest_skipsRemainingSteps(): void
	{
		// completeRequest() is called inside onAuthentication; the steps
		// that would follow it in the loop must not be executed.
		$this->_app->completeAt('onAuthentication');

		$this->_app->run();

		$stepsAfter = [
			'onAuthenticationComplete', 'onAuthorization', 'onAuthorizationComplete',
			'onPreRunService', 'runService', 'onSaveState', 'onSaveStateComplete',
			'onPreFlushOutput', 'flushOutput',
		];
		foreach ($stepsAfter as $step) {
			$this->assertNotContains($step, $this->_app->callLog,
				"$step must be skipped after completeRequest()");
		}
	}

	public function testRun_completeRequest_onEndRequestStillFires(): void
	{
		$this->_app->completeAt('onAuthentication');

		$this->_app->run();

		$this->assertContains('onEndRequest', $this->_app->callLog);
	}

	public function testRun_completeRequest_logOrder(): void
	{
		$this->_app->completeAt('onAuthentication');

		$this->_app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onBeginRequest',
			'onLoadState',
			'onLoadStateComplete',
			'onAuthentication',
			'onEndRequest',
		], $this->_app->callLog);
	}

	public function testRun_completeRequest_noErrorCaptured(): void
	{
		$this->_app->completeAt('onPreRunService');

		$this->_app->run();

		$this->assertNull($this->_app->capturedError);
		$this->assertNotContains('onError', $this->_app->callLog);
	}

	// -----------------------------------------------------------------------
	// getSteps() override — custom lifecycle via subclass
	// -----------------------------------------------------------------------

	private function newCustomApp(): LifecycleCustomStepsApp
	{
		$ref = new \ReflectionClass(LifecycleCustomStepsApp::class);
		$app = $ref->newInstanceWithoutConstructor();
		$app->setMode(TApplicationMode::Debug);
		return $app;
	}

	public function testRun_customSteps_onlyCustomStepsAreExecuted(): void
	{
		$app = $this->newCustomApp();
		$app->run();

		$stepsInLog = array_filter($app->callLog, fn($s) => in_array($s, [
			'onBeginRequest', 'onLoadState', 'onLoadStateComplete',
			'onAuthentication', 'onAuthenticationComplete',
			'onAuthorization', 'onAuthorizationComplete',
			'onPreRunService', 'runService', 'onSaveState', 'onSaveStateComplete',
			'onPreFlushOutput', 'flushOutput',
		]));

		// Only the three custom steps must appear.
		$this->assertSame(
			array_values(LifecycleCustomStepsApp::CUSTOM_STEPS),
			array_values($stepsInLog)
		);
	}

	public function testRun_customSteps_defaultStepsNotInLog(): void
	{
		$app = $this->newCustomApp();
		$app->run();

		// Steps from the default lifecycle that are not in CUSTOM_STEPS must be absent.
		$omitted = array_diff(
			['onLoadState', 'onLoadStateComplete', 'onAuthentication', 'onAuthenticationComplete',
			 'onAuthorization', 'onAuthorizationComplete', 'onPreRunService',
			 'onSaveStateComplete', 'onPreFlushOutput', 'flushOutput'],
			LifecycleCustomStepsApp::CUSTOM_STEPS
		);
		foreach ($omitted as $step) {
			$this->assertNotContains($step, $app->callLog, "$step must not run with custom steps");
		}
	}

	public function testRun_customSteps_initAndEndRequestStillFire(): void
	{
		$app = $this->newCustomApp();
		$app->run();

		// initApplication() events and onEndRequest() are outside getSteps().
		$this->assertContains('onConfiguration', $app->callLog);
		$this->assertContains('onInitComplete', $app->callLog);
		$this->assertContains('onEndRequest', $app->callLog);
	}

	public function testRun_customSteps_fullLogOrder(): void
	{
		$app = $this->newCustomApp();
		$app->run();

		$this->assertSame([
			'onConfiguration',
			'onInitComplete',
			'onBeginRequest',
			'runService',
			'onSaveState',
			'onEndRequest',
		], $app->callLog);
	}

	public function testRun_customSteps_completeRequestStopsLoop(): void
	{
		$app = $this->newCustomApp();
		$app->completeAt('onBeginRequest');
		$app->run();

		// After completeRequest() in onBeginRequest, remaining custom steps must not run.
		$this->assertNotContains('runService', $app->callLog);
		$this->assertNotContains('onSaveState', $app->callLog);
		$this->assertContains('onEndRequest', $app->callLog);
	}

	public function testRun_customSteps_exitExceptionCaptured(): void
	{
		$app = $this->newCustomApp();
		$app->throwAt('runService', new TExitException(42));
		$app->run();

		$this->assertSame(42, $app->capturedExitCode);
		$this->assertNotContains('onSaveState', $app->callLog);
	}
}
