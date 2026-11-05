<?php

/**
 * TVideo class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TVideo class
 * TVideo represents the HTML `<video>` element, video content.
 * {@see setWidth() Width} and {@see setHeight() Height} are the display size in
 * CSS pixels, rendered as the `width` and `height` attributes, which also reserve
 * the space before the video loads. {@see setPoster() Poster} is the image shown
 * before playback, and {@see setPlaysInline() PlaysInline} plays in the page on a
 * phone instead of full screen.
 * ```html
 * <com:TVideo Width="640" Height="360" Poster="intro.jpg">
 *     <com:TMediaSource Src="intro.webm" Type="video/webm" />
 *     <com:TMediaSource Src="intro.mp4" Type="video/mp4" />
 *     <com:TTextTrack Src="intro.en.vtt" Kind="Captions" SrcLang="en" Label="English" IsDefault="true" />
 *     <a href="intro.mp4">Download the video</a>
 * </com:TVideo>
 * ```
 * {@see TBaseMedia} documents the playback properties and accessibility.
 * Recorded video with sound has a `Captions` {@see TTextTrack}. The poster is
 * decorative; the video's subject is in the surrounding text or a caption.
 * The default tag is `video`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TVideo extends TBaseMedia
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'video';
	}

	/**
	 * @return int|string the display width in CSS pixels, or -1 when not set. Defaults to -1.
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int|string $value the display width in CSS pixels; a negative value unsets it.
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int|string the display height in CSS pixels, or -1 when not set. Defaults to -1.
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int|string $value the display height in CSS pixels; a negative value unsets it.
	 */
	public function setHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Height', $value, -1);
	}

	/**
	 * @return string the URL of the image shown before playback. Defaults to ''.
	 */
	public function getPoster()
	{
		return $this->getViewState('Poster', '');
	}

	/**
	 * @param string $value the URL of the image shown before playback.
	 */
	public function setPoster($value)
	{
		$this->setViewState('Poster', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether a phone plays the video in the page instead of full screen.
	 *   Defaults to false.
	 */
	public function getPlaysInline()
	{
		return $this->getViewState('PlaysInline', false);
	}

	/**
	 * @param bool $value whether a phone plays the video in the page instead of full screen.
	 */
	public function setPlaysInline($value)
	{
		$this->setViewState('PlaysInline', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Adds the video attributes that are set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($width = $this->getWidth()) !== -1) {
			$writer->addAttribute('width', (string) $width);
		}
		if (($height = $this->getHeight()) !== -1) {
			$writer->addAttribute('height', (string) $height);
		}
		if (($poster = $this->getPoster()) !== '') {
			$writer->addAttribute('poster', $poster);
		}
		if ($this->getPlaysInline()) {
			$writer->addAttribute('playsinline', 'playsinline');
		}
	}
}
