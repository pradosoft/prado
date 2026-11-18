<?php

/**
 * TSmtpTransport class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Email\Net\TEmailEncryption;
use Prado\Email\Net\TEmailSocketConnection;
use Prado\Email\TEmail;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNetworkException;
use Prado\IO\TUriDefaultPort;
use Prado\IO\TUriScheme;
use Prado\TPropertyValue;

/**
 * TSmtpTransport class.
 *
 * TSmtpTransport delivers emails to an SMTP server (RFC 5321) over a
 * {@see TEmailSocketConnection}.
 *
 * <code>
 * <transport class="Prado\Email\Transport\TSmtpTransport"
 *     Dsn="smtp://user:password@smtp.example.com" />
 * </code>
 *
 * | DSN | Encryption | Default port |
 * |---|---|---|
 * | `smtp://host` | STARTTLS, required | 587 |
 * | `smtps://host` | TLS from the first byte | 465 |
 * | `smtp://host?encryption=none` | none | 25 |
 *
 * The DSN query also takes `auth` ({@see TSmtpAuthMechanism}), `token` (an OAuth 2
 * access token), `verify` (0 turns certificate verification off), and `timeout`
 * (seconds).  The user name, password, and token are URL encoded.
 *
 * The transport authenticates when a {@see setUsername Username} is set.  The
 * connection stays open between emails and is reset with RSET; {@see close} sends QUIT.
 *
 * A reply that refuses a command throws a {@see TSmtpException} with the reply
 * code.  A 4xx reply and a lost connection are transient failures.  An email is
 * delivered to all its recipients or to none: a refused recipient fails the email.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSmtpTransport extends TEmailTransport
{
	/** The most lines read for one reply. */
	protected const MAX_REPLY_LINES = 256;

	/** @var ?TEmailSocketConnection the connection to the server. */
	private ?TEmailSocketConnection $_connection = null;

	/** @var bool whether the connection has greeted and authenticated. */
	private bool $_ready = false;

	/** @var array<string, string> the parameters of the extensions the server offers, by upper-case keyword. */
	private array $_capabilities = [];

	/** @var string the server host. */
	private string $_host = '';

	/** @var ?int the server port, null for the default of the encryption. */
	private ?int $_port = null;

	/** @var string the TEmailEncryption of the connection. */
	private string $_encryption = TEmailEncryption::StartTls;

	/** @var string the user name. */
	private string $_username = '';

	/** @var string the password. */
	private string $_password = '';

	/** @var string the OAuth 2 access token. */
	private string $_accessToken = '';

	/** @var string the TSmtpAuthMechanism. */
	private string $_authMechanism = TSmtpAuthMechanism::Auto;

	/** @var float the connect and read timeout in seconds. */
	private float $_timeout = 30.0;

	/** @var bool whether the server certificate is verified. */
	private bool $_verifyPeer = true;

	/** @var ?string the name the client gives in EHLO. */
	private ?string $_localDomain = null;

	/**
	 * Delivers an email in one SMTP transaction.
	 * @param TEmail $email the final email.
	 * @throws TSmtpException when the server refuses a command.
	 * @throws TEmailTransportException when the email has no sender or recipient, or the connection fails.
	 * @throws TConfigurationException when the Host is not set.
	 */
	public function send(TEmail $email): void
	{
		$sender = $this->getEnvelopeSender($email)->getAddress();
		$recipients = $this->getEnvelopeRecipients($email);
		$message = $this->getMimeBuilder()->build($email);
		try {
			$this->connect();
			$this->command('MAIL FROM:<' . $sender . '>', [250]);
			foreach ($recipients as $recipient) {
				$this->command('RCPT TO:<' . $recipient->getAddress() . '>', [250, 251]);
			}
			$this->command('DATA', [354]);
			$this->getConnection()->write($this->escapeData($message) . '.' . TEmailSocketConnection::EOL);
			$this->expect($this->readReply(), [250], 'DATA');
		} catch (TSmtpException $e) {
			$this->reset();
			throw $e;
		} catch (TNetworkException $e) {
			$this->disconnect();
			throw (new TEmailTransportException('smtptransport_connection_failed', $this->getHost(), rtrim($e->getMessage(), '.')))->setIsTransient(true);
		}
	}

	/**
	 * Sends QUIT and closes the connection.
	 */
	public function close(): void
	{
		$connection = $this->getConnection();
		if ($connection->getIsOpen() && $this->_ready) {
			try {
				$connection->writeLine('QUIT');
				$this->readReply();
			} catch (TNetworkException $e) {
			}
		}
		$this->disconnect();
	}

	/**
	 * Closes the connection without a QUIT.
	 */
	protected function disconnect(): void
	{
		$this->getConnection()->close();
		$this->_ready = false;
		$this->_capabilities = [];
	}

	/**
	 * Ends a failed transaction so the connection can carry the next email.
	 */
	protected function reset(): void
	{
		try {
			if ($this->_ready) {
				$this->command('RSET', [250]);
			} else {
				$this->disconnect();
			}
		} catch (TNetworkException $e) {
			$this->disconnect();
		}
	}

	/**
	 * Makes the connection ready for a transaction: opens it, greets, starts TLS,
	 * and authenticates.  A ready connection is reset, and reopened when it has gone stale.
	 * @throws TConfigurationException when the Host is not set.
	 * @throws TNetworkException when the connection or a command fails.
	 */
	protected function connect(): void
	{
		$connection = $this->getConnection();
		if ($connection->getIsOpen() && $this->_ready) {
			try {
				$this->command('RSET', [250]);
				return;
			} catch (TNetworkException $e) {
				$this->disconnect();
			}
		}
		if (($host = $this->getHost()) === '') {
			throw new TConfigurationException('smtptransport_host_required');
		}
		$encryption = $this->getEncryption();
		if (!$connection->getIsOpen()) {
			$connection->setTimeout($this->getTimeout());
			$connection->setVerifyPeer($this->getVerifyPeer());
			$connection->open($host, $this->getPort(), $encryption === TEmailEncryption::Ssl);
		}
		$this->expect($this->readReply(), [220], 'CONNECT');
		$this->hello();
		if ($encryption === TEmailEncryption::StartTls) {
			if (!isset($this->_capabilities['STARTTLS'])) {
				throw new TSmtpException('smtptransport_starttls_unsupported', $host);
			}
			$this->command('STARTTLS', [220]);
			$connection->startTls();
			$this->hello();
		}
		$this->authenticate();
		$this->_ready = true;
	}

	/**
	 * Sends EHLO, or HELO when the server refuses EHLO, and records the offered extensions.
	 * @throws TNetworkException when the greeting fails.
	 */
	protected function hello(): void
	{
		$domain = $this->getLocalDomain();
		$this->_capabilities = [];
		try {
			[, $lines] = $this->command('EHLO ' . $domain, [250]);
		} catch (TSmtpException $e) {
			if ($e->getReplyCode() < 500) {
				throw $e;
			}
			$this->command('HELO ' . $domain, [250]);
			return;
		}
		foreach (array_slice($lines, 1) as $line) {
			[$keyword, $parameters] = explode(' ', trim($line), 2) + [1 => ''];
			$this->_capabilities[strtoupper($keyword)] = trim($parameters);
		}
	}

	/**
	 * Authenticates when a Username or an AccessToken is set.
	 * @throws TSmtpException when the server refuses the credentials.
	 * @throws TNetworkException when the connection fails.
	 */
	protected function authenticate(): void
	{
		$username = $this->getUsername();
		$token = $this->getAccessToken();
		if ($username === '' && $token === '') {
			return;
		}
		$mechanism = $this->getAuthMechanism();
		if ($mechanism === TSmtpAuthMechanism::Auto) {
			$offered = preg_split('/\s+/', strtoupper($this->_capabilities['AUTH'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
			if ($token !== '') {
				$mechanism = TSmtpAuthMechanism::XOAuth2;
			} else {
				$mechanism = (in_array('LOGIN', $offered, true) && !in_array('PLAIN', $offered, true)) ? TSmtpAuthMechanism::Login : TSmtpAuthMechanism::Plain;
			}
		}
		if ($mechanism === TSmtpAuthMechanism::XOAuth2) {
			[$code] = $this->command('AUTH XOAUTH2 ' . base64_encode('user=' . $username . "\x01auth=Bearer " . $token . "\x01\x01"), [235, 334], 'AUTH XOAUTH2');
			if ($code === 334) {
				$this->command('', [235], 'AUTH XOAUTH2');
			}
		} elseif ($mechanism === TSmtpAuthMechanism::Login) {
			$this->command('AUTH LOGIN', [334]);
			$this->command(base64_encode($username), [334], 'AUTH LOGIN');
			$this->command(base64_encode($this->getPassword()), [235], 'AUTH LOGIN');
		} else {
			$this->command('AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $this->getPassword()), [235], 'AUTH PLAIN');
		}
	}

	/**
	 * Sends a command and reads its reply.
	 * @param string $command the command line.
	 * @param int[] $codes the reply codes that the command allows.
	 * @param ?string $label the text that names the command in an error, for a command that holds a credential.
	 * @throws TSmtpException when the reply code is not allowed.
	 * @throws TNetworkException when the connection fails.
	 * @return array{0: int, 1: string[]} the reply code and the reply lines.
	 */
	protected function command(string $command, array $codes, ?string $label = null): array
	{
		$this->getConnection()->writeLine($command);
		return $this->expect($this->readReply(), $codes, $label ?? $command);
	}

	/**
	 * Reads one reply, which may span lines.
	 * @throws TSmtpException when a line is not an SMTP reply.
	 * @throws TNetworkException when the connection fails.
	 * @return array{0: int, 1: string[]} the reply code and the reply lines.
	 */
	protected function readReply(): array
	{
		$connection = $this->getConnection();
		$lines = [];
		do {
			$line = $connection->readLine();
			if (!preg_match('/^(\d{3})([ \-]?)(.*)$/', $line, $match) || count($lines) >= self::MAX_REPLY_LINES) {
				throw (new TSmtpException('smtptransport_reply_invalid', $this->getHost(), substr($line, 0, 200)))->setIsTransient(true);
			}
			$lines[] = $match[3];
		} while ($match[2] === '-');
		return [(int) $match[1], $lines];
	}

	/**
	 * Checks a reply against the codes a command allows.
	 * @param array{0: int, 1: string[]} $reply the reply code and lines.
	 * @param int[] $codes the allowed reply codes.
	 * @param string $command the text that names the command in an error.
	 * @throws TSmtpException when the reply code is not allowed.
	 * @return array{0: int, 1: string[]} the reply.
	 */
	protected function expect(array $reply, array $codes, string $command): array
	{
		[$code, $lines] = $reply;
		if (!in_array($code, $codes, true)) {
			throw (new TSmtpException('smtptransport_command_failed', $command, $code . ' ' . implode(' ', $lines)))->setReply($code, implode("\n", $lines));
		}
		return $reply;
	}

	/**
	 * Prepares a message for DATA: every line ends in CRLF and a line that starts
	 * with a dot gets a second dot.
	 * @param string $message the message.
	 * @return string the message, ending in CRLF.
	 */
	protected function escapeData(string $message): string
	{
		$message = preg_replace('/\r\n|\r|\n/', TEmailSocketConnection::EOL, $message);
		if (!str_ends_with($message, TEmailSocketConnection::EOL)) {
			$message .= TEmailSocketConnection::EOL;
		}
		return preg_replace('/^\./m', '..', $message);
	}

	/**
	 * @return TEmailSocketConnection the connection to the server.
	 */
	public function getConnection(): TEmailSocketConnection
	{
		return $this->_connection ??= new TEmailSocketConnection();
	}

	/**
	 * @param TEmailSocketConnection $connection the connection to the server; an open one is used as it is.
	 */
	public function setConnection(TEmailSocketConnection $connection)
	{
		$this->_connection = $connection;
		$this->_ready = false;
		$this->_capabilities = [];
	}

	/**
	 * @return array<string, string> the parameters of the extensions the server offered in its last greeting, by upper-case keyword.
	 */
	public function getCapabilities(): array
	{
		return $this->_capabilities;
	}

	/**
	 * Sets the properties from a DSN.
	 * @param string $dsn the DSN, e.g. "smtp://user:password@smtp.example.com:587".
	 * @throws TInvalidDataValueException when the DSN is not an smtp or smtps URL with a host.
	 */
	public function setDsn($dsn)
	{
		$parts = parse_url(TPropertyValue::ensureString($dsn));
		$scheme = strtolower((string) ($parts['scheme'] ?? ''));
		if (!is_array($parts) || !isset($parts['host']) || !in_array($scheme, [TUriScheme::SMTP, TUriScheme::SMTPS], true)) {
			throw new TInvalidDataValueException('smtptransport_dsn_invalid');
		}
		parse_str($parts['query'] ?? '', $query);
		$this->setHost($parts['host']);
		$this->setEncryption($scheme === TUriScheme::SMTPS ? TEmailEncryption::Ssl : ($query['encryption'] ?? TEmailEncryption::StartTls));
		$this->setPort($parts['port'] ?? null);
		$this->setUsername(rawurldecode((string) ($parts['user'] ?? '')));
		$this->setPassword(rawurldecode((string) ($parts['pass'] ?? '')));
		$this->setAccessToken((string) ($query['token'] ?? ''));
		if (isset($query['auth'])) {
			$this->setAuthMechanism($query['auth']);
		}
		if (isset($query['verify'])) {
			$this->setVerifyPeer($query['verify']);
		}
		if (isset($query['timeout'])) {
			$this->setTimeout($query['timeout']);
		}
	}

	/**
	 * @return string the server host. Defaults to ''.
	 */
	public function getHost(): string
	{
		return $this->_host;
	}

	/**
	 * @param string $value the server host.
	 */
	public function setHost($value)
	{
		$this->_host = trim(TPropertyValue::ensureString($value), '[]');
	}

	/**
	 * @return int the server port. Defaults to 587 for StartTls, 465 for Ssl, and 25 for None.
	 */
	public function getPort(): int
	{
		return $this->_port ?? match ($this->_encryption) {
			TEmailEncryption::Ssl => TUriDefaultPort::SMTPS,
			TEmailEncryption::None => TUriDefaultPort::SMTP,
			default => TUriDefaultPort::SUBMISSION,
		};
	}

	/**
	 * @param null|int|string $value the server port; null or empty for the default of the Encryption.
	 */
	public function setPort($value)
	{
		$this->_port = ($value === null || $value === '') ? null : TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the {@see TEmailEncryption} of the connection. Defaults to TEmailEncryption::StartTls.
	 */
	public function getEncryption(): string
	{
		return $this->_encryption;
	}

	/**
	 * @param string $value the {@see TEmailEncryption} of the connection, in any case.
	 */
	public function setEncryption($value)
	{
		$this->_encryption = TPropertyValue::ensureEnum($value, TEmailEncryption::class);
	}

	/**
	 * @return string the user name. Defaults to ''.
	 */
	public function getUsername(): string
	{
		return $this->_username;
	}

	/**
	 * @param string $value the user name; empty sends without authentication.
	 */
	public function setUsername($value)
	{
		$this->_username = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the password. Defaults to ''.
	 */
	public function getPassword(): string
	{
		return $this->_password;
	}

	/**
	 * @param string $value the password.
	 */
	public function setPassword($value)
	{
		$this->_password = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the OAuth 2 access token. Defaults to ''.
	 */
	public function getAccessToken(): string
	{
		return $this->_accessToken;
	}

	/**
	 * @param string $value the OAuth 2 access token for XOAUTH2.
	 */
	public function setAccessToken($value)
	{
		$this->_accessToken = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the {@see TSmtpAuthMechanism}. Defaults to TSmtpAuthMechanism::Auto.
	 */
	public function getAuthMechanism(): string
	{
		return $this->_authMechanism;
	}

	/**
	 * @param string $value the {@see TSmtpAuthMechanism}, in any case.
	 */
	public function setAuthMechanism($value)
	{
		$this->_authMechanism = TPropertyValue::ensureEnum($value, TSmtpAuthMechanism::class);
	}

	/**
	 * @return float the connect and read timeout in seconds. Defaults to 30.
	 */
	public function getTimeout(): float
	{
		return $this->_timeout;
	}

	/**
	 * @param float|string $value the connect and read timeout in seconds, at least 1.
	 */
	public function setTimeout($value)
	{
		$this->_timeout = max(1.0, TPropertyValue::ensureFloat($value));
	}

	/**
	 * @return bool whether the server certificate is verified. Defaults to true.
	 */
	public function getVerifyPeer(): bool
	{
		return $this->_verifyPeer;
	}

	/**
	 * @param bool|string $value whether the server certificate is verified.
	 */
	public function setVerifyPeer($value)
	{
		$this->_verifyPeer = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the name the client gives in EHLO. Defaults to the host name when it is fully qualified, otherwise "[127.0.0.1]".
	 */
	public function getLocalDomain(): string
	{
		if ($this->_localDomain === null) {
			$name = (string) gethostname();
			$this->_localDomain = (str_contains($name, '.') && preg_match('/^[A-Za-z0-9.\-]+$/', $name)) ? $name : '[127.0.0.1]';
		}
		return $this->_localDomain;
	}

	/**
	 * @param string $value the name the client gives in EHLO: a domain or an address literal such as "[192.0.2.1]".
	 * @throws TInvalidDataValueException when the value is not a domain or an address literal.
	 */
	public function setLocalDomain($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!preg_match('/^(?:[A-Za-z0-9.\-]+|\[[A-Za-z0-9.:]+\])$/', $value)) {
			throw new TInvalidDataValueException('smtptransport_localdomain_invalid', $value);
		}
		$this->_localDomain = $value;
	}
}
