<?php

/**
 * TDbEmailStore class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Store;

use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\TDbDriver;
use Prado\Data\TDbDriverCapabilities;
use Prado\Email\Bounce\TEmailBounce;
use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;
use Prado\Email\TEmailFile;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Util\TDbModule;

/**
 * TDbEmailStore class.
 *
 * TDbEmailStore is the {@see IEmailStore} on a database.  The connection is the
 * {@see setConnectionID ConnectionID} of a TDataSourceConfig module, or an SQLite
 * database named "emails.db" in the runtime path.
 *
 * <code>
 * <module id="emailstore" class="Prado\Email\Store\TDbEmailStore" ConnectionID="db" />
 * <module id="email" class="Prado\Email\TEmailModule" StoreID="emailstore" />
 * </code>
 *
 * | Table | Rows |
 * |---|---|
 * | `email_messages` | one per email: its content, status, attempts, and open and click counts |
 * | `email_recipients` | one per To, Cc, and Bcc address of an email, with its bounce |
 * | `email_suppressions` | one per address and list that must not be sent to |
 *
 * The tables are created when missing unless {@see setAutoCreateTables AutoCreateTables}
 * is false; {@see setTablePrefix TablePrefix} replaces "email_".
 *
 * The queue is the emails with the Queued status.  A claim is one UPDATE that
 * moves a Queued row to Sending, so two processes never deliver the same email.  A
 * claim older than {@see setClaimTimeout ClaimTimeout} belongs to a process that
 * died and returns to the queue.  A transient failure is retried after
 * {@see setRetryDelay RetryDelay} seconds, doubled with each attempt, until
 * {@see setMaxAttempts MaxAttempts}.
 *
 * A bounce is matched to an email by its Message-ID and to a recipient of that
 * email, so a forged bounce cannot suppress an address the application did not write to.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method bool dyFindEmails(bool $return, ?string $username, ?string $status)
 * @method bool dyCountEmails(bool $return, ?string $username, ?string $status)
 * @method bool dyDeleteEmails(bool $return, int $seconds, ?string $status)
 */
class TDbEmailStore extends TDbModule implements IEmailStore, IPermissions
{
	use TApplicationClockAwareTrait;

	/** The permission to read the email log. */
	public const PERM_EMAIL_LOG_READ = 'email_log_read';

	/** The permission to delete from the email log. */
	public const PERM_EMAIL_LOG_DELETE = 'email_log_delete';

	/** The longest stored error text. */
	protected const MAX_ERROR_LENGTH = 2000;

	/** @var string the prefix of the table names. */
	private string $_tablePrefix = 'email_';

	/** @var bool whether missing tables are created. */
	private bool $_autoCreateTables = true;

	/** @var bool whether the tables are verified. */
	private bool $_tablesEnsured = false;

	/** @var bool whether the module is initialized. */
	private bool $_storeInitialized = false;

	/** @var int the delivery attempts after which a transient failure is final. */
	private int $_maxAttempts = 5;

	/** @var int the seconds before the first retry. */
	private int $_retryDelay = 60;

	/** @var int the seconds after which a claim is stale. */
	private int $_claimTimeout = 900;

	/**
	 * Initializes the module.
	 * @param mixed $config the module configuration.
	 */
	public function init($config)
	{
		parent::init($config);
		$this->_storeInitialized = true;
	}

