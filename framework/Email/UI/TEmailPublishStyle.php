<?php

/**
 * TEmailPublishStyle class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI;

/**
 * TEmailPublishStyle class.
 *
 * TEmailPublishStyle is how a {@see TEmailBody} makes the files of its template,
 * such as images, reach the recipient.
 *
 * | Style | The file is |
 * |---|---|
 * | URL | published as a web asset and referenced by an absolute URL |
 * | Attachment | added to the email inline and referenced as "cid:..." |
 * | Embed | written into the HTML as a base64 data URI |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailPublishStyle extends \Prado\TEnumerable
{
	public const URL = 'URL';
	public const Attachment = 'Attachment';
	public const Embed = 'Embed';
}
