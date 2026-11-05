<?php

use Prado\Web\Javascripts\TStyleSheetHelper;
use Prado\Web\TIntegrityManager;
use Prado\Web\UI\TPage;

/**
 * StyleSriPage — harness page for stylesheet Subresource Integrity.
 *
 * Pins an SRI value for a cross-origin stylesheet through {@see TIntegrityManager}
 * and renders the `<link>` tag with {@see TStyleSheetHelper::renderStyleSheetFile()}.
 * The stylesheet is requested via `localhost` while the page is served from
 * `127.0.0.1`, so it is remote to {@see \Prado\Web\THttpUtility::isLocalUrl()}.
 *
 * Query parameters:
 *   action — `correct` (default) pins the real hash; `wrong` pins a mismatching
 *            hash so the browser blocks the stylesheet.
 */
class StyleSriPage extends TPage
{
	private string $_action = 'correct';

	private string $_linkTag = '';

	public function onLoad($param): void
	{
		parent::onLoad($param);
		$this->_action = ($this->Request->itemAt('action') === 'wrong') ? 'wrong' : 'correct';

		$content = include dirname(__DIR__, 2) . '/sri-style-content.php';
		$port = (string) ($_SERVER['SERVER_PORT'] ?? '8037');
		$url = 'http://localhost:' . $port . '/tests/harness/HttpHeaders/sri-style.php';

		$hash = ($this->_action === 'wrong')
			? 'sha384-' . base64_encode(str_repeat("\0", 48))
			: TIntegrityManager::calculateIntegrity($content);

		$manager = new TIntegrityManager();
		$manager->addIntegrity($url, $hash);

		$this->_linkTag = TStyleSheetHelper::renderStyleSheetFile($url);
	}

	public function getAction(): string
	{
		return $this->_action;
	}

	public function getLinkTag(): string
	{
		return $this->_linkTag;
	}
}
