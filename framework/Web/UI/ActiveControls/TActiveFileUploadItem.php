<?php
/**
 * TActiveFileUploadItem class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TActiveFileUploadItem class
 *
 * TActiveFileUploadItem represents a single uploaded file from {@see \Prado\Web\UI\ActiveControls\TActiveFileUpload} and
 * is especially needed when {@see \Prado\Web\UI\WebControls\TFileUpload::setMultiple} is set to true.
 *
 * See {@see \Prado\Web\UI\WebControls\TFileUpload} documentation for more details.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 4.0
 */
class TActiveFileUploadItem extends \Prado\Web\UI\WebControls\TFileUploadItem
{
	/**
	 * Saves the uploaded file.
	 * @param string $fileName the file name used to save the uploaded file
	 * @param bool $deleteTempFile whether to delete the temporary file after saving.
	 * If true, you will not be able to save the uploaded file again.
	 * @return bool true if the file saving is successful
	 */
	public function saveAs($fileName, $deleteTempFile = true)
	{
		if (($this->_errorCode === UPLOAD_ERR_OK) && (file_exists($this->_localName))) {
			if ($deleteTempFile) {
				return rename($this->_localName, $fileName);
			} else {
				return copy($this->_localName, $fileName);
			}
		} else {
			return false;
		}
	}
}
