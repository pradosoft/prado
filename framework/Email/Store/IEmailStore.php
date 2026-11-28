<?php

/**
 * IEmailStore interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Store;

use Prado\Email\Bounce\TEmailBounce;
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
 * A stored email is identified by its {@see TEmail::getTrackingId TrackingId}.  The
 * info of an email is an array of its stored fields: "tracking_id",
 * "rfc_message_id", "username", "list_name", "subject", "status", "attempts",
 * "error_text", "created_time", "sent_time", "open_count", "opened_time",
 * "click_count", "clicked_time", "unsubscribed_time", and "recipients", a list of
 * arrays with "address", "display_name", "kind" (to, cc, or bcc), "bounce_status",
 * "bounce_action", "bounce_diagnostic", and "bounced_time".
 *
 * A suppression stops emails to an address.  One with a list name stops the
 * emails of that list; one with an empty list name stops every email.
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

	/**
	 * Adds an address to the suppression list.  An address already suppressed for the list stays as it is.
	 * @param string $address the address.
	 * @param string $reason the {@see TEmailSuppressionReason}.
	 * @param string $list the mailing list to suppress; empty suppresses every email.
	 * @param ?string $trackingId the tracking ID of the email that caused the suppression.
	 */
	public function addSuppression(string $address, string $reason, string $list = '', ?string $trackingId = null): void;

	/**
	 * Removes an address from the suppression list.
	 * @param string $address the address.
	 * @param ?string $list the mailing list to remove it from; empty for the suppression of every email, null for all its suppressions.
	 * @return int the number of suppressions removed.
	 */
	public function removeSuppression(string $address, ?string $list = null): int;

	/**
	 * Rebuilds a stored email.
	 * @param string $trackingId the tracking ID.
	 * @return ?TEmail the final email with its stored Status, null when it is unknown.
	 */
	public function getEmail(string $trackingId): ?TEmail;

	/**
	 * Returns the stored fields of an email.
	 * @param string $trackingId the tracking ID.
	 * @return ?array the info of the email, null when it is unknown.
	 */
	public function getEmailInfo(string $trackingId): ?array;

	/**
	 * Counts an open of an email.
	 * @param string $trackingId the tracking ID.
	 * @return bool whether the email is known.
	 */
	public function recordOpen(string $trackingId): bool;

	/**
	 * Counts a click of a tracked link.
	 * @param string $trackingId the tracking ID.
	 * @param int $link the link number.
	 * @return ?string the target URL of the link, null when the email or the link is unknown.
	 */
	public function recordClick(string $trackingId, int $link): ?string;

	/**
	 * Records an unsubscribe.  An email of a list with one To address suppresses that
	 * address for the list.
	 * @param string $trackingId the tracking ID.
	 * @return ?array the info of the email with "suppressed", the suppressed address or null; null when the email is unknown.
	 */
	public function recordUnsubscribe(string $trackingId): ?array;

	/**
	 * Records a bounce on the recipient of the email it reports.  A permanent bounce
	 * suppresses the address for every email.
	 * @param TEmailBounce $bounce the bounce of one recipient.
	 * @return ?array the info of the email with "suppressed", the suppressed address or null; null when no email and recipient match.
	 */
	public function recordBounce(TEmailBounce $bounce): ?array;

	/**
	 * Records that a recipient reported an email as spam.  The address is
	 * suppressed for every email.
	 * @param string $trackingId the tracking ID.
	 * @param ?string $address the address that complained, a recipient of the email; null for its only recipient.
	 * @return ?array the info of the email with "suppressed", the suppressed address; null when the email or the recipient is unknown.
	 */
	public function recordComplaint(string $trackingId, ?string $address = null): ?array;

	/**
	 * Finds a stored email by its Message-ID, which is how a bounce or a mail
	 * provider names an email.
	 * @param string $messageId the Message-ID, with or without angle brackets.
	 * @return ?string the tracking ID of the email, null when it is unknown.
	 */
	public function findTrackingId(string $messageId): ?string;

	/**
	 * Lists stored emails, newest first.
	 * @param ?string $username the user name of the emails; null for every user.
	 * @param ?string $status the TEmailStatus of the emails; null for every status.
	 * @param int $limit the most emails to return; 0 for all.
	 * @param int $offset the number of emails to skip.
	 * @return array|false the infos of the emails without "recipients"; false when reading the log is not permitted.
	 */
	public function findEmails(?string $username = null, ?string $status = null, int $limit = 0, int $offset = 0): array|false;

	/**
	 * Counts stored emails.
	 * @param ?string $username the user name of the emails; null for every user.
	 * @param ?string $status the TEmailStatus of the emails; null for every status.
	 * @return false|int the number of emails; false when reading the log is not permitted.
	 */
	public function countEmails(?string $username = null, ?string $status = null): false|int;

	/**
	 * Deletes stored emails created before an age.
	 * @param int $seconds the age in seconds.
	 * @param ?string $status the TEmailStatus to delete; null deletes every status except Queued and Sending.
	 * @return false|int the number of emails deleted; false when deleting from the log is not permitted.
	 */
	public function deleteEmails(int $seconds, ?string $status = null): false|int;
}
