<?php

/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TApplicationStatePersister class.
 * TApplicationStatePersister provides a file-based persistent storage
 * for application state. Application state, when serialized, is stored
 * in a file named 'global.cache' under the 'runtime' directory of the application.
 * Cache will be exploited if it is enabled.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="state" class="Prado\TApplicationStatePersister" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'state' => [
 *             'class' => 'Prado\TApplicationStatePersister',
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TApplicationStatePersister extends \Prado\TModule implements IStatePersister
{
	/**
	 * Name of the value stored in cache
	 */
	public const CACHE_NAME = 'prado:appstate';

	/**
	 * Initializes module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration (may be null)
	 */
	public function init($config)
	{
		$this->setAppApplicationStatePersister();
		parent::init($config);
	}

	/**
	 * Registers this module as the application state persister when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setAppApplicationStatePersister()
	{
		$this->getApplication()?->setApplicationStatePersister($this);
	}

	/**
	 * @return string the file path storing the application state
	 */
	protected function getStateFilePath()
	{
		return $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'global.cache';
	}

	/**
	 * Loads application state from persistent storage.
	 * @return ?mixed application state
	 */
	public function load()
	{
		if (($cache = $this->getApplication()->getCache()) !== null && ($value = $cache->get(self::CACHE_NAME)) !== false) {
			return unserialize($value);
		} else {
			if (($content = @file_get_contents($this->getStateFilePath())) !== false) {
				return unserialize($content);
			} else {
				return null;
			}
		}
	}

	/**
	 * Saves application state in persistent storage.
	 * @param mixed $state application state
	 */
	public function save($state)
	{
		$content = serialize($state);
		$saveFile = true;
		if (($cache = $this->getApplication()->getCache()) !== null) {
			if ($cache->get(self::CACHE_NAME) === $content) {
				$saveFile = false;
			} else {
				$cache->set(self::CACHE_NAME, $content);
			}
		}
		if ($saveFile) {
			$fileName = $this->getStateFilePath();
			file_put_contents($fileName, $content, LOCK_EX);
		}
	}
}
