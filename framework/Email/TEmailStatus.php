<?php

/**
 * TEmailStatus class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * TEmailStatus class.
 *
 * TEmailStatus is the delivery status of a {@see TEmail}.
 *
 * | Status | Meaning |
 * |---|---|
 * | New | The email has not been sent. |
 * | Queued | The email is stored for a later attempt. |
 * | Sending | A process has claimed the email and is delivering it. |
 * | Sent | A transport accepted the email. |
 * | Failed | Delivery failed and is not retried. |
 * | Suppressed | Every recipient is on the suppression list. |
 * | Cancelled | An onSendEmail handler stopped the email. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailStatus extends \Prado\TEnumerable
{
	public const New = 'New';
	public const Queued = 'Queued';
	public const Sending = 'Sending';
	public const Sent = 'Sent';
	public const Failed = 'Failed';
	public const Suppressed = 'Suppressed';
	public const Cancelled = 'Cancelled';
}
