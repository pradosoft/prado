<?php

require_once(PRADO_DIR.'/Web/UI/TPageStatePersister.php');

class THiddenFieldPageStatePersister extends TPageStatePersister
{
	private $_page;

	public function __construct($page)
	{
		$this->_page=$page;
	}

	public function save($state)
	{
		$data=Prado::serialize($state);
		$hmac=$this->computeHMAC($data,$this->getKey());
		if(function_exists('gzuncompress') && function_exists('gzcompress'))
			$data=gzcompress($hmac.$data);
		else
			$data=$hmac.$data;
		$this->_page->saveStateField($data);
	}

	public function load()
	{
		$str=$this->_page->loadStateField();
		if($str==='')
			return null;
		if(function_exists('gzuncompress') && function_exists('gzcompress'))
			$data=gzuncompress($str);
		else
			$data=$str;
		if($data!==false && strlen($data)>32)
		{
			$hmac=substr($data,0,32);
			$state=substr($data,32);
			if($hmac===$this->computeHMAC($state,$this->getKey()))
				return Prado::unserialize($state);
		}
		throw new Exception('viewstate data is corrupted.');
	}

	private function getKey()
	{
		return 'abcdefe';
	}

	private function computeHMAC($data,$key)
	{
		if (strlen($key) > 64)
			$key = pack('H32', md5($key));
		elseif (strlen($key) < 64)
			$key = str_pad($key, 64, "\0");
		return md5((str_repeat("\x5c", 64) ^ substr($key, 0, 64)) . pack('H32', md5((str_repeat("\x36", 64) ^ substr($key, 0, 64)) . $data)));
	}
}

?>