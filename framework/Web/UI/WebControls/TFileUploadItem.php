<?php
/**
 * TFileUploadItem class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TFileUploadItem class
 *
 * TFileUploadItem represents a single uploaded file from {@link TFileUpload} and
 * is especially needed when {@link TFileUpload::setMultiple} is set to true.
 *
 * See {@link TFileUpload} documentation for more details.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\WebControls
 * @since 4.0
 */
class TFileUploadItem extends \Prado\TComponent
{
	/**
	 * @var integer the size of the uploaded file (in bytes)
	 */
	private $_fileSize = 0;
	/**
	 * @var string The original name of the file on the client machine
	 */
	private $_fileName = '';
	/**
	 * @var string the name of the temporary file storing the uploaded file
	 */
	private $_localName = '';
	/**
	 * @var string the uploaded file mime type
	 */
	private $_fileType = '';
	/**
	 * @var integer error code of the current file upload
	 */
	private $_errorCode = UPLOAD_ERR_NO_FILE;

	public function __construct($fileName, $fileSize, $fileType, $errorCode, $localName)
	{
		$this->_fileName = $fileName;
		$this->_fileSize = $fileSize;
		$this->_fileType = $fileType;
		$this->_errorCode = $errorCode;
		$this->_localName = $localName;
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
	 * @param string $value the local name of the file (where it is after being uploaded).
	 */
	public function setLocalName($value)
	{
		$this->_localName = $value;
	}

	/**
	 * Returns an error code describing the status of this file uploading.
	 * @return integer the error code
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public function getErrorCode()
	{
		return $this->_errorCode;
	}

	/**
	 * Sets the error code describing the status of this file uploading.
	 * @param integer $value the error code
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public function setErrorCode($value)
	{
		$this->_errorCode = $value;
	}

	/**
	 * @return boolean whether the file is uploaded successfully
	 */
	public function getHasFile()
	{
		return $this->_errorCode === UPLOAD_ERR_OK;
	}

	/**
	 * Saves the uploaded file.
	 * @param string the file name used to save the uploaded file
	 * @param boolean whether to delete the temporary file after saving.
	 * If true, you will not be able to save the uploaded file again.
	 * @return boolean true if the file saving is successful
	 */
	public function saveAs($fileName, $deleteTempFile = true)
	{
		if ($this->_errorCode === UPLOAD_ERR_OK) {
			if ($deleteTempFile) {
				return move_uploaded_file($this->_localName, $fileName);
			} elseif (is_uploaded_file($this->_localName)) {
				return file_put_contents($fileName, file_get_contents($this->_localName)) !== false;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @return array the array representation of the TFileUploadItem
	 */
	public function toArray()
	{
		return [
	  'fileName' => $this->_fileName,
	  'fileSize' => $this->_fileSize,
	  'fileType' => $this->_fileType,
	  'errorCode' => $this->_errorCode,
	  'localName' => $this->_localName
	];
	}
}
