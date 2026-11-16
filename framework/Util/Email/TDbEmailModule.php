<?php

/**
 * TDbEmailModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email;

use Exception;
use Prado\Data\DataGateway\TSqlCriteria;
use Prado\Data\TDbDriver;
use Prado\Data\TDbDriverCapabilities;
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\TApplicationConfiguration;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\IDbModule;
use Prado\Web\THttpRequest;
use Prado\Xml\TXmlElement;

/**
 * TDbEmailModule class.
 *
 * TDbEmailModule extends {@see TEmailModule} with a database log of sent emails,
 * offline (queued) sending, open and click tracking, one-click unsubscribe, and
 * bounce recording.  The connection comes from {@see TDbPropertiesTrait}: the
 * {@see setConnectionID ConnectionID} of a TDataSourceConfig module, or an SQLite
 * database named "emails.db" in the runtime path.
 *
 * Every logged email has a public tracking ID ({@see TEmail::getTrackingId}).  The
 * {@see TDbEmailService} serves these requests by tracking ID:
 * - view: displays the email in the browser.  A {@see TEmail::getSecure Secure}
 *   email is shown only to its logged-in owner.
 * - open: returns a 1x1 GIF and counts an open ({@see UI\WebControls\TEmailTrackerImage}).
 * - click: counts a click and redirects to a link registered in the email
 *   ({@see UI\WebControls\TEmailTrackedLink}).  Only registered links redirect.
 * - unsubscribe: confirms on GET and unsubscribes on POST, including RFC 8058
 *   one-click POSTs from mail clients ({@see UI\WebControls\TEmailUnsubscribeLink}).
 *
 * The service is registered by a nested service element.  Tracking URLs are built
 * from {@see getServiceUrl ServiceUrl}, which defaults to the current request's
 * entry script and must be set when emails render without a web request (cron, shell).
 * <code>
 *   <module id="emailer" class="Prado\Util\Email\TDbEmailModule"
 *       ConnectionID="db" ServiceUrl="https://www.example.com/index.php">
 *     <service id="email" />
 *   </module>
 * </code>
 *
 * When {@see setOfflineEmail OfflineEmail} is true, {@see sendEmail} stores the email
 * as pending and {@see Cron\TDbEmailSenderTask} sends it later through
 * {@see sendPendingEmails}.
 *
 * Bounces raised through {@see TEmailModule::onBounce} are matched to the logged email
 * by Message-ID.  A permanent bounce sets the email status to "bounced".
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method bool dyGetEmailLog(bool $return, ?string $username, int $limit, int $offset)
 * @method bool dyGetEmailLogCount(bool $return, ?string $username)
 * @method bool dyClearEmailLog(bool $return, int $seconds, ?string $status)
 * @method bool dyRemoveEmailLogItem(bool $return, int $emailId)
 * @method bool dyRegisterShellAction(bool $return)
 */
class TDbEmailModule extends TEmailModule implements IDbModule, IPermissions
{
	use TApplicationClockAwareTrait;
	use TDbPropertiesTrait {
		setConnectionID as setTraitConnectionID;
	}

	/** The default ID of the email tracking service. */
	public const EMAIL_SERVICE = 'email';

	public const PERM_EMAIL_SHELL = 'email_shell';

	public const PERM_EMAIL_LOG_READ = 'email_log_read';

	public const PERM_EMAIL_LOG_DELETE = 'email_log_delete';

	/** The email is queued for sending. */
	public const STATUS_PENDING = 'pending';

	/** The email was handed to the mailer. */
	public const STATUS_SENT = 'sent';

	/** The mailer did not accept the email. */
	public const STATUS_FAILED = 'failed';

	/** A recipient permanently bounced the email. */
	public const STATUS_BOUNCED = 'bounced';

	/** @var bool whether sent emails are logged. */
	private $_logging = true;

	/** @var bool whether emails are queued instead of sent. */
	private $_offlineEmail = false;

	/** @var ?string the ID of the tracking service. */
	private $_serviceID;

	/** @var ?string the base URL of the tracking service. */
	private $_serviceUrl;

	/** @var string the email log table. */
	private $_tableName = 'emails';

	/** @var bool whether the table is created when missing. */
	private $_autoCreate = true;

	/** @var bool whether the table has been verified. */
	private $_tableEnsured = false;

	/** @var bool whether the module is initialized. */
	private $_dbInitialized = false;

