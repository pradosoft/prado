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
	private $_detectedLanguage;

	public function init($xml)
	{
		parent::init($xml);

		//set the culture according to browser language settings
		$http = new core\HTTPNegotiator();
		$languages = $http->getLanguages();
		if (count($languages) > 0) {
			$this->_detectedLanguage = $languages[0];
			$this->setCulture($languages[0]);
		}
	}

	public function getDetectedLanguage()
	{
		return $this->_detectedLanguage;
	}
}
