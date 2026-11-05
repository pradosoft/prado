<?php

/**
 * TImage class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TImage class
 *
 * TImage displays an image on a page. The image is specified via the
 * {@see setImageUrl ImageUrl} property which takes a relative or absolute
 * URL to the image file. The style of the image displayed can be set using
 * the {@see setStyle Style} property. To set an alternative text
 * use the {@see setAlternateText AlternateText} property.
 *
 * Loading properties render the `<img>` attributes that are set:
 * | Property | Attribute |
 * |---|---|
 * | {@see setSrcset Srcset}, {@see setSizes Sizes} | `srcset`, `sizes`: image candidates for screen densities and layouts |
 * | {@see setIntrinsicWidth IntrinsicWidth}, {@see setIntrinsicHeight IntrinsicHeight} | `width`, `height`: the image size in pixels, which reserves its layout space |
 * | {@see setLoading Loading} | `loading` ({@see TLoadingMode}) |
 * | {@see setDecoding Decoding} | `decoding` ({@see TImageDecoding}) |
 * | {@see setFetchPriority FetchPriority} | `fetchpriority` ({@see TFetchPriority}) |
 * | {@see setCrossOrigin CrossOrigin} | `crossorigin`: `anonymous` or `use-credentials` |
 * | {@see setReferrerPolicy ReferrerPolicy} | `referrerpolicy` ({@see TReferrerPolicy}) |
 *
 * IntrinsicWidth and IntrinsicHeight are the image's own size; the inherited
 * Width and Height set the CSS display size. A lazy image keeps its layout space
 * only with an intrinsic size. An `<input type="image">`, such as
 * {@see TImageButton}, renders only the intrinsic size of these.
 * ```html
 * <com:TImage ImageUrl="photo-800.jpg" Srcset="photo-800.jpg 800w, photo-1600.jpg 1600w"
 *     Sizes="(min-width: 60em) 50vw, 100vw" IntrinsicWidth="800" IntrinsicHeight="600"
 *     Loading="Lazy" AlternateText="The harbor at dawn" />
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
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
		if (($width = $this->getIntrinsicWidth()) !== -1) {
			$writer->addAttribute('width', (string) $width);
		}
		if (($height = $this->getIntrinsicHeight()) !== -1) {
			$writer->addAttribute('height', (string) $height);
		}
		if ($this->getTagName() === 'img') {
			$this->addLoadingAttributesToRender($writer);
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Adds the `<img>` loading attributes that are set: srcset, sizes, loading,
	 * decoding, fetchpriority, crossorigin, and referrerpolicy.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @since 4.4.0
	 */
	protected function addLoadingAttributesToRender($writer)
	{
		if (($srcset = $this->getSrcset()) !== '') {
			$writer->addAttribute('srcset', $srcset);
		}
		if (($sizes = $this->getSizes()) !== '') {
			$writer->addAttribute('sizes', $sizes);
		}
		if (($loading = $this->getLoading()) !== TLoadingMode::NotSet) {
			$writer->addAttribute('loading', strtolower($loading));
		}
		if (($decoding = $this->getDecoding()) !== TImageDecoding::NotSet) {
			$writer->addAttribute('decoding', strtolower($decoding));
		}
		if (($priority = $this->getFetchPriority()) !== TFetchPriority::NotSet) {
			$writer->addAttribute('fetchpriority', strtolower($priority));
		}
		if (($crossOrigin = $this->getCrossOrigin()) !== '') {
			$writer->addAttribute('crossorigin', $crossOrigin);
		}
		if (($policy = $this->getReferrerPolicy()) !== TReferrerPolicy::NotSet) {
			$writer->addAttribute('referrerpolicy', TReferrerPolicy::toAttributeValue($policy));
		}
	}

	/**
	 * Renders the body content of the image.
	 * Nothing to be rendered within image tags.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 * @phpstan-impure
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
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see getImageUrl()}.
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
	 * This method is required by {@see \Prado\IDataRenderer}.
	 * It is the same as {@see setImageUrl()}.
	 * @param string $value the URL of the image file.
	 * @see setImageUrl
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setImageUrl($value);
	}

	/**
	 * @return string the image candidates, or '' if not set
	 * @since 4.4.0
	 */
	public function getSrcset()
	{
		return $this->getViewState('Srcset', '');
	}

	/**
	 * Sets the image candidates (`srcset`), from which the browser picks one for the
	 * screen density or the display width of {@see setSizes Sizes}.
	 * @param string $value the candidates, such as `a.jpg 1x, a@2x.jpg 2x` or `a-800.jpg 800w, a-1600.jpg 1600w`
	 * @since 4.4.0
	 */
	public function setSrcset($value)
	{
		$this->setViewState('Srcset', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the display sizes of the Srcset width candidates, or '' if not set
	 * @since 4.4.0
	 */
	public function getSizes()
	{
		return $this->getViewState('Sizes', '');
	}

	/**
	 * Sets the display sizes of the {@see setSrcset Srcset} width candidates (`sizes`).
	 * @param string $value the sizes, such as `(min-width: 60em) 50vw, 100vw`
	 * @since 4.4.0
	 */
	public function setSizes($value)
	{
		$this->setViewState('Sizes', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return int the intrinsic width of the image in pixels, or -1 when not set. Defaults to -1.
	 * @since 4.4.0
	 */
	public function getIntrinsicWidth()
	{
		return $this->getViewState('IntrinsicWidth', -1);
	}

	/**
	 * Sets the intrinsic width of the image in pixels (`width`). The browser reserves
	 * the layout space of the image before it loads.
	 * @param int|string $value the width; a negative value unsets it
	 * @since 4.4.0
	 */
	public function setIntrinsicWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('IntrinsicWidth', $value, -1);
	}

	/**
	 * @return int the intrinsic height of the image in pixels, or -1 when not set. Defaults to -1.
	 * @since 4.4.0
	 */
	public function getIntrinsicHeight()
	{
		return $this->getViewState('IntrinsicHeight', -1);
	}

	/**
	 * Sets the intrinsic height of the image in pixels (`height`).
	 * @param int|string $value the height; a negative value unsets it
	 * @since 4.4.0
	 */
	public function setIntrinsicHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('IntrinsicHeight', $value, -1);
	}

	/**
	 * @return string when the image loads, a {@see TLoadingMode}. Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getLoading()
	{
		return $this->getViewState('Loading', TLoadingMode::NotSet);
	}

	/**
	 * Sets when the image loads (`loading`); Lazy defers an off-screen image.
	 * @param string $value a {@see TLoadingMode}
	 * @since 4.4.0
	 */
	public function setLoading($value)
	{
		$this->setViewState('Loading', TPropertyValue::ensureEnum($value, TLoadingMode::class), TLoadingMode::NotSet);
	}

	/**
	 * @return string how the image decodes, a {@see TImageDecoding}. Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getDecoding()
	{
		return $this->getViewState('Decoding', TImageDecoding::NotSet);
	}

	/**
	 * Sets how the image decodes (`decoding`).
	 * @param string $value a {@see TImageDecoding}
	 * @since 4.4.0
	 */
	public function setDecoding($value)
	{
		$this->setViewState('Decoding', TPropertyValue::ensureEnum($value, TImageDecoding::class), TImageDecoding::NotSet);
	}

	/**
	 * @return string the request priority, a {@see TFetchPriority}. Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getFetchPriority()
	{
		return $this->getViewState('FetchPriority', TFetchPriority::NotSet);
	}

	/**
	 * Sets the priority of the image request relative to other images (`fetchpriority`).
	 * @param string $value a {@see TFetchPriority}
	 * @since 4.4.0
	 */
	public function setFetchPriority($value)
	{
		$this->setViewState('FetchPriority', TPropertyValue::ensureEnum($value, TFetchPriority::class), TFetchPriority::NotSet);
	}

	/**
	 * @return string the CORS mode of the image request: '', 'anonymous', or
	 *   'use-credentials'. Defaults to ''.
	 * @since 4.4.0
	 */
	public function getCrossOrigin()
	{
		return $this->getViewState('CrossOrigin', '');
	}

	/**
	 * Sets the CORS mode of the image request (`crossorigin`). A cross-origin image
	 * drawn on a {@see TCanvas} needs it to keep the canvas readable.
	 * @param string $value '', 'anonymous', or 'use-credentials'
	 * @throws TInvalidDataValueException if the value is not one of those.
	 * @since 4.4.0
	 */
	public function setCrossOrigin($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value !== '' && $value !== 'anonymous' && $value !== 'use-credentials') {
			throw new TInvalidDataValueException('media_crossorigin_invalid', $this::class, $value);
		}
		$this->setViewState('CrossOrigin', $value, '');
	}

	/**
	 * @return string the referrer policy of the image request, a {@see TReferrerPolicy}.
	 *   Defaults to NotSet.
	 * @since 4.4.0
	 */
	public function getReferrerPolicy()
	{
		return $this->getViewState('ReferrerPolicy', TReferrerPolicy::NotSet);
	}

	/**
	 * Sets the referrer policy of the image request (`referrerpolicy`).
	 * @param string $value a {@see TReferrerPolicy}
	 * @since 4.4.0
	 */
	public function setReferrerPolicy($value)
	{
		$this->setViewState('ReferrerPolicy', TPropertyValue::ensureEnum($value, TReferrerPolicy::class), TReferrerPolicy::NotSet);
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
