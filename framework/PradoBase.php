<?php
/**
 * PradoBase class file.
 *
 * This is the file that establishes the PRADO component model
 * and error handling mechanism.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System
 */

/**
 * Defines the PRADO framework installation path.
 */
if(!defined('PRADO_DIR'))
	define('PRADO_DIR',dirname(__FILE__));

/**
 * Includes the classes essential for PradoBase class
 */
require_once(PRADO_DIR.'/TComponent.php');
require_once(PRADO_DIR.'/Exceptions/TException.php');
require_once(PRADO_DIR.'/Util/TLogger.php');

/**
 * PradoBase class.
 *
 * PradoBase implements a few fundamental static methods.
 *
 * To use the static methods, Use Prado as the class name rather than PradoBase.
 * PradoBase is meant to serve as the base class of Prado. The latter might be
 * rewritten for customization.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
class PradoBase
{
	/**
	 * File extension for Prado class files.
	 */
	const CLASS_FILE_EXT='.php';
	/**
	 * @var array list of path aliases
	 */
	private static $_aliases=array('System'=>PRADO_DIR);
	/**
	 * @var array list of namespaces currently in use
	 */
	private static $_usings=array();
	/**
	 * @var TApplication the application instance
	 */
	private static $_application=null;
	/**
	 * @var TLogger logger instance
	 */
	private static $_logger=null;

	/**
	 * @return string the version of Prado framework
	 */
	public static function getVersion()
	{
		return '3.1.0b';
	}

	/**
	 * Initializes error handlers.
	 * This method set error and exception handlers to be functions
	 * defined in this class.
	 */
	public static function initErrorHandlers()
	{
		/**
		 * Sets error handler to be Prado::phpErrorHandler
		 */
		set_error_handler(array('PradoBase','phpErrorHandler'),error_reporting());
		/**
		 * Sets exception handler to be Prado::exceptionHandler
		 */
		set_exception_handler(array('PradoBase','exceptionHandler'));
	}

	/**
	 * Class autoload loader.
	 * This method is provided to be invoked within an __auload() magic method.
	 * @param string class name
	 */
	public static function autoload($className)
	{
		include_once($className.self::CLASS_FILE_EXT);
		if(!class_exists($className,false) && !interface_exists($className,false))
			self::fatalError("Class file for '$className' cannot be found.");
	}

	/**
	 * @return string a string that can be displayed on your Web page showing powered-by-PRADO information
	 */
	public static function poweredByPrado()
	{
		return '<a title="Powered by PRADO" href="http://www.pradosoft.com/"><img src="http://www.pradosoft.com/images/powered.gif" style="border-width:0px;" alt="Powered by PRADO" /></a>';
	}

	/**
	 * PHP error handler.
	 * This method should be registered as PHP error handler using
	 * {@link set_error_handler}. The method throws an exception that
	 * contains the error information.
	 * @param integer the level of the error raised
	 * @param string the error message
	 * @param string the filename that the error was raised in
	 * @param integer the line number the error was raised at
	 */
	public static function phpErrorHandler($errno,$errstr,$errfile,$errline)
	{
		if(error_reporting()!=0)
			throw new TPhpErrorException($errno,$errstr,$errfile,$errline);
	}

	/**
	 * Default exception handler.
	 * This method should be registered as default exception handler using
	 * {@link set_exception_handler}. The method tries to use the errorhandler
	 * module of the Prado application to handle the exception.
	 * If the application or the module does not exist, it simply echoes the
	 * exception.
	 * @param Exception exception that is not caught
	 */
	public static function exceptionHandler($exception)
	{
		if(self::$_application!==null && ($errorHandler=self::$_application->getErrorHandler())!==null)
		{
			$errorHandler->handleError(null,$exception);
		}
		else
		{
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
	 * @param TApplication the application instance
	 * @throws TInvalidOperationException if this method is invoked twice or more.
	 */
	public static function setApplication($application)
	{
		if(self::$_application!==null)
			throw new TInvalidOperationException('prado_application_singleton_required');
		self::$_application=$application;
	}

	/**
	 * @return TApplication the application singleton, null if the singleton has not be created yet.
	 */
	public static function getApplication()
	{
		return self::$_application;
	}

	/**
	 * @return string the path of the framework
	 */
	public static function getFrameworkPath()
	{
		return PRADO_DIR;
	}

	/**
	 * Serializes a data.
	 * The original PHP serialize function has a bug that may not serialize
	 * properly an object.
	 * @param mixed data to be serialized
	 * @return string the serialized data
	 */
	public static function serialize($data)
	{
		$arr[0]=$data;
		return serialize($arr);
	}

	/**
	 * Unserializes a data.
	 * The original PHP unserialize function has a bug that may not unserialize
	 * properly an object.
	 * @param string data to be unserialized
	 * @return mixed unserialized data, null if unserialize failed
	 */
	public static function unserialize($str)
	{
		$arr=unserialize($str);
		return isset($arr[0])?$arr[0]:null;
	}

	/**
	 * Creates a component with the specified type.
	 * A component type can be either the component class name
	 * or a namespace referring to the path of the component class file.
	 * For example, 'TButton', 'System.Web.UI.WebControls.TButton' are both
	 * valid component type.
	 * This method can also pass parameters to component constructors.
	 * All paramters passed to this method except the first one (the component type)
	 * will be supplied as component constructor paramters.
	 * @param string component type
	 * @return TComponent component instance of the specified type
	 * @throws TInvalidDataValueException if the component type is unknown
	 */
	public static function createComponent($type)
	{
		self::using($type);
		if(($pos=strrpos($type,'.'))!==false)
			$type=substr($type,$pos+1);
		if(($n=func_num_args())>1)
		{
			$args=func_get_args();
			$s='$args[1]';
			for($i=2;$i<$n;++$i)
				$s.=",\$args[$i]";
			eval("\$component=new $type($s);");
			return $component;
		}
		else
			return new $type;
	}

	/**
	 * Uses a namespace.
	 * A namespace ending with an asterisk '*' refers to a directory, otherwise it represents a PHP file.
	 * If the namespace corresponds to a directory, the directory will be appended
	 * to the include path. If the namespace corresponds to a file, it will be included (include_once).
	 * @param string namespace to be used
	 * @throws TInvalidDataValueException if the namespace is invalid
	 */
	public static function using($namespace)
	{
		if(isset(self::$_usings[$namespace]) || class_exists($namespace,false))
			return;
		if(($pos=strrpos($namespace,'.'))===false)  // a class name
		{
			try
			{
				include_once($namespace.self::CLASS_FILE_EXT);
			}
			catch(Exception $e)
			{
				if(!class_exists($namespace,false))
					throw new TInvalidOperationException('prado_component_unknown',$namespace);
				else
					throw $e;
			}
		}
		else if(($path=self::getPathOfNamespace($namespace,self::CLASS_FILE_EXT))!==null)
		{
			$className=substr($namespace,$pos+1);
			if($className==='*')  // a directory
			{
				if((self::$_application && self::$_application->getMode()===TApplication::STATE_PERFORMANCE) || is_dir($path))
				{
					self::$_usings[$namespace]=$path;
					set_include_path(get_include_path().PATH_SEPARATOR.$path);
				}
				else
					throw new TInvalidDataValueException('prado_using_invalid',$namespace);
			}
			else  // a file
			{
				if((self::$_application && self::$_application->getMode()===TApplication::STATE_PERFORMANCE) || is_file($path))
				{
					self::$_usings[$namespace]=$path;
					if(!class_exists($className,false))
					{
						try
						{
							include_once($path);
						}
						catch(Exception $e)
						{
							if(!class_exists($className,false))
								throw new TInvalidOperationException('prado_component_unknown',$className);
							else
								throw $e;
						}
					}
				}
				else
					throw new TInvalidDataValueException('prado_using_invalid',$namespace);
			}
		}
		else
			throw new TInvalidDataValueException('prado_using_invalid',$namespace);
	}

	/**
	 * Translates a namespace into a file path.
	 * The first segment of the namespace is considered as a path alias
	 * which is replaced with the actual path. The rest segments are
	 * subdirectory names appended to the aliased path.
	 * If the namespace ends with an asterisk '*', it represents a directory;
	 * Otherwise it represents a file whose extension name is specified by the second parameter (defaults to empty).
	 * Note, this method does not ensure the existence of the resulting file path.
	 * @param string namespace
	 * @param string extension to be appended if the namespace refers to a file
	 * @return string file path corresponding to the namespace, null if namespace is invalid
	 */
	public static function getPathOfNamespace($namespace,$ext='')
	{
		if(isset(self::$_usings[$namespace]))
			return self::$_usings[$namespace];
		else if(isset(self::$_aliases[$namespace]))
			return self::$_aliases[$namespace];
		else
		{
			$segs=explode('.',$namespace);
			$alias=array_shift($segs);
			if(($file=array_pop($segs))!==null && ($root=self::getPathOfAlias($alias))!==null)
				return rtrim($root.'/'.implode('/',$segs),'/').(($file==='*')?'':'/'.$file.$ext);
			else
				return null;
		}
	}

	/**
	 * @param string alias to the path
	 * @return string the path corresponding to the alias, null if alias not defined.
	 */
	public static function getPathOfAlias($alias)
	{
		return isset(self::$_aliases[$alias])?self::$_aliases[$alias]:null;
	}

	/**
	 * @param string alias to the path
	 * @param string the path corresponding to the alias
	 * @throws TInvalidOperationException if the alias is already defined
	 * @throws TInvalidDataValueException if the path is not a valid file path
	 */
	public static function setPathOfAlias($alias,$path)
	{
		if(isset(self::$_aliases[$alias]))
			throw new TInvalidOperationException('prado_alias_redefined',$alias);
		else if(($rp=realpath($path))!==false && is_dir($rp))
		{
			if(strpos($alias,'.')===false)
				self::$_aliases[$alias]=$rp;
			else
				throw new TInvalidDataValueException('prado_aliasname_invalid',$alias);
		}
		else
			throw new TInvalidDataValueException('prado_alias_invalid',$alias,$path);
	}

	/**
	 * Fatal error handler.
	 * This method displays an error message together with the current call stack.
	 * The application will exit after calling this method.
	 * @param string error message
	 */
	public static function fatalError($msg)
	{
		echo '<h1>Fatal Error</h1>';
		echo '<p>'.$msg.'</p>';
		if(!function_exists('debug_backtrace'))
			return;
		echo '<h2>Debug Backtrace</h2>';
		echo '<pre>';
		$index=-1;
		foreach(debug_backtrace() as $t)
		{
			$index++;
			if($index==0)  // hide the backtrace of this function
				continue;
			echo '#'.$index.' ';
			if(isset($t['file']))
				echo basename($t['file']) . ':' . $t['line'];
			else
			   echo '<PHP inner-code>';
			echo ' -- ';
			if(isset($t['class']))
				echo $t['class'] . $t['type'];
			echo $t['function'];
			if(isset($t['args']) && sizeof($t['args']) > 0)
				echo '(...)';
			else
				echo '()';
			echo "\n";
		}
		echo '</pre>';
		exit(1);
	}

	/**
	 * Returns a list of user preferred languages.
	 * The languages are returned as an array. Each array element
	 * represents a single language preference. The languages are ordered
	 * according to user preferences. The first language is the most preferred.
	 * @return array list of user preferred languages.
	 */
	public static function getUserLanguages()
	{
		static $languages=null;
		if($languages===null)
		{
			if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				$languages[0]='en';
			else
			{
				$languages=array();
				foreach(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language)
				{
					$array=split(';q=',trim($language));
					$languages[trim($array[0])]=isset($array[1])?(float)$array[1]:1.0;
				}
				arsort($languages);
				$languages=array_keys($languages);
				if(empty($languages))
					$languages[0]='en';
			}
		}
		return $languages;
	}

	/**
	 * Returns the most preferred language by the client user.
	 * @return string the most preferred language by the client user, defaults to English.
	 */
	public static function getPreferredLanguage()
	{
		static $language=null;
		if($language===null)
		{
			$langs=Prado::getUserLanguages();
			$lang=explode('-',$langs[0]);
			if(empty($lang[0]) || !ctype_alpha($lang[0]))
				$language='en';
			else
				$language=$lang[0];
		}
		return $language;
	}

	/**
	 * Writes a log message.
	 * This method wraps {@link log()} by checking the application mode.
	 * When the application is in Debug mode, debug backtrace information is appended
	 * to the message and the message is logged at DEBUG level.
	 * When the application is in Performance mode, this method does nothing.
	 * Otherwise, the message is logged at INFO level.
	 * @param string message to be logged
	 * @param string category of the message
	 * @see log, getLogger
	 */
	public static function trace($msg,$category='Uncategorized')
	{
		if(self::$_application && self::$_application->getMode()===TApplication::STATE_PERFORMANCE)
			return;
		if(self::$_application && self::$_application->getMode()===TApplication::STATE_DEBUG)
		{
			$trace=debug_backtrace();
			if(isset($trace[0]['file']) && isset($trace[0]['line']))
				$msg.=" (line {$trace[0]['line']}, {$trace[0]['file']})";
			$level=TLogger::DEBUG;
		}
		else
			$level=TLogger::INFO;
		self::log($msg,$level,$category);
	}

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@link TLogger::getLogs}
	 * and may be recorded in different media, such as file, email, database, using
	 * {@link TLogRouter}.
	 * @param string message to be logged
	 * @param integer level of the message. Valid values include
	 * TLogger::DEBUG, TLogger::INFO, TLogger::NOTICE, TLogger::WARNING,
	 * TLogger::ERROR, TLogger::ALERT, TLogger::FATAL.
	 * @param string category of the message
	 */
	public static function log($msg,$level=TLogger::INFO,$category='Uncategorized')
	{
		if(self::$_logger===null)
			self::$_logger=new TLogger;
		self::$_logger->log($msg,$level,$category);
	}

	/**
	 * @return TLogger message logger
	 */
	public static function getLogger()
	{
		if(self::$_logger===null)
			self::$_logger=new TLogger;
		return self::$_logger;
	}

	/**
	 * Converts a variable into a string representation.
	 * This method achieves the similar functionality as var_dump and print_r
	 * but is more robust when handling complex objects such as PRADO controls.
	 * @param mixed variable to be dumped
	 * @param integer maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param boolean whether to syntax highlight the output. Defaults to false.
	 * @return string the string representation of the variable
	 */
	public static function varDump($var,$depth=10,$highlight=false)
	{
		require_once(PRADO_DIR.'/Util/TVarDumper.php');
		return TVarDumper::dump($var,$depth,$highlight);
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
	public static function localize($text, $parameters=array(), $catalogue=null, $charset=null)
	{
		Prado::using('System.I18N.Translation');
		$app = Prado::getApplication()->getGlobalization(false);

		$params = array();
		foreach($parameters as $key => $value)
			$params['{'.$key.'}'] = $value;

		//no translation handler provided
		if($app===null || ($config = $app->getTranslationConfiguration())===null)
			return strtr($text, $params);

		Translation::init();

		if(empty($catalogue) && isset($config['catalogue']))
			$catalogue = $config['catalogue'];

		//globalization charset
		$appCharset = $app===null ? '' : $app->getCharset();

		//default charset
		$defaultCharset = ($app===null) ? 'UTF-8' : $app->getDefaultCharset();

		//fall back
		if(empty($charset)) $charset = $appCharset;
		if(empty($charset)) $charset = $defaultCharset;

		return Translation::formatter()->format($text,$params,$catalogue,$charset);
	}
}

/**
 * The following code is meant to fill the gaps between different PHP versions.
 */
if(version_compare(phpversion(),'5.1.0','>='))
{
	/**
	 * TReflectionClass class.
	 * This class is written to cope with the incompatibility between different PHP versions.
	 * It is equivalent to ReflectionClass if PHP version >= 5.1.0
	 * @author Qiang Xue <qiang.xue@gmail.com>
	 * @version $Revision: $  $Date: $
	 * @package System
	 * @since 3.0
	 */
	class TReflectionClass extends ReflectionClass
	{
	}
}
else // PHP < 5.1.0
{
	/**
	 * TReflectionClass class.
	 * This class is written to cope with the incompatibility between different PHP versions.
	 * It mainly provides a way to detect if a method exists for a given class name.
	 *
	 * @author Qiang Xue <qiang.xue@gmail.com>
	 * @version $Revision: $  $Date: $
	 * @package System
	 * @since 3.0
	 */
	class TReflectionClass extends ReflectionClass
	{
		/**
		 * @param string method name
		 * @return boolean whether the method exists
		 */
		public function hasMethod($method)
		{
			try
			{
				return $this->getMethod($method)!==null;
			}
			catch(Exception $e)
			{
				return false;
			}
		}

		/**
		 * @param string property name
		 * @return boolean whether the property exists
		 */
		public function hasProperty($property)
		{
			try
			{
				return $this->getProperty($property)!==null;
			}
			catch(Exception $e)
			{
				return false;
			}
		}
	}

	if(!function_exists('property_exists'))
	{
		/**
		 * Detects whether an object contains the specified member variable.
		 * @param object
		 * @param string member variable (property) name
		 * @return boolean
		 */
		function property_exists($object, $property)
		{
			if(is_object($object))
				return array_key_exists($property, get_object_vars($object));
			else
				return false;
		}
	}
}

?>
