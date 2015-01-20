<?php
/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * TApplicationStatePersister class.
 * TApplicationStatePersister provides a file-based persistent storage
 * for application state. Application state, when serialized, is stored
 * in a file named 'global.cache' under the 'runtime' directory of the application.
 * Cache will be exploited if it is enabled.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
class TApplicationStatePersister extends TModule implements IStatePersister
{
	/**
	 * Name of the value stored in cache
	 */
	const CACHE_NAME='prado:appstate';

	/**
	 * Initializes module.
	 * @param TXmlElement module configuration (may be null)
	 */
	public function init($config)
	{
		$this->getApplication()->setApplicationStatePersister($this);
	}

	/**
	 * @return string the file path storing the application state
	 */
	protected function getStateFilePath()
	{
		return $this->getApplication()->getRuntimePath().'/global.cache';
	}

	/**
	 * Loads application state from persistent storage.
	 * @return mixed application state
	 */
	public function load()
	{
		if(($cache=$this->getApplication()->getCache())!==null && ($value=$cache->get(self::CACHE_NAME))!==false)
			return unserialize($value);
		else
		{
			if(($content=@file_get_contents($this->getStateFilePath()))!==false)
				return unserialize($content);
			else
				return null;
		}
	}

	/**
	 * Saves application state in persistent storage.
	 * @param mixed application state
	 */
	public function save($state)
	{
		$content=serialize($state);
		$saveFile=true;
		if(($cache=$this->getApplication()->getCache())!==null)
		{
			if($cache->get(self::CACHE_NAME)===$content)
				$saveFile=false;
			else
				$cache->set(self::CACHE_NAME,$content);
		}
		if($saveFile)
		{
			$fileName=$this->getStateFilePath();
			file_put_contents($fileName,$content,LOCK_EX);
		}
	}

}