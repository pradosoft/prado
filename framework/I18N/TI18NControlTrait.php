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
	 * Returns the CultureInfo of a culture, or of {@see getCulture()} when the culture is null.
	 * @param ?string $culture the culture identifier, null for {@see getCulture()}.
	 * @return \Prado\I18N\core\CultureInfo the culture information.
	 */
	protected function getCultureInfo($culture = null)
	{
		return CultureInfo::getCultureInfo($culture ?? $this->getCulture());
	}

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
	 * 1) the Charset property of this control,
	 * 2) the application charset in globalization,
	 * 3) the default charset in globalization,
	 * 4) the result of the `dyDefaultCharsetValue` filter of 'UTF-8'.
	 * @return string charset
	 */
	public function getCharset()
	{
		$charset = $this->getViewState('Charset', '');

		if (empty($charset)) {
			$app = $this->getApplication();
			if ($app && ($globalize = $app->getGlobalization(false))) {
				$charset = $globalize->getCharset();
				if (empty($charset)) {
					$charset = $globalize->getDefaultCharset();
				}
			}
			if (empty($charset)) {
				$charset = $this->callBehaviorsFilter('dyDefaultCharsetValue', 'UTF-8');
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
	 * Gets the culture.
	 * It is evaluated in the following order:
	 * 1) the Culture property of this control,
	 * 2) the application culture in globalization,
	 * 3) the result of the `dyDefaultCultureValue` filter of ''.
	 * @return string culture identifier.
	 */
	public function getCulture()
	{
		$culture = $this->getViewState('Culture', '');

		if (empty($culture)) {
			$app = $this->getApplication();
			if ($app && ($globalize = $app->getGlobalization(false))) {
				$culture = $globalize->getCulture();
			}
			if (empty($culture)) {
				$culture = $this->callBehaviorsFilter('dyDefaultCultureValue', '');
			}
		}

		return $culture;
	}

	/**
	 * Sets the culture for this control.
	 * @param string $culture culture identifier.
	 */
	public function setCulture($culture)
	{
		$this->setViewState('Culture', $culture, '');
	}
}
