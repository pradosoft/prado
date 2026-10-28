<?php

/**
 * TTextTrack class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TTextTrack class
 * TTextTrack represents the HTML `<track>` element, a timed text track of a
 * {@see TVideo} or a {@see TAudio}: captions, subtitles, descriptions, chapters,
 * or metadata, in WebVTT. {@see setSrc() Src} is the URL of the `.vtt` file,
 * {@see setKind() Kind} its {@see TTextTrackKind}, {@see setSrcLang() SrcLang} its
 * language, and {@see setLabel() Label} its name in the browser's track menu.
 * {@see setIsDefault() IsDefault} shows the track when no user preference applies.
 * ```html
 * <com:TVideo Src="intro.mp4">
 *     <com:TTextTrack Src="intro.en.vtt" Kind="Captions" SrcLang="en" Label="English" IsDefault="true" />
 * </com:TVideo>
 * ```
 * `<track>` is a void element; child controls do not render. The `.vtt` file is
 * served from the page's origin, or from another origin with CORS and the media
 * element's `CrossOrigin`.
 * Accessibility: recorded video with sound has a `Captions` track (WCAG 1.2.2).
 * Each track has a Label, which names it in the track menu; a `Subtitles` track
 * requires SrcLang. Browsers rarely voice `Descriptions`, so audio description is
 * a described version of the video or a transcript (WCAG 1.2.5).
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTextTrack extends TWebControl
{
	/**
	 * @return string the tag name, `track`.
	 */
	protected function getTagName()
	{
		return 'track';
	}

	/**
	 * @return string the URL of the WebVTT file. Defaults to ''.
	 */
	public function getSrc()
	{
		return $this->getViewState('Src', '');
	}

	/**
	 * @param string $value the URL of the WebVTT file.
	 */
	public function setSrc($value)
	{
		$this->setViewState('Src', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the kind of track, a {@see TTextTrackKind}. Defaults to Subtitles.
	 */
	public function getKind()
	{
		return $this->getViewState('Kind', TTextTrackKind::Subtitles);
	}

	/**
	 * @param string $value the kind of track, a {@see TTextTrackKind}.
	 */
	public function setKind($value)
	{
		$this->setViewState('Kind', TPropertyValue::ensureEnum($value, TTextTrackKind::class), TTextTrackKind::Subtitles);
	}

	/**
	 * @return string the BCP 47 language of the track text. Defaults to ''.
	 */
	public function getSrcLang()
	{
		return $this->getViewState('SrcLang', '');
	}

	/**
	 * @param string $value the BCP 47 language of the track text, such as `en` or `pt-BR`.
	 */
	public function setSrcLang($value)
	{
		$this->setViewState('SrcLang', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the name of the track in the browser's track menu. Defaults to ''.
	 */
	public function getLabel()
	{
		return $this->getViewState('Label', '');
	}

	/**
	 * @param string $value the name of the track in the browser's track menu.
	 */
	public function setLabel($value)
	{
		$this->setViewState('Label', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the track shows when no user preference applies. Defaults to false.
	 */
	public function getIsDefault()
	{
		return $this->getViewState('IsDefault', false);
	}

	/**
	 * @param bool $value whether the track shows when no user preference applies.
	 */
	public function setIsDefault($value)
	{
		$this->setViewState('IsDefault', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Adds the `kind` attribute and the track attributes that are set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('kind', strtolower($this->getKind()));
		foreach (['src' => $this->getSrc(), 'srclang' => $this->getSrcLang(), 'label' => $this->getLabel()] as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
		}
		if ($this->getIsDefault()) {
			$writer->addAttribute('default', 'default');
		}
	}

	/**
	 * Renders nothing; `<track>` is a void element.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @phpstan-impure
	 */
	public function renderContents($writer)
	{
	}
}
