<?php
/**
 * TUnknownMethodException class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * TUnknownMethodException class
 *
 * TUnknownMethodException is raised when the method being called cannot be found
 * in {@see \Prado\TComponent::__call} and {@see \Prado\TComponent::__callStatic}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TUnknownMethodException extends TSystemException
{
}
