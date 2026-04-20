<?php

/**
 * Core Properties of I18N Controls.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N;

use Prado\I18N\core\CultureInfo;
use Prado\Util\TUtf8Converter;

/**
 * TI18NControlTrait trait.
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
 *   Application is used. The default is UTF-8.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @method dyDefaultCharsetValue(string $eventDefault)
 * @method dyDefaultCultureValue(string $eventDefault)
 * @since 4.3.3
 */
trait TI18NControlTrait
{
	/**
	 * Converts the text to the charset property through {@see TUtf8Converter::fromUTF8()}.
	 * @param string $text the text to convert.
	 * @return string the converted text.
	 */
	protected function convertToCharset($text)
	{
		return TUtf8Converter::fromUTF8($text, $this->getCharset());
	}

	/**
	 * Gets the charset.
	 * It is evaluated in the following order:
	 * 1) application charset in globalization,
	 * 2) the default charset in globalization
	 * 3) UTF-8
	 * @return string charset
	 */
	public function getCharset()
	{
		//instance charset
		$charset = $this->getViewState('Charset', '');

		if (empty($charset)) {
			$app = $this->getApplication();
			if ($app && ($globalize = $app->getGlobalization(false))) {
				if (!empty($appCharset = $globalize->getCharset())) {
					$charset = $appCharset;
				} elseif (!empty($appDefaultCharset = $globalize->getDefaultCharset())) {
					$charset = $appDefaultCharset;
				}
			} else {
				$charset = $this->dyDefaultCharsetValue('UTF-8');
			}
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
		//instance charset
		$culture = $this->getViewState('Culture', '');

		//fall back to globalization charset
		if (empty($culture)) {
			$app = $this->getApplication();
			if ($app && ($globalize = $app->getGlobalization(false))) {
				$culture = $globalize->getCulture();
			} else {
				$culture = $this->dyDefaultCultureValue('');
			}
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

	/**
	 * @param null|mixed $culture
	 * @return \Prado\I18N\core\CultureInfo The Culture Info for the {@see getCulture()}.
	 */
	protected function getCultureInfo($culture = null)
	{
		return CultureInfo::getCultureInfo($culture ?? $this->getCulture());
	}
}
