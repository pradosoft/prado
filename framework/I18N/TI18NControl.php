<?php

/**
 * Base I18N component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N;

use Prado\Web\UI\TControl;

/**
 * TI18NControl class.
 *
 * Base class for I18N components, providing Culture and Charset properties.
 *
 * Properties
 * - <b>Culture</b>, string,
 *   <br>Gets or sets the culture for formatting. If the Culture property
 *   is not specified. The culture from the Application/Page is used.
 * - <b>Charset</b>, string,
 *   <br>Gets or sets the charset for both input and output.
 *   If the Charset property is not specified. The charset from the
 *   Application/Page is used. The default is UTF-8.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 */
class TI18NControl extends TControl
{
	use TI18NControlTrait;
}
