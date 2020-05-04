<?php
/**
 * TFileUpload class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net>, Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TFileUpload class
 *
 * TFileUpload displays a file upload field on a page. Upon postback, the selected
 * files will be uploaded to the server. The property {@link getHasFile HasFile}
 * indicates whether the file upload is successful. If successful, the file
 * may be obtained by calling {@link saveAs} to save it at a specified place.
 * You can use {@link getFileName FileName}, {@link getFileType FileType},
 * {@link getFileSize FileSize} to get the original client-side file name,
 * the file mime type, and the file size information. If the upload is not
 * successful, {@link getErrorCode ErrorCode} contains the error code
 * describing the cause of failure.
 *
 * Since Prado 4.0 the TFileUpload supports uploading multiple files at once by
 * setting {@link setMultiple Multiple} to true which renders the additional HTML5
 * attribute and adds square brackets to the name attribute. A new method
 * {@link getFiles} is introduced which returns an array of {@link TFileUploadItem}
 * representing each uploaded file.
 *
 * All (old) methods mentioned in the first paragraph (getHasFile, getFileName, getFileType,
 * getFileSize, getErrorCode and saveAs) also take a new optional parameter specifying
 * the file index to get the desired information from. This is for backward compatibility
 * so that old, single file uploads will still work - internally a {@link TFileUploadItem}
 * is also used for a single file upload.
 * If more than one file is uploaded {@link getValidationPropertyValue} returns a comma
 * separated list of original file names instead of a single file name for validation.
 *
 * TFileUpload raises {@link onFileUpload OnFileUpload} event if one or more files are
 * uploaded (whether it succeeds or not).
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net>, Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TFileUpload extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\IPostBackDataHandler, \Prado\Web\UI\IValidatable
{
	/**
	 * Maximum file size (in bytes) allowed to be uploaded, defaults to 1MB.
	 */
	const MAX_FILE_SIZE = 1048576;

	private $_dataChanged = false;
	private $_isValid = true;
	/**
	 * @var bool wether this file upload supports multiple files
	 */
	private $_multiple = false;
	/**
	 * @var array the list of uploaded files represented by {@link TFileUploadItem}
	 */
	private $_files = [];
	/**
	 * @var class name used to instantiate items for uploaded files: {@link TFileUploadItem}
	 */
	protected static $fileUploadItemClass = '\Prado\Web\UI\WebControls\TFileUploadItem';

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
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->getPage()->ensureRenderInForm($this);
		parent::addAttributesToRender($writer);
		$writer->addAttribute('type', 'file');
		$name = $this->getUniqueID();
		if ($this->getMultiple()) {
			$name .= '[]';
			$writer->addAttribute('multiple', 'multiple');
		}
		$writer->addAttribute('name', $name);
		$isEnabled = $this->getEnabled(true);
		if (!$isEnabled && $this->getEnabled()) {  // in this case parent will not render 'disabled'
			$writer->addAttribute('disabled', 'disabled');
		}
	}

	/**
	 * Sets Enctype of the form on the page.
	 * This method overrides the parent implementation and is invoked before render.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (($form = $this->getPage()->getForm()) !== null) {
			if ($this->getPage()->getIsCallback()) {
				$this->getPage()->getCallbackClient()->setAttribute($form, 'enctype', 'multipart/form-data');
			} else {
				$form->setEnctype('multipart/form-data');
			}
		}
		$this->getPage()->getClientScript()->registerHiddenField('MAX_FILE_SIZE', $this->getMaxFileSize());
		if ($this->getEnabled(true)) {
			$this->getPage()->registerRequiresPostData($this);
		}
	}

	/**
	 * @return int the maximum file size, defaults to 1MB (1048576 bytes).
	 * @see setMaxFileSize
	 */
	public function getMaxFileSize()
	{
		return $this->getViewState('MaxFileSize', self::MAX_FILE_SIZE);
	}

	/**
	 * Sets the maximum size that a file can be uploaded.
	 * Note, this is an advisory value to the browser. Sets this property with
	 * a reasonably large size to save users the trouble of waiting
	 * for a big file being transferred only to find that it was too big
	 * and the transfer failed.
	 * @param int $size the maximum upload size allowed for a file.
	 */
	public function setMaxFileSize($size)
	{
		$this->setViewState('MaxFileSize', TPropertyValue::ensureInteger($size), self::MAX_FILE_SIZE);
	}

	/**
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return string the original full path name of the file on the client machine
	 */
	public function getFileName($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getFileName() : '';
	}

	/**
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return int the actual size of the uploaded file in bytes
	 */
	public function getFileSize($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getFileSize() : 0;
	}

	/**
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return string the MIME-type of the uploaded file (such as "image/gif").
	 * This mime type is not checked on the server side and do not take its value for granted.
	 */
	public function getFileType($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getFileType() : '';
	}

	/**
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return string the local name of the file (where it is after being uploaded).
	 * Note, PHP will delete this file automatically after finishing this round of request.
	 */
	public function getLocalName($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getLocalName() : '';
	}

	/**
	 * Returns an error code describing the status of this file uploading.
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return int the error code
	 * @see http://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public function getErrorCode($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getErrorCode() : UPLOAD_ERR_NO_FILE;
	}

	/**
	 * For backward compatibility, the first file is used by default.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return bool whether the file is uploaded successfully
	 */
	public function getHasFile($index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->getHasFile() : false;
	}

	/**
	 * This method is used for multiple file uploads to indicate if all files were uploaded succsessfully.
	 * @return bool whether all files are uploaded successfully
	 */
	public function getHasAllFiles()
	{
		foreach ($this->_files as $file) {
			if (!$file->getHasFile()) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Saves the uploaded file.
	 * Returns an error code describing the status of this file uploading.
	 * For backward compatibility, the first file is used by default.
	 * @param string $fileName the file name used to save the uploaded file
	 * @param bool $deleteTempFile whether to delete the temporary file after saving.
	 * If true, you will not be able to save the uploaded file again.
	 * @param int $index the index of the uploaded file, defaults to 0.
	 * @return bool true if the file saving is successful
	 */
	public function saveAs($fileName, $deleteTempFile = true, $index = 0)
	{
		return isset($this->_files[$index]) ? $this->_files[$index]->saveAs($fileName, $deleteTempFile) : false;
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the control has been changed
	 */
	public function loadPostData($key, $values)
	{
		if (isset($_FILES[$key])) {
			if ($this->getMultiple() || is_array($_FILES[$key]['name'])) {
				foreach ($_FILES[$key]['name'] as $index => $name) {
					$this->_files[$index] = new static::$fileUploadItemClass($name, $_FILES[$key]['size'][$index], $_FILES[$key]['type'][$index], $_FILES[$key]['error'][$index], $_FILES[$key]['tmp_name'][$index]);
				}
			} else {
				$this->_files[0] = new static::$fileUploadItemClass($_FILES[$key]['name'], $_FILES[$key]['size'], $_FILES[$key]['type'], $_FILES[$key]['error'], $_FILES[$key]['tmp_name']);
			}
			return $this->_dataChanged = true;
		} else {
			return false;
		}
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
	 * The method raises <b>OnFileUpload</b> event to fire up the event handler.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onFileUpload($param)
	{
		$this->raiseEvent('OnFileUpload', $this, $param);
	}

	/**
	 * Returns a value indicating whether postback has caused the control data change.
	 * This method is required by the \Prado\Web\UI\IPostBackDataHandler interface.
	 * @return bool whether postback has caused the control data change. False if the page is not in postback mode.
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * Returns the comma separated list of original file names as the property value to be validated.
	 * This method is required by \Prado\Web\UI\IValidatable property.
	 * @return string the property value to be validated
	 */
	public function getValidationPropertyValue()
	{
		return implode(',', array_map(function ($file) {
			return $file->getFileName();
		}, $this->_files));
	}

	/**
	 * Returns true if this control validated successfully.
	 * Defaults to true.
	 * @return bool wether this control validated successfully.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}
	/**
	 * @param bool $value wether this control is valid.
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool wether this file upload supports multiple files.
	 */
	public function getMultiple()
	{
		return $this->_multiple;
	}

	/**
	 * @param bool $value wether this file upload supports multiple files.
	 */
	public function setMultiple($value)
	{
		$this->_multiple = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return TFileUploadItem[] the array of uploaded files.
	 */
	public function getFiles()
	{
		return $this->_files;
	}
}
