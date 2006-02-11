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
 * form hidden fields.
 *
 * Depending on the {@link TPage::getEnableStateHMAC() EnableStateHMAC}
 * and {@link TPage::getEnableStateEncryption() EnableStateEncryption},
 * TPageStatePersister may do HMAC validation and encryption to prevent
 * the state data from being tampered or viewed.
 * The private keys and hashing/encryption methods are determined by
 * {@link TApplication::getSecurityManager() SecurityManager}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TPageStatePersister extends TComponent implements IPageStatePersister
{
	private $_page;

	/**
	 * @param TPage the page that this persister works for
	 */
	public function getPage()
	{
		return $this->_page;
	}

	/**
	 * @param TPage the page that this persister works for
	 */
	public function setPage(TPage $page)
	{
		$this->_page=$page;
	}

	/**
	 * Saves state in hidden fields.
	 * @param mixed state to be stored
	 */
	public function save($state)
	{
		Prado::trace("Saving state",'System.Web.UI.TPageStatePersister');
		if($this->_page->getEnableStateHMAC())
			$data=$this->getApplication()->getSecurityManager()->hashData(Prado::serialize($state));
		else
			$data=Prado::serialize($state);
		if($this->_page->getEnableStateEncryption())
			$data=$this->getApplication()->getSecurityManager()->encrypt($data);
		if(extension_loaded('zlib'))
			$data=gzcompress($data);
		$this->_page->getClientScript()->registerHiddenField(TPage::FIELD_PAGESTATE,base64_encode($data));
	}

	/**
	 * Loads page state from hidden fields.
	 * @return mixed the restored state
	 * @throws THttpException if page state is corrupted
	 */
	public function load()
	{
		Prado::trace("Loading state",'System.Web.UI.TPageStatePersister');
		$str=base64_decode($this->getRequest()->itemAt(TPage::FIELD_PAGESTATE));
		if($str==='')
			return null;
		if(extension_loaded('zlib'))
			$data=gzuncompress($str);
		else
			$data=$str;
		if($data!==false)
		{
			if($this->_page->getEnableStateEncryption())
				$data=$this->getApplication()->getSecurityManager()->decrypt($data);
			if($this->_page->getEnableStateHMAC())
			{
				if(($data=$this->getApplication()->getSecurityManager()->validateData($data))!==null)
					return Prado::unserialize($data);
			}
			else
				return $data;
		}
		throw new THttpException(400,'pagestatepersister_pagestate_corrupted');
	}
}

?>