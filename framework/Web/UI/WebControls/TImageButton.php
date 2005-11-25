<?php
/**
 * TImageButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TImage class file
 */
Prado::using('System.Web.UI.WebControls.TImage');

/**
 * TImageButton class
 *
 * TImageButton displays an image on the Web page and responds to mouse clicks on the image.
 * It is similar to the TButton component except that the TImageButton also captures the
 * coordinates where the image is clicked.
 *
 * Write a <b>OnClick</b> event handler to programmatically determine the coordinates
 * where the image is clicked. The coordinates can be accessed through <b>x</b> and <b>y</b>
 * properties of the event parameter which is of type <b>TImageClickEventParameter</b>.
 * Note the origin (0, 0) is located at the upper left corner of the image.
 *
 * Write a <b>OnCommand</b> event handler to make the TImageButton component behave
 * like a command button. A command name can be associated with the component by using
 * the <b>CommandName</b> property. The <b>CommandParameter</b> property
 * can also be used to pass additional information about the command,
 * such as specifying ascending order. This allows multiple TImageButton components to be placed
 * on the same Web page. In the event handler, you can also determine
 * the <b>CommandName</b> property value and the <b>CommandParameter</b> property value
 * through <b>name</b> and <b>parameter</b> of the event parameter which is of
 * type <b>TCommandEventParameter</b>.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */

class TImageButton extends TImage implements IPostBackDataHandler, IPostBackEventHandler
{
	private $_x=0;
	private $_y=0;

	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return 'input';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		$writer->addAttribute('type','image');
		if(($uniqueID=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uniqueID);

		$onclick='';
		if($this->getEnabled(true))
		{
			$onclick=$this->getOnClientClick();
			if($onclick!=='')
				$onclick=rtrim($onclick,';').';';
			$onclick.=$page->getClientScript()->getPostBackEventReference($this->getPostBackOptions());
		}
		else if($this->getEnabled())   // in this case, parent will not render 'disabled'
			$writer->addAttribute('disabled','disabled');
		if($onclick!=='')
			$writer->addAttribute('onclick','javascript:'.$onclick);
		parent::addAttributesToRender($writer);
	}

	/**
	 * Returns postback specifications for the button.
	 * This method is used by framework and control developers.
	 * @return TPostBackOptions parameters about how the button defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options=new TPostBackOptions($this);
		$options->ClientSubmit=false;
		$page=$this->getPage();
		if($this->getCausesValidation() && $page->getValidators($this->getValidationGroup())->getCount()>0)
		{
			$options->PerformValidation=true;
			$options->ValidationGroup=$this->getValidationGroup();
		}
		if($this->getPostBackUrl()!=='')
			$options->ActionUrl=$this->getPostBackUrl();
		return $options;
	}

	/**
	 * This method checks if the TImageButton is clicked and loads the coordinates of the clicking position.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		$uid=$this->getUniqueID();
		if(isset($values["{$uid}_x"]) && isset($values["{$uid}_y"]))
		{
			$this->_x=intval($values["{$uid}_x"]);
			$this->_y=intval($values["{$uid}_y"]);
			$page=$this->getPage()->registerRequiresRaiseEvent($this);
		}
		return false;
	}

	/**
	 * Raises postback event.
	 * The implementation of this function should raise appropriate event(s) (e.g. OnClick, OnCommand)
	 * indicating the component is responsible for the postback event.
	 * This method is primarily used by framework developers.
	 * @param string the parameter associated with the postback event
	 */
	public function raisePostBackEvent($param)
	{
		if($this->getCausesValidation())
			$this->getPage()->validate($this->getValidationGroup());
		$this->onClick(new TImageClickEventParameter($this->_x,$this->_y));
		$this->onCommand(new TCommandEventParameter($this->getCommandName(),$this->getCommandParameter()));
	}

	/**
	 * A dummy implementation for the IPostBackDataHandler interface.
	 */
	public function raisePostDataChangedEvent()
	{
		// no post data to handle
	}

	/**
	 * This method is invoked when the component is clicked.
	 * The method raises 'Click' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onClick($param)
	{
		$this->raiseEvent('Click',$this,$param);
	}

	/**
	 * This method is invoked when the component is clicked.
	 * The method raises 'Command' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TCommandEventParameter event parameter to be passed to the event handlers
	 */
	public function onCommand($param)
	{
		$this->raiseEvent('Command',$this,$param);
		$this->raiseBubbleEvent($this,$param);
	}

	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getPage()->registerRequiresPostBack($this);
	}

	/**
	 * @return string the command name associated with the <b>OnCommand</b> event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName','');
	}

	/**
	 * Sets the command name associated with the <b>OnCommand</b> event.
	 * @param string the text caption to be set
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName',$value,'');
	}

	/**
	 * @return string the parameter associated with the <b>OnCommand</b> event
	 */
	public function getCommandParameter()
	{
		return $this->getViewState('CommandParameter','');
	}

	/**
	 * Sets the parameter associated with the <b>OnCommand</b> event.
	 * @param string the text caption to be set
	 */
	public function setCommandParameter($value)
	{
		$this->setViewState('CommandParameter',$value,'');
	}

	/**
	 * @return boolean whether postback event trigger by this button will cause input validation
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this button will cause input validation.
	 * @param string the text caption to be set
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',$value,true);
	}

	/**
	 * @return string the group of validators which the button causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the button causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * @return string the URL of the page to post to when the button is clicked, default is empty meaning post to the current page itself
	 */
	public function getPostBackUrl()
	{
		return $this->getViewState('PostBackUrl','');
	}

	/**
	 * @param string the URL of the page to post to from the current page when the button is clicked, empty if post to the current page itself
	 */
	public function setPostBackUrl($value)
	{
		$this->setViewState('PostBackUrl',$value,'');
	}

	/**
	 * @return string the javascript to be executed when the button is clicked
	 */
	public function getOnClientClick()
	{
		return $this->getViewState('OnClientClick','');
	}

	/**
	 * @param string the javascript to be executed when the button is clicked. Do not prefix it with "javascript:".
	 */
	public function setOnClientClick($value)
	{
		$this->setViewState('OnClientClick',$value,'');
	}

	/**
	 * @return string caption of the button
	 */
	public function getText()
	{
		return $this->getAlternateText();
	}

	/**
	 * @param string caption of the button
	 */
	public function setText($value)
	{
		$this->setAlternateText($value);
	}
}

/**
 * TImageClickEventParameter class
 *
 * TImageClickEventParameter encapsulates the parameter data for <b>OnClick</b>
 * event of TImageButton components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TImageClickEventParameter extends TEventParameter
{
	/**
	 * the X coordinate of the clicking point
	 * @var integer
	 */
	public $x=0;
	/**
	 * the Y coordinate of the clicking point
	 * @var integer
	 */
	public $y=0;
}

?>