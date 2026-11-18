<?php

/**
 * TEmailModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Email\Store\IEmailStore;
use Prado\Email\Transport\IEmailTransport;
use Prado\Email\Transport\TEmailTransportException;
use Prado\Email\Transport\TMailTransport;
use Prado\Email\UI\TEmailBody;
use Prado\Email\UI\TEmailBodyTemplate;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Xml\TXmlElement;

/**
 * TEmailModule class.
 *
 * TEmailModule creates and sends the emails of an application.
 *
 * <code>
 * <module id="email" class="Prado\Email\TEmailModule" From="Shop <shop@example.com>">
 *   <transport class="Prado\Email\Transport\TSmtpTransport" Dsn="smtp://user:password@smtp.example.com" />
 *   <emails MasterClass="Application.Layouts.EmailLayout">
 *     <email id="Help/*" Email.From="help@example.com" />
 *   </emails>
 * </module>
 * </code>
 *
 * {@see createEmail} builds a {@see TEmail} from the files of an email path in the
 * {@see getBasePath BasePath}, which is "Emails" in the application directory:
 *
 * | File | Role |
 * |---|---|
 * | `Order/Shipped.email` | the template of the HTML |
 * | `Order/Shipped.php` | the {@see TEmailBody} class of the template, optional |
 * | `Order/Shipped.text` | the plain text, optional |
 *
 * <code>
 * $email = $app->createEmail('Order.Shipped');
 * $email->setTo($user->Email);
 * $email->getBody()->setOrder($order);
 * $email->send();
 * </code>
 *
 * {@see sendEmail} takes an email through these steps:
 * 1. The Body renders.
 * 2. The module {@see getFrom From}, {@see getReplyTo ReplyTo}, and
 *    {@see getReturnPath ReturnPath} fill the ones the email lacks.
 * 3. With a store, suppressed recipients are removed; an email left without a
 *    recipient ends as TEmailStatus::Suppressed.
 * 4. The module {@see getCc Cc} and {@see getBcc Bcc} are added.
 * 5. The placeholders are replaced and the Message-ID is assigned.
 * 6. onSendEmail is raised; a handler may change or cancel the email.
 * 7. The email is delivered, deferred, or queued by the {@see getSendMode SendMode}.
 * 8. onEmailSent or onEmailFailed is raised after a delivery attempt.
 *
 * | SendMode | First attempt | With a store, when the attempt fails or never happens |
 * |---|---|---|
 * | Immediate | inside sendEmail | a transient failure queues the email for a retry |
 * | Deferred | {@see sendDeferred}, at the end of the request | the email is stored as queued when sent, so it stays for the queue |
 * | Queued | {@see sendQueued}, from cron | retried up to the attempts of the store |
 *
 * A {@see setStoreID store} logs every email and is required for Queued.
 * {@see sendEach} sends one template to many recipients, each as its own email.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailModule extends \Prado\TModule implements IEmailMailer
{
	use TApplicationClockAwareTrait;

	/** The directory of the emails under the application base path. */
	public const DEFAULT_BASE_PATH = 'Emails';

	/** The file extension of an email template. */
	public const EMAIL_FILE_EXT = '.email';

	/** The file extension of the plain text of an email. */
	public const TEXT_FILE_EXT = '.text';

	/** The namespace of the body classes of the emails. */
	public const EMAIL_NAMESPACE_PREFIX = 'Application\\Emails\\';

	/** The name of the behavior that the module attaches to the application. */
	public const BEHAVIOR_NAME = 'email';

	/** @var ?string the directory of the emails. */
	private ?string $_basePath = null;

	/** @var string the body class of an email that has only a template. */
	private string $_bodyClass = TEmailBody::class;

	/** @var ?TEmailConfiguration the initial property values of the email bodies. */
	private ?TEmailConfiguration $_configuration = null;

	/** @var ?IEmailTransport the transport that delivers the emails. */
	private ?IEmailTransport $_transport = null;

	/** @var null|IEmailStore|string the store, or the ID of its module. */
	private null|IEmailStore|string $_store = null;

	/** @var string the TEmailSendMode. */
	private string $_sendMode = TEmailSendMode::Immediate;

	/** @var ?TEmailAddress the From of an email that has none. */
	private ?TEmailAddress $_from = null;

	/** @var ?TEmailAddress the Return-Path of an email that has none. */
	private ?TEmailAddress $_returnPath = null;

	/** @var TEmailAddress[] the Reply-To of an email that has none. */
	private array $_replyTo = [];

	/** @var TEmailAddress[] the addresses added to the Cc of every email. */
	private array $_cc = [];

	/** @var TEmailAddress[] the addresses added to the Bcc of every email. */
	private array $_bcc = [];

	/** @var string the domain of the Message-IDs. */
	private string $_messageIdDomain = '';

	/** @var TEmail[] the emails waiting for the end of the request. */
	private array $_deferred = [];

	/** @var bool whether the module is initialized. */
	private bool $_initialized = false;

	/**
	 * Initializes the module: loads the `<transport>` and `<emails>` elements and
	 * attaches {@see TEmailApplicationBehavior} to the application.
	 * @param null|array|TXmlElement $config the module configuration.
	 * @throws TConfigurationException when the transport configuration is invalid.
	 */
	public function init($config)
	{
		$this->loadConfiguration($config);
		if (($app = $this->getApplication()) !== null) {
			if ($app->asa(self::BEHAVIOR_NAME) === null) {
				$behavior = new TEmailApplicationBehavior();
				$behavior->setModule($this);
				$app->attachBehavior(self::BEHAVIOR_NAME, $behavior);
			}
			$app->attachEventHandler('onEndRequest', [$this, 'endRequest']);
		}
		$this->_initialized = true;
		parent::init($config);
	}

	/**
	 * Loads the transport and the email property rules from the module configuration.
	 * @param null|array|TXmlElement $config the module configuration.
	 * @throws TConfigurationException when there is more than one transport, or the transport has no class or is not an IEmailTransport component.
	 */
	protected function loadConfiguration($config): void
	{
		$transports = [];
		$emails = null;
		if ($config instanceof TXmlElement) {
			foreach ($config->getElementsByTagName('transport') as $element) {
				$properties = $element->getAttributes()->toArray();
				$transports[] = [$properties['class'] ?? null, array_diff_key($properties, ['class' => true]), $element];
			}
			$emails = $config->getElementByTagName('emails');
		} elseif (is_array($config)) {
			if (isset($config['transport'])) {
				$transports[] = [$config['transport']['class'] ?? null, $config['transport']['properties'] ?? [], $config['transport']];
			}
			$emails = $config['emails'] ?? null;
		}
		if (count($transports) > 1) {
			throw new TConfigurationException('emailmodule_transport_single');
		}
		foreach ($transports as [$class, $properties, $transportConfig]) {
			if ($class === null) {
				throw new TConfigurationException('emailmodule_transportclass_required');
			}
			$transport = Prado::createComponent($class);
			if (!($transport instanceof IEmailTransport) || !($transport instanceof TComponent)) {
				throw new TConfigurationException('emailmodule_transport_invalid', $class);
			}
			$transport->setSubProperties($properties);
			if (method_exists($transport, 'init')) {
				$transport->init($transportConfig);
			}
			$this->setTransport($transport);
		}
		$this->getConfiguration()->load($emails);
	}

	/**
	 * Creates the email of an email path.  The properties of the `<emails>` rules
	 * and then the "Email." directives of the template are applied to it.
	 * @param string $emailPath the email path in the BasePath, e.g. "Order.Shipped".
	 * @param ?string $culture the culture of the template and text; null for the current culture.
	 * @throws TConfigurationException when the email has no template and no class, or its class is not a TEmailBody.
	 * @return TEmail the email, with a {@see TEmailBody} as its Body.
	 */
	public function createEmail($emailPath, $culture = null): TEmail
	{
		$emailPath = TPropertyValue::ensureString($emailPath);
		if (!preg_match('/^\w+([.\/]\w+)*$/', $emailPath)) {
			throw new TConfigurationException('emailmodule_email_unknown', $emailPath);
		}
		$emailPath = strtr($emailPath, '/', '.');
		$path = $this->findEmailPath($emailPath);
		$className = $this->getBodyClass();
		if (is_file($path . Prado::CLASS_FILE_EXT)) {
			$className = basename($path);
			$namespaced = static::EMAIL_NAMESPACE_PREFIX . str_replace('.', '\\', $emailPath);
			if (!class_exists($className, false) && !class_exists($namespaced, false)) {
				include_once($path . Prado::CLASS_FILE_EXT);
			}
			if (!class_exists($className, false)) {
				$className = $namespaced;
			}
		}
		if (!is_a($className, TEmailBody::class, true)) {
			throw new TConfigurationException('emailmodule_bodyclass_invalid', $emailPath, $className);
		}

		$email = new TEmail();
		$email->setMailer($this);
		$body = Prado::createComponent($className);
		$body->setEmailModule($this);
		$body->setEmailPath($emailPath);
		$body->setEmail($email);
		$email->setBody($body);
		$body->setSubProperties($this->getConfiguration()->getEmailProperties($emailPath));
		if (is_file($path . self::EMAIL_FILE_EXT)) {
			$template = $this->getApplication()->getTemplateManager()->getTemplateByFileName($path . self::EMAIL_FILE_EXT, TEmailBodyTemplate::class, $culture);
			$body->setTemplate($template);
			if ($template instanceof TEmailBodyTemplate) {
				$email->setSubProperties($template->getEmailDirective());
			}
		}
		if (($textFile = $this->getLocalizedFile($path . self::TEXT_FILE_EXT, $culture)) !== null) {
			$body->setTextFile($textFile);
		}
		return $email;
	}

	/**
	 * Finds the files of an email path: in the BasePath, then in the paths that the
	 * onAdditionalEmailPaths handlers return.
	 * @param string $emailPath the email path, with "." between its segments.
	 * @throws TConfigurationException when no path has a template or a class.
	 * @return string the path of the email files without an extension.
	 */
	protected function findEmailPath(string $emailPath): string
	{
		$relative = strtr($emailPath, '.', DIRECTORY_SEPARATOR);
		$candidates = [$this->getBasePath() . DIRECTORY_SEPARATOR . $relative];
		foreach ($this->onAdditionalEmailPaths($emailPath) as $candidate) {
			if (is_string($candidate) && $candidate !== '') {
				$candidates[] = $candidate;
			}
		}
		foreach ($candidates as $path) {
			if (is_file($path . self::EMAIL_FILE_EXT) || is_file($path . Prado::CLASS_FILE_EXT)) {
				return $path;
			}
		}
		throw new TConfigurationException('emailmodule_email_unknown', $emailPath);
	}

	/**
	 * Finds the localized variant of a file.
	 * @param string $file the path of the file.
	 * @param ?string $culture the culture; null for the current culture.
	 * @return ?string the path of the most specific variant that exists, null when none does.
	 */
	protected function getLocalizedFile(string $file, ?string $culture): ?string
	{
		$globalization = $this->getApplication()?->getGlobalization(false);
		$variants = $globalization === null ? [$file] : $globalization->getLocalizedResource($file, $culture);
		foreach ($variants as $variant) {
			if (is_file($variant)) {
				return $variant;
			}
		}
		return null;
	}

	/**
	 * Raised when an email is created, to add places that hold email files, such as
	 * the directory of a plugin.  A handler returns the path of the email files
	 * without an extension, e.g. "/plugin/Emails/Order/Shipped".
	 * @param string $emailPath the email path, with "." between its segments.
	 * @return array the handler results.
	 */
	public function onAdditionalEmailPaths($emailPath)
	{
		return $this->raiseEvent('onAdditionalEmailPaths', $this, $emailPath);
	}

	/**
	 * Sends an email: delivers it, defers it to the end of the request, or queues it,
	 * by the {@see getSendMode SendMode}.
	 * @param TEmail $email the email.
	 * @throws TInvalidOperationException when the email was sent before; clone it to send it again.
	 * @throws TConfigurationException when the email has no From or no recipient, the SendMode is Queued without a store, or a list email with an unsubscribe header has more than one recipient.
	 * @return string the {@see TEmailStatus} of the email after the call.
	 */
	public function sendEmail(TEmail $email): string
	{
		if ($email->getStatus() !== TEmailStatus::New) {
			throw new TInvalidOperationException('emailmodule_email_resent', $email->getStatus());
		}
		$email->setMailer($this);
		$email->render();
		$this->applyDefaults($email);
		$store = $this->getStore();
		$deliverable = $store === null || $this->applySuppression($email, $store);
		if ($deliverable) {
			$email->setCc(array_merge($email->getCc(), $this->getCc()));
			$email->setBcc(array_merge($email->getBcc(), $this->getBcc()));
		}
		$email->finalize();
		if ($email->getMessageId() === null) {
			$email->setMessageId(bin2hex(random_bytes(16)) . '@' . ($this->getMessageIdDomain() ?: $email->getFrom()->getDomain()));
		}
		if (!$deliverable) {
			$email->setStatus(TEmailStatus::Suppressed);
			$store->addEmail($email, TEmailStatus::Suppressed);
			return TEmailStatus::Suppressed;
		}
		$this->ensureUnsubscribable($email);

		$param = new TEmailEventParameter($email);
		$this->onSendEmail($param);
		if ($param->getIsCancelled()) {
			$email->setStatus(TEmailStatus::Cancelled);
			return TEmailStatus::Cancelled;
		}

		$mode = $this->getSendMode();
		if ($mode === TEmailSendMode::Immediate) {
			$store?->addEmail($email, TEmailStatus::Sending);
			$email->setStatus(TEmailStatus::Sending);
			return $this->deliver($email);
		}
		if ($store === null && $mode === TEmailSendMode::Queued) {
			throw new TConfigurationException('emailmodule_store_required');
		}
		$store?->addEmail($email, TEmailStatus::Queued);
		$email->setStatus(TEmailStatus::Queued);
		if ($mode === TEmailSendMode::Deferred) {
			$this->_deferred[] = $email;
		} else {
			$this->signalQueue();
		}
		return TEmailStatus::Queued;
	}

	/**
	 * Sends one email to many recipients, each as its own email with its own
	 * TrackingId, Message-ID, and placeholder values.  The Body renders once.
	 * @param TEmail $template the email to copy; it is rendered and is not sent itself.
	 * @param iterable $recipients the recipients: an address as the key and its Data array as the value, or addresses alone.
	 * @throws TInvalidOperationException when the template is final or was sent.
	 * @return int the number of emails that ended Sent or Queued.
	 */
	public function sendEach(TEmail $template, iterable $recipients): int
	{
		if ($template->getIsFinal() || $template->getStatus() !== TEmailStatus::New) {
			throw new TInvalidOperationException('emailmodule_template_final');
		}
		$template->setMailer($this);
		$template->render();
		$count = 0;
		foreach ($recipients as $key => $value) {
			$email = clone $template;
			if (is_int($key)) {
				$email->setTo([$value]);
			} else {
				$email->setTo([$key]);
				$email->setData((is_array($value) ? $value : []) + $template->getData());
			}
			$status = $this->sendEmail($email);
			if ($status === TEmailStatus::Sent || $status === TEmailStatus::Queued) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Delivers the emails that the Deferred SendMode holds.  The application calls
	 * it at the end of the request.
	 * @return int the number of emails sent.
	 */
	public function sendDeferred(): int
	{
		$emails = $this->_deferred;
		$this->_deferred = [];
		$store = $this->getStore();
		$count = 0;
		foreach ($emails as $email) {
			try {
				if ($store !== null && !$store->claimEmail($email)) {
					continue;
				}
				$email->setStatus(TEmailStatus::Sending);
				$count += $this->deliver($email) === TEmailStatus::Sent ? 1 : 0;
			} catch (\Throwable $e) {
				Prado::error('Deferred email to ' . TEmailAddress::formatList($email->getTo()) . ' failed: ' . $e->getMessage(), static::class);
			}
		}
		return $count;
	}

	/**
	 * Delivers queued emails of the store that are due.  A cron task calls it.
	 * @param int $limit the most emails to deliver; 0 delivers all that are due.
	 * @throws TConfigurationException when the module has no store.
	 * @return int the number of emails sent.
	 */
	public function sendQueued(int $limit = 0): int
	{
		if (($store = $this->getStore()) === null) {
			throw new TConfigurationException('emailmodule_store_required');
		}
		$count = 0;
		foreach ($store->claimEmails($limit) as $email) {
			$email->setMailer($this);
			$email->setStatus(TEmailStatus::Sending);
			try {
				$count += $this->deliver($email) === TEmailStatus::Sent ? 1 : 0;
			} catch (\Throwable $e) {
				Prado::error('Queued email to ' . TEmailAddress::formatList($email->getTo()) . ' failed: ' . $e->getMessage(), static::class);
			}
		}
		return $count;
	}

	/**
	 * Hands a final email to the transport and records the result.
	 * @param TEmail $email the final email, claimed in the store when there is one.
	 * @throws \Throwable when the delivery fails for a reason other than the transport refusing the email.
	 * @return string TEmailStatus::Sent, TEmailStatus::Queued for a retry, or TEmailStatus::Failed.
	 */
	protected function deliver(TEmail $email): string
	{
		$store = $this->getStore();
		try {
			$this->getTransport()->send($email);
		} catch (\Throwable $e) {
			$refused = $e instanceof TEmailTransportException;
			$email->setError($e->getMessage());
			$status = $store === null ? TEmailStatus::Failed : $store->markFailed($email, $e->getMessage(), $refused && $e->getIsTransient());
			$email->setStatus($status);
			$this->onEmailFailed(new TEmailEventParameter($email, $e));
			if ($status === TEmailStatus::Queued) {
				$this->signalQueue();
			}
			if (!$refused) {
				throw $e;
			}
			Prado::warning('Email to ' . TEmailAddress::formatList($email->getTo()) . ' was not delivered: ' . $e->getMessage(), static::class);
			return $status;
		}
		$email->setStatus(TEmailStatus::Sent);
		$email->setError(null);
		$store?->markSent($email);
		$this->onEmailSent(new TEmailEventParameter($email));
		return TEmailStatus::Sent;
	}

	/**
	 * Fills the From, ReplyTo, ReturnPath, Username, and Date that the email lacks.
	 * @param TEmail $email the email.
	 * @throws TConfigurationException when the email has no From or no recipient.
	 */
	protected function applyDefaults(TEmail $email): void
	{
		if ($email->getFrom() === null) {
			$email->setFrom($this->getFrom());
		}
		if (!$email->getReplyTo()) {
			$email->setReplyTo($this->getReplyTo());
		}
		if ($email->getReturnPath() === null) {
			$email->setReturnPath($this->getReturnPath());
		}
		if ($email->getFrom() === null) {
			throw new TConfigurationException('emailmodule_from_required');
		}
		if (!$email->getRecipients()) {
			throw new TConfigurationException('emailmodule_recipient_required');
		}
		if ($email->getUsername() === null && ($user = $this->getApplication()?->getUser()) !== null && !$user->getIsGuest()) {
			$email->setUsername($user->getName());
		}
		if ($email->getDate() === null) {
			$email->setDate($this->getClock()->time());
		}
	}

	/**
	 * Removes the recipients that the store suppresses for the list of the email.
	 * @param TEmail $email the email.
	 * @param IEmailStore $store the store.
	 * @return bool whether a To or Cc recipient remains.
	 */
	protected function applySuppression(TEmail $email, IEmailStore $store): bool
	{
		$suppressed = $store->getSuppressed(array_map(fn (TEmailAddress $address) => $address->getAddress(), $email->getRecipients()), $email->getList());
		if ($suppressed) {
			$keep = fn (TEmailAddress $address) => !in_array(strtolower($address->getAddress()), $suppressed, true);
			$email->setTo(array_values(array_filter($email->getTo(), $keep)));
			$email->setCc(array_values(array_filter($email->getCc(), $keep)));
			$email->setBcc(array_values(array_filter($email->getBcc(), $keep)));
		}
		return (bool) ($email->getTo() || $email->getCc());
	}

	/**
	 * Checks that an unsubscribe of a list email names one recipient.
	 * @param TEmail $email the email.
	 * @throws TConfigurationException when a list email with a List-Unsubscribe header has several To addresses or a Cc.
	 */
	protected function ensureUnsubscribable(TEmail $email): void
	{
		if ($email->getList() !== '' && $email->getHeader('List-Unsubscribe') !== null && (count($email->getTo()) !== 1 || $email->getCc())) {
			throw new TConfigurationException('emailmodule_unsubscribe_single_recipient', $email->getList());
		}
	}

	/**
	 * Called when an email is left in the queue of the store.  It does nothing;
	 * the queued emails wait for the next {@see sendQueued}.
	 */
	protected function signalQueue(): void
	{
	}

	/**
	 * Raised before an email is delivered, deferred, or queued.  The email is final.
	 * A handler may change it, or cancel it with {@see TEmailEventParameter::cancel}.
	 * @param TEmailEventParameter $param the parameter with the email.
	 */
	public function onSendEmail($param)
	{
		$this->raiseEvent('onSendEmail', $this, $param);
	}

	/**
	 * Raised when a transport accepted an email.
	 * @param TEmailEventParameter $param the parameter with the email.
	 */
	public function onEmailSent($param)
	{
		$this->raiseEvent('onEmailSent', $this, $param);
	}

	/**
	 * Raised when a delivery attempt failed.  The Status of the email is Queued when
	 * it will be tried again and Failed otherwise.
	 * @param TEmailEventParameter $param the parameter with the email and the exception.
	 */
	public function onEmailFailed($param)
	{
		$this->raiseEvent('onEmailFailed', $this, $param);
	}

	/**
	 * Ends the request: delivers the deferred emails and closes the transport.
	 * @param mixed $sender the application.
	 * @param mixed $param the event parameter.
	 */
	public function endRequest($sender = null, $param = null): void
	{
		$this->sendDeferred();
		$this->close();
	}

	/**
	 * Closes the connection that the transport keeps open between emails.
	 */
	public function close(): void
	{
		$this->_transport?->close();
	}

	/**
	 * @return TEmailConfiguration the initial property values of the email bodies.
	 */
	public function getConfiguration(): TEmailConfiguration
	{
		return $this->_configuration ??= new TEmailConfiguration();
	}

	/**
	 * @return IEmailTransport the transport that delivers the emails. Defaults to a {@see TMailTransport}.
	 */
	public function getTransport(): IEmailTransport
	{
		return $this->_transport ??= new TMailTransport();
	}

	/**
	 * @param IEmailTransport $transport the transport that delivers the emails.
	 */
	public function setTransport(IEmailTransport $transport)
	{
		$this->_transport = $transport;
	}

	/**
	 * @throws TConfigurationException when the StoreID is not the ID of an IEmailStore module.
	 * @return ?IEmailStore the store, null when the module has none.
	 */
	public function getStore(): ?IEmailStore
	{
		if (is_string($this->_store)) {
			$store = $this->getApplication()?->getModule($this->_store);
			if (!($store instanceof IEmailStore)) {
				throw new TConfigurationException('emailmodule_storeid_invalid', $this->_store);
			}
			$this->_store = $store;
		}
		return $this->_store;
	}

	/**
	 * @param ?IEmailStore $store the store; null for none.
	 */
	public function setStore(?IEmailStore $store)
	{
		$this->_store = $store;
	}

	/**
	 * @return string the ID of the store module, empty when the module has no store or the store has no ID.
	 */
	public function getStoreID(): string
	{
		if (is_string($this->_store)) {
			return $this->_store;
		}
		return ($this->_store instanceof \Prado\IModule) ? (string) $this->_store->getID() : '';
	}

	/**
	 * @param string $value the ID of an {@see IEmailStore} module; empty for none.
	 */
	public function setStoreID($value)
	{
		$value = TPropertyValue::ensureString($value);
		$this->_store = $value === '' ? null : $value;
	}

	/**
	 * @return string the {@see TEmailSendMode}. Defaults to TEmailSendMode::Immediate.
	 */
	public function getSendMode(): string
	{
		return $this->_sendMode;
	}

	/**
	 * @param string $value the {@see TEmailSendMode}.
	 */
	public function setSendMode($value)
	{
		$this->_sendMode = TPropertyValue::ensureEnum($value, TEmailSendMode::class);
	}

	/**
	 * @throws TConfigurationException when the default directory does not exist.
	 * @return string the directory of the emails. Defaults to "Emails" in the application base path.
	 */
	public function getBasePath(): string
	{
		if ($this->_basePath === null) {
			$path = $this->getApplication()->getBasePath() . DIRECTORY_SEPARATOR . self::DEFAULT_BASE_PATH;
			if (($this->_basePath = (string) realpath($path)) === '' || !is_dir($this->_basePath)) {
				$this->_basePath = null;
				throw new TConfigurationException('emailmodule_basepath_invalid', $path);
			}
		}
		return $this->_basePath;
	}

	/**
	 * @param string $value the directory of the emails: a path, or a namespace such as "Application.Mail".
	 * @throws TInvalidOperationException when the module is initialized.
	 * @throws TConfigurationException when the directory does not exist.
	 */
	public function setBasePath($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('emailmodule_basepath_unchangeable');
		}
		$value = TPropertyValue::ensureString($value);
		$path = (str_contains($value, '/') || str_contains($value, '\\')) ? $value : Prado::getPathOfNamespace($value);
		if ($path === null || ($real = realpath($path)) === false || !is_dir($real)) {
			throw new TConfigurationException('emailmodule_basepath_invalid', $value);
		}
		$this->_basePath = $real;
	}

	/**
	 * @return string the body class of an email that has a template and no class file. Defaults to {@see TEmailBody}.
	 */
	public function getBodyClass(): string
	{
		return $this->_bodyClass;
	}

	/**
	 * @param string $value the body class of an email that has a template and no class file, a {@see TEmailBody} class in namespace format.
	 */
	public function setBodyClass($value)
	{
		$this->_bodyClass = Prado::usingClass(TPropertyValue::ensureString($value)) ?: $value;
	}

	/**
	 * @return ?TEmailAddress the From of an email that has none.
	 */
	public function getFrom(): ?TEmailAddress
	{
		return $this->_from;
	}

	/**
	 * @param null|array|string|TEmailAddress $address the From of an email that has none.
	 */
	public function setFrom($address)
	{
		$this->_from = ($address === null || $address === '') ? null : TEmailAddress::from($address);
	}

	/**
	 * @return ?TEmailAddress the Return-Path of an email that has none.
	 */
	public function getReturnPath(): ?TEmailAddress
	{
		return $this->_returnPath;
	}

	/**
	 * @param null|array|string|TEmailAddress $address the Return-Path of an email that has none; it receives the bounces.
	 */
	public function setReturnPath($address)
	{
		$this->_returnPath = ($address === null || $address === '') ? null : TEmailAddress::from($address);
	}

	/**
	 * @return TEmailAddress[] the Reply-To of an email that has none.
	 */
	public function getReplyTo(): array
	{
		return $this->_replyTo;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the Reply-To of an email that has none.
	 */
	public function setReplyTo($addresses)
	{
		$this->_replyTo = TEmailAddress::parseList($addresses);
	}

	/**
	 * @return TEmailAddress[] the addresses added to the Cc of every email.
	 */
	public function getCc(): array
	{
		return $this->_cc;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the addresses added to the Cc of every email.
	 */
	public function setCc($addresses)
	{
		$this->_cc = TEmailAddress::parseList($addresses);
	}

	/**
	 * @return TEmailAddress[] the addresses added to the Bcc of every email.
	 */
	public function getBcc(): array
	{
		return $this->_bcc;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the addresses added to the Bcc of every email.
	 */
	public function setBcc($addresses)
	{
		$this->_bcc = TEmailAddress::parseList($addresses);
	}

	/**
	 * @return string the domain of the Message-IDs, empty for the domain of the From of each email. Defaults to ''.
	 */
	public function getMessageIdDomain(): string
	{
		return $this->_messageIdDomain;
	}

	/**
	 * @param string $value the domain of the Message-IDs; empty for the domain of the From of each email.
	 * @throws TConfigurationException when the value is not a domain.
	 */
	public function setMessageIdDomain($value)
	{
		$value = TPropertyValue::ensureString($value);
		if ($value !== '' && !preg_match('/^[A-Za-z0-9.\-]+$/', $value)) {
			throw new TConfigurationException('emailmodule_messageiddomain_invalid', $value);
		}
		$this->_messageIdDomain = $value;
	}
}
