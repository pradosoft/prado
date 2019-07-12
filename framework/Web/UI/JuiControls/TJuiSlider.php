<?php
/**
 * TJuiSlider class file.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * TJuiSlider class.
 *
 * TJuiSlider is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/slider/ Slider} widget.
 *
 * <code>
 * <com:TJuiSlider
 *	ID="slider1"
 *	Options.Min="0"
 *  Options.Max="100"
 *  Options.Step="10"
 *	Options.Value="50"
 *  OnStop="slider1_changed"
 * />
 * </code>
 *
 * To retrieve the current value of the slider during callback, get the value
 * property of the callback request parameter within the {@link TJuiEventParameter}.
 *
 * <code>
 * public function slider1_changed($sender, $param) {
 *   $value = $param->getCallbackParameter()->value;
 * }
 * </code>
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\JuiControls
 * @since 4.0
 */
class TJuiSlider extends TActivePanel implements IJuiOptions, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TJuiControlAdapter($this));
	}

	/**
	 * @return string the name of the jQueryUI widget method
	 */
	public function getWidget()
	{
		return 'slider';
	}

	/**
	 * @return string the clientid of the jQueryUI widget element
	 */
	public function getWidgetID()
	{
		return $this->getClientID();
	}

	/**
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if (($options = $this->getViewState('JuiOptions')) === null) {
			$options = new TJuiControlOptions($this);
			$this->setViewState('JuiOptions', $options);
		}
		return $options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return ['animate', 'classes', 'disabled', 'max', 'min', 'orientation', 'range', 'step', 'value', 'values'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return ['change', 'create', 'slide', 'start', 'stop'];
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		return $this->getOptions()->toArray();
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);

		$writer->addAttribute('id', $this->getClientID());
		$options = TJavaScript::encode($this->getPostBackOptions());
		$cs = $this->getPage()->getClientScript();
		$code = "jQuery('#" . $this->getWidgetID() . "')." . $this->getWidget() . "(" . $options . ");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * Raises callback event. This method is required by the {@link ICallbackEventHandler}
	 * interface.
	 * @param TCallbackEventParameter $param the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		$this->getOptions()->raiseCallbackEvent($param);
	}

	/**
	 * Raises the OnChange event
	 * @param object $params event parameters
	 */
	public function onChange($params)
	{
		$this->raiseEvent('OnChange', $this, $params);
	}

	/**
	 * Raises the OnChange event
	 * @param object $params event parameters
	 */
	public function onSlide($params)
	{
		$this->raiseEvent('OnSlide', $this, $params);
	}

	/**
	 * Raises the OnChange event
	 * @param object $params event parameters
	 */
	public function onStart($params)
	{
		$this->raiseEvent('OnStart', $this, $params);
	}

	/**
	 * Raises the OnChange event
	 * @param object $params event parameters
	 */
	public function onStop($params)
	{
		$this->raiseEvent('OnStop', $this, $params);
	}

	/**
	 * Raises the OnCreate event
	 * @param object $params event parameters
	 */
	public function onCreate($params)
	{
		$this->raiseEvent('OnCreate', $this, $params);
	}
}
