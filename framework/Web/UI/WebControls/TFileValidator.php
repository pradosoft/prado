<?php

/**
 * TFileValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;
use Prado\Web\TMediaType;

/**
 * TFileValidator class
 *
 * TFileValidator validates the files selected in a {@see \Prado\Web\UI\WebControls\TFileUpload}
 * control. Each selected file is checked against the following restrictions:
 * - {@see setMaxFileSize MaxFileSize} → maximum file size in bytes. The default 0
 *   uses the {@see TFileUpload::getMaxFileSize MaxFileSize} of the target control.
 * - {@see setMinFileSize MinFileSize} → minimum file size in bytes; 0 disables the check.
 * - {@see setAllowedFileExtensions AllowedFileExtensions} → comma separated list of
 *   file name extensions, compared case-insensitively; an empty list allows every extension.
 * - {@see setAllowedFileTypes AllowedFileTypes} → comma separated list of MIME types;
 *   "image/*" matches every image subtype and an empty list allows every type.
 *
 * The number of selected files is checked against {@see setMinFileCount MinFileCount}
 * and {@see setMaxFileCount MaxFileCount} when the target allows
 * {@see TFileUpload::setMultiple Multiple} files; 0 disables either check. The
 * combined size of the selected files is checked against
 * {@see setTotalMaxFileSize TotalMaxFileSize}; 0 disables the check.
 *
 * With {@see setCheckExtensionMimeType CheckExtensionMimeType}, the MIME type
 * sniffed from the file content must correspond to the file name extension.
 * This detects files renamed to pass an extension restriction. The check runs
 * server side only and requires the fileinfo extension; extensions absent from
 * the known extension map pass unchecked.
 *
 * When AllowedFileExtensions and AllowedFileTypes are both empty, the type
 * restrictions derive from the {@see TFileUpload::getAccept Accept} property or
 * "accept" attribute of the target control. A file is then valid when it matches
 * any accept token, following the HTML file picker semantics: ".jpg" tokens match
 * the file name extension and MIME tokens match the file type.
 *
 * The validation succeeds when no file is selected. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to require a file selection.
 * A file failing the upload with a PHP error code, such as exceeding the server
 * file size limits, fails the validation.
 *
 * When {@see TBaseValidator::setEnableClientScript EnableClientScript} is true
 * (the default), the same checks run in the browser through the HTML5 File API
 * before the files transfer to the server. Server side, the MIME type of each
 * file is determined with the fileinfo extension when available, falling back
 * to the browser supplied type.
 *
 * The "{files}" token in {@see TBaseValidator::setErrorMessage ErrorMessage} is
 * replaced with the comma separated names of the invalid files.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFileValidator extends TBaseValidator
{
	/**
	 * @var string[] the names of the files that failed the last validation.
	 */
	private $_invalidFileNames = [];

	/**
	 * Map of lower case file name extension → sniffed MIME types that the
	 * content of a file with that extension can report. Used by
	 * {@see setCheckExtensionMimeType CheckExtensionMimeType}. Subclasses can
	 * extend the map.
	 * @var array<string, string[]>
	 */
	protected static $extensionMimeTypes = [
		'avif' => [TMediaType::AVIF],
		'bmp' => [TMediaType::BMP, 'image/x-ms-bmp'],
		'gif' => [TMediaType::GIF],
		'ico' => [TMediaType::ICON, 'image/vnd.microsoft.icon'],
		'jpeg' => [TMediaType::JPEG],
		'jpg' => [TMediaType::JPEG],
		'png' => [TMediaType::PNG],
		'svg' => [TMediaType::SVG, TMediaType::XML_TEXT, TMediaType::XML, TMediaType::PLAIN],
		'tif' => [TMediaType::TIFF],
		'tiff' => [TMediaType::TIFF],
		'webp' => [TMediaType::WEBP],
		'css' => [TMediaType::CSS, TMediaType::PLAIN],
		'csv' => [TMediaType::CSV, TMediaType::PLAIN, 'application/csv'],
		'htm' => [TMediaType::HTML],
		'html' => [TMediaType::HTML],
		'js' => [TMediaType::JAVASCRIPT, 'application/javascript', TMediaType::PLAIN],
		'json' => [TMediaType::JSON, TMediaType::PLAIN],
		'log' => [TMediaType::PLAIN],
		'md' => [TMediaType::MARKDOWN, TMediaType::PLAIN],
		'txt' => [TMediaType::PLAIN],
		'xml' => [TMediaType::XML, TMediaType::XML_TEXT],
		'bz2' => [TMediaType::BZIP2],
		'doc' => [TMediaType::DOC, 'application/vnd.ms-office', 'application/cdfv2'],
		'docx' => [TMediaType::DOCX, TMediaType::ZIP],
		'gz' => [TMediaType::GZIP, 'application/x-gzip'],
		'pdf' => [TMediaType::PDF],
		'ppt' => [TMediaType::PPT, 'application/vnd.ms-office', 'application/cdfv2'],
		'pptx' => [TMediaType::PPTX, TMediaType::ZIP],
		'rtf' => [TMediaType::RTF, 'text/rtf'],
		'tar' => [TMediaType::TAR],
		'xls' => [TMediaType::XLS, 'application/vnd.ms-office', 'application/cdfv2'],
		'xlsx' => [TMediaType::XLSX, TMediaType::ZIP],
		'xz' => [TMediaType::XZ],
		'zip' => [TMediaType::ZIP],
		'aac' => [TMediaType::AUDIO_AAC],
		'mp3' => [TMediaType::AUDIO_MPEG],
		'oga' => [TMediaType::AUDIO_OGG, 'application/ogg'],
		'ogg' => [TMediaType::AUDIO_OGG, 'application/ogg'],
		'wav' => [TMediaType::AUDIO_WAV, 'audio/x-wav'],
		'm4v' => [TMediaType::VIDEO_MP4],
		'mp4' => [TMediaType::VIDEO_MP4],
		'ogv' => [TMediaType::VIDEO_OGG, 'application/ogg'],
		'webm' => [TMediaType::VIDEO_WEBM, TMediaType::AUDIO_WEBM],
		'otf' => [TMediaType::OTF, 'font/sfnt', 'application/font-sfnt'],
		'ttf' => [TMediaType::TTF, 'font/sfnt', 'application/font-sfnt'],
		'woff' => [TMediaType::WOFF, 'application/font-woff'],
		'woff2' => [TMediaType::WOFF2],
	];

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TFileValidator';
	}

	/**
	 * @return int the maximum file size in bytes. Defaults to 0, meaning the
	 * {@see TFileUpload::getMaxFileSize MaxFileSize} of the target control is used.
	 */
	public function getMaxFileSize()
	{
		return $this->getViewState('MaxFileSize', 0);
	}

	/**
	 * Sets the maximum size in bytes allowed for each file.
	 * @param int $value the maximum file size in bytes, 0 to use the MaxFileSize of the target control.
	 */
	public function setMaxFileSize($value)
	{
		$this->setViewState('MaxFileSize', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the minimum file size in bytes. Defaults to 0, meaning no minimum.
	 */
	public function getMinFileSize()
	{
		return $this->getViewState('MinFileSize', 0);
	}

	/**
	 * Sets the minimum size in bytes required for each file.
	 * @param int $value the minimum file size in bytes, 0 for no minimum.
	 */
	public function setMinFileSize($value)
	{
		$this->setViewState('MinFileSize', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the maximum combined size of the files in bytes. Defaults to 0, meaning no limit.
	 */
	public function getTotalMaxFileSize()
	{
		return $this->getViewState('TotalMaxFileSize', 0);
	}

	/**
	 * Sets the maximum combined size in bytes allowed for all selected files
	 * together. This helps a {@see TFileUpload::setMultiple Multiple} selection
	 * stay under the "post_max_size" PHP limit.
	 * @param int $value the maximum combined file size in bytes, 0 for no limit.
	 */
	public function setTotalMaxFileSize($value)
	{
		$this->setViewState('TotalMaxFileSize', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the maximum number of files. Defaults to 0, meaning no limit.
	 */
	public function getMaxFileCount()
	{
		return $this->getViewState('MaxFileCount', 0);
	}

	/**
	 * Sets the maximum number of files that can be selected at once.
	 * @param int $value the maximum number of files, 0 for no limit.
	 */
	public function setMaxFileCount($value)
	{
		$this->setViewState('MaxFileCount', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the minimum number of files. Defaults to 0, meaning no minimum.
	 */
	public function getMinFileCount()
	{
		return $this->getViewState('MinFileCount', 0);
	}

	/**
	 * Sets the minimum number of files that must be selected at once.
	 * The check applies when at least one file is selected.
	 * @param int $value the minimum number of files, 0 for no minimum.
	 */
	public function setMinFileCount($value)
	{
		$this->setViewState('MinFileCount', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return string comma separated list of allowed file name extensions. Defaults to ''.
	 */
	public function getAllowedFileExtensions()
	{
		return $this->getViewState('AllowedFileExtensions', '');
	}

	/**
	 * Sets the file name extensions allowed for the files, e.g. "jpg, png".
	 * Extensions are compared case-insensitively and a leading dot is ignored.
	 * @param string $value comma separated list of allowed extensions, '' to allow every extension.
	 */
	public function setAllowedFileExtensions($value)
	{
		$this->setViewState('AllowedFileExtensions', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string comma separated list of allowed MIME types. Defaults to ''.
	 */
	public function getAllowedFileTypes()
	{
		return $this->getViewState('AllowedFileTypes', '');
	}

	/**
	 * Sets the MIME types allowed for the files, e.g. "image/png, image/jpeg".
	 * A type ending in "/*" matches every subtype, e.g. "image/*".
	 * @param string $value comma separated list of allowed MIME types, '' to allow every type.
	 */
	public function setAllowedFileTypes($value)
	{
		$this->setViewState('AllowedFileTypes', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the sniffed MIME type must correspond to the file name extension. Defaults to false.
	 */
	public function getCheckExtensionMimeType()
	{
		return $this->getViewState('CheckExtensionMimeType', false);
	}

	/**
	 * Sets whether the MIME type sniffed from the file content must correspond
	 * to the file name extension, detecting files renamed to pass an extension
	 * restriction. The check runs server side only and requires the fileinfo
	 * extension. A file without an extension, an extension absent from the
	 * known extension map, or an unavailable sniffed type passes the check.
	 * @param bool $value true to cross-check the file content against the file name extension.
	 */
	public function setCheckExtensionMimeType($value)
	{
		$this->setViewState('CheckExtensionMimeType', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string[] the names of the files that failed the last validation.
	 */
	public function getInvalidFileNames()
	{
		return $this->_invalidFileNames;
	}

	/**
	 * Returns the error message with the "{files}" token replaced by the HTML
	 * encoded, comma separated names of the invalid files.
	 * @return string the error message.
	 */
	public function getErrorMessage()
	{
		$message = parent::getErrorMessage();
		if (strpos($message, '{files}') !== false) {
			$names = array_map([THttpUtility::class, 'htmlEncode'], $this->getInvalidFileNames());
			$message = str_replace('{files}', implode(', ', $names), $message);
		}
		return $message;
	}

	/**
	 * Returns the target {@see \Prado\Web\UI\WebControls\TFileUpload} of the validator.
	 * @throws TConfigurationException if the target control is not a TFileUpload.
	 * @return TFileUpload the file upload control to validate.
	 */
	protected function getFileUploadTarget()
	{
		$control = $this->getValidationTarget();
		if (!($control instanceof TFileUpload)) {
			throw new TConfigurationException('filevalidator_fileupload_required', $this::class);
		}
		return $control;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if every selected file satisfies the file count,
	 * file size and file type restrictions. The validation succeeds when no
	 * file is selected.
	 * @return bool whether the validation succeeds
	 */
	protected function evaluateIsValid()
	{
		$this->_invalidFileNames = [];
		$control = $this->getFileUploadTarget();
		$files = array_filter($control->getFiles(), fn ($file) => $file->getErrorCode() !== UPLOAD_ERR_NO_FILE);
		if (count($files) === 0) {
			return true;
		}
		$valid = true;
		if (($max = $this->getMaxFileCount()) > 0 && count($files) > $max) {
			$valid = false;
		}
		if (($min = $this->getMinFileCount()) > 0 && count($files) < $min) {
			$valid = false;
		}
		if (($total = $this->getTotalMaxFileSize()) > 0) {
			$totalSize = array_sum(array_map(fn ($file) => $file->getFileSize(), $files));
			if ($totalSize > $total) {
				$valid = false;
			}
		}
		foreach ($files as $file) {
			if (!$this->validateFile($file)) {
				$this->_invalidFileNames[] = $file->getFileName();
				$valid = false;
			}
		}
		return $valid;
	}

	/**
	 * Checks one uploaded file against the error code, file size and file type restrictions.
	 * @param TFileUploadItem $file the uploaded file to check.
	 * @return bool whether the file satisfies the restrictions.
	 */
	protected function validateFile($file)
	{
		if ($file->getErrorCode() !== UPLOAD_ERR_OK) {
			return false;
		}
		if (($max = $this->getEffectiveMaxFileSize()) > 0 && $file->getFileSize() > $max) {
			return false;
		}
		if (($min = $this->getMinFileSize()) > 0 && $file->getFileSize() < $min) {
			return false;
		}
		if ($this->getCheckExtensionMimeType() && !$this->validateExtensionMimeType($file)) {
			return false;
		}
		return $this->validateFileType($file);
	}

	/**
	 * Checks that the MIME type sniffed from the content of an uploaded file
	 * corresponds to its file name extension. The check passes when the file
	 * has no extension, the extension is absent from the extension map, or no
	 * sniffed type is available.
	 * @param TFileUploadItem $file the uploaded file to check.
	 * @return bool whether the file content corresponds to the file name extension.
	 */
	protected function validateExtensionMimeType($file)
	{
		$extension = $this->getFileExtension($file);
		if ($extension === '' || !isset(static::$extensionMimeTypes[$extension])) {
			return true;
		}
		if (($mimeType = $this->getSniffedMimeType($file)) === null) {
			return true;
		}
		return in_array($mimeType, static::$extensionMimeTypes[$extension], true);
	}

	/**
	 * Checks the extension and MIME type of one uploaded file.
	 * With explicit {@see setAllowedFileExtensions AllowedFileExtensions} or
	 * {@see setAllowedFileTypes AllowedFileTypes}, the file must match every
	 * non-empty list. With restrictions derived from the target's Accept value,
	 * the file must match any of the tokens.
	 * @param TFileUploadItem $file the uploaded file to check.
	 * @return bool whether the file satisfies the type restrictions.
	 */
	protected function validateFileType($file)
	{
		$extensions = $this->getEffectiveFileExtensions();
		$types = $this->getEffectiveFileTypes();
		if (count($extensions) === 0 && count($types) === 0) {
			return true;
		}
		$extensionValid = count($extensions) > 0 && in_array($this->getFileExtension($file), $extensions, true);
		$typeValid = count($types) > 0 && $this->matchesAnyMimeType($this->getFileMimeType($file), $types);
		if ($this->getMatchAnyType()) {
			return $extensionValid || $typeValid;
		}
		return (count($extensions) === 0 || $extensionValid) && (count($types) === 0 || $typeValid);
	}

	/**
	 * Returns the lower case file name extension of an uploaded file.
	 * @param TFileUploadItem $file the uploaded file.
	 * @return string the extension without the dot, '' if the file name has no extension.
	 */
	protected function getFileExtension($file)
	{
		return strtolower(pathinfo($file->getFileName(), PATHINFO_EXTENSION));
	}

	/**
	 * Returns the MIME type of an uploaded file, sniffed from the file content,
	 * falling back to the browser supplied
	 * {@see TFileUploadItem::getFileType FileType}.
	 * @param TFileUploadItem $file the uploaded file.
	 * @return string the lower case MIME type of the file.
	 */
	protected function getFileMimeType($file)
	{
		if (($mimeType = $this->getSniffedMimeType($file)) !== null) {
			return $mimeType;
		}
		return strtolower($file->getFileType());
	}

	/**
	 * Returns the MIME type sniffed from the content of an uploaded file with
	 * the fileinfo extension.
	 * @param TFileUploadItem $file the uploaded file.
	 * @return ?string the lower case sniffed MIME type, null when the fileinfo
	 * extension or the local file is unavailable.
	 */
	protected function getSniffedMimeType($file)
	{
		$localName = $file->getLocalName();
		if (function_exists('finfo_open') && $localName !== '' && is_file($localName)) {
			if (($mimeType = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $localName)) !== false) {
				return strtolower($mimeType);
			}
		}
		return null;
	}

	/**
	 * Checks a MIME type against a list of MIME type patterns.
	 * @param string $mimeType the lower case MIME type to check.
	 * @param string[] $patterns the lower case MIME type patterns; "image/*" matches every image subtype.
	 * @return bool whether the MIME type matches any of the patterns.
	 */
	protected function matchesAnyMimeType($mimeType, $patterns)
	{
		foreach ($patterns as $pattern) {
			if ($pattern === '*' || $pattern === '*/*') {
				return true;
			}
			if (substr($pattern, -2) === '/*') {
				if (strncmp($mimeType, $pattern, strlen($pattern) - 1) === 0) {
					return true;
				}
			} elseif ($mimeType === $pattern) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the maximum file size to enforce, falling back to the
	 * {@see TFileUpload::getMaxFileSize MaxFileSize} of the target control
	 * when {@see setMaxFileSize MaxFileSize} is 0.
	 * @return int the maximum file size in bytes.
	 */
	protected function getEffectiveMaxFileSize()
	{
		if (($max = $this->getMaxFileSize()) > 0) {
			return $max;
		}
		return $this->getFileUploadTarget()->getMaxFileSize();
	}

	/**
	 * Returns the file name extensions to enforce, derived from the target's
	 * Accept value when {@see setAllowedFileExtensions AllowedFileExtensions}
	 * and {@see setAllowedFileTypes AllowedFileTypes} are empty.
	 * @return string[] lower case extensions without dots.
	 */
	protected function getEffectiveFileExtensions()
	{
		if (!$this->getMatchAnyType()) {
			return array_map(fn ($extension) => ltrim($extension, '.'), $this->splitList($this->getAllowedFileExtensions()));
		}
		$extensions = [];
		foreach ($this->splitList($this->getTargetAccept()) as $token) {
			if (strncmp($token, '.', 1) === 0) {
				$extensions[] = ltrim($token, '.');
			}
		}
		return $extensions;
	}

	/**
	 * Returns the MIME type patterns to enforce, derived from the target's
	 * Accept value when {@see setAllowedFileExtensions AllowedFileExtensions}
	 * and {@see setAllowedFileTypes AllowedFileTypes} are empty.
	 * @return string[] lower case MIME type patterns.
	 */
	protected function getEffectiveFileTypes()
	{
		if (!$this->getMatchAnyType()) {
			return $this->splitList($this->getAllowedFileTypes());
		}
		$types = [];
		foreach ($this->splitList($this->getTargetAccept()) as $token) {
			if (strpos($token, '/') !== false) {
				$types[] = $token;
			}
		}
		return $types;
	}

	/**
	 * Returns whether a file matching any of the extension or MIME type lists
	 * is valid. This is true when the type restrictions derive from the
	 * target's Accept value, following the HTML file picker semantics.
	 * @return bool whether any list match validates the file type.
	 */
	protected function getMatchAnyType()
	{
		return $this->getAllowedFileExtensions() === '' && $this->getAllowedFileTypes() === '';
	}

	/**
	 * Returns the Accept value of the target control, from the
	 * {@see TFileUpload::getAccept Accept} property or the "accept" attribute.
	 * @return string the accept value of the target control, '' when not set.
	 */
	protected function getTargetAccept()
	{
		$control = $this->getFileUploadTarget();
		if (($accept = $control->getAccept()) !== '') {
			return $accept;
		}
		return (string) $control->getAttribute('accept');
	}

	/**
	 * Splits a comma or space separated list into lower case items, ignoring
	 * empty items.
	 * @param string $value comma or space separated list.
	 * @return string[] lower case list items.
	 */
	protected function splitList($value)
	{
		return preg_split('/[\s,]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
	}

	/**
	 * Returns an array of javascript validator options.
	 * The ErrorMessage option keeps the raw "{files}" token so the client-side
	 * validator can substitute the invalid file names.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['ErrorMessage'] = parent::getErrorMessage();
		$options['MaxFileSize'] = $this->getEffectiveMaxFileSize();
		$options['MinFileSize'] = $this->getMinFileSize();
		$options['TotalMaxFileSize'] = $this->getTotalMaxFileSize();
		$options['MaxFileCount'] = $this->getMaxFileCount();
		$options['MinFileCount'] = $this->getMinFileCount();
		$options['AllowedFileExtensions'] = $this->getEffectiveFileExtensions();
		$options['AllowedFileTypes'] = $this->getEffectiveFileTypes();
		$options['MatchAnyType'] = $this->getMatchAnyType();
		return $options;
	}
}
