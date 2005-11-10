<?php

class TErrorHandler extends TComponent implements IErrorHandler
{
	/**
	 * @var string module ID
	 */
	private $_id;
	/**
	 * @var boolean whether the module is initialized
	 */
	private $_initialized=false;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param IApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		$application->attachEventHandler('Error',array($this,'handle'));
		$this->_initialized=true;
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

	public function handle($sender,$param)
	{ echo '...........................';
		echo $param;
	}
}

?>