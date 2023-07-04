<?php
/**
 * Prado class file.
 *
 * This is the file that establishes the PRADO component model
 * and error handling mechanism.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TPhpErrorException;
use Prado\Exceptions\TPhpFatalErrorException;
use Prado\Util\TLogger;
use Prado\Util\TVarDumper;
use Prado\I18N\Translation;

// Defines the initialization run time of Prado if no start time is defined.
if (empty($_SERVER["REQUEST_TIME_FLOAT"])) {
	$time = $_SERVER["REQUEST_TIME_FLOAT"] = microtime(true);
	$_SERVER["REQUEST_TIME"] = (int) $time;
}

// Defines the PRADO framework installation path.
if (!defined('PRADO_DIR')) {
	define('PRADO_DIR', __DIR__);
}

// Defines the default permission for writable directories
// @todo, the test on PRADO_CHMOD must be remove in the next major release
if (!defined('PRADO_DIR_CHMOD')) {
	define('PRADO_DIR_CHMOD', !defined('PRADO_CHMOD') ? 0o755 : PRADO_CHMOD);
}

// Defines the default permission for writable files
// @todo, the test on PRADO_CHMOD must be removed in the next major release
if (!defined('PRADO_FILE_CHMOD')) {
	define('PRADO_FILE_CHMOD', !defined('PRADO_CHMOD') ? 0o644 : PRADO_CHMOD);
}

// Defines the default permission for writable directories and files
// @todo, adding this define must be removed in the next major release
if (!defined('PRADO_CHMOD')) {
	define('PRADO_CHMOD', 0o777);
}

// Defines the Composer's vendor/ path.
if (!defined('PRADO_VENDORDIR')) {
	$reflector = new \ReflectionClass('\Composer\Autoload\ClassLoader');
	define('PRADO_VENDORDIR', dirname((string) $reflector->getFileName(), 2));
	unset($reflector);
}

/**
 * Prado class.
 *
 * Prado implements a few fundamental static methods.
 *
 * To use the static methods, Use Prado as the class name rather than Prado.
 * Prado is meant to serve as the base class of Prado. The latter might be
 * rewritten for customization.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class Prado
{
	/**
	 * File extension for Prado class files.
	 */
	public const CLASS_FILE_EXT = '.php';
	/**
	 * @var array<string, string> list of path aliases
	 */
	private static $_aliases = [
		'Prado' => PRADO_DIR,
		'Vendor' => PRADO_VENDORDIR,
		];
	/**
	 * @var array<string, string> list of namespaces currently in use
	 */
	private static $_usings = [
		'Prado' => PRADO_DIR,
		];
	/**
	 * @var array<string, string> list of namespaces currently in use
	 */
	public static $classMap = [];
	/**
	 * @var null|TApplication the application instance
	 */
	private static $_application;
	/**
	 * @var null|TLogger logger instance
	 */
	private static $_logger;
	/**
	 * @var array<string, bool> list of class exists checks
	 */
	protected static $classExists = [];
	/**
	 * @return string the version of Prado framework
	 */
	public static function getVersion(): string
	{
		return '4.2.2';
	}

	/**
	 * Initializes the Prado static class
	 */
	public static function init(): void
	{
		static::initAutoloader();
		static::initErrorHandlers();
	}

	/**
	 * Loads the static classmap and registers the autoload function.
	 */
	public static function initAutoloader(): void
	{
		self::$classMap = require(__DIR__ . '/classes.php');

		spl_autoload_register([static::class, 'autoload']);
	}

	/**
	 * Initializes error handlers.
	 * This method set error and exception handlers to be functions
	 * defined in this class.
	 */
	public static function initErrorHandlers(): void
	{
		/**
		 * Sets error handler to be Prado::phpErrorHandler
		 */
		set_error_handler([static::class, 'phpErrorHandler']);
		/**
		 * Sets shutdown function to be Prado::phpFatalErrorHandler
		 */
		register_shutdown_function([static::class, 'phpFatalErrorHandler']);
		/**
		 * Sets exception handler to be Prado::exceptionHandler
		 */
		set_exception_handler([static::class, 'exceptionHandler']);
		/**
		 * Disable php's builtin error reporting to avoid duplicated reports
		 */
		ini_set('display_errors', '0');
	}

	/**
	 * Class autoload loader.
	 * This method is provided to be registered within an spl_autoload_register() method.
	 * @param string $className class name
	 */
	public static function autoload($className): void
	{
		static::using($className);
	}

	/**
	 * @param int $logoType the type of "powered logo". Valid values include 0 and 1.
	 * @return string a string that can be displayed on your Web page showing powered-by-PRADO information
	 */
	public static function poweredByPrado($logoType = 0): string
	{
		$logoName = $logoType == 1 ? 'powered2' : 'powered';
		if (self::$_application !== null) {
			$am = self::$_application->getAssetManager();
			$url = $am->publishFilePath((string) self::getPathOfNamespace('Prado\\' . $logoName, '.gif'));
		} else {
			$url = 'http://pradosoft.github.io/docs/' . $logoName . '.gif';
		}
		return '<a title="Powered by PRADO" href="https://github.com/pradosoft/prado" target="_blank"><img src="' . $url . '" style="border-width:0px;" alt="Powered by PRADO" /></a>';
	}

	/**
	 * PHP error handler.
	 * This method should be registered as PHP error handler using
	 * {@see set_error_handler}. The method throws an exception that
	 * contains the error information.
	 * @param int $errno the level of the error raised
	 * @param string $errstr the error message
	 * @param string $errfile the filename that the error was raised in
	 * @param int $errline the line number the error was raised at
	 */
	public static function phpErrorHandler($errno, $errstr, $errfile, $errline): bool
	{
		if (error_reporting() & $errno) {
			throw new TPhpErrorException($errno, $errstr, $errfile, $errline);
		}
		return true;
	}

	/**
	 * PHP shutdown function used to catch fatal errors.
	 * This method should be registered as PHP error handler using
	 * {@see register_shutdown_function}. The method throws an exception that
	 * contains the error information.
	 */
	public static function phpFatalErrorHandler(): void
	{
		$error = error_get_last();
		if ($error &&
			TPhpErrorException::isFatalError($error) &&
			error_reporting() & $error['type']) {
			self::exceptionHandler(new TPhpFatalErrorException($error['type'], $error['message'], $error['file'], $error['line']));
		}
	}

	/**
	 * Default exception handler.
	 * This method should be registered as default exception handler using
	 * {@see set_exception_handler}. The method tries to use the errorhandler
	 * module of the Prado application to handle the exception.
	 * If the application or the module does not exist, it simply echoes the
	 * exception.
	 * @param \Throwable $exception exception that is not caught
	 */
	public static function exceptionHandler($exception): void
	{
		if (self::$_application !== null && ($errorHandler = self::$_application->getErrorHandler()) !== null) {
			$errorHandler->handleError(null, $exception);
		} else {
			echo $exception;
		}
		exit(1);
	}

	/**
	 * Stores the application instance in the class static member.
	 * This method helps implement a singleton pattern for TApplication.
	 * Repeated invocation of this method or the application constructor
	 * will cause the throw of an exception.
	 * This method should only be used by framework developers.
	 * @param TApplication $application the application instance
	 * @throws TInvalidOperationException if this method is invoked twice or more.
	 */
	public static function setApplication($application): void
	{
		if (self::$_application !== null && !defined('PRADO_TEST_RUN')) {
			throw new TInvalidOperationException('prado_application_singleton_required');
		}
		self::$_application = $application;
	}

	/**
	 * @return null|TApplication the application singleton, null if the singleton has not be created yet.
	 */
	public static function getApplication(): ?TApplication
	{
		return self::$_application;
	}

	/**
	 * @return string the path of the framework
	 */
	public static function getFrameworkPath(): string
	{
		return PRADO_DIR;
	}

	/**
	 * @deprecated deprecated since version 4.2.2, replaced by @getDefaultDirPermissions and @getDefaultFilePermissions
	 * @return int chmod permissions, defaults to 0777
	 */
	public static function getDefaultPermissions(): int
	{
		return PRADO_CHMOD;
	}

	/**
	 * @return int chmod dir permissions, defaults to 0755
	 */
	public static function getDefaultDirPermissions(): int
	{
		return PRADO_DIR_CHMOD;
	}

	/**
	 * @return int chmod file permissions, defaults to 0644
	 */
	public static function getDefaultFilePermissions(): int
	{
		return PRADO_FILE_CHMOD;
	}

	/**
	 * Convert old Prado namespaces to PHP namespaces
	 * @param string $type old class name in Prado3 namespace format
	 * @return string Equivalent class name in PHP namespace format
	 */
	protected static function prado3NamespaceToPhpNamespace($type): string
	{
		if (substr($type, 0, 6) === 'System') {
			$type = 'Prado' . substr($type, 6);
		}

		if (false === strpos($type, '\\')) {
			return str_replace('.', '\\', $type);
		} else {
			return $type;
		}
	}

	/**
	 * Creates a component with the specified type.
	 * A component type can be either the component class name
	 * or a namespace referring to the path of the component class file.
	 * For example, 'TButton', '\Prado\Web\UI\WebControls\TButton' are both
	 * valid component type.
	 * This method can also pass parameters to component constructors.
	 * All parameters passed to this method except the first one (the component type)
	 * will be supplied as component constructor parameters.
	 * @template T
	 * @param class-string<T> $requestedType component type
	 * @param array<mixed> $params
	 * @throws TInvalidDataValueException if the component type is unknown
	 * @return T component instance of the specified type
	 */
	public static function createComponent($requestedType, ...$params)
	{
		$properties = null;
		if (is_array($requestedType)) {
			$properties = $requestedType;
			$requestedType = $properties['class'];
			unset($properties['class']);
		}
		$type = static::prado3NamespaceToPhpNamespace($requestedType);
		if (!isset(self::$classExists[$type])) {
			self::$classExists[$type] = class_exists($type, false);
		}

		if (!isset(self::$_usings[$type]) && !self::$classExists[$type]) {
			static::using($type);
			self::$classExists[$type] = class_exists($type, false);
		}

		/*
		 * Old apps compatibility support: if the component name has been specified using the
		 * old namespace syntax (eg. Application.Common.MyDataModule), assume that the calling
		 * code expects the class not to be php5.3-namespaced (eg: MyDataModule instead of
		 * \Application\Common\MyDataModule)
		 * Skip this if the class is inside the Prado\* namespace, since all Prado classes are now namespaced
		 */
		if (($pos = strrpos($type, '\\')) !== false && ($requestedType != $type) && strpos($type, 'Prado\\') !== 0) {
			$type = substr($type, $pos + 1);
		}

		if (count($params) > 0) {
			$object = new $type(...$params);
		} else {
			$object = new $type();
		}
		if ($properties) {
			foreach ($properties as $property => $value) {
				$object->setSubProperty($property, $value);
			}
		}
		return $object;
	}

	/**
	 * Uses a namespace.
	 * A namespace ending with an asterisk '*' refers to a directory, otherwise it represents a PHP file.
	 * If the namespace corresponds to a directory, the directory will be appended
	 * to the include path. If the namespace corresponds to a file, it will be included (include_once).
	 * @param string $namespace namespace to be used
	 * @throws TInvalidDataValueException if the namespace is invalid
	 */
	public static function using($namespace): void
	{
		$namespace = static::prado3NamespaceToPhpNamespace($namespace);

		if (isset(self::$_usings[$namespace]) ||
			class_exists($namespace, false) ||
			interface_exists($namespace, false) ||
			trait_exists($namespace, false)) {
			return;
		}

		if (array_key_exists($namespace, self::$classMap)) {
			// fast autoload a Prado3 class name
			$phpNamespace = self::$classMap[$namespace];
			if (class_exists($phpNamespace, true) ||
				interface_exists($phpNamespace, true) ||
				trait_exists($phpNamespace, true)) {
				if (!class_exists($namespace) &&
					!interface_exists($namespace) &&
					!trait_exists($namespace)) {
					class_alias($phpNamespace, $namespace);
				}
				return;
			}
		} elseif (($pos = strrpos($namespace, '\\')) === false) {
			// trying to autoload an old class name
			foreach (self::$_usings as $k => $v) {
				$path = $v . DIRECTORY_SEPARATOR . $namespace . self::CLASS_FILE_EXT;
				if (file_exists($path)) {
					$phpNamespace = '\\' . $k . '\\' . $namespace;
					if (class_exists($phpNamespace, true) ||
						interface_exists($phpNamespace, true) ||
						trait_exists($phpNamespace, true)) {
						if (!class_exists($namespace) &&
							!interface_exists($namespace) &&
							!trait_exists($namespace)) {
							class_alias($phpNamespace, $namespace);
						}
						return;
					}
				}
			}
		} elseif (($path = self::getPathOfNamespace($namespace, self::CLASS_FILE_EXT)) !== null) {
			$className = substr($namespace, $pos + 1);
			if ($className === '*') {  // a directory
				self::$_usings[substr($namespace, 0, $pos)] = $path;
			} else {  // a file
				if (class_exists($className, false) ||
					interface_exists($className, false) ||
					trait_exists($className, false)) {
					return;
				}

				if (file_exists($path)) {
					include_once($path);
					if (!class_exists($className, false) &&
						!interface_exists($className, false) &&
						!trait_exists($className, false)) {
						class_alias($namespace, $className);
					}
				}
			}
		}
	}

	/**
	 * This conforms PHP's Magic Methods to PHP's Visibility standards for public,
	 * protected, and private properties, methods, and constants. This method checks
	 * if the object calling your method can access your object's property, method,
	 * or constant based upon the defined visibility.  External objects can only access
	 * public properties, methods, and constants.  When calling the self, private
	 * properties, methods, and constants are allowed to be accessed by the same class.
	 * @param object|string $object_or_class The object to check for the method within
	 *   and for visibility to the calling object.
	 * @param string $method
	 * @return bool Does the method exist and is publicly callable.
	 * @since 4.2.3
	 */
	public static function method_visible($object_or_class, string $method): bool
	{
		if (method_exists($object_or_class, $method)) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3);
			$reflection = new \ReflectionMethod($object_or_class, $method);
			if (empty($trace[2]) || empty($trace[1]['object']) || empty($trace[2]['object']) || $trace[1]['object'] !== $trace[2]['object']) {
				return $reflection->isPublic();
			} elseif ($reflection->isPrivate()) {
				return $trace[2]['class'] === $reflection->class;
			}
			return true;
		}
		return false;
	}

	/**
	 * This method return the object that is calling your method.
	 * @return ?object The parent object calling your code block.
	 * @since 4.2.3
	 */
	public static function callingObject(): ?object
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		if (!empty($trace[2]) && !empty($trace[2]['object'])) {
			return $trace[2]['object'];
		}
		return null;
	}

	/**
	 * This checks if object calling your object method is the same object.  In effect,
	 * this signifies if self, parents, and children have visibility to "protected"
	 * properties, methods, and constants.
	 * @return bool Does the method exist and is publicly callable.
	 * @since 4.2.3
	 */
	public static function isCallingSelf(): bool
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		return isset($trace[2]) && isset($trace[1]['object']) && isset($trace[2]['object']) && $trace[1]['object'] === $trace[2]['object'];
	}

	/**
	 * This checks if object calling your object method is the same object and same class.
	 * In effect, this allows only the self to have visibility to "private" properties,
	 * methods, and constants.
	 * @return bool Does the method exist and is publicly callable.
	 * @since 4.2.3
	 */
	public static function isCallingSelfClass(): bool
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		return isset($trace[2]) && isset($trace[1]['object']) && isset($trace[2]['object']) && $trace[1]['object'] === $trace[2]['object'] && $trace[1]['class'] === $trace[2]['class'];
	}

	/**
	 * Translates a namespace into a file path.
	 * The first segment of the namespace is considered as a path alias
	 * which is replaced with the actual path. The rest segments are
	 * subdirectory names appended to the aliased path.
	 * If the namespace ends with an asterisk '*', it represents a directory;
	 * Otherwise it represents a file whose extension name is specified by the second parameter (defaults to empty).
	 * Note, this method does not ensure the existence of the resulting file path.
	 * @param string $namespace namespace
	 * @param string $ext extension to be appended if the namespace refers to a file
	 * @return null|string file path corresponding to the namespace, null if namespace is invalid
	 */
	public static function getPathOfNamespace($namespace, $ext = ''): ?string
	{
		$namespace = static::prado3NamespaceToPhpNamespace($namespace);

		if (self::CLASS_FILE_EXT === $ext || empty($ext)) {
			if (isset(self::$_usings[$namespace])) {
				return self::$_usings[$namespace];
			}

			if (isset(self::$_aliases[$namespace])) {
				return self::$_aliases[$namespace];
			}
		}

		$segs = explode('\\', $namespace);
		$alias = array_shift($segs);

		if (null !== ($file = array_pop($segs)) && null !== ($root = self::getPathOfAlias($alias))) {
			return rtrim($root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segs), '/\\') . (($file === '*') ? '' : DIRECTORY_SEPARATOR . $file . $ext);
		}

		return null;
	}

	/**
	 * @param string $alias alias to the path
	 * @return null|string the path corresponding to the alias, null if alias not defined.
	 */
	public static function getPathOfAlias($alias): ?string
	{
		return self::$_aliases[$alias] ?? null;
	}

	/**
	 * @return array<string, string> list of path aliases
	 */
	protected static function getPathAliases(): array
	{
		return self::$_aliases;
	}

	/**
	 * @param string $alias alias to the path
	 * @param string $path the path corresponding to the alias
	 * @throws TInvalidOperationException $alias if the alias is already defined
	 * @throws TInvalidDataValueException $path if the path is not a valid file path
	 */
	public static function setPathOfAlias($alias, $path): void
	{
		if (isset(self::$_aliases[$alias]) && !defined('PRADO_TEST_RUN')) {
			throw new TInvalidOperationException('prado_alias_redefined', $alias);
		} elseif (($rp = realpath($path)) !== false && is_dir($rp)) {
			if (strpos($alias, '.') === false) {
				self::$_aliases[$alias] = $rp;
			} else {
				throw new TInvalidDataValueException('prado_aliasname_invalid', $alias);
			}
		} else {
			throw new TInvalidDataValueException('prado_alias_invalid', $alias, $path);
		}
	}

	/**
	 * Fatal error handler.
	 * This method displays an error message together with the current call stack.
	 * The application will exit after calling this method.
	 * @param string $msg error message
	 */
	public static function fatalError($msg): void
	{
		echo '<h1>Fatal Error</h1>';
		echo '<p>' . $msg . '</p>';
		if (!function_exists('debug_backtrace')) {
			return;
		}
		echo '<h2>Debug Backtrace</h2>';
		echo '<pre>';
		$index = -1;
		foreach (debug_backtrace() as $t) {
			$index++;
			if ($index == 0) {  // hide the backtrace of this function
				continue;
			}
			echo '#' . $index . ' ';
			if (isset($t['file'])) {
				echo basename($t['file']) . ':' . $t['line'];
			} else {
				echo '<PHP inner-code>';
			}
			echo ' -- ';
			if (isset($t['class'])) {
				echo $t['class'] . $t['type'];
			}
			echo $t['function'] . '(';
			if (isset($t['args']) && count($t['args']) > 0) {
				$count = 0;
				foreach ($t['args'] as $item) {
					if (is_string($item)) {
						$str = htmlentities(str_replace("\r\n", "", $item), ENT_QUOTES);
						if (strlen($item) > 70) {
							echo "'" . substr($str, 0, 70) . "...'";
						} else {
							echo "'" . $str . "'";
						}
					} elseif (is_int($item) || is_float($item)) {
						echo $item;
					} elseif (is_object($item)) {
						echo $item::class;
					} elseif (is_array($item)) {
						echo 'array(' . count($item) . ')';
					} elseif (is_bool($item)) {
						echo $item ? 'true' : 'false';
					} elseif ($item === null) {
						echo 'NULL';
					} elseif (is_resource($item)) {
						echo get_resource_type($item);
					}
					$count++;
					if (count($t['args']) > $count) {
						echo ', ';
					}
				}
			}
			echo ")\n";
		}
		echo '</pre>';
		exit(1);
	}

	/**
	 * Returns a list of user preferred languages.
	 * The languages are returned as an array. Each array element
	 * represents a single language preference. The languages are ordered
	 * according to user preferences. The first language is the most preferred.
	 * @return array<string> list of user preferred languages.
	 */
	public static function getUserLanguages(): array
	{
		static $languages = null;
		if ($languages === null) {
			if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$languages[0] = 'en';
			} else {
				$languages = [];
				foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language) {
					$array = explode(';q=', trim($language));
					$languages[trim($array[0])] = isset($array[1]) ? (float) $array[1] : 1.0;
				}
				arsort($languages);
				$languages = array_keys($languages);
				if (count($languages) == 0) {
					$languages[0] = 'en';
				}
			}
		}
		return $languages;
	}

	/**
	 * Returns the most preferred language by the client user.
	 * @return string the most preferred language by the client user, defaults to English.
	 */
	public static function getPreferredLanguage(): string
	{
		static $language = null;
		if ($language === null) {
			$langs = Prado::getUserLanguages();
			$lang = explode('-', $langs[0]);
			if (empty($lang[0]) || !ctype_alpha($lang[0])) {
				$language = 'en';
			} else {
				$language = $lang[0];
			}
		}
		return $language;
	}

	/**
	 * Writes a profile begin message.
	 * @param string $token The profile token.
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function profileBegin($token, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($token, TLogger::PROFILE_BEGIN, $category, $ctl);
	}

	/**
	 * Writes a profile end message.
	 * @param string $token The profile token.
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 * @return ?float The time spent since the PROFILE_BEGIN of the same token.
	 */
	public static function profileEnd($token, $category = null, $ctl = null): ?float
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		return self::log($token, TLogger::PROFILE_END, $category, $ctl);
	}

	/**
	 * Writes a log message.
	 * This method wraps {@see log()} by checking the application mode.
	 * When the application is in Debug mode, debug backtrace information is appended
	 * to the message and the message is logged at DEBUG level.
	 * When the application is in Performance mode, this method does nothing.
	 * Otherwise, the message is logged at INFO level.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, null fills is with the calling class
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message
	 * @see log, getLogger
	 */
	public static function trace($msg, $category = null, $ctl = null): void
	{
		if (self::$_application && self::$_application->getMode() === TApplicationMode::Performance) {
			return;
		}
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		if (!self::$_application || self::$_application->getMode() === TApplicationMode::Debug) {
			$trace = debug_backtrace();
			if (isset($trace[0]['file']) && isset($trace[0]['line'])) {
				$msg .= " (line {$trace[0]['line']}, {$trace[0]['file']})";
			}
			$level = TLogger::DEBUG;
		} else {
			$level = TLogger::INFO;
		}
		self::log($msg, $level, $category, $ctl);
	}

	/**
	 * Writes a debug log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function debug($msg, $category = null, $ctl = null): void
	{
		if (self::$_application && self::$_application->getMode() !== TApplicationMode::Debug) {
			return;
		}
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		$trace = debug_backtrace();
		if (isset($trace[0]['file']) && isset($trace[0]['line'])) {
			$msg .= " (line {$trace[0]['line']}, {$trace[0]['file']})";
		}
		self::log($msg, TLogger::DEBUG, $category, $ctl);
	}

	/**
	 * Writes an info log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function info($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::INFO, $category, $ctl);
	}

	/**
	 * Writes a notice log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function notice($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::NOTICE, $category, $ctl);
	}

	/**
	 * Writes a warning log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function warning($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::WARNING, $category, $ctl);
	}

	/**
	 * Writes an error log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function error($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::ERROR, $category, $ctl);
	}

	/**
	 * Writes an alert log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function alert($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::ALERT, $category, $ctl);
	}

	/**
	 * Writes a fatal log message.
	 * @param string $msg message to be logged
	 * @param ?string $category category of the message, default null for the calling class.
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message, default null
	 */
	public static function fatal($msg, $category = null, $ctl = null): void
	{
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		self::log($msg, TLogger::FATAL, $category, $ctl);
	}

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@see \Prado\Util\TLogger::getLogs}
	 * and may be recorded in different media, such as file, email, database, using
	 * {@see \Prado\Util\TLogRouter}.
	 * @param string $msg message to be logged
	 * @param int $level level of the message. Valid values include
	 * TLogger::DEBUG, TLogger::INFO, TLogger::NOTICE, TLogger::WARNING,
	 * TLogger::ERROR, TLogger::ALERT, TLogger::FATAL.
	 * @param string $category category of the message
	 * @param null|\Prado\Web\UI\TControl|string $ctl control of the message
	 * @return ?float When the $level is PROFILE_END, this returns the delta time
	 *   since the PROFILE_BEGIN of the same $msg.
	 */
	public static function log($msg, $level = TLogger::INFO, $category = 'Uncategorized', $ctl = null): ?float
	{
		if (self::$_logger === null) {
			self::$_logger = new TLogger();
		}
		if ($category === null) {
			$category = self::callingObject()::class;
		}
		return self::$_logger->log($msg, $level, $category, $ctl);
	}

	/**
	 * @return TLogger message logger
	 */
	public static function getLogger(): TLogger
	{
		if (self::$_logger === null) {
			self::$_logger = new TLogger();
		}
		return self::$_logger;
	}

	/**
	 * Converts a variable into a string representation.
	 * This method achieves the similar functionality as var_dump and print_r
	 * but is more robust when handling complex objects such as PRADO controls.
	 * @param mixed $var variable to be dumped
	 * @param int $depth maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param bool $highlight whether to syntax highlight the output. Defaults to false.
	 * @return string the string representation of the variable
	 */
	public static function varDump($var, $depth = 10, $highlight = false): string
	{
		return TVarDumper::dump($var, $depth, $highlight);
	}

	/**
	 * Localize a text to the locale/culture specified in the globalization handler.
	 * @param string $text text to be localized.
	 * @param array<string, string> $parameters a set of parameters to substitute.
	 * @param string $catalogue a different catalogue to find the localize text.
	 * @param string $charset the input AND output charset.
	 * @return string localized text.
	 * @see TTranslate::formatter()
	 * @see TTranslate::init()
	 */
	public static function localize($text, $parameters = [], $catalogue = null, $charset = null): string
	{
		$params = [];
		foreach ($parameters as $key => $value) {
			$params['{' . $key . '}'] = $value;
		}

		// no translation handler provided
		if (self::$_application === null
			|| ($app = self::$_application->getGlobalization(false)) === null
			|| ($config = $app->getTranslationConfiguration()) === null) {
			return strtr($text, $params);
		}

		if ($catalogue === null) {
			$catalogue = $config['catalogue'] ?? 'messages';
		}

		Translation::init($catalogue);

		//globalization charset
		if (empty($charset)) {
			$charset = $app->getCharset();
		}

		//default charset
		if (empty($charset)) {
			$charset = $app->getDefaultCharset();
		}

		return Translation::formatter($catalogue)->format($text, $params, $catalogue, $charset);
	}
}

/**
 * Initialize Prado autoloader and error handler
 */
Prado::init();

/**
 * Defines Prado in global namespace
 */
class_alias(\Prado\Prado::class, 'Prado');
