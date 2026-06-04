<?php

/**
 * TConcreteHeader — shared test double for TBaseHttpHeader tests.
 *
 * Auto-loaded by {@see PradoUnitRequires}; no explicit `require_once` is
 * needed from individual test files. Provides a minimal concrete subclass
 * that overrides the protected {@see TBaseHttpHeader::header()} seam to
 * capture calls without touching the live HTTP stack.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

use Prado\Web\HttpHeaders\TBaseHttpHeader;

class TConcreteHeader extends TBaseHttpHeader
{
	public string $name = 'X-Test';
	public string $value = 'test-value';

	/** @var list<array{header:string,replace:bool,response_code:int}> */
	public array $capturedHeaderCalls = [];

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

	protected function header(string $header, bool $replace = true, int $response_code = 0): void
	{
		$this->capturedHeaderCalls[] = compact('header', 'replace', 'response_code');
	}
}
