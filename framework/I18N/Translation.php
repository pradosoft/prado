<?php
/**
 * Translation, static.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
 */

namespace Prado\I18N;

/**
 * Get the MessageFormat class.
 */
use Prado\I18N\core\MessageCache;
use Prado\I18N\core\MessageFormat;
use Prado\I18N\core\MessageSource;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * Translation class.
 *
 * Provides translation using a static MessageFormatter.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N
 */
class Translation extends \Prado\TComponent
{
	/**
	 * The array of formatters. We define 1 formatter per translation catalog
	 * This is a class static variable.
	 * @var array
	 */
	protected static $formatters = [];

	/**
	 * Initialize the TTranslate translation components
	 * @param mixed $catalogue
	 */
	public static function init($catalogue = 'messages')
	{
		static $saveEventHandlerAttached = false;

		//initialized the default class wide formatter
		if (!isset(self::$formatters[$catalogue])) {
			$app = Prado::getApplication()->getGlobalization();
			$config = $app->getTranslationConfiguration();
			$source = MessageSource::factory(
				$config['type'],
				$config['source'],
				$config['filename']
			);

			$source->setCulture($app->getCulture());

			if (isset($config['cache'])) {
				$source->setCache(new MessageCache($config['cache']));
			}

			self::$formatters[$catalogue] = new MessageFormat($source, $app->getCharset());

			//mark untranslated text
			if ($ps = $config['marker']) {
				self::$formatters[$catalogue]->setUntranslatedPS([$ps, $ps]);
			}

			//save the message on end request
			// Do it only once !
			if (!$saveEventHandlerAttached && TPropertyValue::ensureBoolean($config['autosave'])) {
				Prado::getApplication()->attachEventHandler(
					'OnEndRequest',
					['Translation', 'saveMessages']
				);
				$saveEventHandlerAttached = true;
			}
		}
	}

	/**
	 * Get the static formatter from this component.
	 * @see localize()
	 * @param mixed $catalogue
	 * @return MessageFormat formattter.
	 */
	public static function formatter($catalogue = 'messages')
	{
		return self::$formatters[$catalogue];
	}

	/**
	 * Save untranslated messages to the catalogue.
	 */
	public static function saveMessages()
	{
		static $onceonly = true;

		if ($onceonly) {
			foreach (self::$formatters as $catalogue => $formatter) {
				$app = Prado::getApplication()->getGlobalization();
				$config = $app->getTranslationConfiguration();
				if (isset($config['autosave'])) {
					$formatter->getSource()->setCulture($app->getCulture());
					$formatter->getSource()->save($catalogue);
				}
			}
			$onceonly = false;
		}
	}
}
