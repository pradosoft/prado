<?php

/**
 * TStubHeader — instrumented test double for THttpHeadersManager tests.
 *
 * Auto-loaded by {@see PradoUnitRequires}. Counts lifecycle calls
 * (`init` / `initComplete` / `finalizeHeader`) so tests can assert the
 * manager wires each header instance correctly.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\HttpHeaders\TBaseHttpHeader;

class TStubHeader extends TBaseHttpHeader
{
	public string $name = 'X-Stub';
	public string $value = 'stub-value';
	public int $initCallCount = 0;
	public int $initCompleteCallCount = 0;
	public int $finalizeCallCount = 0;

	public function getHeaderName(): string
	{
		return $this->name;
	}

	public function getHeaderValue(): string
	{
		return $this->value;
	}

	public function setHeaderValue($value): void
	{
		$this->value = (string) $value;
	}

	public function init($config): void
	{
		$this->initCallCount++;
	}

	public function initComplete(): void
	{
		$this->initCompleteCallCount++;
	}

	public function finalizeHeader(): void
	{
		$this->finalizeCallCount++;
	}
}
