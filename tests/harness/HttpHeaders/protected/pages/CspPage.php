<?php

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\UI\TPage;

/**
 * CspPage — harness page for Content-Security-Policy functional tests.
 *
 * Query parameters:
 *   action    — which CSP scenario to activate (see _configureCsp).
 *   collector — optional report-uri URL (Playwright intercepts this).
 */
class CspPage extends TPage
{
	/** @var string Per-request nonce generated in onPreInit(). */
	private string $_nonce = '';

	/** @var string The active action, exposed to the template. */
	private string $_action = '';

	public function onPreInit($param): void
	{
		parent::onPreInit($param);
		$this->_nonce = rtrim(base64_encode(random_bytes(16)), '=');
	}

	public function onLoad($param): void
	{
		parent::onLoad($param);
		$this->_action = $this->Request->itemAt('action') ?? 'script-src';
		$collector = $this->Request->itemAt('collector') ?? '';
		$this->_configureCsp($this->_action, $collector);
	}

	public function getAction(): string
	{
		return $this->_action;
	}

	public function getNonce(): string
	{
		return $this->_nonce;
	}

	private function _configureCsp(string $action, string $collector): void
	{
		/** @var \Prado\Web\HttpHeaders\THttpHeadersManager $manager */
		$manager = $this->getApplication()->getModule('httpHeaders');
		$csp = new THttpHeaderCsp();

		switch ($action) {
			case 'script-src':
				// Allows only same-origin scripts; blocks inline scripts and untrusted origins.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				break;

			case 'script-src-nonce':
				// Allows only scripts bearing the per-request nonce; blocks inline scripts.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'nonce-" . $this->_nonce . "'");
				break;

			case 'img-src':
				// Allows same-origin images only; blocks external images.
				// Scripts are allowed via nonce so fetch-result can still run.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ImgSrc, "'self'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'nonce-" . $this->_nonce . "'");
				break;

			case 'style-src':
				// Allows same-origin stylesheets only; blocks inline styles.
				// Scripts are allowed via nonce so fetch-result can still run.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::StyleSrc, "'self'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'nonce-" . $this->_nonce . "'");
				break;

			case 'frame-src':
				// Blocks all frames and iframes.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
				$csp->setPolicy(TCspDirective::FrameSrc, "'none'");
				break;

			case 'frame-ancestors':
				// Prevents this page from being embedded in any frame.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
				$csp->setPolicy(TCspDirective::FrameAncestors, "'none'");
				break;

			case 'connect-src':
				// Blocks all outbound connections (XHR, fetch, WebSocket).
				// Scripts are allowed via nonce so the fetch attempt can be made.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ConnectSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'nonce-" . $this->_nonce . "'");
				break;

			case 'upgrade-insecure':
				// Upgrades HTTP sub-resource requests to HTTPS.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
				$csp->setPolicy(TCspDirective::UpgradeInsecureRequests);
				break;

			case 'multiple':
				// Realistic production-style policy with several directives.
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				$csp->setPolicy(TCspDirective::StyleSrc, "'self' 'unsafe-inline'");
				$csp->setPolicy(TCspDirective::ImgSrc, "'self' data:");
				$csp->setPolicy(TCspDirective::FontSrc, "'self'");
				$csp->setPolicy(TCspDirective::ConnectSrc, "'self'");
				$csp->setPolicy(TCspDirective::FrameAncestors, "'none'");
				break;

			case 'report-only':
				// Report-Only: violations are reported but content is not blocked.
				$csp->setReportOnly(true);
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				break;

			default:
				$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
				break;
		}

		if ($collector !== '') {
			$csp->setPolicy(TCspDirective::ReportUri, $collector);
		}

		$manager->addHeader($csp);
	}
}
