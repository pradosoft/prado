<?php

/**
 * TWebControlAdapter class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWebControlAdapter class
 *
 * TWebControlAdapter is the base class for adapters that customize
 * rendering for the Web control to which the adapter is attached.
 * It may be used to modify the default markup or behavior for specific
 * browsers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method \Prado\Web\UI\WebControls\TWebControl getControl();
 */
class TWebControlAdapter extends \Prado\Web\UI\TControlAdapter
{
	/**
	 * Renders the control to which the adapter is attached.
	 * It calls {@see renderBeginTag}, {@see renderContents} and
	 * {@see renderEndTag} in order.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		$this->renderBeginTag($writer);
		$this->renderContents($writer);
		$this->renderEndTag($writer);
	}

	/**
	 * Renders the openning tag for the attached control.
	 * Default implementation calls the attached control's corresponding method.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->getControl()->renderBeginTag($writer);
	}

	/**
	 * Renders the body contents within the attached control tag.
	 * Default implementation calls the attached control's corresponding method.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$this->getControl()->renderContents($writer);
	}

	/**
	 * Renders the closing tag for the attached control.
	 * Default implementation calls the attached control's corresponding method.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		$this->getControl()->renderEndTag($writer);
	}
}
