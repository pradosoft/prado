<?php
/**
 * TImage class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TImage class
 *
 * TImage displays an image on a page. The image is specified via the
 * {@link setImageUrl ImageUrl} property which takes a relative or absolute
 * URL to the image file. The style of the image displayed can be set using
 * the {@link setStyle Style} property. To set an alternative text
 * use the {@link setAlternateText AlternateText} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TImage extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\IDataRenderer
{
	/**
	 * @return string tag name of image control
	 */
	protected function getTagName()
	{
		return 'img';
	}

	/**
	 * Adds attributes related to an HTML image element to renderer.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('src', $this->getImageUrl());
		$writer->addAttribute('alt', $this->getAlternateText());
		if (($desc = $this->getDescriptionUrl()) !== '') {
			$writer->addAttribute('longdesc', $desc);
		}
		if (($align = $this->getImageAlign()) !== '') {
			$writer->addAttribute('align', $align);
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the body content of the image.
	 * Nothing to be rendered within image tags.
	 * @param THtmlWriter $writer the writer for rendering
	 */
	public function renderContents($writer)
	{
	}

	/**
	 * @return string the alternative text displayed in the TImage component when the image is unavailable.
	 */
	public function getAlternateText()
	{
		return $this->getViewState('AlternateText', '');
	}

	/**
	 * Sets the alternative text to be displayed in the TImage when the image is unavailable.
	 * @param string $value the alternative text
	 */
	public function setAlternateText($value)
	{
		$this->setViewState('AlternateText', $value, '');
	}

	/**
	 * @return string the alignment of the image with respective to other elements on the page, defaults to empty.
	 * @deprecated use the Style property to get the float and/or vertical-align CSS properties instead
	 */
	public function getImageAlign()
	{
		return $this->getViewState('ImageAlign', '');
	}

	/**
	 * Sets the alignment of the image with respective to other elements on the page.
	 * Possible values include: absbottom, absmiddle, baseline, bottom, left,
	 * middle, right, texttop, and top. If an empty string is passed in,
	 * imagealign attribute will not be rendered.
	 * @param string $value the alignment of the image
	 * @deprecated use the Style property to set the float and/or vertical-align CSS properties instead
	 */
	public function setImageAlign($value)
	{
		$this->setViewState('ImageAlign', $value, '');
	}

	/**
	 * @return string the URL of the image file
	 */
	public function getImageUrl()
	{
		return $this->getViewState('ImageUrl', '');
	}

	/**
	 * @param string $value the URL of the image file
	 */
	public function setImageUrl($value)
	{
		$this->setViewState('ImageUrl', $value, '');
	}

	/**
	 * Returns the URL of the image file.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getImageUrl()}.
	 * @return string the URL of the image file.
	 * @see getImageUrl
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getImageUrl();
	}

	/**
	 * Sets the URL of the image.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setImageUrl()}.
	 * @param string $value the URL of the image file.
	 * @see setImageUrl
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setImageUrl($value);
	}

	/**
	 * @return string the URL to long description
	 * @deprecated use a WAI-ARIA alternative such as aria-describedby or aria-details instead.
	 */
	public function getDescriptionUrl()
	{
		return $this->getViewState('DescriptionUrl', '');
	}

	/**
	 * @param string $value the URL to the long description of the image.
	 * @deprecated use a WAI-ARIA alternative such as aria-describedby or aria-details instead.
	 */
	public function setDescriptionUrl($value)
	{
		$this->setViewState('DescriptionUrl', $value, '');
	}
}
