<?php

/**
 * TImageValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TImageValidator class
 *
 * TImageValidator validates the image files selected in a
 * {@see \Prado\Web\UI\WebControls\TFileUpload} control. Every restriction of
 * {@see \Prado\Web\UI\WebControls\TFileValidator} applies, and each file must be a
 * readable image whose dimensions satisfy the following restrictions; 0 disables
 * either check:
 * - {@see setMinImageWidth MinImageWidth} / {@see setMaxImageWidth MaxImageWidth} → pixel width bounds.
 * - {@see setMinImageHeight MinImageHeight} / {@see setMaxImageHeight MaxImageHeight} → pixel height bounds.
 *
 * A file that getimagesize() cannot read as an image fails the validation.
 *
 * Client side, the image dimensions are read asynchronously through the File
 * API when the selection changes. A file whose dimensions are not yet decoded
 * passes the client-side validation and the validator re-validates when the
 * decoding completes. The server-side validation is authoritative.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TImageValidator extends TFileValidator
{
	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TImageValidator';
	}

	/**
	 * @return int the minimum image width in pixels. Defaults to 0, meaning no minimum.
	 */
	public function getMinImageWidth()
	{
		return $this->getViewState('MinImageWidth', 0);
	}

	/**
	 * Sets the minimum width in pixels required for each image.
	 * @param int $value the minimum image width in pixels, 0 for no minimum.
	 */
	public function setMinImageWidth($value)
	{
		$this->setViewState('MinImageWidth', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the maximum image width in pixels. Defaults to 0, meaning no limit.
	 */
	public function getMaxImageWidth()
	{
		return $this->getViewState('MaxImageWidth', 0);
	}

	/**
	 * Sets the maximum width in pixels allowed for each image.
	 * @param int $value the maximum image width in pixels, 0 for no limit.
	 */
	public function setMaxImageWidth($value)
	{
		$this->setViewState('MaxImageWidth', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the minimum image height in pixels. Defaults to 0, meaning no minimum.
	 */
	public function getMinImageHeight()
	{
		return $this->getViewState('MinImageHeight', 0);
	}

	/**
	 * Sets the minimum height in pixels required for each image.
	 * @param int $value the minimum image height in pixels, 0 for no minimum.
	 */
	public function setMinImageHeight($value)
	{
		$this->setViewState('MinImageHeight', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the maximum image height in pixels. Defaults to 0, meaning no limit.
	 */
	public function getMaxImageHeight()
	{
		return $this->getViewState('MaxImageHeight', 0);
	}

	/**
	 * Sets the maximum height in pixels allowed for each image.
	 * @param int $value the maximum image height in pixels, 0 for no limit.
	 */
	public function setMaxImageHeight($value)
	{
		$this->setViewState('MaxImageHeight', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * Checks one uploaded file against the parent restrictions and the image
	 * restrictions.
	 * @param TFileUploadItem $file the uploaded file to check.
	 * @return bool whether the file satisfies the restrictions.
	 */
	protected function validateFile($file)
	{
		return parent::validateFile($file) && $this->validateImage($file);
	}

	/**
	 * Checks that an uploaded file is a readable image whose dimensions satisfy
	 * the image restrictions. A file that cannot be read as an image fails.
	 * @param TFileUploadItem $file the uploaded file to check.
	 * @return bool whether the file is an image satisfying the restrictions.
	 */
	protected function validateImage($file)
	{
		if (($size = $this->getImageSize($file)) === null) {
			return false;
		}
		[$width, $height] = $size;
		if ($width <= 0 || $height <= 0) {
			return false;
		}
		if (($min = $this->getMinImageWidth()) > 0 && $width < $min) {
			return false;
		}
		if (($max = $this->getMaxImageWidth()) > 0 && $width > $max) {
			return false;
		}
		if (($min = $this->getMinImageHeight()) > 0 && $height < $min) {
			return false;
		}
		if (($max = $this->getMaxImageHeight()) > 0 && $height > $max) {
			return false;
		}
		return true;
	}

	/**
	 * Returns the pixel dimensions of an uploaded image file.
	 * @param TFileUploadItem $file the uploaded file.
	 * @return ?array the [width, height] of the image, null when the local file
	 * is unavailable or is not a readable image.
	 */
	protected function getImageSize($file)
	{
		$localName = $file->getLocalName();
		if ($localName === '' || !is_file($localName)) {
			return null;
		}
		if (($info = @getimagesize($localName)) === false) {
			return null;
		}
		return [$info[0], $info[1]];
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['MinImageWidth'] = $this->getMinImageWidth();
		$options['MaxImageWidth'] = $this->getMaxImageWidth();
		$options['MinImageHeight'] = $this->getMinImageHeight();
		$options['MaxImageHeight'] = $this->getMaxImageHeight();
		return $options;
	}
}
