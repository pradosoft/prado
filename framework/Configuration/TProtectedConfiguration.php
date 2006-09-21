<?php
/**
 * TProtectedConfiguration class.
 * Provides access to the protected-configuration providers for the current application's configuration file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TProtectedConfiguration.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Configuration
 * @since 3.1
 */
final class TProtectedConfiguration extends TModule 
{
	private $_defaultProvider;
	/**
	 * @var array list of providers available
	 */
	private $_providers=array();
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;
	
	public function getDefaultProvider()
	{
		return $this->_defaultProvider;
	}
	public function setDefaultProvider($value)
	{
		$this->_defaultProvider = TPropertyValue::ensureString($value);
	}
	public function getProvider($value=null)
	{
		if ($value)
			$index = $value;
		else 	
			$index = $this->_defaultProvider;
			
		$provider = $this->_providers[$index];
			
		if (!$provider instanceof TProviderBase)
			throw new TConfigurationException('protectedconfiguration_not_a_provider',$index);
			
		return $provider;
	}
	
	public function init($config)
	{
		if($this->_configFile!==null)
		{
			if(is_file($this->_configFile))
			{
				$dom=new TXmlDocument;
				$dom->loadFromFile($this->_configFile);
				$this->loadConfig($dom);
			}
			else
				throw new TConfigurationException('protectedconfiguration_configfile_invalid',$this->_configFile);
		}
		$this->loadConfig($config);
//		$this->getApplication()->attachEventHandler('OnEndRequest',array($this,'collectLogs'));
	}
	/**
	 * Loads configuration from an XML element
	 * @param TXmlElement configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($xml)
	{
		foreach($xml->getElementsByTagName('provider') as $providerConfig)
		{
			$properties=$providerConfig->getAttributes();
			if(($class=$properties->remove('class'))===null)
				throw new TConfigurationException('protectedconfiguration_providerclass_required');
			$provider=Prado::createComponent($class);
			if(!($provider instanceof TProviderBase))
				throw new TConfigurationException('protectedconfiguration_providertype_invalid');
			foreach($properties as $name=>$value)
				$provider->setSubproperty($name,$value);
			$this->_providers[$provider->getId()]=$provider;
			$provider->init($providerConfig);
		}
	}
}
?>