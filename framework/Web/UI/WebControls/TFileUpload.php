<?php
/**
 * TFileUpload class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net>, Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TFileUpload class
 *
 * TFileUpload displays a file upload field on a page. Upon postback,
 * the text entered into the field will be treated as the name of the file
 * that will be uploaded to the server. The property {@link getHasFile HasFile}
 * indicates whether the file upload is successful. If successful, the file
 * may be obtained by calling {@link saveAs} to save it at a specified place.
 * You can use {@link getFileName}, {@link getFileType}, {@link getFileSize}
 * to get the original client-side file name, the file mime type, and the
 * file size information. If the upload is not successful, {@link getErrorCode ErrorCode}
 * contains the error code describing the cause of failure.
 *
 * TFileUpload raises {@link onFileUpload FileUpload} event if a file is uploaded
 * (whether it succeeds or not).
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net>, Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TFileUpload extends TWebControl implements IPostBackDataHandler, IValidatable
{
	/**
	 * Maximum file size (in bytes) allowed to be uploaded, defaults to 1MB.
	 */
	const MAX_FILE_SIZE=1048576;
	/**
	 * @var integer the size of the uploaded file (in bytes)
	 */
	private $_fileSize=0;
	/**
	 * @var string The original name of the file on the client machine
	 */
	private $_fileName='';
	/**
	 * @var string the name of the temporary file storing the uploaded file
	 */
	private $_localName='';
	/**
	 * @var string the uploaded file mime type
	 */
	private $_fileType='';
	/**
	 * @var integer error code of the current file upload
	 */
	private $_errorCode=UPLOAD_ERR_NO_FILE;

	/**
	 * @return string tag name of the file upload control
	 */
	protected function getTagName()
	{
		return 'input';
	}

	/**
	 * Sets name attribute to the unique ID of the control.
	 * This method overrides the parent implementation with additional file update control specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->getPage()->ensureRenderInForm($this);
		parent::addAttributesToRender($writer);
		$writer->addAttribute('type','file');
		$writer->addAttribute('name',$this->getUniqueID());
	}

	/**
	 * Sets Enctype of the form on the page.
	 * This method overrides the parent implementation and is invoked before render.
	 * @param mixed event parameter
	 */
	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		if(($form=$this->getPage()->getForm())!==null)
			$form->setEnctype('multipart/form-data');
		$this->getPage()->getClientScript()->registerHiddenField('MAX_FILE_SIZE',$this->getMaxFileSize());
		if($this->getEnabled(true))
			$this->getPage()->registerRequiresPostData($this);
	}

	/**
	 * @return integer the maximum file size, defaults to 1MB (1048576 bytes).
	 * @see setMaxFileSize
	 */
	public function getMaxFileSize()
	{
		return $this->getViewState('MaxFileSize',self::MAX_FILE_SIZE);
	}

	/**
	 * Sets the maximum size that a file can be uploaded.
	 * Note, this is an advisory value to the browser. Sets this property with
	 * a reasonably large size to save users the trouble of waiting
	 * for a big file being transferred only to find that it was too big
	 * and the transfer failed.
	 * @param int the maximum upload size allowed for a file.
	 */
	public function setMaxFileSize($size)
	{
		$this->setViewState('MaxFileSize',TPropertyValue::ensureInteger($size),self::MAX_FILE_SIZE);
	}

	/**
	 * @return string the original full path name of the file on the client machine
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}

	/**
	 * @return integer the actual size of the uploaded file in bytes
	 */
	public function getFileSize()
	{
		return $this->_fileSize;
	}

	/**
	 * @return string the MIME-type of the uploaded file (such as "image/gif").
	 * This mime type is not checked on the server side and do not take its value for granted.
	 */
	public function getFileType()
	{
		return $this->_fileType;
	}

	/**
	 * @return string the local name of the file (where it is after being uploaded).
	 * Note, PHP will delete this file automatically after finishing this round of request.
	 */
	public function getLocalName()
	{
		return $this->_localName;
	}

	/**
	 * @return integer an error code describing the status of this file uploading
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public function getErrorCode()
	{
		return $this->_errorCode;
	}

	/**
	 * @return boolean whether the file is uploaded successfully
	 */
	public function getHasFile()
	{
		return $this->_errorCode===UPLOAD_ERR_OK;
	}

	/**
	 * Saves the uploaded file.
	 * @param string the file name used to save the uploaded file
	 * @param boolean whether to delete the temporary file after saving.
	 * If true, you will not be able to save the uploaded file again.
	 * @throws TInvalidOperationException file uploading failed or the uploaded
	 * file cannot be found on the server.
	 */
	public function saveAs($fileName,$deleteTempFile=true)
	{
		if($this->_errorCode===UPLOAD_ERR_OK)
		{
			if($deleteTempFile)
				move_uploaded_file($this->_localName,$fileName);
			else if(is_uploaded_file($this->_localName))
				file_put_contents($fileName,file_get_contents($this->_localName));
			else
				throw new TInvalidOperationException('fileupload_saveas_failed');
		}
		else
			throw new TInvalidOperation('fileupload_saveas_forbidden');
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values)
	{
		if(isset($_FILES[$key]))
		{
			$this->_fileName=$_FILES[$key]['name'];
			$this->_fileSize=$_FILES[$key]['size'];
			$this->_fileType=$_FILES[$key]['type'];
			$this->_errorCode=$_FILES[$key]['error'];
			$this->_localName=$_FILES[$key]['tmp_name'];
			return true;
		}
		else
			return false;
	}

	/**
	 * Raises postdata changed event.
	 * This method calls {@link onFileUpload} method.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		$this->onFileUpload(null);
	}

	/**
	 * This method is invoked when a file is uploaded during a postback.
	 * The method raises <b>FileUpload</b> event to fire up the event handler.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onFileUpload($param)
	{
		$this->raiseEvent('FileUpload',$this,$param);
	}

	/**
	 * Returns the original file name as the property value to be validated.
	 * This method is required by IValidatable property.
	 * @return mixed the property value to be validated
	 */
	public function getValidationPropertyValue()
	{
		return $this->getFileName();
	}
}

?>