<?php

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;
use Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints;
use Prado\Web\UI\TPage;

/**
 * CspReportPage — harness page for CSP report-uri and report-to functional tests.
 *
 * Query parameters:
 *   action   — reporting scenario to activate (see _configureCsp).
 *   endpoint — URL for the report receiver (Playwright intercepts this).
 */
class CspReportPage extends TPage
{
	/** @var string The active action, exposed to the template. */
	private string $_action = '';

	public function onLoad($param): void
	{
		parent::onLoad($param);
		$this->_action = $this->Request->itemAt('action') ?? 'report-uri';
		$endpoint = $this->Request->itemAt('endpoint') ?? '';
		$this->_configureCsp($this->_action, $endpoint);
	}

	public function getAction(): string
	{
		return $this->_action;
	}

	private function _configureCsp(string $action, string $endpoint): void
	{
		/** @var \Prado\Web\HttpHeaders\THttpHeadersManager $manager */
		$manager = $this->getApplication()->getModule('httpHeaders');

		switch ($action) {
			case 'report-uri':
				// Enforcing CSP with report-uri; inline scripts trigger a violation report.
				$csp = new THttpHeaderCsp();
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				$csp->setPolicy(
					TCspDirective::ReportUri,
					$endpoint !== '' ? $endpoint : 'https://csp-report.example.invalid/report'
				);
				$manager->addHeader($csp);
				break;

			case 'report-only-report-uri':
				// Report-Only CSP with report-uri; content is NOT blocked but violations are reported.
				$csp = new THttpHeaderCsp();
				$csp->setReportOnly(true);
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				$csp->setPolicy(
					TCspDirective::ReportUri,
					$endpoint !== '' ? $endpoint : 'https://csp-report.example.invalid/report'
				);
				$manager->addHeader($csp);
				break;

			case 'report-to':
				// Enforcing CSP with report-to directive; paired with Reporting-Endpoints header.
				$endpointUrl = $endpoint !== '' ? $endpoint : 'https://csp-report.example.invalid/report';
				$csp = new THttpHeaderCsp();
				$csp->setPolicy(TCspDirective::DefaultSrc, "'none'");
				$csp->setPolicy(TCspDirective::ScriptSrc, "'self'");
				$csp->setPolicy(TCspDirective::ReportTo, 'csp-endpoint');
				$manager->addHeader($csp);

				$re = new THttpHeaderReportingEndpoints();
				$re->addEndpoint('csp-endpoint', $endpointUrl);
				$manager->addHeader($re);
				$re->init([]);
				break;

			default:
				$csp = new THttpHeaderCsp();
				$csp->setPolicy(TCspDirective::DefaultSrc, "'self'");
				$manager->addHeader($csp);
				break;
		}
	}
}
