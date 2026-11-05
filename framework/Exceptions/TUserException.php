<?php

/**
 * TUserException file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * TUserException class
 *
 * TUserException is the base class for exceptions whose message is written for the
 * site visitor, usually because of a mistake the visitor made. It implements
 * {@see IUserException}, so {@see TErrorHandler} shows its message on the error page
 * in every application mode. The application translates the message at the throw
 * site, for example `new TUserException(Prado::localize('Your session has expired.'))`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TUserException extends TApplicationException implements IUserException
{
}
