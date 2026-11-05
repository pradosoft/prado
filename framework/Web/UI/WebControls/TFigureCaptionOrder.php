<?php

/**
 * TFigureCaptionOrder class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TFigureCaptionOrder class
 *
 * TFigureCaptionOrder specifies where {@see TFigure} places the `<figcaption>`
 * auto-generated from its {@see TFigure::getCaption Caption} property.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFigureCaptionOrder extends \Prado\TEnumerable
{
	/**
	 * No auto-generated caption is rendered, even when the Caption property is set.
	 */
	public const None = 'None';

	/**
	 * The caption renders before the figure content.
	 */
	public const First = 'First';

	/**
	 * The caption renders after the figure content. This is the default.
	 */
	public const Last = 'Last';
}
