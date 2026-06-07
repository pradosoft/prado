<?php

/**
 * TWebControlRenderTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;

/**
 * TWebControlRenderTrait provides rendering helpers for web-control test cases.
 *
 * Each method creates a fresh {@see THtmlWriter} backed by a {@see TTextWriter},
 * invokes the corresponding render method on the given control, and returns
 * the captured HTML string.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TWebControlRenderTrait
{
	/**
	 * Renders the full control (open tag, contents, close tag) and returns the HTML string.
	 *
	 * @param \Prado\Web\UI\TControl $control the control to render
	 * @return string the rendered HTML
	 */
	protected function render($control): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	/**
	 * Renders the opening tag and returns the HTML string.
	 *
	 * @param \Prado\Web\UI\WebControls\TWebControl $control the control to render
	 * @return string the rendered opening tag HTML
	 */
	protected function renderBeginTag($control): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderBeginTag($writer);
		return $tw->flush();
	}

	/**
	 * Renders the body content (children) and returns the HTML string.
	 *
	 * @param \Prado\Web\UI\WebControls\TWebControl $control the control to render
	 * @return string the rendered contents HTML
	 */
	protected function renderContents($control): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderContents($writer);
		return $tw->flush();
	}

	/**
	 * Renders the closing tag and returns the HTML string.
	 *
	 * @param \Prado\Web\UI\WebControls\TWebControl $control the control to render
	 * @return string the rendered closing tag HTML
	 */
	protected function renderEndTag($control): string
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderEndTag($writer);
		return $tw->flush();
	}
}
