<?php

/**
 * Base I18N component.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Xiang Wei Zhuo. 
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.1 $  $Date: 2005/08/27 03:21:12 $
 * @package System.I18N
 */


/**
 * Base class for I18N components, providing Culture and Charset properties.
 * Namespace: System.I18N
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
 * @version v1.0, last update on Sat Dec 11 15:25:11 EST 2004
 * @package System.I18N
 */
class TI18NControl extends TControl
{
	
	/**
	 * Sets the charset for message output
	 * @param string the charset, e.g. UTF-8
	 */
	public function setCharset($value)
	{
		$this->setViewState('Charset',$value,'');
	}	

	
	/**
	 * Get the specific culture for this control.
	 * @param parameter
	 * @return string culture identifier. 
	 */
	public function getCulture()
	{
		return $this->getViewState('Culture','');
	}
	
	/**
	 * Get the custom culture identifier.
	 * @param string culture identifier. 
	 */
	public function setCulture($culture)
	{
		$this->setViewState('Culture',$culture,'');
	}

	/**
	 * Gets the charset, with fall back to the application charset,
	 * then the default charset in globalization, and finally UTF-8
	 * @return string charset
	 */
	public  function getCharset()
	{
		$app = $this->Application->getGlobalization();
		
		//instance charset
		$charset = $this->getViewState('Charset','');
		
		//fall back to globalization charset
		if(empty($charset)) 
			$charset = is_null($app) ? '' : $app->Charset;
		
		//fall back to default charset
		if(empty($charset))
			$charset = (is_null($app)) ? 'UTF-8' : $app->getDefaultCharset();
				
		return $charset;
	}	
}

?>