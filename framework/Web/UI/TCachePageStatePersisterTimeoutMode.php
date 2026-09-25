<?php

/**
 * TCachePageStatePersisterTimeoutMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * TCachePageStatePersisterTimeoutMode class.
 *
 * The enumerable type for {@see TCachePageStatePersister::setCacheTimeoutMode()
 * TCachePageStatePersister.CacheTimeoutMode}: where the lifetime of a cached page state
 * comes from.  A page state is needed for as long as its page can still be posted back,
 * so a lifetime shorter than the user's login turns a postback into a "page state
 * corrupted" error.
 *
 * | Value | Lifetime of a page state saved now |
 * |-------|------------------------------------|
 * | `Fixed` | {@see TCachePageStatePersister::getCacheTimeout() CacheTimeout} |
 * | `Session` | the session module's `Timeout`, or `CacheTimeout` without one |
 * | `Auth` | the auth manager's `AuthExpire` for an authenticated user, or `CacheTimeout` |
 * | `Auto` | `Auth`, then `Session`, then `CacheTimeout` |
 *
 * `AuthExpire` applies only when it is above 0 and `AllowAutoLogin` is off, since an
 * auto-login cookie renews an expired login.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCachePageStatePersisterTimeoutMode extends \Prado\TEnumerable
{
	public const Fixed = 'Fixed';
	public const Session = 'Session';
	public const Auth = 'Auth';
	public const Auto = 'Auto';
}
