<?php

/**
 * TEmailTemplate class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\Email\UI\TEmailPublishStyle;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\TControl;

/**
 * TEmailTemplate implements TTemplate but .
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailTemplate extends TTemplate
{
	/** @var string[] paths of the files to attach to the email. */
	protected $_filePaths;

	/** @var string style of which to publish email files: URL, Attachment, or Embed */
	private $_publishStyle;

	/** @var string the absolute URL to the files published on the website */
	private $_absoluteUrl;

	/**
	 * Publishes a file or directory and returns its URL.
	 * @param string $fullPath absolute path of the file or directory to be published
	 * @param mixed $checkTimestamp
	 * @return string URL to the published file or directory
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		if ($this->_publishStyle === TEmailPublishStyle::Embed) {
			$file = file_get_contents($fullPath);
			$data = base64_encode($file);
			return 'data:' . mime_content_type($fullPath) . ';base64,' . $data;
		} elseif ($this->_publishStyle === TEmailPublishStyle::Attachment) {
			$name = basename($fullPath);
			$this->_filePaths[$name] = $fullPath;
			return "cid:" . $name;
		}
		if (!$this->_absoluteUrl) {
			throw new TConfigurationException('emailtemplate_absolute_url_required');
		}
		$url = parent::publishFilePath($fullPath, $checkTimestamp);
		if (!empty($url) && $url[0] === '.' && $url[1] !== '.') {
			$url = substr($url, 1);
		}
		return $this->_absoluteUrl . $url;
	}

	/**
	 * Adds the object instantiated on a template to the child control collection.
	 * This method overrides the parent implementation.
	 * Only framework developers and control developers should use this method.
	 * @param \Prado\TComponent|string $object text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 */
	public function addParsedObject($object)
	{
		//ERROR?  not getting the objects in the page?
		if ($object instanceof \Prado\Web\UI\ActiveControls\IActiveControl) {
			throw new TConfigurationException('emailtemplate_no_activecontrols');
		}
		parent::addParsedObject($object);
	}

	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param \Prado\Web\UI\TControl $tplControl the control who owns the template
	 * @param null|TControl $parentControl the control who will become the root parent of the controls on the template. If null, it uses the template control.
	 */
	public function instantiateIn($tplControl, $parentControl = null)
	{
		$isTEmail = $tplControl instanceof TEmailPage;
		if ($isTEmail) {
			$this->_publishStyle = $tplControl->getPublishStyle();
			$this->_absoluteUrl = $tplControl->getAbsoluteUrl();
		}
		parent::instantiateIn($tplControl, $parentControl);
		if ($isTEmail && $this->_filePaths) {
			foreach ($this->_filePaths as $name => $file) {
				$tplControl->addAttachment($name, $file);
			}
		}
	}
}
