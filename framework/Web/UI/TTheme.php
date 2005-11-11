<?php

class TTheme extends TComponent
{
	private $_themePath;
	private $_skins=array();

	public function __construct($name)
	{
		$this->_themePath=$name;
		$this->initialize();
	}

	private function initialize()
	{
		if(($theme=opendir($this->_themePath))===false)
			throw new Exception("Invalid theme ".$this->_themePath);
		while(($file=readdir($theme))!==false)
		{
			if(basename($file,'.skin')!==$file)
				$this->parseSkinFile($this->_themePath.'/'.$file);
		}
		closedir($theme);
	}

	private function parseSkinFile($fileName)
	{
		if(($skin=simplexml_load_file($fileName))===false)
			throw new Exception("Parsing $fileName failed.");
		foreach($skin->children() as $type=>$control)
		{
			$attributes=array();
			foreach($control->attributes() as $name=>$value)
			{
				$attributes[strtolower($name)]=(string)$value;
			}
			$skinID=isset($attributes['skinid'])?(string)$attributes['skinid']:0;
			unset($attributes['skinid']);
			if(isset($this->_skins[$type][$skinID]))
				throw new Exception("Duplicated skin $type.$skinID");
			else
				$this->_skins[$type][$skinID]=$attributes;
		}
	}

	public function applySkin($control)
	{
		$type=get_class($control);
		if(($id=$control->getSkinID())==='')
			$id=0;
		if(isset($this->_skins[$type][$id]))
		{
			foreach($this->_skins[$type][$id] as $name=>$value)
			{
				$control->setSubProperty($name,$value);
			}
		}
		else
			return;
	}
}


?>