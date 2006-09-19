<?php
/**
 * TProviderBase class.
 * Provides a base implementation for the extensible provider model.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TProviderBase.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Configuration.Provider
 * @since 3.1
 */
abstract class TProviderBase
{
	private $_Description;
	private $_Initialized = false;
	private $_name;

	public function __construct(){}

	public function getDescription()
	{
		return $this->_Description;
	}
	public function getName()
	{
		return $this->_name;
	}
	public function Initialize($name,$config)
	{
		if ($this->_Initialized)
		{
			throw new TProviderException('Provider_Already_Initialized');
		}
		$this->_Initialized=true;

		if ($name === null)
		{
			throw new TProviderException('name');
		}

		if (strlen($name) == 0)
		{
			throw new TProviderException('Config_provider_name_null_or_empty');
		}

		$this->_name = TPropertyValue::ensureString($name);

		if ($config !== null && is_array($config))
		{
			$this->_Description = TPropertyValue::ensureString($config['description']);
			unset($config['description']);
		}
	}
}
?>