<?php

/**
 * TAudio class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAudio class
 * TAudio represents the HTML `<audio>` element, sound content.
 * ```html
 * <com:TAudio Preload="Metadata">
 *     <com:TMediaSource Src="talk.opus" Type="audio/ogg; codecs=opus" />
 *     <com:TMediaSource Src="talk.mp3" Type="audio/mpeg" />
 *     <a href="talk.mp3">Download the talk</a>
 * </com:TAudio>
 * ```
 * {@see TBaseMedia} documents the playback properties and accessibility.
 * Recorded audio has a transcript on the page or linked from it (WCAG 1.2.1).
 * The default tag is `audio`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAudio extends TBaseMedia
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'audio';
	}
}
