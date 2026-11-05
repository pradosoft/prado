<?php

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\UI\TPage;

/**
 * StyleCspPage — harness page for the CSP nonce of style elements.
 *
 * The policy requires the per-request nonce ({@see THttpHeaderCsp::NONCE}) for
 * style elements and allows style attributes:
 * `style-src-elem 'self' NONCE; style-src-attr 'unsafe-inline'`.
 * The page styles four markers: CSS in a TStyleSheet, CSS registered with
 * registerStyleSheet(), CSS a callback registers, and a raw `<style>` without a
 * nonce, which the policy blocks.
 */
class StyleCspPage extends TPage
{
	public function onInit($param): void
	{
		parent::onInit($param);
		$csp = new THttpHeaderCsp();
		$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
		$csp->setPolicy(TCspDirective::ScriptSrc, "'self' " . THttpHeaderCsp::NONCE . " 'strict-dynamic'");
		$csp->setPolicy(TCspDirective::StyleSrcElem, "'self' " . THttpHeaderCsp::NONCE);
		$csp->setPolicy(TCspDirective::StyleSrcAttr, "'unsafe-inline'");
		$csp->init([]);
		$this->getApplication()->getModule('httpHeaders')->addHeader($csp);
	}

	public function onPreRender($param): void
	{
		parent::onPreRender($param);
		$this->getClientScript()->registerStyleSheet('registered', '#registered-marker { color: rgb(0, 0, 255); }');
	}

	public function addStyle($sender, $param): void
	{
		$this->getClientScript()->registerStyleSheet('callback', '#callback-marker { color: rgb(128, 0, 128); }');
		$this->status->setText('styled');
	}
}
