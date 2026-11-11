<?php

/**
 * TEmailDisplayStyle class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI;

/**
 * TEmailPublishStyle class.
 *
 * TEmailPublishStyle defines the enumerable type for the possible styles of encoding files
 * into emails.  Files can be published one of 3 different ways: 1) through the website using
 * absolute URLs in the to refer to the file; saving bandwidth.  2) Files are attached in the
 * email.  3) File data is embedded into the source.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailPublishStyle extends \Prado\TEnumerable
{
	public const URL = 'URL';
	public const Attachment = 'Attachment';
	public const Embed = 'Embed';
}
