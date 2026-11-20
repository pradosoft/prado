<?php

/**
 * TEmailSendMode class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

/**
 * TEmailSendMode class.
 *
 * TEmailSendMode says where the first delivery attempt of an email happens.
 *
 * | Mode | First attempt |
 * |---|---|
 * | Immediate | Inside the send call. |
 * | Deferred | In the same process at the end of the request, after the response. |
 * | Queued | By the cron task that sends the stored emails. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailSendMode extends \Prado\TEnumerable
{
	public const Immediate = 'Immediate';
	public const Deferred = 'Deferred';
	public const Queued = 'Queued';
}
