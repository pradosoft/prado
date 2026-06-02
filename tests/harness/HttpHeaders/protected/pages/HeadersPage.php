<?php

use Prado\Web\HttpHeaders\THttpHeader;
use Prado\Web\HttpHeaders\THttpHeaderHsts;
use Prado\Web\HttpHeaders\THttpHeaderContentType;
use Prado\Web\UI\TPage;

/**
 * HeadersPage — harness page for HSTS, X-Frame-Options, COEP, COOP,
 * Content-Type, and multi-header functional tests. Each `action` value
 * registers a different set of HTTP headers via the headers manager module.
 */
class HeadersPage extends TPage
{
	/**
	 * @var string the current action, read from the `action` query parameter.
	 */
	public string $Action = '';

	/**
	 * Returns the current action string.
	 * @return string action name read from the request.
	 */
	public function getAction(): string
	{
		return $this->Action;
	}

	/**
	 * Reads the `action` query parameter and delegates to
	 * {@see _configureHeaders()}.
	 * @param mixed $param event parameter (unused).
	 */
	public function onLoad($param): void
	{
		parent::onLoad($param);
		$action = $this->Request->itemAt('action') ?? '';
		$this->Action = (string) $action;
		$this->_configureHeaders($this->Action);
	}

	/**
	 * Builds and registers the appropriate HTTP headers based on the action.
	 * @param string $action action token selecting which headers to emit.
	 */
	private function _configureHeaders(string $action): void
	{
		/** @var \Prado\Web\HttpHeaders\THttpHeadersManager $manager */
		$manager = $this->getApplication()->getModule('httpHeaders');

		switch ($action) {
			case 'hsts':
				$hsts = new THttpHeaderHsts();
				$hsts->setMaxAge(31536000);
				$hsts->setIncludeSubDomains(true);
				$hsts->setPreload(true);
				$manager->addHeader($hsts);
				break;

			case 'x-frame-options':
				$header = new THttpHeader();
				$header->setHeaderName('X-Frame-Options');
				$header->setHeaderValue('DENY');
				$manager->addHeader($header);
				break;

			case 'coep-coop':
				$coep = new THttpHeader();
				$coep->setHeaderName('Cross-Origin-Embedder-Policy');
				$coep->setHeaderValue('require-corp');
				$manager->addHeader($coep);

				$coop = new THttpHeader();
				$coop->setHeaderName('Cross-Origin-Opener-Policy');
				$coop->setHeaderValue('same-origin');
				$manager->addHeader($coop);
				break;

			case 'content-type-override':
				$ct = new THttpHeaderContentType();
				$ct->setContentType('application/json');
				$ct->setCharset('UTF-8');
				$manager->addHeader($ct);
				break;

			case 'x-content-type-options':
				$header = new THttpHeader();
				$header->setHeaderName('X-Content-Type-Options');
				$header->setHeaderValue('nosniff');
				$manager->addHeader($header);
				break;

			case 'multi-security':
				$hsts = new THttpHeaderHsts();
				$hsts->setMaxAge(31536000);
				$hsts->setIncludeSubDomains(true);
				$manager->addHeader($hsts);

				$xfo = new THttpHeader();
				$xfo->setHeaderName('X-Frame-Options');
				$xfo->setHeaderValue('SAMEORIGIN');
				$manager->addHeader($xfo);

				$xcto = new THttpHeader();
				$xcto->setHeaderName('X-Content-Type-Options');
				$xcto->setHeaderValue('nosniff');
				$manager->addHeader($xcto);
				break;

			default:
				$header = new THttpHeader();
				$header->setHeaderName('X-HttpHeaders-Test');
				$header->setHeaderValue('harness');
				$manager->addHeader($header);
				break;
		}
	}
}
