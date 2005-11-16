<?php
/**
 * Prado core interfaces and classes.
 *
 * This file contains and includes the definitions of prado core interfaces and classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System
 */

/**
 * The framework installation path.
 */
define('PRADO_DIR',dirname(__FILE__));

/**
 * Includes TComponent definition
 */
require_once(PRADO_DIR.'/TComponent.php');
/**
 * Includes exception definitions
 */
require_once(PRADO_DIR.'/Exceptions/TException.php');
/**
 * Includes TList definition
 */
require_once(PRADO_DIR.'/Collections/TList.php');
/**
 * Includes TMap definition
 */
require_once(PRADO_DIR.'/Collections/TMap.php');
/**
 * Includes TXmlDocument, TXmlElement definition
 */
require_once(PRADO_DIR.'/Data/TXmlDocument.php');

/**
 * IApplication interface.
 *
 * This interface must be implemented by application classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface IApplication
{
	/**
	 * Defines Error event.
	 */
	public function onError($param);
	/**
	 * Defines BeginRequest event.
	 * @param mixed event parameter
	 */
	public function onBeginRequest($param);
	/**
	 * Defines Authentication event.
	 * @param mixed event parameter
	 */
	public function onAuthentication($param);
	/**
	 * Defines PostAuthentication event.
	 * @param mixed event parameter
	 */
	public function onPostAuthentication($param);
	/**
	 * Defines Authorization event.
	 * @param mixed event parameter
	 */
	public function onAuthorization($param);
	/**
	 * Defines PostAuthorization event.
	 * @param mixed event parameter
	 */
	public function onPostAuthorization($param);
	/**
	 * Defines LoadState event.
	 * @param mixed event parameter
	 */
	public function onLoadState($param);
	/**
	 * Defines PostLoadState event.
	 * @param mixed event parameter
	 */
	public function onPostLoadState($param);
	/**
	 * Defines PreRunService event.
	 * @param mixed event parameter
	 */
	public function onPreRunService($param);
	/**
	 * Defines RunService event.
	 * @param mixed event parameter
	 */
	public function onRunService($param);
	/**
	 * Defines PostRunService event.
	 * @param mixed event parameter
	 */
	public function onPostRunService($param);
	/**
	 * Defines SaveState event.
	 * @param mixed event parameter
	 */
	public function onSaveState($param);
	/**
	 * Defines PostSaveState event.
	 * @param mixed event parameter
	 */
	public function onPostSaveState($param);
	/**
	 * Defines EndRequest event.
	 * @param mixed event parameter
	 */
	public function onEndRequest($param);
	/**
	 * Runs the application.
	 */
	public function run();
	/**
	 * Completes and terminates the current request processing.
	 */
	public function completeRequest();
	/**
	 * @return string application ID
	 */
	public function getID();
	/**
	 * @param string application ID
	 */
	public function setID($id);
	/**
	 * @return string a unique ID that can uniquely identify the application from the others
	 */
	public function getUniqueID();
	/**
	 * @return IUser application user
	 */
	public function getUser();
	/**
	 * @param IUser application user
	 */
	public function setUser(IUser $user);
	/**
	 * @param string module ID
	 * @return IModule module corresponding to the ID, null if not found
	 */
	public function getModule($id);
	/**
	 * Adds a module into application.
	 * @param string module ID
	 * @param IModule module to be added
	 * @throws TInvalidOperationException if module with the same ID already exists
	 */
	public function setModule($id,IModule $module);
	/**
	 * @return array list of modules
	 */
	public function getModules();
	/**
	 * @return TMap list of parameters
	 */
	public function getParameters();
	/**
	 * @return IService the currently requested service
	 */
	public function getService();
	/**
	 * @return THttpRequest the current user request
	 */
	public function getRequest();
	/**
	 * @return THttpResponse the response to the request
	 */
	public function getResponse();
	/**
	 * @return THttpSession the user session
	 */
	public function getSession();
	/**
	 * @return ICache cache that is available to use
	 */
	public function getCache();
	/**
	 * @return IErrorHandler error handler
	 */
	public function getErrorHandler();
}

/**
 * IModule interface.
 *
 * This interface must be implemented by application modules.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface IModule
{
	/**
	 * Initializes the module.
	 * @param IApplication the application object
	 * @param TXmlElement the configuration for the module
	 */
	public function init($application,$configuration);
	/**
	 * @return string ID of the module
	 */
	public function getID();
	/**
	 * @param string ID of the module
	 */
	public function setID($id);
}

/**
 * IService interface.
 *
 * This interface must be implemented by services.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface IService
{
	/**
	 * Initializes the service.
	 * @param IApplication the application object
	 * @param TXmlElement the configuration for the service
	 */
	public function init($application,$configuration);
	/**
	 * @return string ID of the service
	 */
	public function getID();
	/**
	 * @param string ID of the service
	 */
	public function setID($id);
	/**
	 * Runs the service.
	 */
	public function run();
}

interface IErrorHandler
{
	public function handle($sender,$param);
}

