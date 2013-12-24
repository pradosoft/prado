<?php
/**
 * TJuiResizable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TJuiResizable.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.JuiControls.TJuiControlAdapter');

/**
 * TJuiResizable class.
 *
 *
 * <code>
 * <com:TJuiResizable
 *     ID="resize1"
 *     Style="border: 1px solid green; width:100px;height:100px;background-color: #00dd00"
 *     Options.maxHeight="250"
 *     Options.maxWidth="350"
 *     Options.minHeight="150"
 *     Options.minWidth="200"
 * >
 * resize me
 * </com:TJuiResizable>
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @version $Id: TJuiResizable.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiResizable extends TActivePanel implements IJuiOptions
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
		return array('alsoResize', 'animate', 'animateDuration', 'animateEasing', 'aspectRatio', 'autoHide', 'cancel', 'containment', 'delay', 'disabled', 'distance', 'ghost', 'grid', 'handles', 'helper', 'maxHeight', 'maxWidth', 'minHeight', 'minWidth');
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
		$code="jQuery('#".$this->getClientId()."').resizable(".$options.");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}
}
