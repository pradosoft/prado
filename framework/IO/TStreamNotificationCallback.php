<?php
/**
 * TStreamNotificationCallback class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Collections\TWeakCallableCollection;
use Prado\Prado;

/**
 * TStreamNotificationCallback class.
 *
 * This class is used to listen into the connections of fopen(), file_get_contents(),
 * and file_put_contents() by patching into context 'notification' parameter
 * callback.
 *
 * This class is an invokable callback that is notified as the connection unfolds.
 * Of particular use is listening to the progress of data transfer with {@see \Prado\IO\TStreamNotificationCallback::onProgress()}
 * The {@see \Prado\IO\TStreamNotificationCallback::filterStreamContext()}
 * method can accept a TStreamNotificationCallback or an array of options that includes
 * "notification".  The array of options are the options for stream_context_create
 * and can include events as keys and event handlers (or an array of Event Handlers)
 * to patch into the "notification" callback.  Within the array of options, "notification"
 * can be a TStreamNotificationCallback or an array of events as keys and event
 * handlers as values.
 *
 * The following event handlers are available to listen to the file connection and
 * transfer:
 *   - onResolve: Raised when the Notification Code is STREAM_NOTIFY_RESOLVE.
 *   - onConnected: Raised when the Notification Code is STREAM_NOTIFY_CONNECT.
 *   - onAuthRequired: Raised when the Notification Code is STREAM_NOTIFY_AUTH_REQUIRED.
 *   - onAuthResult: Raised when the Notification Code is STREAM_NOTIFY_AUTH_RESULT.
 *   - onRedirected: Raised when the Notification Code is STREAM_NOTIFY_REDIRECTED.
 *   - onMimeType: Raised when the Notification Code is STREAM_NOTIFY_MIME_TYPE_IS.
 *       This parses the $message Mime Type and Charset and is retrievable with
 *       {@see \Prado\IO\TStreamNotificationCallback::getMimeType()} and {@see \Prado\IO\TStreamNotificationCallback::getCharset()}.
 *   - onFileSize: Raised when the Notification Code is STREAM_NOTIFY_FILE_SIZE_IS.
 *       This stores the file size and is retrievable with {@see \Prado\IO\TStreamNotificationCallback::getFileSize()}.
 *   - onProgress: Raised when the Notification Code is STREAM_NOTIFY_PROGRESS.
 *       This stores the bytes Transferred and is retrievable with {@see \Prado\IO\TStreamNotificationCallback::getBytesTransferred()}.
 *   - onCompleted: Raised when the Notification Code is STREAM_NOTIFY_COMPLETED.
 *		 This sets the {@see \Prado\IO\TStreamNotificationCallback::getIsCompleted()} to true (from false).
 *   - onFailure: Raised when the Notification Code is STREAM_NOTIFY_FAILURE.
 *		 This sets the {@see \Prado\IO\TStreamNotificationCallback::getIsFailure()} to true (from
 *       false) and stores the Message Code for retrieval with {@see \Prado\IO\TStreamNotificationCallback::getMessageCode()}.
 *
 * These events pass the {@see \Prado\IO\TStreamNotificationParameter} as the parameter. It
 * contains the arguments of the Stream Notification Callback and is reused in each
 * notification.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TStreamNotificationCallback extends \Prado\TComponent
{
	public const NOTIFICATION = 'notification';

	/** @var ?TWeakCallableCollection The registered direct callbacks. */
	private ?TWeakCallableCollection $_callbacks = null;

	/** @var ?int The severity of the failure. */
	private ?int $_severity = null;

	/** @var ?string The message of the notification. */
	private ?string $_message = null;

	/** @var ?int The message code of the failure. */
	private ?int $_messageCode = null;

	/** @var ?int The bytes transferred. */
	private ?int $_bytesTrasferred = null;

	/** @var ?int The total bytes of the file. */
	private ?int $_fileSize = null;

	/** @var ?string The mime type of the file. */
	private ?string $_mimeType = null;

	/** @var ?string The charset in the mime type of the file. */
	private ?string $_charset = null;

	/** @var bool Was the completed notification code sent. */
	private bool $_completed = false;

	/** @var bool Was the failure notification code sent. */
	private bool $_failure = false;

	/**
	 * @var ?TStreamNotificationParameter The Parameter with the callback arguments
	 *   for the events.
	 */
	private ?TStreamNotificationParameter $_parameter = null;

	/**
	 * This converts the input TStreamNotificationCallback or array into a proper stream
	 * context with options and parameters (notification).  If $context is an array
	 * this can use or convert the "notification" into a stream context parameter.
	 * When "notifications" are an array, the keys are events for the TStreamNotificationCallback
	 * and values could be a single event handler callback or an array of event handler
	 * callbacks.
	 * ```php
	 *   $callback = new TStreamNotificationCallback();
	 *   $callback->onConnected[] = function($sender, $param) {...};
	 *   $context = TStreamNotificationCallback::filterStreamContext($callback);
	 *   $context = TStreamNotificationCallback::filterStreamContext(['notification' => $callback,
	 * 				'onProgress' => [$this, 'progressHandler'], 'onFileSize' =>
	 *					[new TEventHandler([$this, 'fileSizeHandler'], $someData), ...]);
	 *   // if you don't need the TStreamNotificationCallback, but get the callback as the event handler $sender
	 *   $context = TStreamNotificationCallback::filterStreamContext(['notification' => [
	 * 				'onProgress' => [$this, 'progressHandler'], 'onFileSize' =>
	 *					[new TEventHandler([$this, 'fileSizeHandler'], $someData), ...],
	 * 				'http' => [...], ...]);
	 *   $context = TStreamNotificationCallback::filterStreamContext([
	 * 				'onProgress' => [$this, 'progressHandler'], 'onFileSize' =>
	 *					[new TEventHandler([$this, 'fileSizeHandler'], $someData), ...],
	 * 				'http' => [...], 'onMimeType' => function($sender, $param) {...},
	 *				'onFileSize' => [$behavior, 'otherHandler']]);
	 *	$stream = fopen($url, 'rb', false, $context);
	 * ```
	 *
	 * If Event Handlers for TStreamNotificationCallback are in the array, and the
	 * TStreamNotificationCallback notification is not instanced, it will be created.
	 * If the "notification" is a callable that is not a TStreamNotificationCallback
	 * then the "notification" callable is wrapped in a TStreamNotificationCallback.
	 * @param mixed $context A Stream Context, TStreamNotificationCallback, or array
	 *   of options with notification.
	 * @return mixed The Streaming Context.
	 */
	public static function filterStreamContext(mixed $context): mixed
	{
		if (is_callable($context)) {
			$context = stream_context_create(null, [self::NOTIFICATION => $context]);
		} elseif (is_array($context)) {
			$notification = array_key_exists(self::NOTIFICATION, $context) ? $context[self::NOTIFICATION] : null;
			unset($context[self::NOTIFICATION]);
			if (is_array($notification)) {
				$notification['class'] ??= TStreamNotificationCallback::class;
				$notification = Prado::createComponent($notification);
			}
			$contextKeys = $context;
			$contextKeys = array_change_key_case($contextKeys);
			$contextEvents = array_intersect_key($contextKeys, ['onresolve' => true, 'onauthrequired' => true, 'onfailure' => true, 'onauthresult' => true, 'onredirect' => true, 'onconnected' => true, 'onfilesize' => true, 'onmimetype' => true, 'onprogress' => true, 'oncompleted' => true]);
			if (!empty($contextEvents)) {
				if (empty($notification)) {
					$notification = new TStreamNotificationCallback();
				} elseif (!($notification instanceof TStreamNotificationCallback)) {
					$notification = new TStreamNotificationCallback($notification);
				}
			}
			if ($notification instanceof TStreamNotificationCallback) {
				foreach($context as $property => $value) {
					if (property_exists($notification, $property) || $notification->canSetProperty($property) || $notification->hasEvent($property)) {
						$notification->setSubProperty($property, $value);
						unset($context[$property]);
					}
				}
			}
			$param = null;
			if($notification) {
				$param = [self::NOTIFICATION => $notification];
			}
			if (empty($context)) {
				$context = null;
			}
			$context = stream_context_create($context, $param);
		}
		return $context;
	}

	/**
	 * Given a Stream Context, this returns that stream context "notification" callback.
	 * In this context, it is likely a TStreamNotificationCallback.
	 * @param mixed $context The Stream Context to retrieve the "notification" callback from.
	 * @return mixed The "notification" callback or null if nothing.
	 */
	public static function getContextNotificationCallback(mixed $context): mixed
	{
		$params = stream_context_get_params($context);

		return $params[self::NOTIFICATION] ?? null;
	}

	/**
	 * The registers the callbacks.
	 * @param array $args Callable or null
	 */
	public function __construct(...$args)
	{
		if (count($args)) {
			$callbacks = $this->getCallbacks();
			$callbacks->mergeWith($args);
		}
		parent::__construct();
	}

	/**
	 * This holds any direct notification callbacks so multiple callbacks are supported.
	 * @return TWeakCallableCollection The direct notification callbacks.
	 */
	public function getCallbacks(): TWeakCallableCollection
	{
		if (!$this->_callbacks) {
			$this->_callbacks = new TWeakCallableCollection();
		}
		return $this->_callbacks;
	}

	/**
	 * @return ?int The severity of the failure.
	 */
	public function getSeverity(): ?int
	{
		return $this->_severity;
	}

	/**
	 * @return ?string The Message from the notification.
	 */
	public function getMessage(): ?string
	{
		return $this->_message;
	}

	/**
	 * @return ?int The Message Code from the failure.
	 */
	public function getMessageCode(): ?int
	{
		return $this->_messageCode;
	}

	/**
	 * @return ?int The total bytes transferred of the streaming file.
	 */
	public function getBytesTransferred(): ?int
	{
		return $this->_bytesTrasferred;
	}

	/**
	 * @return ?int The File Size of the streaming file.
	 */
	public function getFileSize(): ?int
	{
		return $this->_fileSize;
	}

	/**
	 * @return ?string The MimeType of the file.
	 */
	public function getMimeType(): ?string
	{
		return $this->_mimeType;
	}

	/**
	 * @return ?string The charset of the MimeType.
	 */
	public function getCharset(): ?string
	{
		return $this->_charset;
	}

	/**
	 * @return bool Was the Notification Code STREAM_NOTIFY_COMPLETED raised.
	 */
	public function getIsCompleted(): bool
	{
		return $this->_completed;
	}

	/**
	 * @return bool Was the Notification Code STREAM_NOTIFY_FAILURE at any point.
	 */
	public function getIsFailure(): bool
	{
		return $this->_failure;
	}

	/**
	 * @return ?TStreamNotificationParameter The event parameter object.
	 */
	public function getParameter(): ?TStreamNotificationParameter
	{
		return $this->_parameter;
	}

	/**
	 * The callback for stream notifications.
	 * @param int $notification_code One of the STREAM_NOTIFY_* notification constants.
	 * @param int $severity One of the STREAM_NOTIFY_SEVERITY_* notification constants.
	 * @param ?string $message Passed if a descriptive message is available for the event.
	 * @param int $message_code Passed if a descriptive message code is available for the event.
	 * @param int $bytes_transferred If applicable, the bytes_transferred will be populated.
	 * @param int $bytes_max If applicable, the bytes_max will be populated.
	 */
	public function __invoke(int $notification_code, int $severity, ?string $message, int $message_code, int $bytes_transferred, int $bytes_max): void
	{
		$this->_message = $message;
		if (!$this->_parameter) {
			$this->_parameter = new TStreamNotificationParameter($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
		} else {
			$this->_parameter->setNotificationCode($notification_code);
			$this->_parameter->setSeverity($severity);
			$this->_parameter->setMessage($message);
			$this->_parameter->setMessageCode($message_code);
			$this->_parameter->setBytesTransferred($bytes_transferred);
			$this->_parameter->setBytesMax($bytes_max);
		}
		switch($notification_code) {
			case STREAM_NOTIFY_RESOLVE: // value: 1
				$this->onResolve($this->_parameter);
				break;

			case STREAM_NOTIFY_CONNECT: // value: 2
				$this->onConnected($this->_parameter);
				break;

			case STREAM_NOTIFY_AUTH_REQUIRED: // value: 3
				$this->onAuthRequired($this->_parameter);
				break;

			case STREAM_NOTIFY_AUTH_RESULT: // value: 10
				$this->onAuthResult($this->_parameter);
				break;

			case STREAM_NOTIFY_REDIRECTED: // value: 6
				$this->onRedirected($this->_parameter);
				break;

			case STREAM_NOTIFY_MIME_TYPE_IS: // value: 4
				if (strpos($message, ';') !== false) {
					$mimeData = explode(';', $message, 2);
					$message = $mimeData[0];
					if (strpos($mimeData[1] ?? '', '=') !== false) {
						$this->_charset = explode('=', $mimeData[1])[1];
					}
				}
				$this->_mimeType = $message;
				$this->onMimeType($this->_parameter);
				break;

			case STREAM_NOTIFY_FILE_SIZE_IS: // value: 5
				$this->_bytesTrasferred = $bytes_transferred;
				$this->_fileSize = $bytes_max;
				$this->onFileSize($this->_parameter);
				break;

			case STREAM_NOTIFY_PROGRESS: // value: 7
				$this->_bytesTrasferred = $bytes_transferred;
				$this->_fileSize = $bytes_max;
				$this->onProgress($this->_parameter);
				break;

			case STREAM_NOTIFY_COMPLETED: // value: 8
				$this->_completed = true;
				$this->onCompleted($this->_parameter);
				break;

			case STREAM_NOTIFY_FAILURE: // value: 9
				$this->_failure = true;
				$this->_severity = $severity;
				$this->_messageCode = $message_code;
				$this->onFailure($this->_parameter);
				break;
		}
		if ($this->_callbacks && $this->_callbacks->getCount()) {
			foreach($this->_callbacks as $callback) {
				$callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
			}
		}
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_RESOLVE.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onResolve(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onResolve', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_CONNECT.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onConnected(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onConnected', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_AUTH_REQUIRED.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onAuthRequired(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onAuthRequired', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_AUTH_RESULT.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onAuthResult(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onAuthResult', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_REDIRECTED.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onRedirected(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onRedirected', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_MIME_TYPE_IS.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onMimeType(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onMimeType', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_FILE_SIZE_IS.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onFileSize(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onFileSize', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_PROGRESS.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onProgress(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onProgress', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_COMPLETED.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onCompleted(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onCompleted', $this, $param);
	}

	/**
	 * Raised when the Notification Code is STREAM_NOTIFY_FAILURE.
	 * @param ?TStreamNotificationParameter $param
	 */
	public function onFailure(?TStreamNotificationParameter $param): array
	{
		return $this->raiseEvent('onFailure', $this, $param);
	}
}
