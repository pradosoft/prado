<?php

/**
 * TFetchPriority class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TFetchPriority class.
 *
 * TFetchPriority defines the HTML `fetchpriority` attribute of {@see TImage}, the
 * priority of its request relative to other images.
 * - NotSet → no `fetchpriority` attribute.
 * - High → `high`: for the largest image above the fold.
 * - Low → `low`: for images that matter less, such as off-screen carousel slides.
 * - Auto → `auto`: the browser decides.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFetchPriority extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const High = 'High';
	public const Low = 'Low';
	public const Auto = 'Auto';
}
