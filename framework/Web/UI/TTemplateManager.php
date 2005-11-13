<?php

class TTemplateManager extends TComponent implements IModule
{
	const TEMPLATE_FILE_EXT='.tpl';
	const TEMPLATE_CACHE_PREFIX='prado:template:';
	private $_application;
	/**
	 * @var string module ID
	 */
	private $_id;

	public function init($application,$config)
	{
		$this->_application=$application;
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	public function getTemplateByClassName($className)
	{
		$class=new ReflectionClass($className);
		$tplFile=dirname($class->getFileName()).'/'.$className.self::TEMPLATE_FILE_EXT;
		return $this->getTemplateByFileName($tplFile);
	}

	public function getTemplateByFileName($fileName)
	{
		if(is_file($fileName))
		{
			if(($cache=$this->_application->getCache())===null)
				return new TTemplate(file_get_contents($fileName));
			else
			{
				$array=$cache->get(self::TEMPLATE_CACHE_PREFIX.$fileName);
				if(is_array($array))
				{
					list($template,$timestamp)=$array;
					if(filemtime($fileName)<$timestamp)
						return $template;
				}
				$template=new TTemplate(file_get_contents($fileName));
				$cache->set(self::TEMPLATE_CACHE_PREFIX.$fileName,array($template,time()));
				return $template;
			}
		}
		else
			return null;
	}
}

?>