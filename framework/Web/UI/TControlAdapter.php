<?php
/**
 * TControlAdapter class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TControlAdapter class
 *
 * TControlAdapter is the base class for adapters that customize
 * various behaviors for the control to which the adapter is attached.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TControlAdapter extends \Prado\TApplicationComponent
{
	/**
	 * @var TControl the control to which the adapter is attached
	 */
	protected $_control;

	/**
	 * Constructor.
	 * @param TControl $control the control to which the adapter is attached
	 */
	public function __construct($control)
	{
		$this->_control = $control;
	}

	/**
	 * @return TControl the control to which this adapter is attached
	 */
	public function getControl()
	{
		return $this->_control;
	}

	/**
	 * @return TPage the page that contains the attached control
	 */
	public function getPage()
	{
		return $this->_control ? $this->_control->getPage() : null;
	}

	/**
	 * Creates child controls for the attached control.
	 * Default implementation calls the attached control's corresponding method.
	 */
	public function createChildControls()
	{
		$this->_control->createChildControls();
	}

	/**
	 * Loads additional persistent control state.
	 * Default implementation calls the attached control's corresponding method.
	 */
	public function loadState()
	{
		$this->_control->loadState();
	}

	/**
	 * Saves additional persistent control state.
	 * Default implementation calls the attached control's corresponding method.
	 */
	public function saveState()
	{
		$this->_control->saveState();
	}

	/**
	 * This method is invoked when the control enters 'OnInit' stage.
	 * Default implementation calls the attached control's corresponding method.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onInit($param)
	{
		$this->_control->onInit($param);
	}

	/**
	 * This method is invoked when the control enters 'OnLoad' stage.
	 * Default implementation calls the attached control's corresponding method.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onLoad($param)
	{
		$this->_control->onLoad($param);
	}

	/**
	 * This method is invoked when the control enters 'OnPreRender' stage.
	 * Default implementation calls the attached control's corresponding method.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onPreRender($param)
	{
		$this->_control->onPreRender($param);
	}

	/**
	 * This method is invoked when the control enters 'OnUnload' stage.
	 * Default implementation calls the attached control's corresponding method.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onUnload($param)
	{
		$this->_control->onUnload($param);
	}

	/**
	 * This method is invoked when the control renders itself.
	 * Default implementation calls the attached control's corresponding method.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		$this->_control->render($writer);
	}

	/**
	 * Renders the control's children.
	 * Default implementation calls the attached control's corresponding method.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	public function renderChildren($writer)
	{
		$this->_control->renderChildren($writer);
	}
}
