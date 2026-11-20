<?php

namespace Prado\Test\Unit\Email\Transport;

use Prado\Email\TEmail;
use Prado\Email\Transport\TEmailTransport;

/**
 * A transport that keeps the emails it is given, and throws the queued failures first.
 */
class TCaptureTransport extends TEmailTransport
{
	/** @var TEmail[] the emails that were accepted. */
	public array $sent = [];

	/** @var \Throwable[] the failures of the next sends, in order. */
	public array $failures = [];

	/** @var int the number of closes. */
	public int $closes = 0;

	/** @var mixed the configuration passed to init. */
	public $config = false;

	private string $_label = '';

	public function init($config)
	{
		$this->config = $config;
	}

	public function send(TEmail $email): void
	{
		if ($this->failures) {
			throw array_shift($this->failures);
		}
		$this->sent[] = $email;
	}

	public function close(): void
	{
		$this->closes++;
	}

	public function getLabel(): string
	{
		return $this->_label;
	}

	public function setLabel($value)
	{
		$this->_label = (string) $value;
	}
}
