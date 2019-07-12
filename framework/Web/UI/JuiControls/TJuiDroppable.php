<?php
/**
 * TJuiDroppable class file.
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
 * TJuiDroppable class.
 *
 * TJuiDroppable is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/droppable/ Droppable} interaction.
 * When a {@link TJuiDraggable} is dropped over a TJuiDroppable panel, the
 * {@link onDrop OnDrop} event will be triggered. The event hanler will receive
 * a {@link TJuiEventParameter} object containing a reference to the dropped control
 * in the <tt>DraggableControl</tt> property.
 *
 * <code>
 * <com:TJuiDraggable
 * 	ID="drag1"
 * 	Style="border: 1px solid red; width:100px;height:100px;background-color: #fff"
 * >
 * drag me
 * </com:TJuiDraggable>
 *
 * <com:TJuiDroppable
 * 	ID="drop1"
 * 	Style="border: 1px solid blue; width:600px;height:600px; background-color: lime"
 * 	OnDrop="drop1_ondrop"
 * >
 * drop it over me
 * </com:TJuiDroppable>
 * </code>
 *
 * <code>
 *	public function drop1_ondrop($sender, $param)
 *	{
 * 		$draggable=$param->DraggableControl;
 *		$offset=$param->getCallbackParameter()->offset;
 *		$target=$param->getCallbackParameter()->target->offset;
 *		$top=$offset->top - $target->top;
 *		$left=$offset->left - $target->left;
 *		$this->label1->Text="Dropped ".$draggable->ID." at: <br/>Top=".$top." Left=".$left;
 *	}
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiDroppable extends TActivePanel implements IJuiOptions, ICallbackEventHandler
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
		return 'droppable';
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
		return ['accept', 'activeClass', 'addClasses', 'disabled', 'greedy', 'hoverClass', 'scope', 'tolerance'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return ['activate', 'create', 'deactivate', 'drop', 'out', 'over'];
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
	 * Raises the OnActivate event
	 * @param object $params event parameters
	 */
	public function onActivate($params)
	{
		$this->raiseEvent('OnActivate', $this, $params);
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
	 * Raises the OnDeactivate event
	 * @param object $params event parameters
	 */
	public function onDeactivate($params)
	{
		$this->raiseEvent('OnDeactivate', $this, $params);
	}

	/**
	 * Raises the OnDrop event
	 * @param object $params event parameters
	 */
	public function onDrop($params)
	{
		$this->raiseEvent('OnDrop', $this, $params);
	}

	/**
	 * Raises the OnOut event
	 * @param object $params event parameters
	 */
	public function OnOut($params)
	{
		$this->raiseEvent('OnOut', $this, $params);
	}

	/**
	 * Raises the OnOver event
	 * @param object $params event parameters
	 */
	public function OnOver($params)
	{
		$this->raiseEvent('OnOver', $this, $params);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */

	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}
}
