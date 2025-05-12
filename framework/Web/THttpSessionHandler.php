<?php

/**
 * THttpSessionHandler class
 *
 * @author Fabio Bas <ctraltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * THttpSessionHandler class
 *
 * THttpSessionHandler contains methods called internally by PHP when using
 * {@see THttpSession::setUseCustomStorage THttpSession::UseCustomStorage}
 *
 * @author Fabio Bas <ctraltca@gmail.com>
 * @since 4.3.1
 */
class THttpSessionHandler implements \SessionHandlerInterface
{
	/**
	 * @var THttpSession
	 */
	private $_session;

	/**
	 * Constructor.
	 * @param object $session
	 * @throws TInvalidDataTypeException if the object is not a THttpSession
	 */
	public function __construct($session)
	{
		if (!($session instanceof THttpSession)) {
			throw new TInvalidDataTypeException(500, 'httpsession_handler_invalid');
		}

		$this->_session = $session;
	}

	/**
	 * Session close handler.
	 * @return bool whether session is closed successfully
	 */
	public function close(): bool
	{
		return $this->_session->_close();
	}

	/**
	 * Session destroy handler.
	 * @param string $id session ID
	 * @return bool whether session is destroyed successfully
	 */
	public function destroy(string $id): bool
	{
		return $this->_session->_destroy($id);
	}

	/**
	 * Session GC (garbage collection) handler.
	 * @param int $max_lifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
	 * @return false|int whether session is GCed successfully
	 */
	public function gc(int $max_lifetime): int|false
	{
		return $this->_session->_gc($max_lifetime);
	}

	/**
	 * Session open handler.
	 * @param string $path session save path
	 * @param string $name session name
	 * @return bool whether session is opened successfully
	 */
	public function open(string $path, string $name): bool
	{
		return $this->_session->_open($path, $name);
	}

	/**
	 * Session read handler.
	 * @param string $id session ID
	 * @return false|string the session data
	 */
	public function read(string $id): string|false
	{
		return $this->_session->_read($id);
	}

	/**
	 * Session write handler.
	 * @param string $id session ID
	 * @param string $data session data
	 * @return bool whether session write is successful
	 */
	public function write(string $id, string $data): bool
	{
		return $this->_session->_write($id, $data);
	}
}
