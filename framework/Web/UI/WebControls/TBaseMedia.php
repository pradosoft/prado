<?php

/**
 * TBaseMedia class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TBaseMedia class
 * TBaseMedia is the base class of the HTML media elements, {@see TVideo} and
 * {@see TAudio}. {@see setSrc() Src} is the media URL, or the child
 * {@see TMediaSource} controls give alternative resources. The children render in
 * order: sources, then {@see TTextTrack} tracks, then the fallback content, which
 * shows in a browser that cannot play the media, such as a download link.
 * | Property | Attribute | Default |
 * |---|---|---|
 * | {@see setShowControls() ShowControls} | `controls` | true |
 * | {@see setAutoPlay() AutoPlay} | `autoplay` | false |
 * | {@see setLoop() Loop} | `loop` | false |
 * | {@see setMuted() Muted} | `muted` | false |
 * | {@see setPreload() Preload} | `preload` | {@see TMediaPreload}::NotSet |
 * | {@see setCrossOrigin() CrossOrigin} | `crossorigin` | '' |
 * Accessibility:
 * - ShowControls defaults to true, unlike the HTML attribute, because the
 *   browser's controls are the keyboard and screen reader access to play,
 *   pause, volume, and captions. A page that sets it false provides its own
 *   labeled controls. Chromium and Firefox put a media element with controls in
 *   the tab order; Safari does not.
 * - Recorded video with sound has a `Captions` {@see TTextTrack} (WCAG 1.2.2).
 *   Recorded audio has a transcript (WCAG 1.2.1), and video an audio
 *   description or a transcript (WCAG 1.2.3, 1.2.5).
 * - Sound that plays on its own for more than three seconds has a control to
 *   stop it (WCAG 1.4.2), and motion that lasts more than five seconds a control
 *   to pause it (WCAG 2.2.2). With ShowControls, the browser's controls meet
 *   both. AutoPlay with Loop and without ShowControls needs a pause control on
 *   the page. Browsers block AutoPlay with sound unless Muted.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseMedia extends THtmlElement
{
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
	 * @return bool whether the browser's playback controls show. Defaults to true.
	 */
	public function getShowControls()
	{
		return $this->getViewState('ShowControls', true);
	}

	/**
	 * @param bool $value whether the browser's playback controls show.
	 */
	public function setShowControls($value)
	{
		$this->setViewState('ShowControls', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether playback starts on its own. Defaults to false.
	 */
	public function getAutoPlay()
	{
		return $this->getViewState('AutoPlay', false);
	}

	/**
	 * @param bool $value whether playback starts on its own.
	 */
	public function setAutoPlay($value)
	{
		$this->setViewState('AutoPlay', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether playback restarts at the end. Defaults to false.
	 */
	public function getLoop()
	{
		return $this->getViewState('Loop', false);
	}

	/**
	 * @param bool $value whether playback restarts at the end.
	 */
	public function setLoop($value)
	{
		$this->setViewState('Loop', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the audio starts muted. Defaults to false.
	 */
	public function getMuted()
	{
		return $this->getViewState('Muted', false);
	}

	/**
	 * @param bool $value whether the audio starts muted.
	 */
	public function setMuted($value)
	{
		$this->setViewState('Muted', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the loading hint, a {@see TMediaPreload}. Defaults to NotSet.
	 */
	public function getPreload()
	{
		return $this->getViewState('Preload', TMediaPreload::NotSet);
	}

	/**
	 * @param string $value the loading hint, a {@see TMediaPreload}.
	 */
	public function setPreload($value)
	{
		$this->setViewState('Preload', TPropertyValue::ensureEnum($value, TMediaPreload::class), TMediaPreload::NotSet);
	}

	/**
	 * @return string the CORS mode of the media and track requests: '', 'anonymous', or
	 *   'use-credentials'. Defaults to ''.
	 */
	public function getCrossOrigin()
	{
		return $this->getViewState('CrossOrigin', '');
	}

	/**
	 * @param string $value the CORS mode of the media and track requests: '',
	 *   'anonymous', or 'use-credentials'.
	 * @throws TInvalidDataValueException if the value is not one of those.
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
	 * Adds the media attributes that are set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($src = $this->getSrc()) !== '') {
			$writer->addAttribute('src', $src);
		}
		foreach (['controls' => $this->getShowControls(), 'autoplay' => $this->getAutoPlay(),
			'loop' => $this->getLoop(), 'muted' => $this->getMuted()] as $name => $on) {
			if ($on) {
				$writer->addAttribute($name, $name);
			}
		}
		if (($preload = $this->getPreload()) !== TMediaPreload::NotSet) {
			$writer->addAttribute('preload', strtolower($preload));
		}
		if (($crossOrigin = $this->getCrossOrigin()) !== '') {
			$writer->addAttribute('crossorigin', $crossOrigin);
		}
	}
}
