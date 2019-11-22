<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/*
 * Load common active control options.
 */
use Prado\Prado;
use Prado\Web\UI\TControlAdapter;
use Prado\TPropertyValue;

/**
 * TActiveControlAdapter class.
 *
 * Customize the parent TControl class for active control classes.
 * TActiveControlAdapter instantiates a common base active control class
 * throught the {@link getBaseActiveControl BaseActiveControl} property.
 * The type of BaseActiveControl can be provided in the second parameter in the
 * constructor. Default is TBaseActiveControl or TBaseActiveCallbackControl if
 * the control adapted implements ICallbackEventHandler.
 *
 * TActiveControlAdapter will tracking viewstate changes to update the
 * corresponding client-side properties.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveControlAdapter extends TControlAdapter
{
	/**
	 * @var string base active control class name.
	 */
	private $_activeControlType;
	/**
	 * @var TBaseActiveControl base active control instance.
	 */
	private $_baseActiveControl;
	/**
	 * @var TCallbackPageStateTracker view state tracker.
	 */
	private $_stateTracker;
	/**
	 * @var string view state tracker class.
	 */
	private $_stateTrackerClass = '\Prado\Web\UI\ActiveControls\TCallbackPageStateTracker';

	/**
	 * Constructor.
	 * @param IActiveControl $control active control to adapt.
	 * @param null|string $baseCallbackClass Base active control class name.
	 */
	public function __construct(IActiveControl $control, $baseCallbackClass = null)
	{
		parent::__construct($control);
		$this->setBaseControlClass($baseCallbackClass);
	}

	/**
	 * @param string $type base active control instance
	 */
	protected function setBaseControlClass($type)
	{
		if ($type === null) {
			if ($this->getControl() instanceof ICallbackEventHandler) {
				$this->_activeControlType = 'Prado\\Web\UI\\ActiveControls\\TBaseActiveCallbackControl';
			} else {
				$this->_activeControlType = 'Prado\\Web\UI\\ActiveControls\\TBaseActiveControl';
			}
		} else {
			$this->_activeControlType = $type;
		}
	}

	/**
	 * Publish the ajax script
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
	}

	/**
	 * Renders the callback client scripts.
	 * @param mixed $writer
	 */
	public function render($writer)
	{
		$this->getPage()->getClientScript()->registerPradoScript('ajax');
		if ($this->_control->getVisible(false)) {
			parent::render($writer);
		} else {
			$writer->write("<span id=\"" . $this->_control->getClientID() . "\" style=\"display:none\"></span>");
		}
	}

	/**
	 * @param TBaseActiveControl $control change base active control
	 */
	public function setBaseActiveControl($control)
	{
		$this->_baseActiveControl = $control;
	}

	/**
	 * @return TBaseActiveControl Common active control options.
	 */
	public function getBaseActiveControl()
	{
		if ($this->_baseActiveControl === null) {
			$type = $this->_activeControlType;
			$this->_baseActiveControl = new $type($this->getControl());
		}
		return $this->_baseActiveControl;
	}

	/**
	 * @return bool true if the viewstate needs to be tracked.
	 */
	protected function getIsTrackingPageState()
	{
		if ($this->getPage()->getIsCallback()) {
			$target = $this->getPage()->getCallbackEventTarget();
			if ($target instanceof ICallbackEventHandler) {
				$client = $target->getActiveControl()->getClientSide();
				return $client->getEnablePageStateUpdate();
			}
		}
		return false;
	}

	/**
	 * Starts viewstate tracking if necessary after when controls has been loaded
	 * @param mixed $param
	 */
	public function onLoad($param)
	{
		if ($this->getIsTrackingPageState()) {
			$stateTrackerClass = $this->_stateTrackerClass;
			$this->_stateTracker = new $stateTrackerClass($this->getControl());
			$this->_stateTracker->trackChanges();
		}
		parent::onLoad($param);
	}

	/**
	 * Saves additional persistent control state. Respond to viewstate changes
	 * if necessary.
	 */
	public function saveState()
	{
		if (($this->_stateTracker !== null)
			&& $this->getControl()->getActiveControl()->canUpdateClientSide(true)) {
			$this->_stateTracker->respondToChanges();
		}
		parent::saveState();
	}

	/**
	 * @return TCallbackPageStateTracker state tracker.
	 */
	public function getStateTracker()
	{
		return $this->_stateTracker;
	}

	/**
	 * @param string $value state tracker class.
	 */
	public function setStateTracker($value)
	{
		$this->_stateTrackerClass = TPropertyValue::ensureString($value);
	}
}
