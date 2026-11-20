<?php

/**
 * TEmailSuppressionReason class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Store;

/**
 * TEmailSuppressionReason class.
 *
 * TEmailSuppressionReason is why an address is on the suppression list of an {@see IEmailStore}.
 *
 * | Reason | Cause |
 * |---|---|
 * | Unsubscribe | The recipient unsubscribed from a mailing list. |
 * | Bounce | A mail server permanently refused the address. |
 * | Complaint | The recipient reported an email as spam. |
 * | Manual | The application or an administrator added the address. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailSuppressionReason extends \Prado\TEnumerable
{
	public const Unsubscribe = 'Unsubscribe';
	public const Bounce = 'Bounce';
	public const Complaint = 'Complaint';
	public const Manual = 'Manual';
}
