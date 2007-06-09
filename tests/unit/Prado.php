<?php

require_once 'PradoBase.php';

/**
 * Extended Prado class which allows to redefine application instances and aliases.
 */
class Prado extends PradoBase {
	
	private static $_application = null;
	private static $_aliases = array('System'=>PRADO_DIR);
	private static $_usings=array();

	public static function setApplication($application) {
		self::$_application = $application;
	}

	public static function getApplication() {
		return self::$_application;
	}
	
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
				self::$_usings[$namespace]=$path;
				set_include_path(get_include_path().PATH_SEPARATOR.$path);
			}
			else  // a file
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
		}
		else
			throw new TInvalidDataValueException('prado_using_invalid',$namespace);
	}
	
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

	public static function getPathOfAlias($alias)
	{
		return isset(self::$_aliases[$alias])?self::$_aliases[$alias]:null;
	}

	protected static function getPathAliases()
	{
		return self::$_aliases;
	}
	
	public static function setPathOfAlias($alias, $path) {
		if(($rp=realpath($path))!==false && is_dir($rp)) {
			if(strpos($alias,'.') === false)
				self::$_aliases[$alias] = $rp;
			else
				throw new TInvalidDataValueException('prado_aliasname_invalid',$alias);
		}
		else
			throw new TInvalidDataValueException('prado_alias_invalid',$alias,$path);
	}
	
	
	/*public static function setPathOfAlias($alias, $path) {
		if(($rp=realpath($path)) !== false && is_dir($rp)) {
			if(strpos($alias,'.') === false) {
				self::$_aliases[$alias]=$rp;
			} else {
				throw new TInvalidDataValueException('prado_aliasname_invalid', $alias);
			}
		} else {
			throw new TInvalidDataValueException('prado_alias_invalid', $alias, $path);
		}	
	}*/
}

?>