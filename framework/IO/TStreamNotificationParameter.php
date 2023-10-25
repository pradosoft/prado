<?php
/**
 * TStreamNotificationParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

/**
 * TStreamNotificationParameter class.
 *
 * This class parameterizes the Stream Notification Callback arguments into a structure
 * for raising PRADO Stream Notification Events.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 * @link https://www.php.net/manual/en/function.stream-notification-callback.php
 */
class TStreamNotificationParameter extends \Prado\TEventParameter
{
	/** @var int One of the STREAM_NOTIFY_* notification constants. */
	private int $_notification_code;

	/** @var int One of the STREAM_NOTIFY_SEVERITY_* notification constants. */
	private int $_severity;

	/** @var ?string Passed if a descriptive message is available for the event. */
	private ?string $_message;

	/** @var int Passed if a descriptive message code is available for the event. */
	private int $_message_code;

	/** @var int If applicable, the bytes_transferred will be populated. */
	private int $_bytes_transferred;

	/** @var int If applicable, the bytes_max will be populated. */
	private int $_bytes_max;

	/**
	 * @param int $notification_code One of the STREAM_NOTIFY_* notification constants.
	 * @param int $severity One of the STREAM_NOTIFY_SEVERITY_* notification constants.
	 * @param ?string $message Passed if a descriptive message is available for the event.
	 * @param int $message_code Passed if a descriptive message code is available for the event.
	 * @param int $bytes_transferred If applicable, the bytes_transferred will be populated.
	 * @param int $bytes_max If applicable, the bytes_max will be populated.
	 */
	public function __construct(int $notification_code = 0, int $severity = 0, ?string $message = null, int $message_code = 0, int $bytes_transferred = 0, int $bytes_max = 0)
	{
		$this->setNotificationCode($notification_code);
		$this->setSeverity($severity);
		$this->setMessage($message);
		$this->setMessageCode($message_code);
		$this->setBytesTransferred($bytes_transferred);
		$this->setBytesMax($bytes_max);
		parent::__construct();
	}

	/**
	 * @return int One of the STREAM_NOTIFY_* notification constants.
	 */
	public function getNotificationCode(): int
	{
		return $this->_notification_code;
	}

	/**
	 * @param int $value One of the STREAM_NOTIFY_* notification constants.
	 */
	public function setNotificationCode(int $value): void
	{
		$this->_notification_code = $value;
	}

	/**
	 * @return int One of the STREAM_NOTIFY_SEVERITY_* notification constants.
	 */
	public function getSeverity(): int
	{
		return $this->_severity;
	}

	/**
	 * @param int $value One of the STREAM_NOTIFY_SEVERITY_* notification constants.
	 */
	public function setSeverity(int $value): void
	{
		$this->_severity = $value;
	}

	/**
	 * @return ?string Passed if a descriptive message is available for the event.
	 */
	public function getMessage(): ?string
	{
		return $this->_message;
	}

	/**
	 * @param ?string $value Passed if a descriptive message is available for the event.
	 */
	public function setMessage(?string $value): void
	{
		$this->_message = $value;
	}

	/**
	 * @return int Passed if a descriptive message code is available for the event.
	 */
	public function getMessageCode(): int
	{
		return $this->_message_code;
	}

	/**
	 * @param int $value Passed if a descriptive message code is available for the event.
	 */
	public function setMessageCode(int $value): void
	{
		$this->_message_code = $value;
	}

	/**
	 * @return int If applicable, the bytes_transferred will be populated.
	 */
	public function getBytesTransferred(): int
	{
		return $this->_bytes_transferred;
	}

	/**
	 * @param int $value If applicable, the bytes_transferred will be populated.
	 */
	public function setBytesTransferred(int $value): void
	{
		$this->_bytes_transferred = $value;
	}

	/**
	 * @return int If applicable, the bytes_max will be populated.
	 */
	public function getBytesMax(): int
	{
		return $this->_bytes_max;
	}

	/**
	 * @param int $value If applicable, the bytes_max will be populated.
	 */
	public function setBytesMax(int $value): void
	{
		$this->_bytes_max = $value;
	}
}
