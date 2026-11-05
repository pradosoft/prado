<?php

/**
 * TCanvasClientScript class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TClientSideOptions;

/**
 * TCanvasClientScript class.
 * TCanvasClientScript holds the client-side event of a {@see TCanvas}, set through
 * its {@see TCanvas::getClientSide() ClientSide} property.
 * {@see setOnDraw() OnDraw} is JavaScript that draws the canvas. It runs as
 * `function(sender, parameter)`, where `sender` is the `Prado.WebUI.TCanvas` and
 * `parameter` has:
 * - `canvas` → the `<canvas>` element.
 * - `context` → the drawing context of {@see TCanvas::setContextType() ContextType}.
 * - `width`, `height` → the drawing size: CSS pixels when
 *   {@see TCanvas::setMatchDisplaySize() MatchDisplaySize} is true, otherwise the
 *   bitmap size.
 * - `pixelRatio` → the bitmap pixels per drawing unit.
 * - `reducedMotion` → true when the user prefers reduced motion.
 * ```html
 * <com:TCanvas AlternateText="A blue square" ClientSide.OnDraw="
 *     parameter.context.fillStyle = 'blue';
 *     parameter.context.fillRect(0, 0, parameter.width / 2, parameter.height / 2);" />
 * ```
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCanvasClientScript extends TClientSideOptions
{
	/**
	 * @param string $javascript the JavaScript that draws the canvas.
	 */
	public function setOnDraw($javascript)
	{
		$this->setFunction('onDraw', $javascript);
	}

	/**
	 * @return null|\Prado\Web\Javascripts\TJavaScriptLiteral the JavaScript function
	 *   that draws the canvas, or null when it is not set.
	 */
	public function getOnDraw()
	{
		return $this->getOption('onDraw');
	}
}
