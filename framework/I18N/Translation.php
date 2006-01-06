<?php

/**
 * Translation, static.
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
 * @version $Revision: 1.9 $  $Date: 2005/12/17 06:11:28 $
 * @package System.I18N
 */

 /**
 * Get the MessageFormat class.
 */
Prado::using('System.I18N.core.MessageFormat');
 

/**
 * Translation class.
 * 
 * Provides translation using a static MessageFormatter.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Tue Dec 28 11:54:48 EST 2004
 * @package System.I18N
 */
class Translation extends TComponent
{
	

	/**
	 * The string formatter. This is a class static variable.
	 * @var MessageFormat 
	 */	
	protected static $formatter;
	
	/**
	 * Initialize the TTranslate translation components
	 */
	public static function init()
	{	
		//initialized the default class wide formatter
		if(is_null(self::$formatter))
		{
			$app = Prado::getApplication()->getGlobalization();
			$config = $app->getTranslationConfiguration();
			$source = MessageSource::factory($config['type'],
											$config['source'],
											$config['filename']);
											
			$source->setCulture($app->Culture);
			
			if($config['cache'])
				$source->setCache(new MessageCache($config['cache']));
			
			self::$formatter = new MessageFormat($source, $app->Charset);

			//save the message on end request
			Prado::getApplication()->attachEventHandler(
				'EndRequest', array('Translation', 'saveMessages'));
		}			
	}
	
	/**
	 * Get the static formatter from this component.
	 * @return MessageFormat formattter.	 
	 * @see localize()
	 */
	public static function formatter()
	{
		return self::$formatter;
	}
	
	/**
	 * Save untranslated messages to the catalogue.
	 */
	public static function saveMessages()
	{
		static $onceonly = true;
		
		if($onceonly && !is_null($formatter = self::$formatter))
		{
			$app = Prado::getApplication()->getGlobalization();
			$config = $app->getTranslationConfiguration();
			if(isset($config['autosave']))
			{								
				$formatter->getSource()->setCulture($app->Culture);
				$formatter->getSource()->save($config['catalogue']);
			}
			$onceonly = false;
		}		
	}	
}

/**
 * Localize a text to the locale/culture specified in the globalization handler.
 * @param string text to be localized.
 * @param array a set of parameters to substitute.
 * @param string a different catalogue to find the localize text.
 * @param string the input AND output charset.
 * @return string localized text. 
 * @see TTranslate::formatter()
 * @see TTranslate::init()
 */
function localize($text, $parameters=array(), $catalogue=null, $charset=null)
{
	
	$app = Prado::getApplication()->getGlobalization();
	
	$params = array();
	foreach($parameters as $key => $value)
		$params['{'.$key.'}'] = $value;

	//no translation handler provided
	if(is_null($config = $app->getTranslationConfiguration()))
		return strtr($text, $params);

	Translation::init();

	if(empty($catalogue) && isset($config['catalogue']))
		$catalogue = $config['catalogue'];
		
	//globalization charset
	$appCharset = is_null($app) ? '' : $app->Charset; 
		
	//default charset
	$defaultCharset = (is_null($app)) ? 'UTF-8' : $app->getDefaultCharset();
				
	//fall back
	if(empty($charset)) $charset = $appCharset; 	
	if(empty($charset)) $charset = $defaultCharset;
				
	return Translation::formatter()->format($text,$params,$catalogue,$charset);
}

?>