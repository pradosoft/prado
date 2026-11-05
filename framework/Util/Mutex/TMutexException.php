<?php

/**
 * TMutexException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TSystemException;

/**
 * TMutexException class.
 *
 * TMutexException is thrown by {@see TMutex::synchronize()} when the lock is not
 * acquired within the timeout.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMutexException extends TSystemException
{
}
