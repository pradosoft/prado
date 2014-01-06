<?php
/**
 * TJuiDroppable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TJuiDroppable.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.JuiControls.TJuiControlAdapter');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');

/**
 * TJuiDroppable class.
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
 *		$draggable=$param->getDroppedControl()->ID;
 *		$this->drop1->Controls->clear();
 *		$this->drop1->Controls->add("Dropped ".$draggable." at: <br/>Top=".$param->getOffsetTop()." Left=".$param->getOffsetLeft());
 *		// it's still an active panel, after all
 *		$this->drop1->render($param->NewWriter);
 *	}
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @version $Id: TJuiDroppable.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiDroppable extends TActivePanel implements IJuiOptions, ICallbackEventHandler
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
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		static $options;
		if($options===null)
			$options=new TJuiControlOptions($this);
		return $options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return array('addClasses', 'appendTo', 'axis', 'cancel', 'connectToSortable', 'containment', 'cursor', 'cursorAt', 'delay', 'disabled', 'distance', 'grid', 'handle', 'helper', 'iframeFix', 'opacity', 'refreshPositions', 'revert', 'revertDuration', 'scope', 'scroll', 'scrollSensitivity', 'scrollSpeed', 'snap', 'snapMode', 'snapTolerance', 'stack', 'zIndex');
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();
		$options['drop'] = new TJavaScriptLiteral("function( event, ui ) { Prado.Callback(".TJavascript::encode($this->getUniqueID()).", { 'offset' : { 'left' : ui.offset.left - $(this).offset().left, 'top' : ui.offset.top - $(this).offset().top }, 'position' : ui.position, 'draggable' : ui.draggable.get(0).id }) }");
		return $options;
	}

	/**
	 * Raises callback event. This method is required bu {@link ICallbackEventHandler}
	 * interface.
	 * It raises the {@link onDrop OnDrop} event, then, the {@link onCallback OnCallback} event
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		$this->onDrop($param->getCallbackParameter());
		$this->onCallback($param);
	}

	/**
	 * Raises the onDrop event.
	 * The drop parameters are encapsulated into a {@link TDropContainerEventParameter}
	 *
	 * @param object $dropControlId
	 */
	public function onDrop ($dropParams)
	{
		$this->raiseEvent('OnDrop', $this, new TJuiDroppableEventParameter ($this->getResponse(), $dropParams));

	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);

		$writer->addAttribute('id',$this->getClientID());
		$options=TJavascript::encode($this->getPostBackOptions());
		$cs=$this->getPage()->getClientScript();
		$code="jQuery('#".$this->getClientId()."').droppable(".$options.");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}
}

/**
 * TJuiDroppableEventParameter class
 *
 * TJuiDroppableEventParameter encapsulate the parameter
 * data for <b>OnDrop</b> event of TJuiDroppable components
 *
 * @author Christophe BOULAIN (Christophe.Boulain@ceram.fr)
 * @copyright Copyright &copy; 2008, PradoSoft
 * @license http://www.pradosoft.com/license
 * @version $Id: TDropContainer.php 3285 2013-04-11 07:28:07Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 */
class TJuiDroppableEventParameter extends TCallbackEventParameter
{
	public function getDragElementId()		{ return $this->getCallbackParameter()->draggable; }
	public function getPositionTop()		{ return $this->getCallbackParameter()->position->top; }
	public function getPositionLeft()		{ return $this->getCallbackParameter()->position->left; }
	public function getOffsetTop()			{ return $this->getCallbackParameter()->offset->top; }
	public function getOffsetLeft()			{ return $this->getCallbackParameter()->offset->left; }

	/**
	 * GetDroppedControl
	 *
	 * Compatibility method to get the dropped control
	 * @return TControl dropped control, or null if not found
	 */
	 public function getDroppedControl()
	 {
		 $control=null;
		 $service=prado::getApplication()->getService();
		 if ($service instanceof TPageService)
		 {
			// Find the control
			// Warning, this will not work if you have a '_' in your control Id !
			$dropControlId=str_replace(TControl::CLIENT_ID_SEPARATOR,TControl::ID_SEPARATOR,$this->getCallbackParameter()->draggable);
			$control=$service->getRequestedPage()->findControl($dropControlId);
		 }
		 return $control;
	 }
}