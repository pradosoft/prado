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
 * A TApplicationComponent keeps a reference to the {@see TApplication} that is
 * current when the component is constructed. With multiple applications
 * ({@see Prado::getMultipleApplications()}), the component stays bound to its
 * owning application after another application becomes the current one.
 * {@see isCurrentApplication()} tests the binding and
 * {@see makeCurrentApplication()} makes the owning application current.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
class TApplicationComponent extends \Prado\TComponent
{
	public const FX_CACHE_FILE = 'fxevent.cache';

	/**
	 * @var ?TApplication the application that owns this component, bound at construction.
	 *   Excluded from serialization; {@see __wakeup()} binds the current application.
	 * @since 4.4.0
	 */
	private ?TApplication $_application = null;

	/**
	 * Binds the current application and initializes global-event listening.
	 * @since 4.4.0
	 */
	public function __construct()
	{
		$this->resolveApplication();
		parent::__construct();
	}

	/**
	 * Excludes the owning application from serialization so a serialized component
	 * does not carry the {@see TApplication} object graph.
	 * @param array $exprops by reference
	 * @since 4.4.0
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_application";
	}

	/**
	 * Binds the current application after unserialization.
	 * @since 4.4.0
	 */
	public function __wakeup()
	{
		$this->resolveApplication();
		parent::__wakeup();
	}

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
	 * Returns the application that owns this component. A component without a
	 * binding, such as one constructed before any application existed, binds the
	 * current application through {@see resolveApplication()} first.
	 * @return ?TApplication the owning application, or null when none is registered.
	 */
	public function getApplication()
	{
		$this->resolveApplication();
		return $this->getApplicationDirect();
	}

	/**
	 * Returns the bound application without resolving it.
	 * @return ?TApplication the bound application, or null when unbound.
	 * @since 4.4.0
	 */
	protected function getApplicationDirect(): ?TApplication
	{
		return $this->_application;
	}

	/**
	 * Binds an application, or clears the binding with null so that
	 * {@see getApplication()} binds the current application on its next call.
	 * @param ?TApplication $app the application to bind, or null to clear.
	 * @since 4.4.0
	 */
	protected function setApplicationDirect(?TApplication $app): void
	{
		$this->_application = $app;
	}

	/**
	 * Binds the current application when the component is unbound. A bound
	 * component keeps its application.
	 * @since 4.4.0
	 */
	protected function resolveApplication(): void
	{
		if ($this->getApplicationDirect() === null) {
			$this->setApplicationDirect(Prado::getApplication());
		}
	}

	/**
	 * Returns whether the bound application is the current application. An
	 * unbound component returns false.
	 * @return bool whether the bound application is {@see Prado::getApplication()}.
	 * @since 4.4.0
	 */
	public function isCurrentApplication(): bool
	{
		$app = $this->getApplicationDirect();
		return $app !== null && $app === Prado::getApplication();
	}

	/**
	 * Makes the bound application the current application through
	 * {@see Prado::setApplication()}. An unbound component does nothing.
	 * @throws \Prado\Exceptions\TInvalidOperationException when a different
	 *   application is current and multiple applications are not enabled.
	 * @since 4.4.0
	 */
	public function makeCurrentApplication(): void
	{
		$app = $this->getApplicationDirect();
		if ($app !== null && !$this->isCurrentApplication()) {
			Prado::setApplication($app);
		}
	}

	/**
	 * @return ?IService the current service, or null when no application is available.
	 */
	public function getService()
	{
		return $this->getApplication()?->getService();
	}

	/**
	 * @return ?\Prado\Web\THttpRequest the current user request, or null when no application is available.
	 */
	public function getRequest()
	{
		return $this->getApplication()?->getRequest();
	}

	/**
	 * @return ?\Prado\Web\THttpResponse the response, or null when no application is available.
	 */
	public function getResponse()
	{
		return $this->getApplication()?->getResponse();
	}

	/**
	 * @return ?\Prado\Web\THttpSession the user session, or null when no application is available.
	 */
	public function getSession()
	{
		return $this->getApplication()?->getSession();
	}

	/**
	 * @return ?\Prado\Security\IUser the current user, or null when no application is available.
	 */
	public function getUser()
	{
		return $this->getApplication()?->getUser();
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
		$class = TComponentReflection::getReflectionClassByType($className);
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
		return $this->getApplication()?->getAssetManager()?->publishFilePath($fullPath, $checkTimestamp);
	}
}
