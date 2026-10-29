<?php

/**
 * TBaseActiveControl and TBaseActiveCallbackControl class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TMap;
use Prado\TPropertyValue;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;

/**
 * TBaseActiveControl class provided additional basic property for every
 * active control. An instance of TBaseActiveControl or its decendent
 * TBaseActiveCallbackControl is created by {@see \Prado\Web\UI\ActiveControls\TActiveControlAdapter::getBaseActiveControl()}
 * method.
 *
 * The {@see setEnableUpdate EnableUpdate} property determines whether the active
 * control is allowed to update the contents of the client-side when the callback
 * response returns. The {@see setUpdateMode UpdateMode} property determines how a
 * control that renders again updates its markup: replaced, or morphed so the
 * existing nodes keep their listeners and focus.
 * ```html
 * <com:TActivePanel ID="Results" ActiveControl.UpdateMode="Morph">
 * ```
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @since 3.1
 */
class TBaseActiveControl extends \Prado\TComponent
{
	/**
	 * @var TMap map of active control options.
	 */
	private $_options;
	/**
	 * @var \Prado\Web\UI\TControl attached control.
	 */
	private $_control;

	/**
	 * Constructor. Attach a base active control to an active control instance.
	 * @param \Prado\Web\UI\TControl $control active control
	 */
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_options = new TMap();
		parent::__construct();
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
	 * @return \Prado\Web\UI\TControl the attached control.
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
	 * @return string how a callback response updates the control's markup, a
	 *   {@see TActiveUpdateMode}. Defaults to Default.
	 * @since 4.4.0
	 */
	public function getUpdateMode()
	{
		return $this->getOption('UpdateMode', TActiveUpdateMode::Default);
	}

	/**
	 * Sets how a callback response updates the control's markup. Morph keeps the
	 * existing nodes; Default follows the page's
	 * {@see \Prado\Web\UI\TPage::getCallbackUpdateMode CallbackUpdateMode}.
	 * A skin sets it for every control of a type.
	 * @param string $value a {@see TActiveUpdateMode}
	 * @since 4.4.0
	 */
	public function setUpdateMode($value)
	{
		$this->setOption('UpdateMode', TPropertyValue::ensureEnum($value, TActiveUpdateMode::class), TActiveUpdateMode::Default);
	}

	/**
	 * Returns whether a callback response morphs the control's markup:
	 * {@see getUpdateMode UpdateMode} is Morph, or Default on a page whose
	 * CallbackUpdateMode is Morph.
	 * @return bool whether the update morphs
	 * @since 4.4.0
	 */
	public function getIsMorphUpdate(): bool
	{
		$mode = $this->getUpdateMode();
		if ($mode === TActiveUpdateMode::Default && ($page = $this->getPage()) !== null) {
			$mode = $page->getCallbackUpdateMode();
		}
		return $mode === TActiveUpdateMode::Morph;
	}

	/**
	 * Returns true if callback response is allowed to update the browser contents.
	 * Is is true if the control is initilized, and is a callback request and
	 * the {@see setEnableUpdate EnableUpdate} property is true and
	 * the page is not loading post data.
	 * @param mixed $bDontRequireVisibility
	 * @return bool true if the callback response is allowed update
	 * client-side contents.
	 */
	public function canUpdateClientSide($bDontRequireVisibility = false)
	{
		return 	$this->getControl()->getHasChildInitialized()
				&& !$this->getPage()->getIsLoadingPostData()
				&& $this->getPage()->getIsCallback()
				&& $this->getEnableUpdate()
				&& ($bDontRequireVisibility || $this->getControl()->getVisible());
	}
}
