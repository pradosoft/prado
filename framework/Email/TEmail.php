<?php

/**
 * TEmail class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEmail class.
 *
 * TEmail is one email message: its addresses, subject, content, files, and the
 * identifiers that track it.  {@see send} hands it to an {@see IEmailMailer}, which
 * is the {@see TEmailModule} of the application.
 *
 * <code>
 * $email = new TEmail();
 * $email->setFrom('Shop <shop@example.com>');
 * $email->setTo('ann@example.com');
 * $email->setSubject('Order {{NUMBER}} shipped');
 * $email->setHtml('<p>Hello {{NAME}}</p>');
 * $email->setData(['NUMBER' => 1042, 'NAME' => 'Ann']);
 * $email->send();
 * </code>
 *
 * Content comes from {@see setHtml Html} and {@see setText Text}, or from a
 * {@see setBody Body} that renders them, such as the {@see UI\TEmailBody} of a
 * ".email" template.  An email with Html and no Text gets its Text from
 * {@see TEmailHtmlToText}.
 *
 * Placeholders in the Subject, Html, Text, additional headers, and tracked links
 * are replaced by the values of {@see setData Data} when the email is finalized:
 *
 * | Placeholder | Html | Text, Subject, headers | Tracked link |
 * |---|---|---|---|
 * | `{{KEY}}` | HTML encoded | as is | URL encoded |
 * | `{{{KEY}}}` | as is | as is | as is |
 *
 * A key without a value stays in the text.  `{{email:tracking-id}}` is the
 * {@see getTrackingId TrackingId} and `{{email:to}}` the first To address.
 *
 * A value is inserted once and is not searched for placeholders.  Text that
 * users wrote goes into an email as a Data value; written straight into the Html
 * or Text, a "{{KEY}}" in it would be replaced like one of the template.
 *
 * {@see render} runs the Body once and {@see finalize} replaces the placeholders
 * once.  A clone made between the two shares the rendered content and gets its own
 * TrackingId and MessageId, which is how one template is sent to many recipients.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmail extends \Prado\TComponent
{
	/** The placeholder key of the tracking ID. */
	public const TRACKING_ID_KEY = 'email:tracking-id';

	/** The placeholder key of the first To address. */
	public const TO_KEY = 'email:to';

	/** The headers that TEmail properties write; {@see setHeader} rejects them. */
	protected const RESERVED_HEADERS = ['from', 'sender', 'reply-to', 'return-path', 'to', 'cc', 'bcc', 'subject', 'date', 'message-id', 'mime-version', 'content-type', 'content-transfer-encoding'];

	/** @var ?TEmailAddress the author of the email. */
	private ?TEmailAddress $_from = null;

	/** @var ?TEmailAddress the mailbox that submits the email when it is not the author. */
	private ?TEmailAddress $_sender = null;

	/** @var ?TEmailAddress the envelope sender, which receives bounces. */
	private ?TEmailAddress $_returnPath = null;

	/** @var TEmailAddress[] the To addresses. */
	private array $_to = [];

	/** @var TEmailAddress[] the Cc addresses. */
	private array $_cc = [];

	/** @var TEmailAddress[] the Bcc addresses. */
	private array $_bcc = [];

	/** @var TEmailAddress[] the Reply-To addresses. */
	private array $_replyTo = [];

	/** @var string the subject. */
	private string $_subject = '';

	/** @var ?int the date as a Unix timestamp. */
	private ?int $_date = null;

	/** @var ?int the priority, 1 (highest) to 5 (lowest). */
	private ?int $_priority = null;

	/** @var ?string the Message-ID, "id@domain". */
	private ?string $_messageId = null;

	/** @var string the character set of the content. */
	private string $_charset = 'UTF-8';

	/** @var array<string, array{0: string, 1: string}> the additional headers, [name, value] by lower-case name. */
	private array $_headers = [];

	/** @var ?string the HTML content. */
	private ?string $_html = null;

	/** @var ?string the plain-text content. */
	private ?string $_text = null;

	/** @var ?IEmailBody the body that renders the content. */
	private ?IEmailBody $_body = null;

	/** @var bool whether the body has rendered. */
	private bool $_rendered = false;

	/** @var bool whether the placeholders are replaced. */
	private bool $_final = false;

	/** @var TEmailFile[] the attachments and inline files. */
	private array $_files = [];

	/** @var array the placeholder values by key. */
	private array $_data = [];

	/** @var string the mailing list of the email. */
	private string $_list = '';

	/** @var bool whether viewing the email on the web requires its user to be logged in. */
	private bool $_secure = false;

	/** @var ?string the public tracking ID. */
	private ?string $_trackingId = null;

	/** @var string[] the tracked link target URLs by link number. */
	private array $_trackedLinks = [];

	/** @var ?string the name of the user the email belongs to. */
	private ?string $_username = null;

	/** @var string the delivery status. */
	private string $_status = TEmailStatus::New;

	/** @var ?string the reason of a failed delivery. */
	private ?string $_error = null;

	/** @var ?IEmailMailer the mailer that sends the email. */
	private ?IEmailMailer $_mailer = null;

	/**
	 * Gives a clone its own TrackingId and MessageId and the New status.
	 */
	public function __clone()
	{
		parent::__clone();
		$this->_trackingId = null;
		$this->_messageId = null;
		$this->_status = TEmailStatus::New;
		$this->_error = null;
	}

	/**
	 * Sends the email through its mailer.
	 * @throws TConfigurationException when the email has no mailer and the application has no {@see IEmailMailer} module.
	 * @return string the {@see TEmailStatus} of the email after the call.
	 */
	public function send(): string
	{
		return ($this->getMailer() ?? $this->findMailer())->sendEmail($this);
	}

	/**
	 * Finds the first {@see IEmailMailer} module of the application.
	 * @throws TConfigurationException when the application has none.
	 * @return IEmailMailer the mailer.
	 */
	protected function findMailer(): IEmailMailer
	{
		if (($app = Prado::getApplication()) !== null) {
			foreach (array_keys($app->getModulesByType(IEmailMailer::class)) as $id) {
				if (($module = $app->getModule($id)) instanceof IEmailMailer) {
					return $module;
				}
			}
		}
		throw new TConfigurationException('email_mailer_required');
	}

	/**
	 * Runs the Body once so it sets the Html, Text, and other properties.  The
	 * placeholders stay in the content.
	 */
	public function render(): void
	{
		if (!$this->_rendered) {
			$this->_rendered = true;
			$this->_body?->renderEmail($this);
		}
	}

	/**
	 * Makes the content final: renders the Body, derives a missing Text from the Html,
	 * and replaces the placeholders in the Subject, Html, Text, headers, and tracked links.
	 * It acts once; later changes to Data have no effect.
	 */
	public function finalize(): void
	{
		if ($this->_final) {
			return;
		}
		$this->render();
		if ($this->_html !== null && $this->_text === null) {
			$this->_text = TEmailHtmlToText::convert($this->_html);
		}
		$data = $this->getPlaceholderData();
		$this->_subject = preg_replace('/[\r\n]+/', ' ', static::replacePlaceholders($this->_subject, $data, null));
		if ($this->_html !== null) {
			$this->_html = static::replacePlaceholders($this->_html, $data, fn (string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
		}
		if ($this->_text !== null) {
			$this->_text = static::replacePlaceholders($this->_text, $data, null);
		}
		foreach ($this->_headers as $key => [, $value]) {
			$this->_headers[$key][1] = preg_replace('/[\r\n\x00]+/', ' ', static::replacePlaceholders($value, $data, null));
		}
		foreach ($this->_trackedLinks as $index => $url) {
			$this->_trackedLinks[$index] = static::replacePlaceholders($url, $data, 'rawurlencode');
		}
		$this->_final = true;
	}

	/**
	 * @return bool whether the Body has rendered.
	 */
	public function getIsRendered(): bool
	{
		return $this->_rendered;
	}

	/**
	 * @return bool whether the placeholders are replaced and the content is final.
	 */
	public function getIsFinal(): bool
	{
		return $this->_final;
	}

	/**
	 * @return array the placeholder values: the Data and the reserved "email:" keys.
	 */
	protected function getPlaceholderData(): array
	{
		return [
			self::TRACKING_ID_KEY => $this->getTrackingId(),
			self::TO_KEY => isset($this->_to[0]) ? $this->_to[0]->getAddress() : '',
		] + $this->_data;
	}

	/**
	 * Replaces the "{{KEY}}" and "{{{KEY}}}" placeholders that have a value.
	 * @param string $text the text.
	 * @param array $data the values by key.
	 * @param ?callable $encode encodes the value of a "{{KEY}}" placeholder; null leaves it as is.
	 * @return string the text with the placeholders replaced.
	 */
	protected static function replacePlaceholders(string $text, array $data, ?callable $encode): string
	{
		if (!str_contains($text, '{{')) {
			return $text;
		}
		return preg_replace_callback('/\{\{\{\s*([\w.:\-]+)\s*\}\}\}|\{\{\s*([\w.:\-]+)\s*\}\}/', function (array $match) use ($data, $encode) {
			$raw = ($match[2] ?? '') === '';
			$key = $raw ? $match[1] : $match[2];
			if (!array_key_exists($key, $data)) {
				return $match[0];
			}
			$value = $data[$key];
			$value = (is_scalar($value) || $value instanceof \Stringable) ? (string) $value : '';
			return ($raw || $encode === null) ? $value : $encode($value);
		}, $text);
	}

	/**
	 * @return ?TEmailAddress the author of the email.
	 */
	public function getFrom(): ?TEmailAddress
	{
		return $this->_from;
	}

	/**
	 * @param null|array|string|TEmailAddress $address the author of the email; null or empty removes it.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function setFrom($address)
	{
		$this->_from = static::toAddress($address);
	}

	/**
	 * @return ?TEmailAddress the mailbox that submits the email when it is not the author.
	 */
	public function getSender(): ?TEmailAddress
	{
		return $this->_sender;
	}

	/**
	 * @param null|array|string|TEmailAddress $address the submitting mailbox; null or empty removes it.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function setSender($address)
	{
		$this->_sender = static::toAddress($address);
	}

	/**
	 * @return ?TEmailAddress the envelope sender, which receives bounces.
	 */
	public function getReturnPath(): ?TEmailAddress
	{
		return $this->_returnPath;
	}

	/**
	 * @param null|array|string|TEmailAddress $address the envelope sender; null or empty removes it.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function setReturnPath($address)
	{
		$this->_returnPath = static::toAddress($address);
	}

	/**
	 * @return TEmailAddress[] the To addresses.
	 */
	public function getTo(): array
	{
		return $this->_to;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the To addresses, as {@see TEmailAddress::parseList} reads them.
	 * @throws TInvalidDataValueException when an address is invalid.
	 */
	public function setTo($addresses)
	{
		$this->_to = TEmailAddress::parseList($addresses);
	}

	/**
	 * @param string|TEmailAddress $address the To address to add.
	 * @param string $name the display name, when the address is a string without one.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function addTo($address, string $name = '')
	{
		$this->_to[] = static::toNamedAddress($address, $name);
	}

	/**
	 * @return TEmailAddress[] the Cc addresses.
	 */
	public function getCc(): array
	{
		return $this->_cc;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the Cc addresses, as {@see TEmailAddress::parseList} reads them.
	 * @throws TInvalidDataValueException when an address is invalid.
	 */
	public function setCc($addresses)
	{
		$this->_cc = TEmailAddress::parseList($addresses);
	}

	/**
	 * @param string|TEmailAddress $address the Cc address to add.
	 * @param string $name the display name, when the address is a string without one.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function addCc($address, string $name = '')
	{
		$this->_cc[] = static::toNamedAddress($address, $name);
	}

	/**
	 * @return TEmailAddress[] the Bcc addresses.
	 */
	public function getBcc(): array
	{
		return $this->_bcc;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the Bcc addresses, as {@see TEmailAddress::parseList} reads them.
	 * @throws TInvalidDataValueException when an address is invalid.
	 */
	public function setBcc($addresses)
	{
		$this->_bcc = TEmailAddress::parseList($addresses);
	}

	/**
	 * @param string|TEmailAddress $address the Bcc address to add.
	 * @param string $name the display name, when the address is a string without one.
	 * @throws TInvalidDataValueException when the address is invalid.
	 */
	public function addBcc($address, string $name = '')
	{
		$this->_bcc[] = static::toNamedAddress($address, $name);
	}

	/**
	 * @return TEmailAddress[] the Reply-To addresses.
	 */
	public function getReplyTo(): array
	{
		return $this->_replyTo;
	}

	/**
	 * @param null|array|string|TEmailAddress $addresses the Reply-To addresses, as {@see TEmailAddress::parseList} reads them.
	 * @throws TInvalidDataValueException when an address is invalid.
	 */
	public function setReplyTo($addresses)
	{
		$this->_replyTo = TEmailAddress::parseList($addresses);
	}

	/**
	 * Returns the envelope recipients: the To, Cc, and Bcc addresses, each address once.
	 * @return TEmailAddress[] the recipients.
	 */
	public function getRecipients(): array
	{
		$recipients = [];
		foreach ([$this->_to, $this->_cc, $this->_bcc] as $addresses) {
			foreach ($addresses as $address) {
				$recipients[strtolower($address->getAddress())] ??= $address;
			}
		}
		return array_values($recipients);
	}

	/**
	 * @param null|array|string|TEmailAddress $address the address value.
	 * @return ?TEmailAddress the address, null for null or an empty string.
	 */
	protected static function toAddress($address): ?TEmailAddress
	{
		return ($address === null || $address === '') ? null : TEmailAddress::from($address);
	}

	/**
	 * @param string|TEmailAddress $address the address value.
	 * @param string $name the display name for a string address without one.
	 * @return TEmailAddress the address.
	 */
	protected static function toNamedAddress($address, string $name): TEmailAddress
	{
		$address = TEmailAddress::from($address);
		return ($name !== '' && $address->getName() === '') ? new TEmailAddress($address->getAddress(), $name) : $address;
	}

	/**
	 * @return string the subject. Defaults to ''.
	 */
	public function getSubject(): string
	{
		return $this->_subject;
	}

	/**
	 * @param string $subject the subject, on one line.
	 * @throws TInvalidDataValueException when the subject has a line break.
	 */
	public function setSubject($subject)
	{
		$this->_subject = static::ensureHeaderValue('Subject', TPropertyValue::ensureString($subject));
	}

	/**
	 * @return ?int the date as a Unix timestamp, null for the time of sending.
	 */
	public function getDate(): ?int
	{
		return $this->_date;
	}

	/**
	 * @param null|\DateTimeInterface|int|string $date the date: a Unix timestamp, a date string, or a date object; null for the time of sending.
	 * @throws TInvalidDataValueException when a string is not a date.
	 */
	public function setDate($date)
	{
		if ($date === null || $date === '') {
			$this->_date = null;
		} elseif ($date instanceof \DateTimeInterface) {
			$this->_date = $date->getTimestamp();
		} elseif (is_numeric($date)) {
			$this->_date = (int) $date;
		} elseif (($time = strtotime((string) $date)) !== false) {
			$this->_date = $time;
		} else {
			throw new TInvalidDataValueException('email_date_invalid', (string) $date);
		}
	}

	/**
	 * @return ?int the priority, 1 (highest) to 5 (lowest), null for none.
	 */
	public function getPriority(): ?int
	{
		return $this->_priority;
	}

	/**
	 * @param null|int|string $priority the priority, kept within 1 (highest) to 5 (lowest); null or empty for none.
	 */
	public function setPriority($priority)
	{
		$this->_priority = ($priority === null || $priority === '') ? null : max(1, min(5, TPropertyValue::ensureInteger($priority)));
	}

	/**
	 * @return ?string the Message-ID, "id@domain", null until it is assigned.
	 */
	public function getMessageId(): ?string
	{
		return $this->_messageId;
	}

	/**
	 * @param ?string $id the Message-ID, "id@domain", with or without angle brackets; null removes it.
	 * @throws TInvalidDataValueException when the value is not "id@domain".
	 */
	public function setMessageId($id)
	{
		if ($id === null || $id === '') {
			$this->_messageId = null;
			return;
		}
		$id = trim(TPropertyValue::ensureString($id), " \t<>");
		if (!preg_match('/^[^\s<>@]+@[^\s<>@]+$/', $id)) {
			throw new TInvalidDataValueException('email_messageid_invalid', $id);
		}
		$this->_messageId = $id;
	}

	/**
	 * @return string the character set of the content. Defaults to 'UTF-8'.
	 */
	public function getCharset(): string
	{
		return $this->_charset;
	}

	/**
	 * @param string $charset the character set of the Subject, Html, and Text.
	 * @throws TInvalidDataValueException when the name is not a character set token.
	 */
	public function setCharset($charset)
	{
		$charset = TPropertyValue::ensureString($charset);
		if (!preg_match('/^[\w.:\-]+$/', $charset)) {
			throw new TInvalidDataValueException('email_charset_invalid', $charset);
		}
		$this->_charset = $charset;
	}

	/**
	 * @return array<string, string> the additional headers, value by name.
	 */
	public function getHeaders(): array
	{
		return array_column($this->_headers, 1, 0);
	}

	/**
	 * @param string $name the header name, in any case.
	 * @return ?string the value of the additional header, null when it is not set.
	 */
	public function getHeader(string $name): ?string
	{
		return $this->_headers[strtolower($name)][1] ?? null;
	}

	/**
	 * Sets or removes an additional header.  The headers that the properties write,
	 * such as From, To, Subject, and Date, are set through their properties.
	 * @param string $name the header name.
	 * @param ?string $value the value on one line; null removes the header.
	 * @throws TInvalidDataValueException when the name is reserved or invalid, or the value has a line break.
	 */
	public function setHeader(string $name, $value)
	{
		$key = strtolower($name);
		if (!preg_match('/^[!-9;-~]+$/', $name)) {
			throw new TInvalidDataValueException('email_header_invalid', $name);
		}
		if (in_array($key, self::RESERVED_HEADERS, true)) {
			throw new TInvalidDataValueException('email_header_reserved', $name);
		}
		if ($value === null) {
			unset($this->_headers[$key]);
		} else {
			$this->_headers[$key] = [$name, static::ensureHeaderValue($name, TPropertyValue::ensureString($value))];
		}
	}

	/**
	 * @param string $name the header name, for the error message.
	 * @param string $value the header value.
	 * @throws TInvalidDataValueException when the value has a line break or a NUL.
	 * @return string the value.
	 */
	protected static function ensureHeaderValue(string $name, string $value): string
	{
		if (preg_match('/[\r\n\x00]/', $value)) {
			throw new TInvalidDataValueException('email_header_invalid', $name);
		}
		return $value;
	}

	/**
	 * Sets the List-Unsubscribe header (RFC 2369) and, for one-click unsubscribe, the
	 * List-Unsubscribe-Post header (RFC 8058).  A one-click URL is HTTPS and
	 * unsubscribes on a POST of "List-Unsubscribe=One-Click".
	 * @param ?string $url the unsubscribe URL; null with no mailto removes both headers.
	 * @param ?string $mailto an unsubscribe email address.
	 * @param bool $oneClick whether the URL supports one-click unsubscribe.
	 */
	public function setListUnsubscribe(?string $url, ?string $mailto = null, bool $oneClick = true)
	{
		$targets = [];
		if ($url !== null && $url !== '') {
			$targets[] = '<' . $url . '>';
		}
		if ($mailto !== null && $mailto !== '') {
			$targets[] = '<mailto:' . preg_replace('/^mailto:/i', '', $mailto) . '>';
		}
		$this->setHeader('List-Unsubscribe', $targets ? implode(', ', $targets) : null);
		$this->setHeader('List-Unsubscribe-Post', ($url && $oneClick) ? 'List-Unsubscribe=One-Click' : null);
	}

	/**
	 * @return ?string the HTML content, null when the email has none.
	 */
	public function getHtml(): ?string
	{
		return $this->_html;
	}

	/**
	 * @param ?string $html the HTML content; null removes it.
	 */
	public function setHtml($html)
	{
		$this->_html = $html === null ? null : TPropertyValue::ensureString($html);
	}

	/**
	 * @return ?string the plain-text content, null when the email has none.
	 */
	public function getText(): ?string
	{
		return $this->_text;
	}

	/**
	 * @param ?string $text the plain-text content; null derives it from the Html when the email is finalized.
	 */
	public function setText($text)
	{
		$this->_text = $text === null ? null : TPropertyValue::ensureString($text);
	}

	/**
	 * @return ?IEmailBody the body that renders the content, null when the content is set directly.
	 */
	public function getBody(): ?IEmailBody
	{
		return $this->_body;
	}

	/**
	 * @param ?IEmailBody $body the body that renders the content; setting it lets {@see render} run again.
	 */
	public function setBody(?IEmailBody $body)
	{
		$this->_body = $body;
		$this->_rendered = false;
	}

	/**
	 * @return TEmailFile[] the attachments and inline files.
	 */
	public function getFiles(): array
	{
		return $this->_files;
	}

	/**
	 * @param TEmailFile[] $files the attachments and inline files.
	 */
	public function setFiles(array $files)
	{
		$this->_files = [];
		foreach ($files as $file) {
			$this->addFile($file);
		}
	}

	/**
	 * @param TEmailFile $file the attachment or inline file to add.
	 */
	public function addFile(TEmailFile $file)
	{
		$this->_files[] = $file;
	}

	/**
	 * Attaches a file by path.
	 * @param string $path the path of the file.
	 * @param ?string $name the file name shown to the recipient, null for the base name of the path.
	 * @param ?string $mimeType the MIME type, null to derive it from the name.
	 * @return TEmailFile the attachment.
	 */
	public function attach(string $path, ?string $name = null, ?string $mimeType = null): TEmailFile
	{
		$this->addFile($file = TEmailFile::fromPath($path, $name, $mimeType));
		return $file;
	}

	/**
	 * Attaches data as a file.
	 * @param string $data the content.
	 * @param string $name the file name shown to the recipient.
	 * @param ?string $mimeType the MIME type, null to derive it from the name.
	 * @return TEmailFile the attachment.
	 */
	public function attachData(string $data, string $name, ?string $mimeType = null): TEmailFile
	{
		$this->addFile($file = TEmailFile::fromData($data, $name, $mimeType));
		return $file;
	}

	/**
	 * Adds a file inline and returns the content ID that the Html references as "cid:<id>".
	 * A path embedded twice is added once.
	 * @param string $path the path of the file.
	 * @param ?string $contentId the content ID, null to derive a stable one from the path.
	 * @return string the content ID.
	 */
	public function embed(string $path, ?string $contentId = null): string
	{
		$contentId ??= substr(md5($path), 0, 12) . '.' . preg_replace('/[^\w.+\-]/', '_', basename($path));
		foreach ($this->_files as $file) {
			if ($file->getContentId() === $contentId) {
				return $contentId;
			}
		}
		$this->addFile(TEmailFile::fromPath($path)->withContentId($contentId));
		return $contentId;
	}

	/**
	 * @return TEmailFile[] the files that are attachments.
	 */
	public function getAttachments(): array
	{
		return array_values(array_filter($this->_files, fn (TEmailFile $file) => !$file->getIsInline()));
	}

	/**
	 * @return TEmailFile[] the files that the Html references by content ID.
	 */
	public function getInlineFiles(): array
	{
		return array_values(array_filter($this->_files, fn (TEmailFile $file) => $file->getIsInline()));
	}

	/**
	 * @return array the placeholder values by key.
	 */
	public function getData(): array
	{
		return $this->_data;
	}

	/**
	 * @param ?array $data the placeholder values by key; a value is a scalar or a Stringable.
	 */
	public function setData($data)
	{
		$this->_data = $data ?? [];
	}

	/**
	 * @return string the mailing list of the email, empty for a transactional email. Defaults to ''.
	 */
	public function getList(): string
	{
		return $this->_list;
	}

	/**
	 * Sets the mailing list.  An unsubscribe applies to the recipient and this list;
	 * an email without a list is transactional and is sent to unsubscribed recipients.
	 * @param string $list the name of the mailing list, at most 64 characters.
	 * @throws TInvalidDataValueException when the name has a control character or is too long.
	 */
	public function setList($list)
	{
		$list = TPropertyValue::ensureString($list);
		if (strlen($list) > 64 || preg_match('/[\x00-\x1F\x7F]/', $list)) {
			throw new TInvalidDataValueException('email_list_invalid', $list);
		}
		$this->_list = $list;
	}

	/**
	 * @return bool whether viewing the email on the web requires its user to be logged in. Defaults to false.
	 */
	public function getSecure(): bool
	{
		return $this->_secure;
	}

	/**
	 * @param bool $secure whether viewing the email on the web requires its user to be logged in.
	 */
	public function setSecure($secure)
	{
		$this->_secure = TPropertyValue::ensureBoolean($secure);
	}

	/**
	 * Returns the public tracking ID and generates one when it is not set.  The ID
	 * is 128 random bits in hex; it is the credential for viewing a non-secure
	 * email on the web.
	 * @return string the public tracking ID.
	 */
	public function getTrackingId(): string
	{
		return $this->_trackingId ??= bin2hex(random_bytes(16));
	}

	/**
	 * @param ?string $id the public tracking ID, 16 to 64 letters, digits, "-", or "_"; null generates one on access.
	 * @throws TInvalidDataValueException when the ID has other characters or another length.
	 */
	public function setTrackingId($id)
	{
		if ($id !== null && !preg_match('/^[A-Za-z0-9_\-]{16,64}$/', (string) $id)) {
			throw new TInvalidDataValueException('email_trackingid_invalid', (string) $id);
		}
		$this->_trackingId = $id === null ? null : (string) $id;
	}

	/**
	 * Registers a link target for click tracking and returns its link number.  A URL
	 * registered twice keeps its first number.
	 * @param string $url the link target URL, which may hold placeholders.
	 * @return int the link number.
	 */
	public function addTrackedLink(string $url): int
	{
		if (($index = array_search($url, $this->_trackedLinks, true)) === false) {
			$index = count($this->_trackedLinks);
			$this->_trackedLinks[] = $url;
		}
		return $index;
	}

	/**
	 * @return string[] the tracked link target URLs by link number.
	 */
	public function getTrackedLinks(): array
	{
		return $this->_trackedLinks;
	}

	/**
	 * @param ?string[] $links the tracked link target URLs by link number.
	 */
	public function setTrackedLinks($links)
	{
		$this->_trackedLinks = array_values(array_map('strval', $links ?? []));
	}

	/**
	 * @return ?string the name of the user the email belongs to, null for none.
	 */
	public function getUsername(): ?string
	{
		return $this->_username;
	}

	/**
	 * @param ?string $username the name of the user the email belongs to.
	 */
	public function setUsername($username)
	{
		$this->_username = ($username === null || $username === '') ? null : TPropertyValue::ensureString($username);
	}

	/**
	 * @return string the {@see TEmailStatus} of the email. Defaults to TEmailStatus::New.
	 */
	public function getStatus(): string
	{
		return $this->_status;
	}

	/**
	 * @param string $status the {@see TEmailStatus} of the email.
	 */
	public function setStatus($status)
	{
		$this->_status = TPropertyValue::ensureEnum($status, TEmailStatus::class);
	}

	/**
	 * @return ?string the reason of a failed delivery, null when there is none.
	 */
	public function getError(): ?string
	{
		return $this->_error;
	}

	/**
	 * @param ?string $error the reason of a failed delivery.
	 */
	public function setError($error)
	{
		$this->_error = ($error === null || $error === '') ? null : TPropertyValue::ensureString($error);
	}

	/**
	 * @return ?IEmailMailer the mailer that sends the email, null to use the application's.
	 */
	public function getMailer(): ?IEmailMailer
	{
		return $this->_mailer;
	}

	/**
	 * @param ?IEmailMailer $mailer the mailer that sends the email.
	 */
	public function setMailer(?IEmailMailer $mailer)
	{
		$this->_mailer = $mailer;
	}

	/**
	 * Excludes the mailer and the body from serialization; neither belongs to the message.
	 * @param array $exprops the properties to exclude, by reference.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . self::class . "\0_mailer";
		$exprops[] = "\0" . self::class . "\0_body";
	}
}
