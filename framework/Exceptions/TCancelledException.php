<?php

/**
 * TCancelledException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * TCancelledException class.
 *
 * TCancelledException is thrown from a wait that a {@see \Prado\IO\Async\TCancellationToken}
 * cancels, from {@see \Prado\IO\Async\TFuture::await()} with a cancelled token, and from a
 * {@see \Prado\IO\Async\TAsync::timeout()} that elapses.  {@see getPrevious()} carries the
 * reason passed to {@see \Prado\IO\Async\TCancellationToken::cancel()} when one is given.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCancelledException extends TSystemException
{
}
