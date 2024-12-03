<?php

/**
 * TApplicationComponent class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\TApplicationMode;

/**
 * TApplicationComponent class
 *
 * TApplicationComponent is the base class for all components that are
 * application-related, such as controls, modules, services, etc.
 *
 * TApplicationComponent mainly defines a few properties that are shortcuts
 * to some commonly used methods. The {@see getApplication Application}
 * property gives the application instance that this component belongs to;
 * {@see getService Service} gives the current running service;
 * {@see getRequest Request}, {@see getResponse Response} and {@see getSession Session}
 * return the request and response modules, respectively;
 * And {@see getUser User} gives the current user instance.
 *
 * Besides, TApplicationComponent defines two shortcut methods for
 * publishing private files: {@see publishAsset} and {@see publishFilePath}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TApplicationComponent extends \Prado\TComponent
{
	public const FX_CACHE_FILE = 'fxevent.cache';
	/**
	 * TApplicationComponents auto listen to global events.
	 *
	 * @return bool returns whether or not to listen.
	 */
	public function getAutoGlobalListen()
	{
		return true;
	}

	/**
	 * This caches the 'fx' events for PRADO classes in the application cache
	 * @param object $class The object to get the 'fx' events.
	 * @return string[] fx events from a specific class
	 */
	protected function getClassFxEvents($class)
	{
		static $_classfx = [];
		static $_classfxSize = 0;
		static $_loaded = false;

		$app = $this->getApplication();
		$cacheFile = $mode = null;
		if ($app) {
			$cacheFile = $app->getRuntimePath() . DIRECTORY_SEPARATOR . self::FX_CACHE_FILE;
			if ((($mode = $app->getMode()) === TApplicationMode::Normal || $mode === TApplicationMode::Performance) && !$_loaded) {
				$_loaded = true;
				if (($content = @file_get_contents($cacheFile)) !== false) {
					$_classfx = @unserialize($content) ?? [];
					$_classfxSize = count($_classfx);
				}
			}
		}
		$className = $class::class;
		if (array_key_exists($className, $_classfx)) {
			return $_classfx[$className];
		}
		$fx = parent::getClassFxEvents($class);
		$_classfx[$className] = $fx;
		if ($cacheFile) {
			if ($mode === TApplicationMode::Performance) {
				file_put_contents($cacheFile, serialize($_classfx), LOCK_EX);
			} elseif ($mode === TApplicationMode::Normal) {
				static $_flipClassMap = null;

				if ($_flipClassMap === null) {
					$_flipClassMap = array_flip(Prado::$classMap);
				}
				$classData = array_intersect_key($_classfx, $_flipClassMap);
				if (($c = count($classData)) > $_classfxSize) {
					$_classfxSize = $c;
					file_put_contents($cacheFile, serialize($_classfx), LOCK_EX);
				}
			}
		}
		return $fx;
	}

	/**
	 * @return \Prado\TApplication current application instance
	 */
	public function getApplication()
	{
		return Prado::getApplication();
	}

	/**
	 * @return \Prado\TService the current service
	 */
	public function getService()
	{
		return Prado::getApplication()->getService();
	}

	/**
	 * @return \Prado\Web\THttpRequest the current user request
	 */
	public function getRequest()
	{
		return Prado::getApplication()->getRequest();
	}

	/**
	 * @return \Prado\Web\THttpResponse the response
	 */
	public function getResponse()
	{
		return Prado::getApplication()->getResponse();
	}

	/**
	 * @return \Prado\Web\THttpSession user session
	 */
	public function getSession()
	{
		return Prado::getApplication()->getSession();
	}

	/**
	 * @return \Prado\Security\IUser information about the current user
	 */
	public function getUser()
	{
		return Prado::getApplication()->getUser();
	}

	/**
	 * Publishes a private asset and gets its URL.
	 * This method will publish a private asset (file or directory)
	 * and gets the URL to the asset. Note, if the asset refers to
	 * a directory, all contents under that directory will be published.
	 * Also note, it is recommended that you supply a class name as the second
	 * parameter to the method (e.g. publishAsset($assetPath,__CLASS__) ).
	 * By doing so, you avoid the issue that child classes may not work properly
	 * because the asset path will be relative to the directory containing the child class file.
	 *
	 * @param string $assetPath path of the asset that is relative to the directory containing the specified class file.
	 * @param string $className name of the class whose containing directory will be prepend to the asset path. If null, it means $this::class.
	 * @return string URL to the asset path.
	 */
	public function publishAsset($assetPath, $className = null)
	{
		if ($className === null) {
			$className = $this::class;
		}
		$class = new \ReflectionClass($className);
		$fullPath = dirname($class->getFileName()) . DIRECTORY_SEPARATOR . $assetPath;
		return $this->publishFilePath($fullPath);
	}

	/**
	 * Publishes a file or directory and returns its URL.
	 * @param string $fullPath absolute path of the file or directory to be published
	 * @param mixed $checkTimestamp
	 * @return string URL to the published file or directory
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		return Prado::getApplication()->getAssetManager()->publishFilePath($fullPath, $checkTimestamp);
	}
}
