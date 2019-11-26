<?php
/**
 * TInlineFrame class file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TInlineFrame class
 *
 * TInlineFrame displays an inline frame (iframe) on a Web page.
 * The location of the frame content is specified by {@link setFrameUrl FrameUrl}.
 * The frame's alignment is specified by {@link setAlign Align}.
 * The {@link setMarginWidth MarginWidth} and {@link setMarginHeight MarginHeight}
 * properties define the number of pixels to use as the left/right margins and
 * top/bottom margins, respectively, within the inline frame.
 * The {@link setScrollBars ScrollBars} property specifies whether scrollbars are
 * provided for the inline frame. And {@link setDescriptionUrl DescriptionUrl}
 * gives the URI of a long description of the frame's contents.
 *
 * Original Prado v2 IFrame Author Information
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TInlineFrame extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\IDataRenderer
{
	/**
	 * @return string tag name of the iframe.
	 */
	protected function getTagName()
	{
		return 'iframe';
	}

	/**
	 * @return TInlineFrameAlign alignment of the iframe. Defaults to TInlineFrameAlign::NotSet.
	 * @deprecated obsolete since html5
	 */
	public function getAlign()
	{
		return $this->getViewState('Align', TInlineFrameAlign::NotSet);
	}

	/**
	 * @param TInlineFrameAlign $value alignment of the iframe.
	 * @deprecated obsolete since html5
	 */
	public function setAlign($value)
	{
		$this->setViewState('Align', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TInlineFrameAlign'), TInlineFrameAlign::NotSet);
	}

	/**
	 * @return string the URL to long description
	 * @deprecated obsolete since html5
	 */
	public function getDescriptionUrl()
	{
		return $this->getViewState('DescriptionUrl', '');
	}

	/**
	 * @param string $value the URL to the long description of the image.
	 * @deprecated obsolete since html5
	 */
	public function setDescriptionUrl($value)
	{
		$this->setViewState('DescriptionUrl', $value, '');
	}

	/**
	 * @return bool whether there should be a visual separator between the frames. Defaults to true.
	 * @deprecated obsolete since html5, use CSS border:none
	 */
	public function getShowBorder()
	{
		return $this->getViewState('ShowBorder', true);
	}

	/**
	 * @param bool $value whether there should be a visual separator between the frames.
	 * @deprecated obsolete since html5
	 */
	public function setShowBorder($value)
	{
		$this->setViewState('ShowBorder', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string URL that this iframe will load content from. Defaults to ''.
	 */
	public function getFrameUrl()
	{
		return $this->getViewState('FrameUrl', '');
	}

	/**
	 * @param string $value URL that this iframe will load content from.
	 */
	public function setFrameUrl($value)
	{
		$this->setViewState('FrameUrl', $value, '');
	}

	/**
	 * Returns the URL that this iframe will load content from
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getFrameUrl()}.
	 * @return string the URL that this iframe will load content from
	 * @see getFrameUrl
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getFrameUrl();
	}

	/**
	 * Sets the URL that this iframe will load content from.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setFrameUrl()}.
	 * @param string $value the URL that this iframe will load content from
	 * @see setFrameUrl
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setFrameUrl($value);
	}

	/**
	 * @return TInlineFrameScrollBars the visibility and position of scroll bars in an iframe. Defaults to TInlineFrameScrollBars::Auto.
	 * @deprecated obsolete since html5
	 */
	public function getScrollBars()
	{
		return $this->getViewState('ScrollBars', TInlineFrameScrollBars::Auto);
	}

	/**
	 * @param TInlineFrameScrollBars $value the visibility and position of scroll bars in an iframe.
	 * @deprecated obsolete since html5
	 */
	public function setScrollBars($value)
	{
		$this->setViewState('ScrollBars', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TInlineFrameScrollBars'), TInlineFrameScrollBars::Auto);
	}

	/**
	 * @return int the width of the control
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int $value the width of the control
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int the height of the control
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int $value the height of the control
	 */
	public function setHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Height', $value, -1);
	}

	/**
	 * @return int the amount of space, in pixels, that should be left between
	 * the frame's contents and the left and right margins. Defaults to -1, meaning not set.
	 * @deprecated obsolete since html5
	 */
	public function getMarginWidth()
	{
		return $this->getViewState('MarginWidth', -1);
	}

	/**
	 * @param int $value the amount of space, in pixels, that should be left between
	 * the frame's contents and the left and right margins.
	 * @deprecated obsolete since html5
	 */
	public function setMarginWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('MarginWidth', $value, -1);
	}

	/**
	 * @return int the amount of space, in pixels, that should be left between
	 * the frame's contents and the top and bottom margins. Defaults to -1, meaning not set.
	 * @deprecated obsolete since html5
	 */
	public function getMarginHeight()
	{
		return $this->getViewState('MarginHeight', -1);
	}

	/**
	 * @param int $value the amount of space, in pixels, that should be left between
	 * the frame's contents and the top and bottom margins.
	 * @deprecated obsolete since html5
	 */
	public function setMarginHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('MarginHeight', $value, -1);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getID() !== '') {
			$writer->addAttribute('name', $this->getUniqueID());
		}

		if (($src = $this->getFrameUrl()) !== '') {
			$writer->addAttribute('src', $src);
		}

		if (($align = strtolower($this->getAlign())) !== 'notset') {
			$writer->addAttribute('align', $align);
		}

		$scrollBars = $this->getScrollBars();
		if ($scrollBars === TInlineFrameScrollBars::None) {
			$writer->addAttribute('scrolling', 'no');
		} elseif ($scrollBars === TInlineFrameScrollBars::Both) {
			$writer->addAttribute('scrolling', 'yes');
		}

		if (!$this->getShowBorder()) {
			$writer->addAttribute('frameborder', '0');
		}

		if (($longdesc = $this->getDescriptionUrl()) !== '') {
			$writer->addAttribute('longdesc', $longdesc);
		}

		if (($width = $this->getWidth()) !== -1) {
			$writer->addAttribute('width', $width);
		}

		if (($height = $this->getHeight()) !== -1) {
			$writer->addAttribute('height', $height);
		}

		if (($marginheight = $this->getMarginHeight()) !== -1) {
			$writer->addAttribute('marginheight', $marginheight);
		}

		if (($marginwidth = $this->getMarginWidth()) !== -1) {
			$writer->addAttribute('marginwidth', $marginwidth);
		}

		parent::addAttributesToRender($writer);
	}
}
