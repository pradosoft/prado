<?php
/**
 * TRadioButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Using TCheckBox parent class
 */
Prado::using('System.Web.UI.WebControls.TCheckBox');
// using TRadioButtonList ??
/**
 * TRadioButton class
 *
 * TRadioButton displays a radio button on the page.
 * You can specify the caption to display beside the radio buttonby setting
 * the {@link setText Text} property.  The caption can appear either on the right
 * or left of the radio button, which is determined by the {@link setTextAlign TextAlign}
 * property.
 *
 * To determine whether the TRadioButton component is checked, test the {@link getChecked Checked}
 * property. The {@link onCheckedChanged CheckedChanged} event is raised when
 * the {@link getChecked Checked} state of the TRadioButton component changes
 * between posts to the server. You can provide an event handler for
 * the {@link onCheckedChanged CheckedChanged} event to  to programmatically
 * control the actions performed when the state of the TRadioButton component changes
 * between posts to the server.
 *
 * TRadioButton uses {@link setGroupName GroupName} to group together a set of radio buttons.
 *
 * If {@link setAutoPostBack AutoPostBack} is set true, changing the radio button state
 * will cause postback action. And if {@link setCausesValidation CausesValidation}
 * is true, validation will also be processed, which can be further restricted within
 * a {@link setValidationGroup ValidationGroup}.
 *
 * Note, {@link setText Text} is rendered as is. Make sure it does not contain unwanted characters
 * that may bring security vulnerabilities.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRadioButton extends TCheckBox
{
	/**
	 * @var string the name used to fetch radiobutton post data
	 */
	private $_uniqueGroupName=null;

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values)
	{
		$uniqueGroupName=$this->getUniqueGroupName();
		$value=isset($values[$uniqueGroupName])?$values[$uniqueGroupName]:null;
		if($value!==null && $value===$this->getValueAttribute())
		{
			if(!$this->getChecked())
			{
				$this->setChecked(true);
				return true;
			}
			else
				return false;
		}
		else if($this->getChecked())
			$this->setChecked(false);
		return false;
	}

	/**
	 * @return string the name of the group that the radio button belongs to. Defaults to empty.
	 */
	public function getGroupName()
	{
		return $this->getViewState('GroupName','');
	}

	/**
	 * Sets the name of the group that the radio button belongs to
	 * @param string the group name
	 */
	public function setGroupName($value)
	{
		$this->setViewState('GroupName',$value,'');
	}

	/**
	 * @return string the name used to fetch radiobutton post data
	 */
	private function getUniqueGroupName()
	{
		if($this->_uniqueGroupName===null)
		{
			$groupName=$this->getGroupName();
			$uniqueID=$this->getUniqueID();
			if($uniqueID!=='')
			{
				if(($pos=strrpos($uniqueID,TControl::ID_SEPARATOR))!==false)
				{
					if($groupName!=='')
						$groupName=substr($uniqueID,0,$pos+1).$groupName;
					else if($this->getNamingContainer() instanceof TRadioButtonList)
						$groupName=substr($uniqueID,0,$pos);
				}
				if($groupName==='')
					$groupName=$uniqueID;
			}
			$this->_uniqueGroupName=$groupName;
		}
		return $this->_uniqueGroupName;
	}

	/**
	 * @return string the value attribute to be rendered
	 */
	private function getValueAttribute()
	{
		if(($value=$this->getAttribute('value'))===null)
		{
			$value=$this->getID();
			return $value===''?$this->getUniqueID():$value;
		}
		else
			return $value;
	}

	/**
	 * Renders a radiobutton input element.
	 * @param THtmlWriter the writer for the rendering purpose
	 * @param string checkbox id
	 * @param string onclick attribute value for the checkbox
	 */
	protected function renderInputTag($writer,$clientID,$onclick)
	{
		if($clientID!=='')
			$writer->addAttribute('id',$clientID);
		$writer->addAttribute('type','radio');
		$writer->addAttribute('name',$this->getUniqueGroupName());
		$writer->addAttribute('value',$this->getValueAttribute());
		if($this->getChecked())
			$writer->addAttribute('checked','checked');
		if(!$this->getEnabled(true))
			$writer->addAttribute('disabled','disabled');

		$page=$this->getPage();
		if($this->getAutoPostBack() && $page->getClientSupportsJavaScript())
		{
			$options = $this->getAutoPostBackOptions();
			$scripts = $page->getClientScript();
			$postback = $scripts->getPostBackEventReference($this,'',$options,false);
			$scripts->registerClientEvent($this, "click", $postback);
		}

		if(($accesskey=$this->getAccessKey())!=='')
			$writer->addAttribute('accesskey',$accesskey);
		if(($tabindex=$this->getTabIndex())>0)
			$writer->addAttribute('tabindex',"$tabindex");
		if($attributes=$this->getViewState('InputAttributes',null))
			$writer->addAttributes($attributes);
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}
}

?>