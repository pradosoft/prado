<?php

/**
 * IUserException interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

/**
 * IUserException interface
 *
 * IUserException marks an exception whose message is written for the site visitor.
 * {@see \Prado\Exceptions\TErrorHandler} shows the message of an IUserException on the
 * error page in every application mode. The message of any other exception is shown
 * in Debug mode only; in the other modes the visitor sees a generic sentence and the
 * exception is logged.
 *
 * {@see THttpException} and {@see TUserException} implement it. An application
 * exception implements it, or extends TUserException, when its message is meant
 * for the visitor. The message is translated at the throw site, for example with
 * {@see \Prado\Prado::localize()}, or through a message file keyed by error code.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IUserException
{
}
