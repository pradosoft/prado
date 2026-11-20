<?php

/**
 * TEmailImage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TEmailImage class.
 *
 * TEmailImage displays an image file in an email.  {@see setImageFile ImageFile} is
 * published by the {@see \Prado\Email\UI\TEmailPublishStyle} of the body: an
 * absolute URL, an inline attachment ("cid:"), or a data URI.  ImageUrl is used when
 * ImageFile is empty.
 *
 * AlternateText is required, because many mail clients block images and show the
 * alternate text instead.  A purely decorative image sets {@see setDecorative Decorative}
 * to render an empty alt.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailImage extends \Prado\Web\UI\WebControls\TImage
{
	use TEmailControlTrait;

	/**
	 * Publishes the image file and checks the alternate text.
	 * @param mixed $param the event parameter.
	 * @throws TConfigurationException when the image is not decorative and has no alternate text.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (!$this->getDecorative() && trim($this->getAlternateText()) === '') {
			throw new TConfigurationException('emailimage_alternatetext_required', $this->getUniqueID());
		}
		if (($file = $this->getImageFile()) !== '') {
			$this->setImageUrl($this->getEmailBody()->publishEmailFile($this->resolveEmailFile($file)));
		}
	}

	/**
	 * @return string the alternate text; empty when decorative.
	 */
	public function getAlternateText()
	{
		return $this->getDecorative() ? '' : parent::getAlternateText();
	}

	/**
	 * @return string the image file path or namespace. Defaults to empty.
	 */
	public function getImageFile()
	{
		return $this->getViewState('ImageFile', '');
	}

	/**
	 * @param string $value the image file: a path, a path relative to the email template, or a namespace with extension.
	 */
	public function setImageFile($value)
	{
		$this->setViewState('ImageFile', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the image is decorative and renders an empty alt. Defaults to false.
	 */
	public function getDecorative()
	{
		return $this->getViewState('Decorative', false);
	}

	/**
	 * @param bool $value whether the image is decorative and renders an empty alt.
	 */
	public function setDecorative($value)
	{
		$this->setViewState('Decorative', TPropertyValue::ensureBoolean($value), false);
	}
}
