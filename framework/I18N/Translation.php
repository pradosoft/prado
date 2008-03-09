<?php
/**
 * Translation, static.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
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
	 * The array of formatters. We define 1 formatter per translation catalog
	 * This is a class static variable.
	 * @var array
	 */
	protected static $formatters=array();

	/**
	 * Initialize the TTranslate translation components
	 */
	public static function init($catalogue='messages')
	{
		//initialized the default class wide formatter
		if(is_null(self::$formatters[$catalogue]))
		{
			$app = Prado::getApplication()->getGlobalization();
			$config = $app->getTranslationConfiguration();
			$source = MessageSource::factory($config['type'],
											$config['source'],
											$config['filename']);

			$source->setCulture($app->getCulture());

			if($config['cache'])
				$source->setCache(new MessageCache($config['cache']));

			self::$formatters[$catalogue] = new MessageFormat($source, $app->getCharset());

			//mark untranslated text
			if($ps=$config['marker'])
				self::$formatters[$catalogue]->setUntranslatedPS(array($ps,$ps));

			//save the message on end request
			Prado::getApplication()->attachEventHandler(
				'OnEndRequest', array('Translation', 'saveMessages'));
		}
	}

	/**
	 * Get the static formatter from this component.
	 * @return MessageFormat formattter.
	 * @see localize()
	 */
	public static function formatter($catalogue='messages')
	{
		return self::$formatters[$catalogue];
	}

	/**
	 * Save untranslated messages to the catalogue.
	 */
	public static function saveMessages()
	{
		static $onceonly = true;

		if($onceonly)
		{
			foreach (self::$formatters as $catalogue=>$formatter)
			{
				$app = Prado::getApplication()->getGlobalization();
				$config = $app->getTranslationConfiguration();
				if(isset($config['autosave']))
				{
					$formatter->getSource()->setCulture($app->getCulture());
					$formatter->getSource()->save($catalogue);
				}
			}
			$onceonly = false;
		}
	}
}

?>