	/** @var bool whether pending emails are being sent, which bypasses the queue. */
	private $_sendingPending = false;

	/**
	 * Initializes the module and registers the tracking service when a service element is configured.
	 * @param null|array|TXmlElement $config the module configuration.
	 */
	public function init($config)
	{
		$service = null;
		$properties = [];
		if (is_array($config)) {
			$service = $config['service'] ?? null;
			$properties = is_array($service) ? array_change_key_case($service) : [];
			unset($config['service']);
		} elseif ($config instanceof TXmlElement) {
			$service = $config->getElementByTagName('service');
			if ($service) {
				$properties = array_change_key_case($service->getAttributes()->toArray());
			}
		}
		if ($service !== null) {
			$this->_serviceID = $properties['id'] ?? self::EMAIL_SERVICE;
			unset($properties['id']);
		}
		parent::init($config);

		if ($this->_serviceID) {
			$properties['EmailerModule'] = $this;
			$emailServiceConfig = new TApplicationConfiguration();
			$emailServiceConfig->loadFromPhp(['services' => [$this->_serviceID => ['class' => TDbEmailService::class, 'properties' => $properties]]], $this->getApplication()->getBasePath());
			$this->getApplication()->applyConfiguration($emailServiceConfig, true);
		}

		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => Shell\TShellEmailerAction::class, 'EmailerModule' => $this]);
		}
		$this->_dbInitialized = true;
	}

	/**
	 * Listens to global events so the cron module discovers the email tasks.
	 * @return bool true.
	 */
	public function getAutoGlobalListen()
	{
		return true;
	}

	/**
	 * Supplies the email cron tasks to the cron module.
	 * @param \Prado\Util\Cron\TCronModule $cron the cron module asking for tasks.
	 * @param null $param unused.
	 * @return TCronTaskInfo[] the email task infos.
	 */
	public function fxGetCronTaskInfos($cron, $param)
	{
		return [
			new TCronTaskInfo('emailsender', Cron\TDbEmailSenderTask::class, $this->getId(), Prado::localize('Email Sender Task'), Prado::localize('Sends the queued emails of the database email module.')),
			new TCronTaskInfo('emailcleaner', Cron\TDbEmailCleanerTask::class, $this->getId(), Prado::localize('Email Log Cleaner Task'), Prado::localize('Deletes logged emails older than a time period.')),
		];
	}

	/**
	 * Implements IPermissions for the email shell and the email log.
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager the permissions manager.
	 * @return TPermissionEvent[] the permission events.
	 */
	public function getPermissions($manager)
	{
		$userIsOwnerAllowedRule = new TUserOwnerRule();
		return [
			new TPermissionEvent(static::PERM_EMAIL_SHELL, 'Activates email shell commands.', 'dyRegisterShellAction'),
			new TPermissionEvent(static::PERM_EMAIL_LOG_READ, 'Email read Db log.', ['dyGetEmailLog', 'dyGetEmailLogCount'], $userIsOwnerAllowedRule),
			new TPermissionEvent(static::PERM_EMAIL_LOG_DELETE, 'Email delete Db log.', ['dyClearEmailLog', 'dyRemoveEmailLogItem']),
		];
	}

	/**
	 * Sends or queues an email and logs it.  With {@see getOfflineEmail OfflineEmail}
	 * the email is stored as pending and reports success.
	 * @param \Prado\Util\Email\UI\TEmailPage|TEmail $email the email to send.
	 * @param ?array $customData the replacement values for "{{KEY}}" placeholders.
	 * @param bool $many whether this is one of many emails being sent.
	 * @return mixed the result of the mailer, true when queued.
	 */
	public function sendEmail($email, $customData, $many = false)
	{
		if ($email instanceof UI\TEmailPage) {
			$email = $email->getEmail();
		}
		if ($this->getOfflineEmail() && !$this->_sendingPending) {
			if ($customData !== null) {
				$email->setCustomData($customData);
			}
			$this->setMissingUsername($email);
			if (!$email->getMessageId()) {
				$email->setMessageId(bin2hex(random_bytes(16)));
			}
			$this->logEmail($email, self::STATUS_PENDING);
			return true;
		}
		$result = parent::sendEmail($email, $customData, $many);
		if ($this->getLogging() && !$this->_sendingPending) {
			$this->logEmail($email, $result ? self::STATUS_SENT : self::STATUS_FAILED);
		}
		return $result;
	}

	/**
	 * Sends the pending emails, oldest first, and records each result.
	 * @param int $limit the maximum number of emails to send; 0 sends all.
	 * @return int the number of emails sent.
	 */
	public function sendPendingEmails($limit = 0)
	{
		$count = 0;
		$this->_sendingPending = true;
		try {
			foreach ($this->getPendingEmails($limit) as $row) {
				$email = $this->createEmailFromRow($row);
				$sent = (bool) parent::sendEmail($email, $email->getCustomData(), true);
				$this->getTableGateway($this->getTableName())->update(
					['status' => $sent ? self::STATUS_SENT : self::STATUS_FAILED, 'sent_time' => $sent ? $this->getClock()->time() : null],
					'email_id = :email_id',
					[':email_id' => $row['email_id']]
				);
				$count += $sent ? 1 : 0;
			}
		} finally {
			$this->_sendingPending = false;
			$this->closeConnection();
		}
		return $count;
	}

	/**
	 * Stores an email in the log.
	 * @param TEmail $email the email to store.
	 * @param string $status the status of the email, one of the STATUS_* constants.
	 * @return mixed the ID of the new row.
	 */
	public function logEmail($email, $status)
	{
		$this->ensureTable();
		$headers = $email->getHeaders();
		unset($headers['Message-ID']);
		$now = $this->getClock()->time();
		return $this->getTableGateway($this->getTableName())->insert([
			'uid' => $email->getUID(),
			'tracking_id' => $email->getTrackingId(),
			'message_id' => $email->getMessageId(),
			'username' => $email->getUsername(),
			'recipients' => (string) $email->getTo(true),
			'subject' => (string) $email->getSubject(),
			'headers' => json_encode($headers),
			'body' => (string) $email->getBody([]),
			'text_body' => (string) $email->getTextBody([]),
			'custom_data' => json_encode($email->getCustomData()),
			'attachments' => json_encode($this->encodeAttachments($email->getAttachments() ?? [])),
			'links' => json_encode($email->getTrackedLinks()),
			'secure' => $email->getSecure() ? 1 : 0,
			'status' => $status,
			'created_time' => $now,
			'sent_time' => $status === self::STATUS_SENT ? $now : null,
			'open_count' => 0,
			'click_count' => 0,
		]);
	}

	/**
	 * Rebuilds a TEmail from a logged row.
	 * @param array $row the logged email row.
	 * @return TEmail the email.
	 */
	public function createEmailFromRow($row)
	{
		$email = new TEmail();
		$email->setEmailerModule($this);
		$email->setHeaders(json_decode($row['headers'] ?? '[]', true) ?: []);
		$email->setTo($row['recipients']);
		$email->setSubject($row['subject']);
		$email->setMessageId($row['message_id']);
		$email->setUID($row['uid']);
		$email->setTrackingId($row['tracking_id']);
		$email->setUsername($row['username']);
		$email->setBody($row['body']);
		$email->setTextBody($row['text_body']);
		$email->setCustomData(json_decode($row['custom_data'] ?? 'null', true));
		$email->setAttachments($this->decodeAttachments(json_decode($row['attachments'] ?? '[]', true) ?: []));
		$email->setTrackedLinks(json_decode($row['links'] ?? '[]', true) ?: []);
		$email->setSecure((bool) $row['secure']);
		return $email;
	}

	/**
	 * Converts attachments to a JSON-safe form.  File paths are kept; data is base64 encoded.
	 * @param array $attachments the TEmail attachments.
	 * @return array the encoded attachments.
	 */
	protected function encodeAttachments(array $attachments): array
	{
		$encoded = [];
		foreach ($attachments as $name => $attachment) {
			if (!is_array($attachment)) {
				$attachment = is_file($attachment) ? ['path' => $attachment] : ['data' => $attachment];
			}
			if (isset($attachment['data'])) {
				$attachment['data64'] = base64_encode($attachment['data']);
				unset($attachment['data']);
			}
			$encoded[$name] = $attachment;
		}
		return $encoded;
	}

	/**
	 * Restores attachments encoded by {@see encodeAttachments}.
	 * @param array $attachments the encoded attachments.
	 * @return array the TEmail attachments.
	 */
	protected function decodeAttachments(array $attachments): array
	{
		foreach ($attachments as $name => $attachment) {
			if (isset($attachment['data64'])) {
				$attachment['data'] = base64_decode($attachment['data64']);
				unset($attachment['data64']);
				$attachments[$name] = $attachment;
			}
		}
		return $attachments;
	}

	/**
	 * @param string $trackingId the public tracking ID.
	 * @return ?array the logged email row, null when not found.
	 */
	public function getEmailByTrackingId($trackingId)
	{
		return $this->findEmail('tracking_id = :id', [':id' => (string) $trackingId]);
	}

	/**
	 * @param string $uid the internal unique ID.
	 * @return ?array the logged email row, null when not found.
	 */
	public function getEmailByUID($uid)
	{
		return $this->findEmail('uid = :id', [':id' => (string) $uid]);
	}

	/**
	 * Finds a logged email by Message-ID.  Angle brackets and the domain part are ignored.
	 * @param string $messageId the Message-ID, with or without "<...@domain>".
	 * @return ?array the logged email row, null when not found.
	 */
	public function getEmailByMessageId($messageId)
	{
		$id = static::normalizeMessageId($messageId);
		return $id === '' ? null : $this->findEmail('message_id = :id', [':id' => $id]);
	}

	/**
	 * Reduces a Message-ID to the local part that TEmail stores.
	 * @param ?string $messageId the Message-ID, with or without "<...@domain>".
	 * @return string the local part of the Message-ID.
	 */
	public static function normalizeMessageId($messageId): string
	{
		$id = trim((string) $messageId, " \t<>");
		if (($at = strrpos($id, '@')) !== false) {
			$id = substr($id, 0, $at);
		}
		return $id;
	}

	/**
	 * @param string $criteria the SQL condition.
	 * @param array $parameters the named parameters.
	 * @return ?array the first matching row, null when not found.
	 */
	protected function findEmail(string $criteria, array $parameters): ?array
	{
		$this->ensureTable();
		$row = $this->getTableGateway($this->getTableName())->find($criteria, $parameters);
		return is_array($row) ? $row : null;
	}

	/**
	 * Counts the logged emails, optionally of one user.
	 * @param ?string $username the username; null counts all emails.
	 * @return false|int the number of emails, false when a behavior blocks reading the log.
	 */
	public function getEmailLogCount($username = null)
	{
		if ($this->dyGetEmailLogCount(false, $username) === true) {
			return false;
		}
		$this->ensureTable();
		return $username === null
			? $this->getTableGateway($this->getTableName())->count()
			: $this->getTableGateway($this->getTableName())->count('username = :username', [':username' => $username]);
	}

	/**
	 * Lists logged emails, newest first, optionally of one user.
	 * @param ?string $username the username; null lists all emails.
	 * @param int $limit the maximum number of rows; 0 returns all.
	 * @param int $offset the number of rows to skip.
	 * @return array|false the email rows, false when a behavior blocks reading the log.
	 */
	public function getEmailLog($username = null, $limit = 0, $offset = 0)
	{
		if ($this->dyGetEmailLog(false, $username, $limit, $offset) === true) {
			return false;
		}
		$criteria = $username === null ? new TSqlCriteria() : new TSqlCriteria('username = :username', [':username' => $username]);
		$criteria->setOrdersBy('email_id DESC');
		return $this->queryEmails($criteria, $limit, $offset);
	}

	/**
	 * Lists the pending emails, oldest first.
	 * @param int $limit the maximum number of rows; 0 returns all.
	 * @param int $offset the number of rows to skip.
	 * @return array the pending email rows.
	 */
	public function getPendingEmails($limit = 0, $offset = 0)
	{
		$criteria = new TSqlCriteria('status = :status', [':status' => self::STATUS_PENDING]);
		$criteria->setOrdersBy('email_id ASC');
		return $this->queryEmails($criteria, $limit, $offset);
	}

	/**
	 * @param TSqlCriteria $criteria the criteria.
	 * @param int $limit the maximum number of rows; 0 returns all.
	 * @param int $offset the number of rows to skip.
	 * @return array the rows.
	 */
	protected function queryEmails(TSqlCriteria $criteria, $limit, $offset): array
	{
		$this->ensureTable();
		if (($limit = (int) $limit) > 0) {
			$criteria->setLimit($limit);
		}
		if (($offset = (int) $offset) > 0) {
			$criteria->setOffset($offset);
		}
		return $this->getTableGateway($this->getTableName())->findAll($criteria)->readAll();
	}

	/**
	 * Deletes logged emails created before a time period, optionally of one status.
	 * Pending emails are kept unless their status is named.
	 * @param int $seconds the age, in seconds, of the emails to delete.
	 * @param ?string $status the status to delete; null deletes all except pending.
	 * @return false|int the number of deleted emails, false when a behavior blocks the deletion.
	 */
	public function clearEmailLog($seconds, $status = null)
	{
		if ($this->dyClearEmailLog(false, $seconds, $status) === true) {
			return false;
		}
		$this->ensureTable();
		$parameters = [':time' => $this->getClock()->time() - (int) $seconds];
		if ($status === null) {
			$criteria = 'created_time <= :time AND status <> :status';
			$parameters[':status'] = self::STATUS_PENDING;
		} else {
			$criteria = 'created_time <= :time AND status = :status';
			$parameters[':status'] = $status;
		}
		return $this->getTableGateway($this->getTableName())->deleteAll($criteria, $parameters);
	}

	/**
	 * Deletes one logged email.
	 * @param int $emailId the row ID of the email.
	 * @return false|int the number of deleted emails, false when a behavior blocks the deletion.
	 */
	public function removeEmailLogItem($emailId)
	{
		if ($this->dyRemoveEmailLogItem(false, $emailId) === true) {
			return false;
		}
		$this->ensureTable();
		return $this->getTableGateway($this->getTableName())->deleteAll('email_id = :id', [':id' => (int) $emailId]);
	}

	/**
	 * Counts an open of an email and raises {@see onOpenEmail}.
	 * @param string $trackingId the public tracking ID.
	 * @return ?array the logged email row, null when not found.
	 */
	public function recordOpen($trackingId)
	{
		if (!($row = $this->getEmailByTrackingId($trackingId))) {
			return null;
		}
		$this->getTableGateway($this->getTableName())->update(
			['open_count' => (int) $row['open_count'] + 1, 'opened_time' => $row['opened_time'] ?: $this->getClock()->time()],
			'email_id = :email_id',
			[':email_id' => $row['email_id']]
		);
		$this->onOpenEmail($row);
		return $row;
	}

	/**
	 * Counts a click of a tracked link and raises {@see onClickEmail}.
	 * @param string $trackingId the public tracking ID.
	 * @param int|string $link the link number.
	 * @return ?string the registered target URL, null when the email or link is unknown.
	 */
	public function recordClick($trackingId, $link)
	{
		if (!($row = $this->getEmailByTrackingId($trackingId)) || !is_numeric($link)) {
			return null;
		}
		$links = json_decode($row['links'] ?? '[]', true) ?: [];
		if (!isset($links[(int) $link])) {
			return null;
		}
		$url = $links[(int) $link];
		if (is_array($customData = json_decode($row['custom_data'] ?? 'null', true)) && $customData) {
			$url = preg_replace_callback('/{{\s*([^}\s]+)\s*}}/', fn ($m) => array_key_exists($m[1], $customData) ? (string) $customData[$m[1]] : $m[0], $url);
		}
		$now = $this->getClock()->time();
		$this->getTableGateway($this->getTableName())->update(
			['click_count' => (int) $row['click_count'] + 1, 'clicked_time' => $row['clicked_time'] ?: $now, 'opened_time' => $row['opened_time'] ?: $now],
			'email_id = :email_id',
			[':email_id' => $row['email_id']]
		);
		$this->onClickEmail(['email' => $row, 'url' => $url]);
		return $url;
	}

	/**
	 * Records an unsubscribe request and raises {@see onUnsubscribe}.  The application
	 * handles the event to remove the recipient from its mailing lists.
	 * @param string $trackingId the public tracking ID.
	 * @return ?array the logged email row, null when not found.
	 */
	public function recordUnsubscribe($trackingId)
	{
		if (!($row = $this->getEmailByTrackingId($trackingId))) {
			return null;
		}
		if (!$row['unsubscribed_time']) {
			$this->getTableGateway($this->getTableName())->update(
				['unsubscribed_time' => $this->getClock()->time()],
				'email_id = :email_id',
				[':email_id' => $row['email_id']]
			);
		}
		$this->onUnsubscribe($row);
		return $row;
	}

	/**
	 * Records the bounce on the logged email with the same Message-ID, then raises the event.
	 * @param TEmailBounce $bounce the bounce of one recipient.
	 * @return array results of the event handlers.
	 */
	public function onBounce($bounce)
	{
		if ($row = $this->getEmailByMessageId($bounce->getOriginalMessageId())) {
			$data = [
				'bounce_status' => $bounce->getStatus(),
				'bounce_diagnostic' => $bounce->getDiagnosticCode(),
				'bounced_time' => $this->getClock()->time(),
			];
			if ($bounce->getIsPermanent()) {
				$data['status'] = self::STATUS_BOUNCED;
			}
			$this->getTableGateway($this->getTableName())->update($data, 'email_id = :email_id', [':email_id' => $row['email_id']]);
		}
		return parent::onBounce($bounce);
	}

	/**
	 * Raised when a tracker image of an email loads.
	 * @param array $row the logged email row.
	 * @return array results of the event handlers.
	 */
	public function onOpenEmail($row)
	{
		return $this->raiseEvent('onOpenEmail', $this, $row);
	}

	/**
	 * Raised when a tracked link of an email is clicked.
	 * @param array $param ['email' => the logged email row, 'url' => the target URL].
	 * @return array results of the event handlers.
	 */
	public function onClickEmail($param)
	{
		return $this->raiseEvent('onClickEmail', $this, $param);
	}

	/**
	 * Raised when a recipient unsubscribes through an email.
	 * @param array $row the logged email row.
	 * @return array results of the event handlers.
	 */
	public function onUnsubscribe($row)
	{
		return $this->raiseEvent('onUnsubscribe', $this, $row);
	}

	/**
	 * Builds a URL of the tracking service for an email.
	 * @param string|TEmail $email the email or its tracking ID.
	 * @param array $parameters the query parameters, e.g. ['click' => 2].
	 * @throws TConfigurationException when no service is configured or no service URL is known.
	 * @return string the absolute URL.
	 */
	public function getTrackingUrl($email, array $parameters = [])
	{
		if (!$this->getServiceID()) {
			throw new TConfigurationException('dbemail_service_required');
		}
		$trackingId = $email instanceof TEmail ? $email->getTrackingId() : (string) $email;
		$base = $this->getServiceUrl();
		$query = http_build_query([$this->getServiceID() => $trackingId] + $parameters, '', '&', PHP_QUERY_RFC3986);
		return $base . (str_contains($base, '?') ? '&' : '?') . $query;
	}

	/**
	 * Returns the base URL of the tracking service.  It defaults to the absolute URL of
	 * the entry script of the current web request.
	 * @throws TConfigurationException when not set and there is no web request.
	 * @return string the base URL of the tracking service.
	 */
	public function getServiceUrl()
	{
		if ($this->_serviceUrl === null) {
			$request = $this->getApplication()->getRequest();
			if (!($request instanceof THttpRequest) || !$request->getUrl()) {
				throw new TConfigurationException('dbemail_serviceurl_required');
			}
			return $request->getBaseUrl() . $request->getApplicationUrl();
		}
		return $this->_serviceUrl;
	}

	/**
	 * @param ?string $url the absolute URL of the application entry script, e.g. "https://www.example.com/index.php".
	 */
	public function setServiceUrl($url)
	{
		$this->_serviceUrl = ($url === null || $url === '') ? null : TPropertyValue::ensureString($url);
	}

	/**
	 * Checks for the table and creates it when missing and {@see getAutoCreateEmailTable AutoCreateEmailTable}.
	 * @throws TConfigurationException when the table does not exist and is not created.
	 */
	protected function ensureTable()
	{
		if ($this->_tableEnsured) {
			return;
		}
		$db = $this->getDbConnection();
		try {
			$db->createCommand('SELECT * FROM ' . $this->getTableName() . ' WHERE 0=1')->query()->close();
		} catch (Exception $e) {
			if (!$this->getAutoCreateEmailTable()) {
				throw new TConfigurationException('dbemail_table_nonexistent', $this->getTableName());
			}
			$this->createDbTable();
		}
		$this->_tableEnsured = true;
	}

	/**
	 * Creates the email log table and its indexes.
	 */
	protected function createDbTable()
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
		$table = $this->getTableName();
		$db->createCommand('CREATE TABLE ' . $table . ' (
			email_id ' . $id . ',
			uid VARCHAR(64) NOT NULL,
			tracking_id VARCHAR(64) NOT NULL,
			message_id VARCHAR(255) NULL,
			username VARCHAR(127) NULL,
			recipients ' . $text . ' NULL,
			subject VARCHAR(255) NULL,
			headers ' . $text . ' NULL,
			body ' . $text . ' NULL,
			text_body ' . $text . ' NULL,
			custom_data ' . $text . ' NULL,
			attachments ' . $text . ' NULL,
			links ' . $text . ' NULL,
			secure SMALLINT NOT NULL DEFAULT 0,
			status VARCHAR(16) NOT NULL,
			created_time INTEGER NOT NULL,
			sent_time INTEGER NULL,
			open_count INTEGER NOT NULL DEFAULT 0,
			opened_time INTEGER NULL,
			click_count INTEGER NOT NULL DEFAULT 0,
			clicked_time INTEGER NULL,
			bounce_status VARCHAR(16) NULL,
			bounce_diagnostic ' . $text . ' NULL,
			bounced_time INTEGER NULL,
			unsubscribed_time INTEGER NULL
			)')->execute();
		$prefix = preg_replace('/\W+/', '_', $table);
		foreach (['tracking_id', 'message_id', 'username', 'status'] as $column) {
			$db->createCommand('CREATE INDEX ' . $prefix . '_' . $column . ' ON ' . $table . ' (' . $column . ')')->execute();
		}
	}

	/**
	 * @return string the SQLite database filename within the PRADO runtime path.
	 */
	protected function getSqliteDatabaseName(): string
	{
		return 'emails.db';
	}

	/**
	 * @return string the error message key when the ConnectionID is not a TDataSourceConfig.
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dbemail_connectionid_invalid';
	}

	/**
	 * @param string $value the ID of a TDataSourceConfig module.
	 * @throws TInvalidOperationException when the module is already initialized.
	 */
	public function setConnectionID($value)
	{
		$this->assertUninitialized('ConnectionID');
		$this->setTraitConnectionID($value);
	}

	/**
	 * @param string $property the property being changed.
	 * @throws TInvalidOperationException when the module is already initialized.
	 */
	protected function assertUninitialized(string $property): void
	{
		if ($this->_dbInitialized) {
			throw new TInvalidOperationException('dbemail_property_unchangeable', $property);
		}
	}

	/**
	 * @return bool whether sent emails are logged. Defaults to true.
	 */
	public function getLogging()
	{
		return $this->_logging;
	}

	/**
	 * @param bool $logging whether sent emails are logged.
	 */
	public function setLogging($logging)
	{
		$this->_logging = TPropertyValue::ensureBoolean($logging);
	}

	/**
	 * @return bool whether emails are queued instead of sent. Defaults to false.
	 */
	public function getOfflineEmail()
	{
		return $this->_offlineEmail;
	}

	/**
	 * @param bool $offline whether emails are queued instead of sent.
	 */
	public function setOfflineEmail($offline)
	{
		$this->_offlineEmail = TPropertyValue::ensureBoolean($offline);
	}

	/**
	 * @return ?string the ID of the tracking service, null when no service is configured.
	 */
	public function getServiceID()
	{
		return $this->_serviceID;
	}

	/**
	 * @param ?string $serviceID the ID of the tracking service.
	 */
	public function setServiceID($serviceID)
	{
		$this->_serviceID = ($serviceID === null || $serviceID === '') ? null : TPropertyValue::ensureString($serviceID);
	}

	/**
	 * @return string the email log table. Defaults to 'emails'.
	 */
	public function getTableName()
	{
		return $this->_tableName;
	}

	/**
	 * @param string $table the email log table, an SQL identifier.
	 * @throws TInvalidOperationException when the module is already initialized.
	 * @throws \Prado\Exceptions\TInvalidDataValueException when the name is not an SQL identifier.
	 */
	public function setTableName($table)
	{
		$this->assertUninitialized('TableName');
		$this->_tableName = $this->ensureSqlIdentifier(TPropertyValue::ensureString($table), 'TableName');
	}

	/**
	 * @return bool whether the email table is created when missing. Defaults to true.
	 */
	public function getAutoCreateEmailTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool $value whether the email table is created when missing.
	 * @throws TInvalidOperationException when the module is already initialized.
	 */
	public function setAutoCreateEmailTable($value)
	{
		$this->assertUninitialized('AutoCreateEmailTable');
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}
}
