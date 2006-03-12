<?php

class TSQLMap extends TModule
{
	private $_SQLMapLibrary='';
	private $_configFile;
	private $_sqlmap;
	private $_provider;

	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.config';

	public function getSQLMapLibrary()
	{
		if(strlen($this->_SQLMapLibrary) < 1)
			return dirname(__FILE__).'/SQLMap';
		else
			return $this->_SQLMapLibrary;
	}

	public function setSQLMapLibrary($path)
	{
		$this->_SQLMapLibrary = Prado::getPathOfNamespace($path);
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	public function init($xml)
	{
		$config = $xml->getElementByTagName('provider');
		$class = $config->getAttribute('class');
		$provider = Prado::createComponent($class);
		$datasource = $config->getElementByTagName('datasource');
		$properties = $datasource->getAttributes();
		foreach($properties as $name=>$value)
			$provider->setSubproperty($name,$value);
		$this->_provider = $provider;
	}

	/**
	 * @param string external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if(($this->_configFile=Prado::getPathOfNamespace(
					$value,self::CONFIG_FILE_EXT))===null)
			throw new TConfigurationException('sqlmap_configfile_invalid',$value);
	}

	protected function configure($configFile)
	{
		include($this->getSQLMapLibrary().'/TSqlMapper.php');
		$builder = new TDomSqlMapBuilder();
		$this->_sqlmap = $builder->configure($configFile);
		if(!is_null($this->_provider))
			$this->_sqlmap->setDataProvider($this->_provider);
	}

	public function getClient()
	{
		if(is_null($this->_sqlmap))
			$this->configure($this->getConfigFile());
		return $this->_sqlmap;
	}
}

?>