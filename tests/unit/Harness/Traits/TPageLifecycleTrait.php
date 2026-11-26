<?php

/**
 * TPageLifecycleTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\TTestUrlAssetManager;
use Prado\Test\Unit\Harness\Web\UI\TTestPageStatePersister;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;

/**
 * TPageLifecycleTrait runs a page through {@see TPage::run()} for a GET, a postback,
 * or a callback request, and restores the application afterwards.
 *
 * {@see runPage()} sets the request items, a {@see TTestRecordingResponse}, a
 * {@see TTestUrlAssetManager}, and a page service. It restores them, the error
 * handler, and the globalization module when the page ends, and keeps what the page
 * echoed in {@see getLifecycleOutput()}. {@see runCallback()} adds the callback target
 * and parameter. {@see getCallbackParts()} reads the parts of a callback response,
 * such as `X-PRADO-ACTIONS`, from the response adapter.
 *
 * A page that keeps its state between runs uses {@see TTestPageStatePersister};
 * {@see resetPageState()} clears it. Call it in `setUp()` and `tearDown()`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TPageLifecycleTrait
{
	/**
	 * @var ?TTestRecordingResponse the response of the last page run
	 */
	private ?TTestRecordingResponse $_lifecycleResponse = null;

	/**
	 * @var string what the last page run echoed
	 */
	private string $_lifecycleOutput = '';

	/**
	 * Clears the page state that {@see TTestPageStatePersister} keeps between runs.
	 */
	protected function resetPageState(): void
	{
		TTestPageStatePersister::$state = null;
		$this->_lifecycleResponse = null;
		$this->_lifecycleOutput = '';
	}

	/**
	 * Runs a page through its life cycle with the given request items, restoring the application afterwards.
	 * @param TPage $page the page to run
	 * @param array $items the request items, as GET or POST data
	 * @param ?TPageService $service the page service, a new one when null
	 * @return string the rendered output
	 */
	protected function runPage(TPage $page, array $items = [], ?TPageService $service = null): string
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
		$app->setAssetManager(new TTestUrlAssetManager());
		$app->setResponse($this->_lifecycleResponse = new TTestRecordingResponse());
		$app->setService($service ?? new TPageService());
		PradoUnit::setProp($request, '_items', $items);
		$this->_lifecycleOutput = '';
		ob_start();
		try {
			$writer = new THtmlWriter(new TTextWriter());
			$page->run($writer);
			return $writer->flush();
		} finally {
			while (ob_get_level() > $obLevel + 1) {
				ob_end_flush();
			}
			if (ob_get_level() > $obLevel) {
				$this->_lifecycleOutput = (string) ob_get_clean();
			}
			PradoUnit::setProp($request, '_items', $savedItems);
			PradoUnit::setProp($app, '_assetManager', $savedAssets);
			PradoUnit::setProp($app, '_globalization', $savedGlobalization);
			$app->setResponse($savedResponse);
			$app->setService($savedService);
			$app->setErrorHandler($savedHandler);
		}
	}

	/**
	 * Runs a page for a callback to the target, after a GET run saved its state.
	 * @param TPage $page the page to run
	 * @param string $target the unique ID of the callback target
	 * @param mixed $parameter the callback parameter, JSON-encoded into the request
	 * @param array $items further request items, such as post data
	 * @param ?TPageService $service the page service, a new one when null
	 * @return string the rendered output
	 */
	protected function runCallback(TPage $page, string $target, mixed $parameter = null, array $items = [], ?TPageService $service = null): string
	{
		return $this->runPage($page, $items + [
			TPage::FIELD_PAGESTATE => TTestPageStatePersister::CLIENT_STATE,
			TPage::FIELD_CALLBACK_TARGET => $target,
			TPage::FIELD_CALLBACK_PARAMETER => json_encode($parameter),
		], $service);
	}

	/**
	 * @return ?TTestRecordingResponse the response of the last page run
	 */
	protected function getLifecycleResponse(): ?TTestRecordingResponse
	{
		return $this->_lifecycleResponse;
	}

	/**
	 * @return string what the last page run echoed
	 */
	protected function getLifecycleOutput(): string
	{
		return $this->_lifecycleOutput;
	}

	/**
	 * Flushes the writers of the callback response adapter of the last page run.
	 * A part written with a boundary, such as `X-PRADO-ACTIONS`, is keyed by it; a later
	 * part with the same boundary replaces an earlier one.
	 * @return array the content of each callback response part, by boundary
	 */
	protected function getCallbackParts(): array
	{
		$adapter = $this->_lifecycleResponse?->getAdapter();
		if (!$adapter instanceof TCallbackResponseAdapter) {
			return [];
		}
		$parts = [];
		foreach (PradoUnit::getProp($adapter, '_writers') as $writer) {
			$boundary = $writer->getBoundary();
			$content = $writer->flushWithoutBoundary();
			if ($content !== '') {
				$parts[$boundary] = $content;
			}
		}
		return $parts;
	}
}
