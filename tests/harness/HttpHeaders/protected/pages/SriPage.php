<?php

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\TIntegrityManager;
use Prado\Web\UI\TPage;

/**
 * SriPage — harness page for Subresource Integrity functional tests.
 *
 * Pins an SRI value for a cross-origin test asset through {@see TIntegrityManager}
 * and renders the `<script>` tag with {@see TJavaScript::renderScriptFile()}, the
 * same emission path the framework uses. The asset is requested via `localhost`
 * while the page is served from `127.0.0.1`, so it is cross-origin (and "remote"
 * to {@see \Prado\Web\THttpUtility::isLocalUrl()}), which is what makes the
 * `integrity` and `crossorigin` attributes emit.
 *
 * Query parameters:
 *   action — `correct` (default) pins the real hash; `wrong` pins a mismatching
 *            hash so the browser blocks the script.
 */
class SriPage extends TPage
{
	/** @var string The active action, exposed to the template. */
	private string $_action = 'correct';

	/** @var string The rendered `<script>` tag, exposed to the template. */
	private string $_scriptTag = '';

	public function onLoad($param): void
	{
		parent::onLoad($param);

		$this->_action = ($this->Request->itemAt('action') === 'wrong') ? 'wrong' : 'correct';

		$content = include dirname(__DIR__, 2) . '/sri-content.php';
		$port = (string) ($_SERVER['SERVER_PORT'] ?? '8037');
		$url = 'http://localhost:' . $port . '/tests/harness/HttpHeaders/sri-asset.php';

		$hash = ($this->_action === 'wrong')
			? 'sha384-' . base64_encode(str_repeat("\0", 48))
			: TIntegrityManager::calculateIntegrity($content);

		$manager = new TIntegrityManager();
		$manager->addIntegrity($url, $hash);

		$this->_scriptTag = TJavaScript::renderScriptFile($url);
	}

	public function getAction(): string
	{
		return $this->_action;
	}

	public function getScriptTag(): string
	{
		return $this->_scriptTag;
	}
}
