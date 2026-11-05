<?php

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TPage;

/**
 * CspCallbackPage — harness page for scripts that a callback response runs or loads
 * under a nonce-based Content-Security-Policy.
 *
 * The policy is `script-src 'self' NONCE`. Each request, the callback included, gets
 * a new nonce, while the page keeps the policy of its first load. The callback sends:
 *   - an inline script block (Prado.Element.appendScriptBlock),
 *   - a cross-origin script file (Prado.ScriptManager), which `'self'` does not cover,
 *   - markup with a `<script>` carrying the callback's nonce (Prado.Element.replace),
 *   - an end script and a snippet, each run by Prado.Element.evaluateScript.
 * Each script sets a dataset marker on `<html>` when it runs.
 */
class CspCallbackPage extends TPage
{
	public function onPreInit($param): void
	{
		parent::onPreInit($param);
		$csp = new THttpHeaderCsp();
		$csp->init(['policies' => [['name' => TCspDirective::ScriptSrc, 'value' => "'self' " . THttpHeaderCsp::NONCE]]]);
		$this->getApplication()->getModule('httpHeaders')->addHeader($csp);
	}

	public function getNonce(): string
	{
		return (string) TJavaScript::getScriptNonce();
	}

	public function runCallback($sender, $param): void
	{
		$client = $this->getCallbackClient();

		$block = $this->getResponse()->createHtmlWriter();
		$block->write("document.documentElement.dataset.callbackBlockRan = '1';");
		$client->appendScriptBlock($block);

		$port = (string) ($_SERVER['SERVER_PORT'] ?? '8037');
		$this->getClientScript()->registerScriptFile('csp-callback-file', 'http://localhost:' . $port . '/tests/harness/HttpHeaders/csp-callback-asset.php');

		$markup = $this->getResponse()->createHtmlWriter();
		$markup->write(TJavaScript::renderScriptBlock("document.documentElement.dataset.callbackMarkupRan = '1';"));
		$client->replaceContent('replace-target', $markup, false);

		$this->getClientScript()->registerEndScript('csp-callback-end', "document.documentElement.dataset.callbackEndRan = '1';");
		$client->evaluateScript("document.documentElement.dataset.callbackEvaluateRan = '1';");
	}
}