/**
 * ICache interface.
 *
 * This interface must be implemented by cache managers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface ICache
{
	/**
	 * Retrieves a value from cache with a specified key.
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($id);
	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id,$value,$expire=0);
	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id,$value,$expire=0);
	/**
	 * Stores a value identified by a key into cache only if the cache contains this key.
	 * The existing value and expiration time will be overwritten with the new ones.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function replace($id,$value,$expire=0);
	/**
	 * Deletes a value with the specified key from cache
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($id);
	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush();
}

/**
 * ITextWriter interface.
 *
 * This interface must be implemented by writers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface ITextWriter
{
	/**
	 * Writes a string.
	 * @param string string to be written
	 */
	public function write($str);
	/**
	 * Flushes the content that has been written.
	 */
	public function flush();
}

/**
 * ITheme interface.
 *
 * This interface must be implemented by theme.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface ITheme
{
	/**
	 * Applies this theme to the specified control.
	 * @param TControl the control to be applied with this theme
	 */
	public function apply($control);
}

/**
 * ITemplate interface
 *
 * ITemplate specifies the interface for classes encapsulating
 * parsed template structures.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
interface ITemplate
{
	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param TControl the parent control
	 */
	public function instantiateIn($parent);
}

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
	 * @var IApplication the application instance
	 */
	private static $_application=null;

	/**
	 * @return string the version of Prado framework
	 */
	public static function getVersion()
	{
		return '3.0a';
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
			$errorHandler->handle($exception);
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
	 * @param IApplication the application instance
	 * @throws TInvalidOperationException if this method is invoked twice or more.
	 */
	public static function setApplication(IApplication $app)
	{
		if(self::$_application!==null)
			throw new TInvalidOperationException('prado_application_singleton_required');
		self::$_application=$app;
	}

	/**
	 * @return IApplication the application singleton, null if the singleton has not be created yet.
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
	 * Original PHP serialize function has a bug that may not serialize
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
	 * Original PHP unserialize function has a bug that may not unserialize
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
	 * @param string component type
	 * @return TComponent component instance of the specified type
	 * @throws TInvalidDataValueException if the component type is unknown
	 */
	public static function createComponent($type)
	{
		if(class_exists($type,false))
			return new $type;
		if(($pos=strrpos($type,'.'))===false)
		{
			// a class name is supplied
			$className=$type;
			if(!class_exists($className,false))
			{
				include_once($className.self::CLASS_FILE_EXT);
			}
			if(class_exists($className,false))
				return new $className;
			else
				throw new TInvalidDataValueException('prado_component_unknown',$type);
		}
		else
		{
			$className=substr($type,$pos+1);
			if(($path=self::getPathOfNamespace($type))!==null)
			{
				// the class type is given in a namespace form
				if(!class_exists($className,false))
				{
					require_once($path.self::CLASS_FILE_EXT);
				}
				if(class_exists($className,false))
					return new $className;
			}
			throw new TInvalidDataValueException('prado_component_unknown',$type);
		}
	}

	/**
	 * Uses a namespace.
	 * A namespace ending with an asterisk '*' refers to a directory, otherwise it represents a PHP file.
	 * If the namespace corresponds to a directory, the directory will be appended
	 * to the include path. If the namespace corresponds to a file, it will be included (require_once).
	 * @param string namespace to be used
	 * @throws TInvalidDataValueException if the namespace is invalid
	 */
	public static function using($namespace)
	{
		if(!isset(self::$_usings[$namespace]))
		{
			if(($path=self::getPathOfNamespace($namespace,self::CLASS_FILE_EXT))===null)
				throw new TInvalidDataValueException('prado_using_invalid',$namespace);
			else
			{
				if($namespace[strlen($namespace)-1]==='*')  // a file
				{
					if(is_dir($path))
					{
						self::$_usings[$namespace]=$path;
						set_include_path(get_include_path().PATH_SEPARATOR.$path);
					}
					else
						throw new TInvalidDataValueException('prado_using_invalid',$namespace);
				}
				else  // a directory
				{
					if(is_file($path))
					{
						self::$_usings[$namespace]=$path;
						require_once($path);
					}
					else
						throw new TInvalidDataValueException('prado_using_invalid',$namespace);
				}
			}
		}
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
		if(isset(self::$_aliases[$alias]))
			return self::$_aliases[$alias];
		else
			return null;
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
			self::$_aliases[$alias]=$rp;
		else
			throw new TInvalidDataValueException('prado_alias_invalid',$alias,$path);
	}

	/**
	 * Fatal error handler.
	 * This method is used in places where exceptions usually cannot be raised
	 * (e.g. magic methods).
	 * It displays the debug backtrace.
	 * @param string error message
	 */
	function fatalError($msg)
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
}

/**
 * Includes TErrorHandler class
 */
require_once(PRADO_DIR.'/Exceptions/TErrorHandler.php');
/**
 * Includes THttpRequest class
 */
require_once(PRADO_DIR.'/Web/THttpRequest.php'); // include TUser
/**
 * Includes THttpResponse class
 */
require_once(PRADO_DIR.'/Web/THttpResponse.php');
/**
 * Includes THttpSession class
 */
require_once(PRADO_DIR.'/Web/THttpSession.php');
/**
 * Includes TAuthorizationRule class
 */
require_once(PRADO_DIR.'/Security/TAuthorizationRule.php');

?>