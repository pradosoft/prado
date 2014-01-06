<?php
/**
 * TJuiDraggable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.JuiControls.TJuiControlAdapter');

/**
 * TJuiDraggable class.
 *
 *
 * <code>
 * <com:TJuiDraggable
 *	ID="drag1"
 *	Style="border: 1px solid red; width:100px;height:100px"
 *	Options.Axis="y"
 * >
 * drag me
 * </com:TJuiDraggable>
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiDraggable extends TActivePanel implements IJuiOptions
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
		return $options;
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
		$code="jQuery('#".$this->getClientId()."').draggable(".$options.");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}
}
