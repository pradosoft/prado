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
use Prado\Web\UI\TControl;

/**
 * TEmailTemplate is the template of a {@see TEmailPage}.  Files published by the
 * template, such as `<%~ ./logo.png %>`, follow the page's {@see TEmailPublishStyle}:
 * an absolute URL, an inline attachment, or a data URI.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEmailTemplate extends TTemplate
{
	/** @var ?TEmailPage the email page being instantiated. */
	private $_emailPage;

	/**
	 * Publishes a file by the publish style of the email page; other template controls publish normally.
	 * @param string $fullPath absolute path of the file or directory to be published.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @return string URL to the published file or directory.
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		if ($this->_emailPage !== null) {
			return $this->_emailPage->publishEmailFile($fullPath, $checkTimestamp);
		}
		return parent::publishFilePath($fullPath, $checkTimestamp);
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
		$this->_emailPage = $tplControl instanceof TEmailPage ? $tplControl : null;
		try {
			parent::instantiateIn($tplControl, $parentControl);
		} finally {
			$this->_emailPage = null;
		}
	}
}
