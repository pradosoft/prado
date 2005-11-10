<?php
/**
 * THiddenField class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * THiddenField class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THiddenField extends TControl implements IPostBackDataHandler
{
	/**
	 * @return string tag name of the hyperlink
	 */
	protected function getTagName()
	{
		return 'input';
	}

	public function focus()
	{
		throw new TInvalidOperationException('xxx');
	}

	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		$writer->addAttribute('type','hidden');
		if(($uid=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uid);
		if(($id=$this->getID())!=='')
			$writer->addAttribute('id',$id);
		if(($value=$this->getValue())!=='')
			$writer->addAttribute('value',$value);
	}

	/**
	 * @return string the value of the THiddenField
	 */
	public function getValue()
	{
		return $this->getViewState('Value','');
	}

	/**
	 * Sets the value of the THiddenField
	 * @param string the value to be set
	 */
	public function setValue($value)
	{
		$this->setViewState('Value',$value,'');
	}

	public function getEnableTheming()
	{
		return false;
	}

	public function setEnableTheming($value)
	{
		throw new TInvalidOperationException('no_theming_support');
	}

	public function setSkinID($value)
	{
		throw new TInvalidOperationException('no_theming_support');
	}

	/**
	 * Loads hidden field data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the component has been changed
	 */
	public function loadPostData($key,$values)
	{
		$value=$values[$key];
		if($value===$this->getValue())
			return false;
		else
		{
			$this->setValue($value);
			return true;
		}
	}

	/**
	 * Raises postdata changed event.
	 * This method calls {@link onValueChanged} method.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		$this->onValueChanged(new TEventParameter);
	}

	/**
	 * This method is invoked when the value of the <b>Value</b> property changes between posts to the server.
	 * The method raises 'ValueChanged' event to fire up the event delegates.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onValueChanged($param)
	{
		$this->raiseEvent('ValueChanged',$this,$param);
	}
}

?>