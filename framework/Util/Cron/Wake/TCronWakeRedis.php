<?php

/**
 * TCronWakeRedis class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\IEventLoop;
use Prado\Prado;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCronWakeRedis class.
 *
 * TCronWakeRedis is the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} over Redis Pub/Sub:
 * {@see signal()} publishes on the {@see setChannel Channel}, and the waiting cron process holds
 * a subscribed socket of its own that receives the messages.  Every client of the server receives
 * the wake, with no shared file system, cache, or database.  The transport speaks RESP on a stream
 * socket itself, so it needs no `redis` extension.
 *
 * {@see setHost Host} and {@see setPort Port}, or {@see setSocket Socket}, select the server;
 * {@see setPassword Password} (with {@see setUsername Username} for an ACL user) authenticates.
 * {@see setTls Tls} connects over TLS, verifying the server against the system CA bundle or
 * {@see setCaFile CaFile} as {@see setPeerName PeerName} (default: the host); {@see setLocalCert
 * LocalCert} and {@see setLocalKey LocalKey} present a client certificate when the server
 * authenticates clients, and {@see setVerifyPeer VerifyPeer} false accepts any certificate.
 * {@see wait()} blocks in `stream_select()` on the subscribed socket for the remaining time;
 * {@see watch()} registers the socket with the loop, so the daemon wakes at once.  A message
 * received between waits is consumed by the next wait; a lost connection is reopened and
 * resubscribed on the next wait, and the daemon's watch is re-registered on it.
 *
 * ```xml
 * <module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeRedis" Host="redis" Port="6379" />
 * <module id="cron" class="Prado\Util\Cron\TDbCronManager" WakeTransport="cronwake" Daemon="true" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeRedis extends TModule implements ICronWakeTransport
{
	/** The default channel. */
	public const DEFAULT_CHANNEL = 'prado:cron:wake';

	/** The default seconds to wait for a connection and for a reply. */
	public const DEFAULT_TIMEOUT = 2.0;

	/** @var string the host name of the server */
	private string $_host = 'localhost';

	/** @var int the port of the server */
	private int $_port = 6379;

	/** @var ?string the Unix socket of the server; null uses TCP */
	private ?string $_socket = null;

	/** @var ?string the ACL user name */
	private ?string $_username = null;

	/** @var ?string the password */
	private ?string $_password = null;

	/** @var ?string the channel; null for {@see DEFAULT_CHANNEL} */
	private ?string $_channel = null;

	/** @var ?float the seconds to wait for a connection and a reply; null for {@see DEFAULT_TIMEOUT} */
	private ?float $_timeout = null;

	/** @var bool whether to connect over TLS */
	private bool $_tls = false;

	/** @var ?string the PEM file of the CA that signed the server certificate; null for the system bundle */
	private ?string $_caFile = null;

	/** @var bool whether to verify the server certificate and its name */
	private bool $_verifyPeer = true;

	/** @var ?string the name the server certificate must carry; null for the host */
	private ?string $_peerName = null;

	/** @var ?string the PEM file of the client certificate */
	private ?string $_localCert = null;

	/** @var ?string the PEM file of the client private key; null when it is in the certificate file */
	private ?string $_localKey = null;

	/** @var ?resource the subscribed socket of the waiting side */
	private $_subscriber;

	/** @var string the bytes of the subscribed socket not yet parsed */
	private string $_buffer = '';

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;


	/**
	 * Initializes the module.  A property left unset takes its class constant through `static::`.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		$this->_channel ??= static::DEFAULT_CHANNEL;
		$this->_timeout ??= (float) static::DEFAULT_TIMEOUT;
		parent::init($config);
	}

	/**
	 * @return string the host name of the server, default 'localhost'
	 */
	public function getHost(): string
	{
		return $this->_host;
	}

	/**
	 * @param string $value the host name of the server
	 * @return static the current object
	 */
	public function setHost($value): static
	{
		$this->_host = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return int the port of the server, default 6379
	 */
	public function getPort(): int
	{
		return $this->_port;
	}

	/**
	 * @param int|string $value the port of the server
	 * @return static the current object
	 */
	public function setPort($value): static
	{
		$this->_port = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @return ?string the Unix socket of the server; null uses TCP
	 */
	public function getSocket(): ?string
	{
		return $this->_socket;
	}

	/**
	 * @param ?string $value the Unix socket of the server; null or '' uses TCP
	 * @return static the current object
	 */
	public function setSocket($value): static
	{
		$this->_socket = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the ACL user name sent with the password; null for the default user
	 */
	public function getUsername(): ?string
	{
		return $this->_username;
	}

	/**
	 * @param ?string $value the ACL user name sent with the password; null or '' for the default user
	 * @return static the current object
	 */
	public function setUsername($value): static
	{
		$this->_username = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the password; null authenticates nothing
	 */
	public function getPassword(): ?string
	{
		return $this->_password;
	}

	/**
	 * @param ?string $value the password; null or '' authenticates nothing
	 * @return static the current object
	 */
	public function setPassword($value): static
	{
		$this->_password = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return string the channel, default {@see DEFAULT_CHANNEL}
	 */
	public function getChannel(): string
	{
		return $this->_channel ?? static::DEFAULT_CHANNEL;
	}

	/**
	 * @param string $value the channel, without whitespace
	 * @throws TInvalidDataValueException when the channel is empty or holds whitespace
	 * @return static the current object
	 */
	public function setChannel($value): static
	{
		$value = TPropertyValue::ensureString($value);
		if ($value === '' || preg_match('/\s/', $value)) {
			throw new TInvalidDataValueException('cronwakeredis_channel_invalid', $value);
		}
		$this->_channel = $value;
		return $this;
	}

	/**
	 * @return float the seconds to wait for a connection and for a reply, default {@see DEFAULT_TIMEOUT}
	 */
	public function getTimeout(): float
	{
		return $this->_timeout ?? (float) static::DEFAULT_TIMEOUT;
	}

	/**
	 * @param float|string $value the seconds to wait for a connection and for a reply
	 * @throws TInvalidDataValueException when the timeout is not greater than zero
	 * @return static the current object
	 */
	public function setTimeout($value): static
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value <= 0) {
			throw new TInvalidDataValueException('cronwakeredis_timeout_invalid', $value);
		}
		$this->_timeout = $value;
		return $this;
	}

	/**
	 * @return bool whether to connect over TLS, default false
	 */
	public function getTls(): bool
	{
		return $this->_tls;
	}

	/**
	 * @param bool|string $value whether to connect over TLS; a Unix socket is never TLS
	 * @return static the current object
	 */
	public function setTls($value): static
	{
		$this->_tls = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return ?string the PEM file of the CA that signed the server certificate; null for the system bundle
	 */
	public function getCaFile(): ?string
	{
		return $this->_caFile;
	}

	/**
	 * @param ?string $value the PEM file of the CA that signed the server certificate, a path or a namespace; null or '' for the system bundle
	 * @return static the current object
	 */
	public function setCaFile($value): static
	{
		$this->_caFile = $this->ensureFilePath($value);
		return $this;
	}

	/**
	 * @return bool whether to verify the server certificate and its name, default true
	 */
	public function getVerifyPeer(): bool
	{
		return $this->_verifyPeer;
	}

	/**
	 * @param bool|string $value whether to verify the server certificate and its name; false accepts any certificate
	 * @return static the current object
	 */
	public function setVerifyPeer($value): static
	{
		$this->_verifyPeer = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return ?string the name the server certificate must carry; null for the host
	 */
	public function getPeerName(): ?string
	{
		return $this->_peerName;
	}

	/**
	 * @param ?string $value the name the server certificate must carry; null or '' for the host
	 * @return static the current object
	 */
	public function setPeerName($value): static
	{
		$this->_peerName = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the PEM file of the client certificate; null presents none
	 */
	public function getLocalCert(): ?string
	{
		return $this->_localCert;
	}

	/**
	 * @param ?string $value the PEM file of the client certificate, a path or a namespace; null or '' presents none
	 * @return static the current object
	 */
	public function setLocalCert($value): static
	{
		$this->_localCert = $this->ensureFilePath($value);
		return $this;
	}

	/**
	 * @return ?string the PEM file of the client private key; null when it is in the certificate file
	 */
	public function getLocalKey(): ?string
	{
		return $this->_localKey;
	}

	/**
	 * @param ?string $value the PEM file of the client private key, a path or a namespace; null or '' when it is in the certificate file
	 * @return static the current object
	 */
	public function setLocalKey($value): static
	{
		$this->_localKey = $this->ensureFilePath($value);
		return $this;
	}

	/**
	 * @param mixed $value a file path or a dot namespace, or null or '' for none
	 * @return ?string the file path, or null
	 */
	protected function ensureFilePath($value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}
		$value = TPropertyValue::ensureString($value);
		return Prado::getPathOfNamespace($value) ?? $value;
	}

	/**
	 * @return bool whether stream sockets are available, and the `openssl` extension for TLS
	 */
	protected function getIsAvailable(): bool
	{
		return function_exists('stream_socket_client') && (!$this->getIsTls() || \extension_loaded('openssl'));
	}

	/**
	 * @return bool whether the connection is TLS: {@see getTls Tls} over TCP, never over a Unix socket
	 */
	protected function getIsTls(): bool
	{
		return $this->_tls && $this->_socket === null;
	}

	/**
	 * @return string the stream address of the server
	 */
	protected function getAddress(): string
	{
		if ($this->_socket !== null) {
			return 'unix://' . $this->_socket;
		}
		return ($this->_tls ? 'tls://' : 'tcp://') . $this->_host . ':' . $this->_port;
	}

	/**
	 * @return array the `ssl` stream context options of a TLS connection: peer verification, the CA
	 *   file, the peer name, and the client certificate
	 */
	protected function getStreamContextOptions(): array
	{
		if (!$this->getIsTls()) {
			return [];
		}
		$ssl = [
			'verify_peer' => $this->_verifyPeer,
			'verify_peer_name' => $this->_verifyPeer,
			'peer_name' => $this->_peerName ?? $this->_host,
		];
		if ($this->_caFile !== null) {
			$ssl['cafile'] = $this->_caFile;
		}
		if ($this->_localCert !== null) {
			$ssl['local_cert'] = $this->_localCert;
		}
		if ($this->_localKey !== null) {
			$ssl['local_pk'] = $this->_localKey;
		}
		return ['ssl' => $ssl];
	}

	/**
	 * Opens a blocking connection to the server and authenticates.
	 * @return ?resource the socket, or null when the server is unreachable or rejects the credentials
	 */
	protected function connect()
	{
		$timeout = $this->getTimeout();
		$context = stream_context_create($this->getStreamContextOptions());
		$socket = @stream_socket_client($this->getAddress(), $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
		if ($socket === false) {
			return null;
		}
		stream_set_timeout($socket, (int) $timeout, (int) (($timeout - (int) $timeout) * 1000000));
		if ($this->_password !== null) {
			$auth = ($this->_username !== null) ? ['AUTH', $this->_username, $this->_password] : ['AUTH', $this->_password];
			if ($this->command($socket, $auth) !== 'OK') {
				fclose($socket);
				return null;
			}
		}
		return $socket;
	}

	/**
	 * Sends a command and reads its reply on a blocking socket.
	 * @param resource $socket the socket
	 * @param string[] $args the command and its arguments
	 * @return mixed the reply; null on a protocol error, an error reply, or a timeout
	 */
	protected function command($socket, array $args): mixed
	{
		if (@fwrite($socket, $this->encode($args)) === false) {
			return null;
		}
		$buffer = '';
		while (true) {
			$frame = $this->decode($buffer, $pos = 0);
			if ($frame !== null) {
				return $frame[0];
			}
			$data = @fread($socket, 8192);
			if ($data === false || $data === '') {
				return null;
			}
			$buffer .= $data;
		}
	}

	/**
	 * @param string[] $args the command and its arguments
	 * @return string the RESP array of bulk strings
	 */
	protected function encode(array $args): string
	{
		$resp = '*' . count($args) . "\r\n";
		foreach ($args as $arg) {
			$resp .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
		}
		return $resp;
	}

	/**
	 * Decodes one RESP frame at a position of a buffer.  A simple string, an integer, and a bulk
	 * string decode to a string; an array to an array; an error reply and a null bulk to null.
	 * @param string $buffer the bytes received
	 * @param int $pos the position of the frame in the buffer
	 * @throws TInvalidDataValueException when the bytes are not RESP
	 * @return ?array the decoded value and the position after the frame, or null when the frame is incomplete
	 */
	protected function decode(string $buffer, int $pos): ?array
	{
		$end = strpos($buffer, "\r\n", $pos);
		if ($end === false) {
			return null;
		}
		$type = $buffer[$pos] ?? '';
		$line = substr($buffer, $pos + 1, $end - $pos - 1);
		$next = $end + 2;
		switch ($type) {
			case '+':
			case ':':
				return [$line, $next];
			case '-':
				return [null, $next];
			case '$':
				$length = (int) $line;
				if ($length < 0) {
					return [null, $next];
				}
				if (strlen($buffer) < $next + $length + 2) {
					return null;
				}
				return [substr($buffer, $next, $length), $next + $length + 2];
			case '*':
				$count = (int) $line;
				if ($count < 0) {
					return [null, $next];
				}
				$items = [];
				for ($i = 0; $i < $count; $i++) {
					$item = $this->decode($buffer, $next);
					if ($item === null) {
						return null;
					}
					[$items[], $next] = $item;
				}
				return [$items, $next];
			default:
				throw new TInvalidDataValueException('cronwakeredis_protocol_invalid', $type);
		}
	}

	/**
	 * Opens the subscribed socket when none is open: connects, subscribes to the channel, confirms
	 * the subscription, and switches the socket to non-blocking reads.
	 * @return bool whether the subscribed socket is open
	 */
	protected function ensureSubscribed(): bool
	{
		if ($this->_subscriber !== null) {
			return true;
		}
		$socket = $this->connect();
		if ($socket === null) {
			return false;
		}
		$reply = $this->command($socket, ['SUBSCRIBE', $this->getChannel()]);
		if (!is_array($reply) || ($reply[0] ?? null) !== 'subscribe') {
			fclose($socket);
			return false;
		}
		stream_set_blocking($socket, false);
		$this->_subscriber = $socket;
		$this->_buffer = '';
		return true;
	}

	/**
	 * Closes the subscribed socket; the next wait or watch reopens it.
	 */
	protected function closeSubscriber(): void
	{
		if ($this->_subscriber !== null) {
			fclose($this->_subscriber);
			$this->_subscriber = null;
		}
		$this->_buffer = '';
	}

	/**
	 * Reads what the subscribed socket holds and takes the messages out of it.
	 * @return ?bool true when a message arrived, false when none did, null when the connection was lost
	 */
	protected function drain(): ?bool
	{
		while (($data = @fread($this->_subscriber, 8192)) !== false && $data !== '') {
			$this->_buffer .= $data;
		}
		$woken = false;
		$pos = 0;
		try {
			while (($frame = $this->decode($this->_buffer, $pos)) !== null) {
				[$value, $pos] = $frame;
				if (is_array($value) && ($value[0] ?? null) === 'message') {
					$woken = true;
				}
			}
		} catch (TInvalidDataValueException $e) {
			$this->closeSubscriber();
			return null;
		}
		$this->_buffer = substr($this->_buffer, $pos);
		if (!$woken && ($data === false || feof($this->_subscriber))) {
			$this->closeSubscriber();
			return null;
		}
		return $woken;
	}

	/**
	 * Publishes on the channel over a connection of its own.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether the message was published
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable() || ($socket = $this->connect()) === null) {
			return false;
		}
		$reply = $this->command($socket, ['PUBLISH', $this->getChannel(), (string) $now]);
		fclose($socket);
		return $reply !== null;
	}

	/**
	 * Waits for a message until the timestamp passes.  Messages received since the last wait are
	 * consumed at once.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a message arrived, false when the wait timed out, null when the server is unreachable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable() || !$this->ensureSubscribed()) {
			return null;
		}
		if ($this->drain()) {
			return true;
		}
		$remaining = $until - $now;
		if ($this->_subscriber === null || $remaining <= 0) {
			return ($this->_subscriber === null) ? null : false;
		}
		$sec = (int) $remaining;
		$usec = (int) (($remaining - $sec) * 1000000);
		$read = [$this->_subscriber];
		$write = $except = [];
		if (@stream_select($read, $write, $except, $sec, $usec) > 0) {
			return $this->drain() ?? null;
		}
		return false;
	}

	/**
	 * Registers the subscribed socket with the loop and calls back on each message.  A lost
	 * connection is reopened and re-registered.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each message
	 * @return bool whether the transport is watching
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		if (!$this->getIsAvailable() || !$this->ensureSubscribed()) {
			return false;
		}
		$this->unwatch();
		$this->_loop = $loop;
		$this->_watcher = $loop->onReadable($this->_subscriber, function () use ($loop, $onWake) {
			$woken = $this->drain();
			if ($woken === null) {
				$this->watch($loop, $onWake);
				return;
			}
			if ($woken) {
				$onWake();
			}
		});
		return true;
	}

	/**
	 * Cancels the loop callback.
	 */
	public function unwatch(): void
	{
		if ($this->_watcher !== null && $this->_loop !== null) {
			$this->_loop->cancel($this->_watcher);
		}
		$this->_watcher = null;
		$this->_loop = null;
	}

	/**
	 * Cancels the watcher and closes the subscribed socket.
	 */
	public function close(): void
	{
		$this->unwatch();
		$this->closeSubscriber();
	}
}
