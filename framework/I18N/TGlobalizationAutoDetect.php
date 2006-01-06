<?php

Prado::using('System.I18N.core.HTTPNegotiator');

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TGlobalizationAutoDetect extends TGlobalization
{
	public function init($xml)
	{
		parent::init($xml);

		//set the culture according to browser language settings
		$http = new HTTPNegotiator();		
		$languages = $http->getLanguages();
		if(count($languages) > 0)
			$this->Culture = $languages[0];
	}
}

?>