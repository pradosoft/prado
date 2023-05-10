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
 * to some commonly used methods. The {@link getApplication Application}
 * property gives the application instance that this component belongs to;
 * {@link getService Service} gives the current running service;
 * {@link getRequest Request}, {@link getResponse Response} and {@link getSession Session}
 * return the request and response modules, respectively;
 * And {@link getUser User} gives the current user instance.
 *
 * Besides, TApplicationComponent defines two shortcut methods for
 * publishing private files: {@link publishAsset} and {@link publishFilePath}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TApplicationComponent extends \Prado\TComponent
{
	public const APP_COMPONENT_FX_CACHE = 'prado:applicationcomponent:fxcache';
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
	 * @param object $class
	 * @return string[] fx events from a specific class
	 */
	protected function getClassFxEvents($class)
	{
		static $_classfx = null;

		$app = $this->getApplication();
		$mode = $className = $cache = null;
		if ($app && (($mode = $app->getMode()) === TApplicationMode::Normal || $mode === TApplicationMode::Performance) && ($cache = $app->getCache())) {
			if ($_classfx === null) {
				$_classfx = $cache->get(self::APP_COMPONENT_FX_CACHE) ?? [];
			}
			$className = $class::class;
			if (isset($_classfx[$className])) {
				return $_classfx[$className];
			}
		}
		$fx = parent::getClassFxEvents($class);
		if ($cache) {
			if ($pos = strrpos($className, '\\')) {
				$baseClassName = substr($className, $pos + 1);
			} else {
				$baseClassName = $className;
			}
			if ($mode === TApplicationMode::Performance || isset(Prado::$classMap[$baseClassName])) {
				$_classfx[$className] = $fx;
				$cache->set(self::APP_COMPONENT_FX_CACHE, $_classfx);
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
