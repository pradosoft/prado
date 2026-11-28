<?php

/**
 * TEmailAttachment class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailAttachment class.
 *
 * TEmailAttachment attaches a file to its email from the template.  It renders nothing.
 * <code>
 * <com:Prado\Email\UI\WebControls\TEmailAttachment File="report.pdf" Name="Report.pdf" />
 * </code>
 * A relative {@see setFile File} is relative to the email template.  {@see setName Name}
 * defaults to the file name, and the MIME type is detected when {@see setMime Mime} is empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailAttachment extends \Prado\Web\UI\TControl
{
	use TEmailControlTrait;

	/**
	 * Adds the file to the attachments of the email.
	 * @param mixed $param the event parameter.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (($file = $this->getFile()) === '') {
			return;
		}
		$path = $this->resolveEmailFile($file);
		$name = $this->getName() ?: basename($path);
		$this->getEmail()->attach($path, $name, $this->getMime() ?: null);
	}

	/**
	 * Renders nothing.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
	}

	/**
	 * @return string the file path or namespace. Defaults to empty.
	 */
	public function getFile()
	{
		return $this->getViewState('File', '');
	}

	/**
	 * @param string $value the file: a path, a path relative to the email template, or a namespace with extension.
	 */
	public function setFile($value)
	{
		$this->setViewState('File', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the attachment name. Defaults to the file name.
	 */
	public function getName()
	{
		return $this->getViewState('Name', '');
	}

	/**
	 * @param string $value the attachment name.
	 */
	public function setName($value)
	{
		$this->setViewState('Name', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the MIME type. Defaults to empty, which detects it from the file.
	 */
	public function getMime()
	{
		return $this->getViewState('Mime', '');
	}

	/**
	 * @param string $value the MIME type.
	 */
	public function setMime($value)
	{
		$this->setViewState('Mime', TPropertyValue::ensureString($value), '');
	}
}
