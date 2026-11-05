<?php

/**
 * TReferrerPolicy class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TReferrerPolicy class.
 *
 * TReferrerPolicy defines the HTML `referrerpolicy` attribute of {@see TImage},
 * {@see TInlineFrame}, and {@see THyperLink}: the `Referer` header their request sends.
 * - NotSet → no attribute; the page policy applies (by default StrictOriginWhenCrossOrigin).
 * - NoReferrer → `no-referrer`: no header.
 * - NoReferrerWhenDowngrade → `no-referrer-when-downgrade`: the full URL, except from HTTPS to HTTP.
 * - Origin → `origin`: the origin only.
 * - OriginWhenCrossOrigin → `origin-when-cross-origin`: the full URL to the same origin, the origin otherwise.
 * - SameOrigin → `same-origin`: the full URL to the same origin, nothing otherwise.
 * - StrictOrigin → `strict-origin`: the origin, except from HTTPS to HTTP.
 * - StrictOriginWhenCrossOrigin → `strict-origin-when-cross-origin`: the full URL to the
 *   same origin, the origin to others, nothing from HTTPS to HTTP.
 * - UnsafeUrl → `unsafe-url`: the full URL always, including to HTTP.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TReferrerPolicy extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const NoReferrer = 'NoReferrer';
	public const NoReferrerWhenDowngrade = 'NoReferrerWhenDowngrade';
	public const Origin = 'Origin';
	public const OriginWhenCrossOrigin = 'OriginWhenCrossOrigin';
	public const SameOrigin = 'SameOrigin';
	public const StrictOrigin = 'StrictOrigin';
	public const StrictOriginWhenCrossOrigin = 'StrictOriginWhenCrossOrigin';
	public const UnsafeUrl = 'UnsafeUrl';

	/**
	 * Returns the attribute value of a policy, such as `no-referrer` for NoReferrer.
	 * @param string $policy a TReferrerPolicy other than NotSet
	 * @return string the `referrerpolicy` attribute value
	 */
	public static function toAttributeValue(string $policy): string
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $policy));
	}
}
