<?php

/**
 * TI18NWebControl class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\I18N\TI18NControlTrait;

/**
 * TI18NWebControl class.
 *
 * TI18NWebControl is a base class for web controls that require
 * internationalization (I18N) support. It extends {@see TWebControl} and
 * mixes in {@see TI18NControlTrait}, providing the following properties:
 *
 * - <b>Culture</b>, string — BCP 47 locale tag used for number/date formatting.
 *   Falls back to the application globalization culture when not set.
 * - <b>Charset</b>, string — character encoding for output conversion.
 *   Falls back to the application globalization charset, then UTF-8.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TI18NWebControl extends TWebControl
{
	use TI18NControlTrait;
}
