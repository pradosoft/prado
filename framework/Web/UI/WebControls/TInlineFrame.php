<?php

/**
 * TInlineFrame class file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TInlineFrame class
 *
 * TInlineFrame displays an inline frame (iframe) on a Web page.
 * The location of the frame content is specified by {@see setFrameUrl FrameUrl},
 * or the content itself by {@see setSrcDoc SrcDoc}. {@see setWidth Width} and
 * {@see setHeight Height} render the `width` and `height` attributes.
 *
 * | Property | Attribute |
 * |---|---|
 * | {@see setSandbox Sandbox} | `sandbox`: the restrictions on the frame content and the tokens that lift them |
 * | {@see setAllow Allow} | `allow`: the permissions policy of the frame, such as `fullscreen; camera 'none'` |
 * | {@see setLoading Loading} | `loading` ({@see TLoadingMode}): Lazy defers an off-screen frame |
 * | {@see setReferrerPolicy ReferrerPolicy} | `referrerpolicy` ({@see TReferrerPolicy}) |
 * | {@see setSrcDoc SrcDoc} | `srcdoc`: an HTML document that the frame shows in place of FrameUrl |
 *
 * Accessibility: the {@see setToolTip ToolTip} renders the `title` that names the
 * frame to assistive technologies; each frame takes a title that describes its content.
 *
 * Align, ShowBorder, ScrollBars, MarginWidth, MarginHeight, and DescriptionUrl
 * render attributes that HTML made obsolete; CSS replaces them.
 *
 * Original Prado v2 IFrame Author Information
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
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
		$this->setViewState('Align', TPropertyValue::ensureEnum($value, TInlineFrameAlign::class), TInlineFrameAlign::NotSet);
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
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see getFrameUrl()}.
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
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see setFrameUrl()}.
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
		$this->setViewState('ScrollBars', TPropertyValue::ensureEnum($value, TInlineFrameScrollBars::class), TInlineFrameScrollBars::Auto);
	}

	/**
	 * @return int|string the width of the control
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int|string $value the width of the control
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int|string the height of the control
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int|string $value the height of the control
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
	 * @param int|string $value the amount of space, in pixels, that should be left between
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
	 * @param int|string $value the amount of space, in pixels, that should be left between
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
	 * @return ?string the sandbox tokens of the frame: null when the frame is not
	 *   sandboxed, '' for every restriction. Defaults to null.
	 * @since 4.4.0
	 */
	public function getSandbox()
	{
		return $this->getViewState('Sandbox', null);
	}

	/**
	 * Sets the sandbox of the frame (`sandbox`). A sandboxed frame runs no script,
	 * submits no form, and has an opaque origin; each token lifts one restriction.
	 * - null or false → no `sandbox` attribute.
	 * - '' or true → `sandbox=""`: every restriction.
	 * - tokens → the restrictions those tokens leave, such as `allow-scripts allow-forms`.
	 *
	 * A frame of the same origin with `allow-scripts` and `allow-same-origin` can
	 * remove its own sandbox.
	 * @param null|bool|string $value the sandbox state or space-separated tokens
	 * @since 4.4.0
	 */
	public function setSandbox($value)
	{
		if (is_string($value)) {
			$value = trim(preg_replace('/\s+/', ' ', $value));
			$lower = strtolower($value);
			if ($lower === 'true' || $lower === 'false') {
				$value = $lower === 'true';
			}
		}
		if (is_bool($value) || $value === null) {
			$value = $value ? '' : null;
		}
		$this->setViewState('Sandbox', $value, null);
	}

	/**
	 * @return string the permissions policy of the frame, or '' if not set
	 * @since 4.4.0
	 */
	public function getAllow()
	{
		return $this->getViewState('Allow', '');
	}

	/**
	 * Sets the permissions policy of the frame (`allow`), the browser features its
	 * content may use, such as `fullscreen; geolocation 'self'`.
	 * @param string $value the policy directives
	 * @since 4.4.0
	 */
	public function setAllow($value)
	{
		$this->setViewState('Allow', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string when the frame loads, a {@see TLoadingMode}. Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getLoading()
	{
		return $this->getViewState('Loading', TLoadingMode::NotSet);
	}

	/**
	 * Sets when the frame loads (`loading`); Lazy defers an off-screen frame.
	 * @param string $value a {@see TLoadingMode}
	 * @since 4.4.0
	 */
	public function setLoading($value)
	{
		$this->setViewState('Loading', TPropertyValue::ensureEnum($value, TLoadingMode::class), TLoadingMode::NotSet);
	}

	/**
	 * @return string the referrer policy of the frame request, a {@see TReferrerPolicy}.
	 *   Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getReferrerPolicy()
	{
		return $this->getViewState('ReferrerPolicy', TReferrerPolicy::NotSet);
	}

	/**
	 * Sets the referrer policy of the frame request (`referrerpolicy`).
	 * @param string $value a {@see TReferrerPolicy}
	 * @since 4.4.0
	 */
	public function setReferrerPolicy($value)
	{
		$this->setViewState('ReferrerPolicy', TPropertyValue::ensureEnum($value, TReferrerPolicy::class), TReferrerPolicy::NotSet);
	}

	/**
	 * @return string the HTML document of the frame, or '' if not set
	 * @since 4.4.0
	 */
	public function getSrcDoc()
	{
		return $this->getViewState('SrcDoc', '');
	}

	/**
	 * Sets the HTML document that the frame shows (`srcdoc`); it takes precedence
	 * over {@see setFrameUrl FrameUrl}. The value renders HTML-encoded in the
	 * attribute, including `&`, and the browser decodes it into the document
	 * unchanged. Untrusted content belongs in a {@see setSandbox Sandbox} frame.
	 * @param string $value the HTML document
	 * @since 4.4.0
	 */
	public function setSrcDoc($value)
	{
		$this->setViewState('SrcDoc', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getID() !== '') {
			$writer->addAttribute('name', $this->getUniqueID());
		}

		if (($src = $this->getFrameUrl()) !== '') {
			$writer->addAttribute('src', $src);
		}

		if (($srcDoc = $this->getSrcDoc()) !== '') {
			// THtmlWriter keeps '&'; encoding it keeps the document's own character references
			$writer->addAttribute('srcdoc', str_replace('&', '&amp;', $srcDoc));
		}

		if (($sandbox = $this->getSandbox()) !== null) {
			$writer->addAttribute('sandbox', $sandbox);
		}

		if (($allow = $this->getAllow()) !== '') {
			$writer->addAttribute('allow', $allow);
		}

		if (($loading = $this->getLoading()) !== TLoadingMode::NotSet) {
			$writer->addAttribute('loading', strtolower($loading));
		}

		if (($policy = $this->getReferrerPolicy()) !== TReferrerPolicy::NotSet) {
			$writer->addAttribute('referrerpolicy', TReferrerPolicy::toAttributeValue($policy));
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