	/**
	 * Implements IPermissions for reading and deleting the email log.
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager the permissions manager.
	 * @return TPermissionEvent[] the permission events.
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_EMAIL_LOG_READ, 'Email read log.', ['dyFindEmails', 'dyCountEmails'], new TUserOwnerRule()),
			new TPermissionEvent(static::PERM_EMAIL_LOG_DELETE, 'Email delete log.', ['dyDeleteEmails']),
		];
	}

	/**
	 * Stores a new final email and its recipients.
	 * @param TEmail $email the final email.
	 * @param string $status the TEmailStatus to store it with.
	 */
	public function addEmail(TEmail $email, string $status): void
	{
		$this->ensureTables();
		$now = $this->getClock()->time();
		$address = fn (?TEmailAddress $a) => $a === null ? null : [$a->getAddress(), $a->getName()];
		$data = [
			'from' => $address($email->getFrom()),
			'sender' => $address($email->getSender()),
			'returnPath' => $address($email->getReturnPath()),
			'replyTo' => array_map($address, $email->getReplyTo()),
			'date' => $email->getDate(),
			'priority' => $email->getPriority(),
			'charset' => $email->getCharset(),
			'headers' => $email->getHeaders(),
			'files' => array_map(fn (TEmailFile $file) => $file->toArray(), $email->getFiles()),
			'links' => $email->getTrackedLinks(),
		];
		$transaction = $this->getDbConnection()->beginTransaction();
		try {
			$this->getTableGateway($this->getTableName('messages'))->insert([
				'tracking_id' => $email->getTrackingId(),
				'rfc_message_id' => $email->getMessageId(),
				'username' => $email->getUsername(),
				'list_name' => $email->getList(),
				'subject' => $email->getSubject(),
				'html_body' => $email->getHtml(),
				'text_body' => $email->getText(),
				'data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
				'secure' => $email->getSecure() ? 1 : 0,
				'status' => $status,
				'attempts' => 0,
				'claim_token' => $status === TEmailStatus::Sending ? bin2hex(random_bytes(8)) : null,
				'claimed_time' => $status === TEmailStatus::Sending ? $now : null,
				'created_time' => $now,
				'open_count' => 0,
				'click_count' => 0,
			]);
			$messageId = (int) $this->queryScalar('SELECT message_id FROM ' . $this->getTableName('messages') . ' WHERE tracking_id = :id', [':id' => $email->getTrackingId()]);
			$recipients = $this->getTableGateway($this->getTableName('recipients'));
			foreach (['to' => $email->getTo(), 'cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $kind => $addresses) {
				foreach ($addresses as $recipient) {
					$recipients->insert([
						'message_id' => $messageId,
						'address' => $recipient->getAddress(),
						'address_key' => strtolower($recipient->getAddress()),
						'display_name' => $recipient->getName(),
						'kind' => $kind,
					]);
				}
			}
			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollback();
			throw $e;
		}
	}

	/**
	 * Claims a queued email for delivery by this process.
	 * @param TEmail $email the stored email.
	 * @return bool whether this process got the email.
	 */
	public function claimEmail(TEmail $email): bool
	{
		$this->ensureTables();
		return $this->claim('tracking_id = :id', [':id' => $email->getTrackingId()]);
	}

	/**
	 * Claims queued emails that are due, oldest first, after returning stale claims to the queue.
	 * @param int $limit the most emails to claim; 0 claims all that are due.
	 * @return TEmail[] the claimed emails, final and ready for a transport.
	 */
	public function claimEmails(int $limit = 0): array
	{
		$this->ensureTables();
		$now = $this->getClock()->time();
		$this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET status = :queued, claim_token = NULL, claimed_time = NULL WHERE status = :sending AND claimed_time < :stale',
			[':queued' => TEmailStatus::Queued, ':sending' => TEmailStatus::Sending, ':stale' => $now - $this->getClaimTimeout()]
		);
		$criteria = new TSqlCriteria('status = :status AND (next_attempt_time IS NULL OR next_attempt_time <= :now)', [':status' => TEmailStatus::Queued, ':now' => $now]);
		$criteria->setOrdersBy('message_id ASC');
		if ($limit > 0) {
			$criteria->setLimit($limit);
		}
		$emails = [];
		foreach ($this->getTableGateway($this->getTableName('messages'))->findAll($criteria)->readAll() as $row) {
			if ($this->claim('message_id = :id', [':id' => (int) $row['message_id']])) {
				$email = $this->createEmail($row);
				$email->setStatus(TEmailStatus::Sending);
				$emails[] = $email;
			}
		}
		return $emails;
	}

