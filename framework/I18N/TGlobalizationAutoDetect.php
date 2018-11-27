<?php
/**
 * TMultiView and TView class file.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
 */

namespace Prado\I18N;

/**
 * TGlobalizationAutoDetect class will automatically try to resolve the default
 * culture using the user browser language settings.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @package Prado\I18N
 */
class TGlobalizationAutoDetect extends TGlobalization
{
	/**
	 * A list of languages accepted by the browser.
	 * @var array
	 */
	protected $languages;

	/**
	 * A list of charsets accepted by the browser
	 * @var array
	 */
	protected $charsets;

	/**
	 * First language accepted by the browser
	 * @var string
	 */
	private $_detectedLanguage;

	public function init($xml)
	{
		parent::init($xml);

		//set the culture according to browser language settings
		$languages = $this->getLanguages();
		if (count($languages) > 0) {
			$this->_detectedLanguage = $languages[0];
			$this->setCulture($languages[0]);
		}
	}

	public function getDetectedLanguage()
	{
		return $this->_detectedLanguage;
	}

	/**
	 * Checks wether the specified locale is valid and available
	 * @return bool
	 */
	protected function getIsValidLocale($locale)
	{
		static $allLocales;
		if($allLocales === null)
		{
			$all = \ResourceBundle::getLocales('');
		}
		return in_array($locale, $all);
	}

	/**
	 * Get a list of languages acceptable by the client browser
	 * @return array languages ordered in the user browser preferences.
	 */
	protected function getLanguages()
	{
		if ($this->languages !== null) {
			return $this->languages;
		}

		$this->languages = [];

		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return $this->languages;
		}

		foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
			// Cut off any q-value that might come after a semi-colon
			if ($pos = strpos($lang, ';')) {
				$lang = trim(substr($lang, 0, $pos));
			}

			if (strstr($lang, '-')) {
				$codes = explode('-', $lang);
				if ($codes[0] == 'i') {
					// Language not listed in ISO 639 that are not variants
					// of any listed language, which can be registerd with the
					// i-prefix, such as i-cherokee
					if (count($codes) > 1) {
						$lang = $codes[1];
					}
				} else {
					for ($i = 0, $k = count($codes); $i < $k; ++$i) {
						if ($i == 0) {
							$lang = strtolower($codes[0]);
						} else {
							$lang .= '_' . strtoupper($codes[$i]);
						}
					}
				}
			}

			if ($this->getIsValidLocale($lang)) {
				$this->languages[] = $lang;
			}
		}

		return $this->languages;
	}

	/**
	 * Get a list of charsets acceptable by the client browser.
	 * @return array list of charsets in preferable order.
	 */
	protected function getCharsets()
	{
		if ($this->charsets !== null) {
			return $this->charsets;
		}

		$this->charsets = [];

		if (!isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
			return $this->charsets;
		}

		foreach (explode(',', $_SERVER['HTTP_ACCEPT_CHARSET']) as $charset) {
			if (!empty($charset)) {
				$this->charsets[] = preg_replace('/;.*/', '', $charset);
			}
		}

		return $this->charsets;
	}
}
