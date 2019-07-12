<?php
/**
 * TBaseActiveControl and TBaseActiveCallbackControl class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TMap;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\TControl;

/**
 * TBaseActiveControl class provided additional basic property for every
 * active control. An instance of TBaseActiveControl or its decendent
 * TBaseActiveCallbackControl is created by {@link TActiveControlAdapter::getBaseActiveControl()}
 * method.
 *
 * The {@link setEnableUpdate EnableUpdate} property determines wether the active
 * control is allowed to update the contents of the client-side when the callback
 * response returns.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TBaseActiveControl extends \Prado\TComponent
{
	/**
	 * @var TMap map of active control options.
	 */
	private $_options;
	/**
	 * @var TControl attached control.
	 */
	private $_control;

	/**
	 * Constructor. Attach a base active control to an active control instance.
	 * @param TControl $control active control
	 */
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_options = new TMap;
	}

	/**
	 * Sets a named options with a value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string $name option name.
	 * @param mixed $value new value.
	 * @param mixed $default default value.
	 * @return mixed options value.
	 */
	protected function setOption($name, $value, $default = null)
	{
		$value = ($value === null) ? $default : $value;
		if ($value !== null) {
			$this->_options->add($name, $value);
		}
	}

	/**
	 * Gets an option named value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string $name option name.
	 * @param mixed $default default value.
	 * @return mixed options value.
	 */
	protected function getOption($name, $default = null)
	{
		$item = $this->_options->itemAt($name);
		return ($item === null) ? $default : $item;
	}

	/**
	 * @return TMap active control options
	 */
	protected function getOptions()
	{
		return $this->_options;
	}

	/**
	 * @return TPage the page containing the attached control.
	 */
	protected function getPage()
	{
		return $this->_control->getPage();
	}

	/**
	 * @return TControl the attached control.
	 */
	protected function getControl()
	{
		return $this->_control;
	}

	/**
	 * @param bool $value true to allow fine grain callback updates.
	 */
	public function setEnableUpdate($value)
	{
		$this->setOption('EnableUpdate', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool true to allow fine grain callback updates.
	 */
	public function getEnableUpdate()
	{
		return $this->getOption('EnableUpdate', true);
	}

	/**
	 * Returns true if callback response is allowed to update the browser contents.
	 * Is is true if the control is initilized, and is a callback request and
	 * the {@link setEnableUpdate EnableUpdate} property is true and
	 * the page is not loading post data.
	 * @param mixed $bDontRequireVisibility
	 * @return bool true if the callback response is allowed update
	 * client-side contents.
	 */
	public function canUpdateClientSide($bDontRequireVisibility = false)
	{
		return 	$this->getControl()->getHasChildInitialized()
				&& $this->getPage()->getIsLoadingPostData() == false
				&& $this->getPage()->getIsCallback()
				&& $this->getEnableUpdate()
				&& ($bDontRequireVisibility || $this->getControl()->getVisible());
	}
}
