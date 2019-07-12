<?php
/**
 * Base I18N component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
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
 * @package Prado\I18N
 */
class TI18NControl extends TControl
{
	/**
	 * Gets the charset.
	 * It is evaluated in the following order:
	 * 1) application charset,
	 * 2) the default charset in globalization
	 * 3) UTF-8
	 * @return string charset
	 */
	public function getCharset()
	{
		$app = $this->getApplication()->getGlobalization(false);

		//instance charset
		$charset = $this->getViewState('Charset', '');

		//fall back to globalization charset
		if (empty($charset)) {
			$charset = ($app === null) ? '' : $app->getCharset();
		}

		//fall back to default charset
		if (empty($charset)) {
			$charset = ($app === null) ? 'UTF-8' : $app->getDefaultCharset();
		}

		return $charset;
	}

	/**
	 * Sets the charset for message output
	 * @param string $value the charset, e.g. UTF-8
	 */
	public function setCharset($value)
	{
		$this->setViewState('Charset', $value, '');
	}


	/**
	 * Get the specific culture for this control.
	 * @return string culture identifier.
	 */
	public function getCulture()
	{
		$app = $this->getApplication()->getGlobalization(false);

		//instance charset
		$culture = $this->getViewState('Culture', '');

		//fall back to globalization charset
		if (empty($culture)) {
			$culture = ($app === null) ? '' : $app->getCulture();
		}

		return $culture;
	}

	/**
	 * Get the custom culture identifier.
	 * @param string $culture culture identifier.
	 */
	public function setCulture($culture)
	{
		$this->setViewState('Culture', $culture, '');
	}
}
