<?php
/**
 * TJuiDraggable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActivePanel;

/**
 * TJuiDraggable class.
 *
 * TJuiDraggable is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/draggable/ Draggable} interaction.
 * The panel can be moved using the mouse, and eventually dropped over a
 * {@link TJuiDroppable}.
 *
 * <code>
 * <com:TJuiDraggable
 *	ID="drag1"
 *	Style="border: 1px solid red; width:100px;height:100px"
 *	Options.Axis="y"
 *  OnStop="drag1_dragged"
 * >
 * drag me
 * </com:TJuiDraggable>
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiDraggable extends TActivePanel implements IJuiOptions, ICallbackEventHandler
{
	protected $_options;

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
		return 'draggable';
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
		return ['addClasses', 'appendTo', 'axis', 'cancel', 'connectToSortable', 'containment', 'cursor', 'cursorAt', 'delay', 'disabled', 'distance', 'grid', 'handle', 'helper', 'iframeFix', 'opacity', 'refreshPositions', 'revert', 'revertDuration', 'scope', 'scroll', 'scrollSensitivity', 'scrollSpeed', 'snap', 'snapMode', 'snapTolerance', 'stack', 'zIndex'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return ['create', 'drag', 'start', 'stop'];
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
	 * Raises the OnCreate event
	 * @param object $params event parameters
	 */
	public function onCreate($params)
	{
		$this->raiseEvent('OnCreate', $this, $params);
	}

	/**
	 * Raises the OnDrag event
	 * @param object $params event parameters
	 */
	public function onDrag($params)
	{
		$this->raiseEvent('OnDrag', $this, $params);
	}

	/**
	 * Raises the OnStart event
	 * @param object $params event parameters
	 */
	public function onStart($params)
	{
		$this->raiseEvent('OnStart', $this, $params);
	}

	/**
	 * Raises the OnStop event
	 * @param object $params event parameters
	 */
	public function onStop($params)
	{
		$this->raiseEvent('OnStop', $this, $params);
	}
}
