<?php

/**
 * TTestableHttpHeadersManager — shared test double for HttpHeaders tests.
 *
 * Included via require_once from THttpHeadersManagerTest.php and
 * THttpHeaderCspIntegrationTest.php.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\TApplication;
use Prado\Web\HttpHeaders\THttpHeadersManager;

/**
 * Intercepts sendHeaders() so tests can inspect emitted header strings
 * without touching the live HTTP stack. Exposes all protected helpers as
 * public methods so the manager can be exercised without a full TApplication
 * lifecycle.
 */
class TTestableHttpHeadersManager extends THttpHeadersManager
{
	/** @var string[] Headers captured by the overridden sendHeaders(). */
	public array $sentHeaders = [];

	/** @var int Number of times sendHeaders() was called. */
	public int $sendCount = 0;

	/**
	 * Base URL returned by {@see buildReporterUrl()}. The service ID is appended
	 * as a path segment so callers that verify the service ID appears in the URL
	 * (e.g. integration tests for literal ReportingServiceId) still work correctly.
	 */
	public string $fakeReporterUrl = 'https://example.com/csp-report';

	protected function buildReporterUrl(TApplication $app, string $serviceId): string
	{
		return rtrim($this->fakeReporterUrl, '/') . '/' . $serviceId;
	}

	protected function sendHeaders(): void
	{
		$this->finalizeHeaders();
		$this->sendCount++;
		foreach ($this->getHeaders() as $header) {
			$this->sentHeaders[] = (string) $header;
		}
		$this->setHeadersSent(true);
	}

	public function publicLoadHeaderClasses(mixed $config): void
	{
		$this->loadHeaderClasses($this->normalizeConfig($config));
	}

	public function publicLoadHeaders(mixed $config): void
	{
		$this->loadHeaders($this->normalizeConfig($config));
	}

	public function publicNormalizeConfig(mixed $config): array
	{
		return $this->normalizeConfig($config);
	}

	public function publicConfigToArray(\Prado\Xml\TXmlElement $config): array
	{
		return $this->configToArray($config);
	}

	public function publicInitComplete(): void
	{
		$this->initComplete();
	}

	public function publicLoadDefaultHeaders(): void
	{
		$this->loadDefaultHeaders();
	}

	public function publicResolveReportOnly(): bool
	{
		return $this->resolveReportOnly();
	}

	public function publicSetReportOnlyDirect(?bool $value): void
	{
		$this->setReportOnlyDirect($value);
	}

	public function publicGetReportOnlyDirect(): ?bool
	{
		return $this->getReportOnlyDirect();
	}

	public function publicSetHeadersDirect(array $headers): void
	{
		$this->setHeadersDirect($headers);
	}

	public function publicFinalizeReporterService(): void
	{
		$this->finalizeReporterService();
	}

	public function publicFinalizeHeaders(): void
	{
		$this->finalizeHeaders();
	}

	public function publicValidateHeaders(): void
	{
		$this->validateHeaders();
	}

	public function publicValidateCoepCoopPair(): void
	{
		$this->validateCoepCoopPair();
	}

	public function publicValidateFrameAncestorsXFrameOptions(): void
	{
		$this->validateFrameAncestorsXFrameOptions();
	}

	public function publicGetDefaultNameClassMap(): array
	{
		return $this->getDefaultNameClassMap();
	}

	public function publicGetNameClassMapDirect(): array
	{
		return $this->getNameClassMapDirect();
	}
}
