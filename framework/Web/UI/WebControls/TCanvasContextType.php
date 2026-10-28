<?php

/**
 * TCanvasContextType class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TCanvasContextType class.
 * TCanvasContextType defines the drawing contexts that a {@see TCanvas} creates
 * for its {@see TCanvasClientScript::setOnDraw() OnDraw} handler.
 * - Context2D → `getContext('2d')`, scaled to the device pixel ratio when
 *   {@see TCanvas::setMatchDisplaySize() MatchDisplaySize} is true.
 * - WebGL → `getContext('webgl')`.
 * - WebGL2 → `getContext('webgl2')`.
 * - BitmapRenderer → `getContext('bitmaprenderer')`.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCanvasContextType extends \Prado\TEnumerable
{
	public const Context2D = 'Context2D';
	public const WebGL = 'WebGL';
	public const WebGL2 = 'WebGL2';
	public const BitmapRenderer = 'BitmapRenderer';
}
