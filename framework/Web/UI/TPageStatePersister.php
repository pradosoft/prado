<?php
/**
 * TPageStatePersister class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TPageStatePersister class
 *
 * TPageStatePersister implements a page state persistent method based on
 * form hidden fields. It is the default way of storing page state.
 * Should you need to access this module, you may get it via
 * {@link TPageService::getPageStatePersister}.
 *
 * TPageStatePersister uses a private key to generate a private unique hash
 * code to prevent the page state from being tampered.
 * By default, the private key is a randomly generated string.
 * You may specify it explicitly by setting the {@link setPrivateKey PrivateKey} property.
 * This may be useful if your application is running on a server farm.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TPageStatePersister extends TModule implements IStatePersister
{
	/**
	 * @var string private key
	 */
	private $_privateKey=null;

	/**
	 * Registers the module with the page service.
	 * This method is required by IModule interface and is invoked when the module is initialized.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		$this->getService()->setPageStatePersister($this);
	}

	/**
	 * Saves state in hidden fields.
	 * @param mixed state to be stored
	 */
	public function save($state)
	{
		Prado::coreLog("Saving state");
		$data=Prado::serialize($state);
		$hmac=$this->computeHMAC($data,$this->getPrivateKey());
		if(extension_loaded('zlib'))
			$data=gzcompress($hmac.$data);
		else
			$data=$hmac.$data;
		$this->getService()->getRequestedPage()->getClientScript()->registerHiddenField(TPage::FIELD_PAGESTATE,base64_encode($data));
	}

	/**
	 * Loads page state from hidden fields.
	 * @return mixed the restored state
	 * @throws THttpException if page state is corrupted
	 */
	public function load()
	{
		Prado::coreLog("Loading state");
		$str=base64_decode($this->getApplication()->getRequest()->getItems()->itemAt(TPage::FIELD_PAGESTATE));
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
		throw new THttpException(400,'pagestatepersister_pagestate_corrupted');
	}

	/**
	 * Generates a random private key used for hashing the state.
	 * You may override this method to provide your own way of private key generation.
	 * @return string  the rondomly generated private key
	 */
	protected function generatePrivateKey()
	{
		$v1=rand();
		$v2=rand();
		$v3=rand();
		return md5("$v1$v2$v3");
	}

	/**
	 * @return string private key used for hashing the state.
	 */
	public function getPrivateKey()
	{
		if(empty($this->_privateKey))
		{
			if(($this->_privateKey=$this->getApplication()->getGlobalState('prado:pagestatepersister:privatekey'))===null)
			{
				$this->_privateKey=$this->generatePrivateKey();
				$this->getApplication()->setGlobalState('prado:pagestatepersister:privatekey',$this->_privateKey,null);
			}
		}
		return $this->_privateKey;
	}

	/**
	 * @param string private key used for hashing the state.
	 * @throws TInvalidDataValueException if the length of the private key is shorter than 8.
	 */
	public function setPrivateKey($value)
	{
		if(strlen($value)<8)
			throw new TInvalidDataValueException('pagestatepersister_privatekey_invalid');
		$this->_privateKey=$value;
	}

	/**
	 * Computes a hashing code based on the input data and the private key.
	 * @param string input data
	 * @param string the private key
	 * @return string the hashing code
	 */
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