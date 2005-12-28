<?php

class TPageStatePersister extends TModule implements IStatePersister
{
	private $_application;
	private $_privateKey=null;

	/**
	 * Initializes the service.
	 * This method is required by IModule interface.
	 * @param TApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application, $config)
	{
		parent::init($application,$config);
		$this->_application=$application;
		$application->getService()->setPageStatePersister($this);
	}

	public function save($state)
	{
		$data=Prado::serialize($state);
		$hmac=$this->computeHMAC($data,$this->getPrivateKey());
		if(extension_loaded('zlib'))
			$data=gzcompress($hmac.$data);
		else
			$data=$hmac.$data;
		$this->_application->getService()->getRequestedPage()->getClientScript()->registerHiddenField(TPage::FIELD_PAGESTATE,base64_encode($data));
	}

	public function load()
	{
		$str=base64_decode($this->_application->getRequest()->getItems()->itemAt(TPage::FIELD_PAGESTATE));
		if($str==='')
			return null;
		if(extension_loaded('zlib'))
			$data=gzuncompress($str);
		else
			$data=$str;
		if($data!==false && strlen($data)>32)
		{
			$hmac=substr($data,0,32);
			$state=substr($data,32);
			if($hmac===$this->computeHMAC($state,$this->getPrivateKey()))
				return Prado::unserialize($state);
		}
		throw new TInvalidDataValueException('pagestatepersister_viewstate_corrupted.');
	}

	protected function generatePrivateKey()
	{
		$v1=rand();
		$v2=rand();
		$v3=rand();
		return md5("$v1$v2$v3");
	}

	public function getPrivateKey()
	{
		if(empty($this->_privateKey))
		{
			if(($this->_privateKey=$this->_application->getGlobalState('prado:pagestatepersister:privatekey'))===null)
			{
				$this->_privateKey=$this->generatePrivateKey();
				$this->_application->setGlobalState('prado:pagestatepersister:privatekey',$this->_privateKey,null);
			}
		}
		return $this->_privateKey;
	}

	public function setPrivateKey($value)
	{
		if(strlen($value)<8)
			throw new TConfigurationException('pagestatepersister_privatekey_invalid');
		$this->_privateKey=$value;
	}

	private function computeHMAC($data,$key)
	{
		if (strlen($key) > 64)
			$key = pack('H32', md5($key));
		else if (strlen($key) < 64)
			$key = str_pad($key, 64, "\0");
		return md5((str_repeat("\x5c", 64) ^ substr($key, 0, 64)) . pack('H32', md5((str_repeat("\x36", 64) ^ substr($key, 0, 64)) . $data)));
	}
}

?>