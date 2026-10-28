<?php

/**
 * TMediaSource class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TMediaSource class
 * TMediaSource represents the HTML `<source>` element, one alternative resource of
 * a {@see TVideo}, a {@see TAudio}, or a {@see TPicture}. The browser uses the
 * first source whose {@see setType() Type} and {@see setMedia() Media} it
 * supports.
 * - In a TVideo or TAudio, {@see setSrc() Src} is the media URL.
 * - In a TPicture, {@see setSrcset() Srcset} is the image candidates, with
 *   {@see setSizes() Sizes}, {@see setWidth() Width}, and {@see setHeight() Height}.
 * ```html
 * <com:TVideo>
 *     <com:TMediaSource Src="intro.webm" Type="video/webm" />
 *     <com:TMediaSource Src="intro.mp4" Type="video/mp4" />
 * </com:TVideo>
 * ```
 * `<source>` is a void element; child controls do not render.
 * Accessibility: a source has no role or content. Its media element or image
 * carries the controls, captions, and text alternative.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMediaSource extends TWebControl
{
	/**
	 * @return string the tag name, `source`.
	 */
	protected function getTagName()
	{
		return 'source';
	}

	/**
	 * @return string the URL of the media resource. Defaults to ''.
	 */
	public function getSrc()
	{
		return $this->getViewState('Src', '');
	}

	/**
	 * @param string $value the URL of the media resource.
	 */
	public function setSrc($value)
	{
		$this->setViewState('Src', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the MIME type of the resource, with optional `codecs`. Defaults to ''.
	 */
	public function getType()
	{
		return $this->getViewState('Type', '');
	}

	/**
	 * @param string $value the MIME type of the resource, with optional `codecs`.
	 */
	public function setType($value)
	{
		$this->setViewState('Type', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the media query the source applies to. Defaults to ''.
	 */
	public function getMedia()
	{
		return $this->getViewState('Media', '');
	}

	/**
	 * @param string $value the media query the source applies to, such as `(min-width: 600px)`.
	 */
	public function setMedia($value)
	{
		$this->setViewState('Media', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the image candidates of a TPicture source. Defaults to ''.
	 */
	public function getSrcset()
	{
		return $this->getViewState('Srcset', '');
	}

	/**
	 * @param string $value the image candidates of a TPicture source, such as `a.webp 1x, a@2x.webp 2x`.
	 */
	public function setSrcset($value)
	{
		$this->setViewState('Srcset', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the display sizes of the Srcset candidates. Defaults to ''.
	 */
	public function getSizes()
	{
		return $this->getViewState('Sizes', '');
	}

	/**
	 * @param string $value the display sizes of the Srcset candidates, such as `(min-width: 600px) 50vw, 100vw`.
	 */
	public function setSizes($value)
	{
		$this->setViewState('Sizes', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int|string the intrinsic width of a TPicture source image in pixels, or -1
	 *   when not set. Defaults to -1.
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int|string $value the intrinsic width of a TPicture source image in pixels;
	 *   a negative value unsets it.
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int|string the intrinsic height of a TPicture source image in pixels, or -1
	 *   when not set. Defaults to -1.
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int|string $value the intrinsic height of a TPicture source image in pixels;
	 *   a negative value unsets it.
	 */
	public function setHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Height', $value, -1);
	}

	/**
	 * Adds the source attributes that are set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		foreach (['src' => $this->getSrc(), 'type' => $this->getType(), 'media' => $this->getMedia(),
			'srcset' => $this->getSrcset(), 'sizes' => $this->getSizes()] as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
		}
		if (($width = $this->getWidth()) !== -1) {
			$writer->addAttribute('width', (string) $width);
		}
		if (($height = $this->getHeight()) !== -1) {
			$writer->addAttribute('height', (string) $height);
		}
	}

	/**
	 * Renders nothing; `<source>` is a void element.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @phpstan-impure
	 */
	public function renderContents($writer)
	{
	}
}
