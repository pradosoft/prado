<?php

/**
 * IEmailStore interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Store;

use Prado\Email\TEmail;

/**
 * IEmailStore interface.
 *
 * IEmailStore keeps the emails of a {@see \Prado\Email\TEmailModule}: the log of
 * what was sent, the queue of what is waiting, and the list of addresses that
 * must not be sent to.  {@see TDbEmailStore} implements it on a database.
 *
 * An email moves through the {@see \Prado\Email\TEmailStatus} values:
 *
 * | From | Method | To |
 * |---|---|---|
 * | (new) | {@see addEmail} | Queued or Sending |
 * | Queued | {@see claimEmail}, {@see claimEmails} | Sending |
 * | Sending | {@see markSent} | Sent |
 * | Sending | {@see markFailed} | Queued for a retry, or Failed |
 *
 * A claim is atomic: of the processes that claim a queued email, one gets it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEmailStore
{
	/**
	 * Stores a new final email.
	 * @param TEmail $email the final email.
	 * @param string $status the TEmailStatus to store it with.
	 */
	public function addEmail(TEmail $email, string $status): void;

	/**
	 * Claims a queued email for delivery by this process.
	 * @param TEmail $email the stored email.
	 * @return bool whether this process got the email.
	 */
	public function claimEmail(TEmail $email): bool;

	/**
	 * Claims queued emails that are due, oldest first.
	 * @param int $limit the most emails to claim; 0 claims all that are due.
	 * @return TEmail[] the claimed emails, final and ready for a transport.
	 */
	public function claimEmails(int $limit = 0): array;

	/**
	 * Records that a transport accepted a claimed email.
	 * @param TEmail $email the delivered email.
	 */
	public function markSent(TEmail $email): void;

	/**
	 * Records a failed delivery attempt of a claimed email.
	 * @param TEmail $email the email.
	 * @param string $error the reason of the failure.
	 * @param bool $transient whether a later attempt may succeed.
	 * @return string TEmailStatus::Queued when the email will be tried again, TEmailStatus::Failed otherwise.
	 */
	public function markFailed(TEmail $email, string $error, bool $transient): string;

	/**
	 * Returns the addresses that must not receive an email of a list.
	 * @param string[] $addresses the addresses to check.
	 * @param string $list the mailing list of the email, empty for a transactional email.
	 * @return string[] the suppressed addresses, in lower case.
	 */
	public function getSuppressed(array $addresses, string $list = ''): array;
}
