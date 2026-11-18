<?php

namespace Prado\Test\Unit\Email\Store;

use Prado\Email\Store\IEmailStore;
use Prado\Email\TEmail;
use Prado\Email\TEmailStatus;

/**
 * An IEmailStore in memory: one retry of a transient failure, and a suppression list by list name.
 */
class TMemoryEmailStore extends \Prado\TModule implements IEmailStore
{
	/** @var array<string, array{email: TEmail, status: string, attempts: int, error: ?string}> the stored emails by tracking ID. */
	public array $rows = [];

	/** @var array<string, string[]> the suppressed lower-case addresses by list; '' holds the addresses suppressed for every list. */
	public array $suppressed = [];

	/** @var int the attempts after which a transient failure is final. */
	public int $maxAttempts = 2;

	public function addEmail(TEmail $email, string $status): void
	{
		$this->rows[$email->getTrackingId()] = ['email' => $email, 'status' => $status, 'attempts' => 0, 'error' => null];
	}

	public function claimEmail(TEmail $email): bool
	{
		$id = $email->getTrackingId();
		if (($this->rows[$id]['status'] ?? null) !== TEmailStatus::Queued) {
			return false;
		}
		$this->rows[$id]['status'] = TEmailStatus::Sending;
		return true;
	}

	public function claimEmails(int $limit = 0): array
	{
		$emails = [];
		foreach ($this->rows as $id => $row) {
			if ($row['status'] === TEmailStatus::Queued && ($limit === 0 || count($emails) < $limit)) {
				$this->rows[$id]['status'] = TEmailStatus::Sending;
				$copy = clone $row['email'];
				$copy->setTrackingId($id);
				$copy->setMessageId($row['email']->getMessageId());
				$emails[] = $copy;
			}
		}
		return $emails;
	}

	public function markSent(TEmail $email): void
	{
		$this->rows[$email->getTrackingId()]['status'] = TEmailStatus::Sent;
	}

	public function markFailed(TEmail $email, string $error, bool $transient): string
	{
		$row = &$this->rows[$email->getTrackingId()];
		$row['attempts']++;
		$row['error'] = $error;
		return $row['status'] = ($transient && $row['attempts'] < $this->maxAttempts) ? TEmailStatus::Queued : TEmailStatus::Failed;
	}

	public function getSuppressed(array $addresses, string $list = ''): array
	{
		$blocked = array_merge($this->suppressed[''] ?? [], $list === '' ? [] : ($this->suppressed[$list] ?? []));
		return array_values(array_intersect(array_map('strtolower', $addresses), $blocked));
	}

	public function status(TEmail $email): ?string
	{
		return $this->rows[$email->getTrackingId()]['status'] ?? null;
	}
}
