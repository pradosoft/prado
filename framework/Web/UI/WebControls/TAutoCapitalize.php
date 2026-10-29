<?php

/**
 * TAutoCapitalize class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAutoCapitalize enumeration.
 *
 * TAutoCapitalize defines the values of the HTML `autocapitalize` attribute, the
 * capitalization a virtual keyboard or voice input applies. Use with
 * {@see TWebControl::setAutoCapitalize}.
 * - None → `none`: no capitalization.
 * - Sentences → `sentences`: the first letter of each sentence.
 * - Words → `words`: the first letter of each word.
 * - Characters → `characters`: every letter.
 *
 * The attribute does not apply to `url`, `email`, or `password` inputs.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAutoCapitalize extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Sentences = 'Sentences';
	public const Words = 'Words';
	public const Characters = 'Characters';
}
