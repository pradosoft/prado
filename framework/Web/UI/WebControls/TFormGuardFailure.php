<?php

/**
 * TFormGuardFailure class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TFormGuardFailure class.
 *
 * TFormGuardFailure enumerates why a {@see TFormGuard} failed a submission.
 * - Honeypot: the hidden honeypot field has a value.
 * - Stamp: the signed render stamp is missing, altered, or from another guard.
 * - TooFast: the form was submitted before {@see TFormGuard::getMinFillTime MinFillTime} passed.
 * - Expired: the form was submitted after {@see TFormGuard::getMaxFillTime MaxFillTime} passed.
 * - RateLimited: the client exceeded {@see TFormGuard::getRateLimit RateLimit} submissions in the window.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFormGuardFailure extends \Prado\TEnumerable
{
	public const Honeypot = 'Honeypot';
	public const Stamp = 'Stamp';
	public const TooFast = 'TooFast';
	public const Expired = 'Expired';
	public const RateLimited = 'RateLimited';
}
