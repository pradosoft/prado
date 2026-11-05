<?php

/**
 * TTextTrackKind class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTextTrackKind class.
 * TTextTrackKind defines the `kind` of a {@see TTextTrack}.
 * - Subtitles → a translation of the dialogue, for viewers who hear the audio.
 * - Captions → the dialogue, sound effects, and speaker names, for viewers who
 *   do not hear the audio (WCAG 1.2.2, 1.2.4).
 * - Descriptions → text descriptions of the video, for viewers who do not see it.
 * - Chapters → chapter titles for navigation.
 * - Metadata → data for script, not shown.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTextTrackKind extends \Prado\TEnumerable
{
	public const Subtitles = 'Subtitles';
	public const Captions = 'Captions';
	public const Descriptions = 'Descriptions';
	public const Chapters = 'Chapters';
	public const Metadata = 'Metadata';
}
