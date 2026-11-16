<?php

namespace Prado\Test\Unit\Util\Email;

use Prado\Data\TDbConnection;
use Prado\Util\Email\TDbEmailModule;

/**
 * A TDbEmailModule on an in-memory SQLite database whose sent emails are captured.
 */
class TDbEmailModuleTestModule extends TDbEmailModule
{
	/** @var \Prado\Util\Email\TEmail[] the emails handed to the mailer. */
	public array $sent = [];

	/** @var bool the result the mailer reports. */
	public bool $mailerResult = true;

	public function __construct()
	{
		parent::__construct();
		$this->attachEventHandler('onSendEmail', function ($sender, $param) {
			$this->sent[] = $param->getEmail();
			return $this->mailerResult;
		});
	}

	protected function getCustomDbConnection(): ?TDbConnection
	{
		return new TDbConnection('sqlite::memory:');
	}
}
