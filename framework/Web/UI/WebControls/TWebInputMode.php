<?php

/**
 * TWebInputMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWebInputMode enumeration.
 *
 * TWebInputMode defines the allowed values for the HTML 5 `inputmode` attribute,
 * which hints at the type of virtual keyboard to display for a form field.
 * Use with {@see TWebControl::setInputMode}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TWebInputMode extends \Prado\TEnumerable
{
	/** No virtual keyboard; the page provides its own input control. */
	public const None = 'None';
	/** Fractional numeric input; shows digits and the locale decimal separator. */
	public const Decimal = 'Decimal';
	/** Integer numeric input; shows digits only, no decimal separator. */
	public const Numeric = 'Numeric';
	/** Telephone-number input; shows a dial-pad keyboard. */
	public const Tel = 'Tel';
	/** URL input; shows keys for entering a web address (e.g. `/` and `.`). */
	public const Url = 'Url';
	/** E-mail address input; shows keys for e-mail entry (e.g. `@` and `.`). */
	public const Email = 'Email';
	/** Standard text input; the default virtual keyboard. */
	public const Text = 'Text';
	/** Search input; shows a "Search" or "Go" action key. */
	public const Search = 'Search';
}