	/**
	 * Moves one Queued row to Sending.
	 * @param string $condition the SQL condition that selects the row.
	 * @param array $parameters the named parameters of the condition.
	 * @return bool whether the row was Queued and is now claimed.
	 */
	protected function claim(string $condition, array $parameters): bool
	{
		return $this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET status = :sending, claim_token = :token, claimed_time = :now WHERE ' . $condition . ' AND status = :queued',
			[':sending' => TEmailStatus::Sending, ':token' => bin2hex(random_bytes(8)), ':now' => $this->getClock()->time(), ':queued' => TEmailStatus::Queued] + $parameters
		) === 1;
	}

	/**
	 * Records that a transport accepted a claimed email.
	 * @param TEmail $email the delivered email.
	 */
	public function markSent(TEmail $email): void
	{
		$this->ensureTables();
		$this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET status = :status, attempts = attempts + 1, sent_time = :now, error_text = NULL, next_attempt_time = NULL, claim_token = NULL, claimed_time = NULL WHERE tracking_id = :id',
			[':status' => TEmailStatus::Sent, ':now' => $this->getClock()->time(), ':id' => $email->getTrackingId()]
		);
	}

	/**
	 * Records a failed delivery attempt.  A transient failure below MaxAttempts
	 * returns the email to the queue with a delay that doubles with each attempt.
	 * @param TEmail $email the email.
	 * @param string $error the reason of the failure.
	 * @param bool $transient whether a later attempt may succeed.
	 * @return string TEmailStatus::Queued when the email will be tried again, TEmailStatus::Failed otherwise.
	 */
	public function markFailed(TEmail $email, string $error, bool $transient): string
	{
		$this->ensureTables();
		$attempts = 1 + (int) $this->queryScalar('SELECT attempts FROM ' . $this->getTableName('messages') . ' WHERE tracking_id = :id', [':id' => $email->getTrackingId()]);
		$retry = $transient && $attempts < $this->getMaxAttempts();
		$status = $retry ? TEmailStatus::Queued : TEmailStatus::Failed;
		$this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET status = :status, attempts = :attempts, error_text = :error, next_attempt_time = :next, claim_token = NULL, claimed_time = NULL WHERE tracking_id = :id',
			[
				':status' => $status,
				':attempts' => $attempts,
				':error' => substr($error, 0, self::MAX_ERROR_LENGTH),
				':next' => $retry ? $this->getClock()->time() + $this->getRetryDelay() * (2 ** ($attempts - 1)) : null,
				':id' => $email->getTrackingId(),
			]
		);
		return $status;
	}

	/**
	 * Returns the addresses that must not receive an email of a list.
	 * @param string[] $addresses the addresses to check.
	 * @param string $list the mailing list of the email, empty for a transactional email.
	 * @return string[] the suppressed addresses, in lower case.
	 */
	public function getSuppressed(array $addresses, string $list = ''): array
	{
		if (!$addresses) {
			return [];
		}
		$this->ensureTables();
		$parameters = [':list' => $list];
		foreach (array_values(array_unique(array_map('strtolower', $addresses))) as $index => $address) {
			$parameters[':a' . $index] = $address;
		}
		$names = implode(', ', array_diff(array_keys($parameters), [':list']));
		$rows = $this->queryAll(
			'SELECT DISTINCT address_key FROM ' . $this->getTableName('suppressions') . " WHERE address_key IN ({$names}) AND (list_name = '' OR list_name = :list)",
			$parameters
		);
		return array_column($rows, 'address_key');
	}

	/**
	 * Adds an address to the suppression list.  An address already suppressed for the list stays as it is.
	 * @param string $address the address.
	 * @param string $reason the {@see TEmailSuppressionReason}.
	 * @param string $list the mailing list to suppress; empty suppresses every email.
	 * @param ?string $trackingId the tracking ID of the email that caused the suppression.
	 */
	public function addSuppression(string $address, string $reason, string $list = '', ?string $trackingId = null): void
	{
		$this->ensureTables();
		$this->getTableGateway($this->getTableName('suppressions'))->insertOrIgnore([
			'address_key' => strtolower(trim($address)),
			'list_name' => $list,
			'reason' => TPropertyValue::ensureEnum($reason, TEmailSuppressionReason::class),
			'tracking_id' => $trackingId,
			'created_time' => $this->getClock()->time(),
		]);
	}

	/**
	 * Removes an address from the suppression list.
	 * @param string $address the address.
	 * @param ?string $list the mailing list to remove it from; empty for the suppression of every email, null for all its suppressions.
	 * @return int the number of suppressions removed.
	 */
	public function removeSuppression(string $address, ?string $list = null): int
	{
		$this->ensureTables();
		$sql = 'DELETE FROM ' . $this->getTableName('suppressions') . ' WHERE address_key = :address';
		$parameters = [':address' => strtolower(trim($address))];
		if ($list !== null) {
			$sql .= ' AND list_name = :list';
			$parameters[':list'] = $list;
		}
		return $this->execute($sql, $parameters);
	}

	/**
	 * Lists the suppressions, newest first.
	 * @param int $limit the most suppressions to return; 0 for all.
	 * @param int $offset the number of suppressions to skip.
	 * @return array the rows, each with "address_key", "list_name", "reason", "tracking_id", and "created_time".
	 */
	public function getSuppressions(int $limit = 0, int $offset = 0): array
	{
		$this->ensureTables();
		$criteria = new TSqlCriteria();
		$criteria->setOrdersBy('suppression_id DESC');
		if ($limit > 0) {
			$criteria->setLimit($limit);
		}
		if ($offset > 0) {
			$criteria->setOffset($offset);
		}
		return $this->getTableGateway($this->getTableName('suppressions'))->findAll($criteria)->readAll();
	}

	/**
	 * Rebuilds a stored email.
	 * @param string $trackingId the tracking ID.
	 * @return ?TEmail the final email with its stored Status, null when it is unknown.
	 */
	public function getEmail(string $trackingId): ?TEmail
	{
		$row = $this->findMessage('tracking_id = :id', [':id' => $trackingId]);
		return $row === null ? null : $this->createEmail($row);
	}

	/**
	 * Returns the stored fields of an email.
	 * @param string $trackingId the tracking ID.
	 * @return ?array the info of the email, null when it is unknown.
	 */
	public function getEmailInfo(string $trackingId): ?array
	{
		$row = $this->findMessage('tracking_id = :id', [':id' => $trackingId]);
		return $row === null ? null : $this->createInfo($row);
	}

	/**
	 * Counts an open of an email.
	 * @param string $trackingId the tracking ID.
	 * @return bool whether the email is known.
	 */
	public function recordOpen(string $trackingId): bool
	{
		$this->ensureTables();
		return $this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET open_count = open_count + 1, opened_time = COALESCE(opened_time, :now) WHERE tracking_id = :id',
			[':now' => $this->getClock()->time(), ':id' => $trackingId]
		) === 1;
	}

	/**
	 * Counts a click of a tracked link; a click also marks the email opened.
	 * @param string $trackingId the tracking ID.
	 * @param int $link the link number.
	 * @return ?string the target URL of the link, null when the email or the link is unknown.
	 */
	public function recordClick(string $trackingId, int $link): ?string
	{
		if (($row = $this->findMessage('tracking_id = :id', [':id' => $trackingId])) === null) {
			return null;
		}
		$url = $this->decodeData($row)['links'][$link] ?? null;
		if (!is_string($url) || $url === '') {
			return null;
		}
		$this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET click_count = click_count + 1, clicked_time = COALESCE(clicked_time, :now), opened_time = COALESCE(opened_time, :now2) WHERE tracking_id = :id',
			[':now' => $now = $this->getClock()->time(), ':now2' => $now, ':id' => $trackingId]
		);
		return $url;
	}

	/**
	 * Records an unsubscribe.  An email of a list with one To address suppresses that
	 * address for the list.
	 * @param string $trackingId the tracking ID.
	 * @return ?array the info of the email with "suppressed", the suppressed address or null; null when the email is unknown.
	 */
	public function recordUnsubscribe(string $trackingId): ?array
	{
		if (($row = $this->findMessage('tracking_id = :id', [':id' => $trackingId])) === null) {
			return null;
		}
		$now = $this->getClock()->time();
		$this->execute(
			'UPDATE ' . $this->getTableName('messages') . ' SET unsubscribed_time = COALESCE(unsubscribed_time, :now) WHERE tracking_id = :id',
			[':now' => $now, ':id' => $trackingId]
		);
		$row['unsubscribed_time'] ??= $now;
		$info = $this->createInfo($row);
		$to = array_values(array_filter($info['recipients'], fn (array $recipient) => $recipient['kind'] === 'to'));
		$info['suppressed'] = null;
		if ($info['list_name'] !== '' && count($to) === 1) {
			$this->addSuppression($to[0]['address'], TEmailSuppressionReason::Unsubscribe, $info['list_name'], $trackingId);
			$info['suppressed'] = strtolower($to[0]['address']);
		}
		return $info;
	}

	/**
	 * Records a bounce on the recipient of the email it reports.  The email is found
	 * by Message-ID; the recipient is the bounced address, or the only recipient of
	 * the email.  A permanent bounce suppresses the address for every email.
	 * @param TEmailBounce $bounce the bounce of one recipient.
	 * @return ?array the info of the email with "suppressed", the suppressed address or null; null when no email and recipient match.
	 */
	public function recordBounce(TEmailBounce $bounce): ?array
	{
		$messageId = trim($bounce->getOriginalMessageId(), " \t<>");
		if ($messageId === '' || ($row = $this->findMessage('rfc_message_id = :id', [':id' => $messageId])) === null) {
			return null;
		}
		$info = $this->createInfo($row);
		$key = strtolower(trim($bounce->getRecipient()));
		$matches = array_values(array_filter($info['recipients'], fn (array $recipient) => strtolower($recipient['address']) === $key));
		if (!$matches && count($info['recipients']) === 1) {
			$matches = $info['recipients'];
		}
		if (!$matches) {
			return null;
		}
		$address = strtolower($matches[0]['address']);
		$this->execute(
			'UPDATE ' . $this->getTableName('recipients') . ' SET bounce_status = :status, bounce_action = :action, bounce_diagnostic = :diagnostic, bounced_time = :now WHERE message_id = :message AND address_key = :address',
			[
				':status' => substr($bounce->getStatus(), 0, 16),
				':action' => substr($bounce->getAction(), 0, 16),
				':diagnostic' => substr($bounce->getDiagnosticCode(), 0, self::MAX_ERROR_LENGTH),
				':now' => $this->getClock()->time(),
				':message' => (int) $row['message_id'],
				':address' => $address,
			]
		);
		$info = $this->createInfo($row);
		$info['suppressed'] = null;
		if ($bounce->getIsPermanent()) {
			$this->addSuppression($address, TEmailSuppressionReason::Bounce, '', $info['tracking_id']);
			$info['suppressed'] = $address;
		}
		return $info;
	}

	/**
	 * Records that a recipient reported an email as spam.  The address must be a
	 * recipient of the email; it is suppressed for every email.
	 * @param string $trackingId the tracking ID.
	 * @param ?string $address the address that complained; null for the only recipient of the email.
	 * @return ?array the info of the email with "suppressed", the suppressed address; null when the email or the recipient is unknown.
	 */
	public function recordComplaint(string $trackingId, ?string $address = null): ?array
	{
		if (($row = $this->findMessage('tracking_id = :id', [':id' => $trackingId])) === null) {
			return null;
		}
		$info = $this->createInfo($row);
		$addresses = array_map(fn (array $recipient) => strtolower($recipient['address']), $info['recipients']);
		if ($address === null) {
			$address = count($addresses) === 1 ? $addresses[0] : null;
		} else {
			$address = in_array(strtolower(trim($address)), $addresses, true) ? strtolower(trim($address)) : null;
		}
		if ($address === null) {
			return null;
		}
		$this->addSuppression($address, TEmailSuppressionReason::Complaint, '', $trackingId);
		$info['suppressed'] = $address;
		return $info;
	}

	/**
	 * Finds a stored email by its Message-ID.
	 * @param string $messageId the Message-ID, with or without angle brackets.
	 * @return ?string the tracking ID of the email, null when it is unknown.
	 */
	public function findTrackingId(string $messageId): ?string
	{
		$messageId = trim($messageId, " \t<>");
		if ($messageId === '') {
			return null;
		}
		$this->ensureTables();
		$id = $this->queryScalar('SELECT tracking_id FROM ' . $this->getTableName('messages') . ' WHERE rfc_message_id = :id', [':id' => $messageId]);
		return is_string($id) ? $id : null;
	}

	/**
	 * Lists stored emails, newest first.
	 * @param ?string $username the user name of the emails; null for every user.
	 * @param ?string $status the TEmailStatus of the emails; null for every status.
	 * @param int $limit the most emails to return; 0 for all.
	 * @param int $offset the number of emails to skip.
	 * @return array|false the infos of the emails without "recipients"; false when reading the log is not permitted.
	 */
	public function findEmails(?string $username = null, ?string $status = null, int $limit = 0, int $offset = 0): array|false
	{
		if ($this->dyFindEmails(false, $username, $status) === true) {
			return false;
		}
		$this->ensureTables();
		[$condition, $parameters] = $this->logCondition($username, $status);
		$criteria = new TSqlCriteria($condition, $parameters);
		$criteria->setOrdersBy('message_id DESC');
		if ($limit > 0) {
			$criteria->setLimit($limit);
		}
		if ($offset > 0) {
			$criteria->setOffset($offset);
		}
		return array_map(
			fn (array $row) => $this->createInfo($row, false),
			$this->getTableGateway($this->getTableName('messages'))->findAll($criteria)->readAll()
		);
	}

	/**
	 * Counts stored emails.
	 * @param ?string $username the user name of the emails; null for every user.
	 * @param ?string $status the TEmailStatus of the emails; null for every status.
	 * @return false|int the number of emails; false when reading the log is not permitted.
	 */
	public function countEmails(?string $username = null, ?string $status = null): false|int
	{
		if ($this->dyCountEmails(false, $username, $status) === true) {
			return false;
		}
		$this->ensureTables();
		[$condition, $parameters] = $this->logCondition($username, $status);
		return (int) $this->queryScalar('SELECT COUNT(*) FROM ' . $this->getTableName('messages') . ' WHERE ' . $condition, $parameters);
	}

	/**
	 * Deletes stored emails created before an age, with their recipients.
	 * @param int $seconds the age in seconds.
	 * @param ?string $status the TEmailStatus to delete; null deletes every status except Queued and Sending.
	 * @return false|int the number of emails deleted; false when deleting from the log is not permitted.
	 */
	public function deleteEmails(int $seconds, ?string $status = null): false|int
	{
		if ($this->dyDeleteEmails(false, $seconds, $status) === true) {
			return false;
		}
		$this->ensureTables();
		$parameters = [':time' => $this->getClock()->time() - $seconds];
		if ($status === null) {
			$condition = 'created_time <= :time AND status <> :queued AND status <> :sending';
			$parameters += [':queued' => TEmailStatus::Queued, ':sending' => TEmailStatus::Sending];
		} else {
			$condition = 'created_time <= :time AND status = :status';
			$parameters[':status'] = $status;
		}
		$messages = $this->getTableName('messages');
		$this->execute('DELETE FROM ' . $this->getTableName('recipients') . ' WHERE message_id IN (SELECT message_id FROM ' . $messages . ' WHERE ' . $condition . ')', $parameters);
		return $this->execute('DELETE FROM ' . $messages . ' WHERE ' . $condition, $parameters);
	}

	/**
	 * @param ?string $username the user name; null for every user.
	 * @param ?string $status the status; null for every status.
	 * @return array{0: string, 1: array} the SQL condition and its named parameters.
	 */
	protected function logCondition(?string $username, ?string $status): array
	{
		$conditions = ['1 = 1'];
		$parameters = [];
		if ($username !== null) {
			$conditions[] = 'username = :username';
			$parameters[':username'] = $username;
		}
		if ($status !== null) {
			$conditions[] = 'status = :status';
			$parameters[':status'] = $status;
		}
		return [implode(' AND ', $conditions), $parameters];
	}

	/**
	 * @param string $condition the SQL condition.
	 * @param array $parameters the named parameters.
	 * @return ?array the first matching message row, null when there is none.
	 */
	protected function findMessage(string $condition, array $parameters): ?array
	{
		$this->ensureTables();
		$row = $this->getTableGateway($this->getTableName('messages'))->find($condition, $parameters);
		return is_array($row) ? $row : null;
	}

	/**
	 * @param array $row the message row.
	 * @return array the recipient rows of the message.
	 */
	protected function findRecipients(array $row): array
	{
		$criteria = new TSqlCriteria('message_id = :id', [':id' => (int) $row['message_id']]);
		$criteria->setOrdersBy('recipient_id ASC');
		return $this->getTableGateway($this->getTableName('recipients'))->findAll($criteria)->readAll();
	}

	/**
	 * @param array $row the message row.
	 * @return array the decoded "data" column.
	 */
	protected function decodeData(array $row): array
	{
		$data = json_decode((string) ($row['data'] ?? ''), true);
		return is_array($data) ? $data : [];
	}

	/**
	 * Builds the info of an email from its message row.
	 * @param array $row the message row.
	 * @param bool $withRecipients whether the recipients are read.
	 * @return array the info of the email.
	 */
	protected function createInfo(array $row, bool $withRecipients = true): array
	{
		$info = array_diff_key($row, ['html_body' => true, 'text_body' => true, 'data' => true, 'claim_token' => true, 'claimed_time' => true, 'message_id' => true]);
		foreach (['attempts', 'open_count', 'click_count', 'secure', 'created_time'] as $name) {
			$info[$name] = (int) $info[$name];
		}
		foreach (['sent_time', 'opened_time', 'clicked_time', 'unsubscribed_time', 'next_attempt_time'] as $name) {
			$info[$name] = $info[$name] === null ? null : (int) $info[$name];
		}
		$info['list_name'] = (string) $info['list_name'];
		if ($withRecipients) {
			$info['recipients'] = array_map(
				fn (array $recipient) => array_diff_key($recipient, ['recipient_id' => true, 'message_id' => true, 'address_key' => true]),
				$this->findRecipients($row)
			);
		}
		return $info;
	}

	/**
	 * Rebuilds the email of a message row.
	 * @param array $row the message row.
	 * @return TEmail the final email with its stored Status.
	 */
	protected function createEmail(array $row): TEmail
	{
		$data = $this->decodeData($row);
		$email = new TEmail();
		$email->setTrackingId($row['tracking_id']);
		$email->setMessageId($row['rfc_message_id']);
		$email->setUsername($row['username']);
		$email->setList((string) $row['list_name']);
		$email->setSubject((string) $row['subject']);
		$email->setHtml($row['html_body']);
		$email->setText($row['text_body']);
		$email->setSecure((bool) $row['secure']);
		$email->setFrom($data['from'] ?? null);
		$email->setSender($data['sender'] ?? null);
		$email->setReturnPath($data['returnPath'] ?? null);
		$email->setReplyTo($data['replyTo'] ?? []);
		$email->setDate($data['date'] ?? null);
		$email->setPriority($data['priority'] ?? null);
		$email->setCharset($data['charset'] ?? 'UTF-8');
		foreach ($data['headers'] ?? [] as $name => $value) {
			$email->setHeader((string) $name, (string) $value);
		}
		$email->setFiles(array_map(fn (array $file) => TEmailFile::fromArray($file), $data['files'] ?? []));
		$email->setTrackedLinks($data['links'] ?? []);
		foreach ($this->findRecipients($row) as $recipient) {
			$address = new TEmailAddress($recipient['address'], (string) $recipient['display_name']);
			match ($recipient['kind']) {
				'cc' => $email->addCc($address),
				'bcc' => $email->addBcc($address),
				default => $email->addTo($address),
			};
		}
		$email->finalize();
		$email->setStatus($row['status']);
		$email->setError($row['error_text']);
		return $email;
	}

	/**
	 * Runs a statement that changes rows.
	 * @param string $sql the SQL with named parameters.
	 * @param array $parameters the values by parameter name.
	 * @return int the number of rows changed.
	 */
	protected function execute(string $sql, array $parameters = []): int
	{
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($parameters as $name => $value) {
			$command->bindValue($name, $value);
		}
		return (int) $command->execute();
	}

	/**
	 * @param string $sql the SQL with named parameters.
	 * @param array $parameters the values by parameter name.
	 * @return mixed the first column of the first row, false when there is no row.
	 */
	protected function queryScalar(string $sql, array $parameters = [])
	{
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($parameters as $name => $value) {
			$command->bindValue($name, $value);
		}
		return $command->queryScalar();
	}

	/**
	 * @param string $sql the SQL with named parameters.
	 * @param array $parameters the values by parameter name.
	 * @return array the rows.
	 */
	protected function queryAll(string $sql, array $parameters = []): array
	{
		$command = $this->getDbConnection()->createCommand($sql);
		foreach ($parameters as $name => $value) {
			$command->bindValue($name, $value);
		}
		return $command->query()->readAll();
	}

	/**
	 * Checks for the tables and creates them when they are missing and {@see getAutoCreateTables AutoCreateTables}.
	 * @throws TConfigurationException when the tables do not exist and are not created.
	 */
	protected function ensureTables(): void
	{
		if ($this->_tablesEnsured) {
			return;
		}
		$db = $this->getDbConnection();
		try {
			$db->createCommand('SELECT * FROM ' . $this->getTableName('suppressions') . ' WHERE 0=1')->query()->close();
		} catch (\Exception $e) {
			if (!$this->getAutoCreateTables()) {
				throw new TConfigurationException('dbemailstore_table_nonexistent', $this->getTableName('suppressions'));
			}
			$this->createTables();
		}
		$this->_tablesEnsured = true;
	}

	/**
	 * Creates the three tables and their indexes.
	 */
	protected function createTables(): void
	{
		$db = $this->getDbConnection();
		$driver = TDbDriverCapabilities::normalizeDriver($db->getDriverName());
		$id = 'INTEGER NOT NULL PRIMARY KEY';
		$text = 'TEXT';
		if ($driver === TDbDriver::DRIVER_MYSQL) {
			$id .= ' AUTO_INCREMENT';
			$text = 'MEDIUMTEXT';
		} elseif ($driver === TDbDriver::DRIVER_SQLITE) {
			$id = 'INTEGER PRIMARY KEY AUTOINCREMENT';
		} elseif ($driver === TDbDriver::DRIVER_PGSQL) {
			$id = 'SERIAL PRIMARY KEY';
		}
		$messages = $this->getTableName('messages');
		$recipients = $this->getTableName('recipients');
		$suppressions = $this->getTableName('suppressions');
		$statements = [
			'CREATE TABLE ' . $messages . ' (
				message_id ' . $id . ',
				tracking_id VARCHAR(64) NOT NULL,
				rfc_message_id VARCHAR(255) NULL,
				username VARCHAR(127) NULL,
				list_name VARCHAR(64) NOT NULL,
				subject ' . $text . ' NULL,
				html_body ' . $text . ' NULL,
				text_body ' . $text . ' NULL,
				data ' . $text . ' NULL,
				secure SMALLINT NOT NULL,
				status VARCHAR(16) NOT NULL,
				attempts INTEGER NOT NULL,
				next_attempt_time INTEGER NULL,
				claim_token VARCHAR(32) NULL,
				claimed_time INTEGER NULL,
				error_text ' . $text . ' NULL,
				created_time INTEGER NOT NULL,
				sent_time INTEGER NULL,
				open_count INTEGER NOT NULL,
				opened_time INTEGER NULL,
				click_count INTEGER NOT NULL,
				clicked_time INTEGER NULL,
				unsubscribed_time INTEGER NULL
			)',
			'CREATE UNIQUE INDEX ' . $messages . '_tracking ON ' . $messages . ' (tracking_id)',
			'CREATE INDEX ' . $messages . '_rfc ON ' . $messages . ' (rfc_message_id)',
			'CREATE INDEX ' . $messages . '_user ON ' . $messages . ' (username)',
			'CREATE INDEX ' . $messages . '_status ON ' . $messages . ' (status)',
			'CREATE TABLE ' . $recipients . ' (
				recipient_id ' . $id . ',
				message_id INTEGER NOT NULL,
				address VARCHAR(320) NOT NULL,
				address_key VARCHAR(320) NOT NULL,
				display_name VARCHAR(255) NULL,
				kind VARCHAR(3) NOT NULL,
				bounce_status VARCHAR(16) NULL,
				bounce_action VARCHAR(16) NULL,
				bounce_diagnostic ' . $text . ' NULL,
				bounced_time INTEGER NULL
			)',
			'CREATE INDEX ' . $recipients . '_message ON ' . $recipients . ' (message_id)',
			'CREATE INDEX ' . $recipients . '_address ON ' . $recipients . ' (address_key)',
			'CREATE TABLE ' . $suppressions . ' (
				suppression_id ' . $id . ',
				address_key VARCHAR(320) NOT NULL,
				list_name VARCHAR(64) NOT NULL,
				reason VARCHAR(16) NOT NULL,
				tracking_id VARCHAR(64) NULL,
				created_time INTEGER NOT NULL
			)',
			'CREATE UNIQUE INDEX ' . $suppressions . '_address ON ' . $suppressions . ' (address_key, list_name)',
		];
		foreach ($statements as $statement) {
			$db->createCommand($statement)->execute();
		}
	}

	/**
	 * @param string $name the table without the prefix: "messages", "recipients", or "suppressions".
	 * @return string the table name.
	 */
	public function getTableName(string $name): string
	{
		return $this->_tablePrefix . $name;
	}

	/**
	 * @return string the SQLite database file in the PRADO runtime path.
	 */
	protected function getSqliteDatabaseName(): ?string
	{
		return 'emails.db';
	}

	/**
	 * @return string the error message key when the ConnectionID is not a TDataSourceConfig.
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dbemailstore_connectionid_invalid';
	}

	/**
	 * @param string $property the property being changed.
	 * @throws TInvalidOperationException when the module is initialized.
	 */
	protected function assertUninitialized(string $property): void
	{
		if ($this->_storeInitialized) {
			throw new TInvalidOperationException('dbemailstore_property_unchangeable', $property);
		}
	}

	/**
	 * @return string the prefix of the table names. Defaults to 'email_'.
	 */
	public function getTablePrefix(): string
	{
		return $this->_tablePrefix;
	}

	/**
	 * @param string $value the prefix of the table names: letters, digits, and underscores.
	 * @throws TInvalidOperationException when the module is initialized.
	 * @throws TInvalidDataValueException when the prefix has other characters or starts with a digit.
	 */
	public function setTablePrefix($value)
	{
		$this->assertUninitialized('TablePrefix');
		$value = TPropertyValue::ensureString($value);
		if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)?$/', $value)) {
			throw new TInvalidDataValueException('dbemailstore_tableprefix_invalid', $value);
		}
		$this->_tablePrefix = $value;
	}

	/**
	 * @return bool whether missing tables are created. Defaults to true.
	 */
	public function getAutoCreateTables(): bool
	{
		return $this->_autoCreateTables;
	}

	/**
	 * @param bool|string $value whether missing tables are created.
	 * @throws TInvalidOperationException when the module is initialized.
	 */
	public function setAutoCreateTables($value)
	{
		$this->assertUninitialized('AutoCreateTables');
		$this->_autoCreateTables = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int the delivery attempts after which a transient failure is final. Defaults to 5.
	 */
	public function getMaxAttempts(): int
	{
		return $this->_maxAttempts;
	}

	/**
	 * @param int|string $value the delivery attempts after which a transient failure is final, at least 1; 1 turns retries off.
	 */
	public function setMaxAttempts($value)
	{
		$this->_maxAttempts = max(1, TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return int the seconds before the first retry; the delay doubles with each attempt. Defaults to 60.
	 */
	public function getRetryDelay(): int
	{
		return $this->_retryDelay;
	}

	/**
	 * @param int|string $value the seconds before the first retry.
	 */
	public function setRetryDelay($value)
	{
		$this->_retryDelay = max(0, TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return int the seconds after which the claim of a process is stale and its email returns to the queue. Defaults to 900.
	 */
	public function getClaimTimeout(): int
	{
		return $this->_claimTimeout;
	}

	/**
	 * @param int|string $value the seconds after which a claim is stale, longer than a delivery can take.
	 */
	public function setClaimTimeout($value)
	{
		$this->_claimTimeout = max(1, TPropertyValue::ensureInteger($value));
	}
}
